<?php

namespace ComboStore\Classes;

use WP_User;
use WP_Error;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use ComboStore\Classes\ComboStoreSMS;


if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreRegister
{
    public $email = "";
    public $password = "";
    public $role = "customer";


    public function __construct() {}

    function create_user($args)
    {


        $password = !empty($args['password']) ? $args['password'] : $this->generate_password();
        $role = isset($args['role']) ? $args['role'] : '';
        $mobile = isset($args['mobile']) ? $args['mobile'] : '';
        $email = isset($args['email']) ? $args['email'] : '';
        $registerBy = isset($args['registerBy']) ? $args['registerBy'] : 'email';
        $name = isset($args['name']) ? $args['name'] : '';



        if ($registerBy == 'mobile') {

            $blogusers = get_users('meta_value=' . $mobile . '&meta_key=mobile');

            if (count($blogusers) > 0) {
                $response['error'] = true;
                $response['messages'] = ['Mobile number already exists.'];
                return wp_json_encode($response);
            }


            if (empty($mobile)) {
                $response['error'] = true;
                $response['messages'] = ['Mobile number is required'];
                return wp_json_encode($response);
            }
            if (empty($password)) {
                $response['error'] = true;
                $response['messages'] = ['Password is required'];
                return wp_json_encode($response);
            }


            $email =  $mobile . '@mo.lo';
        }




        $this->email = $email;
        $this->password = $password;
        $this->role = $role;



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

        if (!empty($mobile)) {
            update_user_meta($user_id, "phone", $mobile);
        }

        $user = new WP_User($user_id);



        $userdata = array(
            'ID'    => $user_id,
            'display_name'  => $name,
        );

        wp_update_user($userdata);





        $user->set_role($role);


        if (!is_wp_error($user_id)) {
            $response['error'] = false;
            $response['user_id'] = $user_id;
            $response['messages'] = ["Registration Successful, please login."];

            return wp_json_encode($response);
        } else {

            $value = array_values($user_id->errors)[0];
            $message = $value[0];




            $response['error'] = true;
            $response['user_id'] = $user_id;
            $response['messages'] = $value;

            return wp_json_encode($response);
        }
    }






    /**
     * Find WordPress user ID by phone user meta.
     *
     * @param string $phone Phone number.
     * @return int|WP_Error User ID or error.
     */
    function get_user_id_by_phone($phone)
    {

        $phone = trim((string) $phone);

        if (empty($phone)) {
            return new WP_Error(
                'invalid_phone',
                'Phone number is required.',
                ['status' => 400]
            );
        }

        $users = get_users([
            'meta_key'   => 'phone',
            'meta_value' => $phone,
            'number'     => 2,
            'fields'     => 'ID',
        ]);

        if (empty($users)) {
            return new WP_Error(
                'user_not_found',
                'No user found with this phone number.',
                ['status' => 404]
            );
        }

        if (count($users) > 1) {
            return new WP_Error(
                'duplicate_phone',
                'Multiple users have this phone number.',
                ['status' => 409]
            );
        }

        return (int) $users[0];
    }


    /**
     * Generate a 6-digit OTP and save it in user meta.
     *
     * @param string $phone         Phone number.
     * @param int    $expiry_minutes OTP validity in minutes.
     * @return string|WP_Error Generated OTP or error.
     */
    function generate_otp($phone, $expiry_minutes = 5)
    {

        $user_id = $this->get_user_id_by_phone($phone);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $expiry_minutes = max(1, absint($expiry_minutes));

        // Generate a cryptographically secure 6-digit OTP.
        $otp = (string) random_int(100000, 999999);

        // Store the hashed OTP.
        update_user_meta(
            $user_id,
            '_combostore_otp',
            wp_hash_password($otp)
        );

        // Store expiry as a Unix timestamp.
        update_user_meta(
            $user_id,
            '_combostore_otp_expire',
            time() + ($expiry_minutes * MINUTE_IN_SECONDS)
        );

        return $otp;
    }


    /**
     * Verify OTP using the user's phone number.
     *
     * OTP is invalidated after successful verification or expiry.
     *
     * @param string $phone Phone number.
     * @param string $otp   Submitted OTP.
     * @return true|WP_Error
     */
    function verify_otp($phone, $otp)
    {

        $user_id = $this->get_user_id_by_phone($phone);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        $otp = trim((string) $otp);

        if (!preg_match('/^\d{6}$/', $otp)) {
            return new WP_Error(
                'invalid_otp',
                'Please enter a valid 6-digit OTP.',
                ['status' => 400]
            );
        }

        $stored_hash = get_user_meta(
            $user_id,
            '_combostore_otp',
            true
        );

        $expires_at = (int) get_user_meta(
            $user_id,
            '_combostore_otp_expire',
            true
        );

        if (empty($stored_hash) || empty($expires_at)) {
            return new WP_Error(
                'otp_not_found',
                'OTP not found. Please request a new OTP.',
                ['status' => 400]
            );
        }

        if (time() >= $expires_at) {

            delete_user_meta($user_id, '_combostore_otp');
            delete_user_meta($user_id, '_combostore_otp_expire');

            return new WP_Error(
                'otp_expired',
                'OTP has expired. Please request a new OTP.',
                ['status' => 400]
            );
        }

        if (!wp_check_password($otp, $stored_hash, $user_id)) {
            return new WP_Error(
                'otp_mismatch',
                'Incorrect OTP.',
                ['status' => 400]
            );
        }

        // OTP is single-use.
        delete_user_meta($user_id, '_combostore_otp');
        delete_user_meta($user_id, '_combostore_otp_expire');

        return true;
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
        $role = $this->get_role();

        $user = get_user_by('email', $email);

        $user_id = isset($user->ID) ? $user->ID :  $this->create_user($email, $password, $role);

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
    function get_role()
    {

        return $this->role;
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
