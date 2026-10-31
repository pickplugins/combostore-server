<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreTemplates
{
    public $object_name = "";


    public function __construct() {}

    function create_template_part($params)
    {

        error_log(wp_json_encode($params));


        $title       = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $content = isset($params['content']) ? sanitize_text_field($params['content']) : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';

        $response = [];






        $data = array(
            'title'    => $title,
            'type'  => $type,
            'content'  => $content,
            'datetime'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        $format = array(
            '%s',   // title
            '%s',   // title
            '%s',   // type
            '%s',   // content
            '%s',    // datetime
        );

        $wpdb->insert($table, $data, $format);

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


    function get_template_part($id)
    {

        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';


        $expenseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );


        return $expenseRow;
    }




    function get_template_parts($prams)
    {


        $responses = [];
        $type     = isset($prams['type']) ? sanitize_text_field($prams['type']) : "";


        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";


        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_expenses = $prefix . 'cstore_template_parts';
        $offset = ($page - 1) * $per_page;



        $where   = [];
        $params  = [];

        if (!empty($type)) {
            $where[]  = "type = %s";
            $params[] = $type;
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





        $big = 999999999; // need an unlikely integer


        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }






    function update_template_part($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';

        error_log(wp_json_encode($params));



        $id   = isset($params['id']) ? floatval($params['id']) : '';
        $title   = isset($params['title']) ? sanitize_text_field($params['title']) : '';
        $type       = isset($params['type']) ? sanitize_text_field($params['type']) : '';

        $content = isset($params['blocks']) ? stripslashes_deep($params['blocks']) : [];
        $content = wp_json_encode($content, JSON_UNESCAPED_UNICODE);


        $response = [];



        error_log($content);


        $data = array(
            'title'    => $title,
            'type'        => $type,
            'content'  => $content,

        );

        $format = array(
            '%s',   // title
            '%s',   // type
            '%s',   // content
        );


        // ✅ WHERE clause
        $where = array('id' => $id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        if ($updated) {
            $response = true;
        } else {
            $response = false;
        }
        return $response;
    }












    function delete_template_part($id)
    {


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_template_parts';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id), array('%d'));



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
