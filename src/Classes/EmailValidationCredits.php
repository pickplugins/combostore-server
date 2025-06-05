<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationCredits
{
    public $email = "";
    public $user_id = "";


    public function __construct() {}

    function has_credit($user_id)
    {


        $count_total_credit = (int)$this->count_total_credit($user_id);
        $count_total_credit_used = $this->count_total_credit_used($user_id);

        $cron_credit_used = isset($count_total_credit_used['cron']) ? (int)$count_total_credit_used['cron'] : 0;
        $api_credit_used = isset($count_total_credit_used['api']) ? (int)$count_total_credit_used['api'] : 0;

        $total_used = $cron_credit_used + $api_credit_used;

        $has_credit = $count_total_credit - $total_used;



        return ($has_credit > 0) ? true : false;
    }


    function add_credit($user_id, $amount, $credit_type, $source, $notes)
    {
        $type = 'credit';

        $this->user_id = $user_id;

        $response = [];

        if (!$user_id) {
            $response['errors']["create_user_failed"] = "createing user failed";
        }



        $datetime = $this->get_datetime();
        $status = '';

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
			( userid, type, credit_type, source, amount, status, notes, datetime )
			VALUES	( %d,  %s,%s, %s, %s, %s,%s, %s)",
            array($user_id, $type, $credit_type, $source, $amount, $status, $notes, $datetime)
        ));


        $response['status'] = '';

        $this->count_total_credit($user_id);

        return $response;
    }


    function add_daily_credit($user_id, $amount = 50, $credit_type = 'daily', $source = 'cron', $notes = '')
    {

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";



        $today_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE DATE(datetime) = CURDATE() AND credit_type = %s AND userid = %s",
                'daily',
                $user_id
            )
        );



        if ($today_count > 0) {
        } else {


            $this->add_credit($user_id, $amount, $credit_type, $source, $notes);
        }
    }

    function debit_credit($user_id,  $amount, $credit_type, $source, $notes)
    {



        $type = 'debit';
        $this->user_id = $user_id;

        $response = [];

        if (!$user_id) {
            $response['errors']["create_user_failed"] = "createing user failed";
        }



        $datetime = $this->get_datetime();
        $status = '';

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
			( userid, type, credit_type, source, amount, status, notes, datetime )
			VALUES	( %d,  %s,%s, %s, %s, %s,%s, %s)",
            array($user_id, $type, $credit_type, $source, $amount, $status, $notes, $datetime)
        ));


        $response['status'] = '';

        $this->count_total_credit($user_id);

        return $response;
    }






    function delete_credit($id)
    {


        $response = [];
        //$userid = $this->get_user_id($email);

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting API key failed";
        }

        //$status = 'active'; //active, inactive
        //$datetime = $this->get_datetime();
        // $apikey = $this->generate_password(40);



        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";



        $status = $wpdb->delete($table, array('id' => $id), array('%d'));


        $response['status'] = $status;

        return $response;
    }




    function count_total_credit($user_id)
    {


        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $total_credits = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = %s AND userid = %d AND status != %s",
            'credit',
            $user_id,
            'expired'
        ));



        //$id = isset($meta_data->id) ? $meta_data->id : '';
        return $total_credits ? $total_credits : 0;
    }
    function count_daily_credit($user_id, $date = null)
    {


        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $total_credits = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = %s AND userid = %d AND source = %s AND status != %s",
            'credit',
            $user_id,
            'daily',
            'expired'
        ));



        //$id = isset($meta_data->id) ? $meta_data->id : '';
        return $total_credits ? $total_credits : 0;
    }





    function count_total_credit_used($user_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $table_requests = $wpdb->prefix . 'cstore_api_requests';

        $total_debits = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = %s AND userid = %d",
            'debit',
            $user_id
        ));




        $total_credits_request = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(id) FROM $table_requests WHERE userid = %d",
            $user_id,
        ));

        $response['cron'] = $total_debits ? $total_debits : 0;
        $response['api'] = $total_credits_request ? $total_credits_request : 0;


        return ($response);
    }
    function total_credit_used_task($user_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_credits";

        $total_debits = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $table WHERE type = %s AND userid = %d",
            'debit',
            $user_id
        ));

        $total_debits = $total_debits ? $total_debits : 0;


        return $total_debits;
    }



    function total_credit_used_api($user_id)
    {

        $response = [];

        global $wpdb;

        $table_requests = $wpdb->prefix . 'cstore_api_requests';

        $total_credits = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(id) FROM $table_requests WHERE userid = %d",
            $user_id,
        ));

        $total_credits = $total_credits ? $total_credits : 0;


        return $total_credits;
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
