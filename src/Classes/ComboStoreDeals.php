<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Product deals and their waitlists.
 *
 * A product becomes a deal when the product editor's "Deals" addon is on.
 * Its settings are the JSON column cstore_products.deal:
 *
 *   { label, message, startsAt, endsAt, waitlist, collect, showCount, showJoiners }
 *
 * startsAt / endsAt are datetime-local strings ("2026-12-01T10:00") in the
 * site's timezone; either may be empty. The deal is:
 *   upcoming - before startsAt (or no dates at all)
 *   live     - between startsAt and endsAt
 *   ended    - after endsAt
 * Shoppers can join the waitlist while it is upcoming or live, never after it
 * ended. `collect` is what a guest gives: phone, email or both. A signed-in
 * shopper joins with their account.
 *
 * Entries are rows of cstore_deal_waitlist, one per person per product: the
 * same account, email or phone joins once.
 *
 * Routes (POST, combo-store/v2):
 *   get_deal_waitlist_public - anyone: status, countdown, count, recent joiners
 *                              as "Rahim K." only (no contact details), and
 *                              whether the token's user has joined
 *   join_deal_waitlist       - anyone (token optional), rate limited per IP
 *   get_deal_waitlist        - staff: entries with contact details
 *   remove_deal_waitlist     - staff: delete one entry
 */
class ComboStoreDeals
{
    const SCHEMA_VERSION = 1;
    const STAFF_ROLES = ['administrator', 'store_manager', 'shop_manager'];
    const COLLECT = ['phone', 'email', 'both'];

