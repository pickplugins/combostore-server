<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Store-manager data for the dashboard's Store managers list and manager
 * page, and order assignment.
 *
 * A store manager works the orders assigned to them: cstore_orders.assign_to
 * is a JSON list of {id, name}, set on the order page, and get_orders shows a
 * store manager only the orders whose list contains them. What else they do
 * is read from authorship: products (cmb_product) and coupons they created,
 * and purchases they recorded (cstore_purchases.user_id).
 *
 * Order statuses, grouped:
 *  - DONE_STATUSES: completed, delivered;
 *  - VOID_STATUSES: cancelled, refunded, failed;
 *  - incomplete is an abandoned checkout, not an order to work;
 *  - anything else is open (pending, confirmed, processing, hold, scheduled...).
 *
 * Manager details that are not on a plain user (availability, area, shift,
 * capacity, notes) are user meta, prefixed cstore_manager_.
 *
 * Every route is POST with a Bearer JWT, administrators only (the Store
 * managers menu is administrators only, and managers should not edit peers).
 */
class ComboStoreStoreManagers
{
    const ADMIN_ROLES  = ['administrator'];
    const MANAGER_ROLE = 'store_manager';

    const DONE_STATUSES = ['completed', 'delivered'];
    const VOID_STATUSES = ['cancelled', 'canceled', 'refunded', 'failed'];
    const SKIP_STATUSES = ['incomplete'];

    const AVAILABILITY = ['on_duty', 'busy', 'off_duty'];

