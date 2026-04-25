<?php

namespace PromptsHub\Classes;

use Error;

if (!defined('ABSPATH')) exit;  // if direct access


class PromptsHubPurchase
{
    public $object_name = "";


    public function __construct() {}

    function create_purchase($prams)
    {



        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $credits = isset($prams['credits']) ? $prams['credits'] : 0;

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'phub_purchases';

        $response = [];

        $has_prams = [];
        $has_prams['userid'] = $userid;
        $has_prams['object_id'] = $object_id;
        $has_prams['credits'] = $credits;

        $has_purchased = $this->has_purchased($has_prams);


        if ($has_purchased) return;

        $data = array(
            'userid'       => $userid,
            'object_id'       => $object_id,
            'credits'       => $credits,
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


        $userData = get_user_by("ID", $userid);

        $action_prams = array_merge($data, ["email" => $userData->user_email, "name" => $userData->display_name]);

        //do_action("promptshub_purchased", $action_prams);


        return $response;
    }


    function has_purchased($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "phub_purchases";



        $query = "SELECT COUNT(*) FROM $table WHERE userid = %d";
        $params = [$userid];

        if (!empty($object_id)) {
            $query .= " AND object_id = %d";
            $params[] = $object_id;
        }


        $purchase_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        if ($purchase_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function total_loved($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "phub_loved";



        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        // if (!empty($object_id)) {
        //     $query .= " AND object_id = %d";
        //     $params[] = $object_id;
        // }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        return $love_count;
    }














    function delete_loved($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'phub_loved';


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
        $table = $prefix . 'phub_loved';

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
