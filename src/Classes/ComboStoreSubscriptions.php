<?php

namespace ComboStore\Classes;

use Error;
use DateTime;
use DateInterval;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreSubscriptions
{
    public $object_name = "";


    public function __construct() {}

    function create_subscription($params)
    {




        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions';

        $response = [];




        // ✅ Extract variables safely
        $user_id            = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $order_id           = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $plan_id            = isset($params['plan_id']) ? intval($params['plan_id']) : 0;
        /* Per-product subscriptions name their product; older whole-order ones leave both NULL. */
        $product_id         = !empty($params['product_id']) ? intval($params['product_id']) : null;
        $variation_id       = !empty($params['variation_id']) ? intval($params['variation_id']) : null;
        $status             = isset($params['status']) ? sanitize_text_field($params['status']) : 'active';

        $start_date         = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : current_time('mysql');
        $end_date           = !empty($params['end_date']) ? sanitize_text_field($params['end_date']) : null;
        $next_billing_date  = !empty($params['next_billing_date']) ? sanitize_text_field($params['next_billing_date']) : null;

        $cancel_at_period_end = isset($params['cancel_at_period_end']) ? intval($params['cancel_at_period_end']) : 0;

        $renewal_interval   = isset($params['renewal_interval']) ? sanitize_text_field($params['renewal_interval']) : 'month';
        $interval_count      = isset($params['interval_count']) ? intval($params['interval_count']) : 1;

        $trial_start        = !empty($params['trial_start']) ? sanitize_text_field($params['trial_start']) : null;
        $trial_end          = !empty($params['trial_end']) ? sanitize_text_field($params['trial_end']) : null;

        $payment_method_id  = isset($params['payment_method_id']) ? intval($params['payment_method_id']) : 0;
        $last_payment_date  = !empty($params['last_payment_date']) ? sanitize_text_field($params['last_payment_date']) : null;
        $total_amount = isset($params['total_amount']) ? floatval($params['total_amount']) : 0.00;
        $subtotal_amount = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0.00;

        // ✅ Build insert data
        $data = array(
            'userid'              => $user_id,
            'order_id'            => $order_id,
            'plan_id'             => $plan_id,
            'product_id'          => $product_id,
            'variation_id'        => $variation_id,
            'status'              => $status,
            'start_date'          => $start_date,
            'end_date'            => $end_date,
            'next_billing_date'   => $next_billing_date,
            'cancel_at_period_end' => $cancel_at_period_end,
            'renewal_interval'    => $renewal_interval,
            'interval_count'       => $interval_count,
            'trial_start'         => $trial_start,
            'trial_end'           => $trial_end,
            'payment_method_id'   => $payment_method_id,
            'last_payment_date'   => $last_payment_date,
            'total_amount' => $total_amount,
            'subtotal_amount' => $subtotal_amount,
            'created_at'          => current_time('mysql'),
            'updated_at'          => current_time('mysql'),
        );

        $format = array(
            '%d',  // userid
            '%d',  // order_id
            '%d',  // plan_id
            '%d',  // product_id (NULL stays NULL)
            '%d',  // variation_id
            '%s',  // status
            '%s',  // start_date
            '%s',  // end_date
            '%s',  // next_billing_date
            '%d',  // cancel_at_period_end
            '%s',  // renewal_interval
            '%d',  // interval_count
            '%s',  // trial_start
            '%s',  // trial_end
            '%d',  // payment_method_id
            '%s',  // last_payment_date
            '%f',  // total_amount
            '%f',  // subtotal_amount
            '%s',  // created_at
            '%s',  // updated_at
        );

        // ✅ Insert into table
        $wpdb->insert($table, $data, $format);
        $subscription_id = $wpdb->insert_id;



        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        // $ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);


        // $userData = get_user_by("ID", $user_id);

        // $action_prams = array_merge($data, ["order_id" => $order_id, "email" => $userData->user_email, "name" => $userData->display_name]);


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {


            $response['success'] = true;
            $response['subscription_id'] = $subscription_id;

            //do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }





    function get_subscription($subscription_id, $by = 'id')
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_orders = $prefix . 'cstore_orders';
        $table_subscriptions = $prefix . 'cstore_subscriptions';

if($by == 'order_id'){
            $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE order_id = %d",
                $subscription_id
            ),
            ARRAY_A
        );
}else {
        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE id = %d",
                $subscription_id
            ),
            ARRAY_A
        );
}



        $oderId = isset($subscriptionRow['order_id']) ? $subscriptionRow['order_id'] : 0;


        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_orders WHERE id = %d",
                $oderId
            ),
            ARRAY_A
        );




        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;

        $orderRow = empty($orderRow) ? [] : $orderRow;



        $response['subscription'] = $subscriptionRow;
        $response['order'] = $orderRow;


        return $response;
    }
    function update_subscription($params)
    {

        /*
            Partial update: only fields present in $params change. Previously
            every missing field was overwritten with a default (total_amount 0,
            status ''), the format list was out of order (last_payment_date
            saved as %d, i.e. "2026"), and next_billing_date was recomputed
            from today on every save.

            next_billing_date: an explicitly changed date wins; otherwise it is
            recomputed only when the billing cycle (interval or count) changes.
        */
        global $wpdb;
        $table = $wpdb->prefix . 'cstore_subscriptions';

        $subscription_id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$subscription_id) {
            return false;
        }

        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $subscription_id), ARRAY_A);
        if (!$current) {
            return false;
        }

        $valid_date = function ($value) {
            $value = substr(sanitize_text_field((string) $value), 0, 10);
            $d = DateTime::createFromFormat('Y-m-d', $value);
            return ($d && $d->format('Y-m-d') === $value) ? $value : null;
        };

        $data = [];
        $format = [];

        if (isset($params['status']) && in_array($params['status'], ['active', 'paused', 'canceled', 'expired'], true)) {
            $data['status'] = $params['status'];
            $format[] = '%s';
            /* Ending a subscription records when, if no end date is set yet. */
            if (in_array($params['status'], ['canceled', 'expired'], true) && empty($current['end_date']) && !array_key_exists('end_date', $params)) {
                $data['end_date'] = current_time('Y-m-d');
                $format[] = '%s';
            }
        }
        if (isset($params['renewal_interval']) && in_array($params['renewal_interval'], ['day', 'week', 'month', 'year'], true)) {
            $data['renewal_interval'] = $params['renewal_interval'];
            $format[] = '%s';
        }
        if (isset($params['interval_count'])) {
            $data['interval_count'] = max(1, intval($params['interval_count']));
            $format[] = '%d';
        }
        if (isset($params['cancel_at_period_end'])) {
            $data['cancel_at_period_end'] = !empty($params['cancel_at_period_end']) && $params['cancel_at_period_end'] !== '0' ? 1 : 0;
            $format[] = '%d';
        }
        foreach (['total_amount', 'subtotal_amount'] as $money) {
            if (isset($params[$money]) && $params[$money] !== '') {
                $data[$money] = max(0, floatval($params[$money]));
                $format[] = '%f';
            }
        }
        foreach (['last_payment_date', 'end_date', 'trial_end'] as $date_field) {
            if (array_key_exists($date_field, $params)) {
                $data[$date_field] = $params[$date_field] ? $valid_date($params[$date_field]) : null;
                $format[] = '%s';
            }
        }

        $requested_next = array_key_exists('next_billing_date', $params) && $params['next_billing_date']
            ? $valid_date($params['next_billing_date'])
            : null;
        $cycle_changed = (isset($data['renewal_interval']) && $data['renewal_interval'] !== $current['renewal_interval'])
            || (isset($data['interval_count']) && (int) $data['interval_count'] !== (int) $current['interval_count']);

        if ($requested_next && $requested_next !== substr((string) $current['next_billing_date'], 0, 10)) {
            $data['next_billing_date'] = $requested_next;
            $format[] = '%s';
        } elseif ($cycle_changed) {
            /* From today in the store's timezone - an empty date makes the
               helper use PHP's (UTC) clock, which can land a day early. */
            $data['next_billing_date'] = $this->get_next_billing_date(
                current_time('Y-m-d'),
                $data['renewal_interval'] ?? $current['renewal_interval'],
                $data['interval_count'] ?? $current['interval_count']
            );
            $format[] = '%s';
        }

        if (empty($data)) {
            return true;
        }

        $data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        $updated = $wpdb->update($table, $data, ['id' => $subscription_id], $format, ['%d']);

        /* 0 rows changed is still success; false is a database error. */
        return $updated !== false;
    }

    /* ================================================================
       Per-product subscriptions

       A product's `subscription` attribute (cstore_products.subscription,
       set in the product editor) lets the customer choose "Subscribe" on the
       product page. That order line is saved with meta
       { purchase_type: "subscription" }. When the order is completed or paid,
       each such line becomes one subscription row (product, variation,
       interval from the catalogue, amount locked at the line's price). On
       its billing date a daily job creates a pending renewal order, which
       the customer pays like any other.
       ================================================================ */

    const INTERVALS = ['day', 'week', 'month', 'year'];


    /**
     * The product's subscribe option, or null when it has none:
     * { interval, interval_count, setup_fee, discount }.
     */
    function get_product_subscription_settings($product_id)
    {
        global $wpdb;
        $raw = $wpdb->get_var($wpdb->prepare("SELECT subscription FROM {$wpdb->prefix}cstore_products WHERE product_id = %d LIMIT 1", $product_id));
        $settings = $raw ? json_decode($raw, true) : null;

        if (!is_array($settings) || empty($settings['enable']) || in_array($settings['enable'], ['false', '0', 0], true)) {
            return null;
        }

        $interval = isset($settings['interval']) ? sanitize_key($settings['interval']) : 'month';

        return [
            'interval'       => in_array($interval, self::INTERVALS, true) ? $interval : 'month',
            'interval_count' => isset($settings['interval_count']) ? max(1, absint($settings['interval_count'])) : 1,
            'setup_fee'      => isset($settings['setup_fee']) ? max(0, floatval($settings['setup_fee'])) : 0,
            'discount'       => isset($settings['discount']) ? min(100, max(0, floatval($settings['discount']))) : 0,
        ];
    }


    /**
     * An order line's meta as an array. Lines re-saved from the dashboard can
     * hold it JSON-encoded twice, so decode until it is not a string.
     */
    function item_meta($item)
    {
        $meta = is_array($item) ? ($item['meta'] ?? null) : ($item->meta ?? null);
        for ($i = 0; $i < 3 && is_string($meta); $i++) {
            $decoded = json_decode($meta, true);
            if ($decoded === null) break;
            $meta = $decoded;
        }
        return is_array($meta) ? $meta : [];
    }


    /**
     * Completed or paid: the same rule that issues licenses and downloads.
     */
    function order_is_active($order)
    {
        $order = (array) $order;
        return (isset($order['status']) && $order['status'] === 'completed')
            || (isset($order['payment_status']) && $order['payment_status'] === 'paid');
    }


    /**
     * The order line resolved to its parent product and variation (older
     * lines saved a variation's id as the product id).
     */
    function resolve_item_product($item)
    {
        $product_id = absint($item['product_id'] ?? 0);
        $variation_id = absint($item['variation_id'] ?? 0);

        $licenses = new ComboStoreLicenses();
        $variation = $variation_id ? $licenses->get_variation($variation_id) : null;

        if ($variation && absint($variation['product_id'])) {
            $product_id = absint($variation['product_id']);
        } elseif ($variation_id === $product_id) {
            $variation_id = 0;
        }

        return [$product_id, $variation ? $variation_id : 0];
    }


    /**
     * Create the subscriptions an order is owed (one per subscribed line), or,
     * for a paid renewal order, record the payment on the subscriptions it
     * renews. Safe to call repeatedly: lines already covered are skipped, and
     * a per-order lock stops two requests creating the same subscription.
     *
     * @return array ['created' => [ids], 'renewed' => [ids]]
     */
    function generate_order_subscriptions($order_id)
    {
        global $wpdb;
        $order_id = absint($order_id);
        $result = ['created' => [], 'renewed' => []];
        if (!$order_id) return $result;

        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $order_id), ARRAY_A);
        if (!$order || !$this->order_is_active($order)) return $result;

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        $today = current_time('Y-m-d');

        /* A renewal order: its payment keeps the subscriptions going. */
        $renewal_of = $ComboStoreObjectMeta->get_meta('orders', $order_id, 'renewal_of_subscription');
        if (!empty($renewal_of)) {
            foreach (array_filter(array_map('absint', explode(',', (string) $renewal_of))) as $subscription_id) {
                $wpdb->update(
                    "{$wpdb->prefix}cstore_subscriptions",
                    ['last_payment_date' => $today, 'updated_at' => current_time('mysql')],
                    ['id' => $subscription_id],
                    ['%s', '%s'],
                    ['%d']
                );
                $result['renewed'][] = $subscription_id;
            }
            return $result;
        }

        $lock = 'cstore_order_subscriptions_' . $order_id;
        if (!$wpdb->get_var($wpdb->prepare("SELECT GET_LOCK(%s, 10)", $lock))) return $result;

        try {
            $table = "{$wpdb->prefix}cstore_subscriptions";

            /* Subscriptions this order already has, per product / variation. */
            $existing = [];
            foreach ((array) $wpdb->get_results($wpdb->prepare(
                "SELECT product_id, IFNULL(variation_id, 0) AS variation_id, COUNT(id) AS total FROM $table WHERE order_id = %d AND product_id IS NOT NULL GROUP BY product_id, IFNULL(variation_id, 0)",
                $order_id
            ), ARRAY_A) as $row) {
                $existing[absint($row['product_id']) . ':' . absint($row['variation_id'])] = (int) $row['total'];
            }

            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_order_items WHERE order_id = %d", $order_id), ARRAY_A);

            foreach ((array) $items as $item) {
                $meta = $this->item_meta($item);
                if (($meta['purchase_type'] ?? '') !== 'subscription') continue;

                [$product_id, $variation_id] = $this->resolve_item_product($item);
                if (!$product_id) continue;

                /* The cycle comes from the catalogue, never from the cart. */
                $settings = $this->get_product_subscription_settings($product_id);
                if (!$settings) continue;

                $group = $product_id . ':' . $variation_id;
                if (!empty($existing[$group])) {
                    $existing[$group]--;
                    continue;
                }

                $quantity = max(1, absint($item['quantity']));
                $amount = round(floatval($item['price']) * $quantity, 2);

                $created = $this->create_subscription([
                    'user_id'           => absint($order['userid']),
                    'order_id'          => $order_id,
                    'product_id'        => $product_id,
                    'variation_id'      => $variation_id,
                    'status'            => 'active',
                    'start_date'        => $today,
                    'next_billing_date' => $this->get_next_billing_date($today, $settings['interval'], $settings['interval_count']),
                    'renewal_interval'  => $settings['interval'],
                    'interval_count'    => $settings['interval_count'],
                    'last_payment_date' => $today,
                    'total_amount'      => $amount,
                    'subtotal_amount'   => $amount,
                ]);

                if (!empty($created['success'])) {
                    $subscription_id = $created['subscription_id'];
                    /* What a renewal order re-creates: the line as it was bought. */
                    $ComboStoreObjectMeta->update_meta('subscriptions', $subscription_id, 'quantity', $quantity);
                    $ComboStoreObjectMeta->update_meta('subscriptions', $subscription_id, 'unit_price', floatval($item['price']));
                    $ComboStoreObjectMeta->update_meta('subscriptions', $subscription_id, 'product_name', (string) $item['product_name']);
                    $result['created'][] = $subscription_id;
                    do_action('combo_store_subscription_created', $subscription_id, $order);
                }
            }
        } finally {
            $wpdb->query($wpdb->prepare("SELECT RELEASE_LOCK(%s)", $lock));
        }

        if (!empty($result['created'])) {
            (new ComboStoreOrders())->add_order_note([
                'order_id' => $order_id,
                'note'     => count($result['created']) . ' subscription(s) started: #' . implode(', #', $result['created']) . '.',
                'author'   => 'Subscriptions',
            ]);
        }

        return $result;
    }


    /**
     * Every subscription of an order, with its product and the renewal
     * orders created from it; for a renewal order, the order it renews.
     */
    function get_order_subscriptions($order_id)
    {
        global $wpdb;
        $order_id = absint($order_id);
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_subscriptions WHERE order_id = %d ORDER BY id ASC", $order_id), ARRAY_A);

        foreach ($rows as &$row) {
            $row['product_title'] = $row['product_id'] ? get_the_title($row['product_id']) : '';
            $row['product_name'] = (string) $ComboStoreObjectMeta->get_meta('subscriptions', $row['id'], 'product_name');
            $row['quantity'] = (int) $ComboStoreObjectMeta->get_meta('subscriptions', $row['id'], 'quantity');
            $renewals = (string) $ComboStoreObjectMeta->get_meta('subscriptions', $row['id'], 'renewal_orders');
            $row['renewal_orders'] = array_values(array_filter(array_map('absint', explode(',', $renewals))));
        }
        unset($row);

        $renewal_of_order = absint($ComboStoreObjectMeta->get_meta('orders', $order_id, 'renewal_of_order'));
        $renewal_of_subscription = array_values(array_filter(array_map('absint', explode(',', (string) $ComboStoreObjectMeta->get_meta('orders', $order_id, 'renewal_of_subscription')))));

        /*
            Subscribed lines that have no subscription yet: the order is not
            paid or completed, so it has not started. Shown in the dashboard
            so a pending order does not look like it has no subscription.
            A renewal order's lines are renewals, not new subscriptions.
        */
        $pending = [];
        if (!$renewal_of_order) {
            $covered = [];
            foreach ($rows as $row) {
                $key = absint($row['product_id']) . ':' . absint($row['variation_id']);
                $covered[$key] = ($covered[$key] ?? 0) + 1;
            }

            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_order_items WHERE order_id = %d ORDER BY id ASC", $order_id), ARRAY_A);
            foreach ($items as $item) {
                $meta = $this->item_meta($item);
                if (($meta['purchase_type'] ?? '') !== 'subscription') continue;

                list($product_id, $variation_id) = $this->resolve_item_product($item);
                if (!$product_id) continue;

                $key = $product_id . ':' . $variation_id;
                if (!empty($covered[$key])) {
                    $covered[$key]--;
                    continue;
                }

                $settings = $this->get_product_subscription_settings($product_id);
                $quantity = max(1, absint($item['quantity']));
                $pending[] = [
                    'product_id'     => $product_id,
                    'variation_id'   => $variation_id,
                    'product_name'   => (string) $item['product_name'],
                    'quantity'       => $quantity,
                    'amount'         => round(floatval($item['price']) * $quantity, 2),
                    'interval'       => $settings['interval'] ?? null,
                    'interval_count' => $settings['interval_count'] ?? null,
                    /* The product's subscription was turned off since: paying won't start one. */
                    'enabled'        => (bool) $settings,
                ];
            }
        }

        return [
            'subscriptions'           => $rows,
            'pending'                 => $pending,
            'renewal_of_order'        => $renewal_of_order ?: null,
            'renewal_of_subscription' => $renewal_of_subscription,
        ];
    }


    /**
     * Create renewal orders for every product subscription that is due.
     * Run daily by WP-Cron (cstore_subscription_renewals); safe to run again.
     *
     * @param int[] $only Limit to these subscription ids and bill them now
     *                    (the dashboard's "Create renewal order now").
     * @return array ['orders' => [order ids], 'canceled' => [ids], 'skipped' => [..]]
     */
    function process_due_renewals($only = [])
    {
        global $wpdb;
        $result = ['orders' => [], 'canceled' => [], 'skipped' => []];

        if (!$wpdb->get_var("SELECT GET_LOCK('cstore_subscription_renewals', 0)")) {
            $result['skipped'][] = 'Renewals are already running.';
            return $result;
        }

        try {
            $table = "{$wpdb->prefix}cstore_subscriptions";
            $today = current_time('Y-m-d');
            $only = array_filter(array_map('absint', (array) $only));

            /* Only per-product subscriptions renew; older whole-order ones are records only. */
            if ($only) {
                $placeholders = implode(',', array_fill(0, count($only), '%d'));
                $due = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE id IN ($placeholders) AND product_id IS NOT NULL AND status = 'active'", $only), ARRAY_A);
            } else {
                $due = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE status = 'active' AND product_id IS NOT NULL AND next_billing_date IS NOT NULL AND next_billing_date <= %s ORDER BY order_id, id",
                    $today
                ), ARRAY_A);
            }

            $ComboStoreObjectMeta = new ComboStoreObjectMeta();
            $groups = [];

            foreach ((array) $due as $subscription) {
                $billing_date = $subscription['next_billing_date'] ? substr($subscription['next_billing_date'], 0, 10) : $today;

                /* Already renewed for this billing date (e.g. an earlier run). */
                if ($ComboStoreObjectMeta->get_meta('subscriptions', $subscription['id'], 'renewed_for_' . $billing_date)) {
                    $result['skipped'][] = "Subscription #{$subscription['id']} already renewed for $billing_date.";
                    continue;
                }

                if (!empty($subscription['cancel_at_period_end'])) {
                    $wpdb->update($table, ['status' => 'canceled', 'end_date' => $billing_date, 'updated_at' => current_time('mysql')], ['id' => $subscription['id']], ['%s', '%s', '%s'], ['%d']);
                    $result['canceled'][] = (int) $subscription['id'];
                    do_action('combo_store_subscription_ended', (int) $subscription['id']);
                    continue;
                }

                $groups[$subscription['order_id']][] = $subscription;
            }

            foreach ($groups as $original_order_id => $subscriptions) {
                $order_id = $this->create_renewal_order($original_order_id, $subscriptions);
                if (!$order_id) {
                    $result['skipped'][] = "Order #$original_order_id: the renewal order could not be created.";
                    continue;
                }
                $result['orders'][] = $order_id;

                foreach ($subscriptions as $subscription) {
                    $billing_date = $subscription['next_billing_date'] ? substr($subscription['next_billing_date'], 0, 10) : $today;
                    $ComboStoreObjectMeta->update_meta('subscriptions', $subscription['id'], 'renewed_for_' . $billing_date, $order_id);

                    $renewals = array_filter(explode(',', (string) $ComboStoreObjectMeta->get_meta('subscriptions', $subscription['id'], 'renewal_orders')));
                    $renewals[] = $order_id;
                    $ComboStoreObjectMeta->update_meta('subscriptions', $subscription['id'], 'renewal_orders', implode(',', $renewals));

                    /* Next date after today: periods missed while the job was
                       off are skipped, not billed one by one. */
                    $next = $billing_date;
                    do {
                        $next = $this->get_next_billing_date($next, $subscription['renewal_interval'], max(1, (int) $subscription['interval_count']));
                    } while ($next <= $today);

                    $wpdb->update($table, ['next_billing_date' => $next, 'updated_at' => current_time('mysql')], ['id' => $subscription['id']], ['%s', '%s'], ['%d']);
                }
            }
        } finally {
            $wpdb->query("SELECT RELEASE_LOCK('cstore_subscription_renewals')");
        }

        return $result;
    }


    /**
     * One pending order renewing these subscriptions (all from one original
     * order): the original order's customer, billing and shipping, one line
     * per subscription at its locked price, no setup fee.
     *
     * @return int The new order id, or 0.
     */
    function create_renewal_order($original_order_id, $subscriptions)
    {
        global $wpdb;
        $original = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $original_order_id), ARRAY_A);
        if (!$original) return 0;

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        $items = [];
        $total = 0;

        foreach ($subscriptions as $subscription) {
            $quantity = max(1, (int) $ComboStoreObjectMeta->get_meta('subscriptions', $subscription['id'], 'quantity'));
            $unit = $ComboStoreObjectMeta->get_meta('subscriptions', $subscription['id'], 'unit_price');
            $unit = $unit !== '' && $unit !== null ? floatval($unit) : round(floatval($subscription['total_amount']) / $quantity, 2);
            $name = (string) $ComboStoreObjectMeta->get_meta('subscriptions', $subscription['id'], 'product_name');

            $items[] = [
                'id'           => (int) $subscription['product_id'],
                'product_id'   => (int) $subscription['product_id'],
                'variation_id' => (int) $subscription['variation_id'],
                'title'        => $name !== '' ? $name : get_the_title($subscription['product_id']),
                'quantity'     => $quantity,
                'price'        => $unit,
                'subtotal'     => round($unit * $quantity, 2),
                'total'        => round($unit * $quantity, 2),
                'purchase_type' => 'subscription',
                'meta'         => ['purchase_type' => 'subscription', 'renewal_of_subscription' => (int) $subscription['id']],
            ];
            $total += $unit * $quantity;
        }

        $copy = ['userid', 'currency', 'payment_method', 'billing_name', 'billing_email', 'billing_phone', 'billing_address', 'billing_city', 'billing_country', 'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_country'];
        $params = array_intersect_key($original, array_flip($copy));

        $params = array_merge($params, [
            'status'          => 'pending',
            'payment_status'  => 'unpaid',
            'total_amount'    => round($total, 2),
            'subtotal_amount' => round($total, 2),
            'cartItems'       => $items,
            'order_note'      => 'Subscription renewal of order #' . $original_order_id . '.',
        ]);

        $created = (new ComboStoreOrders())->create_order($params);
        $order_id = !empty($created['order_id']) ? (int) $created['order_id'] : 0;

        if ($order_id) {
            $ids = implode(',', array_map(function ($s) { return (int) $s['id']; }, $subscriptions));
            $ComboStoreObjectMeta->update_meta('orders', $order_id, 'renewal_of_order', (int) $original_order_id);
            $ComboStoreObjectMeta->update_meta('orders', $order_id, 'renewal_of_subscription', $ids);
            do_action('combo_store_subscription_renewal_order', $order_id, $original_order_id, $subscriptions);
        }

        return $order_id;
    }


    function get_orders($order_id)
    {


        global $wpdb;
        $table_orders = $wpdb->prefix . "cstore_orders";


        $query = $wpdb->prepare(
            "SELECT * FROM $table_orders WHERE order_id = %d",
            $order_id
        );

        $orders = $wpdb->get_results($query);

        return $orders;
    }



    function delete_subscription($subscription_id)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions';


        $response = [];

        if (!$subscription_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $subscription_id,), array('%d'));


        $response['status'] = $status;

        return $response;
    }









function get_next_billing_date(
     $currentDate,
     $renewalInterval,
     $intervalCount
) {
  if (empty($currentDate)) {
        $date = new DateTime(); // today
    } else {
        $date = new DateTime($currentDate);
    }
    switch (strtolower($renewalInterval)) {
        case 'day':
            $date->add(new DateInterval("P{$intervalCount}D"));
            break;

        case 'week':
            $days = $intervalCount * 7;
            $date->add(new DateInterval("P{$days}D"));
            break;

        case 'month':
            $date->add(new DateInterval("P{$intervalCount}M"));
            break;

        /* `year` is a valid renewal_interval in the table's enum but was
           missing here, so saving a yearly subscription threw. */
        case 'year':
            $date->add(new DateInterval("P{$intervalCount}Y"));
            break;

        default:
            /* Global class: unqualified, PHP looked for ComboStore\Classes\InvalidArgumentException and died. */
            throw new \InvalidArgumentException("Invalid renewal interval");
    }

    return $date->format('Y-m-d');
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
