<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreRequestForStock
{
    public $object_name = "";


    public function __construct() {}

    function create_ticket($params)
    {


        $user_id         = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $product_id         = isset($params['product_id']) ? intval($params['product_id']) : 0;
        $user_name  = isset($params['user_name']) ? sanitize_text_field($params['user_name']) : '';
        $interval  = isset($params['interval']) ? sanitize_text_field($params['interval']) : '';
        $interval_count  = isset($params['interval_count']) ? sanitize_text_field($params['interval_count']) : '';
        $start_date  = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : '';
        $next_date  = isset($params['next_date']) ? sanitize_text_field($params['next_date']) : '';
        $phone = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';
        $status          = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions_to_call';



        $has_prams = [];
        $has_prams['userid'] = $user_id;
        $has_prams['product_id'] = $product_id;


        $has_subscribed = $this->has_subscribed($has_prams);


        if ($has_subscribed) {
            $response['subscribed'] = true;
            $response['message'] = "Already subscribed";
            return $response;
        }





        $response = [];


        $data = array(
            'userid'         => $user_id,
            'product_id'    => $product_id,
            'user_name' => $user_name,
            'interval' => $interval,
            'interval_count' => $interval_count,
            'start_date' => $start_date,
            'next_date' => $next_date,
            'phone'  => $phone,
            'status'  => $status,
            'datetime'      => current_time('mysql')

        );

        $format = array(
            '%d',   // userid
            '%d',   // product_id
            '%s',   // user_name
            '%s',   // interval
            '%d',   // interval_count
            '%s',   // start_date
            '%s',   // next_date
            '%s',   // phone
            '%s',   // status
            '%s',   // datetime

        );

        $wpdb->insert($table, $data, $format);
        $id = $wpdb->insert_id; // Get inserted order_id


        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        // $ComboStoreObjectMeta->update_meta('orders', $id, "delivery_location", $delivery_location);


        // $userData = get_user_by("ID", $user_id);

        // $action_prams = array_merge($data, ["order_id" => $id, "email" => $userData->user_email, "name" => $userData->display_name]);


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {


            $response['success'] = true;
            $response['id'] = $id;

            //do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }


    function has_subscribed($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $phone = isset($prams['phone']) ? $prams['phone'] : '';
        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_subscriptions_to_call';



        $query = "SELECT COUNT(*) FROM $table WHERE userid = %d";
        $params = [$userid];

        if (!empty($product_id)) {
            $query .= " AND product_id = %d";
            $params[] = $product_id;
        }


        $purchase_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        if ($purchase_count > 0) {
            return true;
        } else {
            return false;
        }
    }



    function total_subscribe_by($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $subscribe_to = isset($prams['subscribe_to']) ? $prams['subscribe_to'] : '';
        $order_id = isset($prams['order_id']) ? $prams['order_id'] : '';
        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_subscriptions_to_call";



        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        // if (!empty($object_id)) {
        //     $query .= " AND object_id = %d";
        //     $params[] = $object_id;
        // }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        return $love_count;
    }














    function delete_subscription($id, $userid)
    {


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions_to_call';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }

        $status = $wpdb->delete($table, array('userid' => $userid, 'id' => $id,), array('%d', '%d'));



        $response['status'] = $status;

        return $response;
    }



    function get($prams)
    {
        $id = isset($prams['id']) ? $prams['id'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions_to_call';

        $response = [];

        if (!$id) {
            $response['errors'] = "No item found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%s",  $id));


        return $meta_data;
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
