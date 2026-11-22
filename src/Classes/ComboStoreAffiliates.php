<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use WP_REST_Request;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Affiliate sales, clicks and payouts for the dashboard.
 *
 * The tables (cstore_affiliates, cstore_affiliate_sales, _clicks, _payouts)
 * are created by ComboStoreDatabase; nothing read or wrote them before this.
 *
 * Access:
 *  - administrator / store_manager: everything, read and write.
 *  - affiliate: read-only, and only the rows of their own affiliate account(s).
 *
 * Every route is POST with a Bearer JWT, the same as the rest of combo-store/v2.
 */
class ComboStoreAffiliates
{
    const MANAGER_ROLES = ['administrator', 'store_manager'];

    const SALE_STATUSES   = ['pending', 'approved', 'rejected', 'paid'];
    /* `requested`: asked for by the affiliate (request_affiliate_payout), not yet handled. */
    const PAYOUT_STATUSES = ['requested', 'pending', 'processing', 'paid', 'failed', 'cancelled'];

    /* Payouts that take money out of an affiliate's balance. */
    const PAYOUT_DRAWING_STATUSES = ['requested', 'pending', 'processing', 'paid'];

    /* Order statuses that earn the commission, and ones that void it. */
    const ORDER_APPROVE_STATUSES = ['completed', 'delivered'];
    const ORDER_REJECT_STATUSES  = ['cancelled', 'canceled', 'refunded', 'failed'];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'maybe_upgrade_schema'), 5);
        add_action('rest_api_init', array($this, 'register_routes'));

        add_action('combo_store_order_created', array($this, 'credit_order'));
        add_action('combo_store_order_updated', array($this, 'sync_order'));

        add_action('combo_store_affiliate_payout_requested', array($this, 'email_payout_request'));
        add_action('combo_store_affiliate_payout_paid', array($this, 'email_payout_paid'));
        add_action('combo_store_affiliate_payout_request_cancelled', array($this, 'email_payout_request_cancelled'));
    }

    const SCHEMA_VERSION = 2;

    /**
     * Tables and columns this class needs that older installs lack. Runs on
     * REST requests until it succeeds once, then the option short-circuits it.
     *
     * v1: payouts.requested_at - set when an affiliate asked for the payout, so
     *     it is still known to be their request after the store moves it on to
     *     processing, failed and so on. Payouts already in `requested` are
     *     backfilled from created_at.
     * v2: creates any missing affiliate table. Their creation on activation was
     *     commented out, so a site activated in that time has none; new
     *     activations create them again (ComboStoreDatabase).
     */
    public function maybe_upgrade_schema()
    {
        if ((int) get_option('combo_store_affiliates_schema', 0) >= self::SCHEMA_VERSION) return;

        global $wpdb;
        $t = $this->tables();

        if (!class_exists('ComboStore\Classes\ComboStoreDatabase')) return;

        /* Missing tables only: CREATE TABLE IF NOT EXISTS, never an ALTER, so a
           table whose live shape differs from the definition is left alone. */
        foreach (ComboStoreDatabase::affiliate_tables_sql() as $table => $sql) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) continue;
            if ($wpdb->query($sql) === false) return;
        }

        $has = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['payouts']} LIKE %s", 'requested_at'));
        if (!$has) {
            $ok = $wpdb->query("ALTER TABLE {$t['payouts']} ADD COLUMN requested_at DATETIME DEFAULT NULL AFTER status, ADD KEY requested_at (requested_at)");
            if ($ok === false) return;
        }

        $wpdb->query("UPDATE {$t['payouts']} SET requested_at = created_at WHERE status = 'requested' AND requested_at IS NULL");

        update_option('combo_store_affiliates_schema', self::SCHEMA_VERSION, false);
    }

    public function register_routes()
    {
        /* Public: the storefront calls it for every ?ref= visit, signed in or not. */
        register_rest_route('combo-store/v2', '/track_affiliate_click', array(
            'methods'  => 'POST',
            'callback' => array($this, 'track_affiliate_click'),
            'permission_callback' => '__return_true',
        ));

        $routes = [
            'get_affiliate_stats',
            'get_affiliate_accounts',
            'get_affiliate_account',
            'update_affiliate_account',

            'get_affiliate_sales',
            'get_affiliate_sale',
            'create_affiliate_sale',
            'update_affiliate_sale',
            'delete_affiliate_sales',

            'get_affiliate_clicks',
            'delete_affiliate_clicks',

            'get_affiliate_payouts',
            'get_affiliate_payout',
            'create_affiliate_payout',
            'update_affiliate_payout',
            'delete_affiliate_payouts',

            'request_affiliate_payout',
            'cancel_affiliate_payout_request',
        ];

        foreach ($routes as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }


    /* ------------------------------------------------------------------ */
    /* Helpers                                                             */
    /* ------------------------------------------------------------------ */

    private function tables()
    {
        global $wpdb;
        $p = $wpdb->prefix;

        return [
            'affiliates' => $p . 'cstore_affiliates',
            'sales'      => $p . 'cstore_affiliate_sales',
            'clicks'     => $p . 'cstore_affiliate_clicks',
            'payouts'    => $p . 'cstore_affiliate_payouts',
            'users'      => $wpdb->users,
        ];
    }

    /**
     * Resolve the requester from the Bearer token.
     *
     * Returns ['user' => WP_User, 'manager' => bool, 'affiliate_ids' => int[]|null]
     * where affiliate_ids is null for managers (no scoping), or WP_Error.
     */
    private function auth($request)
    {
        $token = $request->get_header('Authorization');

        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $decoded = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded->sub ?? $decoded->user_id ?? $decoded->data->user->id ?? null;
        $user    = $user_id ? get_user_by('id', $user_id) : null;

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 401));
        }

        $roles   = (array) $user->roles;
        $manager = (bool) array_intersect(self::MANAGER_ROLES, $roles);

        if ($manager) {
            return ['user' => $user, 'manager' => true, 'affiliate_ids' => null];
        }

        if (!in_array('affiliate', $roles, true)) {
            return new WP_Error('forbidden', 'You are not allowed to view affiliate data', array('status' => 403));
        }

        global $wpdb;
        $t = $this->tables();

        $this->ensure_account((int) $user->ID);

        $ids = array_map('intval', $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM {$t['affiliates']} WHERE user_id = %d",
            $user->ID
        )));

        return ['user' => $user, 'manager' => false, 'affiliate_ids' => $ids];
    }

    /** Same as auth(), but only managers pass. */
    private function auth_manager($request)
    {
        $auth = $this->auth($request);

        if (is_wp_error($auth)) return $auth;

        if (!$auth['manager']) {
            return new WP_Error('forbidden', 'You are not allowed to change affiliate data', array('status' => 403));
        }

        return $auth;
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

    /**
     * The affiliate account row for a user, created on first use.
     *
     * Affiliates are WP users with the `affiliate` role; their account row
     * (code, commission) was never created anywhere, so it is made here when
     * first needed.
     */
    private function ensure_account($user_id)
    {
        global $wpdb;
        $t = $this->tables();

        $id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$t['affiliates']} WHERE user_id = %d ORDER BY id ASC LIMIT 1",
            $user_id
        ));

        if ($id) return $id;

        $user = get_user_by('id', $user_id);
        if (!$user) return 0;

        /*
            Pages fire several affiliate requests at once, and each used to see
            "no account" and insert one - a user got two accounts in the same
            second. A per-user lock makes check-then-insert one step; the check
            is repeated inside it. (user_id has no unique key on the live table.)
        */
        $lock = 'cstore_aff_acct_' . (int) $user_id;
        if (!$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 5)', $lock))) {
            return 0;
        }

        try {
            $id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$t['affiliates']} WHERE user_id = %d ORDER BY id ASC LIMIT 1",
                $user_id
            ));

            if (!$id) {
                $base = sanitize_title($user->user_login);
                $code = $base ?: ('aff' . $user_id);

                if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['affiliates']} WHERE affiliate_code = %s", $code))) {
                    $code = $code . '-' . $user_id;
                }

                $wpdb->insert($t['affiliates'], [
                    'user_id'          => $user_id,
                    'affiliate_code'   => $code,
                    'status'           => 'active',
                    'commission_type'  => 'percentage',
                    'commission_value' => 0,
                    'created_at'       => current_time('mysql'),
                ]);

                $id = (int) $wpdb->insert_id;
            }
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }

        return $id;
    }

    /** SELECT columns describing the affiliate, for a row aliased `x`. */
    private function affiliate_columns()
    {
        return "a.affiliate_code AS affiliate_code, a.user_id AS affiliate_user_id,
                u.display_name AS affiliate_name, u.user_email AS affiliate_email";
    }

    private function affiliate_joins()
    {
        $t = $this->tables();
        return "LEFT JOIN {$t['affiliates']} a ON a.id = x.affiliate_id
                LEFT JOIN {$t['users']} u ON u.ID = a.user_id";
    }

    /** Shared WHERE parts: scope, affiliate filter, date range. */
    private function common_where($auth, $body, $date_column = 'x.created_at')
    {
        $where  = [];
        $params = [];

        if ($auth['affiliate_ids'] !== null) {
            if (empty($auth['affiliate_ids'])) {
                $where[] = '1 = 0';
            } else {
                $where[] = 'x.affiliate_id IN (' . implode(',', array_map('intval', $auth['affiliate_ids'])) . ')';
            }
        }

        $affiliate_id = isset($body['affiliate_id']) ? absint($body['affiliate_id']) : 0;
        if ($affiliate_id) {
            $where[]  = 'x.affiliate_id = %d';
            $params[] = $affiliate_id;
        }

        $from = isset($body['from_date']) ? sanitize_text_field($body['from_date']) : '';
        $to   = isset($body['to_date']) ? sanitize_text_field($body['to_date']) : '';

        if ($from) {
            $where[]  = "$date_column >= %s";
            $params[] = substr($from, 0, 10) . ' 00:00:00';
        }
        if ($to) {
            $where[]  = "$date_column <= %s";
            $params[] = substr($to, 0, 10) . ' 23:59:59';
        }

        return [$where, $params];
    }

    /** Run a paged list query: {posts, total, max_pages}. */
    private function paged_list($table, $select, $where, $params, $body)
    {
        global $wpdb;

        $paged    = max(1, isset($body['paged']) ? absint($body['paged']) : 1);
        $per_page = isset($body['per_page']) ? absint($body['per_page']) : 10;
        $per_page = $per_page > 0 ? min($per_page, 200) : 10;
        $order    = (isset($body['order']) && strtoupper($body['order']) === 'ASC') ? 'ASC' : 'DESC';
        $offset   = ($paged - 1) * $per_page;

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $joins     = $this->affiliate_joins();

        $count_sql = "SELECT COUNT(*) FROM $table x $joins $where_sql";
        $list_sql  = "SELECT $select, {$this->affiliate_columns()} FROM $table x $joins $where_sql
                      ORDER BY x.id $order LIMIT %d, %d";

        $total = (int) ($params ? $wpdb->get_var($wpdb->prepare($count_sql, $params)) : $wpdb->get_var($count_sql));
        $rows  = $wpdb->get_results($wpdb->prepare($list_sql, array_merge($params, [$offset, $per_page])), ARRAY_A);

        return [
            'posts'     => $rows ?: [],
            'total'     => $total,
            'max_pages' => max(1, (int) ceil($total / $per_page)),
        ];
    }

    private function get_row($table, $id, $auth)
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT x.*, {$this->affiliate_columns()} FROM $table x {$this->affiliate_joins()} WHERE x.id = %d",
            $id
        ), ARRAY_A);

        if (!$row) return null;

        if ($auth['affiliate_ids'] !== null && !in_array((int) $row['affiliate_id'], $auth['affiliate_ids'], true)) {
            return null;
        }

        return $row;
    }

    private function affiliate_exists($affiliate_id)
    {
        global $wpdb;
        $t = $this->tables();
        return (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['affiliates']} WHERE id = %d", $affiliate_id));
    }

    private function delete_ids($table, $request)
    {
        global $wpdb;

        $body = $this->body($request);
        $ids  = isset($body['ids']) && is_array($body['ids']) ? array_values(array_filter(array_map('absint', $body['ids']))) : [];

        if (!$ids) {
            return ['success' => false, 'deleted' => 0, 'message' => 'No ids given'];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id IN ($placeholders)", $ids));

        return ['success' => $deleted !== false, 'deleted' => (int) $deleted];
    }

    private function money($value)
    {
        return round((float) $value, 2);
    }


    /* ------------------------------------------------------------------ */
    /* Dashboard                                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Figures for the Affiliates dashboard.
     *
     * Body: from_date, to_date (yyyy-mm-dd, default the last 30 days), affiliate_id.
     * Range figures cover the dates; `balance` is all-time (earned - paid out).
     */
    public function get_affiliate_stats($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        if (empty($body['from_date']) && empty($body['to_date'])) {
            $body['to_date']   = current_time('Y-m-d');
            $body['from_date'] = gmdate('Y-m-d', strtotime($body['to_date'] . ' -29 days'));
        }

        $run = function ($sql, $params) use ($wpdb) {
            return $params ? $wpdb->prepare($sql, $params) : $sql;
        };

        /* Range: clicks */
        list($w, $p) = $this->common_where($auth, $body);
        $ws = $w ? 'WHERE ' . implode(' AND ', $w) : '';
        $clicks = (int) $wpdb->get_var($run("SELECT COUNT(*) FROM {$t['clicks']} x $ws", $p));

        $clicks_daily = $wpdb->get_results($run(
            "SELECT DATE(x.created_at) d, COUNT(*) n FROM {$t['clicks']} x $ws GROUP BY DATE(x.created_at)",
            $p
        ), ARRAY_A);

        /* Range: sales by status */
        $by_status = $wpdb->get_results($run(
            "SELECT x.status, COUNT(*) n, SUM(x.order_total) revenue, SUM(x.commission_amount) commission
             FROM {$t['sales']} x $ws GROUP BY x.status",
            $p
        ), ARRAY_A);

        $sales = ['count' => 0, 'revenue' => 0, 'commission' => 0, 'by_status' => []];
        foreach (self::SALE_STATUSES as $s) {
            $sales['by_status'][$s] = ['count' => 0, 'commission' => 0];
        }
        foreach ($by_status as $row) {
            $key = $row['status'] ?: 'pending';
            $sales['by_status'][$key] = ['count' => (int) $row['n'], 'commission' => $this->money($row['commission'])];
            if ($key !== 'rejected') {
                $sales['count']      += (int) $row['n'];
                $sales['revenue']    += (float) $row['revenue'];
                $sales['commission'] += (float) $row['commission'];
            }
        }
        $sales['revenue']    = $this->money($sales['revenue']);
        $sales['commission'] = $this->money($sales['commission']);

        $sales_daily = $wpdb->get_results($run(
            "SELECT DATE(x.created_at) d, COUNT(*) n, SUM(x.commission_amount) commission
             FROM {$t['sales']} x $ws " . ($ws ? 'AND' : 'WHERE') . " x.status <> 'rejected'
             GROUP BY DATE(x.created_at)",
            $p
        ), ARRAY_A);

        /* Range: payouts paid */
        list($wp, $pp) = $this->common_where($auth, $body, 'COALESCE(x.paid_at, x.created_at)');
        $wps = $wp ? 'WHERE ' . implode(' AND ', $wp) : '';
        $payouts_paid = $this->money($wpdb->get_var($run(
            "SELECT SUM(x.amount) FROM {$t['payouts']} x $wps " . ($wps ? 'AND' : 'WHERE') . " x.status = 'paid'",
            $pp
        )));

        /* All-time balance (scope and affiliate filter only, no dates). */
        $scope_body = ['affiliate_id' => $body['affiliate_id'] ?? 0];
        list($wa, $pa) = $this->common_where($auth, $scope_body);
        $was = $wa ? 'WHERE ' . implode(' AND ', $wa) : '';
        $and = $was ? 'AND' : 'WHERE';

        $earned = (float) $wpdb->get_var($run(
            "SELECT SUM(x.commission_amount) FROM {$t['sales']} x $was $and x.status IN ('approved','paid')",
            $pa
        ));
        $paid_out = (float) $wpdb->get_var($run(
            "SELECT SUM(x.amount) FROM {$t['payouts']} x $was $and x.status = 'paid'",
            $pa
        ));
        $pending_payouts = (float) $wpdb->get_var($run(
            "SELECT SUM(x.amount) FROM {$t['payouts']} x $was $and x.status IN ('requested','pending','processing')",
            $pa
        ));

        /* Daily series, every day of the range filled in. */
        $series = [];
        $start  = strtotime(substr($body['from_date'] ?: '1970-01-01', 0, 10));
        $end    = strtotime(substr($body['to_date'] ?: current_time('Y-m-d'), 0, 10));
        if ($end - $start <= 366 * DAY_IN_SECONDS) {
            for ($d = $start; $d <= $end; $d += DAY_IN_SECONDS) {
                $series[gmdate('Y-m-d', $d)] = ['date' => gmdate('Y-m-d', $d), 'clicks' => 0, 'sales' => 0, 'commission' => 0];
            }
        }
        foreach ($clicks_daily as $row) {
            if (isset($series[$row['d']])) $series[$row['d']]['clicks'] = (int) $row['n'];
        }
        foreach ($sales_daily as $row) {
            if (isset($series[$row['d']])) {
                $series[$row['d']]['sales']      = (int) $row['n'];
                $series[$row['d']]['commission'] = $this->money($row['commission']);
            }
        }

        /* Top affiliates (managers only). */
        $top = [];
        if ($auth['manager']) {
            $top = $wpdb->get_results($run(
                "SELECT x.affiliate_id, COUNT(*) sales, SUM(x.order_total) revenue, SUM(x.commission_amount) commission,
                        {$this->affiliate_columns()}
                 FROM {$t['sales']} x {$this->affiliate_joins()} $ws " . ($ws ? 'AND' : 'WHERE') . " x.status <> 'rejected'
                 GROUP BY x.affiliate_id ORDER BY commission DESC LIMIT 5",
                $p
            ), ARRAY_A);
        }

        $recent = $this->paged_list($t['sales'], 'x.*', $w, $p, ['per_page' => 5]);

        /* The affiliate's own account(s), so they can see their code. */
        $accounts = [];
        if (!$auth['manager'] && $auth['affiliate_ids']) {
            $accounts = $wpdb->get_results(
                "SELECT id, affiliate_code, status, commission_type, commission_value FROM {$t['affiliates']}
                 WHERE id IN (" . implode(',', $auth['affiliate_ids']) . ")",
                ARRAY_A
            );
            /* Their saved payout details, to prefill the payout request form. */
            $details = $this->payout_details((int) $auth['user']->ID);
            $accounts = array_map(fn($a) => array_merge($a, $details), $accounts ?: []);
        }

        /* An affiliate's payout request still waiting for the store, if any. */
        $open_request = null;
        if (!$auth['manager'] && $auth['affiliate_ids']) {
            $open_request = $wpdb->get_row(
                "SELECT id, amount, payment_method, created_at FROM {$t['payouts']}
                 WHERE status = 'requested' AND affiliate_id IN (" . implode(',', $auth['affiliate_ids']) . ")
                 ORDER BY id DESC LIMIT 1",
                ARRAY_A
            );
        }

        $this->respond([
            'status'     => 'success',
            'from_date'  => $body['from_date'],
            'to_date'    => $body['to_date'],
            'is_manager' => $auth['manager'],
            'clicks'     => $clicks,
            'sales'      => $sales,
            'conversion_rate' => $clicks > 0 ? round($sales['count'] / $clicks * 100, 2) : 0,
            'payouts_paid'    => $payouts_paid,
            'balance' => [
                'earned'          => $this->money($earned),
                'paid_out'        => $this->money($paid_out),
                'pending_payouts' => $this->money($pending_payouts),
                'due'             => $this->money($earned - $paid_out),
                /* Due less payouts already pending or processing: what a new payout may take. */
                'available'       => $this->money($earned - $paid_out - $pending_payouts),
                'min_payout'      => $this->min_payout(),
            ],
            'series'        => array_values($series),
            'top_affiliates' => $top ?: [],
            'recent_sales'  => $recent['posts'],
            'accounts'      => $accounts,
            'open_request'  => $open_request,
        ]);
    }

    /**
     * Affiliate accounts for pickers and filters.
     *
     * Every user with the `affiliate` role is listed, and gets an account row
     * if they had none. Body: keyword.
     */
    public function get_affiliate_accounts($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $users = get_users(['role' => 'affiliate', 'fields' => ['ID'], 'number' => 500]);
        foreach ($users as $u) {
            $this->ensure_account((int) $u->ID);
        }

        $where  = '';
        $params = [];
        $keyword = isset($body['keyword']) ? sanitize_text_field($body['keyword']) : '';
        if ($keyword !== '') {
            $like    = '%' . $wpdb->esc_like($keyword) . '%';
            $where   = 'WHERE (a.affiliate_code LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
            $params  = [$like, $like, $like];
        }

        /* `available` is what available_balance() computes, for every account at once. */
        $drawing = "'" . implode("','", self::PAYOUT_DRAWING_STATUSES) . "'";
        $sql = "SELECT a.*, u.display_name AS affiliate_name, u.user_email AS affiliate_email,
                    COALESCE((SELECT SUM(s.commission_amount) FROM {$t['sales']} s WHERE s.affiliate_id = a.id AND s.status IN ('approved','paid')), 0)
                  - COALESCE((SELECT SUM(p.amount) FROM {$t['payouts']} p WHERE p.affiliate_id = a.id AND p.status IN ($drawing)), 0) AS available
                FROM {$t['affiliates']} a LEFT JOIN {$t['users']} u ON u.ID = a.user_id
                $where ORDER BY u.display_name ASC LIMIT 200";

        $rows = $wpdb->get_results($params ? $wpdb->prepare($sql, $params) : $sql, ARRAY_A);
        $rows = array_map(function ($row) {
            $row['available'] = $this->money($row['available']);
            return $row;
        }, $rows ?: []);

        $this->respond(['status' => 'success', 'accounts' => $rows ?: [], 'min_payout' => $this->min_payout()]);
    }


    /**
     * One user's affiliate account (created if missing). Body: user_id.
     */
    public function get_affiliate_account($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $user_id = absint($body['user_id'] ?? 0);
        if (!$user_id || !get_user_by('id', $user_id)) {
            $this->respond(['status' => 'error', 'message' => 'User not found']);
        }

        $id = $this->ensure_account($user_id);
        $account = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['affiliates']} WHERE id = %d", $id), ARRAY_A);
        if ($account) {
            $account = array_merge($account, $this->payout_details($user_id));
        }

        /* Who the account belongs to, for the affiliate page's header. */
        $user = get_user_by('id', $user_id);

        $this->respond([
            'status'  => 'success',
            'account' => $account,
            'user'    => [
                'id'         => (int) $user->ID,
                'name'       => $user->display_name,
                'email'      => $user->user_email,
                'avatar'     => get_avatar_url($user->user_email, ['size' => 96]),
                'registered' => $user->user_registered,
                'roles'      => array_values((array) $user->roles),
                'is_affiliate' => in_array('affiliate', (array) $user->roles, true),
            ],
        ]);
    }

    /* Where an affiliate wants to be paid, kept on their user (two meta keys). */
    const PAYOUT_METHOD_META  = 'cstore_affiliate_payout_method';
    const PAYOUT_DETAILS_META = 'cstore_affiliate_payout_details';

    private function payout_details($user_id)
    {
        return [
            'payout_method'  => (string) get_user_meta($user_id, self::PAYOUT_METHOD_META, true),
            'payout_details' => (string) get_user_meta($user_id, self::PAYOUT_DETAILS_META, true),
        ];
    }

    /**
     * Change an account's code, status, commission or payout details.
     * Body: id, affiliate_code, status (active|inactive), commission_type (percentage|flat), commission_value,
     * payout_method, payout_details.
     */
    public function update_affiliate_account($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);

        if (!$id || !$this->affiliate_exists($id)) {
            $this->respond(['status' => 'error', 'message' => 'Affiliate account not found']);
        }

        $data = [];

        if (isset($body['affiliate_code'])) {
            $code = $this->clean_code($body['affiliate_code']);
            if ($code === '') {
                $this->respond(['status' => 'error', 'message' => 'The code may use letters, numbers, - and _ only']);
            }
            if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['affiliates']} WHERE affiliate_code = %s AND id <> %d", $code, $id))) {
                $this->respond(['status' => 'error', 'message' => "The code \"$code\" is already taken"]);
            }
            $data['affiliate_code'] = $code;
        }
        if (isset($body['status'])) {
            $data['status'] = $body['status'] === 'inactive' ? 'inactive' : 'active';
        }
        if (isset($body['commission_type'])) {
            $data['commission_type'] = $body['commission_type'] === 'flat' ? 'flat' : 'percentage';
        }
        if (isset($body['commission_value']) && $body['commission_value'] !== '') {
            $data['commission_value'] = max(0, $this->money($body['commission_value']));
        }

        /* Payout details live on the user, not the account row. */
        if (isset($body['payout_method']) || isset($body['payout_details'])) {
            $owner = (int) $wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$t['affiliates']} WHERE id = %d", $id));
            if ($owner) {
                if (isset($body['payout_method'])) {
                    update_user_meta($owner, self::PAYOUT_METHOD_META, substr(sanitize_text_field((string) $body['payout_method']), 0, 50));
                }
                if (isset($body['payout_details'])) {
                    update_user_meta($owner, self::PAYOUT_DETAILS_META, substr(sanitize_textarea_field((string) $body['payout_details']), 0, 1000));
                }
            }
        }

        if (!$data) {
            $this->respond(['status' => 'success', 'id' => $id]);
        }

        $data['updated_at'] = current_time('mysql');
        $ok = $wpdb->update($t['affiliates'], $data, ['id' => $id]);

        $this->respond($ok !== false
            ? ['status' => 'success', 'id' => $id]
            : ['status' => 'error', 'message' => 'The account could not be saved']);
    }

    private function clean_code($code)
    {
        $code = trim((string) $code);
        return preg_match('/^[A-Za-z0-9_-]{1,100}$/', $code) ? $code : '';
    }


    /* ------------------------------------------------------------------ */
    /* Tracking                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * How long a referral is remembered: Settings > Affiliates (combo_store_settings
     * affiliates.cookieDays), 30 when unset, kept within 1-365. The filter still
     * has the last word.
     */
    private function cookie_days()
    {
        $settings = get_option('combo_store_settings', []);
        $days     = is_array($settings) ? (int) ($settings['affiliates']['cookieDays'] ?? 0) : 0;
        $days     = $days > 0 ? min($days, 365) : 30;

        return max(1, (int) apply_filters('combo_store_affiliate_cookie_days', $days));
    }

    /**
     * Smallest payout that may be recorded: Settings > Affiliates (combo_store_settings
     * affiliates.minPayout). 0 or unset means no minimum.
     */
    private function min_payout()
    {
        $settings = get_option('combo_store_settings', []);
        $min      = is_array($settings) ? (float) ($settings['affiliates']['minPayout'] ?? 0) : 0;

        return max(0, $this->money(apply_filters('combo_store_affiliate_min_payout', $min)));
    }

    /**
     * What an affiliate can still be paid: approved + paid commission, less every
     * payout that is paid, pending or processing (so two pending payouts cannot
     * both draw the same money). $exclude_payout_id leaves one payout out, for
     * checking an edit of it.
     */
    private function available_balance($affiliate_id, $exclude_payout_id = 0)
    {
        global $wpdb;
        $t = $this->tables();

        $earned = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(commission_amount) FROM {$t['sales']} WHERE affiliate_id = %d AND status IN ('approved','paid')",
            $affiliate_id
        ));

        $drawing = "'" . implode("','", self::PAYOUT_DRAWING_STATUSES) . "'";
        $drawn = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$t['payouts']} WHERE affiliate_id = %d AND id <> %d AND status IN ($drawing)",
            $affiliate_id,
            $exclude_payout_id
        ));

        return $this->money($earned - $drawn);
    }

    /** An error message when $amount is more than the affiliate can be paid, else null. */
    private function over_balance($affiliate_id, $amount, $exclude_payout_id = 0)
    {
        $available = $this->available_balance($affiliate_id, $exclude_payout_id);

        return ((float) $amount > $available + 0.005)
            ? sprintf('This affiliate can be paid at most %s (approved commission less paid and in-progress payouts)', number_format(max(0, $available), 2))
            : null;
    }

    /** An error message when $amount is under the minimum payout, else null. */
    private function below_min_payout($amount)
    {
        $min = $this->min_payout();
        return ($min > 0 && (float) $amount < $min)
            ? sprintf('The minimum payout is %s', number_format($min, 2))
            : null;
    }

    private function active_account_by_code($code)
    {
        global $wpdb;
        $t = $this->tables();

        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, u.user_email FROM {$t['affiliates']} a LEFT JOIN {$t['users']} u ON u.ID = a.user_id
             WHERE a.affiliate_code = %s AND a.status = 'active'",
            $code
        ), ARRAY_A);
    }

    /**
     * Record a visit through an affiliate link. Public.
     *
     * Body: code, landing_url, referrer_url, session_id.
     * The same visitor (session id or IP) clicking the same affiliate's link
     * again within 30 minutes is not counted twice; the earlier click is
     * returned. The IP is only kept as a salted hash.
     */
    public function track_affiliate_click($request)
    {
        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $code    = $this->clean_code($body['code'] ?? '');
        $account = $code !== '' ? $this->active_account_by_code($code) : null;

        if (!$account) {
            $this->respond(['status' => 'error', 'message' => 'Unknown or inactive affiliate code']);
        }

        $ip         = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
        $ip_hash    = $ip !== '' ? hash_hmac('sha256', $ip, wp_salt('auth')) : null;
        $session_id = isset($body['session_id']) ? substr(sanitize_text_field($body['session_id']), 0, 100) : '';
        $since      = gmdate('Y-m-d H:i:s', strtotime(current_time('mysql')) - 30 * MINUTE_IN_SECONDS);

        $match = [];
        $params = [(int) $account['id'], $since];
        if ($session_id !== '') {
            $match[]  = 'session_id = %s';
            $params[] = $session_id;
        }
        if ($ip_hash) {
            $match[]  = 'ip_hash = %s';
            $params[] = $ip_hash;
        }

        $existing = $match ? (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$t['clicks']} WHERE affiliate_id = %d AND created_at >= %s AND (" . implode(' OR ', $match) . ")
             ORDER BY id DESC LIMIT 1",
            $params
        )) : 0;

        $click_id = $existing;

        if (!$click_id) {
            $url = function ($value) {
                $value = esc_url_raw(substr((string) $value, 0, 2000));
                return $value !== '' ? $value : null;
            };

            $wpdb->insert($t['clicks'], [
                'affiliate_id'   => (int) $account['id'],
                'affiliate_code' => $account['affiliate_code'],
                'session_id'     => $session_id !== '' ? $session_id : null,
                'landing_url'    => $url($body['landing_url'] ?? ''),
                'referrer_url'   => $url($body['referrer_url'] ?? ''),
                'ip_hash'        => $ip_hash,
                'user_agent'     => substr(sanitize_text_field($request->get_header('user_agent') ?? ''), 0, 500),
                'created_at'     => current_time('mysql'),
            ]);
            $click_id = (int) $wpdb->insert_id;
        }

        $this->respond([
            'status'         => 'success',
            'click_id'       => $click_id,
            'affiliate_code' => $account['affiliate_code'],
            'cookie_days'    => $this->cookie_days(),
            'repeat'         => (bool) $existing,
        ]);
    }

    /**
     * combo_store_order_created: credit the order to the referring affiliate.
     *
     * Commission is on the order total less shipping and tax: a percentage of
     * it, or the account's flat amount. Not credited: inactive or unknown
     * codes, and an affiliate referring their own order (same user or email).
     */
    public function credit_order($args)
    {
        $code     = $this->clean_code($args['affiliate_ref'] ?? '');
        $order_id = absint($args['order_id'] ?? 0);

        if ($code === '' || !$order_id) return;

        $account = $this->active_account_by_code($code);
        if (!$account) return;

        global $wpdb;
        $t = $this->tables();

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, userid, status, total_amount, shipping_amount, tax_amount, billing_email FROM {$wpdb->prefix}cstore_orders WHERE id = %d",
            $order_id
        ), ARRAY_A);
        if (!$order) return;

        $own_user  = (int) $order['userid'] && (int) $order['userid'] === (int) $account['user_id'];
        $own_email = $order['billing_email'] && $account['user_email'] && strcasecmp($order['billing_email'], $account['user_email']) === 0;
        if ($own_user || $own_email) return;

        if ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$t['sales']} WHERE order_id = %d AND affiliate_id = %d",
            $order_id,
            $account['id']
        ))) return;

        list($base, $rate, $amount) = $this->commission_for($account, $order);
        $status = $this->sale_status_for_order($order['status'], 'pending');
        $now    = current_time('mysql');

        $wpdb->insert($t['sales'], [
            'affiliate_id'      => (int) $account['id'],
            'order_id'          => $order_id,
            'order_total'       => $base,
            'commission_rate'   => $rate,
            'commission_amount' => $amount,
            'status'            => $status,
            'created_at'        => $now,
            'approved_at'       => $status === 'approved' ? $now : null,
        ]);

        if (class_exists('ComboStore\Classes\ComboStoreObjectMeta')) {
            $meta = new ComboStoreObjectMeta();
            $meta->update_meta('orders', $order_id, 'affiliate_code', $account['affiliate_code']);
            if (!empty($args['affiliate_click_id'])) {
                $meta->update_meta('orders', $order_id, 'affiliate_click_id', absint($args['affiliate_click_id']));
            }
        }
    }

    /** [commission base, rate, amount] for an order. */
    private function commission_for($account, $order)
    {
        $base = max(0, (float) $order['total_amount'] - (float) $order['shipping_amount'] - (float) $order['tax_amount']);

        if ($account['commission_type'] === 'flat') {
            return [$this->money($base), 0, $this->money($account['commission_value'])];
        }

        $rate = (float) $account['commission_value'];
        return [$this->money($base), $this->money($rate), $this->money($base * $rate / 100)];
    }

    /** The sale status an order status moves it to; $current if none. */
    private function sale_status_for_order($order_status, $current)
    {
        if ($current === 'paid') return 'paid';

        if (in_array($order_status, self::ORDER_REJECT_STATUSES, true)) return 'rejected';
        if (in_array($order_status, self::ORDER_APPROVE_STATUSES, true) && $current === 'pending') return 'approved';

        return $current;
    }

    /**
     * combo_store_order_updated: keep the order's affiliate sales in step.
     *
     * Completed / delivered approves a pending sale; cancelled, refunded or
     * failed rejects it (a paid sale is left alone). While a sale is pending,
     * a changed order total recomputes its commission. Moves only go forward:
     * a rejected sale is not revived automatically.
     */
    public function sync_order($args)
    {
        $order_id = absint($args['order_id'] ?? 0);
        if (!$order_id) return;

        global $wpdb;
        $t = $this->tables();

        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, a.commission_type, a.commission_value FROM {$t['sales']} s
             LEFT JOIN {$t['affiliates']} a ON a.id = s.affiliate_id WHERE s.order_id = %d",
            $order_id
        ), ARRAY_A);
        if (!$sales) return;

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, status, total_amount, shipping_amount, tax_amount FROM {$wpdb->prefix}cstore_orders WHERE id = %d",
            $order_id
        ), ARRAY_A);
        if (!$order) return;

        $now = current_time('mysql');

        foreach ($sales as $sale) {
            $data   = [];
            $status = $this->sale_status_for_order($order['status'], $sale['status']);

            if ($status !== $sale['status'] && $sale['status'] !== 'rejected') {
                $data['status'] = $status;
                if ($status === 'approved' && empty($sale['approved_at'])) {
                    $data['approved_at'] = $now;
                }
            }

            if ($sale['status'] === 'pending' && ($data['status'] ?? 'pending') !== 'rejected') {
                /* Rate as stored on the sale, so a manual rate edit is kept. */
                $account = ['commission_type' => $sale['commission_type'], 'commission_value' => $sale['commission_type'] === 'flat' ? $sale['commission_amount'] : $sale['commission_rate']];
                list($base, , $amount) = $this->commission_for($account, $order);
                if (abs($base - (float) $sale['order_total']) >= 0.01) {
                    $data['order_total']       = $base;
                    $data['commission_amount'] = $amount;
                }
            }

            if ($data) {
                $wpdb->update($t['sales'], $data, ['id' => (int) $sale['id']]);
            }
        }
    }


    /* ------------------------------------------------------------------ */
    /* Sales                                                               */
    /* ------------------------------------------------------------------ */

    /** Body: paged, per_page, order, status, affiliate_id, keyword, from_date, to_date. */
    public function get_affiliate_sales($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        list($where, $params) = $this->common_where($auth, $body);

        $status = isset($body['status']) ? sanitize_key($body['status']) : '';
        if ($status) {
            $where[]  = 'x.status = %s';
            $params[] = $status;
        }

        $keyword = isset($body['keyword']) ? trim(sanitize_text_field($body['keyword'])) : '';
        if ($keyword !== '') {
            $like     = '%' . $wpdb->esc_like(ltrim($keyword, '#')) . '%';
            $where[]  = '(x.order_id = %d OR a.affiliate_code LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
            array_push($params, absint(ltrim($keyword, '#')), $like, $like, $like);
        }

        $this->respond($this->paged_list($t['sales'], 'x.*', $where, $params, $body));
    }

    public function get_affiliate_sale($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $t    = $this->tables();
        $body = $this->body($request);
        $sale = $this->get_row($t['sales'], absint($body['id'] ?? 0), $auth);

        $this->respond($sale
            ? ['status' => 'success', 'sale' => $sale, 'can_edit' => $auth['manager']]
            : ['status' => 'error', 'message' => 'Sale not found']);
    }

    /** Body: affiliate_id, order_id, order_total, commission_rate, commission_amount, status. */
    public function create_affiliate_sale($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $affiliate_id = absint($body['affiliate_id'] ?? 0);
        $order_id     = absint($body['order_id'] ?? 0);

        if (!$affiliate_id || !$this->affiliate_exists($affiliate_id)) {
            $this->respond(['status' => 'error', 'message' => 'Choose an affiliate']);
        }
        if (!$order_id) {
            $this->respond(['status' => 'error', 'message' => 'Enter the order ID']);
        }

        if ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$t['sales']} WHERE order_id = %d AND affiliate_id = %d",
            $order_id,
            $affiliate_id
        ))) {
            $this->respond(['status' => 'error', 'message' => "Order #$order_id is already credited to this affiliate"]);
        }

        $order_total = isset($body['order_total']) && $body['order_total'] !== '' ? (float) $body['order_total'] : null;
        if ($order_total === null) {
            $order_total = (float) $wpdb->get_var($wpdb->prepare(
                "SELECT total_amount FROM {$wpdb->prefix}cstore_orders WHERE id = %d",
                $order_id
            ));
        }

        $account = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['affiliates']} WHERE id = %d", $affiliate_id), ARRAY_A);

        $rate = isset($body['commission_rate']) && $body['commission_rate'] !== ''
            ? (float) $body['commission_rate']
            : ($account['commission_type'] === 'percentage' ? (float) $account['commission_value'] : 0);

        if (isset($body['commission_amount']) && $body['commission_amount'] !== '') {
            $amount = (float) $body['commission_amount'];
        } elseif ($account['commission_type'] !== 'percentage' && !isset($body['commission_rate'])) {
            $amount = (float) $account['commission_value'];
        } else {
            $amount = $order_total * $rate / 100;
        }

        $status = in_array($body['status'] ?? '', self::SALE_STATUSES, true) ? $body['status'] : 'pending';
        $now    = current_time('mysql');

        $ok = $wpdb->insert($t['sales'], [
            'affiliate_id'      => $affiliate_id,
            'order_id'          => $order_id,
            'order_total'       => $this->money($order_total),
            'commission_rate'   => $this->money($rate),
            'commission_amount' => $this->money($amount),
            'status'            => $status,
            'created_at'        => $now,
            'approved_at'       => in_array($status, ['approved', 'paid'], true) ? $now : null,
            'paid_at'           => $status === 'paid' ? $now : null,
        ]);

        $this->respond($ok
            ? ['status' => 'success', 'id' => (int) $wpdb->insert_id]
            : ['status' => 'error', 'message' => 'The sale could not be saved']);
    }

    /** Body: id plus any of order_total, commission_rate, commission_amount, status. */
    public function update_affiliate_sale($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);
        $sale = $this->get_row($t['sales'], $id, $auth);

        if (!$sale) {
            $this->respond(['status' => 'error', 'message' => 'Sale not found']);
        }

        $data = [];
        foreach (['order_total', 'commission_rate', 'commission_amount'] as $field) {
            if (isset($body[$field]) && $body[$field] !== '') {
                $data[$field] = $this->money($body[$field]);
            }
        }

        if (isset($body['status'])) {
            if (!in_array($body['status'], self::SALE_STATUSES, true)) {
                $this->respond(['status' => 'error', 'message' => 'Unknown status']);
            }
            $data['status'] = $body['status'];
            $now = current_time('mysql');

            if (in_array($body['status'], ['approved', 'paid'], true) && empty($sale['approved_at'])) {
                $data['approved_at'] = $now;
            }
            if ($body['status'] === 'paid' && empty($sale['paid_at'])) {
                $data['paid_at'] = $now;
            }
            if ($body['status'] !== 'paid') {
                $data['paid_at'] = null;
            }
            if (in_array($body['status'], ['pending', 'rejected'], true)) {
                $data['approved_at'] = null;
            }
        }

        if (!$data) {
            $this->respond(['status' => 'success', 'id' => $id]);
        }

        $ok = $wpdb->update($t['sales'], $data, ['id' => $id]);

        $this->respond($ok !== false
            ? ['status' => 'success', 'id' => $id]
            : ['status' => 'error', 'message' => 'The sale could not be saved']);
    }

    public function delete_affiliate_sales($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        $this->respond($this->delete_ids($this->tables()['sales'], $request));
    }


    /* ------------------------------------------------------------------ */
    /* Clicks                                                              */
    /* ------------------------------------------------------------------ */

    /** Body: paged, per_page, order, affiliate_id, keyword, from_date, to_date. */
    public function get_affiliate_clicks($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        list($where, $params) = $this->common_where($auth, $body);

        $keyword = isset($body['keyword']) ? trim(sanitize_text_field($body['keyword'])) : '';
        if ($keyword !== '') {
            $like    = '%' . $wpdb->esc_like($keyword) . '%';
            $where[] = '(x.affiliate_code LIKE %s OR x.landing_url LIKE %s OR x.referrer_url LIKE %s OR u.display_name LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }

        /* ip_hash and session_id are not sent: they only identify visitors. */
        $select = 'x.id, x.affiliate_id, x.landing_url, x.referrer_url, x.user_agent, x.created_at';

        $this->respond($this->paged_list($t['clicks'], $select, $where, $params, $body));
    }

    public function delete_affiliate_clicks($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        $this->respond($this->delete_ids($this->tables()['clicks'], $request));
    }


    /* ------------------------------------------------------------------ */
    /* Payouts                                                             */
    /* ------------------------------------------------------------------ */

    /** Body: paged, per_page, order, status, affiliate_id, keyword, from_date, to_date. */
    public function get_affiliate_payouts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        list($where, $params) = $this->common_where($auth, $body);

        $status = isset($body['status']) ? sanitize_key($body['status']) : '';
        if ($status) {
            $where[]  = 'x.status = %s';
            $params[] = $status;
        }

        $method = isset($body['payment_method']) ? sanitize_text_field($body['payment_method']) : '';
        if ($method) {
            $where[]  = 'x.payment_method = %s';
            $params[] = $method;
        }

        /* source: "request" (the affiliate asked) or "store" (added in the dashboard). */
        $source = isset($body['source']) ? sanitize_key($body['source']) : '';
        if ($source === 'request') {
            $where[] = 'x.requested_at IS NOT NULL';
        } elseif ($source === 'store') {
            $where[] = 'x.requested_at IS NULL';
        }

        $keyword = isset($body['keyword']) ? trim(sanitize_text_field($body['keyword'])) : '';
        if ($keyword !== '') {
            $like    = '%' . $wpdb->esc_like($keyword) . '%';
            $where[] = '(x.transaction_id LIKE %s OR a.affiliate_code LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
            array_push($params, $like, $like, $like, $like);
        }

        $this->respond($this->paged_list($t['payouts'], 'x.*', $where, $params, $body));
    }

    public function get_affiliate_payout($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $t      = $this->tables();
        $body   = $this->body($request);
        $payout = $this->get_row($t['payouts'], absint($body['id'] ?? 0), $auth);

        $this->respond($payout
            ? ['status' => 'success', 'payout' => $payout, 'can_edit' => $auth['manager']]
            : ['status' => 'error', 'message' => 'Payout not found']);
    }

    private function payout_fields($body)
    {
        $data = [];

        if (isset($body['amount']) && $body['amount'] !== '') {
            $data['amount'] = $this->money($body['amount']);
        }
        if (isset($body['payment_method'])) {
            $data['payment_method'] = sanitize_text_field($body['payment_method']);
        }
        if (isset($body['transaction_id'])) {
            $data['transaction_id'] = sanitize_text_field($body['transaction_id']);
        }
        if (isset($body['notes'])) {
            $data['notes'] = sanitize_textarea_field($body['notes']);
        }

        return $data;
    }

    /** Body: affiliate_id, amount, payment_method, transaction_id, status, notes. */
    public function create_affiliate_payout($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $affiliate_id = absint($body['affiliate_id'] ?? 0);
        if (!$affiliate_id || !$this->affiliate_exists($affiliate_id)) {
            $this->respond(['status' => 'error', 'message' => 'Choose an affiliate']);
        }

        $status = in_array($body['status'] ?? '', self::PAYOUT_STATUSES, true) ? $body['status'] : 'pending';
        $now    = current_time('mysql');

        $below = $this->below_min_payout($body['amount'] ?? 0);
        if ($below) {
            $this->respond(['status' => 'error', 'message' => $below]);
        }

        /* A failed or cancelled payout draws nothing, so only live ones are checked. */
        if (in_array($status, self::PAYOUT_DRAWING_STATUSES, true)) {
            $over = $this->over_balance($affiliate_id, (float) ($body['amount'] ?? 0));
            if ($over) {
                $this->respond(['status' => 'error', 'message' => $over]);
            }
        }

        $data = array_merge(['amount' => 0], $this->payout_fields($body), [
            'affiliate_id' => $affiliate_id,
            'status'       => $status,
            'created_at'   => $now,
            'paid_at'      => $status === 'paid' ? $now : null,
        ]);

        $ok = $wpdb->insert($t['payouts'], $data);
        $new_id = (int) $wpdb->insert_id;

        if ($ok && $status === 'paid') {
            do_action('combo_store_affiliate_payout_paid', ['payout_id' => $new_id, 'affiliate_id' => $affiliate_id]);
        }

        $this->respond($ok
            ? ['status' => 'success', 'id' => $new_id]
            : ['status' => 'error', 'message' => 'The payout could not be saved']);
    }

    /** Body: id plus any of affiliate_id, amount, payment_method, transaction_id, status, notes. */
    public function update_affiliate_payout($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t      = $this->tables();
        $body   = $this->body($request);
        $id     = absint($body['id'] ?? 0);
        $payout = $this->get_row($t['payouts'], $id, $auth);

        if (!$payout) {
            $this->respond(['status' => 'error', 'message' => 'Payout not found']);
        }

        $data = $this->payout_fields($body);

        /* Only a changed amount is checked, so payouts made under a lower minimum stay editable. */
        if (isset($data['amount']) && abs($data['amount'] - (float) $payout['amount']) >= 0.01) {
            $below = $this->below_min_payout($data['amount']);
            if ($below) {
                $this->respond(['status' => 'error', 'message' => $below]);
            }
        }

        if (!empty($body['affiliate_id'])) {
            $affiliate_id = absint($body['affiliate_id']);
            if (!$this->affiliate_exists($affiliate_id)) {
                $this->respond(['status' => 'error', 'message' => 'Unknown affiliate']);
            }
            $data['affiliate_id'] = $affiliate_id;
        }

        if (isset($body['status'])) {
            if (!in_array($body['status'], self::PAYOUT_STATUSES, true)) {
                $this->respond(['status' => 'error', 'message' => 'Unknown status']);
            }
            $data['status'] = $body['status'];

            if ($body['status'] === 'paid' && empty($payout['paid_at'])) {
                $data['paid_at'] = current_time('mysql');
            }
            if ($body['status'] !== 'paid') {
                $data['paid_at'] = null;
            }
        }

        /*
            Balance check, only when the edit draws more from a balance than the
            payout already did: a larger amount, another affiliate, or a failed /
            cancelled payout made live again. Pending -> paid draws nothing new,
            and payouts recorded before this rule stay editable.
        */
        $was_live = in_array($payout['status'], self::PAYOUT_DRAWING_STATUSES, true);
        $new = [
            'affiliate_id' => (int) ($data['affiliate_id'] ?? $payout['affiliate_id']),
            'amount'       => (float) ($data['amount'] ?? $payout['amount']),
            'status'       => $data['status'] ?? $payout['status'],
        ];
        $draws_more = in_array($new['status'], self::PAYOUT_DRAWING_STATUSES, true) && (
            !$was_live
            || $new['affiliate_id'] !== (int) $payout['affiliate_id']
            || $new['amount'] > (float) $payout['amount'] + 0.005
        );
        if ($draws_more) {
            $over = $this->over_balance($new['affiliate_id'], $new['amount'], $id);
            if ($over) {
                $this->respond(['status' => 'error', 'message' => $over]);
            }
        }

        if (!$data) {
            $this->respond(['status' => 'success', 'id' => $id]);
        }

        /*
            Becoming paid is claimed in one conditional UPDATE, so only one of two
            simultaneous "Mark paid" clicks wins and the affiliate is told once.
        */
        $became_paid = false;
        if (($data['status'] ?? null) === 'paid' && $payout['status'] !== 'paid') {
            $became_paid = (bool) $wpdb->query($wpdb->prepare(
                "UPDATE {$t['payouts']} SET status = 'paid', paid_at = %s WHERE id = %d AND status <> 'paid'",
                $data['paid_at'] ?? current_time('mysql'),
                $id
            ));
            unset($data['status'], $data['paid_at']);
        }

        /*
            The store turning down an affiliate's request: claimed the same way, so
            the affiliate is told once. Any payout that began as their request
            (requested_at set) counts, from requested, pending, processing or
            failed - not from paid, where "nothing has been paid" would be untrue.
            A payout the store created itself was never the affiliate's ask.
            `cancel_message` (optional) is passed to the email, not stored.
        */
        $request_cancelled = false;
        if (($data['status'] ?? null) === 'cancelled' && !empty($payout['requested_at']) && !in_array($payout['status'], ['cancelled', 'paid'], true)) {
            $request_cancelled = (bool) $wpdb->query($wpdb->prepare(
                "UPDATE {$t['payouts']} SET status = 'cancelled', paid_at = NULL
                 WHERE id = %d AND requested_at IS NOT NULL AND status NOT IN ('cancelled','paid')",
                $id
            ));
            unset($data['status'], $data['paid_at']);
        }

        $ok = $data ? $wpdb->update($t['payouts'], $data, ['id' => $id]) : true;

        if ($became_paid) {
            do_action('combo_store_affiliate_payout_paid', ['payout_id' => $id, 'affiliate_id' => (int) ($data['affiliate_id'] ?? $payout['affiliate_id'])]);
        }
        if ($request_cancelled) {
            do_action('combo_store_affiliate_payout_request_cancelled', [
                'payout_id'    => $id,
                'affiliate_id' => (int) ($data['affiliate_id'] ?? $payout['affiliate_id']),
                'message'      => isset($body['cancel_message']) ? sanitize_textarea_field(substr((string) $body['cancel_message'], 0, 2000)) : '',
            ]);
        }

        $this->respond($ok !== false
            ? ['status' => 'success', 'id' => $id]
            : ['status' => 'error', 'message' => 'The payout could not be saved']);
    }

    /**
     * An affiliate asks to be paid. Affiliates only.
     *
     * Body: amount (default: everything available), payment_method, notes (where
     * to send it, e.g. a wallet or account number), affiliate_id (only needed
     * when the user has more than one account). Creates a `requested` payout,
     * held to the minimum payout and to the available balance. One open
     * request at a time. Fires combo_store_affiliate_payout_requested.
     */
    public function request_affiliate_payout($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        if ($auth['manager'] || empty($auth['affiliate_ids'])) {
            $this->respond(['status' => 'error', 'message' => 'Only affiliates can request a payout']);
        }

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $affiliate_id = absint($body['affiliate_id'] ?? 0) ?: $auth['affiliate_ids'][0];
        if (!in_array($affiliate_id, $auth['affiliate_ids'], true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown affiliate account']);
        }

        $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$t['affiliates']} WHERE id = %d", $affiliate_id));
        if ($status !== 'active') {
            $this->respond(['status' => 'error', 'message' => 'Your affiliate account is inactive']);
        }

        if ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$t['payouts']} WHERE affiliate_id = %d AND status = 'requested'",
            $affiliate_id
        ))) {
            $this->respond(['status' => 'error', 'message' => 'You already have a payout request waiting. Cancel it to ask for a different amount.']);
        }

        $available = $this->available_balance($affiliate_id);
        $amount    = isset($body['amount']) && $body['amount'] !== '' ? $this->money($body['amount']) : $available;

        if ($amount <= 0) {
            $this->respond(['status' => 'error', 'message' => 'There is nothing available to pay out yet']);
        }

        $problem = $this->below_min_payout($amount) ?? $this->over_balance($affiliate_id, $amount);
        if ($problem) {
            $this->respond(['status' => 'error', 'message' => $problem]);
        }

        $ok = $wpdb->insert($t['payouts'], [
            'affiliate_id'   => $affiliate_id,
            'amount'         => $amount,
            'payment_method' => isset($body['payment_method']) ? sanitize_text_field($body['payment_method']) : null,
            'notes'          => isset($body['notes']) ? sanitize_textarea_field($body['notes']) : null,
            'status'         => 'requested',
            'requested_at'   => current_time('mysql'),
            'created_at'     => current_time('mysql'),
        ]);

        if (!$ok) {
            $this->respond(['status' => 'error', 'message' => 'The request could not be saved']);
        }

        $payout_id = (int) $wpdb->insert_id;

        do_action('combo_store_affiliate_payout_requested', [
            'payout_id'    => $payout_id,
            'affiliate_id' => $affiliate_id,
            'user_id'      => (int) $auth['user']->ID,
            'amount'       => $amount,
        ]);

        $this->respond(['status' => 'success', 'id' => $payout_id]);
    }

    const MAIL_WRAP_STYLE = 'font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;font-size:14px;line-height:1.5;color:#111827;max-width:560px';

    /**
     * Text for an email body. Escaped twice on purpose: ComboStoreMails::send_email()
     * runs the body through wp_specialchars_decode(), which would turn a single
     * esc_html() back into live markup. The outer pass is htmlspecialchars() with
     * double encoding on: esc_html() never re-encodes an entity, so
     * esc_html(esc_html()) is no different from one call.
     */
    public function mail_escape($value)
    {
        return htmlspecialchars(esc_html((string) $value), ENT_QUOTES, 'UTF-8', true);
    }

    /** One label / value line of an email's details table. $value is HTML, already escaped. */
    public function mail_row($label, $value)
    {
        return '<tr><td style="padding:6px 12px 6px 0;color:#6b7280;vertical-align:top;white-space:nowrap">' . $this->mail_escape($label)
            . '</td><td style="padding:6px 0;color:#111827">' . $value . '</td></tr>';
    }

    /** Settings > Affiliates "Email affiliates when a request is cancelled". On unless switched off. */
    private function email_on_cancel_enabled()
    {
        $settings = get_option('combo_store_settings', []);
        $enabled  = !(is_array($settings) && isset($settings['affiliates']['emailOnCancel']) && $settings['affiliates']['emailOnCancel'] === false);

        return (bool) apply_filters('combo_store_affiliate_email_on_cancel', $enabled);
    }

    /**
     * combo_store_affiliate_payout_request_cancelled: tell the affiliate the
     * store turned their request down, with the store's message if it gave one.
     * The amount is back in their available balance, so they can ask again.
     */
    public function email_payout_request_cancelled($args)
    {
        if (!$this->email_on_cancel_enabled()) return;

        global $wpdb;
        $t = $this->tables();

        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT x.*, {$this->affiliate_columns()} FROM {$t['payouts']} x {$this->affiliate_joins()} WHERE x.id = %d",
            absint($args['payout_id'] ?? 0)
        ), ARRAY_A);
        if (!$payout || $payout['status'] !== 'cancelled' || !is_email($payout['affiliate_email'])) return;

        $e   = [$this, 'mail_escape'];
        $row = [$this, 'mail_row'];

        $site      = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $name      = $payout['affiliate_name'] ?: 'there';
        $amount    = number_format((float) $payout['amount'], 2);
        $available = number_format(max(0, $this->available_balance((int) $payout['affiliate_id'])), 2);
        $message   = trim((string) ($args['message'] ?? ''));
        $store     = $this->payout_notify_recipients();

        $html = '<div style="' . self::MAIL_WRAP_STYLE . '">'
            . '<h2 style="font-size:18px;margin:0 0 4px">Your payout request was cancelled</h2>'
            . '<p style="margin:0 0 16px;color:#4b5563">Hi ' . $e($name) . ', ' . $e($site) . ' has cancelled your request for ' . $e($amount) . '.</p>'
            . ($message !== ''
                ? '<div style="margin:0 0 16px;padding:10px 12px;border-left:3px solid #634FE5;background:#f9fafb;color:#374151">' . nl2br($e($message)) . '</div>'
                : '')
            . '<table style="border-collapse:collapse">'
            . $row('Requested amount', '<strong>' . $e($amount) . '</strong>')
            . $row('Requested on', $e($payout['requested_at'] ?: $payout['created_at']))
            . $row('Payout', '#' . (int) $payout['id'])
            . $row('Available to request', $e($available))
            . '</table>'
            . '<p style="margin:16px 0 0;color:#4b5563">Nothing has been paid for this request, and the amount is back in your balance. '
            . 'You can send a new request from <strong>Affiliates Dashboard</strong>.'
            . ($store ? ' Reply to this email if you have questions.' : '') . '</p>'
            . '</div>';

        $mail = [
            'email_to'      => $payout['affiliate_email'],
            'reply_to'      => $store[0] ?? '',
            'reply_to_name' => $store ? $site : '',
            'subject'       => sprintf('[%s] Payout request cancelled: %s', $site, $amount),
            'html'          => $html,
            'attachments'   => [],
        ];

        if (class_exists('ComboStore\Classes\ComboStoreMails')) {
            (new ComboStoreMails())->send_email($mail);
        }
    }

    /** Settings > Affiliates "Email affiliates when a payout is paid". On unless switched off. */
    private function email_on_paid_enabled()
    {
        $settings = get_option('combo_store_settings', []);
        $enabled  = !(is_array($settings) && isset($settings['affiliates']['emailOnPaid']) && $settings['affiliates']['emailOnPaid'] === false);

        return (bool) apply_filters('combo_store_affiliate_email_on_paid', $enabled);
    }

    /**
     * combo_store_affiliate_payout_paid: tell the affiliate the money is on its way.
     *
     * Leaves out the payout's notes, which are the store's to write. Reply-To is
     * the store's first payout-request address, so a question reaches it.
     */
    public function email_payout_paid($args)
    {
        if (!$this->email_on_paid_enabled()) return;

        global $wpdb;
        $t = $this->tables();

        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT x.*, {$this->affiliate_columns()} FROM {$t['payouts']} x {$this->affiliate_joins()} WHERE x.id = %d",
            absint($args['payout_id'] ?? 0)
        ), ARRAY_A);
        if (!$payout || $payout['status'] !== 'paid' || !is_email($payout['affiliate_email'])) return;

        $e   = [$this, 'mail_escape'];
        $row = [$this, 'mail_row'];

        $site      = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $name      = $payout['affiliate_name'] ?: 'there';
        $amount    = number_format((float) $payout['amount'], 2);
        $available = number_format(max(0, $this->available_balance((int) $payout['affiliate_id'])), 2);
        $store     = $this->payout_notify_recipients();

        $html = '<div style="' . self::MAIL_WRAP_STYLE . '">'
            . '<h2 style="font-size:18px;margin:0 0 4px">Your payout has been sent</h2>'
            . '<p style="margin:0 0 16px;color:#4b5563">Hi ' . $e($name) . ', ' . $e($site) . ' has paid ' . $e($amount) . ' of your affiliate commission.</p>'
            . '<table style="border-collapse:collapse">'
            . $row('Amount', '<strong>' . $e($amount) . '</strong>')
            . ($payout['payment_method'] ? $row('Payment method', $e($payout['payment_method'])) : '')
            . ($payout['transaction_id'] ? $row('Transaction ID', $e($payout['transaction_id'])) : '')
            . $row('Paid on', $e($payout['paid_at'] ?: current_time('mysql')))
            . $row('Payout', '#' . (int) $payout['id'])
            . $row('Still available', $e($available))
            . '</table>'
            . '<p style="margin:16px 0 0;color:#4b5563">It can take a little while to reach you, depending on the payment method. '
            . 'You can see all your payouts under <strong>Affiliates Dashboard &rsaquo; Affiliate payouts</strong>.'
            . ($store ? ' Reply to this email if anything looks wrong.' : '') . '</p>'
            . '</div>';

        $mail = [
            'email_to'      => $payout['affiliate_email'],
            'reply_to'      => $store[0] ?? '',
            'reply_to_name' => $store ? $site : '',
            'subject'       => sprintf('[%s] Payout sent: %s', $site, $amount),
            'html'          => $html,
            'attachments'   => [],
        ];

        if (class_exists('ComboStore\Classes\ComboStoreMails')) {
            (new ComboStoreMails())->send_email($mail);
        }
    }

    /**
     * Who hears about payout requests: Settings > Affiliates "notification
     * emails" (affiliates.notifyEmails, comma separated), else the WordPress
     * admin email.
     */
    private function payout_notify_recipients()
    {
        $settings = get_option('combo_store_settings', []);
        $raw      = is_array($settings) ? (string) ($settings['affiliates']['notifyEmails'] ?? '') : '';

        $emails = array_values(array_filter(array_map('sanitize_email', preg_split('/[\s,;]+/', $raw)), 'is_email'));
        if (!$emails && is_email(get_option('admin_email'))) {
            $emails = [get_option('admin_email')];
        }

        return apply_filters('combo_store_affiliate_payout_request_recipients', $emails);
    }

    /**
     * combo_store_affiliate_payout_requested: tell the store an affiliate wants
     * to be paid. Reply-To is the affiliate, so answering reaches them. Has no
     * link: the server does not know the dashboard's address, and taking one
     * from the request would let it be forged into the email.
     */
    public function email_payout_request($args)
    {
        $to = $this->payout_notify_recipients();
        if (!$to) return;

        global $wpdb;
        $t = $this->tables();

        $payout = $wpdb->get_row($wpdb->prepare(
            "SELECT x.*, {$this->affiliate_columns()} FROM {$t['payouts']} x {$this->affiliate_joins()} WHERE x.id = %d",
            absint($args['payout_id'] ?? 0)
        ), ARRAY_A);
        if (!$payout) return;

        $site      = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $name      = $payout['affiliate_name'] ?: ('Affiliate #' . $payout['affiliate_id']);
        $amount    = number_format((float) $payout['amount'], 2);
        $available = number_format(max(0, $this->available_balance((int) $payout['affiliate_id'])), 2);

        $e   = [$this, 'mail_escape'];
        $row = [$this, 'mail_row'];

        $html = '<div style="' . self::MAIL_WRAP_STYLE . '">'
            . '<h2 style="font-size:18px;margin:0 0 4px">Payout request #' . (int) $payout['id'] . '</h2>'
            . '<p style="margin:0 0 16px;color:#4b5563">' . $e($name) . ' has asked to be paid their affiliate commission.</p>'
            . '<table style="border-collapse:collapse">'
            . $row('Amount', '<strong>' . $e($amount) . '</strong>')
            . $row('Affiliate', $e($name) . ($payout['affiliate_code'] ? ' (' . $e($payout['affiliate_code']) . ')' : ''))
            . ($payout['affiliate_email'] ? $row('Email', $e($payout['affiliate_email'])) : '')
            . ($payout['payment_method'] ? $row('Payment method', $e($payout['payment_method'])) : '')
            . ($payout['notes'] ? $row('Send it to', nl2br($e($payout['notes']))) : '')
            . $row('Left after this', $e($available))
            . $row('Requested', $e($payout['created_at']))
            . '</table>'
            . '<p style="margin:16px 0 0;color:#4b5563">Find it under <strong>Affiliates Dashboard &rsaquo; Affiliate payouts</strong>, filtered to "Requested". '
            . 'Mark it processing or paid once you send the money, or cancel it.</p>'
            . '</div>';

        $mail = [
            'email_to'      => $to,
            'reply_to'      => $payout['affiliate_email'] ?: '',
            'reply_to_name' => $payout['affiliate_email'] ? $name : '',
            'subject'       => sprintf('[%s] Payout request #%d: %s from %s', $site, $payout['id'], $amount, $name),
            'html'          => $html,
            'attachments'   => [],
        ];

        if (class_exists('ComboStore\Classes\ComboStoreMails')) {
            (new ComboStoreMails())->send_email($mail);
        }
    }

    /** An affiliate withdraws their own request while it is still `requested`. Body: id. */
    public function cancel_affiliate_payout_request($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t      = $this->tables();
        $body   = $this->body($request);
        $id     = absint($body['id'] ?? 0);
        $payout = $this->get_row($t['payouts'], $id, $auth);

        if (!$payout || $auth['manager'] || $payout['status'] !== 'requested') {
            $this->respond(['status' => 'error', 'message' => 'Only a waiting request of yours can be cancelled']);
        }

        $ok = $wpdb->update($t['payouts'], ['status' => 'cancelled', 'paid_at' => null], ['id' => $id, 'status' => 'requested']);

        $this->respond($ok
            ? ['status' => 'success', 'id' => $id]
            : ['status' => 'error', 'message' => 'The request could not be cancelled']);
    }

    public function delete_affiliate_payouts($request)
    {
        $auth = $this->auth_manager($request);
        if (is_wp_error($auth)) return $auth;

        $this->respond($this->delete_ids($this->tables()['payouts'], $request));
    }
}
