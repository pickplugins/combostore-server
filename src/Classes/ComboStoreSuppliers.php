<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Supplier-only data for the dashboard's Suppliers list and supplier page.
 *
 * Purchases are cstore_purchases rows with supplier_id = the supplier's user
 * id; what was bought is cstore_purchases_items (its `total` column is often
 * 0, so line values are quantity x price).
 *
 * Money:
 *  - a purchase's value is total_amount; cancelled / refunded / failed ones
 *    (VOID_STATUSES) count for nothing;
 *  - paid is the whole total when payment_status is "paid", otherwise the
 *    advance payment (capped at the total);
 *  - due is value less paid.
 *
 * Supplier details that are not on a plain user (company, what they supply,
 * payment terms, lead time, address, tax number, notes, active) are user meta,
 * prefixed cstore_supplier_.
 *
 * Every route is POST with a Bearer JWT, administrators and store managers only.
 */
class ComboStoreSuppliers
{
    const STAFF_ROLES = ['administrator', 'store_manager'];

    const VOID_STATUSES = ['cancelled', 'canceled', 'refunded', 'failed'];
    const OPEN_STATUSES = ['pending', 'processing', 'hold'];

    const STATES = ['active', 'inactive'];
    const PAYMENT_TERMS = ['advance', 'on_delivery', 'net_7', 'net_15', 'net_30', 'consignment'];

