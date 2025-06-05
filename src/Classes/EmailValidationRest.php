<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use WP_REST_Request;
use Exception;
use WC_Order_Query;
use WC_Order;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use EmailValidation\Classes\EmailVerifier;
use EmailValidation\Classes\EmailValidationExport;
use EmailValidation\Classes\EmailValidationCredits;
use EmailValidation\Classes\EmailValidationAPIKey;
use EmailValidation\Classes\EmailValidationRegister;
use EmailValidation\Classes\EmailValidationAPIRequests;
use EmailValidation\Classes\EmailValidationStats;
use EmailValidation\Classes\EmailValidationSpammers;


class EmailValidationRest
{
    function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }


    public function register_routes()
    {


        register_rest_route(
            'combo-store/v2',
            '/get_user',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_user'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_chart_data',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_chart_data'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/delete_orders',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_orders'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_products'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_product',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_product'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_orders',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_orders'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_order'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/create_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_order'),
                'permission_callback' => '__return_true',
            )
        );





        register_rest_route(
            'combo-store/v2',
            '/get_subscriptions',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_subscriptions'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_subscriptions',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_subscriptions'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_subscription',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_subscription'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_licenses',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_licenses'),
                'permission_callback' => '__return_true',
            )
        );


        // ### Combo Payments

        register_rest_route(
            'combo-store/v2',
            '/validate_token',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'validate_token'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_user_profile',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_user_profile'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_user_profile',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_user_profile'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/validate_email',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'validate_email'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/validate_email_by_user',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'validate_email_by_user'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/register_user',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'register_user'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/create_api_key',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_api_key'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/delete_api_key',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_api_key'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_api_keys',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_api_keys'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_credits',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_credits'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/delete_credits',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_credits'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/create_spammer',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_spammer'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/check_spammer',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'check_spammer'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/create_credits',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_credits'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/api_requests',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'api_requests'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_tasks',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_tasks'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_task',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_task'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/add_email_to_task',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_email_to_task'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_task_report',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_task_report'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/bulk_update_tasks',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'bulk_update_tasks'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_task',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_task'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/delete_api_keys',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_api_keys'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_tasks',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_tasks'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/delete_tasks_entries',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_tasks_entries'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/create_task',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_task'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/add_tasks_entries',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_tasks_entries'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_tasks_entries',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_tasks_entries'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_spammers',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_spammers'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_spammer_domains',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_spammer_domains'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/email_export',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'email_export'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/validation_requests',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'validation_requests'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/add_source_links',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_source_links'),
                'permission_callback' => '__return_true',
            )
        );
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_subscriptions($request)
    {



        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_subscriptions = $prefix . 'cstore_subscriptions';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_subscriptions WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order LIMIT $offset, $limit");

        if ($isAdmin) {

            $entries = $wpdb->get_results("SELECT * FROM $table_subscriptions ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_subscriptions");
        } else {

            $entries = $wpdb->get_results("SELECT * FROM $table_subscriptions WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_subscriptions WHERE userid='$user_id'");
        }




        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_orders($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);

        // // Decode the token
        // try {
        //     $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        // } catch (Exception $e) {
        //     return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        // }


        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : "";
        $type     = isset($request['type']) ? sanitize_text_field($request['type']) : "";
        $customer     = isset($request['customer']) ? sanitize_text_field($request['customer']) : "";
        $customer_id     = isset($request['customer_id']) ? sanitize_text_field($request['customer_id']) : "";
        $billing_country     = isset($request['billing_country']) ? sanitize_text_field($request['billing_country']) : "";
        $billing_first_name     = isset($request['billing_first_name']) ? sanitize_text_field($request['billing_first_name']) : "";
        $billing_last_name     = isset($request['billing_last_name']) ? sanitize_text_field($request['billing_last_name']) : "";
        $payment_method     = isset($request['payment_method']) ? sanitize_text_field($request['payment_method']) : "";
        $currency     = isset($request['currency']) ? sanitize_text_field($request['currency']) : "";


        $query_args = [];


        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($limit)) {
            $query_args['limit'] = $limit;
        }
        if (!empty($orderby)) {
            $query_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $query_args['order'] = $order;
        }
        if (!empty($status)) {
            $query_args['status'] = $status;
        }
        if (!empty($type)) {
            $query_args['type'] = $type;
        }
        if (!empty($customer)) {
            $query_args['customer'] = $customer;
        }
        if (!empty($customer_id)) {
            $query_args['customer_id'] = $customer_id;
        }
        if (!empty($billing_country)) {
            $query_args['billing_country'] = $billing_country;
        }
        if (!empty($billing_first_name)) {
            $query_args['billing_first_name'] = $billing_first_name;
        }
        if (!empty($billing_last_name)) {
            $query_args['billing_last_name'] = $billing_last_name;
        }
        if (!empty($payment_method)) {
            $query_args['payment_method'] = $payment_method;
        }
        if (!empty($currency)) {
            $query_args['currency'] = $currency;
        }



        $orders = wc_get_orders($query_args);


        // $query = new WC_Order_Query($query_args);
        // //$query->set('customer', 'woocommerce@woocommerce.com');
        // $orders = $query->get_orders();
        // $order_id = $order_data['id'];

        $order_data = [];

        foreach ($orders as $order) {


            $order_currency = $order->get_currency();


            // //error_log(serialize($query_args));
            $currency_symbol = get_woocommerce_currency_symbol($order_currency);



            $order_data[] = array(
                'order_id' => $order->get_id(),
                'order_number' => $order->get_order_number(),
                'order_date' => date('Y-m-d H:i:s', strtotime(get_post($order->get_id())->post_date)),
                'status' => $order->get_status(),
                'shipping_total' => $order->get_total_shipping(),
                'shipping_tax_total' => wc_format_decimal($order->get_shipping_tax(), 2),
                'tax_total' => wc_format_decimal($order->get_total_tax(), 2),
                'discount_total' => wc_format_decimal($order->get_total_discount(), 2),
                'order_total' => wc_format_decimal($order->get_total(), 2),
                'order_currency' => $currency_symbol,
                'payment_method' => $order->get_payment_method(),
                'shipping_method' => $order->get_shipping_method(),
                'customer_id' => $order->get_user_id(),
                'customer_user' => $order->get_user_id(),
                'customer_email' => ($a = get_userdata($order->get_user_id())) ? $a->user_email : '',
                'billing_first_name' => $order->get_billing_first_name(),
                'billing_last_name' => $order->get_billing_last_name(),
                'billing_company' => $order->get_billing_company(),
                'billing_email' => $order->get_billing_email(),
                'billing_phone' => $order->get_billing_phone(),
                'billing_address_1' => $order->get_billing_address_1(),
                'billing_address_2' => $order->get_billing_address_2(),
                'billing_postcode' => $order->get_billing_postcode(),
                'billing_city' => $order->get_billing_city(),
                'billing_state' => $order->get_billing_state(),
                'billing_country' => $order->get_billing_country(),
                'shipping_first_name' => $order->get_shipping_first_name(),
                'shipping_last_name' => $order->get_shipping_last_name(),
                'shipping_company' => $order->get_shipping_company(),
                'shipping_address_1' => $order->get_shipping_address_1(),
                'shipping_address_2' => $order->get_shipping_address_2(),
                'shipping_postcode' => $order->get_shipping_postcode(),
                'shipping_city' => $order->get_shipping_city(),
                'shipping_state' => $order->get_shipping_state(),
                'shipping_country' => $order->get_shipping_country(),
                'customer_note' => $order->get_customer_note(),
                'download_permissions' => $order->is_download_permitted() ? $order->is_download_permitted() : 0,
            );
        }

        $response['orders'] = $order_data;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_products($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);

        // // Decode the token
        // try {
        //     $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        // } catch (Exception $e) {
        //     return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        // }


        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : "";
        $downloadable     = isset($request['downloadable']) ? sanitize_text_field($request['downloadable']) : null;
        $type     = isset($request['type']) ? sanitize_text_field($request['type']) : "";
        $sku     = isset($request['sku']) ? sanitize_text_field($request['sku']) : "";
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : "";
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $width     = isset($request['width']) ? sanitize_text_field($request['width']) : "";
        $length     = isset($request['length']) ? sanitize_text_field($request['length']) : "";
        $average_rating     = isset($request['average_rating']) ? sanitize_text_field($request['average_rating']) : "";
        $price     = isset($request['price']) ? sanitize_text_field($request['price']) : "";
        $customer     = isset($request['customer']) ? sanitize_text_field($request['customer']) : "";
        $customer_id     = isset($request['customer_id']) ? sanitize_text_field($request['customer_id']) : "";
        $billing_country     = isset($request['billing_country']) ? sanitize_text_field($request['billing_country']) : "";
        $billing_first_name     = isset($request['billing_first_name']) ? sanitize_text_field($request['billing_first_name']) : "";
        $billing_last_name     = isset($request['billing_last_name']) ? sanitize_text_field($request['billing_last_name']) : "";
        $payment_method     = isset($request['payment_method']) ? sanitize_text_field($request['payment_method']) : "";
        $currency     = isset($request['currency']) ? sanitize_text_field($request['currency']) : "";


        $query_args = [];


        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($limit)) {
            $query_args['limit'] = $limit;
        }
        if (!empty($orderby)) {
            $query_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $query_args['order'] = $order;
        }
        if (!empty($status)) {
            $query_args['status'] = $status;
        }
        if (!empty($downloadable)) {
            $query_args['downloadable'] = $downloadable;
        }
        if (!empty($type)) {
            $query_args['type'] = $type;
        }
        if (!empty($sku)) {
            $query_args['sku'] = $sku;
        }
        if (!empty($name)) {
            $query_args['name'] = $name;
        }
        if (!empty($tag)) {
            $query_args['tag'] = $tag;
        }
        if (!empty($category)) {
            $query_args['category'] = $category;
        }
        if (!empty($width)) {
            $query_args['width'] = $width;
        }
        if (!empty($length)) {
            $query_args['length'] = $length;
        }
        if (!empty($average_rating)) {
            $query_args['average_rating'] = $average_rating;
        }
        if (!empty($price)) {
            $query_args['price'] = $price;
        }
        if (!empty($customer)) {
            $query_args['customer'] = $customer;
        }
        if (!empty($customer_id)) {
            $query_args['customer_id'] = $customer_id;
        }
        if (!empty($billing_country)) {
            $query_args['billing_country'] = $billing_country;
        }
        if (!empty($billing_first_name)) {
            $query_args['billing_first_name'] = $billing_first_name;
        }
        if (!empty($billing_last_name)) {
            $query_args['billing_last_name'] = $billing_last_name;
        }
        if (!empty($payment_method)) {
            $query_args['payment_method'] = $payment_method;
        }
        if (!empty($currency)) {
            $query_args['currency'] = $currency;
        }



        // //error_log(serialize($query_args));


        $products = wc_get_products($query_args);


        // $query = new WC_Order_Query($query_args);
        // //$query->set('customer', 'woocommerce@woocommerce.com');
        // $orders = $query->get_orders();
        // $order_id = $order_data['id'];

        $products_data = [];
        $currency_symbol = get_woocommerce_currency_symbol(get_woocommerce_currency());

        foreach ($products as $product) {

            $price_formatted         = wc_price($product->get_price(), array('currency' => $currency));
            $price_html = $product->get_price_html();

            $regular_price = $product->get_regular_price();
            $sale_price = $product->get_sale_price();
            $type = $product->get_type();

            // Initialize min/max as null
            $min_price = null;
            $max_price = null;
            $min_price_formatted = null;
            $max_price_formatted = null;

            // If variable product, get min/max price range
            if ($product->is_type('variable')) {
                $min_price = $product->get_variation_price('min', true);
                $max_price = $product->get_variation_price('max', true);

                $min_price_formatted = wc_price($min_price, array('currency' => $currency));
                $max_price_formatted = wc_price($max_price, array('currency' => $currency));
            }


            $category_terms = get_the_terms($product->get_id(), 'product_cat');

            $categories = array();
            if (! is_wp_error($category_terms) && ! empty($category_terms)) {
                foreach ($category_terms as $term) {
                    $categories[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                    );
                }
            }

            $tags_terms = get_the_terms($product->get_id(), 'product_tag');

            $tags = array();
            if (! is_wp_error($tags_terms) && ! empty($tags_terms)) {
                foreach ($tags_terms as $term) {
                    $tags[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                    );
                }
            }
            $brands_terms = get_the_terms($product->get_id(), 'product_brand');

            $brands = array();
            if (! is_wp_error($brands_terms) && ! empty($brands_terms)) {
                foreach ($brands_terms as $term) {
                    $brands[] = array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                    );
                }
            }




            $products_data[] = array(
                'id'                => $product->get_id(),
                'name'              => $product->get_name(),
                'slug'              => $product->get_slug(),
                'sku'               => $product->get_sku(),
                'price_formatted'       => $price_formatted,
                'price_html'              => $price_html,
                'price'             => $product->get_price(),
                'regular_price'     => $product->get_regular_price(),
                'sale_price'        => $product->get_sale_price(),
                'min_price'                 => $min_price,
                'max_price'                 => $max_price,
                'min_price_formatted'       => $min_price_formatted,
                'max_price_formatted'       => $max_price_formatted,
                'stock_status'      => $product->get_stock_status(),
                'stock_quantity'    => $product->get_stock_quantity(),
                'description'       => $product->get_description(),
                'short_description' => $product->get_short_description(),
                'type'              => $product->get_type(),
                'status'            => $product->get_status(),
                'featured'          => $product->get_featured(),
                'permalink'         => $product->get_permalink(),
                'image_url'         => wp_get_attachment_url($product->get_image_id()),
                'gallery_image_ids' => $product->get_gallery_image_ids(),
                'categories' => $categories,
                'tags' => $tags,
                'brands' => $brands,
                'attributes'        => $product->get_attributes(),
                'currency_symbol'   => $currency_symbol,

            );
        }

        $response['products'] = $products_data;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_product($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);

        // // Decode the token
        // try {
        //     $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        // } catch (Exception $e) {
        //     return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        // }


        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $id     = isset($request['id']) ? absint($request['id']) : 1;


        // //error_log(serialize($query_args));


        $product = wc_get_product($id);


        // $query = new WC_Order_Query($query_args);
        // //$query->set('customer', 'woocommerce@woocommerce.com');
        // $orders = $query->get_orders();
        // $order_id = $order_data['id'];

        $products_data = [];
        $currency_symbol = get_woocommerce_currency_symbol(get_woocommerce_currency());

        $price_formatted         = wc_price($product->get_price(), array('currency' => $currency_symbol));
        $price_html = $product->get_price_html();

        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        $type = $product->get_type();

        // Initialize min/max as null
        $min_price = null;
        $max_price = null;
        $min_price_formatted = null;
        $max_price_formatted = null;

        // If variable product, get min/max price range
        if ($product->is_type('variable')) {
            $min_price = $product->get_variation_price('min', true);
            $max_price = $product->get_variation_price('max', true);

            $min_price_formatted = wc_price($min_price, array('currency' => $currency_symbol));
            $max_price_formatted = wc_price($max_price, array('currency' => $currency_symbol));
        }


        $category_terms = get_the_terms($product->get_id(), 'product_cat');

        $categories = array();
        if (! is_wp_error($category_terms) && ! empty($category_terms)) {
            foreach ($category_terms as $term) {
                $categories[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                );
            }
        }

        $tags_terms = get_the_terms($product->get_id(), 'product_tag');

        $tags = array();
        if (! is_wp_error($tags_terms) && ! empty($tags_terms)) {
            foreach ($tags_terms as $term) {
                $tags[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                );
            }
        }
        $brands_terms = get_the_terms($product->get_id(), 'product_brand');

        $brands = array();
        if (! is_wp_error($brands_terms) && ! empty($brands_terms)) {
            foreach ($brands_terms as $term) {
                $brands[] = array(
                    'id' => $term->term_id,
                    'name' => $term->name,
                );
            }
        }


        $gallery_image_ids = $product->get_gallery_image_ids();

        // Convert IDs to URLs
        $gallery_image_urls = array();
        foreach ($gallery_image_ids as $image_id) {
            $gallery_image_urls[] = [
                "original" => wp_get_attachment_url($image_id),
                "thumbnail" => wp_get_attachment_url($image_id),
            ];
        }

        $product_data = array(
            'id'                => $product->get_id(),
            'title'              => $product->get_title(),
            'excerpt' => $product->get_short_description(),

            'name'              => $product->get_name(),
            'slug'              => $product->get_slug(),
            'sku'               => $product->get_sku(),
            'price_formatted'       => $price_formatted,
            'price_html'              => $price_html,
            'price'             => $product->get_price(),
            'regular_price'     => $product->get_regular_price(),
            'sale_price'        => $product->get_sale_price(),
            'min_price'                 => $min_price,
            'max_price'                 => $max_price,
            'min_price_formatted'       => $min_price_formatted,
            'max_price_formatted'       => $max_price_formatted,
            'stock_status'      => $product->get_stock_status(),
            'stock_quantity'    => $product->get_stock_quantity(),
            'description'       => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'type'              => $product->get_type(),
            'status'            => $product->get_status(),
            'featured'          => $product->get_featured(),
            'permalink'         => $product->get_permalink(),
            'image_url'         => wp_get_attachment_url($product->get_image_id()),
            'gallery_image_urls' => $gallery_image_urls,

            'gallery_image_ids' => $product->get_gallery_image_ids(),
            'categories' => $categories,
            'tags' => $tags,
            'brands' => $brands,
            'attributes'        => $product->get_attributes(),
            'currency_symbol'   => $currency_symbol,

        );

        $response['product'] = $product_data;


        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_order($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);

        // // Decode the token
        // try {
        //     $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        // } catch (Exception $e) {
        //     return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        // }


        // // Get user by ID
        // // $user = get_user_by('id', $decoded_token->sub);



        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $billing     = isset($request['billing']) ? ($request['billing']) : [];
        $shipping     = isset($request['shipping']) ? ($request['shipping']) : [];
        $payment_method     = isset($request['payment_method']) ? sanitize_text_field($request['payment_method']) : '';
        $payment_method_title     = isset($request['payment_method_title']) ? sanitize_text_field($request['payment_method_title']) : "";
        $line_items     = isset($request['line_items']) ? ($request['line_items']) : [];
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : 'pending';
        $coupon_code     = isset($request['coupon_code']) ? sanitize_text_field($request['coupon_code']) : '';
        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';





        // $address = array(
        //     'first_name' => 'John',
        //     'last_name'  => 'Doe',
        //     'email'      => 'john@example.com',
        //     'phone'      => '017XXXXXXXX',
        //     'address_1'  => '123 Main St',
        //     'city'       => 'Dhaka',
        //     'postcode'   => '1205',
        //     'country'    => 'BD',
        //     'state'      => '13'
        // );

        $order = wc_create_order();

        /*Checking user start*/
        // Check User and Create if not exist
        $user_id =  email_exists($email);

        if (!$user_id && false == username_exists($email)) {
            $random_password = wp_generate_password($length = 12, $include_standard_special_chars = false);
            $user_id = wp_create_user($email, $random_password, $email);

            $user = get_user_by('ID', $user_id);
            $user->add_role('customer');
        }

        /*Checking user end*/


        // Add multiple products

        foreach ($line_items as $line_item) {


            $product_id = isset($line_item['product_id']) ? $line_item['product_id'] : '';
            $quantity = isset($line_item['quantity']) ? $line_item['quantity'] : '';

            $product = wc_get_product($product_id);


            $downloads = $product->get_downloads();
            $product->set_downloads($downloads);

            $order->add_product($product, $quantity); // Product ID, Quantity
        }




        if (!empty($billing)) {
            $order->set_address($billing, 'billing');
        }
        if (!empty($shipping)) {
            $order->set_address($shipping, 'shipping');
        }

        $order->set_payment_method($payment_method);
        $order->set_payment_method_title($payment_method_title);

        $order->calculate_totals();
        if (!empty($coupon_code)) {
            $order->apply_coupon($coupon_code);
        }



        //$order->set_status('wc-completed');
        // $order->set_status( 'wc-completed', 'You can pass some order notes here...' );
        $order->update_status($status);

        $order->set_customer_id($user_id);


        $order->save();

        $order_id = $order->get_id();


        $response['order_id'] = $order_id;


        die(wp_json_encode($response));
    }














    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_chart_data($request)
    {



        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


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


        //error_log($range);
        //error_log($days);

        $page = ($page == 0) ? 1 : $page;


        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_api_requests';
        $table_validation_tasks_entries = $wpdb->prefix . 'cstore_validation_tasks_entries';

        // Get the date range (last 7 days)
        $days_ago = date('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = date('Y-m-d 23:59:59');

        //error_log($days_ago);

        if ($isAdmin) {

            if ($range == '6months' || $range == '1year') {

                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(datetime, '%Y-%m') AS entry_date, COUNT(*) AS entry_count 
     FROM $table_name 
     WHERE datetime BETWEEN %s AND %s 
     GROUP BY DATE_FORMAT(datetime, '%Y-%m')",
                        $days_ago,
                        $now
                    ),
                    ARRAY_A
                );



                $results_2 = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(datetime, '%Y-%m') as entry_date, COUNT(*) as entry_count
         FROM $table_validation_tasks_entries 
         WHERE datetime >= %s AND datetime <= %s 
         GROUP BY DATE_FORMAT(datetime, '%Y-%m')
         ",
                        $days_ago,
                        $now
                    ),
                    ARRAY_A
                );
            } else {
                // Query to count entries grouped by date
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE(datetime) as entry_date, COUNT(*) as entry_count
         FROM $table_name 
         WHERE datetime >= %s AND datetime <= %s 
         GROUP BY DATE(datetime)
         ORDER BY DATE(datetime) DESC",
                        $days_ago,
                        $now
                    ),
                    ARRAY_A
                );
                $results_2 = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE(datetime) as entry_date, COUNT(*) as entry_count
         FROM $table_validation_tasks_entries 
         WHERE datetime >= %s AND datetime <= %s 
         GROUP BY DATE(datetime)
         ORDER BY DATE(datetime) DESC",
                        $days_ago,
                        $now
                    ),
                    ARRAY_A
                );
            }
        } else {




            if ($range == '6months' || $range == '1year') {
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(datetime, '%Y-%m') AS entry_date, COUNT(*) AS entry_count 
     FROM $table_name 
     WHERE datetime BETWEEN %s AND %s AND userid='%d
     GROUP BY DATE_FORMAT(datetime, '%Y-%m')",
                        $days_ago,
                        $now,
                        $user_id
                    ),
                    ARRAY_A
                );


                $results_2 = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE_FORMAT(datetime, '%Y-%m') as entry_date, COUNT(*) as entry_count
         FROM $table_validation_tasks_entries 
         WHERE datetime >= %s AND datetime <= %s  AND userid='%d
         GROUP BY DATE_FORMAT(datetime, '%Y-%m')
         ",
                        $days_ago,
                        $now,
                        $user_id
                    ),
                    ARRAY_A
                );
            } else {
                // Query to count entries grouped by date

                // Query to count entries grouped by date
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE(datetime) as entry_date, COUNT(*) as entry_count
         FROM $table_name 
         WHERE datetime >= %s AND datetime <= %s AND userid='%d'
         GROUP BY DATE(datetime)
         ORDER BY DATE(datetime) DESC",
                        $days_ago,
                        $now,
                        $user_id
                    ),
                    ARRAY_A
                );

                $results_2 = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT DATE(datetime) as entry_date, COUNT(*) as entry_count
         FROM $table_validation_tasks_entries 
         WHERE datetime >= %s AND datetime <= %s AND userid='%d'
         GROUP BY DATE(datetime)
         ORDER BY DATE(datetime) DESC",
                        $days_ago,
                        $now,
                        $user_id
                    ),
                    ARRAY_A
                );
            }
        }




        // Initialize array with all dates for the last 7 days
        $date_counts = [];
        for ($i = $days - 1; $i > 0; $i--) { // Looping in reverse to maintain order
            $date = date('Y-m-d', strtotime("-$i days"));
            $date_counts[$date] = 0; // Default count is 0
        }

        $datasets_0 = [];
        $datasets_1 = [];
        $data = [];
        $labels = [];

        $datasets_0['label'] = 'API Request';
        $datasets_0['data'] = [];
        $datasets_0['borderColor'] = "#367850";
        $datasets_0['backgroundColor'] = "#1a4a2d";

        $datasets_1['label'] = 'Task Entries';
        $datasets_1['data'] = [];
        $datasets_1['borderColor'] = "#4040f0";
        $datasets_1['backgroundColor'] = "#1b1b71";



        // Populate the counts from query results
        foreach ($results as $row) {
            // $date_counts[$row['entry_date']] = $row['entry_count'];

            $labels[] = $row['entry_date'];
            $datasets_0['data'][] = $row['entry_count'];
        }
        foreach ($results_2 as $row) {
            // $date_counts[$row['entry_date']] = $row['entry_count'];

            //$labels[] = $row['entry_date'];
            $datasets_1['data'][] = $row['entry_count'];
        }





        $EmailValidationStats = new EmailValidationStats();



        $response['labels'] = $labels;
        $response['datasets'][0] = $datasets_0;
        $response['datasets'][1] = $datasets_1;

        $total_email_validated = $EmailValidationStats->total_email_validated($user_id);
        $total_task = $EmailValidationStats->total_task($user_id);
        $total_api_keys = $EmailValidationStats->total_api_keys($user_id);
        $total_orders = $EmailValidationStats->total_orders($user_id);
        $count_total_credit = $EmailValidationStats->count_total_credit($user_id);
        $count_total_credit_used = $EmailValidationStats->count_total_credit_used($user_id);
        $total_api_validation = $EmailValidationStats->total_api_validation($user_id);


        $response['total_email_validated'] = $total_email_validated;
        $response['total_task'] = $total_task;
        $response['total_api_keys'] = $total_api_keys;
        $response['total_orders'] = $total_orders;
        $response['count_total_credit'] = $count_total_credit;
        $response['count_total_credit_used'] = $count_total_credit_used;
        $response['total_api_validation'] = $total_api_validation;



        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_order($post_data)
    {



        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $order_id     = isset($post_data['id']) ? absint($post_data['id']) : 0;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $order = new WC_Order($order_id);


        $table_subscriptions = $prefix . 'cstore_subscriptions';
        $table_licenses = $prefix . 'cstore_licenses';

        $response = [];




        $licenseRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_licenses WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );


        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );


        $licenseRow = empty($licenseRow) ? [] : $licenseRow;
        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;

        $order_data = $order->get_data(); // The Order data

        $items = $order->get_items();

        $line_items = [];
        $subtotal = 0;

        foreach ($items as $item_id => $item) {
            $product_name = $item->get_name(); // Product name
            $product_id = $item->get_product_id(); // Product ID
            $variation_id = $item->get_variation_id(); // Variation ID if any
            $quantity = $item->get_quantity(); // Quantity ordered
            $subtotal = $item->get_subtotal(); // Line subtotal (before tax)
            $total = $item->get_total(); // Line total (after discount)

            // Optional: Get full product object
            $product = $item->get_product();

            $line_items[] = [
                "product_name" => $product_name,
                "product_id" => $product_id,
                "quantity" => $quantity,
                "subtotal" => $subtotal,
                "total" => $total,
                "link" => get_permalink($product_id),
            ];

            $subtotal += $item->get_subtotal(); // Before tax and discounts

        }

        $currency = $order->get_currency();

        // Get the currency symbol (e.g., '$', '₹', etc.)
        $currency_symbol = get_woocommerce_currency_symbol($currency);

        $order_data['line_items'] = $line_items;
        $order_data['subtotal'] = ($subtotal);
        $order_data['currency_symbol'] = $currency_symbol;


        $response['order'] = $order_data;
        $response['license'] = $licenseRow;
        $response['subscription'] = $subscriptionRow;


        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_subscription($post_data)
    {



        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $object     = isset($post_data['object']) ? sanitize_email($post_data['object']) : '';
        $id     = isset($post_data['id']) ? absint($post_data['id']) : 0;

        $response = [];

        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_orders';
        $table_subscriptions = $prefix . 'cstore_subscriptions';



        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        $oderId = isset($subscriptionRow['order_id']) ? $subscriptionRow['order_id'] : 0;


        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_orders WHERE order_id = %d",
                $oderId
            ),
            ARRAY_A
        );




        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;

        $orderRow = empty($orderRow) ? [] : $orderRow;



        $response['subscription'] = $subscriptionRow;
        $response['order'] = $orderRow;


        die(wp_json_encode($response));
    }
















    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_licenses($request)
    {



        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $user = get_user_by('id', $user_id);


        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }



        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? absint($request['order']) : "DESC";


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_licenses = $prefix . 'cstore_licenses';
        $table_orders = $prefix . 'cstore_orders';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));


        if ($isAdmin) {

            $entries = $wpdb->get_results("SELECT * FROM $table_licenses ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_licenses");
        } else {

            $entries = $wpdb->get_results("SELECT * FROM $table_licenses WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_licenses WHERE userid='$user_id'");
        }



        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function validate_token($request)
    {

        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }



        $EmailValidationCredits = new EmailValidationCredits();


        $total_credit = $EmailValidationCredits->count_total_credit($user_id);
        $total_credit_used = $EmailValidationCredits->count_total_credit_used($user_id);



        // Return user details
        return rest_ensure_response(array(
            'success' => true,
            'user'    => array(
                'id'    => $user->ID,
                'email' => $user->user_email,
                'name'  => $user->display_name,
                'total_credit'  => $total_credit,
                'credit_used_cron'  => $total_credit_used,
                'credit_used_api'  => $total_credit_used,
                'credit_used'  => $total_credit_used,
                'avatar'  => get_avatar_url($user->user_email),
            ),
        ));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_user($request)
    {

        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }



        $EmailValidationCredits = new EmailValidationCredits();


        $total_credit = $EmailValidationCredits->count_total_credit($user_id);
        $credit_used_task = $EmailValidationCredits->total_credit_used_task($user_id);
        $credit_used_api = $EmailValidationCredits->total_credit_used_api($user_id);


        $total_credit_used = $credit_used_task + $credit_used_api;

        $EmailValidationCredits->add_daily_credit($user_id);


        // Return user details
        return rest_ensure_response(array(
            'success' => true,
            'user'    => array(
                'id'    => $user->ID,
                'email' => $user->user_email,
                'name'  => $user->display_name,
                'total_credit'  => $total_credit,
                'credit_used_cron'  => $credit_used_task,
                'credit_used_api'  => $credit_used_api,
                'total_credit_used'  => $total_credit_used,
                'avatar'  => get_avatar_url($user->user_email),
                'roles'  => $user->roles,
            ),
        ));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_user_profile($request)
    {

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
        $response = [];


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        //$user_id     = isset($post_data['id']) ? sanitize_text_field($post_data['id']) : '';

        if (empty($user_id)) {
            $response['errors'] = 'User id empty';
            die(wp_json_encode($response));
        }

        $userData = get_user_by('id', $user_id);


        $first_name              = get_user_meta($user_id,  "first_name", true);
        $last_name              = get_user_meta($user_id,  "last_name", true);
        $email              = isset($userData->user_email) ? $userData->user_email : '';
        $phone              = get_user_meta($user_id,  "phone", true);
        $address_1              = get_user_meta($user_id,  "address_1", true);
        $address_2              = get_user_meta($user_id,  "address_2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);



        $response['first_name'] = $first_name;
        $response['last_name'] = $last_name;
        $response['email'] = $email;
        $response['phone'] = $phone;
        $response['address_1'] = $address_1;
        $response['address_2'] = $address_2;
        $response['zip_code'] = $zip_code;
        $response['country'] = $country;



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function update_user_profile($request)
    {
        $response = [];

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';
        $userData     = isset($request['userData']) ? ($request['userData']) : [];
        $first_name     = isset($userData['first_name']) ? sanitize_text_field($userData['first_name']) : '';
        $last_name     = isset($userData['last_name']) ? sanitize_text_field($userData['last_name']) : '';
        $phone     = isset($userData['phone']) ? sanitize_text_field($userData['phone']) : '';
        $address_1     = isset($userData['address_1']) ? sanitize_text_field($userData['address_1']) : '';
        $address_2     = isset($userData['address_2']) ? sanitize_text_field($userData['address_2']) : '';
        $zip_code     = isset($userData['zip_code']) ? sanitize_text_field($userData['zip_code']) : '';
        $country     = isset($userData['country']) ? sanitize_text_field($userData['country']) : '';


        update_user_meta($id, 'first_name', $first_name);
        update_user_meta($id, 'last_name', $last_name);
        update_user_meta($id, 'last_name', $last_name);
        update_user_meta($id, 'phone', $phone);
        update_user_meta($id, 'address_1', $address_1);
        update_user_meta($id, 'address_2', $address_2);
        update_user_meta($id, 'zip_code', $zip_code);
        update_user_meta($id, 'country', $country);



        $response['success'] = true;


        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_api_keys($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;




        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_api_keys = $prefix . 'cstore_api_keys';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));


        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table_api_keys   ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_api_keys  ");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table_api_keys  WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_api_keys  WHERE userid='$user_id'");
        }


        $entriesX = $entries;


        foreach ($entries as $index => $entry) {
            $entry_userid = isset($entry->userid) ? $entry->userid : 0;
            $entry_user = get_user_by('id', $entry_userid);
            $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
            $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        }


        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_credits($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $type     = isset($request['type']) ? sanitize_text_field($request['type']) : '';
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_credits = $prefix . 'cstore_credits';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_credits WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order LIMIT $offset, $limit");


        if ($isAdmin) {

            // $entries = $wpdb->get_results("SELECT * FROM $table_credits WHERE type='$type' ORDER BY id $order LIMIT $offset, $limit");


            $query = "SELECT * FROM $table_credits";

            if (in_array($type, ['credit', 'debit'])) {
                $query .= " WHERE type='$type'";
            }

            $query .= " ORDER BY id $order LIMIT $offset, $limit";

            $entries = $wpdb->get_results($query);

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_credits WHERE type='$type'");
        } else {

            //$entries = $wpdb->get_results("SELECT * FROM $table_credits WHERE userid='$user_id' AND type='$type' ORDER BY id $order LIMIT $offset, $limit");


            $query = "SELECT * FROM $table_credits";

            if (in_array($type, ['credit', 'debit'])) {
                $query .= " WHERE type='$type' AND userid='$user_id'";
            } else {
                $query .= " WHERE userid='$user_id'";
            }

            $query .= " ORDER BY id $order LIMIT $offset, $limit";

            $entries = $wpdb->get_results($query);


            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_credits WHERE userid='$user_id' AND type='$type'");
        }

        $entriesX = $entries;


        foreach ($entries as $index => $entry) {


            $entry_userid = isset($entry->userid) ? $entry->userid : 0;
            $entry_user = get_user_by('id', $entry_userid);
            $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
            $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        }


        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }













    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_tasks($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);


        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_validation_tasks = $prefix . 'cstore_validation_tasks';
        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));


        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table_validation_tasks ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks ORDER BY id");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table_validation_tasks WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks WHERE userid='$user_id' ORDER BY id");
        }



        //$total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks");

        $entriesX = $entries;
        foreach ($entries as $index => $entry) {

            $task_id = isset($entry->id) ? $entry->id : 0;
            $task_userid = isset($entry->userid) ? $entry->userid : 0;
            $total_entries = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks_entries WHERE task_id=$task_id");
            $total_completed = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks_entries WHERE task_id=$task_id AND status='completed'");
            $total_pending = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_validation_tasks_entries WHERE task_id=$task_id AND status='pending'");

            $entriesX[$index]->total = $total_entries;
            $entriesX[$index]->completed = $total_completed;
            $entriesX[$index]->pending = $total_pending;


            $task_user = get_user_by('id', $task_userid);

            $entriesX[$index]->user_name = $task_user->display_name;
        }



        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_tasks_entries($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $task_id     = isset($request['task_id']) ? sanitize_text_field($request['task_id']) : 0;
        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 20;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";

        $status               = (isset($request['status']) && !empty($request['status'])) ? sanitize_text_field($request['status']) : null;
        $safeToSend           = (isset($request['safeToSend']) && !empty($request['safeToSend'])) ? sanitize_text_field($request['safeToSend']) : null;
        $isSyntaxValid        = (isset($request['isSyntaxValid']) && !empty($request['isSyntaxValid'])) ? sanitize_text_field($request['isSyntaxValid']) : null;
        $isValidEmail         = (isset($request['isValidEmail']) && !empty($request['isValidEmail'])) ? sanitize_text_field($request['isValidEmail']) : null;
        $hasValidDomain       = (isset($request['hasValidDomain']) && !empty($request['hasValidDomain'])) ? sanitize_text_field($request['hasValidDomain']) : null;
        $isDisposableDomain   = (isset($request['isDisposableDomain']) && !empty($request['isDisposableDomain'])) ? sanitize_text_field($request['isDisposableDomain']) : null;
        $isInboxFull          = (isset($request['isInboxFull']) && !empty($request['isInboxFull'])) ? sanitize_text_field($request['isInboxFull']) : null;
        $isFreeEmailProvider  = (isset($request['isFreeEmailProvider']) && !empty($request['isFreeEmailProvider'])) ? sanitize_text_field($request['isFreeEmailProvider']) : null;
        $isGibberishEmail     = (isset($request['isGibberishEmail']) && !empty($request['isGibberishEmail'])) ? sanitize_text_field($request['isGibberishEmail']) : null;
        $checkDomainReputation = (isset($request['checkDomainReputation']) && !empty($request['checkDomainReputation'])) ? sanitize_text_field($request['checkDomainReputation']) : null;
        $isSMTPBlacklisted    = (isset($request['isSMTPBlacklisted']) && !empty($request['isSMTPBlacklisted'])) ? sanitize_text_field($request['isSMTPBlacklisted']) : null;
        $isRoleBasedEmail     = (isset($request['isRoleBasedEmail']) && !empty($request['isRoleBasedEmail'])) ? sanitize_text_field($request['isRoleBasedEmail']) : null;
        $isCatchAllDomain     = (isset($request['isCatchAllDomain']) && !empty($request['isCatchAllDomain'])) ? sanitize_text_field($request['isCatchAllDomain']) : null;
        $verifySMTP           = (isset($request['verifySMTP']) && !empty($request['verifySMTP'])) ? sanitize_text_field($request['verifySMTP']) : null;




        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_name = $prefix . 'cstore_validation_tasks_entries';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_name WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order LIMIT $offset, $limit");

        // $entries = $wpdb->get_results("SELECT * FROM $table_name WHERE task_id=$task_id ORDER BY id $order LIMIT $offset, $limit");



        $where = ['task_id' => $task_id];
        $conditions = ['task_id = %d'];
        $params = [$task_id];

        if ($status) {
            $conditions[] = "JSON_CONTAINS(result, %s, '$.status')";
            $params[] = json_encode($status);
        }

        if (!is_null($safeToSend)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.safeToSend')) = %s";
            $params[] = $safeToSend;
        }

        if (!is_null($hasValidDomain)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.hasValidDomain')) = %d";
            $params[] = (int) $hasValidDomain;
        }

        if (!is_null($isSyntaxValid)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSyntaxValid')) = %d";
            $params[] = (int) $isSyntaxValid;
        }

        if (!is_null($isValidEmail)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isValidEmail')) = %d";
            $params[] = (int) $isValidEmail;
        }

        if (!is_null($isDisposableDomain)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isDisposableDomain')) = %d";
            $params[] = (int) $isDisposableDomain;
        }

        if (!is_null($isInboxFull)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isInboxFull')) = %d";
            $params[] = (int) $isInboxFull;
        }

        if (!is_null($isFreeEmailProvider)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isFreeEmailProvider')) = %d";
            $params[] = (int) $isFreeEmailProvider;
        }

        if (!is_null($isGibberishEmail)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isGibberishEmail')) = %d";
            $params[] = (int) $isGibberishEmail;
        }

        if (!is_null($checkDomainReputation)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.checkDomainReputation')) = %d";
            $params[] = (int) $checkDomainReputation;
        }

        if (!is_null($isSMTPBlacklisted)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSMTPBlacklisted')) = %d";
            $params[] = (int) $isSMTPBlacklisted;
        }
        if (!is_null($isRoleBasedEmail)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isRoleBasedEmail')) = %d";
            $params[] = (int) $isRoleBasedEmail;
        }
        if (!is_null($isCatchAllDomain)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.isCatchAllDomain')) = %d";
            $params[] = (int) $isCatchAllDomain;
        }
        if (!is_null($verifySMTP)) {
            $conditions[] = "JSON_UNQUOTE(JSON_EXTRACT(result, '$.verifySMTP')) = %d";
            $params[] = (int) $verifySMTP;
        }





        $query = "SELECT id, email, result, status FROM $table_name WHERE " . implode(" AND ", $conditions) . " ORDER BY id $order LIMIT %d, %d";
        $params[] = (int) $offset;
        $params[] = (int) $limit;







        // $query = "SELECT email, result, status FROM $table_name WHERE " . implode(" AND ", $conditions);
        $prepared_query = $wpdb->prepare($query, ...$params);
        $results = $wpdb->get_results($prepared_query, ARRAY_A);



        $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_name WHERE task_id=$task_id ORDER BY id $order LIMIT $offset, $limit");

        // $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_name WHERE task_id=$task_id");
        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $results;


        die(wp_json_encode($response));
    }












    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function add_email_to_task($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        $EmailValidationAPIKey = new EmailValidationAPIKey();
        $apiData = $EmailValidationAPIKey->get_apikey_data($token);


        $user_id = isset($apiData['userid']) ? $apiData['userid'] : 0;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $task_id     = isset($request['task_id']) ? sanitize_text_field($request['task_id']) : 0;
        $emails     = isset($request['emails']) ? sanitize_text_field($request['emails']) : '';







        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';

        $response = [];
        $emailArray = explode(" ", trim($emails));

        // Remove duplicate emails (optional)
        $emailArray = array_unique($emailArray);




        $values = [];
        $data = [];


        // Convert to desired format
        // $data = array_map(function ($user_id, $task_id, $email) {
        //     return [$user_id, $task_id, $email]; // Assigning sequential numbers as second values
        // }, [$user_id], [$task_id], $emailArray,);

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        foreach ($emailArray as $email) {

            $data[] = [$user_id, $task_id, $email, '', 'pending', $datetime];
        }



        foreach ($data as $row) {
            $values[] = $wpdb->prepare("(%d, %d, %s, %s, %s, %s)", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
        }

        $sql = "INSERT INTO $table_validation_tasks_entries (userid, task_id, email, result, status, datetime) VALUES " . implode(',', $values);
        $wpdb->query($sql);



        $response['errors'] = false;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function add_tasks_entries($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $task_id     = isset($request['task_id']) ? sanitize_text_field($request['task_id']) : 0;
        $emails     = isset($request['emails']) ? sanitize_text_field($request['emails']) : '';





        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';

        $response = [];
        $emailArray = explode(" ", trim($emails));

        $email_counts = array_count_values($emailArray);

        // Calculate total duplicates
        $total_duplicates = array_sum(array_map(fn($count) => $count > 1 ? $count - 1 : 0, $email_counts));


        // $EmailValidationObjectMeta = new EmailValidationObjectMeta();
        //$duplicate_count = $EmailValidationObjectMeta->get_meta('task', $task_id, "duplicate_count");

        //$EmailValidationObjectMeta->update_meta('task', $task_id, "duplicate_count", $total_duplicates);


        // Remove duplicate emails (optional)
        $emailArray = array_unique($emailArray);


        $EmailVerifier = new EmailVerifier();


        $values = [];
        $data = [];


        // Convert to desired format
        // $data = array_map(function ($user_id, $task_id, $email) {
        //     return [$user_id, $task_id, $email]; // Assigning sequential numbers as second values
        // }, [$user_id], [$task_id], $emailArray,);

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        foreach ($emailArray as $email) {
            //$response = $EmailVerifier->verifyEmail($email);
            //$result = wp_json_encode($response);
            $data[] = [$user_id, $task_id, $email, '', 'pending', $datetime];
        }



        foreach ($data as $row) {
            $values[] = $wpdb->prepare("(%d, %d, %s, %s, %s, %s)", $row[0], $row[1], $row[2], $row[3], $row[4], $row[5]);
        }

        $sql = "INSERT INTO $table_validation_tasks_entries (userid, task_id, email, result, status, datetime) VALUES " . implode(',', $values);
        $wpdb->query($sql);



        $response['errors'] = false;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_task($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks = $prefix . 'cstore_validation_tasks';

        $response = [];

        $values = [];
        $data = [];


        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        $taskRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_validation_tasks WHERE id = %d",
                $id
            ),
            ARRAY_A
        );


        $response = $taskRow;


        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_task_report($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $task_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_validation_tasks = $prefix . 'cstore_validation_tasks';
        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';

        $response = [];

        $values = [];
        $data = [];


        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        // $taskRow = $wpdb->get_row(
        //     $wpdb->prepare(
        //         "SELECT * FROM $table_validation_tasks WHERE id = %d",
        //         $id
        //     ),
        //     ARRAY_A
        // );


        global $wpdb;



        $query = $wpdb->prepare("
    SELECT 
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.safeToSend')) = 'no') AS safeToSend,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSyntaxValid')) = 'no') AS isSyntaxValid,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.hasValidDomain')) = 'no') AS hasValidDomain,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.safeToSend')) = 'no') AS safeToSend,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSyntaxValid')) = 'no') AS isSyntaxValid,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isValidEmail')) = 'no') AS isValidEmail,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.hasValidDomain')) = 'no') AS hasValidDomain,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isDisposableDomain')) = 'yes') AS isDisposableDomain,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isInboxFull')) = 'yes') AS isInboxFull,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isFreeEmailProvider')) = 'yes') AS isFreeEmailProvider,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isGibberishEmail')) = 'yes') AS isGibberishEmail,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSMTPBlacklisted')) = 'yes') AS isSMTPBlacklisted,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isRoleBasedEmail')) = 'yes') AS isRoleBasedEmail,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isCatchAllDomain')) = 'yes') AS isCatchAllDomain,
        SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.verifySMTP')) = 'yes') AS verifySMTP,
        SUM(status = 'completed') AS completeCount,
        SUM(status = 'pending') AS pendingCount
    FROM $table_validation_tasks_entries
    WHERE task_id = %d
