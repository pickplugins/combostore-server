<?php

namespace ComboStore\Classes;

use Error;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreLove
{
    public $object_name = "";


    public function __construct() {}

    function ceate_loved($prams)
    {



        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $source = isset($prams['source']) ? $prams['source'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_loved';

        $response = [];

        $has_prams = [];
        $has_prams['userid'] = $userid;
        $has_prams['object_id'] = $object_id;
        $has_prams['source'] = $source;



        if (!$userid) {

            $response['errors'] = true;
            $response['message'] = __("User id missing.", 'combo-store-server');
            return $response;
        }







        $daily_limit = $this->loved_daily_limit($prams);


        if ($daily_limit >= 3) {

            $response['errors'] = true;
            $response['message'] = __("Daily love limit reached.", 'combo-store-server');
            return $response;
        }







        $has_loved = $this->has_loved($has_prams);

        if ($has_loved) return;









        $data = array(
            'userid'       => $userid,
            'object_id'       => $object_id,
            'source'       => $source,
            'datetime'         => $this->get_datetime(),
        );

        $format = array(
            '%d',
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


    function has_loved($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_loved";



        $query = "SELECT COUNT(*) FROM $table WHERE userid = %d";
        $params = [$userid];

        if (!empty($object_id)) {
            $query .= " AND object_id = %d";
            $params[] = $object_id;
        }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        if ($love_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function total_loved($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_loved";



        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        // if (!empty($object_id)) {
        //     $query .= " AND object_id = %d";
        //     $params[] = $object_id;
        // }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        return $love_count;
    }


    function loved_daily_limit($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $userid = isset($prams['userid']) ? $prams['userid'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_loved";
        $today  = current_time('Y-m-d'); // Gets today's date in WordPress timezone



        $query = "SELECT COUNT(*) FROM $table WHERE userid = %d";
        $params = [$userid];

        if (!empty($userid)) {
            $query .= " AND DATE(datetime) = %s";
            $params[] = $today;
        }


        $vote_count = $wpdb->get_var($wpdb->prepare($query, ...$params));





        return $vote_count;
    }











    function delete_loved($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_loved';


        $response = [];

        if (!$userid) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('userid' => $userid, 'object_id' => $object_id,), array('%d', '%d'));


        $response['status'] = $status;

        return $response;
    }



    function get_loved($email)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_loved';

        $response = [];

        if (!$email) {
            $response['errors'] = "No loved found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email=%s",  $email));


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
