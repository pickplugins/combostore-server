<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access

use DateTime;

class ComboStoreStats
{
    public $total_credit = "";
    public $total_credit_used = "";
    public $user_id = "";


    public function __construct() {}

    function get_top_object_from_orders($args = [])
    {



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



    function get_top_customersX($args)
    {

        $response = [];


        $object = $args['object'] ?? null;
        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['per_page'] ?? 20;
        $paged = $args['paged'] ?? 1;


        error_log(wp_json_encode($args));

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


        $response['users'] = $wpdb->get_results($prepared);


        return $response;
    }



    function get_top_customers($args)
    {
        global $wpdb;

        $response = [];

        $start_date = $args['start_date'] ?? null;
        $end_date   = $args['end_date'] ?? null;

        // Pagination
        $limit = max(1, min(100, absint($args['per_page'] ?? 20)));
        $paged = max(1, absint($args['paged'] ?? 1));
        $offset = ($paged - 1) * $limit;

        $table = $wpdb->prefix . 'cstore_orders';

        // Base WHERE conditions
        $where = "
        WHERE status = %s
        AND payment_status = %s
    ";

        $params = ['completed', 'paid'];

        if (!empty($start_date)) {
            $where .= " AND completed_at >= %s";
            $params[] = sanitize_text_field($start_date) . ' 00:00:00';
        }

        if (!empty($end_date)) {
            $where .= " AND completed_at <= %s";
            $params[] = sanitize_text_field($end_date) . ' 23:59:59';
        }

        // Count total customers matching the same filters
        $count_query = "
        SELECT COUNT(DISTINCT billing_email)
        FROM {$table}
        {$where}
    ";

        $total = (int) $wpdb->get_var(
            $wpdb->prepare($count_query, $params)
        );

        // Fetch paginated customers
        $query = "
        SELECT
            userid,
            billing_name,
            billing_email,
            COUNT(id) AS total_orders,
            SUM(total_amount) AS total_spent
        FROM {$table}
        {$where}
        GROUP BY billing_email
        ORDER BY total_spent DESC
        LIMIT %d OFFSET %d
    ";

        $query_params = array_merge($params, [$limit, $offset]);

        $prepared = $wpdb->prepare($query, $query_params);

        $response['users'] = $wpdb->get_results($prepared);

        // Pagination metadata
        // $response['pagination'] = [
        //     'total'         => $total,
        //     'per_page'      => $limit,
        //     'current_page'  => $paged,
        //     'total_pages'   => (int) ceil($total / $limit),
        //     'has_next_page' => $paged < ceil($total / $limit),
        //     'has_prev_page' => $paged > 1,
        // ];

        return $response;
    }






    function get_orders_range($request)
    {


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";
        $page = ($page == 0) ? 1 : $page;





        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_orders';

        // Get the date range (last 7 days)
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
        } elseif ($range == '30days') {
            $days = 30;

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
        } elseif ($range == '15days') {
            $days = 15;

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
        } elseif ($range == '7days') {
            $days = 7;

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
        } elseif ($range == 'custom') {
            $days = 0;

            $days_ago = gmdate('Y-m-d H:i:s', strtotime("-$days days", strtotime($now)));

            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";





            if (!empty($first_date) && !empty($last_date)) {
                $date1 = new DateTime($first_date);
                $date2 = new DateTime($last_date);

                // Difference in days
                $days = $date1->diff($date2)->days + 1;
            }







            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT DATE(created_at) as entry_label, COUNT(*) as entry_count
         FROM $table_name
         WHERE created_at >= %s AND created_at <= %s
         GROUP BY DATE(created_at)
         ORDER BY DATE(created_at) DESC",
                    $first_date,
                    $last_date
                ),
                ARRAY_A
            );

            // Optional: To ensure you have data for all last 7 days, even if there are no entries,
            // you can initialize an array and merge the results.

            $final_results = [];
            for ($i = 0; $i < $days; $i++) {
                $date = gmdate('Y-m-d', strtotime("-$i days", strtotime($last_date)));
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



        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
        // return $dataset;
    }

