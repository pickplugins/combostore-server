<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreRegister;
use ComboStore\Classes\ComboStoreDeliveries;
use DateTime;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreShipping
{
    public $object_name = "";


    public function __construct() {}

    function create_shipping_zone($params)
    {


        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description          = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $region        = isset($params['region']) ? sanitize_text_field($params['region']) : null;
        $status    = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $countries = isset($params['countries']) ? wp_json_encode($params['countries']) : null;
        $settings = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_zones';

        $response = [];

        $data = array(
            'name'         => $name,
            'description'  => $description,
            'region'        => $region,
            'status'        => $status,
            'countries' => $countries,
            'settings' => $settings,
            'priority' => $priority,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%s',   // name
            '%s',   // description
            '%s',   // region
            '%s',   // status
            '%s',   // countries
            '%s',   // settings
            '%d',   // priority
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $order_id;
        }






        return $response;
    }

    function create_shipping_zone_location($params)
    {


        $zone_id          = isset($params['zone_id']) ? sanitize_text_field($params['zone_id']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $value        = isset($params['value']) ? sanitize_text_field($params['value']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_orders';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'type'          => $type,
            'value'        => $value,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // type
            '%s',   // value
            '%s',   // status
            '%d',   // priority
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $order_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $order_id;
        }






        return $response;
    }

    function create_shipping_method($params)
    {




        $zone_id          = isset($params['zone_id']) ? intval($params['zone_id']) : '';
        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $amount          = isset($params['amount']) ? sanitize_text_field($params['amount']) : 0;
        $description        = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $states        = isset($params['states']) ? wp_json_encode($params['states']) : null;
        $settings        = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority        = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;
        $status        = isset($params['status']) ? sanitize_text_field($params['status']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_methods';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'name'          => $name,
            'type'          => $type,
            'amount'          => $amount,
            'description'        => $description,
            'states'        => $states,
            'settings'        => $settings,
            'priority'        => $priority,
            'status'        => $status,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // name
            '%s',   // type
            '%s',   // amount
            '%s',   // description
            '%s',   // states
            '%s',   // settings
            '%d',   // priority
            '%s',   // status
            '%s',   // created_at
            '%s'    // updated_at
        );

        $wpdb->insert($table, $data, $format);
        $zone_id = $wpdb->insert_id; // Get inserted order_id



        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {


            $response['success'] = true;
            $response['zone_id'] = $zone_id;
        }






        return $response;
    }













    function get_shipping_zone($order_id)
    {

        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_shipping_zones";
        $ComboStoreDeliveries = new ComboStoreDeliveries();

        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $countries = isset($orderRow['countries']) ? json_decode($orderRow['countries'], true) : [];
        $settings = isset($orderRow['settings']) ? json_decode($orderRow['settings'], true) : [];



        $orderRow['countries'] = $countries;
        $orderRow['settings'] = $settings;

        return $orderRow;
    }
    function get_shipping_method($id)
    {



        $response = [];

        global $wpdb;
        $table = $wpdb->prefix . "cstore_shipping_methods";

        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        $states = isset($orderRow['states']) ? json_decode($orderRow['states'], true) : [];
        $settings = isset($orderRow['settings']) ? json_decode($orderRow['settings'], true) : [];



        $orderRow['states'] = $states;
        $orderRow['settings'] = $settings;

        return $orderRow;
    }



    function get_shipping_zones($prams)
    {



        $responses = [];

        $user_role     = isset($prams['user_role']) ? ($prams['user_role']) : '';
        $user_id     = isset($prams['user_id']) ? ($prams['user_id']) : '';
        $object     = isset($prams['object']) ? sanitize_email($prams['object']) : '';
        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";
        $status     = isset($prams['status']) ? sanitize_text_field($prams['status']) : "";
        $payment_method     = isset($prams['payment_method']) ? sanitize_text_field($prams['payment_method']) : "";
        $payment_status     = isset($prams['payment_status']) ? sanitize_text_field($prams['payment_status']) : "";
        $product_id     = isset($prams['product_id']) ? sanitize_text_field($prams['product_id']) : "";
        $customer     = isset($prams['customer']) ? ($prams['customer']) : [];

        $customer_id = isset($customer['id']) ? $customer['id'] : "";


        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';





        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table_shipping_zones = $prefix . 'cstore_shipping_zones';

        $table_shipping_zones = $prefix . 'cstore_shipping_zones';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_shipping_zones WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        $where   = [];
        $params  = [];

        if (!empty($status)) {
            $where[]  = "status = %s";
            $params[] = $status;
        }

        if (!empty($payment_method)) {
            $where[]  = "payment_method = %s";
            $params[] = $payment_method;
        }

        if (!empty($payment_status)) {
            $where[]  = "payment_status = %s";
            $params[] = $payment_status;
        }

        if (!empty($customer_id)) {
            $where[]  = "userid = %s";
            $params[] = $customer_id;
        }

        if ($from_date && $to_date) {
            $where[]  = "created_at BETWEEN %s AND %s";
            $params[] = $from_date . " 00:00:00";
            $params[] = $to_date . " 23:59:59";
        }



        $where_sql = '';

        if (!empty($where)) {
            $where_sql = "WHERE " . implode(' AND ', $where);
        }

        $allowed_order = ['ASC', 'DESC'];
        $order = strtoupper($order);

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        $sql = "SELECT * FROM $table_shipping_zones
                    $where_sql
                    ORDER BY created_at $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;


        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);




        $count_sql = "
                SELECT COUNT(*)
                FROM $table_shipping_zones
                $where_sql
                ORDER BY created_at $order
            ";
        $filter_params = [];
        $filter_params[] = $status;




        $count_query = !empty($filter_params)
            ? $wpdb->prepare($count_sql, $filter_params)
            : $count_sql;



        $total = (int) $wpdb->get_var($count_query);




        //$total = count($entries);





        $big = 999999999; // need an unlikely integer





        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }



    function get_shipping_methods($prams)
    {



        $responses = [];

        $zone_id     = isset($prams['zone_id']) ? ($prams['zone_id']) : '';
        $object     = isset($prams['object']) ? sanitize_email($prams['object']) : '';
        $page     = isset($prams['paged']) ? absint($prams['paged']) : 1;
        $per_page     = isset($prams['per_page']) ? absint($prams['per_page']) : 10;
        $order     = isset($prams['order']) ? sanitize_text_field($prams['order']) : "DESC";
        $status     = isset($prams['status']) ? sanitize_text_field($prams['status']) : "";
        $payment_method     = isset($prams['payment_method']) ? sanitize_text_field($prams['payment_method']) : "";
        $payment_status     = isset($prams['payment_status']) ? sanitize_text_field($prams['payment_status']) : "";
        $product_id     = isset($prams['product_id']) ? sanitize_text_field($prams['product_id']) : "";
        $customer     = isset($prams['customer']) ? ($prams['customer']) : [];

        $customer_id = isset($customer['id']) ? $customer['id'] : "";


        $from_date = isset($prams['from_date']) ? sanitize_text_field($prams['from_date']) : '';
        $to_date   = isset($prams['to_date']) ? sanitize_text_field($prams['to_date']) : '';





        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_shipping_zones = $prefix . 'cstore_shipping_methods';
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));


        $where   = [];
        $params  = [];



        if (!empty($zone_id)) {
            $where[]  = "zone_id = %d";
            $params[] = $zone_id;
        }
        if (!empty($status)) {
            $where[]  = "status = %s";
            $params[] = $status;
        }




        $where_sql = '';

        if (!empty($where)) {
            $where_sql = "WHERE " . implode(' AND ', $where);
        }

        $allowed_order = ['ASC', 'DESC'];
        $order = strtoupper($order);

        if (!in_array($order, $allowed_order)) {
            $order = 'DESC';
        }

        $sql = "SELECT * FROM $table_shipping_zones
                    $where_sql
                    ORDER BY created_at $order
                    LIMIT %d, %d";

        $params[] = (int) $offset;
        $params[] = (int) $per_page;


        $query = $wpdb->prepare($sql, $params);

        $entries = $wpdb->get_results($query);




        $count_sql = "
                SELECT COUNT(*)
                FROM $table_shipping_zones
                $where_sql
                ORDER BY created_at $order
            ";
        $filter_params = [];
        $filter_params[] = $status;




        $count_query = !empty($filter_params)
            ? $wpdb->prepare($count_sql, $filter_params)
            : $count_sql;



        $total = (int) $wpdb->get_var($count_query);




        //$total = count($entries);





        $big = 999999999; // need an unlikely integer





        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $responses['total'] = (int)$total;

        $responses['max_pages'] = (int)$max_pages;
        $responses['posts'] = $entries;


        return $responses;
    }





    function get_shipping_zones_by_countryX($params)
    {
        $responses = [];

        $country = isset($params['country'])            ? sanitize_text_field($params['country'])            : '';

        global $wpdb;

        $table_shipping_zones = $wpdb->prefix . 'cstore_shipping_zones';

        $where  = [];
        $query_params = [];

        if (!empty($country)) {
            $where[] = "JSON_CONTAINS(settings, %s, '$.countries')";
            $query_params[] = wp_json_encode((string) $country);
        }

        $where_sql = '';

        if (!empty($where)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where);
        }

        $limit  = isset($params['limit']) ? absint($params['limit']) : 25;
        $offset = isset($params['offset']) ? absint($params['offset']) : 0;

        $sql = "SELECT *
            FROM $table_shipping_zones
            $where_sql
            LIMIT %d, %d";

        $query_params[] = $offset;
        $query_params[] = $limit;

        $query = $wpdb->prepare($sql, $query_params);


        $entries = $wpdb->get_results($query);
        $zone_ids = array_map(function ($zone) {
            return (int) $zone->id;
        }, $entries);

        $responses['zone_ids'] = $zone_ids;

        return $responses;
    }












    function get_all_methods_by_zone($prams)
    {


        $responses = [];

        $zone_ids     = isset($prams['zone_ids']) ? stripslashes_deep($prams['zone_ids']) : [];



        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_shipping_methods = $prefix . 'cstore_shipping_methods';


        $where = [];
        $params = [];

        if (!empty($zone_ids)) {
            $placeholders = implode(',', array_fill(0, count($zone_ids), '%d'));

            $where[] = "zone_id IN ($placeholders)";
            $params = array_merge($params, $zone_ids);
        }

        if (!empty($state)) {
            $where[] = "JSON_CONTAINS(settings, %s, '$.states')";
            $params[] = wp_json_encode($state);
        }

        // if (!empty($city)) {
        //     $where[] = "JSON_CONTAINS(settings, %s, '$.cities')";
        //     $params[] = wp_json_encode($city);
        // }

        $sql = "
            SELECT *
            FROM {$wpdb->prefix}cstore_shipping_methods
            WHERE " . implode(' AND ', $where) . "
            ORDER BY priority ASC
        ";

        $query = $wpdb->prepare($sql, ...$params);

        $methods = $wpdb->get_results($query);







        return $methods;
    }


    function update_shipping_zone($params)
    {

        $responses = [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_zones';

        $order_id      = isset($params['id']) ? intval($params['id']) : 0;

        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $description          = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $region        = isset($params['region']) ? sanitize_text_field($params['region']) : null;
        $status    = isset($params['status']) ? sanitize_text_field($params['status']) : '';
        $countries = isset($params['countries']) ? wp_json_encode($params['countries']) : null;
        $settings = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;


        // ✅ Data to update
        $data = array(
            'name'         => $name,
            'description'  => $description,
            'region'        => $region,
            'status'        => $status,
            'countries' => $countries,
            'settings' => $settings,
            'priority' => $priority,
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        // ✅ Format mapping
        $format = array(
            '%s',   // name
            '%s',   // description
            '%s',   // region
            '%s',   // status
            '%s',   // countries
            '%s',   // settings
            '%d',   // priority
            '%s'    // updated_at
        );

        // ✅ WHERE clause
        $where = array('id' => $order_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);



        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }
        return $responses;
    }
    function update_shipping_method($params)
    {

        $responses = [];



        $id          = isset($params['id']) ? intval($params['id']) : '';
        $zone_id          = isset($params['zone_id']) ? intval($params['zone_id']) : '';
        $name          = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $type          = isset($params['type']) ? sanitize_text_field($params['type']) : '';
        $amount          = isset($params['amount']) ? sanitize_text_field($params['amount']) : 0;
        $description        = isset($params['description']) ? sanitize_text_field($params['description']) : '';
        $states        = isset($params['states']) ? wp_json_encode($params['states']) : null;
        $settings        = isset($params['settings']) ? wp_json_encode($params['settings']) : null;
        $priority        = isset($params['priority']) ? sanitize_text_field($params['priority']) : 1;
        $status        = isset($params['status']) ? sanitize_text_field($params['status']) : '';



        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_shipping_methods';

        $response = [];

        $data = array(
            'zone_id'         => $zone_id,
            'name'          => $name,
            'type'          => $type,
            'amount'          => $amount,
            'description'        => $description,
            'states'        => $states,
            'settings'        => $settings,
            'priority'        => $priority,
            'status'        => $status,
            'created_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s')
        );

        $format = array(
            '%d',   // zone_id
            '%s',   // name
            '%s',   // type
            '%s',   // amount
            '%s',   // description
            '%s',   // states
            '%s',   // settings
            '%d',   // priority
            '%s',   // status
            '%s',   // created_at
            '%s'    // updated_at
        );


        // ✅ WHERE clause
        $where = array('id' => $id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);



        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }
        return $responses;
    }


    /**
     * Find available shipping methods.
     *
     * Shipping flow:
     * Country -> Shipping Zone -> State -> Shipping Methods
     *
     * @param array $params {
     *     @type string $country Country code, e.g. BD.
     *     @type string $state   State code, e.g. BD-55.
     * }
     *
     * @return array
     */
    function find_shipping_methods($params)
    {
        global $wpdb;

        $response = [
            'methods' => [],
        ];

        $country = isset($params['country'])            ? sanitize_text_field($params['country'])            : '';

        $state = isset($params['state'])            ? sanitize_text_field($params['state'])            : '';

        // Backward compatibility if checkout currently sends city.
        if (empty($state) && isset($params['city'])) {
            $state = sanitize_text_field($params['city']);
        }



        if (empty($country)) {
            return $response;
        }


        /*
     * ---------------------------------------------------------
     * 1. Find shipping zones for country
     * ---------------------------------------------------------
     */

        $zones = $this->get_shipping_zones_by_country([
            'country' => $country,
        ]);

        $zone_ids = !empty($zones['zone_ids'])            ? array_map('intval', $zones['zone_ids'])            : [];




        if (empty($zone_ids)) {
            return $response;
        }



        /*
     * ---------------------------------------------------------
     * 2. Find shipping methods belonging to those zones
     * ---------------------------------------------------------
     */
        $table_shipping_methods = $wpdb->prefix . 'cstore_shipping_methods';

        $placeholders = implode(
            ',',
            array_fill(0, count($zone_ids), '%d')
        );

        $sql = "
        SELECT *
        FROM {$table_shipping_methods}
        WHERE zone_id IN ($placeholders)
          AND status = 1
        ORDER BY priority ASC, id ASC
    ";

        $query = $wpdb->prepare(
            $sql,
            ...$zone_ids
        );

        $methods = $wpdb->get_results($query);

        error_log("methods");
        error_log(wp_json_encode($methods));


        if (empty($methods)) {
            return $response;
        }

        /*
     * ---------------------------------------------------------
     * 3. Filter methods by state
     *
     * states column example:
     * ["BD-55"]
     *
     * Empty states = available for entire zone.
     * ---------------------------------------------------------
     */
        $available_methods = [];

        foreach ($methods as $method) {

            $method_states = [];

            if (!empty($method->states)) {
                $decoded_states = json_decode(
                    $method->states,
                    true
                );

                if (is_array($decoded_states)) {
                    $method_states = $decoded_states;
                }
            }

            /*
         * No state restriction.
         *
         * Example:
         * states = []
         *
         * This method is available throughout
         * the shipping zone.
         */
            if (empty($method_states)) {
                $available_methods[] = $method;
                continue;
            }

            /*
         * State-specific method.
         */
            if (!empty($state) && in_array($state, $method_states, true)) {
                $available_methods[] = $method;
            }
        }

        /*
     * ---------------------------------------------------------
     * 4. Return methods
     * ---------------------------------------------------------
     */

        error_log("available_methods");
        error_log(wp_json_encode($available_methods));



        $response['methods'] = $available_methods;

        return $response;
    }


    /**
     * Get shipping zones by country.
     *
     * Shipping zone countries are stored in the `countries` column.
     *
     * Example:
     * countries = ["BD"]
     *
     * @param array $params {
     *     @type string $country Country code, e.g. BD.
     *     @type int    $limit   Number of zones to return.
     *     @type int    $offset  Offset.
     * }
     *
     * @return array
     */
    function get_shipping_zones_by_country($params)
    {
        global $wpdb;

        $response = [
            'zone_ids' => [],
            'zones'    => [],
        ];

        $country = isset($params['country'])
            ? sanitize_text_field($params['country'])
            : '';

        if (empty($country)) {
            return $response;
        }

        $table_shipping_zones = $wpdb->prefix . 'cstore_shipping_zones';

        $limit = isset($params['limit'])
            ? absint($params['limit'])
            : 25;

        $offset = isset($params['offset'])
            ? absint($params['offset'])
            : 0;

        /*
     * Find active zones where the country
     * exists in the countries JSON array.
     *
     * Example:
     * countries = ["BD", "IN"]
     */
        $sql = "
        SELECT *
        FROM {$table_shipping_zones}
        WHERE status = 1
          AND JSON_CONTAINS(countries, %s)
        ORDER BY priority ASC, id ASC
        LIMIT %d, %d
    ";

        $query = $wpdb->prepare(
            $sql,
            wp_json_encode($country),
            $offset,
            $limit
        );

        $zones = $wpdb->get_results($query);

        if (empty($zones)) {
            return $response;
        }

        /*
     * Zone IDs
     */
        $response['zone_ids'] = array_map(
            function ($zone) {
                return (int) $zone->id;
            },
            $zones
        );

        /*
     * Return zones as well.
     */
        $response['zones'] = $zones;

        return $response;
    }
}
