<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use Browser;
use geoPlugin;

if (!defined('ABSPATH')) exit;  // if direct access

// Load 3rd party classes that don't have namespaces
require_once(__DIR__ . '/Browser.php');
require_once(__DIR__ . '/geoPlugin.php');


class ComboStoreActivitiy
{
    public $object_name = "";


    public function __construct() {}

    function create($params)
    {


        $userid         = isset($params['userid']) ? intval($params['userid']) : "";
        $event  = isset($params['event']) ? sanitize_text_field($params['event']) : '';
        $value  = isset($params['value']) ? ($params['value']) : null;
        $source  = isset($params['source']) ? sanitize_text_field($params['source']) : '';
        $device  = isset($params['device']) ? sanitize_text_field($params['device']) : '';
        // $browser  = isset($params['browser']) ? sanitize_text_field($params['browser']) : '';
        // $platform  = isset($params['platform']) ? sanitize_text_field($params['platform']) : '';
        // $ip  = isset($params['ip']) ? sanitize_text_field($params['ip']) : '';
        // $city  = isset($params['city']) ? sanitize_text_field($params['city']) : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_activities';

        $Browser = new \Browser();
        $userAgent = $Browser->getUserAgent();
        $platform = $Browser->getPlatform();
        $browser = $Browser->getBrowser();






        // geo data
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $geoplugin = new \geoPlugin();
        $geoplugin->locate();
        $city = isset($geoplugin->city) ? $geoplugin->city : '';
        $region = isset($geoplugin->region) ? $geoplugin->region : '';
        $countryName = isset($geoplugin->countryCode) ? $geoplugin->countryCode : '';


        $response = [];


        $data = array(
            'userid'         => $userid,
            'event'    => $event,
            'source' => $source,
            'value' => wp_json_encode($value),
            'device' => $userAgent,
            'browser' => $browser,
            'platform' => $platform,
            'ip' => $ip,
            'city' => $city,
            'datetime'      => current_time('mysql')

        );



        $format = array(
            '%d',   // userid
            '%s',   // event
            '%s',   // source
            '%s',   // value
            '%s',   // device
            '%s',   // browser
            '%s',   // platform
            '%s',   // ip
            '%s',   // city
            '%s',   // datetime

        );

        $wpdb->insert($table, $data, $format);
        $id = $wpdb->insert_id; // Get inserted order_id

error_log($id);

        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        // $ComboStoreObjectMeta->update_meta('orders', $id, "delivery_location", $delivery_location);


        // $userData = get_user_by("ID", $user_id);

        // $action_prams = array_merge($data, ["order_id" => $id, "email" => $userData->user_email, "name" => $userData->display_name]);


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {


            $response['success'] = true;
            $response['id'] = $id;

            //do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }



    function delete($prams)
    {
        $id = isset($prams['id']) ? $prams['id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_activities';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id,), array('%d'));


        $response['status'] = $status;

        return $response;
    }




    function get($prams)
    {
        $id = isset($prams['id']) ? $prams['id'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_activities';

        $response = [];

        if (!$id) {
            $response['errors'] = "No item found";
        }

        $meta_data    = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%s",  $id));


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
