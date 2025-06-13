<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use WP_REST_Request;
use Exception;
use WC_Order_Query;
use WC_Order;
use WP_Query;
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
            '/get_medias',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_medias'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_terms',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_terms'),
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
            '/delete_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_products'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/add_product',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_product'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/update_product',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_product'),
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
    public function get_medias($request)
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


        $paged     = isset($request['page']) ? absint($request['page']) : 1;
        $limit     = isset($request['limit']) ? absint($request['limit']) : 20;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";


        $query_args = [];

        $query_args['post_type'] = 'attachment';
        $query_args['post_status'] = 'inherit';

        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($limit)) {
            $query_args['posts_per_page'] = $limit;
        }

        // if (!empty($orderby)) {
        //     $query_args['orderby'] = $orderby;
        // }
        // if (!empty($order)) {
        //     $query_args['order'] = $order;
        // }




        $posts = [];
        $wp_query = new WP_Query($query_args);
        if ($wp_query->have_posts()) :
            while ($wp_query->have_posts()) :
                $wp_query->the_post();
                $post_data = get_post(get_the_id());
                $post_id = $post_data->ID;


                $posts[] = [
                    "title" => $post_data->post_title,
                    "src" => wp_get_attachment_url($post_id),
                    "id" => $post_id,

                ];
            endwhile;
            wp_reset_query();
            wp_reset_postdata();
        endif;



        $response['posts'] = $posts;

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_terms($request)
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


        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : '';


        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 20,
        ));





        $category_tree = build_category_tree($terms);



        $response['terms'] = $category_tree;


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
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";


        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'product';
        $query_args['post_status'] = 'any';


        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($paged)) {
            $query_args['s'] = $keyword;
        }


        if (!empty($limit)) {
            $query_args['posts_per_page'] = $limit;
        }
        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($orderby)) {
            $query_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $query_args['order'] = $order;
        }
        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }
        if (!empty($tax_query)) {
            $query_args['tax_query'] = $tax_query;
        }

        $wp_query = new WP_Query($query_args);


        $entries = [];

        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                $postData = get_post($post_id);


                $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
                $featured = get_post_meta($post_id, 'featured', true);
                $price = get_post_meta($post_id, 'price', true);
                $sku = get_post_meta($post_id, 'sku', true);
                $oldPrice = get_post_meta($post_id, 'oldPrice', true);
                $stockStatus = get_post_meta($post_id, 'stockStatus', true);
                $stockCount = get_post_meta($post_id, 'stockCount', true);
                $gallery = get_post_meta($post_id, 'gallery', true);
                $addons = get_post_meta($post_id, 'addons', true);
                $downloads = get_post_meta($post_id, 'downloads', true);
                $weight = get_post_meta($post_id, 'weight', true);
                $length = get_post_meta($post_id, 'length', true);
                $width = get_post_meta($post_id, 'width', true);
                $height = get_post_meta($post_id, 'height', true);
                $upsells = get_post_meta($post_id, 'upsells', true);
                $crosssells = get_post_meta($post_id, 'crosssells', true);
                $faq = get_post_meta($post_id, 'faq', true);
                $relatedProducts = get_post_meta($post_id, 'relatedProducts', true);
                $variations = get_post_meta($post_id, 'variations', true);

                $categories = [];
                $tags = [];
                $brands = [];
                $brands = [];

                $entries[] = [
                    "id" => $postData->ID,
                    "title" => $postData->post_title,
                    "status" => $postData->post_status,
                    "sku" => $sku,
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "price" => $price,
                    "oldPrice" => $oldPrice,
                    "categories" => $categories,
                    "tags" => $tags,
                    "brands" => $brands,
                    "stockStatus" => $stockStatus,
                    "stockCount" => $stockCount,
                    "gallery" => $gallery,
                    "addons" => $addons,
                    "downloads" => $downloads,
                    "weight" => $weight,
                    "length" => $length,
                    "width" => $width,
                    "height" => $height,
                    "upsells" => $upsells,
                    "crosssells" => $crosssells,
                    "faq" => $faq,
                    "relatedProducts" => $relatedProducts,
                    "variations" => $variations,
                ];

            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = 10;


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


        $post_id     = isset($request['id']) ? absint($request['id']) : 1;



        $productData = [];

        $postData = get_post($post_id);


        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
        $featured = get_post_meta($post_id, 'featured', true);
        $priceType = get_post_meta($post_id, 'priceType', true);
        $price = get_post_meta($post_id, 'price', true);
        $oldPrice = get_post_meta($post_id, 'oldPrice', true);
        $menuOrder = get_post_meta($post_id, 'menuOrder', true);

        $sku = get_post_meta($post_id, 'sku', true);
        $stockStatus = get_post_meta($post_id, 'stockStatus', true);
        $stockCount = get_post_meta($post_id, 'stockCount', true);
        $gallery = get_post_meta($post_id, 'gallery', true);
        $addons = get_post_meta($post_id, 'addons', true);


        $downloads = get_post_meta($post_id, 'downloads', true);

        $weight = get_post_meta($post_id, 'weight', true);

        $length = get_post_meta($post_id, 'length', true);
        $width = get_post_meta($post_id, 'width', true);
        $height = get_post_meta($post_id, 'height', true);
        $upsells = get_post_meta($post_id, 'upsells', true);
        $crosssells = get_post_meta($post_id, 'crosssells', true);
        $faq = get_post_meta($post_id, 'faq', true);
        $relatedProducts = get_post_meta($post_id, 'relatedProducts', true);
        $variations = get_post_meta($post_id, 'variations', true);

        $categories = get_the_terms($post_id, 'product_cat');
        $categories = $categories ? $categories : [];

        $tags = get_the_terms($post_id, 'product_tag');
        $tags = $tags ? $tags : [];

        $brands = [];
        $brands = [];


        $categoriesData = [];
        foreach ($categories as $index => $category) {

            $categoriesData[$index] = $category->term_id;
        }


        $tagsData = [];
        foreach ($tags as $index => $tag) {

            $tagsData[$index] = [
                "term_id" => $tag->term_id,
                "name" => $tag->name
            ];
        }


        $post_thumbnail = [
            "id" => "",
            "title" => "",
            "src" => $post_thumbnail_url,
        ];

        $productData = [
            "id" => $postData->ID,
            "title" => $postData->post_title,
            "post_content" => $postData->post_content,
            "post_excerpt" => $postData->post_excerpt,
            "slug" => $postData->post_name,
            "postStatus" => $postData->post_status,
            "featured" => $featured,
            "sku" => $sku,
            "priceType" => $priceType,
            "price" => $price,
            "oldPrice" => $oldPrice,
            "stockStatus" => $stockStatus,
            "stockCount" => $stockCount,
            "menuOrder" => $menuOrder,

            "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
            "tags" => !empty($tags) ? $tags : [],
            "categories" => !empty($categories) ? $categories : [],
            "brands" => !empty($brands) ? $brands : [],
            "addons" => !empty($addons) ? $addons : ["manageStock", "gallery", "dimensions", "categories", "tags"],
            "faq" => !empty($faq) ? $faq : [],
            "relatedProducts" => !empty($relatedProducts) ? $relatedProducts : [],
            "crosssells" => !empty($crosssells) ? $crosssells : [],
            "upsells" => !empty($upsells) ? $upsells : [],
            "height" => !empty($height) ? $height : [],
            "width" => !empty($width) ? $width : [],
            "length" => !empty($length) ? $length : [],
            "weight" => !empty($weight) ? $weight : [],
            "downloads" => !empty($downloads) ? $downloads : [],
            "gallery" => !empty($gallery) ? $gallery : [],
            "variations" => !empty($variations) ? $variations : [],

        ];



        $response['product'] = $productData;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function add_product($request)
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


        $data = [
            'post_title'      => 'Sample Product Title',
            'post_type'      => 'product',
            'post_status'      => 'draft',

        ];



        $inserted = wp_insert_post($data);

        $response['success'] = true;


        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function update_product($request)
    {


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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);
        error_log(wp_json_encode($body_arr));


        $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_title = $request->get_param('title');
        $post_content = $request->get_param('post_content');
        $post_excerpt = $request->get_param('post_excerpt');
        $post_status = $request->get_param('postStatus');
        $comment_status = $request->get_param('comment_status');
        $post_parent = $request->get_param('post_parent');
        $menu_order = $request->get_param('menu_order');
        $categories = $request->get_param('categories');
        $tags = $request->get_param('tags');

        $tags = $tags ? $tags : [];
        $categories = $categories ? $categories : [];

        $post_thumbnail = $request->get_param('post_thumbnail');
        $post_thumbnail_id = isset($post_thumbnail['id']) ? $post_thumbnail['id'] : '';

        $featured = $request->get_param('featured');


        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[] = $tag['term_id'];
        }

        // error_log(wp_json_encode($tagIds));


        $response = [];
        $postData = [
            "ID" => $post_id,
            "post_title" => $post_title,
            "post_content" => $post_content,
            "post_excerpt" => $post_excerpt,
            "post_status" => $post_status,
            "comment_status" => $comment_status,
            "post_parent" => $post_parent,
            "menu_order" => $menu_order,
        ];
        wp_update_post($postData);

        wp_set_post_terms($post_id, $categories, 'product_cat');
        wp_set_post_terms($post_id, $tagIds, 'product_tag');

        if (!empty($post_thumbnail_id)) {
            set_post_thumbnail($post_id, $post_thumbnail_id);
        }


        if (!empty($body_arr['sku'])) {
            update_post_meta($post_id, 'sku', $body_arr['sku']);
        }
        if (!empty($body_arr['menuOrder'])) {
            update_post_meta($post_id, 'menuOrder', $body_arr['menuOrder']);
        }
        if (!empty($body_arr['featured'])) {
            update_post_meta($post_id, 'featured', $featured);
        }
        if (!empty($body_arr['priceType'])) {
            update_post_meta($post_id, 'priceType', $body_arr['priceType']);
        }
        if (!empty($body_arr['price'])) {

            update_post_meta($post_id, 'price', $body_arr['price']);
        }
        if (!empty($body_arr['oldPrice'])) {
            update_post_meta($post_id, 'oldPrice', $body_arr['oldPrice']);
        }
        if (!empty($body_arr['stockStatus'])) {
            update_post_meta($post_id, 'stockStatus', $body_arr['stockStatus']);
        }
        if (!empty($body_arr['stockCount'])) {
            update_post_meta($post_id, 'stockCount', $body_arr['stockCount']);
        }
        if (!empty($body_arr['gallery'])) {
            $gallery = maybe_json_decode($body_arr['gallery']);
            update_post_meta($post_id, 'gallery', $gallery);
        }
        if (!empty($body_arr['addons'])) {
            $addons = maybe_json_decode($body_arr['addons']);
            update_post_meta($post_id, 'addons', $addons);
        }
        if (!empty($body_arr['downloads'])) {
            $downloads = maybe_json_decode($body_arr['downloads']);
            update_post_meta($post_id, 'downloads', $downloads);
        }
        if (!empty($body_arr['weight'])) {
            $weight = maybe_json_decode($body_arr['weight']);
            update_post_meta($post_id, 'weight', $weight);
        }
        if (!empty($body_arr['length'])) {
            $length = maybe_json_decode($body_arr['length']);
            update_post_meta($post_id, 'length', $length);
        }
        if (!empty($body_arr['width'])) {
            $width = maybe_json_decode($body_arr['width']);
            update_post_meta($post_id, 'width', $width);
        }
        if (!empty($body_arr['height'])) {
            $height = maybe_json_decode($body_arr['height']);
            update_post_meta($post_id, 'height', $height);
        }
        if (!empty($body_arr['upsells'])) {
            $upsells = maybe_json_decode($body_arr['upsells']);
            update_post_meta($post_id, 'upsells', $upsells);
        }
        if (!empty($body_arr['crosssells'])) {
            $crosssells = maybe_json_decode($body_arr['crosssells']);
            update_post_meta($post_id, 'crosssells', $crosssells);
        }
        if (!empty($body_arr['faq'])) {
            $faq = maybe_json_decode($body_arr['faq']);
            update_post_meta($post_id, 'faq', $faq);
        }
        if (!empty($body_arr['relatedProducts'])) {
            $relatedProducts = maybe_json_decode($body_arr['relatedProducts']);
            update_post_meta($post_id, 'relatedProducts', $relatedProducts);
        }
        if (!empty($body_arr['variations'])) {
            $variations = maybe_json_decode($body_arr['variations']);
            update_post_meta($post_id, 'variations', $variations);
        }





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

        $page = ($page == 0) ? 1 : $page;


        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_api_requests';
        $table_validation_tasks_entries = $wpdb->prefix . 'cstore_validation_tasks_entries';

        // Get the date range (last 7 days)
        $days_ago = date('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = date('Y-m-d 23:59:59');

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
    public function delete_products($request)
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


        // Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        // $gmt_offset = get_option('gmt_offset');
        // $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        // global $wpdb;
        // $prefix = $wpdb->prefix;
        // $table_subscriptions = $prefix . 'cstore_subscriptions';

        // $table_orders = $prefix . 'cstore_orders';
        // $table_orders_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        if (!empty($ids)) {

            foreach ($ids as $id) {
                wp_delete_post($id, false);
                // $wpdb->delete($table_subscriptions, array('id' => $id), array('%d'));
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
