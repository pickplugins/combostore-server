<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access

class ComboStoreSMS
{

    public function __construct() {}


    function send_bulksmsbd_sms($prams)
    {

        $phone     = isset($prams['phone']) ? sanitize_text_field($prams['phone']) : null;
        $message     = isset($prams['message']) ? sanitize_text_field($prams['message']) : null;
        $template_id     = isset($prams['template']) ? sanitize_text_field($prams['template']) : null;

        $template = $this->get_sms_template($template_id);

        $api_key  = 'YOUR_API_KEY';
        $senderid = '8809648908147';

        $url = add_query_arg(
            array(
                'api_key' => $api_key,
                'type'    => 'text',
                'number'  => $phone,
                'senderid' => $senderid,
                'message' => $message,
            ),
            'https://bulksmsbd.net/api/smsapi'
        );

        $response = wp_remote_get($url, array(
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message(),
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body        = wp_remote_retrieve_body($response);

        return array(
            'success'      => $status_code >= 200 && $status_code < 300,
            'status_code'  => $status_code,
            'response'     => $body,
        );
    }




    public function get_sms_template($id)
    {

        $settings = get_option('combo_store_settings');


        $apiKeys = $settings['apiKeys'] ?? [];
        $smsTemplates = $apiKeys['smsTemplates'] ?? [];

        $template = isset($smsTemplates[$id]) ? $smsTemplates[$id] : null;

        $message = '';


        return ['template' => $template, 'message' => $message];
    }












    public function email_templates_parameters()
    {

        $parameters['user_registered'] = array(
            '{site_name}' => __('Website title', 'combostore-server'),
            '{site_description}' => __('Website tagline', 'combostore-server'),
            '{site_url}' => __('Website URL', 'combostore-server'),
            '{site_logo_url}' => __('Website logo URL', 'combostore-server'),
            '{user_name}' => __('Username', 'combostore-server'),
            '{user_display_name}' => __('User display name', 'combostore-server'),
            '{first_name}' => __('User first name', 'combostore-server'),
            '{last_name}' => __('User last name', 'combostore-server'),
            '{user_avatar}' => __('User avatar', 'combostore-server'),
            '{user_email}' => __('User email address', 'combostore-server'),
            '{ac_activaton_url}' => __('Account activation URL', 'combostore-server'),
        );




        $parameters = apply_filters('combostore_email_templates_parameters', $parameters);

        return $parameters;
    }
}
