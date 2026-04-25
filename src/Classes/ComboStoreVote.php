<?php

namespace ComboStore\Classes;

use Error;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreVote
{
    public $object_name = "";


    public function __construct() {}

    function ceate_vote($prams)
    {



        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $vote_type = isset($prams['vote_type']) ? $prams['vote_type'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_vote';

        $response = [];

        $prams = [];
        $prams['userid'] = $userid;
        $prams['object_id'] = $object_id;
        $prams['vote_type'] = $vote_type;


        if (!$userid) {

            $response['errors'] = true;
            $response['message'] = __("User id missing.", 'combo-store-server');
            return $response;
        }

        $daily_limit = $this->vote_daily_limit($prams);

        if ($daily_limit >= 10) {

            $response['errors'] = true;
            $response['message'] = __("Daily vote limit reached.", 'combo-store-server');
            return $response;
        }

        $has_voted = $this->has_voted($prams);


        if ($has_voted) {
            $response = $this->update_vote($prams);
        } else {
            $data = array(
                'userid'       => $userid,
                'object_id'       => $object_id,
                'vote_type'       => $vote_type,
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
        }




        return $response;
    }
    function update_vote($prams)
    {



        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $vote_type = isset($prams['vote_type']) ? $prams['vote_type'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_vote';

        $response = [];

        $has_prams = [];
        $has_prams['userid'] = $userid;
        $has_prams['object_id'] = $object_id;
        $has_prams['vote_type'] = $vote_type;


        $updated_data = array(
            'userid' => $userid,
            'object_id' => $object_id,
            'vote_type' => $vote_type,

        );
        $where = array('userid' => $userid, 'object_id' => $object_id,);

        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }









        return $response;
    }


    function has_voted($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_vote";



        $query = "SELECT COUNT(*) FROM $table WHERE userid = %d";
        $params = [$userid];

        if (!empty($object_id)) {
            $query .= " AND object_id = %d";
            $params[] = $object_id;
        }


        $vote_count = $wpdb->get_var($wpdb->prepare($query, ...$params));



        if ($vote_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function total_vote($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_vote";



        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        // if (!empty($object_id)) {
        //     $query .= " AND object_id = %d";
        //     $params[] = $object_id;
        // }


        $vote_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        return $vote_count;
    }
    function vote_daily_limit($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $userid = isset($prams['userid']) ? $prams['userid'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_vote";
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














    function delete_vote($prams)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_vote';


        $response = [];

        if (!$userid) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('userid' => $userid, 'object_id' => $object_id,), array('%d', '%d'));


        $response['status'] = $status;

        return $response;
    }



    function get_vote($prams)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_vote';

        $response = [];

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';



        if (!$userid) {
            $response['errors'] = "No vote found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE userid=%d AND object_id=%d",  $userid, $object_id));


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
