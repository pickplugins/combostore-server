<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access

class ComboStoreMails
{

    public function __construct() {}

    public function send_email($email_data)
    {

        $email_data = apply_filters('combostore_email_data', $email_data);


        $email_to = isset($email_data['email_to']) ? $email_data['email_to'] : '';
        $email_bcc = isset($email_data['email_bcc']) ? $email_data['email_bcc'] : '';

        $email_from = !empty($email_data['email_from']) ? $email_data['email_from'] : get_option('admin_email');
        $email_from_name = !empty($email_data['email_from_name']) ? $email_data['email_from_name'] : get_bloginfo('name');

        $reply_to = !empty($email_data['reply_to']) ? $email_data['reply_to'] : get_option('admin_email');
        $reply_to_name = !empty($email_data['reply_to_name']) ? $email_data['reply_to_name'] : get_bloginfo('name');

        // $subject = isset($email_data['subject']) ? $email_data['subject'] : '';
        $subject = isset($email_data['subject']) ? wp_specialchars_decode($email_data['subject'], ENT_QUOTES) : '';
        $email_body = isset($email_data['html']) ? wp_specialchars_decode($email_data['html'], ENT_QUOTES) : '';
        $attachments = isset($email_data['attachments']) ? $email_data['attachments'] : '';




        //$headers = [];
        // $headers['From'] = $email_from_name.' <'.$email_from.'>';

        // if (!empty($reply_to)) {
        //     $headers['Reply-To:'] = $reply_to_name.' <'.$reply_to.'>';
        // }

        // $headers['MIME-Version'] ='1.0';
        // $headers['Content-Type'] = 'text/html; charset=UTF-8';

        // if (!empty($email_bcc)) {
        //     $headers['Bcc'] = $email_bcc.' <'.$email_bcc.'>';
        // }



        $headers = array();
        $headers[] = "From: " . $email_from_name . " <" . $email_from . ">";

        if (!empty($reply_to)) {
            $headers[] = "Reply-To: " . $reply_to_name . " <" . $reply_to . ">";
        }

        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        if (!empty($email_bcc)) {
            $headers[] = "Bcc: " . $email_bcc;
        }




        $headers = apply_filters('combostore_mail_headers', $headers);

        $status = wp_mail($email_to, $subject, $email_body, $headers, $attachments);



        return $status;
    }




    public function email_templates_data()
    {

        $templates_data_html = array();

        include combo_store_plugin_dir . 'templates/emails/user_registered.php';


        $templates_data = array(
            'user_registered' => array(
                'name' => __('New User Registration', 'combo-store-server'),
                'description' => __('Notification email for admin when a new user is registered.', 'combo-store-server'),
                'subject' => __('New user submitted - {site_url}', 'combo-store-server'),
                'html' => $templates_data_html['user_registered'],
                'email_to' => get_option('admin_email'),
                'email_from' => get_option('admin_email'),
                'email_from_name' => get_bloginfo('name'),
                'enable' => 'yes',
            ),


        );


        return $templates_data;
    }



    public function email_templates_parameters()
    {

        $parameters['user_registered'] = array(
            '{site_name}' => __('Website title', 'combo-store-server'),
            '{site_description}' => __('Website tagline', 'combo-store-server'),
            '{site_url}' => __('Website URL', 'combo-store-server'),
            '{site_logo_url}' => __('Website logo URL', 'combo-store-server'),
            '{user_name}' => __('Username', 'combo-store-server'),
            '{user_display_name}' => __('User display name', 'combo-store-server'),
            '{first_name}' => __('User first name', 'combo-store-server'),
            '{last_name}' => __('User last name', 'combo-store-server'),
            '{user_avatar}' => __('User avatar', 'combo-store-server'),
            '{user_email}' => __('User email address', 'combo-store-server'),
            '{ac_activaton_url}' => __('Account activation URL', 'combo-store-server'),
        );




        $parameters = apply_filters('combostore_email_templates_parameters', $parameters);

        return $parameters;
    }
}
