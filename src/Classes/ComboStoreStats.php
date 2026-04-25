<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreStats
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";


    public function __construct() {}

    function get_top_object_from_orders($args = [])
    {



        error_log(wp_json_encode($args));
        $object = $args['object'] ?? null;




        if ($object == 'userid') {
            $results = $this->get_top_customers($args);
        } else if ($object == 'status') {
            $results = $this->get_top_status($args);
        } else if ($object == 'payment_method') {
            $results = $this->get_top_payment_methods($args);
        } else if ($object == 'payment_status') {
            $results = $this->get_top_status($args);
        }

        return $results;
    }














    function get_top_payment_methods($args)
    {

        $object = $args['object'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['limit'] ?? 10;

        global $wpdb;


        $table = $wpdb->prefix . 'cstore_orders';

        $query = "
        SELECT 
            payment_method,
            COUNT(id) AS total_orders,
            SUM(total_amount) AS total_revenue
        FROM {$table}
        WHERE 1=1
        AND payment_status = %s
    ";

        $params = ['paid'];

        if (!empty($start_date)) {
            $query .= " AND completed_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND completed_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $query .= "
        GROUP BY payment_method
        ORDER BY total_orders DESC
    ";

        $prepared = $wpdb->prepare($query, $params);

        $results = $wpdb->get_results($prepared);


        return $results;
    }
    function get_top_payment_status($args)
    {

        $object = $args['object'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['limit'] ?? 10;

        global $wpdb;


        $table = $wpdb->prefix . 'cstore_orders';

        $query = "
        SELECT 
            payment_status,
            COUNT(id) AS total_orders,
            SUM(total_amount) AS total_revenue
        FROM {$table}
        WHERE 1=1
        AND payment_status = %s
    ";

        $params = ['paid'];

        if (!empty($start_date)) {
            $query .= " AND completed_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND completed_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $query .= "
        GROUP BY payment_status
        ORDER BY total_orders DESC
    ";

        $prepared = $wpdb->prepare($query, $params);

        return $wpdb->get_results($prepared);
    }


    function get_top_status($args)
    {

        $object = $args['object'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['limit'] ?? 10;



        global $wpdb;


        $table = $wpdb->prefix . 'cstore_orders';

        $query = "
        SELECT 
            status,
            COUNT(id) AS total_orders,
            SUM(total_amount) AS total_amount
        FROM {$table}
        WHERE 1=1
    ";

        $params = [];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $query .= "
        GROUP BY status
        ORDER BY total_orders DESC
    ";

        $prepared = $wpdb->prepare($query, $params);

        return $wpdb->get_results($prepared);
    }



    function get_top_customers($args)
    {

        $object = $args['object'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['limit'] ?? 20;


        global $wpdb;


        $table = $wpdb->prefix . 'cstore_orders';

        $query = "
        SELECT 
            userid,
            billing_name,
            billing_email,
            COUNT(id) AS total_orders,
            SUM(total_amount) AS total_spent
        FROM {$table}
        WHERE 1=1
        AND status = %s
        AND payment_status = %s
    ";

        $params = ['completed', 'paid'];

        if (!empty($start_date)) {
            $query .= " AND completed_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND completed_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $query .= "
        GROUP BY billing_email
        ORDER BY total_spent DESC
        LIMIT %d
    ";

        $params[] = (int) $limit;

        $prepared = $wpdb->prepare($query, $params);

        return $wpdb->get_results($prepared);
    }








    function get_orders_range($request)
    {


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";
        $page = ($page == 0) ? 1 : $page;



        if ($range == '7days') {
            $days = 7;
        }
        if ($range == '15days') {
            $days = 15;
        }
        if ($range == '30days') {
            $days = 30;
        }
        if ($range == '6months') {
            $days = 180;
        }
        if ($range == '1year') {
            $days = 365;
        }
        if ($range == 'custom') {
            $start_day = '';
            $end_day = '';
        }



        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_orders';

        // Get the date range (last 7 days)
        $days_ago = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = gmdate('Y-m-d 23:59:59');



        if ($range == '6months') {

            // Get current date
            $now_6_months = current_time('mysql', true); // GMT time
            // Calculate date 6 months ago
            $six_months_ago = gmdate('Y-m-01 00:00:00', strtotime('-6 months', strtotime($now_6_months))); // Start of the month 6 months ago

            $results_6_months = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $six_months_ago,
                    $now_6_months
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 6 months, even if there are no entries
            $final_results_6_months = [];
            for ($i = 0; $i < 6; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_6_months)));
                $final_results_6_months[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_6_months as $row) {
                if (isset($final_results_6_months[$row['entry_label']])) {
                    $final_results_6_months[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_6_months);
        } elseif ($range == '1year') {

            // Get current date
            $now_1_year = current_time('mysql', true); // GMT time
            // Calculate date 1 year ago (12 months ago)
            $one_year_ago = gmdate('Y-m-01 00:00:00', strtotime('-12 months', strtotime($now_1_year))); // Start of the month 12 months ago

            $results_1_year = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $one_year_ago,
                    $now_1_year
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 12 months, even if there are no entries
            $final_results_1_year = [];
            for ($i = 0; $i < 12; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_1_year)));
                $final_results_1_year[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_1_year as $row) {
                if (isset($final_results_1_year[$row['entry_label']])) {
                    $final_results_1_year[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_1_year);
        } else {


            $now = current_time('mysql', true); // current_time('mysql', true) gets current GMT time in MySQL format
            $days_ago = gmdate('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) as entry_label, COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) DESC",
                    $days_ago,
                    $now
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 7 days, even if there are no entries,
            // you can initialize an array and merge the results.

            $final_results = [];
            for ($i = 0; $i < $days; $i++) {
                $date = gmdate('Y-m-d', strtotime("-$i days", strtotime($now)));
                $final_results[$date] = [
                    'entry_label' => $date,
                    'entry_count' => 0
                ];
            }

            foreach ($results as $row) {
                if (isset($final_results[$row['entry_label']])) {
                    $final_results[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert the associative array to an indexed array of objects for the desired output
            $output_array = array_values($final_results);
        }

        $output_array = array_reverse($output_array);


        $dataset = [];
        $data = [];
        $labels = [];

        $dataset['label'] = 'Orders';
        $dataset['data'] = [];
        $dataset['borderColor'] = "#5560ffff";
        $dataset['backgroundColor'] = "#5560ffff";
        $dataset['borderWidth'] = 2;
        $dataset['tension'] = .3;

        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
    }

    function get_orders_statsx($args)
    {


        $userid     = isset($args['userid']) ? sanitize_text_field($args['userid']) : '';
        $product_id     = isset($args['product_id']) ? sanitize_text_field($args['product_id']) : '';
        $orderStatus     = isset($args['orderStatus']) ? sanitize_text_field($args['orderStatus']) : '';
        $paymentStatus     = isset($args['paymentStatus']) ? sanitize_text_field($args['paymentStatus']) : '';
        $paymentMethod     = isset($args['paymentMethod']) ? sanitize_text_field($args['paymentMethod']) : '';
        $date_from     = isset($args['date_from']) ? sanitize_text_field($args['date_from']) : '';
        $date_to     = isset($args['date_to']) ? sanitize_text_field($args['date_to']) : '';





        global $wpdb;

        $table_orders = $wpdb->prefix . 'cstore_orders';
        $table_items  = $wpdb->prefix . 'cstore_order_items';

        $product_id = isset($product_id) ? intval($product_id) : 0;
        $date_from  = isset($date_from) ? $date_from : '';
        $date_to    = isset($date_to) ? $date_to : '';

        $where = "WHERE 1=1";

        if ($userid) {
            $where .= $wpdb->prepare(" AND o.userid = %d", $userid);
        }
        if ($orderStatus) {
            $where .= $wpdb->prepare(" AND o.status = %s", $orderStatus);
        }
        if ($paymentStatus) {
            $where .= $wpdb->prepare(" AND o.payment_status = %s", $paymentStatus);
        }
        if ($paymentMethod) {
            $where .= $wpdb->prepare(" AND o.payment_method = %s", $paymentMethod);
        }



        if ($product_id) {
            $where .= $wpdb->prepare(" AND oi.product_id = %d", $product_id);
        }

        if ($date_from && $date_to) {
            $where .= $wpdb->prepare(" AND DATE(o.created_at) BETWEEN %s AND %s", $date_from, $date_to);
        }

        $query = "
        SELECT 
            DATE(o.created_at) as order_date,
            COUNT(DISTINCT o.id) as total_orders,
            SUM(o.total_amount) as total_amount,
            SUM(o.discount_amount) as discount_amount,
            SUM(o.shipping_amount) as shipping_amount
        FROM $table_orders o
        LEFT JOIN $table_items oi 
            ON o.id = oi.order_id
        $where
        GROUP BY DATE(o.created_at)
        ORDER BY DATE(o.created_at) ASC
        ";


        $results = $wpdb->get_results($query, ARRAY_A);



        return $results;
    }

    function get_orders_stats($args)
    {
        global $wpdb;

        $table_orders = $wpdb->prefix . 'cstore_orders';
        $table_items  = $wpdb->prefix . 'cstore_order_items';

        // Sanitize inputs
        $userid        = isset($args['userid']) ? intval($args['userid']) : 0;
        $product_id    = isset($args['product_id']) ? intval($args['product_id']) : 0;
        $orderStatus   = isset($args['orderStatus']) ? sanitize_text_field($args['orderStatus']) : '';
        $paymentStatus = isset($args['paymentStatus']) ? sanitize_text_field($args['paymentStatus']) : '';
        $paymentMethod = isset($args['paymentMethod']) ? sanitize_text_field($args['paymentMethod']) : '';
        $date_from     = isset($args['date_from']) ? sanitize_text_field($args['date_from']) : '';
        $date_to       = isset($args['date_to']) ? sanitize_text_field($args['date_to']) : '';


        // error_log(wp_json_encode($args));

        // -----------------------------
        // Determine grouping (day/month)
        // -----------------------------
        $group_by = 'day';

        if ($date_from && $date_to) {
            $diff_days = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24);
            if ($diff_days > 90) {
                $group_by = 'month';
            }
        }

        // -----------------------------
        // Build WHERE conditions
        // -----------------------------
        $where = "WHERE 1=1";

        if ($userid) {
            $where .= $wpdb->prepare(" AND o.userid = %d", $userid);
        }
        if ($orderStatus) {
            $where .= $wpdb->prepare(" AND o.status = %s", $orderStatus);
        }
        if ($paymentStatus) {
            $where .= $wpdb->prepare(" AND o.payment_status = %s", $paymentStatus);
        }
        if ($paymentMethod) {
            $where .= $wpdb->prepare(" AND o.payment_method = %s", $paymentMethod);
        }
        if ($product_id) {
            $where .= $wpdb->prepare(" AND oi.product_id = %d", $product_id);
        }

        // -----------------------------
        // Dynamic date format
        // -----------------------------
        $date_format = ($group_by === 'month')
            ? "DATE_FORMAT(ds.order_date, '%Y-%m')"
            : "ds.order_date";

        // -----------------------------
        // Main Query (WITH date series)
        // -----------------------------
        $query = "
    WITH RECURSIVE date_series AS (
        SELECT DATE(%s) AS order_date
        UNION ALL
        SELECT order_date + INTERVAL 1 DAY
        FROM date_series
        WHERE order_date < DATE(%s)
    )
    SELECT 
        $date_format AS order_date,
        COUNT(DISTINCT o.id) as total_orders,
        COALESCE(SUM(o.total_amount), 0) as total_amount,
        COALESCE(SUM(o.subtotal_amount), 0) as subtotal_amount,
        COALESCE(SUM(o.discount_amount), 0) as discount_amount,
        COALESCE(SUM(o.shipping_amount), 0) as shipping_amount,
        COALESCE(SUM(o.gross_profit), 0) as gross_profit,
        COALESCE(SUM(o.net_profit), 0) as net_profit
    FROM date_series ds
    LEFT JOIN $table_orders o 
        ON DATE(o.created_at) = ds.order_date
    LEFT JOIN $table_items oi 
        ON o.id = oi.order_id
    $where
    GROUP BY $date_format
    ORDER BY $date_format ASC
    ";

        // Prepare query safely
        $prepared_query = $wpdb->prepare($query, $date_from, $date_to);

        $results = $wpdb->get_results($prepared_query, ARRAY_A);

        return $results;
    }




    function get_refunds_range($request)
    {


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";
        $page = ($page == 0) ? 1 : $page;



        if ($range == '7days') {
            $days = 7;
        }
        if ($range == '15days') {
            $days = 15;
        }
        if ($range == '30days') {
            $days = 30;
        }
        if ($range == '6months') {
            $days = 180;
        }
        if ($range == '1year') {
            $days = 365;
        }
        if ($range == 'custom') {
            $start_day = '';
            $end_day = '';
        }



        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_orders';

        // Get the date range (last 7 days)
        $days_ago = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = gmdate('Y-m-d 23:59:59');



        if ($range == '6months') {

            // Get current date
            $now_6_months = current_time('mysql', true); // GMT time
            // Calculate date 6 months ago
            $six_months_ago = gmdate('Y-m-01 00:00:00', strtotime('-6 months', strtotime($now_6_months))); // Start of the month 6 months ago

            $results_6_months = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE payment_status = 'refunded' AND created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $six_months_ago,
                    $now_6_months
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 6 months, even if there are no entries
            $final_results_6_months = [];
            for ($i = 0; $i < 6; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_6_months)));
                $final_results_6_months[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_6_months as $row) {
                if (isset($final_results_6_months[$row['entry_label']])) {
                    $final_results_6_months[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_6_months);
        } elseif ($range == '1year') {

            // Get current date
            $now_1_year = current_time('mysql', true); // GMT time
            // Calculate date 1 year ago (12 months ago)
            $one_year_ago = gmdate('Y-m-01 00:00:00', strtotime('-12 months', strtotime($now_1_year))); // Start of the month 12 months ago

            $results_1_year = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE payment_status = 'refunded' AND created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $one_year_ago,
                    $now_1_year
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 12 months, even if there are no entries
            $final_results_1_year = [];
            for ($i = 0; $i < 12; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_1_year)));
                $final_results_1_year[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_1_year as $row) {
                if (isset($final_results_1_year[$row['entry_label']])) {
                    $final_results_1_year[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_1_year);
        } else {


            $now = current_time('mysql', true); // current_time('mysql', true) gets current GMT time in MySQL format
            $days_ago = gmdate('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) as entry_label, COUNT(*) as entry_count
         FROM $table_name
         WHERE payment_status = 'refunded' AND created_at >= %s AND created_at <= %s
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) DESC",
                    $days_ago,
                    $now
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 7 days, even if there are no entries,
            // you can initialize an array and merge the results.

            $final_results = [];
            for ($i = 0; $i < $days; $i++) {
                $date = gmdate('Y-m-d', strtotime("-$i days", strtotime($now)));
                $final_results[$date] = [
                    'entry_label' => $date,
                    'entry_count' => 0
                ];
            }

            foreach ($results as $row) {
                if (isset($final_results[$row['entry_label']])) {
                    $final_results[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert the associative array to an indexed array of objects for the desired output
            $output_array = array_values($final_results);
        }

        $output_array = array_reverse($output_array);


        $dataset = [];
        $data = [];
        $labels = [];

        $dataset['label'] = 'Refunds';
        $dataset['data'] = [];
        $dataset['borderColor'] = "#fe1414ff";
        $dataset['backgroundColor'] = "#fe1414ff";
        $dataset['borderWidth'] = 2;
        $dataset['tension'] = .3;

        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
    }




    function get_purchases_range($request)
    {


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";
        $page = ($page == 0) ? 1 : $page;



        if ($range == '7days') {
            $days = 7;
        }
        if ($range == '15days') {
            $days = 15;
        }
        if ($range == '30days') {
            $days = 30;
        }
        if ($range == '6months') {
            $days = 180;
        }
        if ($range == '1year') {
            $days = 365;
        }
        if ($range == 'custom') {
            $start_day = '';
            $end_day = '';
        }



        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_purchases';

        // Get the date range (last 7 days)
        $days_ago = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = gmdate('Y-m-d 23:59:59');



        if ($range == '6months') {

            // Get current date
            $now_6_months = current_time('mysql', true); // GMT time
            // Calculate date 6 months ago
            $six_months_ago = gmdate('Y-m-01 00:00:00', strtotime('-6 months', strtotime($now_6_months))); // Start of the month 6 months ago

            $results_6_months = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $six_months_ago,
                    $now_6_months
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 6 months, even if there are no entries
            $final_results_6_months = [];
            for ($i = 0; $i < 6; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_6_months)));
                $final_results_6_months[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_6_months as $row) {
                if (isset($final_results_6_months[$row['entry_label']])) {
                    $final_results_6_months[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_6_months);
        } elseif ($range == '1year') {

            // Get current date
            $now_1_year = current_time('mysql', true); // GMT time
            // Calculate date 1 year ago (12 months ago)
            $one_year_ago = gmdate('Y-m-01 00:00:00', strtotime('-12 months', strtotime($now_1_year))); // Start of the month 12 months ago

            $results_1_year = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT
            DATE_FORMAT(created_at, '%%Y-%%m') as entry_label,
            COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY entry_label
         ORDER BY entry_label DESC",
                    $one_year_ago,
                    $now_1_year
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 12 months, even if there are no entries
            $final_results_1_year = [];
            for ($i = 0; $i < 12; $i++) {
                $month = gmdate('Y-m', strtotime("-$i months", strtotime($now_1_year)));
                $final_results_1_year[$month] = [
                    'entry_label' => $month,
                    'entry_count' => 0
                ];
            }

            foreach ($results_1_year as $row) {
                if (isset($final_results_1_year[$row['entry_label']])) {
                    $final_results_1_year[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert to indexed array for desired output
            $output_array = array_values($final_results_1_year);
        } else {


            $now = current_time('mysql', true); // current_time('mysql', true) gets current GMT time in MySQL format
            $days_ago = gmdate('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) as entry_label, COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) DESC",
                    $days_ago,
                    $now
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 7 days, even if there are no entries,
            // you can initialize an array and merge the results.

            $final_results = [];
            for ($i = 0; $i < $days; $i++) {
                $date = gmdate('Y-m-d', strtotime("-$i days", strtotime($now)));
                $final_results[$date] = [
                    'entry_label' => $date,
                    'entry_count' => 0
                ];
            }

            foreach ($results as $row) {
                if (isset($final_results[$row['entry_label']])) {
                    $final_results[$row['entry_label']]['entry_count'] = (int) $row['entry_count'];
                }
            }

            // Convert the associative array to an indexed array of objects for the desired output
            $output_array = array_values($final_results);
        }

        $output_array = array_reverse($output_array);


        $dataset = [];
        $data = [];
        $labels = [];

        $dataset['label'] = 'Purchases';
        $dataset['data'] = [];
        $dataset['borderColor'] = "#f98118ff";
        $dataset['backgroundColor'] = "#f98118ff";
        $dataset['borderWidth'] = 2;
        $dataset['tension'] = .3;

        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
    }

    // function get_orders_total_amount() {
    //     global $wpdb;

    //     $table = $wpdb->prefix . 'cstore_orders';

    //     $total = $wpdb->get_var(
    //         $wpdb->prepare(
    //             "SELECT SUM(total_amount)
    //              FROM $table
    //              WHERE status = %s",
    //             'completed'
    //         )
    //     );

    //     return $total ? floatval($total) : 0;
    // }


    function get_orders_total_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;

        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        // Base query
        $query = "SELECT SUM(total_amount)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        // Add date filters if provided
        if (!empty($start_date)) {
            $query .= " AND DATE(created_at) >= %s";
            $params[] = $start_date;
        }

        if (!empty($end_date)) {
            $query .= " AND DATE(created_at) <= %s";
            $params[] = $end_date;
        }

        // Prepare safely
        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }


    function cstore_get_top_selling_products_last30($limit = 10)
    {
        global $wpdb;


        $orders_table = $wpdb->prefix . 'cstore_orders';
        $items_table  = $wpdb->prefix . 'cstore_order_items';

        $sql = $wpdb->prepare("
        SELECT 
            oi.product_id,
            oi.product_name,
            SUM(oi.quantity) AS total_units_sold,
            SUM(oi.total) AS total_revenue
        FROM {$items_table} oi
        JOIN {$orders_table} o ON oi.order_id = o.id
        WHERE 
            o.status = %s
            AND o.payment_status = %s
            AND o.completed_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        GROUP BY oi.product_id, oi.product_name
        ORDER BY total_units_sold DESC
        LIMIT %d
    ", 'completed', 'paid', 30, $limit);

        return $wpdb->get_results($sql);
    }


    function get_top_selling_products($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['limit'] ?? 10;
        $page = $args['page'] ?? 1;




        global $wpdb;

        $orders_table = $wpdb->prefix . 'cstore_orders';
        $items_table  = $wpdb->prefix . 'cstore_order_items';

        $page  = max(1, (int) $page);
        $limit = (int) $limit;
        $offset = ($page - 1) * $limit;

        $query = "
        SELECT 
            oi.product_id,
            oi.product_name,
            oi.sku,
            SUM(oi.quantity) AS total_units_sold,
            COUNT(DISTINCT oi.order_id) AS total_orders,
            SUM(oi.total) AS total_revenue
        FROM {$items_table} oi
        INNER JOIN {$orders_table} o ON oi.order_id = o.id
        WHERE 1=1
        AND o.status = %s
        AND o.payment_status = %s
    ";

        $params = ['completed', 'paid'];

        // Start date filter
        if (!empty($start_date)) {
            $query .= " AND o.completed_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        // End date filter
        if (!empty($end_date)) {
            $query .= " AND o.completed_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $query .= "
        GROUP BY oi.product_id, oi.product_name, oi.sku
        ORDER BY total_units_sold DESC
        LIMIT %d OFFSET %d
    ";

        $params[] = $limit;
        $params[] = $offset;

        $prepared_query = $wpdb->prepare($query, $params);
        $items = $wpdb->get_results($prepared_query);




        foreach ($items as $key => $value) {
            $id = intval($value->product_id);
            $product_name = get_the_title($id);
            $items[$key]->product_image = get_the_post_thumbnail_url($id, 'thumbnail');
            $items[$key]->product_name = $product_name;
        }

        return $items;
    }



    function get_orders_total_due_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;

        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        // Base query
        $query = "SELECT SUM(total_amount)
              FROM $table
              WHERE payment_status = %s";

        $params = ['due'];

        // Add start date filter
        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        // Add end date filter
        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        // Prepare safely
        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }


    function get_orders_total_pending_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;

        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        // Base query
        $query = "SELECT SUM(total_amount)
              FROM $table
              WHERE payment_status = %s";

        $params = ['pending'];

        // Add start date filter
        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        // Add end date filter
        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        // Prepare safely
        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }








    function get_purchase_total_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;

        global $wpdb;

        $table = $wpdb->prefix . 'cstore_purchases';

        $query = "SELECT SUM(total_amount)
              FROM $table
              WHERE payment_status = %s";

        $params = ['paid'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }





    function get_expenses_total_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;

        global $wpdb;

        $table = $wpdb->prefix . 'cstore_expenses';

        $query = "SELECT SUM(total_amount)
              FROM $table
              WHERE payment_status = %s";

        $params = ['paid'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }






    function get_total_discount_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $query = "SELECT SUM(discount_amount)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }



    function get_total_tax_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $query = "SELECT SUM(tax_amount)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }


    function get_total_shipping_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $query = "SELECT SUM(shipping_amount)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }

    function get_total_gross_profit_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $query = "SELECT SUM(gross_profit)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        return $total ? floatval($total) : 0;
    }
    function get_total_net_profit_amount($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $query = "SELECT SUM(net_profit)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        error_log("Prepared Query: " . $query); // Debugging line to check the final query

        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);

        error_log("total: " . $total); // Debugging line to check the final query



        return $total ? floatval($total) : 0;
    }










    function total_orders($args = [])
    {
        $user_id = $args['user_id'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $query = "SELECT COUNT(id) FROM $table WHERE 1=1";
        $params = [];

        if (!$isAdmin) {
            $query .= " AND userid = %d";
            $params[] = $user_id;
        }

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        $total = $wpdb->get_var($prepared_query);

        return $total ? $total : 0;
    }



    function total_subscriptions($args = [])
    {
        $user_id = $args['user_id'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        global $wpdb;

        $table = $wpdb->prefix . 'cstore_subscriptions';

        $user = get_user_by('id', $user_id);
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $query = "SELECT COUNT(id) FROM $table WHERE 1=1";
        $params = [];

        if (!$isAdmin) {
            $query .= " AND userid = %d";
            $params[] = $user_id;
        }

        if (!empty($start_date)) {
            $query .= " AND created_at >= %s";
            $params[] = $start_date . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $query .= " AND created_at <= %s";
            $params[] = $end_date . ' 23:59:59';
        }

        $prepared_query = !empty($params) ? $wpdb->prepare($query, $params) : $query;

        $total = $wpdb->get_var($prepared_query);

        return $total ? $total : 0;
    }

    function total_products()
    {

        $count_posts = wp_count_posts('product');

        $total = $count_posts->publish;


        return $total ? $total : 0;
    }


    function total_customers()
    {

        $args = array(
            'role'    => 'customer',
            'fields'  => 'ID'
        );

        $users = get_users($args);
        $total = count($users);
        return $total ? $total : 0;
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


$ComboStoreStats = new ComboStoreStats();
