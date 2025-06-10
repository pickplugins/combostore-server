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
        $table = $this->object_meta_table();

        $response = null;
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
            $response = true;
        } else {
            $response = false;
        }

        return $response;
    }

    function has_meta($object, $object_id, $meta_key)
    {
        $this->object_name = $object;
        $table = $this->object_meta_table();

        $response = null;
        global $wpdb;


        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE object_id = %d AND meta_key = %s",
                $object_id,
                $meta_key
            )
        );


        if ($existing) {
            $response = true;
        } else {
            $response = false;
        }

        return $response;
    }




    function delete_meta($object, $id, $meta_key)
    {

        $this->object_name = $object;
        $table = $this->object_meta_table();
        global $wpdb;

        $response = [];

        if (!$id) {
            $response['errors']["id_missing"] = "Object Id Missing.";
        }


        $status = $wpdb->delete($table, array('id' => $id, 'meta_key' => $meta_key,), array('%d', '%s'));


        $response['status'] = $status;

        return $response;
    }




    function update_meta($object, $id, $meta_key, $meta_value)
    {

        $this->object_name = $object;
        $table = $this->object_meta_table();

        $response = null;

        if (!$id) {
            $response['errors']["id_missing"] = "Object Id Missing.";
        }


        global $wpdb;

        error_log($meta_key);
        error_log("meta_key: " . wp_json_encode($meta_value));

        if (is_array($meta_value)) {
            $meta_value = wp_json_encode($meta_value);
        }

        $meta_exist = $this->has_meta($object, $id, $meta_key);

        if ($meta_exist) {
            $updated_data = array(
                'meta_value' => $meta_value,
            );
            $where = array('object_id' => $id, 'meta_key' => $meta_key,);

            $updated = $wpdb->update($table, $updated_data, $where);


            error_log($updated);
        } else {

            $updated = $this->ceate_meta($object, $id, $meta_key, $meta_value);
        }




        if ($updated) {
            $response = true;
        } else {
            $response = true;
        }
        return $response;
    }

    function get_meta($object, $id, $meta_key)
    {
        $this->object_name = $object;
        $table = $this->object_meta_table();

        global $wpdb;

        $response = [];

        if (!$id) {
            $response['errors']["id_missing"] = "Object Id Missing.";
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
        if ($object == 'product') {
            $table = $prefix . 'cstore_products';
        }

        return $table;
    }


    function object_meta_table()
    {
        global $wpdb;
        $object = $this->object_name;
        $prefix = $wpdb->prefix;


        if ($object == 'orders') {
            $table = $prefix . 'cstore_orders_meta';
        }
        if ($object == 'subscriptions') {
            $table = $prefix . 'cstore_subscriptions_meta';
        }
        if ($object == 'product') {
            $table = $prefix . 'cstore_products_meta';
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
