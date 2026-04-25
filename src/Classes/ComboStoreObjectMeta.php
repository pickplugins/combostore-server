<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreObjectMeta
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

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE object_id=%d AND meta_key = %s", $id, $meta_key));



        $meta_value = isset($meta_data->meta_value) ? $meta_data->meta_value : '';



        return $meta_value;
    }
    function get_object_id_meta_value($object, $meta_key, $value)
    {
        $this->object_name = $object;
        $table = $this->object_meta_table();





        global $wpdb;

        $response = [];

        if (!$value) {
            $response['errors']["id_missing"] = "Object Id Missing.";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE meta_key=%d AND meta_value = %s",  $meta_key, $value));



        $object_id = isset($meta_data->object_id) ? $meta_data->object_id : '';



        return $object_id;
    }


    function object_table()
    {
        global $wpdb;
        $object = $this->object_name;
        $prefix = $wpdb->prefix;


        if ($object == 'orders') {
            $table = $prefix . 'cstore_orders';
        }
        if ($object == 'subscriptions') {
            $table = $prefix . 'cstore_subscriptions';
        }
        if ($object == 'purchases') {
            $table = $prefix . 'cstore_purchases';
        }
        if ($object == 'expenses') {
            $table = $prefix . 'cstore_expenses';
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
        if ($object == 'purchases') {
            $table = $prefix . 'cstore_purchases_meta';
        }
        if ($object == 'expenses') {
            $table = $prefix . 'cstore_expenses_meta';
        }

        return $table;
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