    /* Editable supplier fields => [meta key, max length]. */
    const FIELDS = [
        'state'          => ['cstore_supplier_state', 20],
        'company'        => ['cstore_supplier_company', 150],
        'contact_person' => ['cstore_supplier_contact_person', 100],
        'supplies'       => ['cstore_supplier_supplies', 200],
        'payment_terms'  => ['cstore_supplier_payment_terms', 20],
        'lead_days'      => ['cstore_supplier_lead_days', 4],
        'address'        => ['cstore_supplier_address', 500],
        'tax_id'         => ['cstore_supplier_tax_id', 60],
        'notes'          => ['cstore_supplier_notes', 2000],
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        foreach (['get_suppliers_summary', 'get_supplier_overview', 'update_supplier_details'] as $route) {
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

        if (!$user || !array_intersect(self::STAFF_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view supplier data', array('status' => 403));
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

    private function tables()
    {
        global $wpdb;
        return [
            'purchases' => $wpdb->prefix . 'cstore_purchases',
            'items'     => $wpdb->prefix . 'cstore_purchases_items',
        ];
    }

    private function in_list(array $values)
    {
        return "'" . implode("','", array_map('esc_sql', $values)) . "'";
    }

    /** SQL for a purchase's value, paid and due amounts (0 when void). */
    private function money_sql($alias = 'p')
    {
        $void  = $this->in_list(self::VOID_STATUSES);
        $value = "CASE WHEN COALESCE($alias.status,'') IN ($void) THEN 0 ELSE COALESCE($alias.total_amount,0) END";
        $paid  = "CASE WHEN COALESCE($alias.status,'') IN ($void) THEN 0
                       WHEN $alias.payment_status = 'paid' THEN COALESCE($alias.total_amount,0)
                       ELSE LEAST(GREATEST(COALESCE($alias.advance_payment,0),0), COALESCE($alias.total_amount,0)) END";
        return ['value' => $value, 'paid' => $paid, 'due' => "GREATEST(($value) - ($paid), 0)"];
    }

    /** The supplier fields of one user. */
    private function details($user_id)
    {
        $out = [];
        foreach (self::FIELDS as $key => [$meta]) {
            $out[$key] = (string) get_user_meta($user_id, $meta, true);
        }
        if ($out['state'] === '') $out['state'] = 'active';
        return $out;
    }


    /* ---------------------------------------------------------------- */

    /**
     * Per-supplier figures for the Suppliers list. Body: ids (user ids, max 200).
     * Answers {suppliers: {id: {purchases, open, spent, due, last_purchase, ...details}}}.
     */
    public function get_suppliers_summary($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);
        $ids  = isset($body['ids']) && is_array($body['ids']) ? array_slice(array_values(array_filter(array_map('absint', $body['ids']))), 0, 200) : [];

        if (!$ids) $this->respond(['status' => 'success', 'suppliers' => new \stdClass()]);

        $in   = implode(',', $ids);
        $m    = $this->money_sql();
        $open = $this->in_list(self::OPEN_STATUSES);

        $rows = $wpdb->get_results(
            "SELECT p.supplier_id, COUNT(*) purchases, SUM(p.status IN ($open)) open_n,
                    SUM({$m['value']}) spent, SUM({$m['due']}) due, MAX(p.created_at) last_purchase
             FROM {$t['purchases']} p WHERE p.supplier_id IN ($in) GROUP BY p.supplier_id",
            ARRAY_A
        );

        $by = [];
        foreach ($rows as $r) $by[(int) $r['supplier_id']] = $r;

        $out = [];
        foreach ($ids as $id) {
            $r = $by[$id] ?? null;
            $out[$id] = array_merge($this->details($id), [
                'purchases'     => $r ? (int) $r['purchases'] : 0,
                'open'          => $r ? (int) $r['open_n'] : 0,
                'spent'         => $r ? round((float) $r['spent'], 2) : 0,
                'due'           => $r ? round((float) $r['due'], 2) : 0,
                'last_purchase' => $r['last_purchase'] ?? null,
            ]);
        }

        $this->respond(['status' => 'success', 'suppliers' => $out]);
    }

    /**
     * Everything the supplier page shows. Body: supplier_id, from_date, to_date
     * (yyyy-mm-dd; both empty = all time). What is owed is always all time.
     */
    public function get_supplier_overview($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $supplier_id = absint($body['supplier_id'] ?? 0);
        $supplier    = $supplier_id ? get_user_by('id', $supplier_id) : null;
        if (!$supplier) $this->respond(['status' => 'error', 'message' => 'Supplier not found']);

        $from = !empty($body['from_date']) ? substr(sanitize_text_field($body['from_date']), 0, 10) . ' 00:00:00' : '';
        $to   = !empty($body['to_date']) ? substr(sanitize_text_field($body['to_date']), 0, 10) . ' 23:59:59' : '';

        $range  = '';
        $params = [$supplier_id];
        if ($from) { $range .= ' AND p.created_at >= %s'; $params[] = $from; }
        if ($to)   { $range .= ' AND p.created_at <= %s'; $params[] = $to; }

        $m    = $this->money_sql();
        $void = $this->in_list(self::VOID_STATUSES);
        $open = $this->in_list(self::OPEN_STATUSES);

        /* Range figures */
        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT p.status, COUNT(*) n, SUM({$m['value']}) value, SUM({$m['paid']}) paid
             FROM {$t['purchases']} p WHERE p.supplier_id = %d $range GROUP BY p.status",
            $params
        ), ARRAY_A);

        $counts = ['total' => 0, 'open' => 0, 'completed' => 0, 'void' => 0];
        $statuses = [];
        $spent = 0; $paid = 0;
        foreach ($by_status as $row) {
            $n = (int) $row['n'];
            $status = (string) $row['status'];
            $statuses[$status !== '' ? $status : 'unknown'] = $n;
            $counts['total'] += $n;
            if (in_array($status, self::VOID_STATUSES, true)) $counts['void'] += $n;
            elseif (in_array($status, self::OPEN_STATUSES, true)) $counts['open'] += $n;
            elseif ($status === 'completed') $counts['completed'] += $n;
            $spent += (float) $row['value'];
            $paid  += (float) $row['paid'];
        }
        $valid = $counts['total'] - $counts['void'];

        /* What was bought in range, by product. */
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT i.product_id, MAX(i.product_name) product_name, SUM(i.quantity) quantity,
                    SUM(i.quantity * i.price) value, COUNT(DISTINCT i.purchase_id) purchases,
                    SUBSTRING_INDEX(GROUP_CONCAT(i.price ORDER BY p.created_at DESC, i.id DESC), ',', 1) last_price
             FROM {$t['items']} i JOIN {$t['purchases']} p ON p.id = i.purchase_id
             WHERE p.supplier_id = %d AND COALESCE(p.status,'') NOT IN ($void) $range
             GROUP BY i.product_id, CASE WHEN i.product_id IS NULL OR i.product_id = 0 THEN i.product_name ELSE '' END
             ORDER BY value DESC LIMIT 8",
            $params
        ), ARRAY_A);

