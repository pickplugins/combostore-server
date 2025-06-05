<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class EmailValidationRegister
{
    public $email = "";
    public $password = "";


    public function __construct() {}

    function create_user($email, $password)
    {

        $this->email = $email;
        $this->password = $password;
        $response = [];

        $username = $this->generate_username_from_email($email);
        $random_password = !empty($password) ? $password : $this->generate_password();

        $credentials['email'] = $email;
        $credentials['password'] = $random_password;
        $credentials['username'] = $username;


        $user_login = isset($credentials['user_login']) ? $credentials['user_login'] : '';
        $user = get_user_by('login', $user_login);
        $user_id = isset($user->ID) ? $user->ID : '';
        $user_id = wp_create_user($credentials['username'], $credentials['password'], $credentials['email']);



        if (!is_wp_error($user_id)) {
            $response['error'] = false;
            $response['messages']['success'] = "Registration Success";

            return wp_json_encode($response);
        } else {



            $response['error'] = true;
            $response['messages'] = $user_id->errors;

            return wp_json_encode($response);
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
    private function generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
    {

        $length = empty($length) ? 12 : $length;
        $special_chars = empty($special_chars) ? true : (bool)$special_chars;
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
        $password = $this->get_password();

        $user = get_user_by('email', $email);

        $user_id = isset($user->ID) ? $user->ID :  $this->create_user($email, $password);

        return $user_id;
    }
    function get_email()
    {

        return $this->email;
    }
    function get_password()
    {

        return $this->password;
    }


    function get_apikey_data($apikey)
    {
        $id = '';
        $response = [];

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
