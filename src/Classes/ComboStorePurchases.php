<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreStocks;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStorePurchases
{
    public $object_name = "";


    public function __construct() {}

    function create_purchase($params)
    {


        $user_id         = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $status         = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';
        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $subtotal_amount   = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0;
        $discount_amount   = isset($params['discount_amount']) ? floatval($params['discount_amount']) : 0;
        $tax_amount   = isset($params['tax_amount']) ? floatval($params['tax_amount']) : 0;
        $shipping_amount   = isset($params['shipping_amount']) ? floatval($params['shipping_amount']) : 0;
        $advance_payment   = isset($params['advance_payment']) ? floatval($params['advance_payment']) : 0;
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';
        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
        $billing_name = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : '';
        $billing_email = isset($params['billing_email']) ? sanitize_email($params['billing_email']) : '';
        $billing_phone = isset($params['billing_phone']) ? sanitize_text_field($params['billing_phone']) : '';
        $billing_address = isset($params['billing_address']) ? sanitize_text_field($params['billing_address']) : '';



        $user_id     = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $supplier_id    = isset($params['supplier_id']) ? intval($params['supplier_id']) : 0;

        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
     $lineItems = isset($params['lineItems']) ? stripslashes_deep($params['lineItems']) : [];
        $created_at     = get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s');



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_purchases = $prefix . 'cstore_purchases';

        $response = [];






        $data = array(
            'status'        => $status,
            'category'  => $category,
            'total_amount'    => $total_amount,
            'subtotal_amount'    => $subtotal_amount,
            'discount_amount'  => $discount_amount,
            'tax_amount'  => $tax_amount,
            'shipping_amount'  => $shipping_amount,
            'advance_payment'  => $advance_payment,
            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,
            'billing_name'  => $billing_name,
            'billing_email'  => $billing_email,
            'billing_phone'  => $billing_phone,
            'billing_address'  => $billing_address,
            'user_id'      => $user_id,
            'supplier_id'     => $supplier_id,
            'note'            => $note,
            'created_at'      => $created_at
        );

        $format = array(
            '%s',   // status
            '%s',   // category
            '%f',   // total_amount
            '%f',   // subtotal_amount
            '%s',   // discount_amount
            '%f',   // tax_amount
            '%f',   // shipping_amount
            '%f',   // advance_payment
            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%d',   // user_id
            '%d',   // supplier_id
            '%s',   // note
            '%s'    // created_at
        );

        $wpdb->insert($table_purchases, $data, $format);

        $purchase_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
            do_action("combo_store_purchase_create_failed", $action_prams);
        } else {


        if(!empty($lineItems)){
                    $prams = [
                        "purchase_id" => $purchase_id,
                        "lineItems" => $lineItems,
                    ];

                    $this->insert_purchase_items($prams);
        }



            $response['success'] = true;
            $response['purchase_id'] = $purchase_id;

            do_action("combo_store_purchase_created", $action_prams);
        }




        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {

            $response['success'] = true;
            $response['id'] = $purchase_id;
        }






        return $response;
    }





    function get_purchase($purchase_id)
    {

        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_purchases = $prefix . 'cstore_purchases';


        $purchaseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_purchases WHERE id = %d",
                $purchase_id
            ),
            ARRAY_A
        );

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

       $advance_payment_note = $ComboStoreObjectMeta->get_meta('purchases', $purchase_id, "advance_payment_note");

       $purchaseRow['advance_payment_note'] = $advance_payment_note;

       
        $order_items = $this->get_purchase_items($purchase_id);

        foreach ($order_items as $order_item) {

            $product_id = isset($order_item->product_id) ? $order_item->product_id : 0;

            $product_title = get_the_title($product_id);

            $order_item->title = $product_title;
        }



        $response['lineItems'] = $order_items;
        $response['purchase'] = $purchaseRow;


        return $response;
    }


    function get_purchases($prams)
    {


        $responses = [];

        $isAdmin     = isset($prams['isAdmin']) ? ($prams['isAdmin']) : false;
        $user_id     = isset($prams['user_id']) ? ($prams['user_id']) : '';
        $object     = isset($prams['object']) ? sanitize_email($prams['object']) : '';
        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";
        $status     = isset($prams['status']) ? sanitize_text_field($prams['status']) : "";
        $payment_method     = isset($prams['payment_method']) ? sanitize_text_field($prams['payment_method']) : "";
        $payment_status     = isset($prams['payment_status']) ? sanitize_text_field($prams['payment_status']) : "";
        $product_id     = isset($prams['product_id']) ? sanitize_text_field($prams['product_id']) : "";
        $customer     = isset($prams['customer']) ? ($prams['customer']) : [];

        $customer_id = isset($customer['id']) ? $customer['id'] : "";

        /* Purchases have a supplier, not a customer: the list filters by supplier_id
           (sent as supplier_id, or as the old customer.id). */
        $supplier_id = isset($prams['supplier_id']) ? absint($prams['supplier_id']) : absint($customer_id);


        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';






        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_purchases = $prefix . 'cstore_purchases';
        $table_purchases_items = $prefix . 'cstore_purchases_items';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_purchases WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        if ($isAdmin) {
            $where   = [];
            $params  = [];

            if (!empty($status)) {
                $where[]  = "status = %s";
                $params[] = $status;
            }

            if (!empty($payment_method)) {
                $where[]  = "payment_method = %s";
                $params[] = $payment_method;
            }

            if (!empty($payment_status)) {
                $where[]  = "payment_status = %s";
                $params[] = $payment_status;
            }

            /* "userid = %s" named a column cstore_purchases does not have, so this
               filter failed with a SQL error and the list came back empty. */
            if (!empty($supplier_id)) {
                $where[]  = "supplier_id = %d";
                $params[] = $supplier_id;
            }

            if ($from_date && $to_date) {
                $where[]  = "created_at BETWEEN %s AND %s";
                $params[] = $from_date . " 00:00:00";
                $params[] = $to_date . " 23:59:59";
            }


            if (!empty($product_id)) {
                $where[] = "EXISTS (
                    SELECT 1 FROM {$table_purchases_items} oi
                    WHERE oi.purchase_id = {$table_purchases}.id
                    AND oi.product_id = %d
                )";

                $params[] = $product_id;
            }

            $where_sql = '';

            if (!empty($where)) {
                $where_sql = "WHERE " . implode(' AND ', $where);
            }

            $allowed_order = ['ASC', 'DESC'];
            $order = strtoupper($order);

            if (!in_array($order, $allowed_order)) {
                $order = 'DESC';
            }

            $sql = "SELECT * FROM $table_purchases
                    $where_sql
                    ORDER BY id $order
                    LIMIT %d, %d";

            $params[] = (int) $offset;
            $params[] = (int) $per_page;

            $query = $wpdb->prepare($sql, $params);

            $entries = $wpdb->get_results($query);

            /* Every matching purchase, not this page's rows (that made max_pages 1). */
            $count_params = array_slice($params, 0, -2);
            $count_sql = "SELECT COUNT(*) FROM $table_purchases $where_sql";
            $total = (int) $wpdb->get_var(!empty($count_params) ? $wpdb->prepare($count_sql, $count_params) : $count_sql);
                                                                                            

        } else {
            /* The creator is user_id; "userid" does not exist, so non-admins (store
               managers) got a SQL error and an empty list. */
            $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
            $entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_purchases WHERE user_id = %d ORDER BY id $order LIMIT %d, %d", $user_id, $offset, $per_page));
            $total = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_purchases WHERE user_id = %d", $user_id));
        }




        $big = 999999999; // need an unlikely integer



        foreach ($entries as $key => $entry) {
            $entries[$key]->purchase_items = $this->get_purchase_items($entry->id);
        }




        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = $per_page > 0 ? ceil($total / $per_page) : 1;

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }






    function update_purchase($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_purchases';


        $purchase_id         = isset($params['id']) ? intval($params['id']) : '';
        $status         = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';
        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $subtotal_amount   = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0;
        $discount_amount   = isset($params['discount_amount']) ? floatval($params['discount_amount']) : 0;
        $tax_amount   = isset($params['tax_amount']) ? floatval($params['tax_amount']) : 0;
        $shipping_amount   = isset($params['shipping_amount']) ? floatval($params['shipping_amount']) : 0;
        $advance_payment   = isset($params['advance_payment']) ? floatval($params['advance_payment']) : 0;
        /* Text: floatval() turned a note such as "paid by bKash" into 0. */
        $advance_payment_note   = isset($params['advance_payment_note']) ? sanitize_text_field($params['advance_payment_note']) : '';
            
        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
        $billing_name = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : '';
        $billing_email = isset($params['billing_email']) ? sanitize_email($params['billing_email']) : '';
        $billing_phone = isset($params['billing_phone']) ? sanitize_text_field($params['billing_phone']) : '';
        $billing_address = isset($params['billing_address']) ? sanitize_text_field($params['billing_address']) : '';
        $user_id     = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $supplier_id    = isset($params['supplier_id']) ? intval($params['supplier_id']) : 0;

        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        $lineItems = isset($params['lineItems']) ? stripslashes_deep($params['lineItems']) : [];
        $created_at = isset($params['created_at']) ? sanitize_text_field($params['created_at']) :get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s');


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_purchases = $prefix . 'cstore_purchases';

        $response = [];






        $data = array(
            'status'        => $status,
            'category'  => $category,
            'total_amount'    => $total_amount,
            'subtotal_amount'    => $subtotal_amount,
            'discount_amount'  => $discount_amount,
            'tax_amount'  => $tax_amount,
            'shipping_amount'  => $shipping_amount,
            'advance_payment'  => $advance_payment,
            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,
            'billing_name'  => $billing_name,
            'billing_email'  => $billing_email,
            'billing_phone'  => $billing_phone,
            'billing_address'  => $billing_address,
            'user_id'      => $user_id,
            'supplier_id'     => $supplier_id,
            'note'            => $note,
            'created_at'      => $created_at,
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        $format = array(
            '%s',   // status
            '%s',   // category
            '%f',   // total_amount
            '%f',   // subtotal_amount
            '%s',   // discount_amount
            '%f',   // tax_amount
            '%f',   // shipping_amount
            '%f',   // advance_payment
            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%d',   // user_id
            '%d',   // supplier_id
            '%s',   // note
            '%s',    // created_at
            '%s'    // updated_at
        );

        // ✅ WHERE clause
        $where = array('id' => $purchase_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        $prams = [
                "purchase_id" => $purchase_id,
                "lineItems" => $lineItems,
            ];

            $this->insert_purchase_items($prams);

        /* Stock follows the purchase's status (see the helpers below). */
        if ($purchase_id && $status === 'completed') {
            $this->add_stock_for_completed_purchase($purchase_id);
        } elseif ($purchase_id && in_array($status, array('cancelled', 'canceled', 'refunded'), true)) {
            $this->remove_stock_for_cancelled_purchase($purchase_id);
        }




        if ($updated) {
            $response = true;

            $ComboStoreObjectMeta = new ComboStoreObjectMeta();
            $ComboStoreObjectMeta->update_meta('purchases', $purchase_id, "advance_payment_note", $advance_payment_note);






        } else {
            $response = false;
        }
        return $response;
    }



    /**
     * The purchase's current stock cycle. Cancelling or refunding a completed
     * purchase ends a cycle, so completing it again adds its items again.
     *
     * @param int $purchase_id
     * @return int
     */
    function stock_cycle($purchase_id)
    {
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        return max(1, (int) $ComboStoreObjectMeta->get_meta('purchases', absint($purchase_id), 'stock_cycle'));
    }

    /**
     * Whether this purchase already has a stock entry of the given kind for a
     * product in the current cycle. By purchase and product, not by line:
     * saving a purchase deletes and re-inserts its lines with new ids.
     */
    private function has_stock_event($purchase_id, $product_id, $kind, $cycle)
    {
        global $wpdb;
        $suffix = $cycle > 1 ? "-{$kind}-{$cycle}" : "-{$kind}";

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->prefix}cstore_stock_history
             WHERE reference_type = 'purchase' AND reference_id = %d AND product_id = %d
               AND event_id LIKE %s LIMIT 1",
            $purchase_id,
            $product_id,
            $wpdb->esc_like("purchase-{$purchase_id}-item-") . '%' . $wpdb->esc_like($suffix)
        ));
    }

    /**
     * Add a completed purchase's items to stock, once.
     *
     * Purchases never touched stock: receiving goods meant adding them by hand
     * on the Stocks screen. Each line goes through ComboStoreStocks::update_stock,
     * so it is in stock history as a "purchase" at the line's price.
     *
     *  - Only products that track stock (in stock or out of stock); untracked
     *    ones are skipped, since starting a count from just this delivery would
     *    put a wrong number on the shelf.
     *  - Variable products are skipped: purchase lines do not say which
     *    variation, and the stock is on the variations.
     *
     * @param int $purchase_id
     * @return array { added: int, units: int, skipped: string[] }
     */
    function add_stock_for_completed_purchase($purchase_id)
    {
        global $wpdb;
        $prefix      = $wpdb->prefix;
        $purchase_id = absint($purchase_id);
        $summary     = array('added' => 0, 'units' => 0, 'skipped' => array());

        if (!$purchase_id) {
            return $summary;
        }

        $cycle = $this->stock_cycle($purchase_id);
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT id, product_id, product_name, quantity, price FROM {$prefix}cstore_purchases_items WHERE purchase_id = %d ORDER BY id",
            $purchase_id
        ), ARRAY_A);

        $ComboStoreStocks = new ComboStoreStocks();
        $failed = false;
        $seen = array();

        foreach ((array) $items as $item) {
            $post_id  = absint($item['product_id']);
            $quantity = max(0, (int) round((float) $item['quantity']));

            if (!$post_id || !$quantity) {
                continue;
            }

            /* One product on two lines: both lines count, in the same pass. */
            if (!isset($seen[$post_id]) && $this->has_stock_event($purchase_id, $post_id, 'received', $cycle)) {
                continue; // already added in this cycle
            }
            $seen[$post_id] = true;

            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT id, stock_status FROM {$prefix}cstore_products WHERE product_id = %d LIMIT 1",
                $post_id
            ), ARRAY_A);

            if (!$product) {
                continue; // a custom line, or a product that no longer exists
            }

            /* Only real products: lines saved before the product-id fix point at row ids. */
            if (get_post_type($post_id) !== 'cmb_product') {
                $summary['skipped'][] = "{$item['product_name']}: the line is not linked to a product.";
                continue;
            }

            if ($wpdb->get_var($wpdb->prepare("SELECT 1 FROM {$prefix}cstore_products_variations WHERE product_id = %d LIMIT 1", $post_id))) {
                $summary['skipped'][] = "{$item['product_name']}: a variable product - add it to the right variation on the Stocks screen.";
                continue;
            }

            if (!in_array($product['stock_status'], array('instock', 'outofstock'), true)) {
                $summary['skipped'][] = "{$item['product_name']}: stock is not tracked for it.";
                continue;
            }

            $suffix = $cycle > 1 ? "-received-{$cycle}" : '-received';

            $result = $ComboStoreStocks->update_stock(array(
                'product_id'     => $post_id,   // update_stock finds products by post id
                'variation_id'   => 0,
                'action'         => 'increase',
                'quantity'       => $quantity,
                'unit_cost'      => max(0, (float) $item['price']),
                'event_type'     => 'purchase',
                'reference_type' => 'purchase',
                'reference_id'   => $purchase_id,
                'event_id'       => "purchase-{$purchase_id}-item-{$item['id']}{$suffix}",
                'note'           => "Purchase #{$purchase_id} completed",
            ));

            if (isset($result['status']) && $result['status'] === 'success') {
                $summary['added']++;
                $summary['units'] += $quantity;
            } elseif (isset($result['message']) && strpos($result['message'], 'already been recorded') !== false) {
                /* Added before; nothing to do. */
            } else {
                $failed = true;
                $summary['skipped'][] = "{$item['product_name']}: " . (isset($result['message']) ? $result['message'] : 'not added.');
            }
        }

        return $summary;
    }

    /**
     * Take back out what completing the purchase added, when it is cancelled
     * or refunded - never below zero (what was sold since stays sold, and is
     * reported) - and end the cycle so completing it again adds again.
     *
     * @param int $purchase_id
     * @return array { removed: int, units: int, skipped: string[] }
     */
    function remove_stock_for_cancelled_purchase($purchase_id)
    {
        global $wpdb;
        $prefix      = $wpdb->prefix;
        $purchase_id = absint($purchase_id);
        $summary     = array('removed' => 0, 'units' => 0, 'skipped' => array());

        if (!$purchase_id) {
            return $summary;
        }

        $cycle    = $this->stock_cycle($purchase_id);
        $received = $cycle > 1 ? "-received-{$cycle}" : '-received';
        $returned = $cycle > 1 ? "-returned-{$cycle}" : '-returned';

        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT event_id, product_id, quantity FROM {$prefix}cstore_stock_history
             WHERE reference_type = 'purchase' AND reference_id = %d AND event_type = 'purchase'
               AND quantity > 0 AND event_id LIKE %s",
            $purchase_id,
            $wpdb->esc_like("purchase-{$purchase_id}-item-") . '%' . $wpdb->esc_like($received)
        ), ARRAY_A);

        /* Nothing was added in this cycle: nothing to take back, and the cycle stays open. */
        if (empty($entries)) {
            return $summary;
        }

        $ComboStoreStocks = new ComboStoreStocks();
        $failed = false;

        foreach ($entries as $entry) {
            if (!preg_match('/^purchase-' . $purchase_id . '-item-(\d+)' . preg_quote($received, '/') . '$/', $entry['event_id'], $m)) {
                continue;
            }

            $post_id  = (int) $entry['product_id'];
            $quantity = (int) round((float) $entry['quantity']);
            $have     = (int) $wpdb->get_var($wpdb->prepare("SELECT stock_count FROM {$prefix}cstore_products WHERE product_id = %d LIMIT 1", $post_id));
            $take     = min($quantity, max(0, $have));

            if ($take < $quantity) {
                $summary['skipped'][] = "Product {$post_id}: " . ($quantity - $take) . " of {$quantity} already sold, so not taken back.";
            }

            $result = $ComboStoreStocks->update_stock(array(
                'product_id'     => $post_id,
                'variation_id'   => 0,
                'action'         => 'decrease',
                'quantity'       => $take,
                'event_type'     => 'purchase_return',
                'reference_type' => 'purchase',
                'reference_id'   => $purchase_id,
                'event_id'       => "purchase-{$purchase_id}-item-{$m[1]}{$returned}",
                'note'           => "Purchase #{$purchase_id} cancelled or refunded",
            ));

            if (isset($result['status']) && $result['status'] === 'success') {
                $summary['removed']++;
                $summary['units'] += $take;
            } elseif (isset($result['message']) && strpos($result['message'], 'already been recorded') !== false) {
                /* Taken back before; nothing to do. */
            } else {
                $failed = true;
                $summary['skipped'][] = "Product {$post_id}: " . (isset($result['message']) ? $result['message'] : 'not taken back.');
            }
        }

        if (!$failed) {
            $ComboStoreObjectMeta = new ComboStoreObjectMeta();
            $ComboStoreObjectMeta->update_meta('purchases', $purchase_id, 'stock_cycle', $cycle + 1);
        }

        return $summary;
    }

    function insert_purchase_items($prams)
    {


        $response = [];

        $lineItems = isset($prams['lineItems']) ? $prams['lineItems'] : '';
        $purchase_id = isset($prams['purchase_id']) ? $prams['purchase_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_purchases_items";


        $this->delete_purchase_items($purchase_id);



        foreach ($lineItems as $cartItem) {
            /*
                The product is product_id - what every caller sends. This read
                $cartItem['id'], which for a line loaded from the database is the
                item row's own id and for a new line is missing, so every save
                linked the lines to the wrong product, or to none.
            */
            $product_id    = isset($cartItem['product_id']) ? intval($cartItem['product_id']) : 0;
            $product_name  = isset($cartItem['product_name']) ? sanitize_text_field($cartItem['product_name']) : '';
            $sku           = isset($cartItem['sku']) ? sanitize_text_field($cartItem['sku']) : '';
            $quantity      = isset($cartItem['quantity']) ? intval($cartItem['quantity']) : 1;
            $price         = isset($cartItem['price']) ? floatval($cartItem['price']) : 0.00;
            $subtotal      = isset($cartItem['subtotal']) ? floatval($cartItem['subtotal']) : 0.00;
            $tax           = isset($cartItem['tax']) ? floatval($cartItem['tax']) : 0.00;
            $total         = isset($cartItem['total']) ? floatval($cartItem['total']) : 0.00;
            $meta          = isset($cartItem['meta']) ? wp_json_encode($cartItem['meta']) : null;

            // Insert data
            $data = array(
                'purchase_id'     => $purchase_id,
                'product_id'   => $product_id,
                'product_name' => $product_name,
                'sku'          => $sku,
                'quantity'     => $quantity,
                'price'        => $price,
                'subtotal'     => $subtotal,
                'tax'          => $tax,
                'total'        => $total,
                'meta'         => $meta
            );

            $format = array(
                '%d', // order_id
                '%d', // product_id
                '%s', // product_name
                '%s', // sku
                '%d', // quantity
                '%f', // price
                '%f', // subtotal
                '%f', // tax
                '%f', // total
                '%s'  // meta (JSON string)
            );

            $wpdb->insert($table, $data, $format);

            // Get inserted item_id
            $item_id = $wpdb->insert_id;
        }



        if ($wpdb->last_error) {
            $response['status'] = 'failed';
        } else {
            $response['status'] = 'success';
        }

            return $response;

        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    function update_purchase_items($prams)
    {

        $lineItems = isset($prams['lineItems']) ? $prams['lineItems'] : '';
        $purchase_id = isset($prams['purchase_id']) ? $prams['purchase_id'] : '';



        global $wpdb;
        $table = $wpdb->prefix . "cstore_purchases_items";



        $this->delete_purchase_items($purchase_id);


        foreach ($lineItems as $cartItem) {
            $product_id    = isset($cartItem['product_id']) ? intval($cartItem['product_id']) : 0;
            $product_name  = isset($cartItem['product_name']) ? sanitize_text_field($cartItem['product_name']) : '';
            $sku           = isset($cartItem['sku']) ? sanitize_text_field($cartItem['sku']) : '';
            $quantity      = isset($cartItem['quantity']) ? intval($cartItem['quantity']) : 1;
            $price         = isset($cartItem['price']) ? floatval($cartItem['price']) : 0.00;
            $subtotal      = isset($cartItem['subtotal']) ? floatval($cartItem['subtotal']) : 0.00;
            $tax           = isset($cartItem['tax']) ? floatval($cartItem['tax']) : 0.00;
            $total         = isset($cartItem['total']) ? floatval($cartItem['total']) : 0.00;
            $meta          = isset($cartItem['meta']) ? wp_json_encode($cartItem['meta']) : null;

            // Insert data
            $data = array(
                'purchase_id'     => $purchase_id,
                'product_id'   => $product_id,
                'product_name' => $product_name,
                'sku'          => $sku,
                'quantity'     => $quantity,
                'price'        => $price,
                'subtotal'     => $subtotal,
                'tax'          => $tax,
                'total'        => $total,
                'meta'         => $meta
            );

            $format = array(
                '%d', // purchase_id
                '%d', // product_id
                '%s', // product_name
                '%s', // sku
                '%d', // quantity
                '%f', // price
                '%f', // subtotal
                '%f', // tax
                '%f', // total
                '%s'  // meta (JSON string)
            );

            $wpdb->insert($table, $data, $format);

            // Get inserted item_id
            $item_id = $wpdb->insert_id;
        }




        if ($wpdb->last_error) {
            $response['status'] = 'failed';
        } else {
            $response['status'] = 'success';
        }

            return $response;


        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }





 function delete_purchase_items($purchase_id)
    {

    
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_purchases_items';


        $response = [];

        if (!$purchase_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('purchase_id' => $purchase_id), array('%d'));



        $response['status'] = $status;

        return $response;
    }








    function get_purchase_items($purchase_id)
    {


        global $wpdb;
        $table_order_items = $wpdb->prefix . "cstore_purchases_items";


        $query = $wpdb->prepare(
            "SELECT * FROM $table_order_items WHERE purchase_id = %d",
            $purchase_id
        );

        $purchase_items = $wpdb->get_results($query);


        foreach ($purchase_items as $key => $entry) {
     
            $product_name = isset($entry->product_name) ? $entry->product_name : '';
            $product_id = isset($entry->product_id) ? $entry->product_id : '';
            
            if(empty($product_name)){
                $product_name = get_the_title($product_id);
            }

            $purchase_items[$key]->product_name = $product_name;

        }


        return $purchase_items;
    }
    function search_purchase_items($keyword )
    {

        $response = [];

        global $wpdb;
        $table_order_items = $wpdb->prefix . "cstore_purchases_items";

        $limit = 10;

                $query = $wpdb->prepare(
            "SELECT * 
            FROM $table_order_items 
            WHERE product_name LIKE %s
            LIMIT %d",
            '%' . $wpdb->esc_like($keyword) . '%',
            $limit
        );

        $purchase_items = $wpdb->get_results($query);


        foreach ($purchase_items as $key => $entry) {
     
            $product_name = isset($entry->product_name) ? $entry->product_name : '';
            $product_id = isset($entry->product_id) ? $entry->product_id : '';
            
            if(empty($product_name)){
                $product_name = get_the_title($product_id);
            $purchase_items[$key]->product_name = $product_name;

            }


        }

        $response['posts'] = $purchase_items;

        return $response;
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