", $task_id, $task_id);


        $results = $wpdb->get_row($query, ARRAY_A);

        // $task_id = 123; // Example task_id

        // $query = $wpdb->prepare("
        //     SELECT 
        //         SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.safeToSend')) = 'yes') AS safeToSend,
        //         SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isSyntaxValid')) = 'true') AS isSyntaxValid,
        //         SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.hasValidDomain')) = 'true') AS hasValidDomain,
        //         SUM(JSON_UNQUOTE(JSON_EXTRACT(result, '$.isDisposableDomain')) = 'true') AS isDisposableDomain,
        //         SUM(status = 'complete') AS completeCount,
        //         SUM(status = 'pending') AS pendingCount
        //     FROM $table_validation_tasks_entries
        //     WHERE task_id = %d
        // ", $task_id);

        // $results = $wpdb->get_row($query, ARRAY_A);

        // // Output as JSON
        // echo json_encode($results, JSON_PRETTY_PRINT);





        //$response = $taskRow;


        die(wp_json_encode($results));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function update_task($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;
        $title     = isset($request['title']) ? sanitize_text_field($request['title']) : '';
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : '';


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_validation_tasks';

        $response = [];

        $values = [];
        $data = [];


        $updated_data = [];

        if ($status) {
            $updated_data['status'] = $status;
        }
        if ($title) {
            $updated_data['title'] = $title;
        }

        // $updated_data = array(
        //     // 'title' => $status,

        // );
        $where = array('id' => $id);

        $updated = $wpdb->update($table, $updated_data, $where);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function bulk_update_tasks($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : '';


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_validation_tasks';

        $response = [];

        $values = [];
        $data = [];



        if (!empty($ids)) {
            foreach ($ids as $id) {
                $updated_data = array(
                    'status' => $status,
                );
                $where = array('id' => $id);

                $updated = $wpdb->update($table, $updated_data, $where);
            }
        }




        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function update_object_meta($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;
        $object     = isset($request['object']) ? sanitize_text_field($request['object']) : 0;
        $key     = isset($request['key']) ? sanitize_text_field($request['key']) : 0;
        $value     = isset($request['value']) ? sanitize_text_field($request['key']) : 0;


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_validation_tasks';

        $response = [];

        $values = [];
        $data = [];



        $updated_data = array(
            // 'status' => $status,

        );
        $where = array('id' => $id);

        $updated = $wpdb->update($table, $updated_data, $where);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function duplicate_object($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;
        $object     = isset($request['object']) ? sanitize_text_field($request['object']) : '';

        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_validation_tasks';

        $response = [];

        $values = [];
        $data = [];



        $updated_data = array(
            // 'status' => $status,

        );
        $where = array('id' => $id);

        $updated = $wpdb->update($table, $updated_data, $where);


        die(wp_json_encode($response));
    }


















    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function validation_requests($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $id     = isset($request['id']) ? absint($request['id']) : '';
        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table_requests = $prefix . 'cstore_api_requests';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));




        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table_requests ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_requests");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table_requests WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_requests WHERE userid='$user_id'");
        }


        $entriesX = $entries;


        foreach ($entries as $index => $entry) {


            $entry_userid = isset($entry->userid) ? $entry->userid : 0;
            $entry_user = get_user_by('id', $entry_userid);
            $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
            $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        }

        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;


        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function api_requests($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $id     = isset($request['id']) ? absint($request['id']) : '';
        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table_requests = $prefix . 'cstore_api_requests';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));




        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table_requests WHERE apikeyid='$id' ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_requests WHERE apikeyid='$id'");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table_requests WHERE userid='$user_id' AND apikeyid='$id' ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_requests WHERE userid='$user_id' AND apikeyid='$id'");
        }


        $entriesX = $entries;


        foreach ($entries as $index => $entry) {


            $entry_userid = isset($entry->userid) ? $entry->userid : 0;
            $entry_user = get_user_by('id', $entry_userid);
            $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
            $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        }

        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;


        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_spammerX($request)
    {

        //error_log("create_spammer");

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');
        $apiKey     = isset($request['apiKey']) ? sanitize_email($request['apiKey']) : '';



        if (!$apiKey) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        //$token = str_replace('Bearer ', '', $token);

        // // Decode the token
        // try {
        //     $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        // } catch (Exception $e) {
        //     return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        // }


        // // Get user by ID
        // // $user = get_user_by('id', $decoded_token->sub);



        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }


        $EmailValidationAPIKey = new EmailValidationAPIKey();
        $apiData = $EmailValidationAPIKey->get_apikey_data($apiKey);


        $user_id = isset($apiData['userid']) ? $apiData['userid'] : 0;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }








        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $website     = isset($request['website']) ? esc_url($request['website']) : '';
        $content     = isset($request['content']) ? sanitize_text_field($request['content']) : '';


        $EmailValidationSpammers = new EmailValidationSpammers();

        $response = [];

        $prams = [];

        if (!empty($email)) {
            $prams['email'] = $email;
        }
        if (!empty($name)) {
            $prams['name'] = $name;
        }
        if (!empty($website)) {
            $prams['website'] = $website;
        }
        if (!empty($content)) {
            $prams['content'] = $content;
        }




        $response = $EmailValidationSpammers->ceate_spammer($prams);

        //error_log(serialize($response));

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_spammers($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';
        $domain     = isset($request['domain']) ? sanitize_text_field($request['domain']) : '';

        //error_log("domain: $domain");

        global $wpdb;

        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));








        if ($isAdmin) {
            $sql = "SELECT * FROM $table";
            $where = [];
            $params = [];


            // Add condition for domain if it's not empty
            // if (!empty($domain)) {
            //     $where[] = "JSON_CONTAINS(domains, %s)";
            //     $params[] = json_encode($domain);
            // }

            if (!empty($userid)) {
                $where[] = "domains = %d";
                $params[] = $domain;
            }

            // If we have any WHERE conditions, join them with AND
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // Append ORDER BY and LIMIT
            $sql .= " ORDER BY id $order LIMIT %d, %d";
            $params[] = $offset;
            $params[] = $limit;

            // Prepare and execute query
            $query = $wpdb->prepare($sql, ...$params);
            $entries = $wpdb->get_results($query);

            //error_log(sprintf($sql, ...$params));

            $total = $wpdb->get_var(sprintf($sql, ...$params));
        } else {




            $sql = "SELECT * FROM $table";
            $where = [];
            $params = [];

            // Add condition for user ID if it's not empty
            if (!empty($userid)) {
                $where[] = "userid = %d";
                $params[] = $userid;
            }

            if (!empty($userid)) {
                $where[] = "domains = %d";
                $params[] = $domain;
            }

            // Add condition for domain if it's not empty
            // if (!empty($domain)) {
            //     $where[] = "JSON_CONTAINS(domains, %s)";
            //     $params[] = json_encode($domain);
            // }

            // If we have any WHERE conditions, join them with AND
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }

            // Append ORDER BY and LIMIT
            $sql .= " ORDER BY id $order LIMIT %d, %d";
            $params[] = $offset;
            $params[] = $limit;

            // Prepare and execute query
            $query = $wpdb->prepare($sql, ...$params);
            $entries = $wpdb->get_results($query);


            // $total = count($entries);


            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table WHERE userid='$user_id'");
        }


        $entriesX = $entries;

        ////error_log(serialize($entries));

        // foreach ($entries as $index => $entry) {


        //     $entry_userid = isset($entry->userid) ? $entry->userid : 0;
        //     $entry_user = get_user_by('id', $entry_userid);
        //     $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
        //     $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        // }

        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;


        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_spammer_domains($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');



        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);


        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $user = get_user_by('id', $user_id);

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_spammers_domain';

        $response = [];
        $offset = ($page - 1) * $limit;

        $last_day = date("Y-m-d");
        $first_date = date("Y-m-d", strtotime("7 days ago"));

        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table WHERE userid='$user_id' ORDER BY id $order LIMIT $offset, $limit");

            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table WHERE userid='$user_id'");
        }


        $entriesX = $entries;


        // foreach ($entries as $index => $entry) {


        //     $entry_userid = isset($entry->userid) ? $entry->userid : 0;
        //     $entry_user = get_user_by('id', $entry_userid);
        //     $username = isset($entry_user->display_name) ? $entry_user->display_name : '';
        //     $entriesX[$index]->username = $username . " (#" . $entry_userid . ")";
        // }

        $max_pages = ceil($total / $limit);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;


        $response['posts'] = $entriesX;


        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function register_user($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $password     = isset($request['password']) ? sanitize_text_field($request['password']) : '';


        $EmailValidationRegister = new EmailValidationRegister();

        $response = [];


        $response = $EmailValidationRegister->create_user($email, $password);

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_api_key($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $title     = isset($request['title']) ? sanitize_text_field($request['title']) : '';


        $EmailValidationAPIKey = new EmailValidationAPIKey();

        $response = [];


        $response = $EmailValidationAPIKey->ceate_api_key($title, $user_id);

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_credits($request)
    {

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $userid     = isset($request['userid']) ? sanitize_text_field($request['userid']) : $user_id;
        $amount     = isset($request['amount']) ? sanitize_text_field($request['amount']) : 100;
        $source     = isset($request['source']) ? sanitize_text_field($request['source']) : 'dashboard'; // codes. redeem, 

        $credit_type = '';
        $notes = '';

        $response = [];

        $EmailValidationCredits = new EmailValidationCredits();


        $response = $EmailValidationCredits->add_credit($userid, $amount, $credit_type, $source, $notes);

        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_task($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $title     = isset($request['title']) ? sanitize_text_field($request['title']) : '';
        $status     = isset($request['status']) ? sanitize_text_field($request['status']) : 'pending';



        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $table = $wpdb->prefix . "cstore_validation_tasks";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
			( userid, title, status, datetime )
			VALUES	( %s, %s, %s, %s)",
            array($user_id, $title, $status, $datetime)
        ));

        $inserted =  $wpdb->insert_id;

        if ($inserted) {
            $response['id'] = $inserted;
            $response['success'] = true;
        }

        if (!$inserted) {
            $response['errors'] = true;
        }

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_tasks($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $table = $wpdb->prefix . "cstore_validation_tasks";
        $table_validation_tasks_entries = $wpdb->prefix . 'cstore_validation_tasks_entries';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                $wpdb->delete($table_validation_tasks_entries, array('task_id' => $id), array('%d'));
                $wpdb->delete($table, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_api_keys($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $table = $wpdb->prefix . 'cstore_api_keys';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                $wpdb->delete($table, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_credits($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $table = $wpdb->prefix . 'cstore_credits';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                $wpdb->delete($table, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_orders($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_orders';
        // $table_orders_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                //$wpdb->delete($table_subscriptions, array('task_id' => $id), array('%d'));
                $wpdb->delete($table_orders, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_subscriptions($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_subscriptions = $prefix . 'cstore_subscriptions';

        // $table_orders = $prefix . 'cstore_orders';
        // $table_orders_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        if (!empty($ids)) {

            foreach ($ids as $id) {

                $wpdb->delete($table_subscriptions, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_tasks_entries($request)
    {



        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $table_validation_tasks_entries = $wpdb->prefix . 'cstore_validation_tasks_entries';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                $wpdb->delete($table_validation_tasks_entries, array('id' => $id), array('%d'));
            }
        }

        $response['success'] = true;


        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function email_export($request)
    {

        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');


        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $task_id     = isset($request['task_id']) ? ($request['task_id']) : [];
        $queryPrams     = isset($request['queryPrams']) ? ($request['queryPrams']) : [];



        $EmailValidationExport = new EmailValidationExport();

        $url = $EmailValidationExport->export_email_validation_data($task_id, $queryPrams);

        $response['success'] = true;
        $response['file'] = esc_url_raw($url);

        // if ($inserted) {
        //     $response['id'] = $inserted;
        //     $response['success'] = true;
        // }

        // if (!$inserted) {
        //     $response['errors'] = true;
        // }

        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function delete_api_key($request)
    {



        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }



        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';


        $EmailValidationAPIKey = new EmailValidationAPIKey();

        $response = [];


        $response = $EmailValidationAPIKey->delete_api_key($id);


        die(wp_json_encode($response));
    }






    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function validate_email_by_user($request)
    {

        $response = [];


        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');



        $token = $request->get_header('Authorization');


        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        // Remove "Bearer " prefix if present
        $token = str_replace('Bearer ', '', $token);

        // Decode the token
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $testType     = isset($request['testType']) ? sanitize_text_field($request['testType']) : 'short';




        $EmailVerifier = new EmailVerifier();
        $EmailValidationAPIRequests = new EmailValidationAPIRequests();


        $response = $EmailVerifier->verifyEmail($email, $testType);
        $EmailValidationAPIRequests->add_request($token, $email, $response);



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function validate_email($request)
    {

        $response = [];


        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);






        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $apiKey     = isset($request['apiKey']) ? sanitize_text_field($request['apiKey']) : '';
        $testType     = isset($request['testType']) ? sanitize_text_field($request['testType']) : 'short';


        $EmailValidationAPIKey = new EmailValidationAPIKey();
        $apiData = $EmailValidationAPIKey->get_apikey_data($apiKey);


        $user_id = isset($apiData['userid']) ? $apiData['userid'] : 0;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $EmailVerifier = new EmailVerifier();
        $EmailValidationAPIRequests = new EmailValidationAPIRequests();


        $response = $EmailVerifier->verifyEmail($email, $testType);
        $EmailValidationAPIRequests->add_request($apiKey, $email, $response);



        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function create_spammer($request)
    {

        $response = [];


        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);






        $apiKey     = isset($request['apiKey']) ? sanitize_text_field($request['apiKey']) : '';



        $EmailValidationAPIKey = new EmailValidationAPIKey();
        $apiData = $EmailValidationAPIKey->get_apikey_data($apiKey);


        $user_id = isset($apiData['userid']) ? $apiData['userid'] : 0;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        //error_log($apiKey);

        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $website     = isset($request['website']) ? esc_url($request['website']) : '';
        $content     = isset($request['content']) ? sanitize_text_field($request['content']) : '';



        $EmailValidationSpammers = new EmailValidationSpammers();

        $response = [];

        $prams = [];

        if (!empty($email)) {
            $prams['email'] = $email;
        }
        if (!empty($name)) {
            $prams['name'] = $name;
        }
        if (!empty($website)) {
            $prams['website'] = $website;
        }
        if (!empty($content)) {
            $prams['content'] = $content;
        }




        $response = $EmailValidationSpammers->ceate_spammer($prams);

        //error_log(serialize($response));

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function check_spammer($request)
    {

        $response = [];


        // header('Access-Control-Allow-Origin: *');
        // header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        // header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");


        // $token = $request->get_header('Authorization');


        // if (!$token) {
        //     return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        // }

        // // Remove "Bearer " prefix if present
        // $token = str_replace('Bearer ', '', $token);






        $apiKey     = isset($request['apiKey']) ? sanitize_text_field($request['apiKey']) : '';



        $EmailValidationAPIKey = new EmailValidationAPIKey();
        $apiData = $EmailValidationAPIKey->get_apikey_data($apiKey);


        $user_id = isset($apiData['userid']) ? $apiData['userid'] : 0;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }

        //error_log($apiKey);

        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $website     = isset($request['website']) ? esc_url($request['website']) : '';
        $content     = isset($request['content']) ? sanitize_text_field($request['content']) : '';



        $EmailValidationSpammers = new EmailValidationSpammers();

        $response = [];

        $prams = [];

        if (!empty($email)) {
            $prams['email'] = $email;
        }
        if (!empty($name)) {
            $prams['name'] = $name;
        }
        if (!empty($website)) {
            $prams['website'] = $website;
        }
        if (!empty($content)) {
            $prams['content'] = $content;
        }




        $has_spammer = $EmailValidationSpammers->has_spammer($prams);

        if ($has_spammer) {
            $response['found'] = true;
        } else {
            $response['found'] = false;
        }

        ////error_log($response);

        die(wp_json_encode($response));
    }









    /**
     * Handle source links from scraper
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request data.
     */
    public function add_source_links($request)
    {
        $response = [];

        // Get the posted data
        $links = $request->get_json_params();

        if (empty($links) || !is_array($links)) {
            return new WP_Error('invalid_data', 'Invalid or empty data received', array('status' => 400));
        }

        global $wpdb;
        $table = $wpdb->prefix . "cstore_source_links";
        $datetime = current_time('mysql');

        // Prepare batch insert
        $values = [];
        $placeholders = [];
        $query_args = [];

        foreach ($links as $link) {
            if (!empty($link['link'])) {
                $placeholders[] = "(%s, %s, %s)";
                array_push(
                    $query_args,
                    $link['link'],
                    $link['target'],
                    $datetime
                );
            }
        }

        if (!empty($placeholders)) {
            $query = "INSERT INTO " . $table . " 
                     (link, target, datetime) 
                     VALUES " . implode(', ', $placeholders);

            $prepared_query = $wpdb->prepare($query, $query_args);
            $result = $wpdb->query($prepared_query);

            if ($result === false) {
                $response['success'] = false;
                $response['message'] = 'Failed to insert links';
            } else {
                $response['success'] = true;
                $response['message'] = 'Links saved successfully';
                $response['count'] = count($links);
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'No valid links to insert';
        }

        return rest_ensure_response($response);
    }
}


new EmailValidationRest();
