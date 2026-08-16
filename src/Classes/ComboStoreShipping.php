<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use ComboStore\Classes\ComboStoreDeliveries;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreShipping
{
    public $object_name = "";


    public function __construct() {}

    function create_shipping_zone($params)
    {


        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description          = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $region        = isset($params['region']) ? sanitize_text_field($params['region']) : null;
        $status    = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $settings = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;


        error_log(wp_json_encode($params));


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_zones';

        $response = [];

        $data = array(
            'name'         => $name,
            'description'  => $description,
            'region'        => $region,
            'status'        => $status,
            'settings' => $settings,
            'priority' => $priority,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%s',   // name
            '%s',   // description
            '%s',   // region
            '%s',   // status
            '%s',   // settings
            '%d',   // priority
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $order_id;
        }






        return $response;
    }

    function create_shipping_zone_location($params)
    {


        $zone_id          = isset($params['zone_id']) ? sanitize_text_field($params['zone_id']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $value        = isset($params['value']) ? sanitize_text_field($params['value']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_orders';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'type'          => $type,
            'value'        => $value,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // type
            '%s',   // value
            '%s',   // status
            '%d',   // priority
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $order_id;
        }






        return $response;
    }

    function create_shipping_method($params)
    {

        error_log(wp_json_encode($params));



        $zone_id          = isset($params['zone_id']) ? intval($params['zone_id']) : '';
        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $amount          = isset($params['amount']) ? sanitize_text_field($params['amount']) : 0;
        $description        = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $settings        = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority        = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;
        $status        = isset($params['status']) ? sanitize_text_field($params['status']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_methods';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'name'          => $name,
            'type'          => $type,
            'amount'          => $amount,
            'description'        => $description,
            'settings'        => $settings,
            'priority'        => $priority,
            'status'        => $status,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // name
            '%s',   // type
            '%s',   // amount
            '%s',   // description
            '%s',   // settings
            '%d',   // priority
            '%s',   // status
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $order_id;
        }






        return $response;
    }













    function get_shipping_zone($order_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_shipping_zones";
        $ComboStoreDeliveries = new ComboStoreDeliveries();

        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );



        return $orderRow;
    }
    function get_shipping_method($order_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_shipping_methods";

        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );



        return $orderRow;
    }



    function get_shipping_zones($prams)
    {



        $responses = [];

        $user_role     = isset($prams['user_role']) ? ($prams['user_role']) : '';
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
        $table_shipping_zones = $prefix . 'cstore_shipping_zones';

        $table_shipping_zones = $prefix . 'cstore_shipping_zones';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_shipping_zones WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

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



        $where_sql = '';

        if (!empty($where)) {
            $where_sql = "WHERE " . implode(' AND ', $where);
        }

        $allowed_order = ['ASC', 'DESC'];
        $order = strtoupper($order);

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        $sql = "SELECT * FROM $table_shipping_zones
                    $where_sql
                    ORDER BY created_at $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;


        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);




        $count_sql = "
                SELECT COUNT(*)
                FROM $table_shipping_zones
                $where_sql
                ORDER BY created_at $order
            ";
        $filter_params = [];
        $filter_params[] = $status;




        $count_query = !empty($filter_params)
            ? $wpdb->prepare($count_sql, $filter_params)
            : $count_sql;



        $total = (int) $wpdb->get_var($count_query);




        //$total = count($entries);





        $big = 999999999; // need an unlikely integer





        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }



    function get_shipping_methods($prams)
    {



        $responses = [];

        $zone_id     = isset($prams['zone_id']) ? ($prams['zone_id']) : '';
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

        $table_shipping_zones = $prefix . 'cstore_shipping_methods';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));


        $where   = [];
        $params  = [];



        if (!empty($zone_id)) {
            $where[]  = "zone_id = %d";
            $params[] = $zone_id;
        }
        if (!empty($status)) {
            $where[]  = "status = %s";
            $params[] = $status;
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

        $sql = "SELECT * FROM $table_shipping_zones
                    $where_sql
                    ORDER BY created_at $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;


        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);




        $count_sql = "
                SELECT COUNT(*)
                FROM $table_shipping_zones
                $where_sql
                ORDER BY created_at $order
            ";
        $filter_params = [];
        $filter_params[] = $status;




        $count_query = !empty($filter_params)
            ? $wpdb->prepare($count_sql, $filter_params)
            : $count_sql;



        $total = (int) $wpdb->get_var($count_query);




        //$total = count($entries);





        $big = 999999999; // need an unlikely integer





        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }


    function update_shipping_zone($params)
    {

        $responses = [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_zones';

        $order_id      = isset($params['id']) ? intval($params['id']) : 0;

        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description          = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $region        = isset($params['region']) ? sanitize_text_field($params['region']) : null;
        $status    = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $settings = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;


        // ✅ Data to update
        $data = array(
            'name'         => $name,
            'description'  => $description,
            'region'        => $region,
            'status'        => $status,
            'settings' => $settings,
            'priority' => $priority,
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        // ✅ Format mapping
        $format = array(
            '%s',   // name
            '%s',   // description
            '%s',   // region
            '%s',   // status
            '%s',   // settings
            '%d',   // priority
            '%s'    // updated_at
        );

        // ✅ WHERE clause
        $where = array('id' => $order_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);



        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }
        return $responses;
    }
    function update_shipping_method($params)
    {

        $responses = [];



        $id          = isset($params['id']) ? intval($params['id']) : '';
        $zone_id          = isset($params['zone_id']) ? intval($params['zone_id']) : '';
        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $amount          = isset($params['amount']) ? sanitize_text_field($params['amount']) : 0;
        $description        = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $settings        = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority        = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;
        $status        = isset($params['status']) ? sanitize_text_field($params['status']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_methods';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'name'          => $name,
            'type'          => $type,
            'amount'          => $amount,
            'description'        => $description,
            'settings'        => $settings,
            'priority'        => $priority,
            'status'        => $status,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // name
            '%s',   // type
            '%s',   // amount
            '%s',   // description
            '%s',   // settings
            '%d',   // priority
            '%s',   // status
            '%s',   // created_at
            '%s'    // updated_at
        );


        // ✅ WHERE clause
        $where = array('id' => $id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);



        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }
        return $responses;
    }
}
