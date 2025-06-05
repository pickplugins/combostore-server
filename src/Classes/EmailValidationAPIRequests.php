<?php

namespace EmailValidation\Classes;



if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationAPIRequests
{

    private $apikey = '';
    private $email = '';

    public function __construct() {}

    function add_request($apikey, $email, $results)
    {

        $this->apikey = $apikey;
        $this->email = $email;

        $apikeyid = $this->get_apikey_id();
        $apikeyData = $this->get_apikey_data();


        $id = isset($apikeyData['id']) ? $apikeyData['id'] : '';
        $userid = isset($apikeyData['userid']) ? $apikeyData['userid'] : '';
        $status = isset($apikeyData['status']) ? $apikeyData['status'] : '';




        $prams = '';
        $datetime = $this->get_datetime();




        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_requests";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
			( id,userid, apikeyid,  email, result, datetime )
			VALUES	( %d, %d, %s, %s, %s, %s)",
            array('', $userid, $apikeyid, $email, wp_json_encode($results), $datetime)
        ));
    }

    function get_apikey_id()
    {

        $apikey = $this->apikey;


        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_keys";


        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE apikey = %s", $apikey));



        $id = isset($meta_data->id) ? $meta_data->id : '';
        return $id;
    }






    function get_apikey_data()
    {
        $apikey = $this->apikey;

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_keys";


        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE apikey = %s", $apikey));



        $id = isset($meta_data->id) ? $meta_data->id : '';
        $userid = isset($meta_data->userid) ? $meta_data->userid : '';
        $title = isset($meta_data->title) ? $meta_data->title : '';
        $status = isset($meta_data->status) ? $meta_data->status : '';


        $response['id'] = $id;
        $response['userid'] = $userid;
        $response['title'] = $title;
        $response['status'] = $status;

        return $response;
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