    /* Editable manager fields => [meta key, max length]. */
    const FIELDS = [
        'availability' => ['cstore_manager_availability', 20],
        'area'         => ['cstore_manager_area', 150],
        'shift'        => ['cstore_manager_shift', 100],
        'max_open'     => ['cstore_manager_max_open', 4],
        'notes'        => ['cstore_manager_notes', 2000],
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));

        /* A deleted manager comes off every order's assign_to list. */
        add_action('delete_user', array($this, 'on_delete_user'));
    }

    public function on_delete_user($user_id)
    {
        global $wpdb;
        $t = $this->orders_table();
        $user_id = (int) $user_id;
        foreach ((array) $wpdb->get_results("SELECT id, assign_to FROM $t o WHERE {$this->assigned_sql($user_id)}") as $row) {
            $list = json_decode((string) $row->assign_to, true);
            if (!is_array($list)) continue;
            $list = array_values(array_filter($list, function ($e) use ($user_id) {
                return is_array($e) && (int) ($e['id'] ?? 0) !== $user_id;
            }));
            $wpdb->update($t, ['assign_to' => wp_json_encode($list)], ['id' => (int) $row->id], ['%s'], ['%d']);
        }
    }

    public function register_routes()
    {
        foreach (['get_store_managers_summary', 'get_store_manager_overview', 'update_store_manager_details', 'assign_order'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }


    /* ---------------------------------------------------------------- */

    private function auth($request)
    {
        $token = $request->get_header('Authorization');
        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }
        try {
            $decoded = JWT::decode(str_replace('Bearer ', '', $token), new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded->sub ?? $decoded->user_id ?? $decoded->data->user->id ?? null;
        $user    = $user_id ? get_user_by('id', $user_id) : null;

        if (!$user || !array_intersect(self::ADMIN_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view store manager data', array('status' => 403));
        }
        return $user;
    }

    private function body($request)
    {
        $body = json_decode($request->get_body(), true);
        return is_array($body) ? $body : [];
    }

    private function respond($data)
    {
        die(wp_json_encode($data));
    }

    private function in_list(array $values)
    {
        return "'" . implode("','", array_map('esc_sql', $values)) . "'";
    }

    private function orders_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_orders';
    }

    /** SQL matching orders assigned to a user (ids were saved as numbers or strings). */
    private function assigned_sql($user_id, $alias = 'o')
    {
        $id = (int) $user_id;
        return "(JSON_VALID($alias.assign_to) AND (JSON_CONTAINS($alias.assign_to, '{\"id\":$id}') OR JSON_CONTAINS($alias.assign_to, '{\"id\":\"$id\"}')))";
    }

    /** SQL matching orders nobody is assigned to. */
    private function unassigned_sql($alias = 'o')
    {
        return "(NOT JSON_VALID($alias.assign_to) OR JSON_LENGTH($alias.assign_to) = 0)";
    }

    private function open_sql($alias = 'o')
    {
        $closed = $this->in_list(array_merge(self::DONE_STATUSES, self::VOID_STATUSES, self::SKIP_STATUSES));
        return "COALESCE($alias.status, '') NOT IN ($closed)";
    }

    /** The manager fields of one user. */
    private function details($user_id)
    {
        $out = [];
        foreach (self::FIELDS as $key => [$meta]) {
            $out[$key] = (string) get_user_meta($user_id, $meta, true);
        }
        if ($out['availability'] === '') $out['availability'] = 'on_duty';
        return $out;
    }

    private function order_columns($alias = 'o')
    {
        return "$alias.id, $alias.status, $alias.payment_status, $alias.total_amount, $alias.billing_name, $alias.billing_phone, $alias.created_at, $alias.updated_at, $alias.completed_at";
    }


    /* ---------------------------------------------------------------- */

    /**
     * Per-manager figures for the Store managers list. Body: ids (user ids, max 200).
     * Answers {managers: {id: {open, done_30, revenue_30, products, last_activity, ...details}},
     * unassigned: n}.
     */
    public function get_store_managers_summary($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->orders_table();
        $body = $this->body($request);
        $ids  = isset($body['ids']) && is_array($body['ids']) ? array_slice(array_values(array_filter(array_map('absint', $body['ids']))), 0, 200) : [];

        $unassigned = (int) $wpdb->get_var("SELECT COUNT(*) FROM $t o WHERE {$this->unassigned_sql()} AND {$this->open_sql()}");

        $since = gmdate('Y-m-d H:i:s', current_time('timestamp') - 30 * DAY_IN_SECONDS);
        $done  = $this->in_list(self::DONE_STATUSES);
        $open  = $this->open_sql();

        $out = [];
        foreach ($ids as $id) {
            $mine = $this->assigned_sql($id);
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT SUM($open) open_n,
                        SUM(o.status IN ($done) AND COALESCE(o.completed_at, o.updated_at) >= %s) done_30,
                        SUM(CASE WHEN o.status IN ($done) AND COALESCE(o.completed_at, o.updated_at) >= %s THEN o.total_amount ELSE 0 END) revenue_30,
                        MAX(o.updated_at) last_order
                 FROM $t o WHERE $mine",
                $since, $since
            ), ARRAY_A);

            $last_post = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(post_modified) FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ('cmb_product','coupon') AND post_status <> 'trash'",
                $id
            ));
            $products = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'cmb_product' AND post_status NOT IN ('trash','auto-draft')",
                $id
            ));
            $last = max((string) ($row['last_order'] ?? ''), (string) $last_post);

            $out[$id] = array_merge($this->details($id), [
                'open'          => (int) ($row['open_n'] ?? 0),
                'done_30'       => (int) ($row['done_30'] ?? 0),
                'revenue_30'    => round((float) ($row['revenue_30'] ?? 0), 2),
                'products'      => $products,
                'last_activity' => $last !== '' ? $last : null,
            ]);
        }

        $this->respond(['status' => 'success', 'managers' => $out ?: new \stdClass(), 'unassigned' => $unassigned]);
    }

    /**
     * Everything the manager page shows. Body: manager_id, from_date, to_date
     * (yyyy-mm-dd; both empty = all time). The queue is always as of now.
     */
    public function get_store_manager_overview($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->orders_table();
        $body = $this->body($request);

        $manager_id = absint($body['manager_id'] ?? 0);
        $manager    = $manager_id ? get_user_by('id', $manager_id) : null;
        if (!$manager) $this->respond(['status' => 'error', 'message' => 'Store manager not found']);

        $from = !empty($body['from_date']) ? substr(sanitize_text_field($body['from_date']), 0, 10) . ' 00:00:00' : '';
        $to   = !empty($body['to_date']) ? substr(sanitize_text_field($body['to_date']), 0, 10) . ' 23:59:59' : '';

        $mine = $this->assigned_sql($manager_id);
        $open = $this->open_sql();
        $done = $this->in_list(self::DONE_STATUSES);
        $void = $this->in_list(self::VOID_STATUSES);
        $cols = $this->order_columns();

        /* Their open orders now, oldest first. */
        $queue = $wpdb->get_results(
            "SELECT $cols FROM $t o WHERE $mine AND $open ORDER BY o.created_at ASC LIMIT 100",
            ARRAY_A
        );

        /* Open orders nobody has, for the "assign" list. */
        $unassigned = $wpdb->get_results(
            "SELECT $cols FROM $t o WHERE {$this->unassigned_sql()} AND $open ORDER BY o.created_at ASC LIMIT 20",
            ARRAY_A
        );
        $unassigned_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $t o WHERE {$this->unassigned_sql()} AND $open");

        /* Range figures, on when the order was placed. */
        $range = ''; $params = [];
        if ($from) { $range .= ' AND o.created_at >= %s'; $params[] = $from; }
        if ($to)   { $range .= ' AND o.created_at <= %s'; $params[] = $to; }
        $prep = function ($sql) use ($wpdb, $params) {
            return $params ? $wpdb->prepare($sql, $params) : $sql;
        };

        $stats = $wpdb->get_row($prep(
            "SELECT COUNT(*) n,
                    SUM(o.status IN ($done)) done_n,
                    SUM(o.status IN ($void)) void_n,
                    SUM(CASE WHEN o.status IN ($done) THEN o.total_amount ELSE 0 END) revenue,
                    AVG(CASE WHEN o.status IN ($done) AND o.completed_at IS NOT NULL AND o.completed_at >= o.created_at
                             THEN TIMESTAMPDIFF(MINUTE, o.created_at, o.completed_at) END) avg_minutes
             FROM $t o WHERE $mine AND COALESCE(o.status,'') NOT IN ('incomplete') $range"
        ), ARRAY_A);

        $by_status = [];
        foreach ($wpdb->get_results($prep("SELECT o.status, COUNT(*) n FROM $t o WHERE $mine $range GROUP BY o.status"), ARRAY_A) as $r) {
            $by_status[$r['status'] !== null && $r['status'] !== '' ? $r['status'] : 'unknown'] = (int) $r['n'];
        }

        $recent = $wpdb->get_results($prep(
            "SELECT $cols FROM $t o WHERE $mine AND COALESCE(o.status,'') NOT IN ('incomplete') $range ORDER BY o.created_at DESC LIMIT 10"
        ), ARRAY_A);

        /* What they added: products, coupons (post_date in range), purchases recorded. */
        $prange = ''; $pparams = [$manager_id];
        if ($from) { $prange .= ' AND post_date >= %s'; $pparams[] = $from; }
        if ($to)   { $prange .= ' AND post_date <= %s'; $pparams[] = $to; }
        $authored = [];
        foreach (['cmb_product' => 'products', 'coupon' => 'coupons'] as $type => $key) {
            $authored[$key] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author = %d AND post_type = '$type' AND post_status NOT IN ('trash','auto-draft') $prange",
                $pparams
            ));
        }
        $recent_products = $wpdb->get_results($wpdb->prepare(
            "SELECT ID id, post_title title, post_status status, post_date created, post_type type FROM {$wpdb->posts}
             WHERE post_author = %d AND post_type IN ('cmb_product','coupon') AND post_status NOT IN ('trash','auto-draft') $prange
             ORDER BY post_date DESC LIMIT 8",
            $pparams
        ), ARRAY_A);

        $purange = str_replace('post_date', 'created_at', $prange);
        $authored['purchases'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}cstore_purchases WHERE user_id = %d $purange",
            $pparams
        ));

        $last_activity = max(
            (string) $wpdb->get_var("SELECT MAX(o.updated_at) FROM $t o WHERE $mine"),
            (string) $wpdb->get_var($wpdb->prepare("SELECT MAX(post_modified) FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ('cmb_product','coupon')", $manager_id))
        );

        $done_n = (int) ($stats['done_n'] ?? 0);
        $void_n = (int) ($stats['void_n'] ?? 0);

        $this->respond([
            'status'  => 'success',
            'manager' => array_merge([
                'id'         => (int) $manager->ID,
                'name'       => $manager->display_name,
                'email'      => $manager->user_email,
                'phone'      => (string) get_user_meta($manager->ID, 'phone', true),
                'avatar'     => get_avatar_url($manager->user_email, ['size' => 96]),
                'registered' => $manager->user_registered,
                'roles'      => array_values((array) $manager->roles),
                'is_manager' => in_array(self::MANAGER_ROLE, (array) $manager->roles, true),
                'last_activity' => $last_activity !== '' ? $last_activity : null,
            ], $this->details($manager->ID)),
            'queue'            => $queue ?: [],
            'unassigned'       => $unassigned ?: [],
            'unassigned_total' => $unassigned_total,
            'orders'           => (int) ($stats['n'] ?? 0),
            'done'             => $done_n,
            'void'             => $void_n,
            'revenue'          => round((float) ($stats['revenue'] ?? 0), 2),
            'completion_rate'  => ($done_n + $void_n) ? round($done_n / ($done_n + $void_n) * 100, 1) : null,
            'avg_minutes'      => isset($stats['avg_minutes']) && $stats['avg_minutes'] !== null ? (int) round($stats['avg_minutes']) : null,
            'statuses'         => $by_status ?: new \stdClass(),
            'recent'           => $recent ?: [],
            'authored'         => $authored,
            'recent_authored'  => $recent_products ?: [],
        ]);
    }

    /**
     * Save manager fields. Body: id (user id) and any of FIELDS' keys.
     */
    public function update_store_manager_details($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);
        if (!$id || !get_user_by('id', $id)) $this->respond(['status' => 'error', 'message' => 'Store manager not found']);

        if (isset($body['availability']) && !in_array($body['availability'], self::AVAILABILITY, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown availability']);
        }
        if (isset($body['max_open']) && $body['max_open'] !== '') {
            if (!is_numeric($body['max_open']) || (int) $body['max_open'] < 1 || (int) $body['max_open'] > 999) {
                $this->respond(['status' => 'error', 'message' => 'Capacity must be 1 to 999 orders']);
            }
            $body['max_open'] = (string) (int) $body['max_open'];
        }

        foreach (self::FIELDS as $key => [$meta, $max]) {
            if (!array_key_exists($key, $body)) continue;
            $value = $key === 'notes' ? sanitize_textarea_field((string) $body[$key]) : sanitize_text_field((string) $body[$key]);
            update_user_meta($id, $meta, mb_substr($value, 0, $max));
        }

        $this->respond(['status' => 'success', 'id' => $id, 'details' => $this->details($id)]);
    }

    /**
     * Add a store manager to an order's assign_to list, or take them off it.
     * Body: order_id, manager_id, assign (true = add, false = remove).
     * The list's other entries are kept, in the shape the order page saves.
     */
    public function assign_order($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->orders_table();
        $body = $this->body($request);

        $order_id   = absint($body['order_id'] ?? 0);
        $manager_id = absint($body['manager_id'] ?? 0);
        $assign     = !isset($body['assign']) || filter_var($body['assign'], FILTER_VALIDATE_BOOLEAN);

        $raw = $order_id ? $wpdb->get_var($wpdb->prepare("SELECT assign_to FROM $t WHERE id = %d", $order_id)) : null;
        $exists = $order_id && $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $t WHERE id = %d", $order_id));
        if (!$exists) $this->respond(['status' => 'error', 'message' => 'Order not found']);

        $manager = $manager_id ? get_user_by('id', $manager_id) : null;
        if (!$manager || ($assign && !in_array(self::MANAGER_ROLE, (array) $manager->roles, true))) {
            $this->respond(['status' => 'error', 'message' => 'That user is not a store manager']);
        }

        $list = json_decode((string) $raw, true);
        if (!is_array($list)) $list = [];
        $list = array_values(array_filter($list, function ($e) use ($manager_id) {
            return is_array($e) && (int) ($e['id'] ?? 0) !== $manager_id;
        }));
        if ($assign) {
            $list[] = ['id' => $manager_id, 'name' => $manager->display_name];
        }

        $wpdb->update($t, ['assign_to' => wp_json_encode($list)], ['id' => $order_id], ['%s'], ['%d']);

        $this->respond(['status' => 'success', 'order_id' => $order_id, 'assign_to' => $list]);
    }
}
