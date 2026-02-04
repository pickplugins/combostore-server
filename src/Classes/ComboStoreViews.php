<?php

namespace ComboStore\Classes;

use Error;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreViews
{
    public $object_name = "";


    public function __construct() {}




    function is_time_update($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . 'cstore_views_count';

        // Check if there's a row older than 6 hours
        $allow_update = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table
         WHERE object_id = %s
         AND datetime <= DATE_SUB(NOW(), INTERVAL 6 HOUR)
         LIMIT 1",
                $object_id
            )
        );



        if ($allow_update > 0) {
            return true;
        } else {
            return false;
        }
    }


    function has_view($prams)
    {
        global $wpdb;
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $table = $wpdb->prefix . "cstore_views_count";
        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        if (!empty($object_id)) {
            $query .= " AND object_id = %d";
            $params[] = $object_id;
        }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));




        if ($love_count > 0) {
            return true;
        } else {
            return false;
        }
    }

    function get_object_view_count($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . 'cstore_views_count';

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count FROM $table WHERE object_id = %d",
                $object_id
            )
        );



        return $count;
    }


    function calculate_object_view($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_views";

        $query = "SELECT COUNT(*) FROM $table WHERE object_id = %d";
        $params = [$object_id];

        // if (!empty($object_id)) {
        //     $query .= " AND object_id = %d";
        //     $params[] = $object_id;
        // }


        $love_count = $wpdb->get_var($wpdb->prepare($query, ...$params));

        return $love_count;
    }

    function update_object_total_view($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $table_views = $wpdb->prefix . "cstore_views";
        $table_views_count = $wpdb->prefix . "cstore_views_count";
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_views_count
         WHERE object_id = %s
         LIMIT 1",
                $object_id
            )
        );


        if (!$row) {

            $view_count = $this->calculate_object_view($prams);

            $data = array(
                'object_id'       => $object_id,
                'count'       => $view_count,
                'datetime'         => $this->get_datetime(),
            );

            $format = array(
                '%d',
                '%d',
                '%s'
            );

            $wpdb->insert($table_views_count, $data, $format);
        }


        if ($row) {
            // For example, increment count and update datetime
            $new_count = (int)$row->count + 1;
            $wpdb->update(
                $table_views_count,
                [
                    'count' => $new_count,
                    'datetime' => current_time('mysql') // WordPress-aware current datetime
                ],
                ['id' => $row->id] // update by primary key
            );
        }

        return true;
    }









    function add_view($prams)
    {


        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';
        $source = isset($prams['source']) ? $prams['source'] : '';

        $city = '';
        $country = '';
        $platform = '';
        $browser = '';
        $referer = '';
        $screensize = '';
        $ip = '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_views';

        $response = [];

        $has_prams = [];
        $has_prams['object_id'] = $object_id;
        $has_prams['source'] = $source;

        $has_view = $this->has_view($has_prams);


        if ($has_view) return;

        $data = array(
            'object_id'       => $object_id,
            'source'       => $source,
            'city'       => $city,
            'country'       => $country,
            'platform'       => $platform,
            'browser'       => $browser,
            'referer'       => $referer,
            'screensize'       => $screensize,
            'ip'       => $ip,
            'datetime'         => $this->get_datetime(),
        );

        $format = array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        );

        $wpdb->insert($table, $data, $format);


        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {
            $response['success'] = true;
        }









        return $response;
    }




    function delete_view($prams)
    {

        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_views';


        $response = [];

        if (!$object_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('object_id' => $object_id,), array('%d'));
        $response['status'] = $status;

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
