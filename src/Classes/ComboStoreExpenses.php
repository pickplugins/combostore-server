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



        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';
        $subcategory    = isset($params['subcategory']) ? sanitize_text_field($params['subcategory']) : '';

        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';

        $created_by     = isset($params['created_by']) ? intval($params['created_by']) : 0;
        $created_for    = isset($params['created_for']) ? intval($params['created_for']) : 0;

        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';

        $created_at     = current_time('mysql');



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';

        $response = [];






        $data = array(
            'total_amount'    => $total_amount,
            'category'        => $category,
            'subcategory'     => $subcategory,

            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,

            'craeted_by'      => $created_by,
            'craeted_for'     => $created_for,

            'note'            => $note,

            'created_at'      => current_time('mysql')
        );

        $format = array(
            '%f',   // total_amount
            '%s',   // category
            '%s',   // subcategory

            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id

            '%d',   // craeted_by
            '%d',   // craeted_for

            '%s',   // note

            '%s'    // created_at
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





    function get_expenses($id)
    {

        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';


        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_expenses WHERE id = %d",
                $id
            ),
            ARRAY_A
        );



        return $orderRow;
    }








    function update_expenses($params)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_expenses';



        $id   = isset($params['id']) ? floatval($params['id']) : '';
        $total_amount   = isset($params['total_amount']) ? floatval($params['total_amount']) : 0;
        $category       = isset($params['category']) ? sanitize_text_field($params['category']) : '';
        $subcategory    = isset($params['subcategory']) ? sanitize_text_field($params['subcategory']) : '';

        $payment_method = isset($params['payment_method']) ? sanitize_text_field($params['payment_method']) : '';
        $payment_status = isset($params['payment_status']) ? sanitize_text_field($params['payment_status']) : '';
        $transaction_id = isset($params['transaction_id']) ? sanitize_text_field($params['transaction_id']) : '';

        $created_by     = isset($params['created_by']) ? intval($params['created_by']) : 0;
        $created_for    = isset($params['created_for']) ? intval($params['created_for']) : 0;

        $note           = isset($params['note']) ? sanitize_textarea_field($params['note']) : '';

        $created_at     = current_time('mysql');



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_expenses = $prefix . 'cstore_expenses';

        $response = [];






        $data = array(
            'total_amount'    => $total_amount,
            'category'        => $category,
            'subcategory'     => $subcategory,

            'payment_method'  => $payment_method,
            'payment_status'  => $payment_status,
            'transaction_id'  => $transaction_id,

            'craeted_by'      => $created_by,
            'craeted_for'     => $created_for,

            'note'            => $note,

            'created_at'      => current_time('mysql')
        );

        $format = array(
            '%f',   // total_amount
            '%s',   // category
            '%s',   // subcategory

            '%s',   // payment_method
            '%s',   // payment_status
            '%s',   // transaction_id

            '%d',   // craeted_by
            '%d',   // craeted_for

            '%s',   // note

            '%s'    // created_at
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