    /* Joins one IP may make an hour - enough for a household, not for a script. */
    const JOIN_LIMIT_PER_HOUR = 20;

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'maybe_upgrade_schema'), 5);
        add_action('rest_api_init', array($this, 'register_routes'));

        /* A deleted product takes its waitlist with it. */
        add_action('before_delete_post', array($this, 'on_delete_product'));
    }

    public function register_routes()
    {
        foreach (['get_deal_waitlist_public', 'join_deal_waitlist', 'get_deal_waitlist', 'remove_deal_waitlist'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }

    /* ---------------------------------------------------------------- */
    /* Schema                                                            */
    /* ---------------------------------------------------------------- */

    private function table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_deal_waitlist';
    }

    public function maybe_upgrade_schema()
    {
        if ((int) get_option('combo_store_deals_schema', 0) >= self::SCHEMA_VERSION) return;

        global $wpdb;
        $table = $this->table();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            userid bigint(20) unsigned NOT NULL DEFAULT 0,
            name varchar(150) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(40) NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'waiting',
            ip varchar(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_id (product_id),
            KEY product_user (product_id, userid),
            KEY product_email (product_id, email),
            KEY product_phone (product_id, phone)
        ) $charset_collate;");

        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) return;

        update_option('combo_store_deals_schema', self::SCHEMA_VERSION, false);
    }

    public function on_delete_product($post_id)
    {
        if (get_post_type($post_id) !== 'cmb_product') return;
        global $wpdb;
        $wpdb->delete($this->table(), ['product_id' => (int) $post_id], ['%d']);
    }

    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */
    /* ---------------------------------------------------------------- */

    private function body($request)
    {
        $body = json_decode($request->get_body(), true);
        return is_array($body) ? $body : [];
    }

    private function respond($data)
    {
        die(wp_json_encode($data));
    }

    private function refuse($message, $extra = [])
    {
        $this->respond(array_merge(['errors' => true, 'message' => $message], $extra));
    }

    /** The token's user, null without a (valid) token. */
    private function token_user($request)
    {
        $token = $request->get_header('Authorization');
        if (!$token) return null;
        try {
            $decoded = JWT::decode(str_replace('Bearer ', '', $token), new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
        $user_id = $decoded->sub ?? $decoded->user_id ?? $decoded->data->user->id ?? null;
        $user = $user_id ? get_user_by('id', $user_id) : null;
        return $user ?: null;
    }

    private function staff($request)
    {
        $user = $this->token_user($request);
        if (!$user) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        if (!array_intersect(self::STAFF_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view deal waitlists', array('status' => 403));
        }
        return $user;
    }

    /**
     * The product's deal settings, cleaned, or null when the product is not a
     * deal (no Deals addon).
     */
    public static function deal_for($product_id)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT addons, deal FROM {$wpdb->prefix}cstore_products WHERE product_id = %d",
            (int) $product_id
        ), ARRAY_A);
        if (!$row) return null;

        $addons = json_decode((string) $row['addons'], true);
        if (!is_array($addons) || !in_array('deals', $addons, true)) return null;

        $deal = json_decode((string) ($row['deal'] ?? ''), true);
        return self::sanitize_deal(is_array($deal) ? $deal : []);
    }

    /** What the product editor may save as a deal. */
    public static function sanitize_deal($deal)
    {
        $deal = is_array($deal) ? $deal : [];
        $date = function ($value) {
            $value = sanitize_text_field((string) $value);
            return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value) ? substr($value, 0, 16) : '';
        };
        $on = function ($value, $default) {
            return $value === null ? $default : rest_sanitize_boolean($value);
        };

        return [
            'label'       => mb_substr(sanitize_text_field($deal['label'] ?? ''), 0, 80),
            'message'     => mb_substr(sanitize_textarea_field($deal['message'] ?? ''), 0, 500),
            'startsAt'    => $date($deal['startsAt'] ?? ''),
            'endsAt'      => $date($deal['endsAt'] ?? ''),
            'waitlist'    => $on($deal['waitlist'] ?? null, true),
            'collect'     => in_array($deal['collect'] ?? '', self::COLLECT, true) ? $deal['collect'] : 'phone',
            'showCount'   => $on($deal['showCount'] ?? null, true),
            'showJoiners' => $on($deal['showJoiners'] ?? null, true),
        ];
    }

    /** upcoming | live | ended, and seconds to the next change (null when none). */
    private function status_of(array $deal)
    {
        $tz = wp_timezone();
        $now = new \DateTimeImmutable('now', $tz);
        $parse = function ($value) use ($tz) {
            if (empty($value)) return null;
            $date = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, $tz);
            return $date ?: null;
        };
        $start = $parse($deal['startsAt']);
        $end = $parse($deal['endsAt']);

        if ($end && $now >= $end) {
            return ['status' => 'ended', 'starts_in' => null, 'ends_in' => null];
        }
        if ($start && $now < $start) {
            return [
                'status' => 'upcoming',
                'starts_in' => $start->getTimestamp() - $now->getTimestamp(),
                'ends_in' => $end ? $end->getTimestamp() - $now->getTimestamp() : null,
            ];
        }
        if ($start) {
            return ['status' => 'live', 'starts_in' => null, 'ends_in' => $end ? $end->getTimestamp() - $now->getTimestamp() : null];
        }
        /* No start date: the waitlist is open until the end date, if any. */
        return ['status' => 'upcoming', 'starts_in' => null, 'ends_in' => $end ? $end->getTimestamp() - $now->getTimestamp() : null];
    }

    /** "Rahim Khan" -> "Rahim K." - the most a public page shows of anyone. */
    private function public_name($name)
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        $parts = array_values(array_filter($parts, 'strlen'));
        if (empty($parts)) return __('Someone', 'combostore-server');
        $first = mb_substr($parts[0], 0, 20);
        $last = count($parts) > 1 ? ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) . '.' : '';
        return $first . $last;
    }

    private function initials($name)
    {
        $parts = array_values(array_filter(preg_split('/\s+/', trim((string) $name)), 'strlen'));
        if (empty($parts)) return '?';
        $letters = mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '');
        return mb_strtoupper($letters);
    }

    private function normalize_phone($phone)
    {
        $phone = preg_replace('/[^\d+]/', '', (string) $phone);
        return mb_substr($phone, 0, 20);
    }

    private function client_ip()
    {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        return mb_substr(sanitize_text_field($ip), 0, 64);
    }

    private function count_for($product_id)
    {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table()} WHERE product_id = %d",
            (int) $product_id
        ));
    }

    /* ---------------------------------------------------------------- */
    /* Public routes                                                     */
    /* ---------------------------------------------------------------- */

    public function get_deal_waitlist_public($request)
    {
        $body = $this->body($request);
        $product_id = absint($body['product_id'] ?? 0);
        $deal = $product_id ? self::deal_for($product_id) : null;

        if (!$deal) {
            $this->respond(['deal' => null]);
        }

        global $wpdb;
        $status = $this->status_of($deal);
        $count = $this->count_for($product_id);

        $recent = [];
        if ($deal['showJoiners']) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT name, created_at FROM {$this->table()} WHERE product_id = %d ORDER BY id DESC LIMIT 8",
                $product_id
            ), ARRAY_A);
            foreach ((array) $rows as $row) {
                $recent[] = [
                    'name'      => $this->public_name($row['name']),
                    'initials'  => $this->initials($row['name']),
                    'joined_at' => mysql_to_rfc3339($row['created_at']),
                ];
            }
        }

        $joined = false;
        $user = $this->token_user($request);
        if ($user) {
            $joined = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table()} WHERE product_id = %d AND userid = %d LIMIT 1",
                $product_id,
                $user->ID
            ));
        }

        $this->respond(['deal' => [
            'label'       => $deal['label'],
            'message'     => $deal['message'],
            'waitlist'    => $deal['waitlist'],
            'collect'     => $deal['collect'],
            'showCount'   => $deal['showCount'],
            'showJoiners' => $deal['showJoiners'],
            'status'      => $status['status'],
            'starts_in'   => $status['starts_in'],
            'ends_in'     => $status['ends_in'],
            'count'       => $deal['showCount'] ? $count : null,
            'recent'      => $recent,
            'joined'      => $joined,
            'signed_in'   => (bool) $user,
        ]]);
    }

    public function join_deal_waitlist($request)
    {
        $body = $this->body($request);
        $product_id = absint($body['product_id'] ?? 0);
        $deal = $product_id ? self::deal_for($product_id) : null;

        if (!$deal || !$deal['waitlist']) {
            $this->refuse(__("This product doesn't have a waitlist.", 'combostore-server'));
        }
        if ($this->status_of($deal)['status'] === 'ended') {
            $this->refuse(__('This deal has ended.', 'combostore-server'));
        }

        $ip = $this->client_ip();
        $limit_key = 'cstore_deal_join_' . md5($ip);
        $tries = (int) get_transient($limit_key);
        if ($tries >= self::JOIN_LIMIT_PER_HOUR) {
            $this->refuse(__('Too many tries. Please wait a while and try again.', 'combostore-server'));
        }
        set_transient($limit_key, $tries + 1, HOUR_IN_SECONDS);

        $user = $this->token_user($request);

        $name = mb_substr(sanitize_text_field($body['name'] ?? ''), 0, 150);
        $email = sanitize_email($body['email'] ?? '');
        $phone = $this->normalize_phone($body['phone'] ?? '');

        if ($user) {
            if ($name === '') $name = $user->display_name;
            if ($email === '') $email = $user->user_email;
            if ($phone === '') $phone = $this->normalize_phone(get_user_meta($user->ID, 'phone', true));
        } else {
            if ($name === '') {
                $this->refuse(__('Please enter your name.', 'combostore-server'));
            }
            $needs_phone = in_array($deal['collect'], ['phone', 'both'], true);
            $needs_email = in_array($deal['collect'], ['email', 'both'], true);
            if ($needs_phone && strlen(preg_replace('/\D/', '', $phone)) < 6) {
                $this->refuse(__('Please enter a valid phone number.', 'combostore-server'));
            }
            if ($needs_email && !is_email($email)) {
                $this->refuse(__('Please enter a valid email address.', 'combostore-server'));
            }
        }

        /* Once per person per product: by account, else by email / phone. */
        global $wpdb;
        $table = $this->table();
        $where = [];
        $args = [$product_id];
        if ($user) {
            $where[] = 'userid = %d';
            $args[] = $user->ID;
        }
        if ($email !== '') {
            $where[] = 'email = %s';
            $args[] = strtolower($email);
        }
        if ($phone !== '') {
            $where[] = 'phone = %s';
            $args[] = $phone;
        }
        if (!empty($where)) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE product_id = %d AND (" . implode(' OR ', $where) . ") LIMIT 1",
                $args
            ));
            if ($existing) {
                $this->respond([
                    'success' => true,
                    'already' => true,
                    'message' => __("You're already on the waitlist.", 'combostore-server'),
                    'count'   => $deal['showCount'] ? $this->count_for($product_id) : null,
                ]);
            }
        }

        $ok = $wpdb->insert($table, [
            'product_id' => $product_id,
            'userid'     => $user ? $user->ID : 0,
            'name'       => $name,
            'email'      => strtolower($email),
            'phone'      => $phone,
            'status'     => 'waiting',
            'ip'         => $ip,
            'created_at' => current_time('mysql'),
        ], ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']);

        if (!$ok) {
            $this->refuse(__('Could not join the waitlist. Please try again.', 'combostore-server'));
        }

        /* For anything that wants to follow up: an SMS, an email, a marketing list. */
        do_action('combo_store_deal_waitlist_joined', $wpdb->insert_id, $product_id, $user ? $user->ID : 0);

        $this->respond([
            'success' => true,
            'message' => __("You're on the waitlist. We'll let you know when the deal starts.", 'combostore-server'),
            'count'   => $deal['showCount'] ? $this->count_for($product_id) : null,
        ]);
    }

    /* ---------------------------------------------------------------- */
    /* Staff routes                                                      */
    /* ---------------------------------------------------------------- */

    public function get_deal_waitlist($request)
    {
        $staff = $this->staff($request);
        if (is_wp_error($staff)) return $staff;

        $body = $this->body($request);
        $product_id = absint($body['product_id'] ?? 0);
        $page = max(1, absint($body['page'] ?? 1));
        $per_page = min(500, max(1, absint($body['per_page'] ?? 50)));

        if (!$product_id) {
            $this->refuse(__('No product given.', 'combostore-server'));
        }

        global $wpdb;
        $table = $this->table();
        $total = $this->count_for($product_id);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT w.id, w.userid, w.name, w.email, w.phone, w.status, w.created_at, u.display_name AS account
             FROM $table w LEFT JOIN {$wpdb->users} u ON u.ID = w.userid
             WHERE w.product_id = %d ORDER BY w.id DESC LIMIT %d OFFSET %d",
            $product_id,
            $per_page,
            ($page - 1) * $per_page
        ), ARRAY_A);

        $this->respond([
            'total'     => $total,
            'max_pages' => (int) ceil($total / $per_page),
            'entries'   => array_map(function ($row) {
                $row['id'] = (int) $row['id'];
                $row['userid'] = (int) $row['userid'];
                return $row;
            }, (array) $rows),
        ]);
    }

    public function remove_deal_waitlist($request)
    {
        $staff = $this->staff($request);
        if (is_wp_error($staff)) return $staff;

        $body = $this->body($request);
        $id = absint($body['id'] ?? 0);
        if (!$id) {
            $this->refuse(__('No entry given.', 'combostore-server'));
        }

        global $wpdb;
        $deleted = $wpdb->delete($this->table(), ['id' => $id], ['%d']);
        $this->respond(['success' => (bool) $deleted]);
    }
}
