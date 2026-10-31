<?php

namespace ComboStore\Classes;

use Exception;
use Throwable;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreStocks
{
    public $email = "";
    public $user_id = "";


    public function __construct() {}



    /**
     * Add a stock history record.
     *
     * @param array $params Stock movement data.
     *
     * Required:
     * - product_id
     * - quantity
     * - stock_before
     * - stock_after
     *
     * Optional:
     * - variation_id
     * - warehouse_id
     * - event_type
     * - unit_cost
     * - reference_type
     * - reference_id
     * - event_id
     * - note
     * - created_by
     *
     * @return array Response containing status and record ID or error.
     */
    function add_stock_histoy($params)
    {
        global $wpdb;

        $response = array(
            'status' => 'failed',
        );

        if (!is_array($params)) {
            $response['message'] = 'Invalid parameters.';
            return $response;
        }

        $product_id = isset($params['product_id'])            ? absint($params['product_id'])            : 0;

        $variation_id = !empty($params['variation_id'])            ? absint($params['variation_id'])            : null;

        $warehouse_id = !empty($params['warehouse_id'])            ? absint($params['warehouse_id'])            : null;

        $quantity = isset($params['quantity'])            ? (float) $params['quantity']            : null;

        $stock_before = isset($params['stock_before'])            ? (float) $params['stock_before']            : null;

        $stock_after = isset($params['stock_after'])            ? (float) $params['stock_after']            : null;

        $event_type = isset($params['event_type'])            ? sanitize_text_field($params['event_type'])            : 'adjustment';

        $unit_cost = isset($params['unit_cost'])            ? (float) $params['unit_cost']            : 0;

        $reference_type = isset($params['reference_type'])            ? sanitize_text_field($params['reference_type'])            : null;

        $reference_id = !empty($params['reference_id'])            ? absint($params['reference_id'])            : null;

        $event_id = isset($params['event_id'])            ? sanitize_text_field($params['event_id'])            : null;

        $note = isset($params['note'])            ? sanitize_textarea_field($params['note'])            : null;

        $created_by = isset($params['created_by'])            ? absint($params['created_by'])            : get_current_user_id();

        // Validate required values.
        if (
            $product_id <= 0 ||
            $quantity === null ||
            $stock_before === null ||
            $stock_after === null
        ) {
            $response['message'] = 'Missing required stock history fields.';
            return $response;
        }

        if (
            !is_finite($quantity) ||
            !is_finite($stock_before) ||
            !is_finite($stock_after) ||
            !is_finite($unit_cost) ||
            $quantity < 0 ||
            $unit_cost < 0 ||
            $stock_before < 0 ||
            $stock_after < 0
        ) {
            $response['message'] = 'Invalid stock quantity or unit cost.';
            return $response;
        }

        if ($event_type === '') {
            $response['message'] = 'Event type is required.';
            return $response;
        }

        // The stock change must match the recorded movement.
        // quantity is stored as a positive magnitude.
        $expected_after = $stock_before;

        if (in_array($event_type, array(
            'purchase',
            'return',
            'adjustment_in',
            'transfer_in',
        ), true)) {
            $expected_after += $quantity;
        } elseif (in_array($event_type, array(
            'sale',
            'adjustment_out',
            'transfer_out',
        ), true)) {
            $expected_after -= $quantity;
        } elseif ($event_type === 'adjustment') {
            // For a general adjustment, quantity is the absolute
            // movement; stock_after determines the direction.
            $expected_after = $stock_after;
        } elseif ($event_type === 'set_stock') {
            // Setting stock directly can increase or decrease it.
            $expected_after = $stock_after;
        } else {
            // For custom events, accept the supplied stock values.
            $expected_after = $stock_after;
        }

        if (
            $event_type !== 'adjustment' &&
            $event_type !== 'set_stock' &&
            abs($expected_after - $stock_after) > 0.001
        ) {
            $response['message'] = 'Stock movement does not match stock_before and stock_after.';
            return $response;
        }

        $table = $wpdb->prefix . 'cstore_stock_history';

        // Prevent duplicate processing when an event ID is provided.
        if ($event_id !== null && $event_id !== '') {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE event_id = %s LIMIT 1",
                    $event_id
                )
            );

            if ($existing_id) {
                $response['status'] = 'success';
                $response['message'] = 'Stock history event already exists.';
                $response['id'] = (int) $existing_id;
                $response['duplicate'] = true;

                return $response;
            }
        }

        $data = array(
            'product_id'    => $product_id,
            'variation_id'  => $variation_id,
            'warehouse_id'  => $warehouse_id,
            'event_type'    => $event_type,
            'unit_cost'     => $unit_cost,
            'quantity'      => $quantity,
            'stock_before'  => $stock_before,
            'stock_after'   => $stock_after,
            'reference_type' => $reference_type,
            'reference_id'  => $reference_id,
            'event_id'      => $event_id,
            'note'          => $note,
            'created_by'    => $created_by ?: null,
            'created_at'    => current_time('mysql'),
        );

        $format = array(
            '%d', // product_id
            '%d', // variation_id
            '%d', // warehouse_id
            '%s', // event_type
            '%f', // unit_cost
            '%f', // quantity
            '%f', // stock_before
            '%f', // stock_after
            '%s', // reference_type
            '%d', // reference_id
            '%s', // event_id
            '%s', // note
            '%d', // created_by
            '%s', // created_at
        );

        $inserted = $wpdb->insert($table, $data, $format);

        if ($inserted === false) {
            $response['message'] = 'Failed to insert stock history.';
            $response['error'] = $wpdb->last_error;

            return $response;
        }

        $response['status'] = 'success';
        $response['message'] = 'Stock history added successfully.';
        $response['id'] = (int) $wpdb->insert_id;
        $response['duplicate'] = false;

        return $response;
    }



    /**
     * Update product or variation stock and record stock history.
     *
     * @param array $params {
     *     @type int    $product_id    Product row ID in cstore_products.
     *     @type int    $variation_id  Variation row ID. 0 for simple products.
     *     @type string $action        increase, decrease, or set.
     *     @type float  $quantity      Quantity to increase/decrease/set.
     *     @type float  $unit_cost     Cost per unit for stock history.
     *     @type int    $warehouse_id  Optional warehouse ID.
     *     @type string $event_type    Optional event type override.
     *     @type string $reference_type Optional reference type, e.g. order.
     *     @type int    $reference_id  Optional reference ID.
     *     @type string $event_id      Optional unique event ID.
     *     @type string $note          Optional note.
     *     @type int    $created_by    Optional user ID.
     * }
     *
     * @return array
     */
    function update_stock($params)
    {
        global $wpdb;

        $response = array(
            'status' => 'failed',
        );

        if (!is_array($params)) {
            $response['message'] = 'Invalid parameters.';
            return $response;
        }

        $product_id = isset($params['product_id'])        ? absint($params['product_id'])        : 0;
        $variation_id = isset($params['variation_id'])        ? absint($params['variation_id'])        : 0;
        $action = isset($params['action'])        ? sanitize_key($params['action'])        : '';
        $quantity = isset($params['quantity'])        ? (float) $params['quantity']        : -1;
        $unit_cost = isset($params['unit_cost'])        ? (float) $params['unit_cost']        : 0;
        $warehouse_id = !empty($params['warehouse_id'])        ? absint($params['warehouse_id'])        : null;
        $reference_type = !empty($params['reference_type'])        ? sanitize_text_field($params['reference_type'])        : null;
        $reference_id = !empty($params['reference_id'])        ? absint($params['reference_id'])        : null;
        $event_id = !empty($params['event_id'])        ? sanitize_text_field($params['event_id'])        : null;
        $note = isset($params['note'])        ? sanitize_textarea_field($params['note'])        : null;
        $created_by = isset($params['created_by'])        ? absint($params['created_by'])        : get_current_user_id();

        if (
            $product_id <= 0 ||
            !in_array($action, array('increase', 'decrease', 'set'), true) ||
            !is_finite($quantity) ||
            !is_finite($unit_cost) ||
            $quantity < 0 ||
            $unit_cost < 0
        ) {
            $response['message'] = 'Invalid product, action, quantity, or unit cost.';
            return $response;
        }

        // Stock counts are BIGINT in the supplied schema.
        if (floor($quantity) != $quantity) {
            $response['message'] = 'Stock quantity must be a whole number.';
            return $response;
        }

        $table_products = $wpdb->prefix . 'cstore_products';
        $table_variations = $wpdb->prefix . 'cstore_products_variations';
        $table_history = $wpdb->prefix . 'cstore_stock_history';

        $is_variation = $variation_id > 0;

        $table = $is_variation            ? $table_variations            : $table_products;
        $row_id = $is_variation            ? $variation_id            : $product_id;

        $wpdb->query('START TRANSACTION');

        try {
            /*
         * Lock the stock row to prevent concurrent updates
         * from overwriting each other.
         */
            // $item = $wpdb->get_row(
            //     $wpdb->prepare(
            //         "SELECT id, product_id, stock_count, stock_status
            //      FROM {$table}
            //      WHERE id = %d
            //      FOR UPDATE",
            //         $row_id
            //     ),
            //     ARRAY_A
            // );

            if ($is_variation) {




                $item = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id, product_id, stock_count, stock_status
             FROM {$table_variations}
             WHERE id = %d
             FOR UPDATE",
                        $variation_id
                    ),
                    ARRAY_A
                );
            } else {
                $item = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id, product_id, stock_count, stock_status
             FROM {$table_products}
             WHERE product_id = %d
             FOR UPDATE",
                        $product_id
                    ),
                    ARRAY_A
                );
            }





            if (!$item) {
                throw new Exception('Product or variation not found.');
            }

            /*
         * When updating a variation, verify that it belongs
         * to the supplied product if the IDs use the same
         * product identifier convention in both tables.
         *
         * If your product_id fields use different identifiers,
         * adapt this check to your actual relationship.
         */
            if (
                $is_variation &&
                !empty($item['product_id']) &&
                (int) $item['product_id'] !== $product_id
            ) {
                throw new Exception('Variation does not belong to this product.');
            }

            // Treat NULL stock_count as zero.
            $stock_before = $item['stock_count'] !== null  ? (int) $item['stock_count'] : 0;

            /*
         * Calculate the resulting stock.
         */
            if ($action === 'increase') {
                $stock_after = $stock_before + (int) $quantity;
                $event_type = 'adjustment_in';
            } elseif ($action === 'decrease') {
                $stock_after = $stock_before - (int) $quantity;
                $event_type = 'adjustment_out';
            } else {
                $stock_after = (int) $quantity;
                $event_type = 'adjustment';
            }

            if ($stock_after < 0) {
                throw new Exception('Insufficient stock.');
            }

            if ($stock_after > PHP_INT_MAX) {
                throw new Exception('Stock quantity exceeds the supported range.');
            }

            // Allow the caller to specify a business event.
            if (!empty($params['event_type'])) {
                $event_type = sanitize_key($params['event_type']);
            }

            /*
         * Derive the actual movement quantity.
         * History quantity remains a positive magnitude.
         */
            $movement_quantity = abs($stock_after - $stock_before);

            /*
         * Idempotency check.
         */
            if ($event_id !== null) {
                $existing_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM {$table_history}
                     WHERE event_id = %s LIMIT 1",
                        $event_id
                    )
                );

                if ($existing_id) {
                    throw new Exception(
                        'This stock event has already been recorded.'
                    );
                }
            }

            /*
         * Determine stock status.
         */
            $stock_status = $stock_after > 0                ? 'instock'                : 'outofstock';

            /*
         * Update product or variation stock.
         */
            $updated = $wpdb->update(
                $table,
                array(
                    'stock_count'  => $stock_after,
                    'stock_status' => $stock_status,
                ),
                array(
                    'id' => $row_id,
                ),
                array('%d', '%s'),
                array('%d')
            );

            // Sync parent product stock after a variation update.
            if ($is_variation) {
                $sync_result = $this->sync_variable_product_stock($product_id);

                if ($sync_result['status'] !== 'success') {
                    throw new Exception($sync_result['message']);
                }
            }


            if ($updated === false) {
                throw new Exception(
                    'Failed to update stock: ' . $wpdb->last_error
                );
            }

            /*
         * Record stock history.
         */
            $history_data = array(
                'product_id'     => $product_id,
                'variation_id'   => $is_variation ? $variation_id : null,
                'warehouse_id'   => $warehouse_id,
                'event_type'     => $event_type,
                'unit_cost'      => $unit_cost,
                'quantity'       => $movement_quantity,
                'stock_before'   => $stock_before,
                'stock_after'    => $stock_after,
                'reference_type' => $reference_type,
                'reference_id'   => $reference_id,
                'event_id'       => $event_id,
                'note'           => $note,
                'created_by'     => $created_by ?: null,
                'created_at'     => current_time('mysql'),
            );

            $history_formats = array(
                '%d',
                '%d',
                '%d',
                '%s',
                '%f',
                '%f',
                '%f',
                '%f',
                '%s',
                '%d',
                '%s',
                '%s',
                '%d',
                '%s',
            );

            $inserted = $wpdb->insert(
                $table_history,
                $history_data,
                $history_formats
            );

            if ($inserted === false) {
                throw new Exception(
                    'Failed to insert stock history: ' . $wpdb->last_error
                );
            }

            $history_id = (int) $wpdb->insert_id;

            $committed = $wpdb->query('COMMIT');

            if ($committed === false) {
                throw new Exception('Failed to commit stock transaction.');
            }

            return array(
                'status'        => 'success',
                'message'       => 'Stock updated successfully.',
                'product_id'    => $product_id,
                'variation_id'  => $is_variation ? $variation_id : 0,
                'stock_before'  => $stock_before,
                'stock_after'   => $stock_after,
                'quantity'      => $movement_quantity,
                'stock_status'  => $stock_status,
                'history_id'    => $history_id,
            );
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');

            $response['message'] = $e->getMessage();

            return $response;
        }
    }


    /**
     * Synchronize variable product stock from its variations.
     *
     * @param int $product_id Parent product row ID.
     * @return array
     */
    function sync_variable_product_stock($product_id)
    {
        global $wpdb;

        $product_id = absint($product_id);

        if (!$product_id) {
            return array(
                'status'  => 'failed',
                'message' => 'Invalid product ID.',
            );
        }

        $table_products = $wpdb->prefix . 'cstore_products';
        $table_variations = $wpdb->prefix . 'cstore_products_variations';

        // Sum stock from enabled variations only.
        $stock = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(COALESCE(stock_count, 0)), 0)
             FROM {$table_variations}
             WHERE product_id = %d
             AND enable = %s",
                $product_id,
                'yes'
            )
        );

        if ($stock === null && $wpdb->last_error) {
            return array(
                'status'  => 'failed',
                'message' => $wpdb->last_error,
            );
        }

        $stock = (int) $stock;

        $stock_status = $stock > 0  ? 'instock' : 'outofstock';

        error_log("stock: $stock");
        error_log("product_id: $product_id");


        $updated = $wpdb->update(
            $table_products,
            array(
                'stock_count'  => $stock,
                'stock_status' => $stock_status,
            ),
            array(
                'product_id' => $product_id,
            ),
            array('%d', '%s'),
            array('%d')
        );

        error_log(wp_json_encode($updated));


        if ($updated === false) {
            return array(
                'status'  => 'failed',
                'message' => $wpdb->last_error,
            );
        }

        return array(
            'status'       => 'success',
            'product_id'   => $product_id,
            'stock_count'  => $stock,
            'stock_status' => $stock_status,
        );
    }



    function get_stocks_histories($prams)
    {


        $responses = [];

        $isAdmin     = isset($prams['isAdmin']) ? ($prams['isAdmin']) : false;
        $product_id     = isset($prams['product_id']) ? sanitize_text_field($prams['product_id']) : '';
        $variation_id     = isset($prams['variation_id']) ? sanitize_text_field($prams['variation_id']) : "";
        $warehouse_id     = isset($prams['warehouse_id']) ? sanitize_text_field($prams['warehouse_id']) : "";

        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";
        $customer     = isset($prams['customer']) ? ($prams['customer']) : [];

        $customer_id = isset($customer['id']) ? $customer['id'] : "";


        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';






        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_expenses = $prefix . 'cstore_stock_history';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_expenses WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        if ($isAdmin) {
            $where   = [];
            $params  = [];

            if (!empty($status)) {
                $where[]  = "status = %s";
                $params[] = $status;
            }


            if (!empty($product_id)) {
                $where[]  = "product_id = %d";
                $params[] = $product_id;
            }
            if (!empty($variation_id)) {
                $where[]  = "variation_id = %d";
                $params[] = $variation_id;
            }
            if (!empty($warehouse_id)) {
                $where[]  = "warehouse_id = %d";
                $params[] = $warehouse_id;
            }

            if ($from_date && $to_date) {
                $where[]  = "created_at BETWEEN %s AND %s";
                $params[] = $from_date . " 00:00:00";
                $params[] = $to_date . " 23:59:59";
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

            $sql = "SELECT * FROM $table_expenses
                    $where_sql
                    ORDER BY id $order
                    LIMIT %d, %d";

            $params[] = (int) $offset;
            $params[] = (int) $per_page;

            $query = $wpdb->prepare($sql, $params);

            $entries = $wpdb->get_results($query);



            $total = count($entries);
        }




        $big = 999999999; // need an unlikely integer



        foreach ($entries as $key => $entry) {
            $user = get_user_by('id', $entry->created_by);


            // $entry->created_by = [
            //     'name' => isset($user->display_name) ? $user->display_name : '',
            //     'id' => isset($user->ID) ? $user->ID : '',
            // ];
        }




        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
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


$ComboStoreStocks = new ComboStoreStocks();
