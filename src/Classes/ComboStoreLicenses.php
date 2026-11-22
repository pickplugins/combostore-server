<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


/*
    Licenses live in {prefix}cstore_licenses with their meta in {prefix}cstore_licenses_meta
    (see ComboStoreDatabase::create_tables). Columns:

    id, userid, user_email, order_id, product_id, variation_id, license_key, status,
    activation_limit, activation_count, domains (JSON), meta (JSON),
    created_at, updated_at, renewed_at, expires_at
*/
class ComboStoreLicenses
{
    public $object_name = "licenses";

    /* expires_at is NOT NULL, so a license that never expires carries this date. */
    const LIFETIME = '9999-12-31 23:59:59';

    /* activation_limit of 0 (or empty) means unlimited activations. */
    const UNLIMITED = 0;

    /* Same set Lemon Squeezy uses for license keys. */
    const STATUSES = ['active', 'inactive', 'disabled', 'expired'];

    public function __construct() {}


    function licenses_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_licenses';
    }

    function licenses_meta_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_licenses_meta';
    }


    /**
     * Column => format map for every writable column. JSON columns are encoded before insert/update.
     */
    function columns()
    {
        return [
            'userid'           => '%d',
            'user_email'       => '%s',
            'order_id'         => '%d',
            'product_id'       => '%d',
            'variation_id'     => '%d',
            'license_key'      => '%s',
            'status'           => '%s',
            'activation_limit' => '%s',
            'activation_count' => '%d',
            'domains'          => '%s',
            'meta'             => '%s',
            'created_at'       => '%s',
            'updated_at'       => '%s',
            'renewed_at'       => '%s',
            'expires_at'       => '%s',
        ];
    }


    /**
     * Sanitize the license fields present in $params. Only keys that were passed are returned,
     * so the same routine serves create (with defaults merged on top) and partial updates.
     */
    function sanitize_fields($params)
    {
        $params = (array) $params;
        $fields = [];

        if (isset($params['userid']) || isset($params['user_id'])) {
            $fields['userid'] = intval(isset($params['userid']) ? $params['userid'] : $params['user_id']);
        }
        if (isset($params['user_email']))       $fields['user_email'] = sanitize_email($params['user_email']);
        if (isset($params['order_id']))         $fields['order_id'] = intval($params['order_id']);
        if (isset($params['product_id']))       $fields['product_id'] = intval($params['product_id']);
        if (array_key_exists('variation_id', $params)) {
            $fields['variation_id'] = ($params['variation_id'] === null || $params['variation_id'] === '') ? null : intval($params['variation_id']);
        }
        if (isset($params['license_key']))      $fields['license_key'] = sanitize_text_field($params['license_key']);
        if (isset($params['status']))           $fields['status'] = sanitize_key($params['status']);
        if (isset($params['activation_limit'])) $fields['activation_limit'] = (string) absint($params['activation_limit']);
        if (isset($params['activation_count'])) $fields['activation_count'] = absint($params['activation_count']);

        if (isset($params['domains'])) {
            $domains = is_array($params['domains']) ? $params['domains'] : json_decode((string) $params['domains'], true);
            $fields['domains'] = wp_json_encode(array_values(array_unique(array_filter(array_map([$this, 'normalize_domain'], (array) $domains)))));
        }
        if (isset($params['meta'])) {
            $meta = (is_array($params['meta']) || is_object($params['meta'])) ? $params['meta'] : json_decode((string) $params['meta'], true);
            $fields['meta'] = wp_json_encode($meta ? $meta : new \stdClass());
        }

        foreach (['created_at', 'renewed_at', 'expires_at'] as $date_key) {
            if (!empty($params[$date_key])) {
                $fields[$date_key] = $this->sanitize_datetime($params[$date_key]);
            }
        }

        return $fields;
    }


    function create_license($params)
    {
        global $wpdb;
        $table_licenses = $this->licenses_table();

        $response = [];
        $now = $this->get_datetime();

        $defaults = [
            'userid'           => 0,
            'user_email'       => '',
            'order_id'         => 0,
            'product_id'       => 0,
            'variation_id'     => null,
            'license_key'      => '',
            'status'           => 'active',
            'activation_limit' => (string) self::UNLIMITED,
            'activation_count' => 0,
            'domains'          => wp_json_encode([]),
            'meta'             => wp_json_encode(new \stdClass()),
            'created_at'       => $now,
            'renewed_at'       => $now,
            'expires_at'       => self::LIFETIME,
        ];

        $data = array_merge($defaults, $this->sanitize_fields($params));
        $data['updated_at'] = $now;

        if (empty($data['license_key'])) {
            $data['license_key'] = $this->generate_license_key();
        } elseif ($this->get_license_by_key($data['license_key'])) {
            $response['errors'] = true;
            $response['message'] = __('License key already exists.', 'combo-store-server');
            return $response;
        }

        if (empty($data['user_email']) && !empty($data['userid'])) {
            $user = get_user_by('id', $data['userid']);
            $data['user_email'] = $user ? $user->user_email : '';
        }

        $data['activation_count'] = max((int) $data['activation_count'], count((array) json_decode($data['domains'], true)));

        $wpdb->insert($table_licenses, $data, $this->formats_for($data));

        if ($wpdb->last_error) {
            $response['errors'] = true;
            $response['message'] = $wpdb->last_error;
        } else {
            $response['success'] = true;
            $response['id'] = $wpdb->insert_id;
            $response['license_key'] = $data['license_key'];
        }

        return $response;
    }


    function get_license($license_id)
    {
        global $wpdb;
        $table_licenses = $this->licenses_table();

        $licenseRow = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_licenses WHERE id = %d", $license_id)
        );

        $response = [];
        $response['license'] = $licenseRow ? $this->prepare_license($licenseRow) : null;

        return $response;
    }


    function get_license_by_key($license_key)
    {
        global $wpdb;
        $table_licenses = $this->licenses_table();

        $licenseRow = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_licenses WHERE license_key = %s", sanitize_text_field($license_key))
        );

        return $licenseRow ? $this->prepare_license($licenseRow) : null;
    }


    function get_licenses($prams)
    {
        global $wpdb;
        $table_licenses = $this->licenses_table();

        $responses = [];

        $isAdmin      = isset($prams['isAdmin']) ? (bool) $prams['isAdmin'] : false;
        $user_id      = isset($prams['user_id']) ? intval($prams['user_id']) : 0;
        $page         = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order        = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";
        $status       = isset($prams['status']) ? sanitize_key($prams['status']) : "";
        $product_id   = isset($prams['product_id']) ? absint($prams['product_id']) : 0;
        $variation_id = isset($prams['variation_id']) ? absint($prams['variation_id']) : 0;
        $order_id     = isset($prams['order_id']) ? absint($prams['order_id']) : 0;
        /* Several orders at once, e.g. one page of the customer's order list. */
        $order_ids    = isset($prams['order_ids']) ? array_slice(array_values(array_filter(array_map('absint', (array) $prams['order_ids']))), 0, 100) : [];
        $user_email   = isset($prams['user_email']) ? sanitize_email($prams['user_email']) : "";
        $keyword      = isset($prams['keyword']) ? sanitize_text_field($prams['keyword']) : "";
        $customer     = isset($prams['customer']) ? (array) $prams['customer'] : [];
        $customer_id  = isset($customer['id']) ? absint($customer['id']) : 0;
        $expired      = isset($prams['expired']) ? $prams['expired'] : null; // true: only expired, false: only unexpired

        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';

        $page = ($page == 0) ? 1 : $page;
        $offset = ($page - 1) * $per_page;

        $where  = [];
        $params = [];

        /* Non-admins only ever see their own licenses, whatever customer filter they send. */
        if (!$isAdmin) {
            $where[]  = "userid = %d";
            $params[] = $user_id;
        } elseif (!empty($customer_id)) {
            $where[]  = "userid = %d";
            $params[] = $customer_id;
        }

        if (!empty($product_id)) {
            $where[]  = "product_id = %d";
            $params[] = $product_id;
        }
        if (!empty($variation_id)) {
            $where[]  = "variation_id = %d";
            $params[] = $variation_id;
        }
        if (!empty($order_id)) {
            $where[]  = "order_id = %d";
            $params[] = $order_id;
        }
        if (!empty($order_ids)) {
            $where[]  = "order_id IN (" . implode(', ', array_fill(0, count($order_ids), '%d')) . ")";
            $params   = array_merge($params, $order_ids);
        }
        if (!empty($user_email)) {
            $where[]  = "user_email = %s";
            $params[] = $user_email;
        }
        if ($keyword !== '' && $keyword[0] === '#') {
            /* "#12" means an ID: license #12 or order #12, nothing fuzzy. */
            $number   = absint(substr($keyword, 1));
            $where[]  = "(id = %d OR order_id = %d)";
            $params[] = $number;
            $params[] = $number;
        } elseif ($keyword !== '') {
            $like     = '%' . $wpdb->esc_like($keyword) . '%';
            $number   = ctype_digit($keyword) ? absint($keyword) : 0;
            $where[]  = "(id = %d OR order_id = %d OR license_key LIKE %s OR user_email LIKE %s OR domains LIKE %s)";
            array_push($params, $number, $number, $like, $like, $like);
        }
        if ($expired !== null && $expired !== '') {
            $where[]  = filter_var($expired, FILTER_VALIDATE_BOOLEAN) ? "expires_at < %s" : "expires_at >= %s";
            $params[] = $this->get_datetime();
        }
        if ($from_date && $to_date) {
            $where[]  = "created_at BETWEEN %s AND %s";
            $params[] = $from_date . " 00:00:00";
            $params[] = $to_date . " 23:59:59";
        }

        /* Counts per status for the filter, taken before the status filter itself. */
        $base_where_sql = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';
        $counts_sql = "SELECT status, COUNT(id) FROM $table_licenses $base_where_sql GROUP BY status";
        $status_counts = array_fill_keys(self::STATUSES, 0);
        foreach ($wpdb->get_results(!empty($params) ? $wpdb->prepare($counts_sql, $params) : $counts_sql, ARRAY_N) as $row) {
            $status_counts[$row[0]] = (int) $row[1];
        }

        if (!empty($status)) {
            $where[]  = "status = %s";
            $params[] = $status;
        }

        $where_sql = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM $table_licenses $where_sql ORDER BY id $order LIMIT %d, %d";
        $entries = $wpdb->get_results($wpdb->prepare($sql, array_merge($params, [(int) $offset, (int) $per_page])));

        $count_sql = "SELECT COUNT(id) FROM $table_licenses $where_sql";
        $total = (int) $wpdb->get_var(!empty($params) ? $wpdb->prepare($count_sql, $params) : $count_sql);

        foreach ($entries as $key => $entry) {
            $entries[$key] = $this->prepare_license($entry);
        }

        $max_pages = $per_page > 0 ? ceil($total / $per_page) : 1;

        $responses['total'] = $total;
        $responses['max_pages'] = (int) $max_pages;
        $responses['status_counts'] = $status_counts;
        $responses['posts'] = $entries;

        return $responses;
    }


    /**
     * Partial update: only the columns present in $params change.
     */
    function update_license($params)
    {
        global $wpdb;
        $table = $this->licenses_table();

        $id = isset($params['id']) ? absint($params['id']) : 0;
        if (empty($id)) return false;

        $data = $this->sanitize_fields($params);

        if (isset($data['license_key'])) {
            $existing = $this->get_license_by_key($data['license_key']);
            if ($existing && (int) $existing->id !== $id) return false;
        }

        /* Keep activation_count in step with the domains list when domains are replaced. */
        if (isset($data['domains']) && !isset($params['activation_count'])) {
            $data['activation_count'] = count((array) json_decode($data['domains'], true));
        }

        $data['updated_at'] = $this->get_datetime();

        $updated = $wpdb->update($table, $data, ['id' => $id], $this->formats_for($data), ['%d']);

        return $updated !== false;
    }


    function delete_license($license_id)
    {
        global $wpdb;

        $license_id = absint($license_id);
        $wpdb->delete($this->licenses_meta_table(), ['object_id' => $license_id], ['%d']);
        $deleted = $wpdb->delete($this->licenses_table(), ['id' => $license_id], ['%d']);

        return (bool) $deleted;
    }


    function renew_license($license_id, $expires_at)
    {
        return $this->update_license([
            'id'         => $license_id,
            'status'     => 'active',
            'renewed_at' => $this->get_datetime(),
            'expires_at' => $expires_at,
        ]);
    }


    function search_licenses($keyword, $limit = 10)
    {
        global $wpdb;
        $table_licenses = $this->licenses_table();

        $like = '%' . $wpdb->esc_like(sanitize_text_field($keyword)) . '%';

        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_licenses WHERE license_key LIKE %s OR user_email LIKE %s ORDER BY id DESC LIMIT %d",
            $like,
            $like,
            absint($limit)
        ));

        foreach ($entries as $key => $entry) {
            $entries[$key] = $this->prepare_license($entry);
        }

        return ['posts' => $entries];
    }



    /* ---------- Licenses from orders ---------- */


    /**
     * Whether an order has reached the point where its licenses are issued:
     * it is completed, or it has been paid.
     */
    function order_is_licensable($order)
    {
        $order = (array) $order;
        $status = isset($order['status']) ? $order['status'] : '';
        $payment_status = isset($order['payment_status']) ? $order['payment_status'] : '';

        $licensable = $status === 'completed' || $payment_status === 'paid';

        /* Lets a store add e.g. "processing", or hold licenses for review. */
        return (bool) apply_filters('combo_store_order_is_licensable', $licensable, $order);
    }


    /**
     * Normalise a `license` attribute - the same shape the dashboard's "License
     * Management" card saves and the storefront copies onto cart items:
     * { enable, limit, interval (day|week|month|year), interval_count }.
     * Returns null unless licensing is enabled.
     */
    function normalize_license_settings($raw)
    {
        $settings = is_string($raw) ? json_decode($raw, true) : (is_object($raw) ? (array) $raw : $raw);

        if (!is_array($settings) || empty($settings['enable']) || in_array($settings['enable'], ['false', '0', 0], true)) {
            return null;
        }

        return [
            'limit'          => isset($settings['limit']) ? absint($settings['limit']) : 0,
            'interval'       => isset($settings['interval']) ? sanitize_key($settings['interval']) : '',
            'interval_count' => isset($settings['interval_count']) ? max(1, absint($settings['interval_count'])) : 1,
        ];
    }


    /**
     * The product's `license` attribute (cstore_products.license, keyed by the
     * product's post id), or null when the product does not issue licenses.
     */
    function get_product_license_settings($product_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cstore_products';

        return $this->normalize_license_settings(
            $wpdb->get_var($wpdb->prepare("SELECT license FROM $table WHERE product_id = %d LIMIT 1", $product_id))
        );
    }


    /**
     * The variation row (id, product_id, license) or null. `license` is only
     * selected when the column exists, since older installs lack it.
     */
    function get_variation($variation_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cstore_products_variations';

        static $has_license_column = null;
        if ($has_license_column === null) {
            $has_license_column = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $table LIKE %s", 'license'));
        }

        $columns = $has_license_column ? 'id, product_id, license' : 'id, product_id';

        return $wpdb->get_row($wpdb->prepare("SELECT $columns FROM $table WHERE id = %d", $variation_id), ARRAY_A);
    }


    /**
     * Work out which product / variation an order line is, and the license
     * attribute that applies to it: the variation's own when it has licensing
     * enabled, otherwise the parent product's.
     *
     * The settings always come from the catalogue, never from the cart item: a
     * cart is built in the shopper's browser, so a `license` sent with it could
     * ask for unlimited sites with no expiry on any product.
     *
     * @return array|null ['product_id', 'variation_id', 'settings'] or null if
     *                    the line is not licensed.
     */
    function resolve_item_license($item)
    {
        $product_id   = absint(isset($item['product_id']) ? $item['product_id'] : 0);
        $variation_id = absint(isset($item['variation_id']) ? $item['variation_id'] : 0);

        $variation = $variation_id ? $this->get_variation($variation_id) : null;

        if ($variation) {
            /* Older orders saved the cart's `id` - the variation id - as product_id. */
            $product_id = absint($variation['product_id']) ? absint($variation['product_id']) : $product_id;
        } elseif ($variation_id === $product_id) {
            /* Simple products sometimes repeat their own id as variation_id. */
            $variation_id = 0;
        }

        if (!$product_id) return null;

        $settings = null;
        if ($variation && isset($variation['license'])) {
            $settings = $this->normalize_license_settings($variation['license']);
        }
        if (!$settings) {
            $settings = $this->get_product_license_settings($product_id);
        }
        if (!$settings) return null;

        return [
            'product_id'   => $product_id,
            'variation_id' => $variation ? $variation_id : 0,
            'settings'     => $settings,
        ];
    }


    /**
     * Expiry for a license issued at $from: interval_count × interval later,
     * or LIFETIME when the product has no interval.
     */
    function expiry_from_settings($settings, $from)
    {
        $units = ['day' => 'days', 'week' => 'weeks', 'month' => 'months', 'year' => 'years'];
        if (empty($settings['interval']) || !isset($units[$settings['interval']])) {
            return self::LIFETIME;
        }

        $date = new \DateTime($from);
        $date->modify('+' . $settings['interval_count'] . ' ' . $units[$settings['interval']]);

        return $date->format('Y-m-d H:i:s');
    }


    /**
     * Issue the licenses an order is owed: one license per cart item (order
     * line) whose product or variation has its `license` attribute enabled, with
     * that attribute's activation limit multiplied by the line's quantity. Two
     * lines of the same product get two licenses.
     *
     * Safe to call repeatedly (order created as paid, then completed, then a
     * gateway webhook...). Order lines are deleted and re-inserted whenever the
     * order is edited, so their ids cannot mark a line as done; instead, for
     * each product / variation, only the lines beyond the licenses that order
     * already has are issued. A per-order lock stops two requests issuing the
     * same license at once.
     *
     * @param int  $order_id
     * @param bool $force    Skip the completed/paid check.
     * @return array ['created' => [license rows], 'skipped' => [reasons]]
     */
    function generate_order_licenses($order_id, $force = false)
    {
        global $wpdb;

        $order_id = absint($order_id);
        $result = ['created' => [], 'skipped' => []];
        if (!$order_id) return $result;

        $table_orders = $wpdb->prefix . 'cstore_orders';
        $table_items  = $wpdb->prefix . 'cstore_order_items';
        $table        = $this->licenses_table();

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_orders WHERE id = %d", $order_id), ARRAY_A);
        if (!$order) return $result;

        if (!$force && !$this->order_is_licensable($order)) {
            $result['skipped'][] = 'Order is not completed or paid yet.';
            return $result;
        }

        /* A subscription renewal extends the keys the customer already has. */
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        $renewal_of_order = absint($ComboStoreObjectMeta->get_meta('orders', $order_id, 'renewal_of_order'));
        if ($renewal_of_order) {
            return $this->renew_order_licenses($order_id, $renewal_of_order);
        }

        $lock = 'cstore_order_licenses_' . $order_id;
        if (!$wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 10)", $lock))) {
            $result['skipped'][] = 'Another request is issuing licenses for this order.';
            return $result;
        }

        try {
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_items WHERE order_id = %d", $order_id), ARRAY_A);

            $user_id = absint($order['userid']);
            $email = !empty($order['billing_email']) ? $order['billing_email'] : '';
            if (!$email && $user_id) {
                $user = get_user_by('id', $user_id);
                $email = $user ? $user->user_email : '';
            }

            $now = $this->get_datetime();

            /* Licenses this order already has, per product / variation. */
            $existing = [];
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT product_id, IFNULL(variation_id, 0) AS variation_id, COUNT(id) AS total FROM $table WHERE order_id = %d GROUP BY product_id, IFNULL(variation_id, 0)",
                $order_id
            ), ARRAY_A);
            foreach ((array) $rows as $row) {
                $existing[absint($row['product_id']) . ':' . absint($row['variation_id'])] = (int) $row['total'];
            }

            foreach ((array) $items as $item) {
                $resolved = $this->resolve_item_license($item);
                if (!$resolved) continue;

                $product_id   = $resolved['product_id'];
                $variation_id = $resolved['variation_id'];
                $settings     = $resolved['settings'];
                $group        = $product_id . ':' . $variation_id;

                /* This line is covered by a license issued earlier (or by hand). */
                if (!empty($existing[$group])) {
                    $existing[$group]--;
                    $result['skipped'][] = "Line \"" . (isset($item['product_name']) ? $item['product_name'] : "#$product_id") . "\" already has a license.";
                    continue;
                }

                $quantity = max(1, absint($item['quantity']));
                $limit = $settings['limit'] > 0 ? $settings['limit'] * $quantity : self::UNLIMITED;

                $created = $this->create_license([
                    'userid'           => $user_id,
                    'user_email'       => $email,
                    'order_id'         => $order_id,
                    'product_id'       => $product_id,
                    'variation_id'     => $variation_id ? $variation_id : null,
                    'status'           => 'active',
                    'activation_limit' => $limit,
                    'created_at'       => $now,
                    'renewed_at'       => $now,
                    'expires_at'       => $this->expiry_from_settings($settings, $now),
                    'meta'             => [
                        'source'        => 'order',
                        'order_item_id' => absint($item['id']),
                        'quantity'      => $quantity,
                        'product_name'  => isset($item['product_name']) ? $item['product_name'] : '',
                        'license'       => $settings,
                        /* "subscription" when bought with Subscribe: renewals extend only those keys. */
                        'purchase_type' => (new ComboStoreSubscriptions())->item_meta($item)['purchase_type'] ?? 'one_time',
                    ],
                ]);

                if (!empty($created['success'])) {
                    $license = $this->get_license($created['id'])['license'];
                    $result['created'][] = $license;
                    do_action('combo_store_license_created', $license, $order);
                } else {
                    $result['skipped'][] = "Product #$product_id: " . (!empty($created['message']) ? $created['message'] : 'license could not be created.');
                }
            }
        } finally {
            $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock));
        }

        if (!empty($result['created'])) {
            $lines = array_map(function ($license) {
                $name = $license->product_title ? $license->product_title : 'Product #' . $license->product_id;
                return "License #{$license->id} for {$name}: {$license->license_key}";
            }, $result['created']);

            (new ComboStoreOrders())->add_order_note([
                'order_id' => $order_id,
                'note'     => count($lines) . " license(s) issued.\n" . implode("\n", $lines),
                'author'   => 'Licenses',
            ]);

            do_action('combo_store_order_licenses_created', $order_id, $result['created'], $order);
        }

        return $result;
    }



    /**
     * A paid subscription renewal order: push the expiry of the original
     * order's licenses for the renewed products forward by the product's
     * license interval, from today or from the current expiry if that is
     * later. Lifetime keys are left alone. Runs once per renewal order.
     *
     * @return array ['created' => [], 'skipped' => [..], 'renewed' => [license rows]]
     */
    function renew_order_licenses($order_id, $original_order_id)
    {
        global $wpdb;
        $result = ['created' => [], 'skipped' => [], 'renewed' => []];

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        if ($ComboStoreObjectMeta->get_meta('orders', $order_id, 'licenses_renewed')) {
            $result['skipped'][] = 'Licenses already renewed for this order.';
            return $result;
        }

        $lock = 'cstore_order_licenses_' . $order_id;
        if (!$wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 10)", $lock))) return $result;

        try {
            if ($ComboStoreObjectMeta->get_meta('orders', $order_id, 'licenses_renewed')) return $result;

            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_order_items WHERE order_id = %d", $order_id), ARRAY_A);
            $now = $this->get_datetime();

            foreach ((array) $items as $item) {
                $resolved = $this->resolve_item_license($item);
                if (!$resolved) continue;

                $licenses = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$this->licenses_table()} WHERE order_id = %d AND product_id = %d AND IFNULL(variation_id, 0) = %d",
                    $original_order_id,
                    $resolved['product_id'],
                    $resolved['variation_id']
                ));

                /* If the order bought the product both ways, only the subscribed
                   key renews; keys issued before this was recorded all do. */
                $subscribed = array_filter((array) $licenses, function ($license) {
                    $meta = json_decode((string) $license->meta, true);
                    return ($meta['purchase_type'] ?? '') === 'subscription';
                });
                if (!empty($subscribed)) $licenses = $subscribed;

                foreach ((array) $licenses as $license) {
                    if ($license->expires_at === self::LIFETIME) continue;

                    $from = strtotime($license->expires_at) > strtotime($now) ? $license->expires_at : $now;
                    $this->renew_license($license->id, $this->expiry_from_settings($resolved['settings'], $from));
                    $result['renewed'][] = $this->get_license($license->id)['license'];
                }
            }

            $ComboStoreObjectMeta->update_meta('orders', $order_id, 'licenses_renewed', 1);
        } finally {
            $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock));
        }

        if (!empty($result['renewed'])) {
            $lines = array_map(function ($license) {
                return "License #{$license->id} extended to {$license->expires_at}";
            }, $result['renewed']);

            (new ComboStoreOrders())->add_order_note([
                'order_id' => $order_id,
                'note'     => "Subscription renewal:\n" . implode("\n", $lines),
                'author'   => 'Licenses',
            ]);
        }

        return $result;
    }


    /* ---------- Activation / validation ---------- */


    /**
     * Check a key (optionally for a domain). Returns ['valid' => bool, 'code' => string, 'license' => object|null].
     */
    function validate_license($license_key, $domain = '')
    {
        $license = $this->get_license_by_key($license_key);

        if (!$license) return ['valid' => false, 'code' => 'not_found', 'license' => null];
        if ($license->status !== 'active') return ['valid' => false, 'code' => 'inactive', 'license' => $license];
        if ($license->is_expired) return ['valid' => false, 'code' => 'expired', 'license' => $license];

        $domain = $this->normalize_domain($domain);
        if ($domain && !in_array($domain, $license->domains, true)) {
            return ['valid' => false, 'code' => 'domain_not_activated', 'license' => $license];
        }

        return ['valid' => true, 'code' => 'valid', 'license' => $license];
    }


    function activate_domain($license_key, $domain)
    {
        $domain = $this->normalize_domain($domain);
        if (!$domain) return ['success' => false, 'code' => 'invalid_domain'];

        $check = $this->validate_license($license_key);
        if (!$check['valid']) return ['success' => false, 'code' => $check['code']];

        $license = $check['license'];
        $domains = $license->domains;

        if (in_array($domain, $domains, true)) {
            return ['success' => true, 'code' => 'already_activated', 'license' => $license];
        }

        $limit = (int) $license->activation_limit;
        if ($limit !== self::UNLIMITED && count($domains) >= $limit) {
            return ['success' => false, 'code' => 'activation_limit_reached', 'license' => $license];
        }

        $domains[] = $domain;
        $this->update_license(['id' => $license->id, 'domains' => $domains]);

        return ['success' => true, 'code' => 'activated', 'license' => $this->get_license_by_key($license_key)];
    }


    function deactivate_domain($license_key, $domain)
    {
        $domain = $this->normalize_domain($domain);
        $license = $this->get_license_by_key($license_key);

        if (!$license) return ['success' => false, 'code' => 'not_found'];
        if (!in_array($domain, $license->domains, true)) {
            return ['success' => false, 'code' => 'domain_not_activated', 'license' => $license];
        }

        $domains = array_values(array_diff($license->domains, [$domain]));
        $this->update_license(['id' => $license->id, 'domains' => $domains]);

        return ['success' => true, 'code' => 'deactivated', 'license' => $this->get_license_by_key($license_key)];
    }



    /* ---------- Meta (cstore_licenses_meta) ---------- */


    function get_meta($license_id, $meta_key, $default = '')
    {
        global $wpdb;
        $table = $this->licenses_meta_table();

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM $table WHERE object_id = %d AND meta_key = %s LIMIT 1",
            $license_id,
            $meta_key
        ));

        return $value === null ? $default : maybe_unserialize($value);
    }


    function update_meta($license_id, $meta_key, $meta_value)
    {
        global $wpdb;
        $table = $this->licenses_meta_table();

        $meta_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE object_id = %d AND meta_key = %s LIMIT 1",
            $license_id,
            $meta_key
        ));

        $value = maybe_serialize($meta_value);

        if ($meta_id) {
            return $wpdb->update($table, ['meta_value' => $value], ['id' => $meta_id], ['%s'], ['%d']) !== false;
        }

        return (bool) $wpdb->insert(
            $table,
            ['object_id' => $license_id, 'meta_key' => $meta_key, 'meta_value' => $value],
            ['%d', '%s', '%s']
        );
    }


    function delete_meta($license_id, $meta_key)
    {
        global $wpdb;

        return (bool) $wpdb->delete(
            $this->licenses_meta_table(),
            ['object_id' => $license_id, 'meta_key' => $meta_key],
            ['%d', '%s']
        );
    }



    /* ---------- Helpers ---------- */


    /**
     * Decode JSON columns, cast numbers, and attach the user / product details the dashboard shows.
     */
    function prepare_license($entry)
    {
        $domains = json_decode((string) $entry->domains, true);
        $meta = json_decode((string) $entry->meta, true);

        $entry->id               = (int) $entry->id;
        $entry->userid           = (int) $entry->userid;
        $entry->order_id         = (int) $entry->order_id;
        $entry->product_id       = (int) $entry->product_id;
        $entry->variation_id     = $entry->variation_id === null ? null : (int) $entry->variation_id;
        $entry->activation_limit = (int) $entry->activation_limit;
        $entry->activation_count = (int) $entry->activation_count;
        $entry->domains          = is_array($domains) ? $domains : [];
        $entry->meta             = is_array($meta) ? $meta : [];
        $entry->is_lifetime      = $entry->expires_at === self::LIFETIME;
        $entry->is_expired       = !$entry->is_lifetime && strtotime($entry->expires_at) < strtotime($this->get_datetime());

        $user = $entry->userid ? get_user_by('id', $entry->userid) : false;
        $entry->user = [
            'id'   => $user ? $user->ID : '',
            'name'  => $user ? $user->display_name : '',
            'email' => $user ? $user->user_email : '',
        ];

        $entry->product_title   = $entry->product_id ? get_the_title($entry->product_id) : '';
        $entry->variation_title = $entry->variation_id ? get_the_title($entry->variation_id) : '';

        return $entry;
    }


    function formats_for($data)
    {
        $columns = $this->columns();
        $formats = [];

        foreach (array_keys($data) as $key) {
            $formats[] = $columns[$key];
        }

        return $formats;
    }


    function generate_license_key()
    {
        global $wpdb;
        $table = $this->licenses_table();

        do {
            $key = strtoupper(implode('-', str_split(bin2hex(random_bytes(16)), 8)));
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE license_key = %s", $key));
        } while ($exists);

        return $key;
    }


    function normalize_domain($domain)
    {
        $domain = strtolower(trim((string) $domain));
        if ($domain === '') return '';

        $host = wp_parse_url(strpos($domain, '://') === false ? 'http://' . $domain : $domain, PHP_URL_HOST);
        $host = $host ? preg_replace('/^www\./', '', $host) : '';

        return sanitize_text_field($host);
    }


    function sanitize_datetime($value)
    {
        $timestamp = strtotime((string) $value);
        return $timestamp ? gmdate('Y-m-d H:i:s', $timestamp) : self::LIFETIME;
    }


    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }


    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = gmdate('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = gmdate('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}
