<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreExpenses
{
    public $object_name = "";


    public function __construct() {}

    function create_expense($params)
    {


        $user_id         = isset($params['user_id']) ? intval($params['user_id']) : 0;



        $title   = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';

        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';

        $created_by     = isset($params['created_by']) ? intval($params['created_by']) : 0;
        $created_for    = isset($params['created_for']) ? intval($params['created_for']) : $created_by;
        $billing_name = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : '';
        $billing_email = isset($params['billing_email']) ? sanitize_email($params['billing_email']) : '';
        $billing_phone = isset($params['billing_phone']) ? sanitize_text_field($params['billing_phone']) : '';
        $billing_address = isset($params['billing_address']) ? sanitize_text_field($params['billing_address']) : '';
        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';

        $created_at     = get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s');



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';

        $response = [];






        $data = array(
            'title'    => $title,
            'total_amount'    => $total_amount,
            'category'        => $category,

            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,

            'created_by'      => $created_by,
            'created_for'     => $created_for,
            'billing_name'  => $billing_name,
            'billing_email'  => $billing_email,
            'billing_phone'  => $billing_phone,
            'billing_address'  => $billing_address,
            'note'            => $note,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%s',   // title
            '%f',   // total_amount
            '%s',   // category

            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id

            '%d',   // craeted_by
            '%d',   // created_for
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%s',   // note

            '%s',    // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table_expenses, $data, $format);

        $order_id = $wpdb->insert_id; // Get inserted order_id


        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        //$ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);




        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {

            $response['success'] = true;
            $response['id'] = $order_id;
        }






        return $response;
    }


  function get_expense($expense_id)
    {

        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';


        $expenseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_expenses WHERE id = %d",
                $expense_id
            ),
            ARRAY_A
        );

        //     $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        //    $advance_payment_note = $ComboStoreObjectMeta->get_meta('expenses', $expense_id, "advance_payment_note");

        //    $expenseRow['advance_payment_note'] = $advance_payment_note;

       
      $order_items = $this->get_purchase_items($expense_id);

        foreach ($order_items as $order_item) {

            $product_id = isset($order_item->product_id) ? $order_item->product_id : 0;

            $product_title = get_the_title($product_id);

            $order_item->title = $product_title;
        }

        $response['expense'] = $expenseRow;
        $response['lineItems'] = $order_items;


        return $response;
    }


 

    function get_expenses($prams)
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


        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';






        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_expenses = $prefix . 'cstore_expenses';
        $table_expenses_items = $prefix . 'cstore_expenses_items';
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

            if (!empty($payment_method)) {
                $where[]  = "payment_method = %s";
                $params[] = $payment_method;
            }

            if (!empty($payment_status)) {
                $where[]  = "payment_status = %s";
                $params[] = $payment_status;
            }

            if (!empty($customer_id)) {
                $where[]  = "userid = %s";
                $params[] = $customer_id;
            }

            if ($from_date && $to_date) {
                $where[]  = "created_at BETWEEN %s AND %s";
                $params[] = $from_date . " 00:00:00";
                $params[] = $to_date . " 23:59:59";
            }


            if (!empty($product_id)) {
                $where[] = "EXISTS (
                    SELECT 1 FROM {$table_expenses_items} oi
                    WHERE oi.order_id = {$table_expenses}.id
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

            $sql = "SELECT * FROM $table_expenses
                    $where_sql
                    ORDER BY id $order
                    LIMIT %d, %d";

            $params[] = (int) $offset;
            $params[] = (int) $per_page;

            $query = $wpdb->prepare($sql, $params);

            $entries = $wpdb->get_results($query);

          

            $total = count($entries);
                                                                                            

        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table_expenses WHERE userid='$user_id' ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_expenses WHERE userid='$user_id'");
        }




        $big = 999999999; // need an unlikely integer



        foreach ($entries as $key => $entry) {
            $user = get_user_by('id', $entry->created_by);
            $entry->created_by = [
                'name'=>$user->display_name,
                'id'=>$user->ID
                ];

            $entries[$key]->lineItems = $this->get_purchase_items($entry->id);
        }




        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }






    function update_expense($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_expenses';



        $id   = isset($params['id']) ? floatval($params['id']) : '';
        $title   = isset($params['title']) ? sanitize_text_field($params['title']) : 0;
        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';

        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';

        $created_by     = isset($params['created_by']) ? intval($params['created_by']) : 0;
        $created_for    = isset($params['created_for']) ? intval($params['created_for']) : $created_by;
        $billing_name = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : '';
        $billing_email = isset($params['billing_email']) ? sanitize_email($params['billing_email']) : '';
        $billing_phone = isset($params['billing_phone']) ? sanitize_text_field($params['billing_phone']) : '';
        $billing_address = isset($params['billing_address']) ? sanitize_text_field($params['billing_address']) : '';
        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';
        $lineItems           = isset($params['lineItems']) ? ($params['lineItems']) : [];



        $created_at = isset($params['created_at']) ? sanitize_text_field($params['created_at']) :get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s');


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';

        $response = [];






        $data = array(
            'title'    => $title,
            'total_amount'    => $total_amount,
            'category'        => $category,

            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,

            'created_by'      => $created_by,
            'created_for'     => $created_for,
            'billing_name'  => $billing_name,
            'billing_email'  => $billing_email,
            'billing_phone'  => $billing_phone,
            'billing_address'  => $billing_address,
            'note'            => $note,
            'created_at'      => $created_at,
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%s',   // title
            '%f',   // total_amount
            '%s',   // category

            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id
            '%d',   // craeted_by
            '%d',   // created_for
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%s',   // note
            '%s',    // created_at
            '%s'    // updated_at
        );


        // ✅ WHERE clause
        $where = array('id' => $id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        $prams = [
                "expense_id" => $id,
                "lineItems" => $lineItems,
            ];

            $this->insert_purchase_items($prams);


        if ($updated) {
            $response = true;
        } else {
            $response = false;
        }
        return $response;
    }




    function insert_purchase_items($prams)
    {


        $response = [];

        $lineItems = isset($prams['lineItems']) ? $prams['lineItems'] : '';
        $expense_id = isset($prams['expense_id']) ? $prams['expense_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_expenses_items";


        $this->delete_purchase_items($expense_id);



        foreach ($lineItems as $cartItem) {
            $product_id    = isset($cartItem['id']) ? intval($cartItem['id']) : 0;
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
                'expense_id'     => $expense_id,
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
        $expense_id = isset($prams['expense_id']) ? $prams['expense_id'] : '';



        global $wpdb;
        $table = $wpdb->prefix . "cstore_expenses_items";



        $this->delete_purchase_items($expense_id);


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
                'expense_id'     => $expense_id,
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
                '%d', // expense_id
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





 function delete_purchase_items($expense_id)
    {

    
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_expenses_items';


        $response = [];

        if (!$expense_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('expense_id' => $expense_id), array('%d'));



        $response['status'] = $status;

        return $response;
    }








    function get_purchase_items($expense_id)
    {


        global $wpdb;
        $table_order_items = $wpdb->prefix . "cstore_expenses_items";


        $query = $wpdb->prepare(
            "SELECT * FROM $table_order_items WHERE expense_id = %d",
            $expense_id
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


    function search_expense_items($keyword )
    {

        $response = [];

        global $wpdb;
        $table_order_items = $wpdb->prefix . "cstore_expenses_items";

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
