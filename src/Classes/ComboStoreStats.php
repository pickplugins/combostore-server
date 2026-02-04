<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreStats
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";


    public function __construct() {}









    function total_orders($user_id)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_orders';

        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        if ($isAdmin) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table",
                $user_id,
            ));
        } else {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table WHERE userid = %d",
                $user_id,
            ));
        }



        return $total ? $total : 0;
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


$ComboStoreStats = new ComboStoreStats();