        $select = "p.id, p.status, p.payment_status, p.payment_method, p.total_amount, p.created_at, p.updated_at,
                   ({$m['paid']}) AS paid, ({$m['due']}) AS due,
                   (SELECT COUNT(*) FROM {$t['items']} i WHERE i.purchase_id = p.id) item_count,
                   (SELECT i.product_name FROM {$t['items']} i WHERE i.purchase_id = p.id ORDER BY i.id LIMIT 1) first_item";

        /* Lists: what is still owed (any date), and the latest in range. */
        $unpaid = $wpdb->get_results($wpdb->prepare(
            "SELECT $select FROM {$t['purchases']} p
             WHERE p.supplier_id = %d AND ({$m['due']}) > 0
             ORDER BY p.created_at ASC LIMIT 50",
            $supplier_id
        ), ARRAY_A);

        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT $select FROM {$t['purchases']} p WHERE p.supplier_id = %d $range ORDER BY p.created_at DESC LIMIT 10",
            $params
        ), ARRAY_A);

        $all_time = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM({$m['due']}) due, SUM(p.status IN ($open)) open_n, MIN(p.created_at) first_purchase, MAX(p.created_at) last_purchase
             FROM {$t['purchases']} p WHERE p.supplier_id = %d",
            $supplier_id
        ), ARRAY_A);

        $this->respond([
            'status'   => 'success',
            'supplier' => array_merge([
                'id'          => (int) $supplier->ID,
                'name'        => $supplier->display_name,
                'email'       => $supplier->user_email,
                'phone'       => (string) get_user_meta($supplier->ID, 'phone', true),
                'avatar'      => get_avatar_url($supplier->user_email, ['size' => 96]),
                'registered'  => $supplier->user_registered,
                'roles'       => array_values((array) $supplier->roles),
                'is_supplier' => in_array('supplier', (array) $supplier->roles, true),
                'first_purchase' => $all_time['first_purchase'] ?? null,
                'last_purchase'  => $all_time['last_purchase'] ?? null,
            ], $this->details($supplier->ID)),
            'counts'    => $counts,
            'statuses'  => $statuses,
            'spent'     => round($spent, 2),
            'paid'      => round($paid, 2),
            'avg_value' => $valid ? round($spent / $valid, 2) : null,
            'due'       => round((float) ($all_time['due'] ?? 0), 2),
            'open_all'  => (int) ($all_time['open_n'] ?? 0),
            'products'  => $products ?: [],
            'unpaid'    => $unpaid ?: [],
            'recent'    => $recent ?: [],
        ]);
    }

    /**
     * Save supplier fields. Body: id (user id) and any of FIELDS' keys.
     */
    public function update_supplier_details($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);
        if (!$id || !get_user_by('id', $id)) $this->respond(['status' => 'error', 'message' => 'Supplier not found']);

        if (isset($body['state']) && !in_array($body['state'], self::STATES, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown supplier state']);
        }
        if (isset($body['payment_terms']) && $body['payment_terms'] !== '' && !in_array($body['payment_terms'], self::PAYMENT_TERMS, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown payment terms']);
        }
        if (isset($body['lead_days']) && $body['lead_days'] !== '') {
            if (!is_numeric($body['lead_days']) || (int) $body['lead_days'] < 0 || (int) $body['lead_days'] > 365) {
                $this->respond(['status' => 'error', 'message' => 'Lead time must be 0 to 365 days']);
            }
            $body['lead_days'] = (string) (int) $body['lead_days'];
        }

        foreach (self::FIELDS as $key => [$meta, $max]) {
            if (!array_key_exists($key, $body)) continue;
            $multiline = in_array($key, ['notes', 'address'], true);
            $value = $multiline ? sanitize_textarea_field((string) $body[$key]) : sanitize_text_field((string) $body[$key]);
            update_user_meta($id, $meta, mb_substr($value, 0, $max));
        }

        $this->respond(['status' => 'success', 'id' => $id, 'details' => $this->details($id)]);
    }
}