    function get_orders_stats($args)
    {
        global $wpdb;

        $table_orders = $wpdb->prefix . 'cstore_orders';
        $table_items  = $wpdb->prefix . 'cstore_order_items';

        // -----------------------------
        // Sanitize inputs
        // -----------------------------
        $userid        = isset($args['customer_id']) ? intval($args['customer_id']) : 0;
        $product_id    = isset($args['product_id']) ? intval($args['product_id']) : 0;
        $orderStatus   = isset($args['orderStatus']) ? sanitize_text_field($args['orderStatus']) : '';
        $paymentStatus = isset($args['paymentStatus']) ? sanitize_text_field($args['paymentStatus']) : '';
        $paymentMethod = isset($args['paymentMethod']) ? sanitize_text_field($args['paymentMethod']) : '';
        $first_date     = isset($args['first_date']) ? sanitize_text_field($args['first_date']) : '';
        $last_date       = isset($args['last_date']) ? sanitize_text_field($args['last_date']) : '';



        // -----------------------------
        // Determine grouping
        // -----------------------------
        $group_by = 'day';

        if ($first_date && $last_date) {
            $diff_days = (strtotime($last_date) - strtotime($first_date)) / DAY_IN_SECONDS;

            if ($diff_days > 90) {
                $group_by = 'month';
            }
        }

        // -----------------------------
        // Build WHERE conditions
        // -----------------------------
        $where  = "WHERE 1=1";
        $params = [];

        if ($userid) {
            $where .= " AND o.userid = %d";
            $params[] = $userid;
        }

        if ($orderStatus) {
            $where .= " AND o.status = %s";
            $params[] = $orderStatus;
        }

        if ($paymentStatus) {
            $where .= " AND o.payment_status = %s";
            $params[] = $paymentStatus;
        }

        if ($paymentMethod) {
            $where .= " AND o.payment_method = %s";
            $params[] = $paymentMethod;
        }

        /*
     * Filter orders which contain the selected product.
     *
     * EXISTS is used instead of JOIN so that an order is
     * counted only once and order-level amounts are not duplicated.
     */
        if ($product_id) {
            $where .= "
            AND EXISTS (
                SELECT 1
                FROM $table_items oi
                WHERE oi.order_id = o.id
                AND oi.product_id = %d
            )
        ";

            $params[] = $product_id;
        }



        // -----------------------------
        // Date grouping
        // -----------------------------
        if ($group_by === 'month') {
            $date_format = "DATE_FORMAT(ds.order_date, '%Y-%m')";
        } else {
            $date_format = "ds.order_date";
        }

        // -----------------------------
        // Main query
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
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(o.total_amount), 0) AS total_amount,
            COALESCE(SUM(o.subtotal_amount), 0) AS subtotal_amount,
            COALESCE(SUM(o.discount_amount), 0) AS discount_amount,
            COALESCE(SUM(o.shipping_amount), 0) AS shipping_amount,
            COALESCE(SUM(o.tax_amount), 0) AS tax_amount,
            COALESCE(SUM(o.gross_profit), 0) AS gross_profit,
            COALESCE(SUM(o.net_profit), 0) AS net_profit
        FROM date_series ds
        LEFT JOIN $table_orders o
            ON DATE(o.created_at) = ds.order_date
        $where
        GROUP BY $date_format
        ORDER BY $date_format ASC
        ";




        // -----------------------------
        // Prepare query
        // -----------------------------
        $prepared_query = $wpdb->prepare(
            $query,
            array_merge(
                [$first_date, $last_date],
                $params
            )
        );

