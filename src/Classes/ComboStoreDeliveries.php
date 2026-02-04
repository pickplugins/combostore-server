<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreDeliveries
{
    public $object_name = "";


    public function __construct() {}

    function create_delivery($params)
    {


        $customer_id         = isset($params['customer_id']) ? intval($params['customer_id']) : 0;
        $order_id         = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $rider_id         = isset($params['rider_id']) ? intval($params['rider_id']) : 0;
        $status          = isset($params['status']) ? sanitize_text_field($params['status']) : 'pending';
        $notes        = isset($params['notes']) ? sanitize_text_field($params['notes']) : '';
        $startLatLng        = isset($params['startLatLng']) ? stripslashes_deep($params['startLatLng']) : '';
        $endLatLng        = isset($params['endLatLng']) ? stripslashes_deep($params['endLatLng']) : '';

        // $cartItems = isset($params['cartItems']) ? stripslashes_deep($params['cartItems']) : [];


        $has_prams = [];
        $has_prams['customer_id'] = $customer_id;
        $has_prams['order_id'] = $order_id;


        $has_delivery = $this->has_delivery($has_prams);


        if ($has_delivery) {
            $response['message'] = "delivery exist";
            return $response;
        }






        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_deliveries';

        $response = [];





        $data = array(
            'customer_id'         => $customer_id,
            'order_id'         => $order_id,
            'rider_id'        => $rider_id,
            'status'          => $status,
            'startLatLng'    => wp_json_encode($startLatLng),
            'endLatLng'    => wp_json_encode($endLatLng),
            'notes'    => $notes,
            'datetime'      => current_time('mysql'),
        );

        $format = array(
            '%d',   // order_id
            '%d',   // order_id
            '%d',   // rider_id
            '%s',   // status
            '%s',   // startLatLng
            '%s',   // endLatLng
            '%s',   // $notes
            '%s',   // current_time

        );

        $wpdb->insert($table, $data, $format);
        $delivery_id = $wpdb->insert_id; // Get inserted order_id


        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        // $ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);


        // $userData = get_user_by("ID", $user_id);

        // $action_prams = array_merge($data, ["order_id" => $order_id, "email" => $userData->user_email, "name" => $userData->display_name]);


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {

            $response['success'] = true;
            $response['delivery_id'] = $delivery_id;

            //do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }





    function get_delivery($order_id, $delivery_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_deliveries";

        if(!empty($delivery_id)){

            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE id = %d",
                    $delivery_id
                    ),
                    ARRAY_A
                );

        }else{
            $rowData = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE order_id = %d",
                    $order_id
                ),
                ARRAY_A
            );
        }

        $row = [
            "id" => isset($rowData['id']) ? $rowData['id'] : 0,
            "customer_id" => isset($rowData['customer_id']) ? $rowData['customer_id'] : 0,
            "order_id" => isset($rowData['order_id']) ? $rowData['order_id'] : 0,
            "rider_id" => isset($rowData['rider_id']) ? $rowData['rider_id'] : 0,
            "type" => isset($rowData['type']) ? $rowData['type'] : '',
            "status" => isset($rowData['status']) ? $rowData['status'] : '',
            "startLatLng" => isset($rowData['startLatLng']) ? json_decode($rowData['startLatLng'], true) : [],
            "endLatLng" => isset($rowData['endLatLng']) ? json_decode($rowData['endLatLng'], true) : [],
            "notes" => isset($rowData['notes']) ? $rowData['notes'] : '',
            "datetime" => isset($rowData['datetime']) ? $rowData['datetime'] : ''
        ];




        return $row;
    }

    function update_delivery($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_deliveries';




        // ✅ Extract variables safely (example fields you may want to update)
        $delivery_id      = isset($params['id']) ? intval($params['id']) : 0;
        $status               = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $type               = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $rider_id               = isset($params['rider_id']) ? sanitize_text_field($params['rider_id']) : '';
        $notes    = !empty($params['notes']) ? sanitize_text_field($params['notes']) : null;
        $startLatLng    = !empty($params['startLatLng']) ? (wp_unslash($params['startLatLng'])) : null;
        $endLatLng    = !empty($params['endLatLng']) ? (wp_unslash($params['endLatLng'])) : null;


        
        

        // ✅ Data to update
        $data = array(
            'type'              => $type,
            'status'              => $status,
            'rider_id'              => $rider_id,
            'notes'   => $notes,
            'startLatLng'   => wp_json_encode($startLatLng),
            'endLatLng'   => wp_json_encode($endLatLng),

        );

        // ✅ Format mapping
        $format = array(
            '%s',  // type
            '%s',  // status
            '%d',  // rider_id
            '%s',  // notes
            '%s',  // startLatLng
            '%s',  // endLatLng

        );

        // ✅ WHERE clause
        $where = array('id' => $delivery_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        if ($updated) {
            $response = true;
        } else {
            $response = true;
        }
        return $response;
    }
    function has_delivery($prams)
    {

        $order_id = isset($prams['order_id']) ? $prams['order_id'] : '';
        $customer_id = isset($prams['customer_id']) ? $prams['customer_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_deliveries';



        $query = "SELECT COUNT(*) FROM $table WHERE order_id = %d";
        $params = [$order_id];

        if (!empty($customer_id)) {
            $query .= " AND customer_id = %d";
            $params[] = $customer_id;
        }


        $count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        if ($count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function insert_tracking($prams)
    {



        $order_id           = isset($prams['order_id']) ? sanitize_text_field($prams['order_id']) : '';
        $delivery_id           = isset($prams['delivery_id']) ? sanitize_text_field($prams['delivery_id']) : '';

        $status           = isset($prams['status']) ? sanitize_text_field($prams['status']) : '';
        $latlng          = isset($prams['latlng']) ? ($prams['latlng']) : '';

        $notes           = isset($prams['notes']) ? sanitize_text_field($prams['notes']) : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_deliveries_trackings";



        // Insert data
        $data = array(
            'order_id'     => $order_id,
            'delivery_id'   => $delivery_id,
            'status' => $status,
            'latlng'          => wp_json_encode($latlng),
            'notes'     => $notes,
            'datetime'      => current_time('mysql'),

        );

        $format = array(
            '%d', // $order_id
            '%d', // $delivery_id
            '%s', // $status
            '%s', // $latlng
            '%s', // $notes
            '%s', // current_time

        );

        $wpdb->insert($table, $data, $format);

        // Get inserted item_id
        $item_id = $wpdb->insert_id;


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {

            $response['tracking_id'] = $item_id;

            $response['success'] = true;

            //do_action("combo_store_order_created", $action_prams);
        }

        return $response;
    }

    function get_trackings($order_id)
    {


        global $wpdb;
        $table = $wpdb->prefix . "cstore_deliveries_trackings";


        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE order_id = %d",
            $order_id
        );

        $trackings = $wpdb->get_results($query);

        return $trackings;
    }

    function delete_tracking($prams)
    {

        $customer_id = isset($prams['customer_id']) ? $prams['customer_id'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_loved';


        $response = [];

        if (!$customer_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('customer_id' => $customer_id, 'object_id' => $object_id,), array('%d', '%d'));


        $response['status'] = $status;

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
