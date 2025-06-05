<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationStats
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";


    public function __construct() {}



    function total_email_validated($user_id)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_validation_tasks_entries';


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


    function total_task($user_id)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_validation_tasks';

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
    function total_api_keys($user_id)
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_api_keys';


        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        if ($isAdmin) {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table",

            ));
        } else {
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table WHERE userid = %d",
                $user_id,
            ));
        }



        error_log($total);


        return $total ? $total : 0;
    }



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








    function count_total_credit($user_id)
    {

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        if ($isAdmin) {
            $total_credits = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $table WHERE type = %s AND  status != %s",
                'credit',
                'expired'
            ));
        } else {
            $total_credits = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $table WHERE type = %s AND userid = %d AND status != %s",
                'credit',
                $user_id,
                'expired'
            ));
        }




        return $total_credits ? $total_credits : 0;
    }



    function count_total_credit_used($user_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";
        $table_requests = $wpdb->prefix . 'cstore_api_requests';



        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        if ($isAdmin) {
            $total_credits = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $table WHERE  type = %s",

                'debit'
            ));

            $total_credits_request = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table_requests ",

            ));
        } else {
            $total_credits = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM $table WHERE userid = %d AND type = %s",
                $user_id,
                'debit'
            ));

            $total_credits_request = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(id) FROM $table_requests WHERE userid = %d",
                $user_id,
            ));
        }










        $response['cron'] = $total_credits ? $total_credits : 0;
        $response['api'] = $total_credits_request ? $total_credits_request : 0;



        return ($response);
    }

    function total_api_validation($user_id)
    {



        $response = [];

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_api_requests';

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


$EmailValidationStats = new EmailValidationStats();
