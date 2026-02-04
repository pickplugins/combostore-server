<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreOrders
{
    public $object_name = "";


    public function __construct() {}

    function create_order($params)
    {

error_log(wp_json_encode($params));

        $user_id         = isset($params['user_id']) ? intval($params['user_id']) : 0;


        $order_note          = isset($params['order_note']) ? sanitize_text_field($params['order_note']) : '';
        $status          = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';
        $currency        = isset($params['currency']) ? sanitize_text_field($params['currency']) : 'BDT';
        $total_amount    = isset($params['total_amount']) ? floatval($params['total_amount']) : 0.00;
        $subtotal_amount = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0.00;
        $discount_amount = isset($params['discount_amount']) ? floatval($params['discount_amount']) : 0.00;
        $tax_amount      = isset($params['tax_amount']) ? floatval($params['tax_amount']) : 0.00;
        $shipping_amount = isset($params['shipping_amount']) ? floatval($params['shipping_amount']) : 0.00;

        $payment_method  = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status  = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : 'unpaid';
        $transaction_id  = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';
        $shipping_method = isset($params['shipping_method']) ? sanitize_text_field($params['shipping_method']) : '';
        $billing = isset($params['billing']) ? stripslashes_deep($params['billing']) : [];
        $shipping = isset($params['shipping']) ? stripslashes_deep($params['shipping']) : [];
        $subscription = isset($params['subscription']) ? stripslashes_deep($params['subscription']) : [];

        $billing_name    = isset($billing['name']) ? sanitize_text_field($billing['name']) : '';
        $billing_firstName    = isset($billing['firstName']) ? sanitize_text_field($billing['firstName']) : '';
        $billing_lastName    = isset($billing['lastName']) ? sanitize_text_field($billing['lastName']) : '';
        $billing_company    = isset($billing['company']) ? sanitize_text_field($billing['company']) : '';
        $billing_address1    = isset($billing['address1']) ? sanitize_text_field($billing['address1']) : '';
        $billing_address2    = isset($billing['address2']) ? sanitize_text_field($billing['address2']) : '';
        $billing_country    = isset($billing['country']) ? sanitize_text_field($billing['country']) : '';
        $billing_city    = isset($billing['city']) ? sanitize_text_field($billing['city']) : '';
        $billing_phone    = isset($billing['phone']) ? sanitize_text_field($billing['phone']) : '';
        $billing_email    = isset($billing['email']) ? sanitize_email($billing['email']) : '';

        $shipping_name    = isset($shipping['name']) ? sanitize_text_field($shipping['name']) : '';
        $shipping_firstName    = isset($shipping['firstName']) ? sanitize_text_field($shipping['firstName']) : '';
        $shipping_lastName    = isset($shipping['lastName']) ? sanitize_text_field($shipping['lastName']) : '';
        $shipping_company    = isset($shipping['company']) ? sanitize_text_field($shipping['company']) : '';
        $shipping_address1    = isset($shipping['address1']) ? sanitize_text_field($shipping['address1']) : '';
        $shipping_address2    = isset($shipping['address2']) ? sanitize_text_field($shipping['address2']) : '';
        $shipping_country    = isset($shipping['country']) ? sanitize_text_field($shipping['country']) : '';
        $shipping_city    = isset($shipping['city']) ? sanitize_text_field($shipping['city']) : '';
        $shipping_phone    = isset($shipping['phone']) ? sanitize_text_field($shipping['phone']) : '';
        $shipping_email    = isset($shipping['email']) ? sanitize_email($shipping['email']) : '';

        $subscription_enable    = isset($subscription['enable']) ? sanitize_text_field($subscription['enable']) : '';
        $subscription_interval    = isset($subscription['interval']) ? sanitize_text_field($subscription['interval']) : '';
        $subscription_interval_count    = isset($subscription['interval_count']) ? sanitize_text_field($subscription['interval_count']) : '';

        $billing_address = "$billing_address1 $billing_address2";

        $shipping_name   = $shipping_name ? "$shipping_name" : "";
        $shipping_address =  $shipping_address1 ? "$shipping_address1 $shipping_address2" : "";

        $cartItems = isset($params['cartItems']) ? stripslashes_deep($params['cartItems']) : [];
        $delivery_location = isset($params['delivery_location']) ? stripslashes_deep($params['delivery_location']) : [];





        if (empty($user_id)) {



            $guestUserData = $this->check_user_by('email', $billing_email);



            if (isset($guestUserData['id'])) {
                $user_id = isset($guestUserData['id']) ? $guestUserData['id'] : '';
            } else {

                $ComboStoreRegister = new ComboStoreRegister();
                $newUserResp = $ComboStoreRegister->create_user(['email' => $billing_email, 'password' => '', 'role' => 'customer', 'mobile' => $billing_phone]);


                $newUserResp = json_decode($newUserResp);
                $user_id = isset($newUserResp->user_id) ? $newUserResp->user_id : '';
            }
        }









        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_orders';

        $response = [];

        $data = array(
            'userid'         => $user_id,
            'status'          => $status,
            'currency'        => $currency,
            'total_amount'    => $total_amount,
            'subtotal_amount' => $subtotal_amount,
            'discount_amount' => $discount_amount,
            'tax_amount'      => $tax_amount,
            'shipping_amount' => $shipping_amount,
            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,
            'shipping_method' => $shipping_method,
            'billing_name'    => $billing_name,
            'billing_email'   => $billing_email,
            'billing_phone'   => $billing_phone,
            'billing_address' => $billing_address,
            'shipping_name'   => $shipping_name,
            'shipping_phone'  => $shipping_phone,
            'shipping_address' => $shipping_address,
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql')
        );

        $format = array(
            '%d',   // user_id
            '%s',   // status
            '%s',   // currency
            '%f',   // total_amount
            '%f',   // subtotal_amount
            '%f',   // discount_amount
            '%f',   // tax_amount
            '%f',   // shipping_amount
            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id
            '%s',   // shipping_method
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%s',   // shipping_name
            '%s',   // shipping_phone
            '%s',   // shipping_address
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id


        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);
        
        if(!empty($order_note)){
        $this->add_order_note([
                    'order_id' => $order_id,
                    'note' => $order_note,
                    'author_email' => $billing_email,
                    'author' => $billing_name,

                ]);
        }


        // Current date (Y-m-d, you can change format)
        $current_datetime = gmdate("Y-m-d H:i:s");

        // Combine string + date
        $combined = $order_id . $current_datetime;

        // Generate hash (MD5 example)
        $order_id_hash = md5($combined);



        $ComboStoreObjectMeta->update_meta('orders', $order_id, "order_id_hash", $order_id_hash);


        if ($subscription_enable) {

            if ($subscription_interval == 'day') {
                $currentDate = new DateTime("2025-09-13");
                $daysToAdd = 1 * $subscription_interval_count;
                $currentDate->modify("+{$daysToAdd} days");

                $next_billing_date = $currentDate->format("Y-m-d");
            }
            if ($subscription_interval == 'week') {
                $currentDate = new DateTime("2025-09-13");
                $daysToAdd = 7 * $subscription_interval_count;
                $currentDate->modify("+{$daysToAdd} days");

                $next_billing_date = $currentDate->format("Y-m-d");
            }
            if ($subscription_interval == 'month') {
                $currentDate = new DateTime("2025-09-13");
                $daysToAdd = 30 * $subscription_interval_count;
                $currentDate->modify("+{$daysToAdd} days");

                $next_billing_date = $currentDate->format("Y-m-d");
            }
            if ($subscription_interval == 'year') {
                $currentDate = new DateTime("2025-09-13");
                $daysToAdd = 365 * $subscription_interval_count;
                $currentDate->modify("+{$daysToAdd} days");

                $next_billing_date = $currentDate->format("Y-m-d");
            }


            $sub_params = [];
            $sub_params['user_id'] =  $user_id;
            $sub_params['order_id'] =  $order_id;
            $sub_params['status'] =  "active";
            $sub_params['next_billing_date'] =  $next_billing_date;
            $sub_params['renewal_interval'] =  $subscription_interval;
            $sub_params['interval_count'] =  $subscription_interval_count;
            $sub_params['last_payment_amount'] =  $subscription_interval_count;
            $sub_params['total_amount'] =  $total_amount;
            $sub_params['subtotal_amount'] =  $subtotal_amount;



            $ComboStoreSubscriptions = new ComboStoreSubscriptions();
            $subscription_response = $ComboStoreSubscriptions->create_subscription($sub_params);
        }


        $userData = get_user_by("ID", $user_id);

        $action_prams = array_merge(
            $data,
            [
                "cartItems" => $cartItems,
                "order_id" => $order_id,
                "email" => isset($userData->user_email) ? $userData->user_email : '',
                "name" => isset($userData->display_name) ? $userData->display_name : '',
            ],
        );


        if ($wpdb->last_error) {
            $response['errors'] = true;
            do_action("combo_store_order_create_failed", $action_prams);
        } else {



            $prams = [
                "order_id" => $order_id,
                "cartItems" => $cartItems,
            ];

            $this->insert_order_items($prams);

            $response['success'] = true;
            $response['order_id'] = $order_id;
            $response['order_hash'] = $order_id_hash;

            do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }





    function get_order($order_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_order";


        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $order_items = $this->get_order_items($order_id);

        $orderRow->order_items = $order_items;
        $orderRow->shipping_amount = 0;


        return $orderRow;
    }








    function update_order($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_orders';


        // ✅ Extract variables safely (example fields you may want to update)
        $order_id      = isset($params['id']) ? intval($params['id']) : 0;
        $user_id      = isset($params['userid']) ? intval($params['userid']) : 0;
        $status               = isset($params['status']) ? sanitize_text_field($params['status']) : null;
        $currency               = isset($params['currency']) ? sanitize_text_field($params['currency']) : "";
        $billing_name               = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : null;
        $billing_email    = isset($params['billing_email']) ? sanitize_email($params['billing_email']) : null;

        $payment_method    = !empty($params['payment_method']) ? sanitize_text_field($params['payment_method']) : null;
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : null;
        $transaction_id    = !empty($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : null;
        $shipping_method  = isset($params['shipping_method']) ? sanitize_text_field($params['shipping_method']) : null;

        $billing_name  = isset($params['billing_name']) ? sanitize_text_field($params['billing_name']) : null;
        $billing_address  = isset($params['billing_address']) ? sanitize_text_field($params['billing_address']) : null;
        $billing_phone  = isset($params['billing_phone']) ? sanitize_text_field($params['billing_phone']) : null;
        $shipping_name  = isset($params['shipping_name']) ? sanitize_text_field($params['shipping_name']) : null;
        $shipping_phone  = isset($params['shipping_phone']) ? sanitize_text_field($params['shipping_phone']) : null;
        $shipping_address  = isset($params['shipping_address']) ? sanitize_text_field($params['shipping_address']) : null;



        $total_amount    = isset($params['total_amount']) ? floatval($params['total_amount']) : 0.00;
        $subtotal_amount = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0.00;
        $discount_amount = isset($params['discount_amount']) ? floatval($params['discount_amount']) : 0.00;
        $tax_amount      = isset($params['tax_amount']) ? floatval($params['tax_amount']) : 0.00;
        $shipping_amount = isset($params['shipping_amount']) ? floatval($params['shipping_amount']) : 0.00;

        $lineItems = isset($params['lineItems']) ? stripslashes_deep($params['lineItems']) : [];


        // ✅ Data to update
        $data = array(
            'userid'         => $user_id,
            'status'          => $status,
            'currency'        => $currency,
            'total_amount'    => $total_amount,
            'subtotal_amount' => $subtotal_amount,
            'discount_amount' => $discount_amount,
            'tax_amount'      => $tax_amount,
            'shipping_amount' => $shipping_amount,
            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,
            'shipping_method' => $shipping_method,
            'billing_name'    => $billing_name,
            'billing_email'   => $billing_email,
            'billing_phone'   => $billing_phone,
            'billing_address' => $billing_address,
            'shipping_name'   => $shipping_name,
            'shipping_phone'  => $shipping_phone,
            'shipping_address' => $shipping_address,
            'updated_at'      => current_time('mysql')
        );

        // ✅ Format mapping
        $format = array(
            '%d',   // user_id
            '%s',   // status
            '%s',   // currency
            '%f',   // total_amount
            '%f',   // subtotal_amount
            '%f',   // discount_amount
            '%f',   // tax_amount
            '%f',   // shipping_amount
            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id
            '%s',   // shipping_method
            '%s',   // billing_name
            '%s',   // billing_email
            '%s',   // billing_phone
            '%s',   // billing_address
            '%s',   // shipping_name
            '%s',   // shipping_phone
            '%s',   // shipping_address
            '%s'    // updated_at
        );

        // ✅ WHERE clause
        $where = array('id' => $order_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        $action_prams = array_merge($data, ["order_id" => $order_id, "billing_name" => $billing_name, "total_amount" => $total_amount, "payment_method" => $payment_method, 'billing_email' => $billing_email],);



        if ($updated) {
            $response = true;

            if($status == 'completed'){
                foreach($lineItems as $lineItem){
                    $product_id = isset($lineItem['product_id']) ? intval($lineItem['product_id']) : 0;
                    $quantity = isset($lineItem['quantity']) ? intval($lineItem['quantity']) : 1;

                    $stockStatus = get_post_meta($product_id, 'stockStatus', true);

                    if($stockStatus == 'instock'){
                    $stockCount = intval(get_post_meta($product_id, 'stockCount', true));

                    $stockCount = $stockCount-$quantity;
                    if($stockCount < 0){
                        $stockCount = 0;
                    }

                    $ComboStoreObjectMeta = new ComboStoreObjectMeta();
                    $stock_updated = $ComboStoreObjectMeta->get_meta('orders', $order_id, "stock_updated");


                    if(!$stock_updated ){
                        update_post_meta($product_id, 'stockCount', $stockCount);
                        $ComboStoreObjectMeta->update_meta('orders', $order_id, "stock_updated", 1);

                        if($stockCount < 5){
                            do_action("combo_store_low_stock", $product_id);
                        }

                    }
 
                    }

                    
                }

            }


            $prams = [
                "order_id" => $order_id,
                "cartItems" => $lineItems,
            ];

            $this->update_order_items($prams);

            do_action("combo_store_order_updated", $action_prams);

        } else {
            $response = false;
            do_action("combo_store_order_update_failed", $action_prams);
        }
        return $response;
    }

    function insert_order_items($prams)
    {


$response = [];

        $cartItems = isset($prams['cartItems']) ? $prams['cartItems'] : '';
        $order_id = isset($prams['order_id']) ? $prams['order_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_order_items";

        foreach ($cartItems as $cartItem) {
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
                'order_id'     => $order_id,
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
        }else{
            $response['status'] = 'success';
}

            return $response;

        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    function update_order_items($prams)
    {

        $cartItems = isset($prams['cartItems']) ? $prams['cartItems'] : '';
        $order_id = isset($prams['order_id']) ? $prams['order_id'] : '';



        global $wpdb;
        $table = $wpdb->prefix . "cstore_order_items";



        $this->delete_order_items($order_id);


        foreach ($cartItems as $cartItem) {
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
                'order_id'     => $order_id,
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
        }else{
            $response['status'] = 'success';
}

            return $response;


        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }





 function delete_order_items($order_id)
    {

        // $order_id = isset($prams['order_id']) ? $prams['order_id'] : '';
        // $item_id = isset($prams['item_id']) ? $prams['item_id'] : '';




        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_order_items';


        $response = [];

        if (!$order_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('order_id' => $order_id), array('%d'));



        $response['status'] = $status;

        return $response;
    }








    function get_order_items($order_id)
    {


        global $wpdb;
        $table_order_items = $wpdb->prefix . "cstore_order_items";


        $query = $wpdb->prepare(
            "SELECT * FROM $table_order_items WHERE order_id = %d",
            $order_id
        );

        $order_items = $wpdb->get_results($query);

        return $order_items;
    }





    function get_order_id_by_hash($hash)
    {


        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $object_id = $ComboStoreObjectMeta->get_object_id_meta_value('orders', 'order_id_hash', $hash);


        return  $object_id;
    }



    function add_order_note($args)
    {

    $order_id = isset($args['order_id']) ? $args['order_id'] : '';
    $note = isset($args['note']) ? $args['note'] : '';
    $author_email = isset($args['author_email']) ? $args['author_email'] : '';
    $author = isset($args['author']) ? $args['author'] : 'Admin';


     $comment_id = wp_insert_comment([
    'comment_post_ID'      => $order_id,
    'comment_author'       => $author,
    'comment_author_email' => $author_email,
    'comment_content'      => $note,
    'comment_type'         => 'order_note',
    'comment_approved'     => 1,
]);

add_comment_meta($comment_id, 'is_customer_note', 0);   

    }


    function check_user_by($by, $value)
    {
        $user = get_user_by($by, $value);
        $user_id = isset($user->ID) ? $user->ID : '';

        return $user_id ? ['id' => $user_id] : null;
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
