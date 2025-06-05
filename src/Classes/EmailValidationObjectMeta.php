<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationObjectMeta
{
    public $object_name = "";


    public function __construct() {}

    function ceate_meta($object, $object_id, $meta_key, $meta_value)
    {
        $this->object_name = $object;
        $table = $this->object_table();

        $response = [];
        global $wpdb;


        $data = array(
            'object_id'       => $object_id,
            'meta_key'           => $meta_key,
            'meta_value'         => $meta_value,
        );

        $format = array(
            '%d',
            '%s',
            '%s'
        );

        $wpdb->insert($table, $data, $format);


        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {
            $response['success'] = true;
        }










        return $response;
    }
    function delete_meta($object, $id, $meta_key)
    {

        $this->object_name = $object;
        $table = $this->object_table();
        global $wpdb;

        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting API key failed";
        }


        $status = $wpdb->delete($table, array('id' => $id, 'meta_key' => $meta_key,), array('%d', '%s'));


        $response['status'] = $status;

        return $response;
    }
    function update_meta($object, $id, $meta_key, $meta_value)
    {

        $this->object_name = $object;
        $table = $this->object_table();

        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting API key failed";
        }


        global $wpdb;

        $updated_data = array(
            'meta_value' => $meta_value,
        );
        $where = array('id' => $id, 'meta_key' => $meta_key,);

        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }
        return $response;
    }
    function get_meta($object, $id, $meta_key)
    {
        $this->object_name = $object;
        $table = $this->object_table();

        global $wpdb;

        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting API key failed";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d AND meta_key = %s", $id, $meta_key));


        $meta_value = isset($meta_data->meta_value) ? $meta_data->meta_value : '';



        return $meta_value;
    }


    function object_table()
    {
        global $wpdb;
        $object = $this->object_name;
        $prefix = $wpdb->prefix;

        if ($object == 'task') {
            $table = $prefix . 'cstore_validation_tasks';
        }
        if ($object == 'orders') {
            $table = $prefix . 'cstore_orders';
        }
        if ($object == 'subscriptions') {
            $table = $prefix . 'cstore_subscriptions';
        }

        return $table;
    }



    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }





    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = date('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = date('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}
