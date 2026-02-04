<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreAPIKey
{
    public $email = "";
    public $user_id = "";


    public function __construct() {}

    function ceate_api_key($title, $user_id)
    {

        $this->user_id = $user_id;

        $response = [];
        $email = $this->get_user_email($user_id);

        if (!$user_id) {
            $response['errors']["create_user_failed"] = "createing user failed";
        }

        $status = 'active'; //active, inactive
        $datetime = $this->get_datetime();
        $apikey = $this->generate_password(40);



        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_keys";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
			( id, title, apikey, userid, datetime, status )
			VALUES	( %d,  %s,%s, %s,%s, %s)",
            array('', $title, $apikey, $user_id, $datetime, $status)
        ));


        $response['apikey'] = $apikey;
        $response['status'] = $status;
        $response['userid'] = $user_id;

        return $response;
    }
    function delete_api_key($id)
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
        $table = $wpdb->prefix . "cstore_api_keys";



        $status = $wpdb->delete($table, array('id' => $id), array('%d'));


        $response['status'] = $status;

        return $response;
    }




    function get_apikey_id($apikey)
    {

        //$apikey = $this->apikey;


        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_keys";


        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE apikey = %s", $apikey));



        $id = isset($meta_data->id) ? $meta_data->id : '';
        return $id;
    }






    function get_apikey_data($apikey)
    {
        //$apikey = $this->apikey;

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_api_keys";



        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE apikey = %s", $apikey));


        if (empty($meta_data)) {
            $response['errors'] = true;

            return $response;
        }


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







    function create_user()
    {

        $email = $this->get_email();

        $username = $this->generate_username_from_email($email);
        $random_password = $this->generate_password();

        $credentials['email'] = $email;
        $credentials['password'] = $random_password;
        $credentials['username'] = $username;


        $user_login = isset($credentials['user_login']) ? $credentials['user_login'] : '';
        $user = get_user_by('login', $user_login);
        $user_id = isset($user->ID) ? $user->ID : '';
        $user_id = wp_create_user($credentials['username'], $credentials['password'], $credentials['email']);
        if ($user_id) {
            return $user_id;
        } else {
            return false;
        }
    }

    function regenerate_username($username)
    {
        if (username_exists($username)) {
            $x = 1;
            while (username_exists($username)) {
                $username = $username . $x;
                $x++;
            }
        }
        return $username;
    }
    private function generate_password($length = 12, $special_chars = false, $extra_special_chars = false)
    {

        $length = empty($length) ? 12 : $length;
        $special_chars = empty($special_chars) ? false : (bool)$special_chars;
        $extra_special_chars = empty($extra_special_chars) ? false : (bool)$extra_special_chars;

        $random_password = wp_generate_password($length, $special_chars, $extra_special_chars);

        return $random_password;
    }

    private function generate_username_from_email($email)
    {
        $get_email = $this->get_email();
        $email_arr = explode('@', $get_email);

        $username = isset($email_arr[0]) ? $email_arr[0] : '';
        $username = $this->regenerate_username($username);

        return $username;
    }


    function get_user_id()
    {
        $email = $this->get_email();

        $user = get_user_by('email', $email);

        $user_id = isset($user->ID) ? $user->ID :  $this->create_user();;

        return $user_id;
    }
    function get_user_email($user_id)
    {

        $user = get_user_by('id', $user_id);

        $user_email = isset($user->user_email) ? $user->user_email :  $this->create_user();;

        return $user_email;
    }






    function get_email()
    {
        $user_id = $this->user_id;

        $user = get_user_by('id', $user_id);

        $user_email = isset($user->user_email) ? $user->user_email :  $this->create_user();;
        return $this->email;
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