        return $wpdb->get_results($prepared_query, ARRAY_A);
    }


    function get_stats_expenses($args)
    {



        global $wpdb;

        $table_expenses = $wpdb->prefix . 'cstore_expenses';

        // -----------------------------
        // Sanitize inputs
        // -----------------------------
        $userid        = isset($args['customer_id']) ? intval($args['customer_id']) : 0;
        $orderStatus   = isset($args['orderStatus']) ? sanitize_text_field($args['orderStatus']) : '';
        $paymentStatus = isset($args['paymentStatus']) ? sanitize_text_field($args['paymentStatus']) : '';
        $paymentMethod = isset($args['paymentMethod']) ? sanitize_text_field($args['paymentMethod']) : '';
        $first_date     = isset($args['first_date']) ? sanitize_text_field($args['first_date']) : '';
        $last_date       = isset($args['last_date']) ? sanitize_text_field($args['last_date']) : '';



        // -----------------------------
        // Determine grouping
        // -----------------------------
        $group_by = 'day';

        if ($first_date && $last_date) {
            $diff_days = (strtotime($last_date) - strtotime($first_date)) / DAY_IN_SECONDS;

            if ($diff_days > 90) {
                $group_by = 'month';
            }
        }

        // -----------------------------
        // Build WHERE conditions
        // -----------------------------
        $where  = "WHERE 1=1";
        $params = [];

        if ($userid) {
            $where .= " AND o.userid = %d";
            $params[] = $userid;
        }

        if ($orderStatus) {
            $where .= " AND o.status = %s";
            $params[] = $orderStatus;
        }

        if ($paymentStatus) {
            $where .= " AND o.payment_status = %s";
            $params[] = $paymentStatus;
        }

        if ($paymentMethod) {
            $where .= " AND o.payment_method = %s";
            $params[] = $paymentMethod;
        }



        // -----------------------------
        // Date grouping
        // -----------------------------
        if ($group_by === 'month') {
            $date_format = "DATE_FORMAT(ds.order_date, '%Y-%m')";
        } else {
            $date_format = "ds.order_date";
        }

        // -----------------------------
        // Main query
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
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(o.total_amount), 0) AS total_amount
        FROM date_series ds
        LEFT JOIN $table_expenses o
            ON DATE(o.created_at) = ds.order_date
        $where
        GROUP BY $date_format
        ORDER BY $date_format ASC
        ";




        // -----------------------------
        // Prepare query
        // -----------------------------
        $prepared_query = $wpdb->prepare(
            $query,
            array_merge(
                [$first_date, $last_date],
                $params
            )
        );




        return $wpdb->get_results($prepared_query, ARRAY_A);
    }

    function get_stats_purchases($args)
    {



        global $wpdb;

        $table_purchases = $wpdb->prefix . 'cstore_purchases';

        // -----------------------------
        // Sanitize inputs
        // -----------------------------
        $userid        = isset($args['customer_id']) ? intval($args['customer_id']) : 0;
        $orderStatus   = isset($args['orderStatus']) ? sanitize_text_field($args['orderStatus']) : '';
        $paymentStatus = isset($args['paymentStatus']) ? sanitize_text_field($args['paymentStatus']) : '';
        $paymentMethod = isset($args['paymentMethod']) ? sanitize_text_field($args['paymentMethod']) : '';
        $first_date     = isset($args['first_date']) ? sanitize_text_field($args['first_date']) : '';
        $last_date       = isset($args['last_date']) ? sanitize_text_field($args['last_date']) : '';



        // -----------------------------
        // Determine grouping
        // -----------------------------
        $group_by = 'day';

        if ($first_date && $last_date) {
            $diff_days = (strtotime($last_date) - strtotime($first_date)) / DAY_IN_SECONDS;

            if ($diff_days > 90) {
                $group_by = 'month';
            }
        }

        // -----------------------------
        // Build WHERE conditions
        // -----------------------------
        $where  = "WHERE 1=1";
        $params = [];

        if ($userid) {
            $where .= " AND o.userid = %d";
            $params[] = $userid;
        }

        if ($orderStatus) {
            $where .= " AND o.status = %s";
            $params[] = $orderStatus;
        }

        if ($paymentStatus) {
            $where .= " AND o.payment_status = %s";
            $params[] = $paymentStatus;
        }

        if ($paymentMethod) {
            $where .= " AND o.payment_method = %s";
            $params[] = $paymentMethod;
        }



        // -----------------------------
        // Date grouping
        // -----------------------------
        if ($group_by === 'month') {
            $date_format = "DATE_FORMAT(ds.order_date, '%Y-%m')";
        } else {
            $date_format = "ds.order_date";
        }

        // -----------------------------
        // Main query
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
            COUNT(DISTINCT o.id) AS total_orders,
            COALESCE(SUM(o.total_amount), 0) AS total_amount
        FROM date_series ds
        LEFT JOIN $table_purchases o
            ON DATE(o.created_at) = ds.order_date
        $where
        GROUP BY $date_format
        ORDER BY $date_format ASC
        ";




        // -----------------------------
        // Prepare query
        // -----------------------------
        $prepared_query = $wpdb->prepare(
            $query,
            array_merge(
                [$first_date, $last_date],
                $params
            )
        );




        return $wpdb->get_results($prepared_query, ARRAY_A);
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
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";


            if (!empty($first_date) && !empty($last_date)) {
                $date1 = new DateTime($first_date);
                $date2 = new DateTime($last_date);

                // Difference in days
                $days = $date1->diff($date2)->days;
            }
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

        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
        // return $dataset;
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
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";


            if (!empty($first_date) && !empty($last_date)) {
                $date1 = new DateTime($first_date);
                $date2 = new DateTime($last_date);

                // Difference in days
                $days = $date1->diff($date2)->days;
            }
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


        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
        // return $dataset;
    }
    function get_expenses_range($request)
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
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";


            if (!empty($first_date) && !empty($last_date)) {
                $date1 = new DateTime($first_date);
                $date2 = new DateTime($last_date);

                // Difference in days
                $days = $date1->diff($date2)->days;
            }
        }



        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_expenses';

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


        // Populate the counts from query results
        foreach ($output_array as $row) {
            // $date_counts[$row['entry_label']] = $row['entry_count'];

            $labels[] = $row['entry_label'];
            $dataset['data'][] = $row['entry_count'];
        }

        return ["labels" => $labels, "dataset" => $dataset];
        // return $dataset;
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
    function get_orders_trade_price($args = [])
    {

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;


        global $wpdb;

        $table = $wpdb->prefix . 'cstore_orders';

        // Base query
        $query = "SELECT SUM(trade_price)
              FROM $table
              WHERE status = %s";

        $params = ['completed'];
        // $params = [];

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


    function get_top_selling_productsX($args = [])
    {

        $response = [];

        $start_date = $args['start_date'] ?? null;
        $end_date = $args['end_date'] ?? null;
        $limit = $args['per_page'] ?? 10;
        $page = $args['paged'] ?? 1;




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


        $response['products'] = $items;

        return $response;
    }



    function get_top_selling_products($args = [])
    {
        global $wpdb;

        $response = [];

        $start_date = $args['start_date'] ?? null;
        $end_date   = $args['end_date'] ?? null;

        // Pagination
        $limit  = max(1, min(100, absint($args['per_page'] ?? $args['limit'] ?? 10)));
        $paged  = max(1, absint($args['paged'] ?? $args['page'] ?? 1));
        $offset = ($paged - 1) * $limit;

        $orders_table = $wpdb->prefix . 'cstore_orders';
        $items_table  = $wpdb->prefix . 'cstore_order_items';

        // Base WHERE conditions
        $where = "
        WHERE o.status = %s
        AND o.payment_status = %s
    ";

        $params = ['completed', 'paid'];

        // Start date filter
        if (!empty($start_date)) {
            $where .= " AND o.completed_at >= %s";
            $params[] = sanitize_text_field($start_date) . ' 00:00:00';
        }

        // End date filter
        if (!empty($end_date)) {
            $where .= " AND o.completed_at <= %s";
            $params[] = sanitize_text_field($end_date) . ' 23:59:59';
        }

        // Count total distinct products matching the filters
        $count_query = "
        SELECT COUNT(*)
        FROM (
            SELECT oi.product_id
            FROM {$items_table} oi
            INNER JOIN {$orders_table} o ON oi.order_id = o.id
            {$where}
            GROUP BY oi.product_id, oi.product_name, oi.sku
        ) AS product_groups
    ";

        $total = (int) $wpdb->get_var(
            $wpdb->prepare($count_query, $params)
        );

        // Fetch paginated top-selling products
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
        {$where}
        GROUP BY oi.product_id, oi.product_name, oi.sku
        ORDER BY total_units_sold DESC, oi.product_id ASC
        LIMIT %d OFFSET %d
    ";

        $query_params = array_merge($params, [$limit, $offset]);

        $prepared_query = $wpdb->prepare($query, $query_params);
        $items = $wpdb->get_results($prepared_query);

        // Add product images and current product titles
        foreach ($items as $key => $value) {
            $id = absint($value->product_id);

            $product_name = get_the_title($id);

            if ($product_name) {
                $items[$key]->product_name = $product_name;
            }

            $items[$key]->product_image = get_the_post_thumbnail_url(
                $id,
                'thumbnail'
            );
        }

        $total_pages = (int) ceil($total / $limit);

        $response['products'] = $items;

        // $response['pagination'] = [
        //     'total'         => $total,
        //     'per_page'      => $limit,
        //     'current_page'  => $paged,
        //     'total_pages'   => $total_pages,
        //     'has_next_page' => $paged < $total_pages,
        //     'has_prev_page' => $paged > 1,
        // ];

        return $response;
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


        $prepared_query = $wpdb->prepare($query, $params);

        $total = $wpdb->get_var($prepared_query);




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
