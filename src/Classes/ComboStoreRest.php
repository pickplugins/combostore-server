<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use WP_REST_Request;
use WP_User_Query;
use Exception;
use WP_User;
use stdClass;
use WP_Query;
use Parsedown;
use DateTime;
use WP_Comment_Query;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use ComboStore\Classes\ComboStoreExport;
use ComboStore\Classes\ComboStoreOrders;
use ComboStore\Classes\ComboStoreRegister;
use ComboStore\Classes\ComboStoreStats;
use ComboStore\Classes\ComboStoreLove;
use ComboStore\Classes\ComboStoreViews;
use ComboStore\Classes\ComboStoreObjectMeta;
use ComboStore\Classes\ComboStoreSubscriptionsToCall;
use ComboStore\Classes\ComboStoreProduct;
use ComboStore\Classes\ComboStoreDeliveries;
use ComboStore\Classes\ComboStoreSubscriptions;
use ComboStore\Classes\ComboStoreExpenses;
use ComboStore\Classes\ComboStoreTemplateParts;
use ComboStore\Classes\RedxCourier;
use ComboStore\Classes\ComboStorePages;
use ComboStore\Classes\ComboStoreShipping;
use ComboStore\Classes\ComboStoreSMS;
use ComboStore\Classes\ComboStoreStocks;
use ComboStore\Classes\ComboStorePayPal;


class ComboStoreRest
{
    function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }


    public function register_routes()
    {

        register_rest_route(
            'combo-store/v2',
            '/combo_store_connection',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'combo_store_connection'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/send_sms',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'send_sms'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/send_login_otp',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'send_login_otp'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/verify_login_otp',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'verify_login_otp'),
                'permission_callback' => '__return_true',
            )
        );










        register_rest_route(
            'combo-store/v2',
            '/search_purchase_items',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'search_purchase_items'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/search_expense_items',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'search_expense_items'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_top_object_from_orders',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_top_object_from_orders'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_top_customers',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_top_customers'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_pages',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_pages'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/create_page',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_page'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_redx_areas',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_redx_areas'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_top_selling_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_top_selling_products'),
                'permission_callback' => '__return_true',
            )
        );






        register_rest_route(
            'combo-store/v2',
            '/handle_external_login',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'handle_external_login'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/send_to_courier',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'send_to_courier'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/check_mobile_fraud',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'check_mobile_fraud'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_settings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_settings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/couriers_webhook',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'couriers_webhook'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_settings_public',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_settings_public'),
                // 'permission_callback' => '__return_true',
                'permission_callback' => function ($request) {

                    // $api_key = $request->get_header('X-API-KEY');
                    $api_key = $request->get_header('x-api-key');



                    if (!$api_key) {
                        return new WP_Error(
                            'no_api_key',
                            'API key missing',
                            ['status' => 401]
                        );
                    }

                    $settings = get_option('combo_store_settings');


                    $apiKeys = $settings['apiKeys'] ?? [];
                    $apiToken = $apiKeys['apiToken']['key'] ?? '';




                    if (!hash_equals($apiToken, $api_key)) {
                        return new WP_Error(
                            'invalid_api_key',
                            'Invalid API key',
                            ['status' => 403]
                        );
                    }

                    return true;
                },
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/update_order_status',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_order_status'),
                // 'permission_callback' => '__return_true',
                'permission_callback' => function ($request) {

                    $api_key = $request->get_header('x-api-key');



                    if (!$api_key) {
                        return new WP_Error(
                            'no_api_key',
                            'API key missing',
                            ['status' => 401]
                        );
                    }

                    $settings = get_option('combo_store_settings');


                    $apiKeys = $settings['apiKeys'] ?? [];
                    $apiToken = $apiKeys['apiToken']['key'] ?? [];



                    if (!hash_equals($apiToken, $api_key)) {
                        return new WP_Error(
                            'invalid_api_key',
                            'Invalid API key',
                            ['status' => 403]
                        );
                    }

                    return true;
                },
            )
        );











        register_rest_route(
            'combo-store/v2',
            '/update_settings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_settings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/active_header',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'active_header'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/active_footer',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'active_footer'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/active_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'active_template'),
                'permission_callback' => '__return_true',
            )
        );











        register_rest_route(
            'combo-store/v2',
            '/set_thumbnail_from_url',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'set_thumbnail_from_url'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/add_activity',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_activity'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/create_subscribe_to_call',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_subscribe_to_call'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/start_delivery',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'start_delivery'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/start_subscription',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'start_subscription'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/add_tracking',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_tracking'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/check_subscribe_to_call',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'check_subscribe_to_call'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_subscribe_to_calls',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_subscribe_to_calls'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/bulk_submit_product',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'bulk_submit_product'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/bulk_submit_users',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'bulk_submit_users'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/bulk_submit_blog',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'bulk_submit_blog'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_post',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_post'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_page',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_page'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_posts',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_posts'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/request_for_stock',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'request_for_stock'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/post_vote',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'post_vote'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/steadfast_create_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'steadfast_create_order'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/post_loved',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'post_loved'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/post_view',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'post_view'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_tickets',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_tickets'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/submit_ticket',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'submit_ticket'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/add_ticket',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_ticket'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/delete_tickets',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_tickets'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_page',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_page'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/duplicate_page',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'duplicate_page'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/update_page',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_page'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_post_type_objects',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_post_type_objects'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_post_types',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_post_types'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_ticket',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_ticket'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/update_ticket',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_ticket'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_comments',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_comments'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/moderate_comments',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'moderate_comments'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_order_notes',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_order_notes'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_order_note',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_order_note'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_users',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_users'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/submit_comment',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'submit_comment'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/add_order_note',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_order_note'),
                'permission_callback' => '__return_true',
            )
        );




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
            '/get_user_data',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_user_data'),
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
            '/get_stats_purchases',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_stats_purchases'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_stats_expenses',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_stats_expenses'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_stats_all',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_stats_all'),
                'permission_callback' => '__return_true',
            )
        );









        register_rest_route(
            'combo-store/v2',
            '/get_store_total_overviews',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_store_total_overviews'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_orders_stats',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_orders_stats'),
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
            '/delete_shipping_zones',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_shipping_zones'),
                'permission_callback' => '__return_true',
            )
        );





        register_rest_route(
            'combo-store/v2',
            '/duplicate_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'duplicate_order'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/delete_expenses',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_expenses'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_template_parts',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_template_parts'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_template'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/delete_purchases',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_purchases'),
                'permission_callback' => '__return_true',
            )
        );








        register_rest_route(
            'combo-store/v2',
            '/duplicate_product',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'duplicate_product'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/duplicate_media',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'duplicate_media'),
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
            '/delete_medias',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_medias'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/delete_users',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_users'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/update_product_bulk',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_product_bulk'),
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
            '/get_total_counts',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_total_counts'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_brands_by_terms',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_brands_by_terms'),
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
            '/add_term',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_term'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_terms',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_terms'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_term',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_term'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_term',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_term'),
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
            '/query_products_stocks',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'query_products_stocks'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_low_stock_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_low_stock_products'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/get_product_feed',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_product_feed'),
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
            '/delete_deliveries',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_deliveries'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_trackings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_trackings'),
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
            '/get_wishlists',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_wishlists'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_wishlist',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_wishlist'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/delete_wishlists',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_wishlists'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/add_wishlist',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_wishlist'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_coupons',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_coupons'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_coupon',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_coupon'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_coupon_details',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_coupon_details'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/add_coupon',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'add_coupon'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_coupon',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_coupon'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_delivery',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_delivery'),
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
            '/update_post',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_post'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/get_activities',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_activities'),
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
            '/get_shipping_zones',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_shipping_zones'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_shipping_methods',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_shipping_methods'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/find_shipping_methods',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'find_shipping_methods'),
                'permission_callback' => '__return_true',
            )
        );







        register_rest_route(
            'combo-store/v2',
            '/get_stocks_histories',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_stocks_histories'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/adjust_stock',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'adjust_stock'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_expenses',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_expenses'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_template_parts',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_template_parts'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_templates',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_templates'),
                'permission_callback' => '__return_true',
            )
        );







        register_rest_route(
            'combo-store/v2',
            '/get_purchases',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_purchases'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_purchase',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_purchase'),
                'permission_callback' => '__return_true',
            )
        );








        register_rest_route(
            'combo-store/v2',
            '/get_expense',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_expense'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/get_template_part',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_template_part'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_template'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_active_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_active_template'),
                'permission_callback' => '__return_true',
            )
        );
        /* The active site template's settings (fonts, colors) only - the storefront reads it on every page, so it must stay small. */
        register_rest_route(
            'combo-store/v2',
            '/get_template_settings',
            array(
                'methods'  => array('GET', 'POST'),
                'callback' => array($this, 'get_template_settings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_page_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_page_template'),
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
            '/get_shipping_zone',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_shipping_zone'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/get_shipping_method',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_shipping_method'),
                'permission_callback' => '__return_true',
            )
        );





        register_rest_route(
            'combo-store/v2',
            '/get_order_by_hash',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_order_by_hash'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_trackings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_trackings'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_deliveries',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_deliveries'),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            'combo-store/v2',
            '/get_delivery',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_delivery'),
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


        /*
            PayPal. The three storefront routes are open the way create_order
            is - checkout runs for guests - and they are safe to be, because
            none of them takes an amount: paypal_create_order derives it from
            the order row, and paypal_capture_order learns which order a
            payment belongs to from PayPal rather than from the caller. The
            order hash is the only handle a buyer holds, as with
            get_order_by_hash.

            The settings routes are admin work and need the dashboard's JWT.
            The webhook authenticates itself by signature - PayPal has no token
            to send - so it must stay open.
        */
        register_rest_route(
            'combo-store/v2',
            '/paypal_config',
            array(
                'methods'  => array('GET', 'POST'),
                'callback' => array($this, 'paypal_config'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_create_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_create_order'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_capture_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_capture_order'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_webhook',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_webhook'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_get_settings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_get_settings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_update_settings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_update_settings'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_test_connection',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_test_connection'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/paypal_refund',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'paypal_refund'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/create_shipping_zone',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_shipping_zone'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/create_shipping_method',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_shipping_method'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/create_expense',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_expense'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/create_template_part',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_template_part'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/create_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_template'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/create_purchase',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_purchase'),
                'permission_callback' => '__return_true',
            )
        );








        register_rest_route(
            'combo-store/v2',
            '/create_subscriptions',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'create_subscriptions'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/update_subscription',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_subscription'),
                'permission_callback' => '__return_true',
            )
        );


        register_rest_route(
            'combo-store/v2',
            '/update_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_order'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_shipping_zone',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_shipping_zone'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_shipping_method',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_shipping_method'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/bulk_update_orders',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'bulk_update_orders'),
                'permission_callback' => '__return_true',
            )
        );





        register_rest_route(
            'combo-store/v2',
            '/update_purchase',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_purchase'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_expense',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_expense'),
                'permission_callback' => '__return_true',
            )
        );
        register_rest_route(
            'combo-store/v2',
            '/update_template_part',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_template_part'),
                'permission_callback' => '__return_true',
            )
        );
        /* The builder's Template Settings save here; the route was never registered, so those saves always failed. */
        register_rest_route(
            'combo-store/v2',
            '/update_template',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_template'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/update_order_items',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_order_items'),
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
            '/delete_activities',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_activities'),
                'permission_callback' => '__return_true',
            )
        );









        register_rest_route(
            'combo-store/v2',
            '/delete_subscriptions_to_call',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_subscriptions_to_call'),
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
            '/valilast_dateken',
            array(
                'methods'  => 'GET',
                'callback' => array($this, 'valilast_dateken'),
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
            '/update_user_password',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_user_password'),
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
            '/get_customer',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_customer'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/upload_media',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'upload_media'),
                'permission_callback' => '__return_true',
            )
        );



        register_rest_route(
            'combo-store/v2',
            '/get_rider',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_rider'),
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
            '/email_export',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'email_export'),
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
     * @param WP_REST_Request $request Post data.
     */
    public function bulk_submit_product($request)
    {

        function is_json($string)
        {
            if (!is_string($string)) {
                return false;
            }

            json_decode($string);
            return (json_last_error() === JSON_ERROR_NONE);
        }



        /**
         * Download an image from URL and set it as featured image for a post.
         *
         * @param int    $post_id   Post ID.
         * @param string $image_url Image URL.
         * @return int|WP_Error Attachment ID on success, WP_Error on failure.
         */
        function set_featured_image_from_url($post_id, $image_url)
        {
            if (empty($post_id) || empty($image_url)) {
                return new WP_Error('missing_data', 'Post ID or Image URL is missing.');
            }

            // Load required files
            if (! function_exists('download_url')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            // Download file to temp location
            $tmp = download_url($image_url);
            if (is_wp_error($tmp)) {
                return $tmp;
            }

            // 👇 Build your custom slug here (using post title as example)
            $post_title = get_the_title($post_id);
            $slug       = string_to_file_slug($post_title);

            // Try to preserve extension
            $path_info = pathinfo(parse_url($image_url, PHP_URL_PATH));
            $ext       = isset($path_info['extension']) ? '.' . strtolower($path_info['extension']) : '.jpg';

            // Final custom filename
            $file_name = $slug . $ext;

            $file = array(
                'name'     => sanitize_file_name($file_name), // <– here is your slug
                'tmp_name' => $tmp,
            );

            // Detect mime type
            $filetype = wp_check_filetype($file['name']);
            if (! empty($filetype['type'])) {
                $file['type'] = $filetype['type'];
            } else {
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = finfo_file($finfo, $tmp);
                        finfo_close($finfo);
                        if ($mime) {
                            $file['type'] = $mime;
                        }
                    }
                }
            }

            // Upload to media library
            $attachment_id = media_handle_sideload($file, $post_id);

            // Cleanup temp file on error
            if (is_wp_error($attachment_id)) {
                @unlink($tmp);
                return $attachment_id;
            }

            // Set as featured image
            set_post_thumbnail($post_id, $attachment_id);

            return $attachment_id;
        }
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
        // $body = $request->get_body();

        // // $body_arr = json_decode($body);
        // $body_arr = json_decode($body, true);



        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_content = $request->get_param('post_content');
        $categories = $request->get_param('categories');


        //$categories = array_column($categories, 'term_id');



        $tags = $request->get_param('tags');
        $tags = $tags ? $tags : [];
        $tags = array_column($tags, 'term_id');


        $brands = $request->get_param('brands');
        $brands = $brands ? $brands : [];
        $brands = array_column($brands, 'term_id');



        $focusedIn = $request->get_param('focusedIn');
        $output = $request->get_param('output');
        $response = [];



        if (is_json($post_content)) {


            $lines = json_decode($post_content);


            foreach ($lines as $line) {


                $title = isset($line->title) ? $line->title : "";
                $image_url = isset($line->image) ? $line->image : "";
                $price = isset($line->price) ? $line->price : "";
                $price2 = isset($line->price2) ? $line->price2 : "";



                $postData = [
                    "post_title" => $title,
                    "post_content" => '',
                    'post_type'      => 'cmb_product',
                    'post_status'      => 'draft',
                ];
                // wp_update_post($postData);
                $post_id = wp_insert_post($postData);

                if (!empty($price2)) {
                    update_post_meta($post_id, 'regular_price', $price2);
                    update_post_meta($post_id, 'sale_price', $price);
                } else {
                    update_post_meta($post_id, 'regular_price', $price);
                }



                $result = set_featured_image_from_url($post_id, $image_url);

                if (is_wp_error($result)) {
                } else {
                }

                wp_set_post_terms($post_id, $categories, 'product_cat');
                wp_set_post_terms($post_id, $tags, 'product_tag');
                // wp_set_post_terms($post_id, $brands, 'product_brand');

                if ($post_id) {
                    $response['success'][$post_id] = true;
                }
                if (!$post_id) {
                    $response['success'][$post_id] = false;
                    die(wp_json_encode($response));
                }
            }
        } else {
            $lines = explode("\n", $post_content);




            foreach ($lines as $line) {


                $postData = [
                    "post_title" => $line,
                    "post_content" => '',
                    'post_type'      => 'cmb_product',
                    'post_status'      => 'draft',
                    "post_excerpt" => "",
                ];
                // wp_update_post($postData);
                $post_id = wp_insert_post($postData);

                // update_post_meta($post_id, 'focusedIn', $focusedIn);
                // update_post_meta($post_id, 'output', $output);



                wp_set_post_terms($post_id, $categories, 'product_cat');
                wp_set_post_terms($post_id, $tags, 'product_tag');
                wp_set_post_terms($post_id, $brands, 'product_brand');

                if ($post_id) {
                    $response['success'][$post_id] = true;
                }
                if (!$post_id) {
                    $response['error'][$post_id] = false;
                    die(wp_json_encode($response));
                }
            }


            if (empty($post_content)) {
                $response['errors']['prompt_missing'] = __("Prompt titles Missing", 'combostore-server');
            }
        }


        // Convert to array using newline as delimiter


        //return;

        // $tagNames = [];
        // foreach ($tags as $tag) {
        //     $tagNames[] = $tag;
        // }



        die(wp_json_encode($response));
    }





    public function upload_media($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;

        $files_to_upload = isset($request->get_file_params()['file']) ? $request->get_file_params()['file'] : [];


        $response = upload_media_file($files_to_upload);



        // $files = [];
        // if (!empty($files_to_upload)) {
        //     $i = 0;
        //     foreach ($files_to_upload as $index => $data) {
        //         foreach ($data as $metaKey => $fileInfo) {
        //             $files[$metaKey][$index] = $fileInfo;
        //         }
        //     }
        // }
        // if (!empty($files)) {
        //     foreach ($files as $metaKey => $metavalue) {
        //         if (!empty($metavalue['name'])) {
        //             $file_response = upload_media_file($metavalue);

        //             //$file_response['id']

        //         }
        //     }
        // }

        //$response['total'] = '';
        // $response['max_pages'] = $max_pages;
        // $response['posts'] = $entries;


        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function bulk_submit_users($request)
    {

        function is_json($string)
        {
            if (!is_string($string)) {
                return false;
            }

            json_decode($string);
            return (json_last_error() === JSON_ERROR_NONE);
        }



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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);



        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_content = $request->get_param('post_content');
        $role = $request->get_param('role');

        $response = [];


        if (is_json($post_content)) {


            $lines = json_decode($post_content);


            foreach ($lines as $index => $line) {



                $email = isset($line->email) ? $line->email : "";
                $name = isset($line->name) ? $line->name : "";
                $mobile = isset($line->mobile) ? $line->mobile : "";
                $address = isset($line->address) ? $line->address : "";



                $user_id = 0;


                $ComboStoreRegister = new ComboStoreRegister();
                $response_new_user = $ComboStoreRegister->create_user(['email' => $email, "password" => "", "role" => $role, "mobile" => $mobile]);

                $new_user_res = json_decode($response_new_user, true);

                $user_id = isset($new_user_res['user_id']) ? $new_user_res['user_id'] : 0;
                $error = isset($new_user_res['error']) ? $new_user_res['error'] : false;




                if (!empty($user_id) && !$error) {

                    wp_update_user(array(
                        'ID'           => $user_id,
                        'display_name' => $name,
                        'first_name' => $name,
                    ));

                    update_user_meta($user_id, 'billing_address1', $address);
                    update_user_meta($user_id, 'phone', $mobile);

                    $response['success'][$user_id] = true;
                } else {
                    $response['error'][$index] = true;
                }
            }
        }


        // Convert to array using newline as delimiter


        //return;

        // $tagNames = [];
        // foreach ($tags as $tag) {
        //     $tagNames[] = $tag;
        // }



        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function set_thumbnail_from_url($request)
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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);



        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $image_url = $request->get_param('url');
        $post_id = $request->get_param('postId');


        if (empty($post_id) || empty($image_url)) {
            $response['error'] = "Post ID or Image URL is missing.";
            die(wp_json_encode($response));
        }

        // Load required files
        if (! function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Download file to temp location
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return $tmp;
        }


        // 👇 Build your custom slug here (using post title as example)
        $post_title = get_the_title($post_id);
        $slug       = string_to_file_slug($post_title);

        $get_post_thumbnail_id = get_post_thumbnail_id($post_id);
        delete_post_thumbnail($post_id);
        wp_delete_attachment($get_post_thumbnail_id, true);


        // Try to preserve extension
        $path_info = pathinfo(parse_url($image_url, PHP_URL_PATH));
        $ext       = isset($path_info['extension']) ? '.' . strtolower($path_info['extension']) : '.jpg';

        // Final custom filename
        $file_name = $slug . $ext;

        $file = array(
            'name'     => sanitize_file_name($file_name), // <– here is your slug
            'tmp_name' => $tmp,
        );

        // Detect mime type
        $filetype = wp_check_filetype($file['name']);
        if (! empty($filetype['type'])) {
            $file['type'] = $filetype['type'];
        } else {
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                    if ($mime) {
                        $file['type'] = $mime;
                    }
                }
            }
        }

        // Upload to media library
        $attachment_id = media_handle_sideload($file, $post_id);

        // Cleanup temp file on error
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return $attachment_id;
        }



        $fullsize_path = get_attached_file($attachment_id);

        $editor = wp_get_image_editor($fullsize_path);
        if (! is_wp_error($editor)) {
            $editor->resize(500, 0, false); // width=500px, height auto, no crop
            $resized = $editor->save($fullsize_path); // overwrite original
            if (! is_wp_error($resized)) {
                // Update metadata so WordPress knows about new dimensions
                wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $fullsize_path));
            }
        }

        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);

        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);


        $response['success'] = true;
        $response['attachment_id'] = $attachment_id;
        $response['src'] = $post_thumbnail_url;



        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function bulk_submit_blog($request)
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
        // $body = $request->get_body();

        // // $body_arr = json_decode($body);
        // $body_arr = json_decode($body, true);



        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_content = $request->get_param('post_content');
        $categories = $request->get_param('categories');


        //$categories = array_column($categories, 'term_id');



        $tags = $request->get_param('tags');
        $tags = $tags ? $tags : [];
        $tags = array_column($tags, 'term_id');






        $focusedIn = $request->get_param('focusedIn');
        $output = $request->get_param('output');
        $response = [];

        $post_content = preg_replace('/^\h*\v+/m', '', $post_content);


        $lines = explode("\n", $post_content);

        if (empty($post_content)) {
            $response['errors']['prompt_missing'] = __("Prompt titles Missing", 'combostore-server');
            die(wp_json_encode($response));
        }


        foreach ($lines as $line) {


            $postData = [
                "post_title" => $line,
                "post_content" => '',
                'post_type'      => 'post',
                'post_status'      => 'draft',
                "post_excerpt" => "",
            ];
            // wp_update_post($postData);
            $post_id = wp_insert_post($postData);

            // update_post_meta($post_id, 'focusedIn', $focusedIn);
            // update_post_meta($post_id, 'output', $output);

            wp_set_post_terms($post_id, $categories, 'category');
            wp_set_post_terms($post_id, $tags, 'post_tag');

            if ($post_id) {
                $response['success'][$post_id] = true;
            }
            if (!$post_id) {
                $response['error'][$post_id] = false;
                die(wp_json_encode($response));
            }
        }




        // Convert to array using newline as delimiter


        //return;

        // $tagNames = [];
        // foreach ($tags as $tag) {
        //     $tagNames[] = $tag;
        // }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_subscriptions($request)
    {
        /*
            Requires a signed-in user. Administrators and store managers see
            every subscription; anyone else sees only their own. This used to
            be open with `$isAdmin = true; $user_id = 1;` hard-coded, so any
            visitor - and every customer's account page - received every
            customer's subscriptions.
        */
        $token = $request->get_header('Authorization');
        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }
        $token = str_replace('Bearer ', '', $token);
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;
        $user = $user_id ? get_user_by('id', $user_id) : null;
        if (!$user) {
            return new WP_Error('invalid_token', 'User not found', array('status' => 401));
        }
        $userRoles = isset($user->roles) ? (array) $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles, true) || in_array('store_manager', $userRoles, true);

        $page = isset($request['paged']) ? max(1, absint($request['paged'])) : (isset($request['page']) ? max(1, absint($request['page'])) : 1);
        $per_page = isset($request['per_page']) ? min(max(absint($request['per_page']), 1), 100) : 10;
        /* Whitelisted: this value is written straight into ORDER BY. */
        $order = isset($request['order']) && strtoupper($request['order']) === 'ASC' ? 'ASC' : 'DESC';
        $status = isset($request['status']) ? sanitize_key($request['status']) : '';
        $status = in_array($status, ['active', 'paused', 'canceled', 'expired'], true) ? $status : '';
        $keyword = isset($request['keyword']) ? trim(sanitize_text_field($request['keyword'])) : '';

        global $wpdb;
        $table_subscriptions = $wpdb->prefix . 'cstore_subscriptions';
        $table_orders = $wpdb->prefix . 'cstore_orders';

        $where = ['1=1'];
        $values = [];

        if (!$isAdmin) {
            $where[] = 's.userid = %d';
            $values[] = absint($user_id);
        }
        if ($keyword !== '' && $keyword[0] === '#') {
            /* "#12" means an ID: subscription #12 or order #12, nothing fuzzy. */
            $number = absint(substr($keyword, 1));
            $where[] = '(s.id = %d OR s.order_id = %d)';
            array_push($values, $number, $number);
        } elseif ($keyword !== '') {
            $like = '%' . $wpdb->esc_like($keyword) . '%';
            $number = ctype_digit($keyword) ? absint($keyword) : 0;
            $where[] = '(s.id = %d OR s.order_id = %d OR o.billing_name LIKE %s OR o.billing_phone LIKE %s OR o.billing_email LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
            array_push($values, $number, $number, $like, $like, $like, $like, $like);
        }

        $from = "FROM $table_subscriptions s
                 LEFT JOIN $table_orders o ON o.id = s.order_id
                 LEFT JOIN {$wpdb->users} u ON u.ID = s.userid";
        $base_where = implode(' AND ', $where);

        /* Counts per status for the filter, before the status filter itself. */
        $count_sql = "SELECT s.status, COUNT(s.id) $from WHERE $base_where GROUP BY s.status";
        $status_counts = ['active' => 0, 'paused' => 0, 'canceled' => 0, 'expired' => 0];
        foreach ($wpdb->get_results($values ? $wpdb->prepare($count_sql, $values) : $count_sql, ARRAY_N) as $row) {
            $status_counts[$row[0]] = (int) $row[1];
        }

        if ($status) {
            $where[] = 's.status = %s';
            $values[] = $status;
        }
        $full_where = implode(' AND ', $where);

        $total_sql = "SELECT COUNT(s.id) $from WHERE $full_where";
        $total = (int) $wpdb->get_var($values ? $wpdb->prepare($total_sql, $values) : $total_sql);

        $list_sql = "SELECT s.*, o.billing_name, o.billing_email, o.billing_phone,
                            u.display_name AS customer_name, u.user_email AS customer_email
                     $from WHERE $full_where ORDER BY s.id $order LIMIT %d, %d";
        $entries = $wpdb->get_results($wpdb->prepare($list_sql, array_merge($values, [($page - 1) * $per_page, $per_page])));

        $response = [];
        $response['total'] = $total;
        $response['max_pages'] = (int) ceil($total / $per_page);
        $response['status_counts'] = $status_counts;
        $response['posts'] = $entries;

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_subscribe_to_calls($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_subscriptions_to_call';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        if ($isAdmin) {

            $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table");
        } else {

            $entries = $wpdb->get_results("SELECT * FROM $table WHERE userid='$user_id' ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table WHERE userid='$user_id'");
        }

        foreach ($entries as $entry) {

            $product_id = isset($entry->product_id) ? $entry->product_id : "";
            $entry->product_title = get_the_title($product_id);
        }


        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_activities($request)
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



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $user_id = 1;
        // $isAdmin = true;
        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";

        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_activities';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_orders WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        $entries = $wpdb->get_results("SELECT * FROM $table_orders ORDER BY id $order limit $offset, $per_page");
        $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_orders");

        // if ($isAdmin) {
        //     $entries = $wpdb->get_results("SELECT * FROM $table_orders ORDER BY id $order limit $offset, $per_page");
        //     $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_orders");
        // } else {
        //     $entries = $wpdb->get_results("SELECT * FROM $table_orders WHERE userid='$user_id' ORDER BY id $order limit $offset, $per_page");
        //     $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_orders WHERE userid='$user_id'");
        // }




        $big = 999999999; // need an unlikely integer








        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $response['total'] = $total;

        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_orders($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $userRole = 'customer';

        if (in_array('administrator', $userRoles)) {
            $userRole = 'administrator';
        } elseif (in_array('store_manager', $userRoles)) {
            $userRole = 'store_manager';
        } elseif (in_array('support_agent', $userRoles)) {
            $userRole = 'support_agent';
        } else {
            $userRole = 'customer';
        }


        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['user_role'] = $userRole;
        $body_arr['user_id'] = $user_id;

        $ComboStoreOrders = new ComboStoreOrders();
        $response = $ComboStoreOrders->get_orders($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_shipping_zones($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $userRole = 'customer';

        if (in_array('administrator', $userRoles)) {
            $userRole = 'administrator';
        } elseif (in_array('store_manager', $userRoles)) {
            $userRole = 'store_manager';
        } elseif (in_array('support_agent', $userRoles)) {
            $userRole = 'support_agent';
        } else {
            $userRole = 'customer';
        }


        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['user_role'] = $userRole;
        $body_arr['user_id'] = $user_id;

        $ComboStoreShipping = new ComboStoreShipping();
        $response = $ComboStoreShipping->get_shipping_zones($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_shipping_methods($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $userRole = 'customer';

        if (in_array('administrator', $userRoles)) {
            $userRole = 'administrator';
        } elseif (in_array('store_manager', $userRoles)) {
            $userRole = 'store_manager';
        } elseif (in_array('support_agent', $userRoles)) {
            $userRole = 'support_agent';
        } else {
            $userRole = 'customer';
        }


        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['user_role'] = $userRole;
        $body_arr['user_id'] = $user_id;

        $ComboStoreShipping = new ComboStoreShipping();
        $response = $ComboStoreShipping->get_shipping_methods($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function find_shipping_methods($request)
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



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        // $user_id = 1;
        // $isAdmin = true;


        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $ComboStoreShipping = new ComboStoreShipping();
        $response = $ComboStoreShipping->find_shipping_methods($body_arr);


        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_purchases($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
        $body_arr['user_id'] = $user_id;

        $ComboStorePurchases = new ComboStorePurchases();
        $response = $ComboStorePurchases->get_purchases($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_expenses($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
        $body_arr['user_id'] = $user_id;

        $ComboStoreExpenses = new ComboStoreExpenses();
        $response = $ComboStoreExpenses->get_expenses($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    /**
     * Change a product's (or variation's) stock by hand, from the Stocks screen.
     *
     * Goes through ComboStoreStocks::update_stock, so the change is in stock
     * history with who made it and why. There was no route for this: the only
     * way to change a count was the product editor, which wrote the number
     * directly and left no history.
     *
     * Body: product_id (the product's post id), variation_id (optional),
     * action (increase | decrease | set), quantity (whole number), note.
     *
     * @param WP_REST_Request $request
     */
    public function adjust_stock($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        if (!array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to change stock', array('status' => 403));
        }

        $product_id   = isset($request['product_id']) ? absint($request['product_id']) : 0;
        $variation_id = isset($request['variation_id']) ? absint($request['variation_id']) : 0;
        $action       = isset($request['action']) ? sanitize_key($request['action']) : '';
        $quantity     = isset($request['quantity']) ? $request['quantity'] : null;
        $note         = isset($request['note']) ? sanitize_textarea_field($request['note']) : '';

        $answer = function ($status, $message, $extra = array()) {
            die(wp_json_encode(array_merge(array('status' => $status, 'message' => $message), $extra)));
        };

        if (!$product_id || get_post_type($product_id) !== 'cmb_product') {
            $answer('failed', 'Product not found.');
        }

        if (!in_array($action, array('increase', 'decrease', 'set'), true)) {
            $answer('failed', 'Choose add, remove or set.');
        }

        if (!is_numeric($quantity) || (float) $quantity < 0 || floor((float) $quantity) != (float) $quantity) {
            $answer('failed', 'The quantity must be a whole number, 0 or more.');
        }

        if ($action !== 'set' && (int) $quantity === 0) {
            $answer('failed', 'Enter how many to add or remove.');
        }

        $ComboStoreStocks = new ComboStoreStocks();

        $result = $ComboStoreStocks->update_stock(array(
            'product_id'     => $product_id,
            'variation_id'   => $variation_id,
            'action'         => $action,
            'quantity'       => (int) $quantity,
            'reference_type' => 'manual',
            'note'           => $note !== '' ? $note : 'Adjusted on the Stocks screen',
            'created_by'     => (int) $user->ID,
        ));

        if (!isset($result['status']) || $result['status'] !== 'success') {
            $message = isset($result['message']) ? $result['message'] : 'Stock not changed.';

            if ($message === 'Insufficient stock.') {
                $message = 'There is not that much in stock to remove.';
            }

            $answer('failed', $message);
        }

        if (isset($result['stock_after']) && (int) $result['stock_after'] < 5) {
            do_action('combo_store_low_stock', $product_id);
        }

        $answer('success', 'Stock updated.', array(
            'stock_before' => (int) $result['stock_before'],
            'stock_after'  => (int) $result['stock_after'],
            'stock_status' => $result['stock_status'],
        ));
    }

    public function get_stocks_histories($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        /* The same roles the sidebar shows this screen to. */
        $isAdmin = (bool) array_intersect(['administrator', 'store_manager'], (array) $userRoles);



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
        $body_arr['user_id'] = $user_id;

        $ComboStoreStocks = new ComboStoreStocks();
        $response = $ComboStoreStocks->get_stocks_histories($body_arr);


        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_template_parts($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
        $body_arr['user_id'] = $user_id;

        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_template_parts($body_arr);


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_templates($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
        $body_arr['user_id'] = $user_id;

        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_templates($body_arr);


        die(wp_json_encode($response));
    }





















    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_trackings($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        // $userRoles = isset($user->roles) ? $user->roles : [];
        // $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $delivery_id     = isset($request['delivery_id']) ? sanitize_text_field($request['delivery_id']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";

        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_deliveries_trackings';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_orders WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        $entries = $wpdb->get_results("SELECT * FROM $table_orders WHERE delivery_id='$delivery_id' ORDER BY id $order limit $offset, $per_page");
        $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_orders WHERE delivery_id='$delivery_id'");




        $big = 999999999; // need an unlikely integer








        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $response['total'] = $total;

        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_deliveries($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $isRider = in_array('rider', $userRoles) ? true : false;

        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";

        $page = ($page == 0) ? 1 : $page;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_deliveries';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        if ($isAdmin) {
            $entries = $wpdb->get_results("SELECT * FROM $table ORDER BY id $order limit $offset, $per_page");
            // $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table");
        } elseif ($isRider) {
            $entries = $wpdb->get_results("SELECT * FROM $table WHERE rider_id='$user_id' ORDER BY id $order limit $offset, $per_page");
            // $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table  ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table WHERE rider_id='$user_id'");
        } else {
            $entries = $wpdb->get_results("SELECT * FROM $table WHERE customer_id='$user_id' ORDER BY id $order limit $offset, $per_page");
            // $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table  ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table WHERE customer_id='$user_id'");
        }




        $big = 999999999; // need an unlikely integer








        // Fix the total count query by removing the per_page clause

        // Calculate max pages
        $max_pages = ceil($total / $per_page);

        $response['total'] = $total;

        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function send_to_courier($request)
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

        // $user_id = 1;
        // $isAdmin = true;
        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $isRider = in_array('rider', $userRoles) ? true : false;

        $courierData     = isset($request['courierData']) ? stripslashes_deep($request['courierData']) : '';
        $courier     = isset($courierData['courier']) ? sanitize_text_field($courierData['courier']) : '';


        $app_response = apply_filters('cstore_send_to_courier_' . $courier, $courierData);

        $response['posts'] = $app_response;


        die(wp_json_encode($response));
    }












    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_medias($request)
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
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 30;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";


        $query_args = [];

        $query_args['post_type'] = 'attachment';
        $query_args['post_status'] = 'inherit';

        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
        }

        // if (!empty($orderby)) {
        //     $query_args['orderby'] = $orderby;
        // }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }




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
     * @param WP_REST_Request $request Post data.
     */
    public function get_post($request)
    {        // $token = $request->get_header('Authorization');


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

        $response = [];
        $user_id = 1;
        // $post_id     = isset($request['id']) ? absint($request['id']) : 0;
        $post_slug     = isset($request['slug']) ? sanitize_text_field($request['slug']) : "";

        if (!$post_slug) {

            die(wp_json_encode($response));
        }

        $post = get_page_by_path($post_slug, OBJECT, 'page'); // Change 'post' to your post type if it's not 'post'
        $post_id = isset($post->ID) ? $post->ID : "";

        if (empty($post_id)) {
            $post = get_page_by_path($post_slug, OBJECT, 'post'); // Change 'post' to your post type if it's not 'post'

        }
        $post_id = isset($post->ID) ? $post->ID : "";



        $prams = [];
        $prams['object_id'] = $post_id;
        $prams['userid'] = $user_id;

        // $ComboStoreViews = new ComboStoreViews();
        // $viewCount = $ComboStoreViews->get_object_view_count($prams);

        // $ComboStoreLove = new ComboStoreLove();
        // $loveCount = $ComboStoreLove->total_loved($prams);
        // $loved = $ComboStoreLove->has_loved($prams);



        $promptData = [];

        $postData = get_post($post_id);


        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
        $post_thumbnail_id = get_post_thumbnail_id($post_id);


        $featured = get_post_meta($post_id, 'featured', true);
        $author_id = isset($postData->post_author) ? $postData->post_author : null;
        $post_type = isset($postData->post_type) ? $postData->post_type : null;
        $author_name = isset($author_id) ? get_the_author_meta("display_name", $author_id) : null;
        $avatar_url = isset($author_id) ? get_avatar_url($author_id, ["size" => 40]) : null;

        $author =  ["name" => $author_name, "id" => $author_id, "avatar" => $avatar_url];



        $gallery = get_post_meta($post_id, 'gallery', true);
        $gallery = $gallery ? $gallery : [];


        $addons = get_post_meta($post_id, 'addons', true);
        $addons = $addons ? $addons : [];

        $downloads = get_post_meta($post_id, 'downloads', true);
        $downloads = $downloads ? $downloads : [];

        $downloadCount = get_post_meta($post_id, 'downloadCount', true);
        $voteCount = get_post_meta($post_id, 'voteCount', true);
        // $loveCount = get_post_meta($post_id, 'loveCount', true);
        // $viewCount = get_post_meta($post_id, 'viewCount', true);
        $faq = get_post_meta($post_id, 'faq', true);
        $faq = $faq ? $faq : [];

        $relatedPrompts = get_post_meta($post_id, 'relatedPrompts', true);
        $relatedPrompts = $relatedPrompts ? $relatedPrompts : [];

        $variations = get_post_meta($post_id, 'variations', true);
        $variations = $variations ? $variations : [];



        $categories = get_the_terms($post_id, 'category');
        $categories = $categories ? $categories : [];

        $tags = get_the_terms($post_id, 'post_tag');
        $tags = $tags ? $tags : [];


        $categoriesData = [];
        foreach ($categories as $index => $category) {

            $categoriesData[$index] = [
                "term_id" => $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
            ];
        }


        // $tagsData = [];
        // foreach ($tags as $index => $tag) {

        //     $tagsData[$index] = $tag->name;
        // }

        // $modelsData = [];
        // foreach ($models as $index => $model) {

        //     $modelsData[$index] = [
        //         "term_id" => $model->term_id,
        //         "name" => $model->name
        //     ];
        // }

        $content = isset($postData->post_content) ? $postData->post_content : null;


        $Parsedown = new Parsedown();
        $post_content_html = $Parsedown->text($content);



        $content_html = apply_filters('the_content', $post_content_html); // Also runs do_blocks() and other filters
        // OR just:
        //$content_html = do_blocks($content);

        $promptData = [
            "id" => isset($postData->ID) ? $postData->ID : null,
            "title" => isset($postData->post_title) ? $postData->post_title : null,
            "content" => $content_html,
            "post_content_html" => isset($post_content_html) ? $post_content_html : '',

            "post_status" => isset($postData->post_status) ? $postData->post_status : null,
            "post_type" => isset($postData->post_type) ? $postData->post_type : null,
            "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
            "post_date" => isset($postData->post_date) ? $postData->post_date : null,
            "post_thumbnail" => [
                'id' => $post_thumbnail_id,
                'src' => $post_thumbnail_url
            ],
            "post_thumbnail_url" => $post_thumbnail_url,
            "featured" => $featured,
            "author" => $author,

            "categories" => $categoriesData ?? [],
            "tags" => $tagsData ?? [],

            "gallery" => $gallery,
            "related_posts" => $relatedPrompts,
        ];

        error_log(wp_json_encode($promptData));

        $response['post'] = $promptData;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_page($request)
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

        $response = [];
        $user_id = 1;
        $slug     = isset($request['path']) ? sanitize_text_field($request['path']) : '';
        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';



        $promptData = [];
        if ($slug) {


            if ($slug == "home") {
                $front_page_id = get_option('page_on_front');
                $postData = get_post($front_page_id);
            } else {
                $postData = get_page_by_path($slug, OBJECT, 'page');
            }
        } else {

            if (!$id) {
                die(wp_json_encode($response));
            }

            $postData = get_post($id);
        }


        if (!isset($postData->ID)) {

            die(wp_json_encode($response));
        }

        // $args = array(
        //     'name'           => $slug,
        //     'post_type'      => 'page',
        //     'post_status'    => 'publish',
        //     'posts_per_page' => 1
        // );
        // $postsData = get_posts($args);

        // $postData = isset($postsData[0]) ? $postsData[0] : null;
        $css = get_post_meta($postData->ID, 'css');
        /* The builder's page settings (see update_page); an object even when empty, so the client can spread it. */
        $page_meta = isset($postData->ID) ? get_post_meta($postData->ID, 'page_meta', true) : [];


        $promptData = [
            "id" => isset($postData->ID) ? $postData->ID : null,
            "title" => isset($postData->post_title) ? $postData->post_title : null,
            "content" => isset($postData->post_content) ? $postData->post_content : null,
            "post_status" => isset($postData->post_status) ? $postData->post_status : null,
            "post_type" => isset($postData->post_type) ? $postData->post_type : null,
            "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
            "css" => $css,
            "meta" => (object) (is_array($page_meta) ? $page_meta : []),
        ];




        $response = $promptData;


        die(wp_json_encode($response));
    }










    /**
     * The roles of the user whose token came with the request.
     *
     * For public endpoints that only show more to some users: a missing,
     * invalid or expired token is an anonymous visitor, never an error, so a
     * stale token left in the storefront cannot break a public page.
     *
     * @param WP_REST_Request $request
     * @return string[]
     */
    private function request_user_roles($request)
    {
        $token = $request->get_header('Authorization');

        if (!$token) {
            return [];
        }

        try {
            $decoded_token = JWT::decode(str_replace('Bearer ', '', $token), new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return [];
        }

        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;
        $user = $user_id ? get_user_by('id', $user_id) : false;

        return ($user && isset($user->roles)) ? (array) $user->roles : [];
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_posts($request)
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

        // $user_id = 1;

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $post_type     = isset($request['post_type']) ? stripslashes_deep($request['post_type']) : ['post'];

        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "date";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "DESC";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? stripslashes_deep($request['categories']) : [];
        $taxQuery     = isset($request['taxQuery']) ? ($request['taxQuery']) : [];

        $posts_per_page     = isset($request['posts_per_page']) ? absint($request['posts_per_page']) : 20;
        $brand     = isset($request['brand']) ? sanitize_text_field($request['brand']) : '';
        $brands     = isset($request['brands']) ? stripslashes_deep($request['brands']) : [];
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $tags     = isset($request['tags']) ? stripslashes_deep($request['tags']) : [];
        $post__in     = isset($request['post__in']) ? stripslashes_deep($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? stripslashes_deep($request['post__not_in']) : [];
        $stock_status     = isset($request['stock_status']) ? stripslashes_deep($request['stock_status']) : null;













        $query_args = [];
        $meta_query = [];
        $tax_query = [];


        if (in_array('post', $post_type)) {
            if (!empty($category)) {
                $tax_query[] = array(
                    'taxonomy' => 'category',
                    'field'    => 'term_id',
                    'terms'    => [$category],
                    'operator'    => 'IN',
                );
            }


            if (!empty($categories)) {
                $tax_query[] = array(
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $categories,
                    'operator'    => 'IN',
                );
            }
        }
        if (in_array('cmb_product', $post_type)) {

            if ($stock_status) {

                $meta_query[] = array(
                    'key' => 'stock_status',
                    'value'    => $stock_status,
                    'operator'    => '=',
                );
            }

            if (!empty($tag)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => [$tag],
                    'operator'    => 'IN',
                );
            }
            if (!empty($tags)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => $tags,
                    'operator'    => 'IN',
                );
            }

            //Hello

            if (!empty($brand)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_brand',
                    'field'    => 'slug',
                    'terms'    => [$brand],
                    'operator'    => 'IN',
                );
            }
            if (!empty($brands)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_brand',
                    'field'    => 'slug',
                    'terms'    => $brands,
                    'operator'    => 'IN',
                );
            }



            if (!empty($category)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => [$category],
                    'operator'    => 'IN',
                );
            }

            if (!empty($categories)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $categories,
                    'operator'    => 'IN',
                );
            }
            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'slug',
                'terms'    => ['exclude-from-search', 'exclude-from-catalog'],
                'operator'    => 'NOT IN',
            );
        }










        if (!empty($taxQuery)) {
            $tax_query[] = $this->convert_tax_query($taxQuery);
        }







        $query_args['post_status'] = 'publish';

        /*
            This endpoint is public and the storefront uses it, so unpublished
            posts are only listed when an administrator asks for them by name
            (the dashboard's Posts screen sends post_status "any"). An admin
            browsing the shop, who sends no post_status, still sees only
            published posts.
        */
        $requested_status = isset($request['post_status']) ? sanitize_key($request['post_status']) : '';

        if ($requested_status && in_array($requested_status, ['any', 'publish', 'draft', 'pending', 'private', 'future'], true)) {
            if (in_array('administrator', $this->request_user_roles($request), true)) {
                $query_args['post_status'] = $requested_status;
            }
        }

        if (!empty($post_type)) {
            $query_args['post_type'] = $post_type;
        }

        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }


        if (!empty($posts_per_page)) {
            $query_args['posts_per_page'] = $posts_per_page;
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
        /* post__in / post__not_in were read above but never applied, so a
           query for given posts (e.g. a product's upsells) returned every post. */
        if (!empty($post__in)) {
            $query_args['post__in'] = array_map('absint', (array) $post__in);
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = array_map('absint', (array) $post__not_in);
        }

        error_log(wp_json_encode($query_args));


        $wp_query = new WP_Query($query_args);


        $entries = [];

        if ($wp_query->have_posts()) :
            $count = 1;

            while ($wp_query->have_posts()) : $wp_query->the_post();

                $post_id = get_the_ID();
                $postData = get_post($post_id);
                $postAuthorID = isset($postData->post_author) ? $postData->post_author : null;

                $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
                $featured = get_post_meta($post_id, 'featured', true);
                $author_name = isset($postAuthorID) ? get_the_author_meta("display_name", $postAuthorID) : null;
                $avatar_url = isset($postAuthorID) ? get_avatar_url($postAuthorID, ["size" => 40]) : null;

                $author_id = get_the_author_meta("ID", $postAuthorID);
                $author =  ["name" => $author_name, "id" => $author_id, "avatar" => $avatar_url];

                $gallery = get_post_meta($post_id, 'gallery', true);
                $gallery = $gallery ? $gallery : [];


                $addons = get_post_meta($post_id, 'addons', true);
                $addons = $addons ? $addons : [];


                $downloads = get_post_meta($post_id, 'downloads', true);

                $voteCount = get_post_meta($post_id, 'voteCount', true);
                // $viewCount = get_post_meta($post_id, 'viewCount', true);
                $downloadCount = get_post_meta($post_id, 'downloadCount', true);
                $faq = get_post_meta($post_id, 'faq', true);
                $relatedPrompts = get_post_meta($post_id, 'relatedPrompts', true);
                $variations = get_post_meta($post_id, 'variations', true);
                $variations = $variations ? $variations : [];

                $categories = get_the_terms($post_id, 'category');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'post_tag');
                $tags = $tags ? $tags : [];



                $categoriesData = [];
                foreach ($categories as $index => $category) {

                    $categoriesData[$index] = [
                        "term_id" => isset($category->term_id) ? $category->term_id : '',
                        "name" => isset($category->name) ? $category->name : '',
                        "slug" => isset($category->slug) ? $category->slug : '',
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => isset($tag->term_id) ? $tag->term_id : '',
                        "name" => isset($tag->name) ? $tag->name : '',
                        "slug" => isset($tag->slug) ? $tag->slug : '',
                    ];
                }

                // $prams = [];
                // $prams['object_id'] = $post_id;
                // $prams['userid'] = $user_id;

                // $ComboStoreViews = new ComboStoreViews();
                // $viewCount = $ComboStoreViews->get_object_view_count($prams);

                // $ComboStoreLove = new ComboStoreLove();
                // $loveCount = $ComboStoreLove->total_loved($prams);
                // $loved = $ComboStoreLove->has_loved($prams);

                $post_thumbnail = [
                    "id" => "",
                    "title" => "",
                    "src" => $post_thumbnail_url,
                ];

                if ($postData->post_type == 'post') {
                    $entries[] = [
                        "id" => isset($postData->ID) ? $postData->ID : null,
                        "title" => isset($postData->post_title) ? $postData->post_title : null,
                        "post_type" => isset($postData->post_type) ? $postData->post_type : null,

                        "slug" => isset($postData->post_name) ? $postData->post_name : null,
                        "content" => isset($postData->post_content) ? $postData->post_content : null,
                        "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
                        "status" => isset($postData->post_status) ? $postData->post_status : null,
                        "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],

                        "post_thumbnail_url" => $post_thumbnail_url,
                        "featured" => $featured,
                        "author" => $author,

                        "categories" => $categoriesData,
                        "tags" => $tagsData,

                        "gallery" => $gallery,
                        "addons" => $addons,
                        "downloads" => $downloads,
                        "voteCount" => !empty($voteCount) ? $voteCount : 0,
                        "viewCount" => !empty($viewCount) ? $viewCount : 0,
                        "loveCount" => !empty($loveCount) ? $loveCount : 0,
                        "downloadCount" => !empty($downloadCount) ? $downloadCount : 0,
                        // "loved" => $loved,
                        "faq" => $faq,
                        "relatedPrompts" => $relatedPrompts,
                        "variations" => $variations,
                    ];
                }





                if ($postData->post_type == 'cmb_product') {

                    $post_id = get_the_ID();
                    $postData = get_post($post_id);


                    $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
                    $featured = get_post_meta($post_id, 'featured', true);
                    $sku = get_post_meta($post_id, 'sku', true);
                    $bulk_prices = get_post_meta($post_id, 'bulk_prices', true);
                    $price_type = get_post_meta($post_id, 'price_type', true);
                    $price_type = !empty($price_type) ? $price_type : 'simple';


                    $sale_price = get_post_meta($post_id, 'sale_price', true);
                    $regular_price = get_post_meta($post_id, 'regular_price', true);


                    $stock_status = get_post_meta($post_id, 'stock_status', true);
                    $stock_count = get_post_meta($post_id, 'stock_count', true);
                    $gallery = get_post_meta($post_id, 'gallery', true);
                    $addons = get_post_meta($post_id, 'addons', true);
                    $downloads = get_post_meta($post_id, 'downloads', true);

                    $faq = get_post_meta($post_id, 'faq', true);
                    $variations = get_post_meta($post_id, 'variations', true);
                    $attributes = get_post_meta($post_id, 'attributes', true);

                    $categories = get_the_terms($post_id, 'product_cat');
                    $categories = $categories ? $categories : [];

                    $tags = get_the_terms($post_id, 'product_tag');
                    $tags = $tags ? $tags : [];

                    $brands = get_the_terms($post_id, 'product_brand');
                    $brands = $brands ? $brands : [];

                    $visibility = get_the_terms($post_id, 'product_visibility');
                    $visibility = $visibility ? $visibility : [];




                    $categoriesData = [];
                    foreach ($categories as $index => $category) {



                        $categoriesData[$index] = [
                            "term_id" => $category->term_id,
                            "name" => $category->name,
                            "slug" => $category->slug,
                        ];
                    }

                    $visibilityData = [];
                    foreach ($visibility as $index => $category) {



                        $visibilityData[$index] = [
                            "name" => $category->name,
                            "slug" => $category->slug,
                        ];
                    }


                    $tagsData = [];
                    foreach ($tags as $index => $tag) {

                        $tagsData[$index] = [
                            "term_id" => $tag->term_id,
                            "name" => $tag->name
                        ];
                    }

                    $brandsData = [];
                    foreach ($brands as $index => $brand) {

                        $brandsData[$index] = [
                            "term_id" => $brand->term_id,
                            "name" => $brand->name
                        ];
                    }




                    $post_thumbnail = [
                        "id" => get_post_thumbnail_id($post_id),
                        "title" => "",
                        "src" => $post_thumbnail_url,
                    ];




                    $entry = [
                        "id" => isset($postData->ID) ? $postData->ID : null,
                        "post_type" => isset($postData->post_type) ? $postData->post_type : null,
                        "title" => isset($postData->post_title) ? $postData->post_title : null,
                        "slug" => isset($postData->post_name) ? $postData->post_name : null,
                        "status" => isset($postData->post_status) ? $postData->post_status : null,
                        "sku" => $sku,
                        "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
                        "post_thumbnail_url" => $post_thumbnail_url,
                        "featured" => $featured,
                        "categories" => $categoriesData,
                        "tags" => $tagsData,
                        "brands" => $brandsData,
                        "visibility" => $visibilityData,

                    ];


                    $ComboStoreProduct = new ComboStoreProduct();

                    $product_data = $ComboStoreProduct->get_product(['product_id' => $postData->ID]);


                    $json_fields = [
                        'gallery',
                        'bulk_prices',
                        'addons',
                        'downloads',
                        'upsells',
                        'crosssells',
                        'faq',
                        'attributes',
                        'related_products',
                    ];

                    foreach ($json_fields as $field) {

                        if (!array_key_exists($field, $product_data)) {
                            continue;
                        }

                        if (empty($product_data[$field])) {
                            $product_data[$field] = [];
                            continue;
                        }

                        $decoded = json_decode($product_data[$field], true);

                        // If valid JSON, use decoded array/value.
                        // Otherwise keep the original value.
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $product_data[$field] = $decoded;
                        }
                    }



                    $sale_price = !empty($product_data['sale_price']) ? ($product_data['sale_price']) : 0;
                    $regular_price = !empty($product_data['regular_price']) ? ($product_data['regular_price']) : 0;
                    $price =  !empty($sale_price) ? $sale_price : $regular_price;
                    $productData['price'] = $price;


                    unset($product_data['id']);


                    /*
                        Archive cards render ProductPriceView too, which builds the
                        "min - max" range from `variations` when addons has
                        'variations'. Without them the card printed 0 - 0.
                    */
                    $variations = [];
                    if (is_array($product_data['addons'] ?? null) && in_array('variations', $product_data['addons'], true)) {
                        $variations = $ComboStoreProduct->get_variations(['product_id' => $postData->ID]);
                        $variations = is_array($variations) ? $variations : [];

                        foreach ($variations as &$variation) {
                            if (!empty($variation->thumbnail)) {
                                $thumbnail = json_decode($variation->thumbnail, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $variation->thumbnail = $thumbnail;
                                }
                            } else {
                                $variation->thumbnail = [];
                            }

                            if (!empty($variation->attributes) && is_string($variation->attributes)) {
                                $attributes = json_decode($variation->attributes, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    $variation->attributes = $attributes;
                                }
                            } else {
                                $variation->attributes = $variation->attributes ?? [];
                            }

                            $variation->variation_id = $variation->id;
                        }
                        unset($variation);
                    }


                    $entry['price'] = $price;
                    $entry['variations'] = $variations;


                    $entries[] = array_merge($entry, $product_data);
                }








            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;

        $max_pages = ceil($total / $posts_per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_terms($request)
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


        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : null;
        $hierarchical     = isset($request['hierarchical']) ? (bool) $request['hierarchical'] : false;
        $page     = isset($request['paged']) ? max(1, absint($request['paged'])) : 1;

        /*
            Page size. `number` still wins when sent. `per_page` is honoured only
            together with `paged` - the dashboard list screens send both - and
            never for a hierarchical request, whose tree has to be built from
            every term. Pickers and menus send a small `per_page` without
            `paged` yet expect every term back (the default of 999), which is
            how this endpoint has always answered them.
        */
        if (isset($request['number'])) {
            $number = absint($request['number']);
        } elseif (isset($request['per_page'], $request['paged']) && !$hierarchical) {
            $number = min(max(absint($request['per_page']), 1), 999);
        } else {
            $number = 999;
        }
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';
        $parent     = isset($request['parent']) ?  (int) $request['parent'] : 0;
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : null;
        $hide_empty     = isset($request['hide_empty']) ? sanitize_text_field($request['hide_empty']) : false;
        $slug     = isset($request['slug']) ? stripslashes_deep($request['slug']) : [];








        $response = [];

        if (empty($taxonomy)) {
            die(wp_json_encode($response));
        }

        $tax_obj = get_taxonomy($taxonomy);




        $termsQuery = [];
        if ($taxonomy) {
            $termsQuery['taxonomy'] = $taxonomy;
        }

        $termsQuery['hide_empty'] = $hide_empty;

        if ($number) {
            $termsQuery['number'] = $number;
            $termsQuery['offset'] = ($page - 1) * $number;
        }
        if ($order) {
            $termsQuery['order'] = $order;
        }
        if ($slug) {
            $termsQuery['slug'] = $slug;
        }

        if ($keyword) {
            $termsQuery['search'] = $keyword;
        }
        // if ($hierarchical) {
        //     $termsQuery['hierarchical'] = true;
        // }


        // $termsQuery['parent'] = 0;





        $terms = get_terms($termsQuery);

        /* Same filters without the page window, for the table's pager. */
        $countQuery = $termsQuery;
        unset($countQuery['number'], $countQuery['offset']);
        $total = wp_count_terms($countQuery);
        $total = is_wp_error($total) ? 0 : (int) $total;
        $response['total'] = $total;
        $response['max_pages'] = $number ? (int) ceil($total / $number) : 1;




        if (!is_wp_error($terms)) {
            foreach ($terms as &$term) {
                $thumbnail = get_term_meta($term->term_id, 'thumbnail', true);

                $term->thumbnail = $thumbnail;

                /*
                    The editors save the image as {id, title, src}. Passing that
                    array to wp_get_attachment_url() returned false for every
                    term, so the URL is taken from `src`, falling back to the
                    attachment when only an id is present (and to a bare ID or
                    URL, should older data hold one).
                */
                $thumbnail_url = '';
                if (is_array($thumbnail)) {
                    if (!empty($thumbnail['src'])) {
                        $thumbnail_url = $thumbnail['src'];
                    } elseif (!empty($thumbnail['id'])) {
                        $thumbnail_url = wp_get_attachment_url(absint($thumbnail['id'])) ?: '';
                    }
                } elseif (is_numeric($thumbnail) && $thumbnail > 0) {
                    $thumbnail_url = wp_get_attachment_url(absint($thumbnail)) ?: '';
                } elseif (is_string($thumbnail) && filter_var($thumbnail, FILTER_VALIDATE_URL)) {
                    $thumbnail_url = $thumbnail;
                }
                $term->thumbnail_url = $thumbnail_url;

                // Optional: Get a specific image size
                // $term->thumbnail_url = $thumbnail_id
                //     ? wp_get_attachment_image_url($thumbnail_id, 'medium')
                //     : '';
            }
        }
        unset($term);


        if (!is_wp_error($terms)) {
            $terms = array_values($terms);



            // foreach ($terms as $term) {

            //     $term_id = isset($term->term_id) ? $term->term_id : "";
            //     //$thumbnail = get_term_meta($term_id, "thumbnail", true);

            //     //$term->thumbnail = $thumbnail;
            // }

            if ($hierarchical) {
                $category_tree = build_category_tree($terms);



                $response['terms'] = $category_tree;
            } else {
                $response['terms'] = $terms;
            }
        }











        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_term($request)
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


        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : null;
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $slug     = isset($request['slug']) ? sanitize_text_field($request['slug']) : '';
        $description     = isset($request['description']) ? sanitize_text_field($request['description']) : '';
        $parent     = isset($request['parent']) ? sanitize_text_field($request['parent']) : '';







        $response = [];

        if (empty($taxonomy)) {
            die(wp_json_encode($response));
        }





        $term = wp_insert_term(
            $name,
            $taxonomy,
            [
                'slug'   => $slug,
                'description' => $description,
                'parent' => $parent, // Parent term ID
            ]
        );








        $response['term'] = $term;









        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_terms($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $taxonomy     = isset($request['taxonomy']) ? ($request['taxonomy']) : '';


        $response = [];


        $deleted = false;

        if (!empty($ids)) {

            foreach ($ids as $id) {
                $deleted = wp_delete_term($id, $taxonomy);
            }
        }

        $response['success'] = $deleted;


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
     * @param WP_REST_Request $request Post data.
     */
    public function get_brands_by_terms($request)
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
        $term_slug     = isset($request['term_slug']) ? sanitize_text_field($request['term_slug']) : '';





        $brands = get_terms([
            'taxonomy' => 'product_brand',
            'hide_empty' => true,
            'object_ids' => get_posts([
                'post_type'   => 'cmb_product',
                'numberposts' => -1,
                'fields'      => 'ids',
                'tax_query'   => [
                    [
                        'taxonomy' => $taxonomy,
                        'field'    => 'slug',
                        'terms'    => $term_slug,
                    ]
                ]
            ])
        ]);





        die(wp_json_encode($brands));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_term($request)
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


        $term_id     = isset($request['term_id']) ? sanitize_text_field($request['term_id']) : '';
        $term_slug     = isset($request['slug']) ? sanitize_text_field($request['slug']) : '';
        $parent     = isset($request['parent']) ? (bool)$request['parent'] : false;



        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : '';



        if (!empty($term_slug)) {
            $term = get_term_by('slug', $term_slug, $taxonomy);
        } else {
            $term = get_term($term_id, $taxonomy);
        }



        $thumbnail = get_term_meta($term_id, "thumbnail", true);

        if ($term) {
            $term->thumbnail = $thumbnail;
        }

        $response['term'] = $term;


        if (!empty($parent)) {

            $parentTerm = isset($term->parent) ? get_term($term->parent, $taxonomy) : [];

            if (!is_wp_error($parentTerm)) {
                $response['parent'] = $parentTerm;
            }
        }





        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_term($request)
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


        $term_id     = isset($request['term_id']) ? sanitize_text_field($request['term_id']) : '';
        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : '';
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';
        $slug     = isset($request['slug']) ? sanitize_text_field($request['slug']) : '';
        $description     = isset($request['description']) ? sanitize_text_field($request['description']) : '';
        $thumbnail     = isset($request['thumbnail']) ? stripslashes_deep($request['thumbnail']) : [];

        $args = array(
            'name' => $name,
            'slug' => $slug,
            'description' => $description
        );

        /*
            Parent, for hierarchical taxonomies only (categories) - brands, tags
            and badges send `parent: 0` from their editors and must not be
            touched. A term cannot be its own parent; deeper loops (parent set to
            one of its own children) are broken up by WordPress's
            wp_check_term_hierarchy_for_loops filter.
        */
        if (isset($request['parent']) && $taxonomy && is_taxonomy_hierarchical($taxonomy)) {
            $parent = absint($request['parent']);
            if ($parent !== absint($term_id)) {
                $args['parent'] = $parent;
            }
        }

        $result = wp_update_term($term_id, $taxonomy, $args);


        if (is_wp_error($result)) {
            $response['errors'] = 'Error: ' . $result->get_error_message();
        } else {
            $response['success'] = 'Term updated successfully!';

            update_term_meta($term_id, "thumbnail", $thumbnail);
        }




        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function handle_external_login($request)
    {


        $params = $request->get_json_params();
        $id_token = $params['token'];


        // Validate Google token
        $response = wp_remote_get("https://oauth2.googleapis.com/tokeninfo?id_token=$id_token");
        if (is_wp_error($response)) return new WP_Error('google_error', 'Token validation failed', ['status' => 403]);

        $user_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($user_data['email'])) return new WP_Error('no_email', 'Email not found', ['status' => 403]);

        $email = sanitize_email($user_data['email']);
        $user = get_user_by('email', $email);

        // Auto-create user if not exist
        if (!$user) {
            $username = sanitize_user(current(explode('@', $email)), true);
            $random_password = wp_generate_password();
            $user_id = wp_create_user($username, $random_password, $email);
            if (is_wp_error($user_id)) return $user_id;
            $user = get_user_by('id', $user_id);
        }

        // Generate JWT token
        // $token = jwt_auth_generate_token($user->ID);


        // Generate JWT manually
        $secret_key = defined('JWT_AUTH_SECRET_KEY') ? JWT_AUTH_SECRET_KEY : 'your-secret-key';
        $issued_at = time();
        $not_before = $issued_at;
        $expire = $issued_at + (DAY_IN_SECONDS * 7); // token valid for 7 days

        $payload = [
            'iss' => get_bloginfo('url'),
            'iat' => $issued_at,
            'nbf' => $not_before,
            'exp' => $expire,
            'data' => [
                'user' => [
                    'id' => $user->ID,
                ],
            ],
        ];

        $token = JWT::encode($payload, $secret_key, 'HS256');






        $response = [
            'token' => $token,
            'user' => [
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
            ]
        ];


        die(wp_json_encode($response));
    }













    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_products($request)
    {
        $token = $request->get_header('Authorization');

        $isAdmin = false;

        if ($token) {
            //return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));

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



            $user = get_user_by('id', $user_id);

            if (!$user) {
                return new WP_Error('user_not_found', 'User not found', array('status' => 404));
            }

            $userRoles = isset($user->roles) ? $user->roles : [];
            /* The roles the Products menu is open to. Store managers used to see
               published products only - never a draft or pending one. */
            $isAdmin = (bool) array_intersect(['administrator', 'store_manager'], (array) $userRoles);
        }


        $body = $request->get_body();



        $post_type     = isset($request['post_type']) ? stripslashes_deep($request['post_type']) : ['post'];
        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $posts_per_page     = isset($request['posts_per_page']) ? absint($request['posts_per_page']) : 20;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? ($request['categories']) : [];
        $brand     = isset($request['brand']) ? sanitize_text_field($request['brand']) : '';
        $brands     = isset($request['brands']) ? stripslashes_deep($request['brands']) : [];
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $tags     = isset($request['tags']) ? stripslashes_deep($request['tags']) : [];
        $post__in     = isset($request['post__in']) ? stripslashes_deep($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? stripslashes_deep($request['post__not_in']) : [];
        $stock_status     = isset($request['stock_status']) ? stripslashes_deep($request['stock_status']) : null;

        $is_admin     = isset($request['is_admin']) ? ($request['is_admin']) : false;




        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'cmb_product';
        $query_args['post_status'] = 'publish';

        if ($isAdmin) {
            $query_args['post_status'] = 'any';

            /* The Products screen's Status filter. It was sent but never read. */
            $wanted_status = isset($request['status']) ? sanitize_key($request['status']) : '';

            if (in_array($wanted_status, array('publish', 'draft', 'pending', 'private', 'future'), true)) {
                $query_args['post_status'] = $wanted_status;
            }
        }




        // if (!empty($post_type)) {
        //     $query_args['post_type'] = $post_type;
        // }

        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = [$post__not_in];
        }

        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => [$tag],
                'operator'    => 'IN',
            );
        }
        if (!empty($tags)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $tags,
                'operator'    => 'IN',
            );
        }

        if (!empty($brand)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => [$brand],
                'operator'    => 'IN',
            );
        }
        if (!empty($brands)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $brands,
                'operator'    => 'IN',
            );
        }



        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$category],
                'operator'    => 'IN',
            );
        }

        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
                'operator'    => 'IN',
            );
        }



        if (!$is_admin) {

            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'slug',
                'terms'    => ['exclude-from-search', 'exclude-from-catalog'],
                'operator'    => 'NOT IN',
            );
        }

        /*
           Stock lives in cstore_products. This filtered on a stock_status post
           meta that only one product has, so "In stock" found 1 product of 35.
        */
        if ($stock_status) {
            global $wpdb;

            $wanted_stock = array_values(array_intersect(array_map('sanitize_key', (array) $stock_status), array('instock', 'outofstock')));

            if (!empty($wanted_stock)) {
                $placeholders = implode(',', array_fill(0, count($wanted_stock), '%s'));
                $stock_ids = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT product_id FROM {$wpdb->prefix}cstore_products WHERE stock_status IN ($placeholders)", $wanted_stock)));

                if (!empty($query_args['post__in'])) {
                    $stock_ids = array_values(array_intersect($stock_ids, array_map('intval', (array) $query_args['post__in'])));
                }

                /* No match must mean no products, not every product. */
                $query_args['post__in'] = !empty($stock_ids) ? $stock_ids : array(0);
            }
        }








        if (!empty($posts_per_page)) {
            $query_args['posts_per_page'] = $posts_per_page;
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
                $sku = get_post_meta($post_id, 'sku', true);
                $bulk_prices = get_post_meta($post_id, 'bulk_prices', true);
                $price_type = get_post_meta($post_id, 'price_type', true);

                $price_type = !empty($price_type) ? $price_type : 'simple';

                $sale_price = get_post_meta($post_id, 'sale_price', true);
                $regular_price = get_post_meta($post_id, 'regular_price', true);




                $stock_status = get_post_meta($post_id, 'stock_status', true);

                $variations = get_post_meta($post_id, 'variations', true);
                $attributes = get_post_meta($post_id, 'attributes', true);

                $categories = get_the_terms($post_id, 'product_cat');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'product_tag');
                $tags = $tags ? $tags : [];

                $brands = get_the_terms($post_id, 'product_brand');
                $brands = $brands ? $brands : [];

                $visibility = get_the_terms($post_id, 'product_visibility');
                $visibility = $visibility ? $visibility : [];





                $categoriesData = [];
                foreach ($categories as $index => $category) {



                    $categoriesData[$index] = [
                        "term_id" => $category->term_id,
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }

                $visibilityData = [];
                foreach ($visibility as $index => $category) {



                    $visibilityData[$index] = [
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => $tag->term_id,
                        "name" => $tag->name
                    ];
                }

                $brandsData = [];
                foreach ($brands as $index => $brand) {

                    $brandsData[$index] = [
                        "term_id" => $brand->term_id,
                        "name" => $brand->name
                    ];
                }




                $post_thumbnail = [
                    "id" => get_post_thumbnail_id($post_id),
                    "title" => "",
                    "src" => $post_thumbnail_url,
                ];



                $ComboStoreProduct = new ComboStoreProduct();

                $product_data = $ComboStoreProduct->get_product(['product_id' => $post_id]);

                $entry = [
                    "id" => $post_id,
                    "post_type" => isset($postData->post_type) ? $postData->post_type : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "slug" => isset($postData->post_name) ? $postData->post_name : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "sku" => $sku,
                    "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brandsData,
                    "visibility" => $visibilityData,

                ];

                if ($product_data) {


                    $sale_price = !empty($product_data['sale_price']) ? ($product_data['sale_price']) : 0;
                    $regular_price = !empty($product_data['regular_price']) ? ($product_data['regular_price']) : 0;
                    $price =  !empty($sale_price) ? $sale_price : $regular_price;



                    $json_fields = [
                        'gallery',
                        'bulk_prices',
                        'addons',
                        'downloads',
                        'upsells',
                        'crosssells',
                        'faq',
                        'attributes',
                        'related_products',
                    ];

                    foreach ($json_fields as $field) {

                        if (!array_key_exists($field, $product_data)) {
                            continue;
                        }

                        if (empty($product_data[$field])) {
                            $product_data[$field] = [];
                            continue;
                        }

                        $decoded = json_decode($product_data[$field], true);

                        // If valid JSON, use decoded array/value.
                        // Otherwise keep the original value.
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $product_data[$field] = $decoded;
                        }
                    }


                    unset($product_data['id']);


                    $variations = $ComboStoreProduct->get_variations(['product_id' => $postData->ID]);



                    foreach ($variations as &$variation) {



                        // Decode thumbnail JSON
                        if (!empty($variation->thumbnail)) {
                            $thumbnail = json_decode($variation->thumbnail, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                $variation->thumbnail = $thumbnail;
                            }
                        } else {
                            $variation->thumbnail = [];
                        }

                        // Decode attributes if they are still JSON strings
                        if (!empty($variation->attributes) && is_string($variation->attributes)) {
                            $attributes = json_decode($variation->attributes, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                $variation->attributes = $attributes;
                            }
                        } else {
                            $variation->attributes = $variation->attributes ?? [];
                        }

                        $variation->variation_id = $variation->id;
                    }



                    $entry['price'] = $price;
                    $entry['variations'] = $variations;




                    $entries[] = array_merge($entry, $product_data);
                } else {
                    $entries[] = $entry;
                }





            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;


        $max_pages = ceil($total / $posts_per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function query_products_stocks($request)
    {
        global $wpdb;

        $token = $request->get_header('Authorization');

        $isAdmin = false;

        if ($token) {
            //return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));

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



            $user = get_user_by('id', $user_id);

            if (!$user) {
                return new WP_Error('user_not_found', 'User not found', array('status' => 404));
            }

            $userRoles = isset($user->roles) ? $user->roles : [];
            $isAdmin = in_array('administrator', $userRoles) ? true : false;
        }


        $body = $request->get_body();



        $post_type     = isset($request['post_type']) ? stripslashes_deep($request['post_type']) : ['post'];
        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $posts_per_page     = isset($request['posts_per_page']) ? absint($request['posts_per_page']) : 20;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? ($request['categories']) : [];
        $brand     = isset($request['brand']) ? sanitize_text_field($request['brand']) : '';
        $brands     = isset($request['brands']) ? stripslashes_deep($request['brands']) : [];
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $tags     = isset($request['tags']) ? stripslashes_deep($request['tags']) : [];
        $post__in     = isset($request['post__in']) ? stripslashes_deep($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? stripslashes_deep($request['post__not_in']) : [];
        $stock_status     = isset($request['stock_status']) ? stripslashes_deep($request['stock_status']) : null;

        $is_admin     = isset($request['is_admin']) ? ($request['is_admin']) : false;




        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'cmb_product';
        $query_args['post_status'] = 'publish';

        if ($isAdmin) {
            $query_args['post_status'] = 'any';
        }




        // if (!empty($post_type)) {
        //     $query_args['post_type'] = $post_type;
        // }

        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = [$post__not_in];
        }

        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => [$tag],
                'operator'    => 'IN',
            );
        }
        if (!empty($tags)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $tags,
                'operator'    => 'IN',
            );
        }

        if (!empty($brand)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => [$brand],
                'operator'    => 'IN',
            );
        }
        if (!empty($brands)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $brands,
                'operator'    => 'IN',
            );
        }



        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$category],
                'operator'    => 'IN',
            );
        }

        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
                'operator'    => 'IN',
            );
        }



        if (!$is_admin) {

            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'slug',
                'terms'    => ['exclude-from-search', 'exclude-from-catalog'],
                'operator'    => 'NOT IN',
            );
        }









        if (!empty($posts_per_page)) {
            $query_args['posts_per_page'] = $posts_per_page;
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
        /*
            Stock lives in the products table, not post meta, so these two filters
            become a list of ids. A number only looks at products that track stock:
            most have a blank status and a count of 0, which is not "low".
        */
        $stock_conditions = [];

        if ($stock_status && is_string($stock_status)) {
            $stock_conditions[] = $wpdb->prepare('stock_status = %s', $stock_status);
        }

        if (isset($request['min_stock']) && is_numeric($request['min_stock'])) {
            $stock_conditions[] = "stock_status <> ''";
            $stock_conditions[] = $wpdb->prepare('stock_count <= %d', (int) $request['min_stock']);
        }

        if (!empty($stock_conditions)) {
            $stock_ids = $wpdb->get_col("SELECT product_id FROM {$wpdb->prefix}cstore_products WHERE " . implode(' AND ', $stock_conditions));

            if (!empty($post__in)) {
                $stock_ids = array_intersect($stock_ids, (array) $post__in);
            }

            /* An empty post__in means "no limit", so nothing matching needs an id that cannot exist. */
            $query_args['post__in'] = !empty($stock_ids) ? $stock_ids : [0];
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
                $sku = get_post_meta($post_id, 'sku', true);
                $bulk_prices = get_post_meta($post_id, 'bulk_prices', true);
                $price_type = get_post_meta($post_id, 'price_type', true);

                $price_type = !empty($price_type) ? $price_type : 'simple';

                $sale_price = get_post_meta($post_id, 'sale_price', true);
                $regular_price = get_post_meta($post_id, 'regular_price', true);




                $stock_status = get_post_meta($post_id, 'stock_status', true);

                $variations = get_post_meta($post_id, 'variations', true);
                $attributes = get_post_meta($post_id, 'attributes', true);

                $categories = get_the_terms($post_id, 'product_cat');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'product_tag');
                $tags = $tags ? $tags : [];

                $brands = get_the_terms($post_id, 'product_brand');
                $brands = $brands ? $brands : [];

                $visibility = get_the_terms($post_id, 'product_visibility');
                $visibility = $visibility ? $visibility : [];





                $categoriesData = [];
                foreach ($categories as $index => $category) {



                    $categoriesData[$index] = [
                        "term_id" => $category->term_id,
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }

                $visibilityData = [];
                foreach ($visibility as $index => $category) {



                    $visibilityData[$index] = [
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => $tag->term_id,
                        "name" => $tag->name
                    ];
                }

                $brandsData = [];
                foreach ($brands as $index => $brand) {

                    $brandsData[$index] = [
                        "term_id" => $brand->term_id,
                        "name" => $brand->name
                    ];
                }




                $post_thumbnail = [
                    "id" => get_post_thumbnail_id($post_id),
                    "title" => "",
                    "src" => $post_thumbnail_url,
                ];



                $ComboStoreProduct = new ComboStoreProduct();

                $product_data = $ComboStoreProduct->get_product(['product_id' => $post_id]);

                $entry = [
                    "id" => $post_id,
                    "post_type" => isset($postData->post_type) ? $postData->post_type : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "slug" => isset($postData->post_name) ? $postData->post_name : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "sku" => $sku,
                    "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brandsData,
                    "visibility" => $visibilityData,

                ];

                if ($product_data) {


                    $sale_price = !empty($product_data['sale_price']) ? ($product_data['sale_price']) : 0;
                    $regular_price = !empty($product_data['regular_price']) ? ($product_data['regular_price']) : 0;
                    $price =  !empty($sale_price) ? $sale_price : $regular_price;



                    $json_fields = [
                        'gallery',
                        'bulk_prices',
                        'addons',
                        'downloads',
                        'upsells',
                        'crosssells',
                        'faq',
                        'attributes',
                        'related_products',
                    ];

                    foreach ($json_fields as $field) {

                        if (!array_key_exists($field, $product_data)) {
                            continue;
                        }

                        if (empty($product_data[$field])) {
                            $product_data[$field] = [];
                            continue;
                        }

                        $decoded = json_decode($product_data[$field], true);

                        // If valid JSON, use decoded array/value.
                        // Otherwise keep the original value.
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $product_data[$field] = $decoded;
                        }
                    }


                    unset($product_data['id']);


                    $variations = $ComboStoreProduct->get_variations(['product_id' => $postData->ID]);



                    foreach ($variations as &$variation) {

                        // Decode thumbnail JSON
                        if (!empty($variation->thumbnail)) {
                            $thumbnail = json_decode($variation->thumbnail, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                $variation->thumbnail = $thumbnail;
                            }
                        } else {
                            $variation->thumbnail = [];
                        }

                        // Decode attributes if they are still JSON strings
                        if (!empty($variation->attributes) && is_string($variation->attributes)) {
                            $attributes = json_decode($variation->attributes, true);

                            if (json_last_error() === JSON_ERROR_NONE) {
                                $variation->attributes = $attributes;
                            }
                        } else {
                            $variation->attributes = $variation->attributes ?? [];
                        }

                        $variation->variation_id = $variation->id;
                    }



                    $entry['price'] = $price;
                    $entry['variations'] = $variations;




                    $entries[] = array_merge($entry, $product_data);
                } else {
                    $entries[] = $entry;
                }





            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;


        $max_pages = ceil($total / $posts_per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;



        die(wp_json_encode($response));
    }
    /*
        add_stock_histoy() was removed: a public test endpoint that ignored the
        request and always took 2 units from product 10018 / variation 352.
    */










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_low_stock_products($request)
    {
        $token = $request->get_header('Authorization');

        $isAdmin = false;

        if ($token) {
            //return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));

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



            $user = get_user_by('id', $user_id);

            if (!$user) {
                return new WP_Error('user_not_found', 'User not found', array('status' => 404));
            }

            $userRoles = isset($user->roles) ? $user->roles : [];
            $isAdmin = in_array('administrator', $userRoles) ? true : false;
        }


        $body = $request->get_body();

        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 6;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? ($request['categories']) : [];
        $brand     = isset($request['brand']) ? sanitize_text_field($request['brand']) : "";
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $post__in     = isset($request['post__in']) ? ($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? ($request['post__not_in']) : [];

        $is_admin     = isset($request['is_admin']) ? ($request['is_admin']) : false;
        $min_stock     = isset($request['min_stock']) ? sanitize_text_field($request['min_stock']) : 5;



        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'cmb_product';
        $query_args['post_status'] = 'publish';

        if ($isAdmin) {
            $query_args['post_status'] = 'any';
        }




        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = [$post__not_in];
        }

        if (!empty($tag)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => [$tag],
                'operator'    => 'IN',
            );
        }


        if (!empty($brand)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => [$brand],
                'operator'    => 'IN',
            );
        }



        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => [$category],
                'operator'    => 'IN',
            );
        }

        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
                'operator'    => 'IN',
            );
        }

        if (!$is_admin) {

            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'slug',
                'terms'    => ['exclude-from-search', 'exclude-from-catalog'],
                'operator'    => 'NOT IN',
            );
        }




        $meta_query = [
            'relation' => 'AND',
            array(
                'key'     => 'stock_status',
                'value'   => 'instock',
                'compare' => '='
            ),
            array(
                'key'     => 'stock_count',
                'value'   => $min_stock,
                'compare' => '<=',
                'type'    => 'NUMERIC'
            )

        ];








        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
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
                $sku = get_post_meta($post_id, 'sku', true);
                $bulk_prices = get_post_meta($post_id, 'bulk_prices', true);
                $attributes = get_post_meta($post_id, 'attributes', true);
                $price_type = get_post_meta($post_id, 'price_type', true);
                $sale_price = get_post_meta($post_id, 'sale_price', true);
                $regular_price = get_post_meta($post_id, 'regular_price', true);
                $trade_price = get_post_meta($post_id, 'trade_price', true);




                $stock_status = get_post_meta($post_id, 'stock_status', true);
                $stock_count = get_post_meta($post_id, 'stock_count', true);
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
                $related_products = get_post_meta($post_id, 'related_products', true);
                $variations = get_post_meta($post_id, 'variations', true);
                $attributes = get_post_meta($post_id, 'attributes', true);

                $categories = get_the_terms($post_id, 'product_cat');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'product_tag');
                $tags = $tags ? $tags : [];

                $brands = get_the_terms($post_id, 'product_brand');
                $brands = $brands ? $brands : [];

                $visibility = get_the_terms($post_id, 'product_visibility');
                $visibility = $visibility ? $visibility : [];





                $categoriesData = [];
                foreach ($categories as $index => $category) {



                    $categoriesData[$index] = [
                        "term_id" => $category->term_id,
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }

                $visibilityData = [];
                foreach ($visibility as $index => $category) {



                    $visibilityData[$index] = [
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => $tag->term_id,
                        "name" => $tag->name
                    ];
                }

                $brandsData = [];
                foreach ($brands as $index => $brand) {

                    $brandsData[$index] = [
                        "term_id" => $brand->term_id,
                        "name" => $brand->name
                    ];
                }





                $post_thumbnail = [
                    "id" => "",
                    "title" => "",
                    "src" => $post_thumbnail_url,
                ];

                // $new_entry = [];

                // $new_entry['id'] = isset($postData->ID) ? $postData->ID : null;
                // $new_entry['title'] = isset($postData->post_title) ? $postData->post_title : null;
                // $new_entry['slug'] = isset($postData->post_name) ? $postData->post_name : null;
                // $new_entry['status'] = isset($postData->post_status) ? $postData->post_status : null;
                // $new_entry['sku'] = $sku;
                // $new_entry['post_thumbnail'] = !empty($post_thumbnail) ? $post_thumbnail : [];
                // $new_entry['post_thumbnail_url'] = $post_thumbnail_url;
                // $new_entry['featured'] = $featured;
                // $new_entry['sale_price'] = $sale_price;
                // $new_entry['regular_price'] = $regular_price;
                // $new_entry['stock_count'] = $stock_quantity;
                // $new_entry['stock_status'] = $stock_status;
                // $new_entry['categories'] = $categoriesData;
                // $new_entry['tags'] = $tagsData;
                // $new_entry['brands'] = $brandsData;
                // $new_entry['visibility'] = $visibilityData;
                // $new_entry['variations'] = $variations;




                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "slug" => isset($postData->post_name) ? $postData->post_name : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "sku" => $sku,
                    "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "trade_price" => $trade_price,
                    "sale_price" => $sale_price,
                    "regular_price" => $regular_price,
                    "price" => !empty($sale_price) ? $sale_price : $regular_price,
                    "price_type" => $price_type,
                    "bulk_prices" => $bulk_prices,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brandsData,
                    "visibility" => $visibilityData,
                    "stock_status" => $stock_status,
                    "stock_count" => $stock_count,
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
                    "related_products" => $related_products,
                    "variations" => $variations,
                    "attributes" => $attributes,
                ];



            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;


        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_product_feed($request)
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

        $body = $request->get_body();



        /*
            Feed filters. `category`, `brand` and `tag` take one slug or a
            comma-separated list ("baby,toys"); `include`/`exclude` take post
            IDs the same way. Price and stock live in the cstore_products table,
            not post meta, so they are resolved to a post__in list below.
        */
        $slug_list = function ($value) {
            if (is_array($value)) $value = implode(',', $value);
            return array_values(array_filter(array_map('sanitize_title', explode(',', (string) $value))));
        };
        $id_list = function ($value) {
            if (is_array($value)) $value = implode(',', $value);
            return array_values(array_filter(array_map('absint', explode(',', (string) $value))));
        };

        $paged     = isset($request['paged']) ? max(1, absint($request['paged'])) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 100;
        $per_page     = $per_page > 0 ? min($per_page, 1000) : 100;

        // error_log($per_page);


        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $orderby     = in_array($orderby, ['date', 'modified', 'title', 'menu_order', 'rand', 'ID'], true) ? $orderby : "";
        $order     = isset($request['order']) && strtoupper($request['order']) === 'ASC' ? 'ASC' : 'DESC';
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $categories     = array_merge(
            $slug_list($request['category'] ?? ''),
            $slug_list($request['categories'] ?? '')
        );
        $brands_in     = $slug_list($request['brand'] ?? '');
        $tags_in     = $slug_list($request['tag'] ?? '');
        $post__in     = array_merge($id_list($request['post__in'] ?? ''), $id_list($request['include'] ?? ''));
        $post__not_in     = array_merge($id_list($request['post__not_in'] ?? ''), $id_list($request['exclude'] ?? ''));
        $stock_status     = isset($request['stock_status']) ? sanitize_key($request['stock_status']) : "";
        $stock_status     = in_array($stock_status, ['instock', 'outofstock'], true) ? $stock_status : "";
        $min_price     = isset($request['min_price']) && $request['min_price'] !== '' ? floatval($request['min_price']) : null;
        $max_price     = isset($request['max_price']) && $request['max_price'] !== '' ? floatval($request['max_price']) : null;
        $on_sale     = !empty($request['on_sale']);
        /* Full descriptions push a 1000-product feed past Next.js's 2 MB fetch
           cache and toward Vercel's 4.5 MB response limit; ads need far less. */
        $description_length     = isset($request['description_length']) ? absint($request['description_length']) : 1000;
        $description_length     = min(max($description_length, 50), 5000);


        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'cmb_product';
        $query_args['post_status'] = 'publish';


        /* Effective price = sale price when set, else regular price - the same
           rule the entries below use for `price`. */
        if ($stock_status || $min_price !== null || $max_price !== null || $on_sale) {
            global $wpdb;
            $products_table = $wpdb->prefix . 'cstore_products';
            $effective = "(CASE WHEN sale_price > 0 THEN sale_price ELSE regular_price END)";
            $where = ['1=1'];
            $values = [];

            if ($stock_status) {
                $where[] = 'stock_status = %s';
                $values[] = $stock_status;
            }
            if ($min_price !== null) {
                $where[] = "$effective >= %f";
                $values[] = $min_price;
            }
            if ($max_price !== null) {
                $where[] = "$effective <= %f";
                $values[] = $max_price;
            }
            if ($on_sale) {
                $where[] = 'sale_price > 0 AND sale_price < regular_price';
            }

            $sql = "SELECT product_id FROM $products_table WHERE " . implode(' AND ', $where);
            $matched = array_map('absint', $wpdb->get_col($values ? $wpdb->prepare($sql, $values) : $sql));

            $post__in = $post__in ? array_values(array_intersect($post__in, $matched)) : $matched;
            /* No match must mean no products, not "no restriction". */
            if (empty($post__in)) $post__in = [0];
        }


        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = $post__not_in;
        }

        if (!empty($tags_in)) {
            $tax_query[] = array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => $tags_in,
                'operator'    => 'IN',
            );
        }
        if (!empty($brands_in)) {
            $tax_query[] = array(
                'taxonomy' => 'product_brand',
                'field'    => 'slug',
                'terms'    => $brands_in,
                'operator'    => 'IN',
            );
        }

        if (!empty($categories)) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $categories,
                'operator'    => 'IN',
            );
        }

        $tax_query[] = array(
            'taxonomy' => 'product_visibility',
            'field'    => 'slug',
            'terms'    => ['exclude-from-search', 'exclude-from-catalog'],
            'operator'    => 'NOT IN',
        );



        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
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




        /*
            Feed-sized query. The feed can ask for 1000 products, so nothing here
            runs per product: price/stock/gallery rows come from cstore_products
            in one query, thumbnails and terms are primed in bulk, and only the
            fields the feed uses are returned. The old full-row merge produced
            ~4 MB of JSON for 1000 products - over Next.js's 2 MB fetch-cache
            limit, so the feed was never cached and every hit came back here.
        */
        $query_args['no_found_rows'] = false;
        $query_args['update_post_meta_cache'] = true;
        $query_args['update_post_term_cache'] = true;


        $wp_query = new WP_Query($query_args);
        $posts = $wp_query->posts;

        $entries = [];

        if (!empty($posts)) {
            update_post_thumbnail_cache($wp_query);

            global $wpdb;
            $post_ids = array_map('intval', wp_list_pluck($posts, 'ID'));
            $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, product_id, sku, stock_status, stock_count, regular_price, sale_price, gallery
                     FROM {$wpdb->prefix}cstore_products WHERE product_id IN ($placeholders)",
                    $post_ids
                ),
                ARRAY_A
            );
            $rows_by_post = [];
            foreach ($rows as $row) {
                $rows_by_post[(int) $row['product_id']] = $row;
            }

            $term_list = function ($post_id, $taxonomy) {
                $terms = get_the_terms($post_id, $taxonomy);
                if (!$terms || is_wp_error($terms)) return [];
                return array_values(array_map(function ($term) {
                    return ["name" => $term->name, "slug" => $term->slug];
                }, $terms));
            };

            foreach ($posts as $postData) {
                $post_id = (int) $postData->ID;
                $row = $rows_by_post[$post_id] ?? null;

                /* Block tags become spaces first so paragraphs do not run
                   together ("PiecesPotty") once the HTML is stripped. */
                $description = strip_shortcodes(
                    !empty($postData->post_content) ? $postData->post_content : $postData->post_excerpt
                );
                $description = preg_replace('/<\/?(p|br|div|li|ul|ol|h[1-6]|tr|td)\b[^>]*>/i', ' ', (string) $description);
                $description = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($description)));
                if (mb_strlen($description) > $description_length) {
                    $description = rtrim(mb_substr($description, 0, $description_length - 1)) . '…';
                }

                $gallery = [];
                if ($row && !empty($row['gallery'])) {
                    $decoded = json_decode($row['gallery'], true);
                    foreach (is_array($decoded) ? $decoded : [] as $image) {
                        if (!empty($image['src'])) $gallery[] = ["src" => $image['src']];
                    }
                }

                $sale_price = $row ? (float) $row['sale_price'] : 0;
                $regular_price = $row ? (float) $row['regular_price'] : 0;

                $entries[] = [
                    /* cstore_products row id - what the Pixel sends as content_ids.
                       Products without a row fall back to the post ID, as before. */
                    "id" => $row ? $row['id'] : (string) $post_id,
                    "product_id" => $post_id,
                    "title" => $postData->post_title,
                    "slug" => $postData->post_name,
                    "description" => $description,
                    "post_thumbnail_url" => get_the_post_thumbnail_url($post_id) ?: '',
                    "gallery" => $gallery,
                    "sku" => $row ? (string) $row['sku'] : '',
                    "stock_status" => $row ? $row['stock_status'] : '',
                    "stock_count" => $row ? $row['stock_count'] : null,
                    "regular_price" => $row ? $row['regular_price'] : '0',
                    "sale_price" => $row ? $row['sale_price'] : '0',
                    "price" => $sale_price > 0 ? $sale_price : $regular_price,
                    "categories" => $term_list($post_id, 'product_cat'),
                    "tags" => $term_list($post_id, 'product_tag'),
                    "brands" => $term_list($post_id, 'product_brand'),
                ];
            }
        }

        $total = $wp_query->found_posts;
        $response = [];
        $response['total'] = $total;
        $response['max_pages'] = $per_page ? ceil($total / $per_page) : 0;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }











    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_wishlists($request)
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
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $post__in     = isset($request['post__in']) ? ($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? ($request['post__not_in']) : [];



        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'wishlist';
        $query_args['post_status'] = 'any';


        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }
        if (!empty($post__in)) {
            $query_args['post__in'] = $post__in;
        }
        if (!empty($post__not_in)) {
            $query_args['post__not_in'] = $post__not_in;
        }


        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
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
                $sale_price = get_post_meta($post_id, 'sale_price', true);
                $sku = get_post_meta($post_id, 'sku', true);
                $regular_price = get_post_meta($post_id, 'regular_price', true);
                $stock_status = get_post_meta($post_id, 'stock_status', true);
                $stock_count = get_post_meta($post_id, 'stock_count', true);
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
                $related_products = get_post_meta($post_id, 'related_products', true);
                $variations = get_post_meta($post_id, 'variations', true);

                $categories = get_the_terms($post_id, 'product_cat');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'product_tag');
                $tags = $tags ? $tags : [];

                $categoriesData = [];
                foreach ($categories as $index => $category) {



                    $categoriesData[$index] = [
                        "term_id" => $category->term_id,
                        "name" => $category->name,
                        "slug" => $category->slug,
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => $tag->term_id,
                        "name" => $tag->name
                    ];
                }


                $categories = [];
                $tags = [];
                $brands = [];
                $brands = [];

                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "sku" => $sku,
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "sale_price" => $sale_price,
                    "regular_price" => $regular_price,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brands,
                    "stock_status" => $stock_status,
                    "stock_count" => $stock_count,
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
                    "related_products" => $related_products,
                    "variations" => $variations,
                ];

            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;


        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_coupon($request)
    {
        /* A valid token used to be enough: any customer could edit any coupon. */
        $staff = $this->require_coupon_staff($request);
        if (is_wp_error($staff)) {
            return $staff;
        }



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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        /*
            Only coupons. The user, rider, seller and supplier editors were copies
            of the coupon editor and sent their person's user ID here, which read
            (and on save overwrote) whatever post had that number.
        */
        if (empty($post_id) || get_post_type($post_id) !== 'coupon') {
            return new WP_Error('not_a_coupon', 'That is not a coupon', array('status' => 400));
        }

        $post_title = $request->get_param('title');
        $post_status = $request->get_param('postStatus');




        $response = [];
        $postData = [
            "ID" => $post_id,
            "post_title" => $post_title,
            "post_status" => $post_status,
        ];
        wp_update_post($postData);


        if (isset($body_arr['couponType'])) {
            update_post_meta($post_id, 'couponType', $body_arr['couponType']);
        }
        if (isset($body_arr['couponCode'])) {
            update_post_meta($post_id, 'couponCode', $body_arr['couponCode']);
        }
        if (isset($body_arr['startDate'])) {
            update_post_meta($post_id, 'startDate', $body_arr['startDate']);
        }
        if (isset($body_arr['endDate'])) {
            update_post_meta($post_id, 'endDate', $body_arr['endDate']);
        }
        if (isset($body_arr['per_page'])) {

            update_post_meta($post_id, 'per_page', $body_arr['per_page']);
        }
        if (isset($body_arr['amount'])) {
            update_post_meta($post_id, 'amount', $body_arr['amount']);
        }
        if (isset($body_arr['amountUnit'])) {
            update_post_meta($post_id, 'amountUnit', $body_arr['amountUnit']);
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_redx_areas($request)
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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_title = $request->get_param('title');
        $post_status = $request->get_param('postStatus');




        $response = [];

        $RedxCourier = new RedxCourier();

        $getAreas = $RedxCourier->getAreas();



        die(wp_json_encode($getAreas));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_top_object_from_orders($request)
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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        // $post_title = $request->get_param('title');
        // $post_status = $request->get_param('postStatus');




        $response = [];

        $ComboStoreStats = new ComboStoreStats();

        $getAreas = $ComboStoreStats->get_top_object_from_orders($body_arr);



        die(wp_json_encode($getAreas));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_top_customers($request)
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

        /* This lists customers' contact details and spending, so it is for the roles the sidebar shows it to. */
        $user = get_user_by('id', $user_id);

        if (!$user || !array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view top customers', array('status' => 403));
        }
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        // $post_title = $request->get_param('title');
        // $post_status = $request->get_param('postStatus');




        $response = [];

        $ComboStoreStats = new ComboStoreStats();

        $getAreas = $ComboStoreStats->get_top_customers($body_arr);



        die(wp_json_encode($getAreas));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_top_selling_products($request)
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

        /* Sales and revenue figures, so it is for the roles the sidebar shows it to. */
        $user = get_user_by('id', $user_id);

        if (!$user || !array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view top selling products', array('status' => 403));
        }
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        // $post_title = $request->get_param('title');
        // $post_status = $request->get_param('postStatus');




        $response = [];

        $ComboStoreStats = new ComboStoreStats();

        $getTopSellingProducts = $ComboStoreStats->get_top_selling_products($body_arr);



        die(wp_json_encode($getTopSellingProducts));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_coupon($request)
    {
        /* Any coupon, or any post by ID, used to be readable by anyone. */
        $staff = $this->require_coupon_staff($request);
        if (is_wp_error($staff)) {
            return $staff;
        }

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




        $post_id     = isset($request['id']) ? absint($request['id']) : "";

        /*
            Only coupons. The user, rider, seller and supplier editors were copies
            of the coupon editor and sent their person's user ID here, which read
            (and on save overwrote) whatever post had that number.
        */
        if (empty($post_id) || get_post_type($post_id) !== 'coupon') {
            return new WP_Error('not_a_coupon', 'That is not a coupon', array('status' => 400));
        }

        $postData = get_post($post_id);


        $productData = [];


        $couponCode = get_post_meta($post_id, 'couponCode', true);
        $couponType = get_post_meta($post_id, 'couponType', true);
        $startDate = get_post_meta($post_id, 'startDate', true);
        $endDate = get_post_meta($post_id, 'endDate', true);
        $limit = get_post_meta($post_id, 'limit', true);
        $amount = get_post_meta($post_id, 'amount', true);

        $response = [];

        if (isset($postData->ID)) {
            $productData = [
                "id" => $postData->ID,
                "title" => $postData->post_title,
                "postStatus" => $postData->post_status,
                "couponCode" => $couponCode,
                "amount" => $amount,
                "couponType" => $couponType,
                "startDate" => $startDate,
                "endDate" => $endDate,
                "limit" => $limit,

            ];



            $response['coupon'] = $productData;
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_coupon_details($request)
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




        $couponCode     = isset($request['couponCode']) ? sanitize_text_field($request['couponCode']) : "";

        if (!empty($couponCode)) {

            $posts = get_posts([
                'post_type'  => 'coupon', // or your custom post type
                'post_status'  => 'publish', // or your custom post type
                'meta_key'   => 'couponCode',
                'meta_value' => $couponCode,
                'fields'     => 'ids', // return only IDs
            ]);



            if (!empty($posts)) {
                $post_id = $posts[0]; // first matching post ID
            } else {
                $response['errors'] = true;
                $response['message'] = __("Coupon doesn't exist.", 'combostore-server');
                die(wp_json_encode($response));
            }
        }


        $postData = get_post($post_id);


        $productData = [];


        $couponCode = get_post_meta($post_id, 'couponCode', true);
        $couponType = get_post_meta($post_id, 'couponType', true);
        $startDate = get_post_meta($post_id, 'startDate', true);
        $endDate = get_post_meta($post_id, 'endDate', true);
        $limit = get_post_meta($post_id, 'limit', true);
        $amount = get_post_meta($post_id, 'amount', true);

        $today = new DateTime(); // current date
        $start = new DateTime($startDate);
        $end   = new DateTime($endDate);

        if ($today <= $start) {
            $status = "waiting";

            $response['errors'] = true;
            $response['message'] = __("Oppps sorry! Coupon is waiting to publish.", 'combostore-server');
            die(wp_json_encode($response));
        } else if ($today >= $end) {
            $status = "expired";

            $response['errors'] = true;
            $response['message'] = __("Oppps sorry! Coupon has expired.", 'combostore-server');
            die(wp_json_encode($response));
        } else if ($today >= $start && $today <= $end) {
            $status = "active";
        } else {
            $status = "error";

            $response['errors'] = true;
            $response['message'] = __("There is an error.", 'combostore-server');
            die(wp_json_encode($response));
        }



        $productData = [
            "id" => $postData->ID,
            "title" => $postData->post_title,

            "postStatus" => $postData->post_status,
            "couponCode" => $couponCode,
            "amount" => $amount,
            "couponType" => $couponType,
            "startDate" => $startDate,
            "endDate" => $endDate,
            "limit" => $limit,
            "status" => $status,

        ];



        $response['coupon'] = $productData;


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

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }

        // Get the date range (last 7 days)
        $last_date = gmdate('Y-m-d 23:59:59');



        if ($range == 'custom') {
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'start_date' => $first_date,
            'end_date' => $last_date,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();

        $get_orders_range = $ComboStoreStats->get_orders_range($request);
        $get_purchases_range = $ComboStoreStats->get_purchases_range($request);
        $get_expenses_range = $ComboStoreStats->get_expenses_range($request);
        $get_refunds_range = $ComboStoreStats->get_refunds_range($request);

        $response['labels'] = $get_orders_range['labels'];
        $response['orders'] = $get_orders_range['dataset'];
        $response['purchases'] = $get_purchases_range['dataset'];
        $response['expenses'] = $get_expenses_range['dataset'];
        $response['refunds'] = $get_refunds_range['dataset'];








        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_stats_purchases($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }

        // Get the date range (last 7 days)
        $last_date = gmdate('Y-m-d 23:59:59');



        if ($range == 'custom') {
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'start_date' => $first_date,
            'end_date' => $last_date,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();

        $get_purchases_range = $ComboStoreStats->get_purchases_range($request);




        die(wp_json_encode($get_purchases_range));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_stats_expenses($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }

        // Get the date range (last 7 days)
        $last_date = gmdate('Y-m-d 23:59:59');



        if ($range == 'custom') {
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'first_date' => $first_date,
            'last_date' => $last_date,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();

        // $get_orders_range = $ComboStoreStats->get_orders_range($request);
        $get_expenses_range = $ComboStoreStats->get_stats_expenses($query_args);

        //$response['labels'] = $get_orders_range['labels'];

        die(wp_json_encode($get_expenses_range));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_stats_all($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }

        // Get the date range (last 7 days)
        $last_date = gmdate('Y-m-d 23:59:59');



        if ($range == 'custom') {
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'first_date' => $first_date,
            'last_date' => $last_date,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();

        $get_orders_stats = $ComboStoreStats->get_orders_stats($query_args);
        $get_stats_expenses = $ComboStoreStats->get_stats_expenses($query_args);
        $get_stats_purchases = $ComboStoreStats->get_stats_purchases($query_args);


        // $response['labels'] = $get_orders_range['labels'];
        $response['orders'] = $get_orders_stats;
        $response['purchases'] = $get_stats_purchases;
        $response['expenses'] = $get_stats_expenses;

        die(wp_json_encode($response));
    }








    public function get_store_total_overviews($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        }

        // Get the date range (last 7 days)
        $last_date = gmdate('Y-m-d 23:59:59');



        if ($range == 'custom') {
            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'start_date' => $first_date,
            'end_date' => $last_date,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();



        $total_products = $ComboStoreStats->total_products($user_id);
        $total_customers = $ComboStoreStats->total_customers($user_id);
        $total_orders = $ComboStoreStats->total_orders($query_args);
        $total_subscriptions = $ComboStoreStats->total_subscriptions($query_args);
        $total_orders_amount = $ComboStoreStats->get_orders_total_amount($query_args);
        $total_trade_price = $ComboStoreStats->get_orders_trade_price($query_args);
        $total_discount_amount = $ComboStoreStats->get_total_discount_amount($query_args);
        $total_tax_amount = $ComboStoreStats->get_total_tax_amount($query_args);
        $total_shipping_amount = $ComboStoreStats->get_total_shipping_amount($query_args);
        $total_gross_profit_amount = $ComboStoreStats->get_total_gross_profit_amount($query_args);
        $total_net_profit_amount = $ComboStoreStats->get_total_net_profit_amount($query_args);


        $total_purchase_amount = $ComboStoreStats->get_purchase_total_amount($query_args);
        $total_expenses_amount = $ComboStoreStats->get_expenses_total_amount($query_args);
        $total_orders_due_amount = $ComboStoreStats->get_orders_total_due_amount($query_args);
        $total_orders_pending_amount = $ComboStoreStats->get_orders_total_pending_amount($query_args);



        $response['total_products'] = $total_products;
        $response['total_customers'] = $total_customers;
        $response['total_orders'] = $total_orders;
        $response['total_subscriptions'] = $total_subscriptions;
        $response['total_orders_amount'] = $total_orders_amount;
        $response['total_discount_amount'] = $total_discount_amount;
        $response['total_tax_amount'] = $total_tax_amount;
        $response['total_trade_price'] = $total_trade_price;
        $response['total_gross_profit_amount'] = $total_gross_profit_amount;
        $response['total_net_profit_amount'] = $total_net_profit_amount;

        $response['total_shipping_amount'] = $total_shipping_amount;
        $response['total_purchase_amount'] = $total_purchase_amount;
        $response['total_expenses_amount'] = $total_expenses_amount;
        $response['total_due_amount'] = $total_orders_due_amount;
        $response['total_pending_amount'] = $total_orders_pending_amount;




        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_orders_stats($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $range     = isset($request['range']) ? sanitize_text_field($request['range']) : "7days";
        $customer_id     = isset($request['customer_id']) ? sanitize_text_field($request['customer_id']) : null;
        $product_id     = isset($request['product_id']) ? sanitize_text_field($request['product_id']) : null;
        $orderStatus     = isset($request['orderStatus']) ? sanitize_text_field($request['orderStatus']) : null;
        $paymentStatus     = isset($request['paymentStatus']) ? sanitize_text_field($request['paymentStatus']) : null;
        $paymentMethod     = isset($request['paymentMethod']) ? sanitize_text_field($request['paymentMethod']) : null;


        if ($range == '7days') {
            $days = 7;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
            $last_date = gmdate('Y-m-d 23:59:59');
        }
        if ($range == '15days') {
            $days = 15;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
            $last_date = gmdate('Y-m-d 23:59:59');
        }
        if ($range == '30days') {
            $days = 30;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
            $last_date = gmdate('Y-m-d 23:59:59');
        }
        if ($range == '6months') {
            $days = 180;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
            $last_date = gmdate('Y-m-d 23:59:59');
        }
        if ($range == '1year') {
            $days = 365;
            $first_date = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
            $last_date = gmdate('Y-m-d 23:59:59');
        }

        // Get the date range (last 7 days)


        if ($range == 'custom') {


            $first_date     = isset($request['first_date']) ? sanitize_text_field($request['first_date']) : "";
            $last_date     = isset($request['last_date']) ? sanitize_text_field($request['last_date']) : "";
        }



        $query_args = [
            'first_date' => $first_date,
            'last_date' => $last_date,
            'customer_id' => $customer_id,
            'product_id' => $product_id,
            'orderStatus' => $orderStatus,
            'paymentStatus' => $paymentStatus,
            'paymentMethod' => $paymentMethod,
        ];


        $ComboStoreStats = new ComboStoreStats();

        $stats = $ComboStoreStats->get_orders_stats($query_args);



        die(wp_json_encode($stats));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $post_data Post data.
     */
    public function get_total_counts($request)
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

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;





        $ComboStoreStats = new ComboStoreStats();

        $total_products = $ComboStoreStats->total_products($user_id);
        $total_customers = $ComboStoreStats->total_customers($user_id);
        $total_orders = $ComboStoreStats->total_orders($user_id);
        $total_subscriptions = $ComboStoreStats->total_subscriptions($user_id);


        $response['total_products'] = $total_products;
        $response['total_customers'] = $total_customers;
        $response['total_orders'] = $total_orders;
        $response['total_subscriptions'] = $total_subscriptions;





        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function submit_comment($request)
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


        // $user_id = 1;

        // $body = $request->get_body();

        // $body_arr = json_decode($body);
        // $body_arr = json_decode($body, true);




        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $content = $request->get_param('content');
        $postId = absint($request->get_param('postId'));
        $parentId = $request->get_param('parentId');

        /*
            Reviews and blog comments stay open to anyone logged in. A ticket is a
            private conversation, so only staff and the ticket's owner may reply.
            The id is cast above: "123abc" would otherwise pass as "not a ticket"
            here and still be saved against post 123.
        */
        if (get_post_type($postId) === 'ticket') {
            $is_owner = (int) get_post_field('post_author', $postId) === (int) $user_id;

            if (!$user || (!$is_owner && !$this->is_ticket_staff($user))) {
                return new WP_Error('forbidden', 'You are not allowed to reply to this ticket', array('status' => 403));
            }
        }
        $rate = $request->get_param('rate');

        // Only a numeric 1-5 value counts as a rating, anything else is a plain comment.
        $rate = is_numeric($rate) ? floatval($rate) : 0;

        if ($rate < 1 || $rate > 5) {
            $rate = 0;
        }
        $postType = $request->get_param('postType');
        $response = [];
        if ($rate) {
            $has_rated_prams = [
                "userid" => $user_id,
                "postId" => $postId,
            ];


            $has_rated = combo_store_has_rated($has_rated_prams);
            // $has_rated = 0; // for testing

            if ($has_rated) {
                $response['errors']['already_rate'] = __("You already rated.", 'combostore-server');

                $response['message'] = __("Thanks for trying, you already rated.", 'combostore-server');
                die(wp_json_encode($response));
            }
        }




        if (empty($content)) {
            $response['errors']['content_missing'] = __("Comment Missing", 'combostore-server');
        }

        $display_name = isset($user->display_name) ? $user->display_name : "";
        $user_email = isset($user->user_email) ? $user->user_email : "";



        $commentdata = array(
            'comment_post_ID' => $postId, // Post ID to attach the comment to
            'comment_author' => $display_name,
            'comment_author_email' => $user_email,
            'comment_author_url' => '',
            'comment_content' => $content,
            'comment_type' => '', // Empty for regular comments
            'comment_parent' => $parentId, // 0 if it's not a reply
            'user_id' => $user_id, // 0 for guest, or provide a user ID
            'comment_approved' => 1, // 1 to auto-approve
            'avatar' => get_avatar_url($user_email),
            'rate' => $rate,

        );

        // Insert the comment into the database
        $comment_id = wp_insert_comment($commentdata);

        if (!$comment_id) {
            $response['success'] = false;
            $response['errors']['comment_failed'] = __("Could not save your comment.", 'combostore-server');
            die(wp_json_encode($response));
        }

        if ($rate) {
            add_comment_meta($comment_id, 'rate', $rate);
        }

        // Recalculate the rating from the stored ratings, so the average can never drift.
        if ($rate && $postType == 'cmb_product') {

            $rating = $this->calculate_post_rating($postId);

            $ComboStoreProduct = new ComboStoreProduct();

            $ComboStoreProduct->update_product(array(
                'id'         => $postId,
                'total_rate' => $rating['total_rate'],
                'avg_rating' => $rating['avg_rating'],
            ));

            $response['rating'] = $rating;
        }

        $action_prams = array_merge($commentdata, ["comment_id" => $comment_id, 'post_id' => $postId]);

        // do_action("combo_store_comment_submitted", $action_prams);

        $response['comment'] = $commentdata;
        $response['comment_id'] = $comment_id;
        $response['success'] = true;

        $source     = 'action_submit_comment'; // codes. redeem, 
        $credit_type = 'mining';
        $notes = '';
        $amount = 2;


        // if (isset($body_arr['menu_order'])) {
        //     update_post_meta($post_id, 'menu_order', $body_arr['menu_order']);
        // }



        die(wp_json_encode($response));
    }
    /**
     * Rating totals for a post, calculated from the ratings stored on its comments.
     *
     * @since 1.0.0
     * @param int $post_id Post the ratings belong to.
     * @return array Total number of ratings and the average rating.
     */
    public function calculate_post_rating($post_id)
    {

        global $wpdb;

        $post_id = intval($post_id);

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(cm.meta_value) AS total_rate, AVG(cm.meta_value) AS avg_rating
                FROM $wpdb->commentmeta cm
                INNER JOIN $wpdb->comments c ON c.comment_ID = cm.comment_id
                WHERE cm.meta_key = 'rate'
                AND cm.meta_value > 0
                AND c.comment_post_ID = %d
                AND c.comment_approved = '1'",
                $post_id
            ),
            ARRAY_A
        );

        return array(
            'total_rate' => isset($row['total_rate']) ? intval($row['total_rate']) : 0,
            'avg_rating' => isset($row['avg_rating']) ? round(floatval($row['avg_rating']), 2) : 0,
        );
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_order_note($request)
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


        // $user_id = 1;

        // $body = $request->get_body();

        // $body_arr = json_decode($body);
        // $body_arr = json_decode($body, true);




        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $content = $request->get_param('content');
        $postId = $request->get_param('post_id');

        $response = [];


        if (empty($content)) {
            $response['errors']['content_missing'] = __("Comment Missing", 'combostore-server');
        }

        $display_name = isset($user->display_name) ? $user->display_name : "";
        $user_email = isset($user->user_email) ? $user->user_email : "";



        $commentdata = array(
            'comment_post_ID' => $postId, // Post ID to attach the comment to
            'comment_author' => $display_name,
            'comment_author_email' => $user_email,
            'comment_author_url' => '',
            'comment_content' => $content,
            'comment_type' => 'order_note', // Empty for regular comments
            // 'comment_parent' => $parentId, // 0 if it's not a reply
            'user_id' => $user_id, // 0 for guest, or provide a user ID
            'comment_approved' => 1, // 1 to auto-approve
            'avatar' => get_avatar_url($user_email),

        );

        // Insert the comment into the database
        $comment_id = wp_insert_comment($commentdata);

        if (!empty($rate)) {
            add_comment_meta($comment_id, 'rate', $rate);
        }

        $action_prams = array_merge($commentdata, ["comment_id" => $comment_id]);

        // do_action("combo_store_comment_submitted", $action_prams);

        $response['comment'] = $commentdata;


        if ($comment_id) {
            $response['success'] = true;

            $source     = 'action_submit_comment'; // codes. redeem, 
            $credit_type = 'mining';
            $notes = '';
            $amount = 2;
        }
        if (!$comment_id) {
            $response['success'] = false;
            die(wp_json_encode($response));
        }


        // if (isset($body_arr['menu_order'])) {
        //     update_post_meta($post_id, 'menu_order', $body_arr['menu_order']);
        // }



        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_comments($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['post_id']) ? absint($request['post_id']) : 0;
        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 12;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $hierarchical     = isset($request['hierarchical']) ? sanitize_text_field($request['hierarchical']) : false;


        $comment_args = [];
        $meta_query = [];



        $comment_args['status'] = 'approve';
        $comment_args['number'] = $per_page;



        if (!empty($hierarchical)) {
            $comment_args['hierarchical'] = $hierarchical;
        }
        if (!empty($post_id)) {
            $comment_args['post_id'] = $post_id;
        }
        if (!empty($post_id)) {
            $comment_args['post_id'] = $post_id;
        }
        if (!empty($paged)) {
            $comment_args['paged'] = $paged;
        }
        if (!empty($orderby)) {
            $comment_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $comment_args['order'] = $order;
        }

        /*
            Optional filters, used by the dashboard's Comments screen. Callers that
            send none of them (the storefront, Reviews) get what they always got.
        */
        $filter_post_type = isset($request['post_type']) ? sanitize_key($request['post_type']) : '';
        $filter_type      = isset($request['type']) ? sanitize_key($request['type']) : '';
        $filter_keyword   = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : '';

        if (!empty($filter_post_type)) {
            $comment_args['post_type'] = $filter_post_type;
        }
        if (!empty($filter_type)) {
            /* WP_Comment_Query's "comment" also matches the empty type older comments carry. */
            $comment_args['type'] = $filter_type;
        }
        if (!empty($filter_keyword)) {
            $comment_args['search'] = $filter_keyword;
        }


        /*
            Reviews and blog comments stay public, so this is not a login check on
            the whole endpoint: only ticket comments need one. Anyone not logged in
            simply counts as not staff here.
        */
        $viewer = $this->get_ticket_user($request);
        $is_staff = !is_wp_error($viewer) && $this->is_ticket_staff($viewer);

        /*
            Pending, spam and trashed comments are for moderators only; everyone
            else keeps seeing approved comments whatever they ask for.
        */
        $is_moderator = !is_wp_error($viewer) && (bool) array_intersect(['administrator', 'store_manager'], (array) $viewer->roles);
        $filter_status = isset($request['status']) ? sanitize_key($request['status']) : '';

        if ($is_moderator && in_array($filter_status, ['all', 'approve', 'hold', 'spam', 'trash'], true)) {
            $comment_args['status'] = $filter_status;
        }

        if (!empty($post_id) && get_post_type($post_id) === 'ticket') {
            if (is_wp_error($viewer)) {
                return $viewer;
            }

            if (!$is_staff && (int) get_post_field('post_author', $post_id) !== (int) $viewer->ID) {
                return new WP_Error('forbidden', 'You are not allowed to view these comments', array('status' => 403));
            }
        }

        /* With no post_id this lists every comment, so tickets must be left out. */
        if (empty($post_id) && !$is_staff) {
            $comment_args['post__not_in'] = get_posts([
                'post_type'      => 'ticket',
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
        }

        $response = [];

        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query($comment_args);

        $comments_list = [];

        if ($comments) {
            foreach ($comments as $comment) {


                $rate = get_comment_meta($comment->comment_ID, 'rate', true);

                $comment->avatar = get_avatar_url($comment->comment_author_email);

                /* A commenter's email and IP are for staff, not for the public response. */
                if (!$is_staff) {
                    $comment->comment_author_email = '';
                    $comment->comment_author_IP = '';
                    $comment->comment_agent = '';
                }

                $comment->rate = $rate;

                /* What the comment is on, for the dashboard's list. */
                if ($is_moderator) {
                    $comment->post_title = get_the_title($comment->comment_post_ID);
                    $comment->post_type = get_post_type($comment->comment_post_ID);
                }

                $comments_list[] = $comment;
            }
        }



        $response['comments'] = $comments_list;

        /* The same query counted, so a list can page through it. */
        $count_args = $comment_args;
        unset($count_args['number'], $count_args['paged'], $count_args['offset']);
        $count_args['count'] = true;

        $total = (int) (new WP_Comment_Query)->query($count_args);

        $response['total'] = $total;
        $response['max_pages'] = $per_page > 0 ? (int) ceil($total / $per_page) : 1;


        die(wp_json_encode($response));
    }

    /**
     * Approve, unapprove, spam, trash or restore comments.
     *
     * For the dashboard's Comments screen. Order notes and ticket replies are
     * comments too, but they have their own screens and are left alone here.
     *
     * @param WP_REST_Request $request ids[] and action.
     */
    public function moderate_comments($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        if (!array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to moderate comments', array('status' => 403));
        }

        $action = isset($request['action']) ? sanitize_key($request['action']) : '';
        $allowed = ['approve', 'hold', 'spam', 'trash', 'untrash', 'unspam'];

        if (!in_array($action, $allowed, true)) {
            return new WP_Error('invalid_action', 'Unknown action', array('status' => 400));
        }

        $ids = isset($request['ids']) && is_array($request['ids']) ? array_filter(array_map('absint', $request['ids'])) : [];

        $done = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $comment = get_comment($id);

            if (!$comment || $comment->comment_type === 'order_note' || get_post_type($comment->comment_post_ID) === 'ticket') {
                $skipped++;
                continue;
            }

            if ($action === 'untrash') {
                $ok = wp_untrash_comment($id);
            } elseif ($action === 'unspam') {
                $ok = wp_unspam_comment($id);
            } else {
                $ok = wp_set_comment_status($id, $action);
            }

            $ok ? $done++ : $skipped++;
        }

        die(wp_json_encode(['success' => true, 'done' => $done, 'skipped' => $skipped]));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_order_notes($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['post_id']) ? absint($request['post_id']) : 0;
        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 12;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";


        $comment_args = [];
        $meta_query = [];



        $comment_args['status'] = 'all';
        $comment_args['comment_type'] = 'order_note';
        $comment_args['number'] = $per_page;




        if (!empty($post_id)) {
            $comment_args['post_id'] = $post_id;
        }

        if (!empty($paged)) {
            $comment_args['paged'] = $paged;
        }
        if (!empty($orderby)) {
            $comment_args['orderby'] = $orderby;
        }
        if (!empty($order)) {
            $comment_args['order'] = $order;
        }


        $response = [];

        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query($comment_args);

        $comments_list = [];

        if ($comments) {
            foreach ($comments as $comment) {


                $rate = get_comment_meta($comment->comment_ID, 'rate', true);

                $comment->avatar = get_avatar_url($comment->comment_author_email);
                $comment->rate = $rate;
                $comments_list[] = $comment;
            }
        }



        $response['comments'] = $comments_list;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_order_note($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        /*
            Any valid token used to be enough, and any comment ID was accepted,
            so a customer could permanently delete any order note, review, blog
            comment or ticket reply. Only the roles the order's Notes tab offers
            Delete to (OrderNotes.jsx) may use it, and only on order notes.
        */
        if (!array_intersect(['administrator', 'rider'], (array) $userRoles)) {
            return new WP_Error('forbidden', 'You are not allowed to delete order notes', array('status' => 403));
        }


        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $comment_id     = isset($request['comment_id']) ? absint($request['comment_id']) : 0;

        $comment_args = [];
        $meta_query = [];





        if (empty($comment_id)) {
            $response['success'] = false;
            die(wp_json_encode($response));
        }

        $comment = get_comment($comment_id);

        if (!$comment || $comment->comment_type !== 'order_note') {
            return new WP_Error('not_an_order_note', 'That is not an order note', array('status' => 400));
        }



        $response = [];

        $status = wp_delete_comment($comment_id, true);


        if ($status) {
            $response['success'] = true;
        } else {
            $response['success'] = false;
        }


        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_coupon($request)
    {
        /* Anyone could create coupons; this had no login check at all. */
        $staff = $this->require_coupon_staff($request);
        if (is_wp_error($staff)) {
            return $staff;
        }

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
            'post_title'      => 'Sample Coupon',
            'post_type'      => 'coupon',
            'post_status'      => 'draft',

        ];



        $inserted = wp_insert_post($data);

        /* The new coupon's ID, so the dashboard can open it straight away. */
        $response['id'] = is_wp_error($inserted) ? 0 : (int) $inserted;

        $response['success'] = true;


        die(wp_json_encode($response));
    }
    /**
     * The logged-in administrator or store manager, for the coupon endpoints.
     *
     * @param WP_REST_Request $request
     * @return WP_User|WP_Error
     */
    private function require_coupon_staff($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        if (!array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to manage coupons', array('status' => 403));
        }

        return $user;
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_coupons($request)
    {
        /* Every coupon code, drafts included, used to be listed to anyone who asked. */
        $staff = $this->require_coupon_staff($request);
        if (is_wp_error($staff)) {
            return $staff;
        }

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
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";


        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'coupon';
        $query_args['post_status'] = 'any';


        if (!empty($paged)) {
            $query_args['paged'] = $paged;
        }
        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }


        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
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


                $couponType = get_post_meta($post_id, 'couponType', true);
                $couponCode = get_post_meta($post_id, 'couponCode', true);
                $startDate = get_post_meta($post_id, 'startDate', true);
                $endDate = get_post_meta($post_id, 'endDate', true);
                $limit = get_post_meta($post_id, 'limit', true);
                $amount = get_post_meta($post_id, 'amount', true);
                $amountUnit = get_post_meta($post_id, 'amountUnit', true);

                $entries[] = [
                    "id" => $postData->ID,
                    "title" => $postData->post_title,
                    "post_status" => $postData->post_status,
                    "couponCode" => $couponCode,
                    "couponType" => $couponType,
                    "startDate" => $startDate,
                    "endDate" => $endDate,
                    "limit" => $limit,
                    "amount" => $amount,

                ];

            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;


        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_product($request)
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
        $post_slug     = isset($request['slug']) ? sanitize_text_field($request['slug']) : "";


        if (!empty($post_slug)) {
            $post = get_page_by_path($post_slug, OBJECT, "cmb_product");
            $post_id = $post ? $post->ID : null;
        }



        $productData = [];

        $postData = get_post($post_id);


        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
        $featured = get_post_meta($post_id, 'featured', true);


        $categories = get_the_terms($post_id, 'product_cat');
        $categories = $categories ? $categories : [];

        $tags = get_the_terms($post_id, 'product_tag');
        $tags = $tags ? $tags : [];

        $badges = get_the_terms($post_id, 'product_badge');
        $badges = $badges ? $badges : [];

        $brands = get_the_terms($post_id, 'product_brand');
        $brands = $brands ? $brands : [];

        $visibility = get_the_terms($post_id, 'product_visibility');
        $visibility = $visibility ? $visibility : [];




        $categoriesData = [];
        foreach ($categories as $index => $category) {

            $categoriesData[$index] = [
                "term_id" => $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
            ];
        }
        $visibilityData = [];
        foreach ($visibility as $index => $category) {

            $visibilityData[$index] = [
                "term_id" => $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
            ];
        }


        $tagsData = [];
        foreach ($tags as $index => $tag) {

            $tagsData[$index] = [
                "term_id" => $tag->term_id,
                "name" => $tag->name,
                "slug" => $tag->slug,
            ];
        }

        $badgesData = [];
        foreach ($badges as $index => $tag) {

            $badgesData[$index] = [
                "term_id" => $tag->term_id,
                "name" => $tag->name,
                "slug" => $tag->slug,
            ];
        }

        $brandsData = [];
        foreach ($brands as $index => $brand) {

            $brandsData[$index] = [
                "term_id" => $brand->term_id,
                "name" => $brand->name,
                "slug" => $brand->slug,
            ];
        }


        $post_thumbnail = [
            "id" => "",
            "title" => "",
            "src" => $post_thumbnail_url,
        ];


        $Parsedown = new Parsedown();
        $post_content_html = $Parsedown->text(isset($postData->post_content) ? $postData->post_content : null);





        $productData = [
            "id" => isset($postData->ID) ? $postData->ID : '',
            "product_id" => isset($post_id) ? $post_id : '',
            "post_id" => isset($post_id) ? $post_id : '',
            "post_type" => isset($postData->post_type) ? $postData->post_type : "",

            "title" => isset($postData->post_title) ? $postData->post_title : '',
            "post_content" => isset($postData->post_content) ? $postData->post_content : '',
            "post_content_html" => isset($post_content_html) ? $post_content_html : '',
            "post_excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : "",
            "slug" => isset($postData->post_name) ? $postData->post_name : '',
            "postStatus" => isset($postData->post_status) ? $postData->post_status : '',
            "featured" => $featured,
            "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
            "tags" => !empty($tagsData) ? $tagsData : [],
            "badges" => !empty($badgesData) ? $badgesData : [],
            "categories" => !empty($categoriesData) ? $categoriesData : [],
            "visibility" => !empty($visibilityData) ? $visibilityData : [],
            "brands" => !empty($brandsData) ? $brandsData : [],

        ];



        if ($postData->ID) {


            $ComboStoreProduct = new ComboStoreProduct();

            $product_data = $ComboStoreProduct->get_product(['product_id' => $postData->ID]);

            if (!$product_data) {

                $response['product'] = $productData;
                die(wp_json_encode($response));
            }

            $variations = $ComboStoreProduct->get_variations(['product_id' => $postData->ID]);


            $sale_price = !empty($product_data['sale_price']) ? ($product_data['sale_price']) : 0;
            $regular_price = !empty($product_data['regular_price']) ? ($product_data['regular_price']) : 0;
            $price =  !empty($sale_price) ? $sale_price : $regular_price;



            $json_fields = [
                'gallery',
                'bulk_prices',
                'addons',
                'downloads',
                'upsells',
                'crosssells',
                'faq',
                'attributes',
                'related_products',
                'product_template',
            ];

            foreach ($json_fields as $field) {

                if (!array_key_exists($field, $product_data)) {
                    continue;
                }

                if (empty($product_data[$field])) {
                    $product_data[$field] = [];
                    continue;
                }

                $decoded = json_decode($product_data[$field], true);

                // If valid JSON, use decoded array/value.
                // Otherwise keep the original value.
                if (json_last_error() === JSON_ERROR_NONE) {
                    $product_data[$field] = $decoded;
                }
            }



            foreach ($variations as &$variation) {

                // Decode thumbnail JSON
                if (!empty($variation->thumbnail)) {
                    $thumbnail = json_decode($variation->thumbnail, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $variation->thumbnail = $thumbnail;
                    }
                } else {
                    $variation->thumbnail = [];
                }


                $Parsedown = new Parsedown();
                $description_content = $Parsedown->text($variation->description);

                $variation->description = $description_content;


                // Decode attributes if they are still JSON strings
                if (!empty($variation->attributes) && is_string($variation->attributes)) {
                    $attributes = json_decode($variation->attributes, true);

                    if (json_last_error() === JSON_ERROR_NONE) {
                        $variation->attributes = $attributes;
                    }
                } else {
                    $variation->attributes = $variation->attributes ?? [];
                }
            }

            unset($variation);


            $productData['variations'] = $variations;
            $productData['price'] = $price;
            unset($product_data['id']);

            $response['product'] = array_merge($productData, $product_data);
        } else {
            $response['product'] = $productData;
        }



        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function post_vote($request)
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


        $vote_type     = isset($request['vote_type']) ? sanitize_text_field($request['vote_type']) : '';
        $object_id     = isset($request['object_id']) ? sanitize_text_field($request['object_id']) : '';



        $response = [];


        $prams = [];

        if (!empty($user_id)) {
            $prams['userid'] = $user_id;
        }
        if (!empty($object_id)) {
            $prams['object_id'] = $object_id;
        }
        if (!empty($vote_type)) {
            $prams['vote_type'] = $vote_type;
        }


        $ComboStoreVote = new ComboStoreVote();
        $has_voted = $ComboStoreVote->has_voted($prams);
        //$get_vote = $ComboStoreVote->get_vote($prams);


        if ($has_voted) {

            $ComboStoreVote->update_vote($prams);

            $response['removed'] = true;
        } else {
            $response = $ComboStoreVote->ceate_vote($prams);

            $source     = 'action_vote'; // codes. redeem, 
            $credit_type = 'mining';
            $notes = '';
            $amount = 1;
        }
        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_wishlist($request)
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
        $price_type = get_post_meta($post_id, 'price_type', true);
        $sale_price = get_post_meta($post_id, 'sale_price', true);
        $regular_price = get_post_meta($post_id, 'regular_price', true);
        $menu_order = get_post_meta($post_id, 'menu_order', true);

        $sku = get_post_meta($post_id, 'sku', true);
        $stock_status = get_post_meta($post_id, 'stock_status', true);
        $stock_count = get_post_meta($post_id, 'stock_count', true);
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
        $related_products = get_post_meta($post_id, 'related_products', true);
        $variations = get_post_meta($post_id, 'variations', true);

        $categories = get_the_terms($post_id, 'product_cat');
        $categories = $categories ? $categories : [];

        $tags = get_the_terms($post_id, 'product_tag');
        $tags = $tags ? $tags : [];

        $brands = [];
        $brands = [];


        $categoriesData = [];
        foreach ($categories as $index => $category) {

            $categoriesData[$index] = [
                "term_id" => $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
            ];
        }


        $tagsData = [];
        foreach ($tags as $index => $tag) {

            $tagsData[$index] = [
                "term_id" => $tag->term_id,
                "name" => $tag->name,
                "slug" => $tag->slug,
            ];
        }


        $post_thumbnail = [
            "id" => "",
            "title" => "",
            "src" => $post_thumbnail_url,
        ];

        $productData = [
            "id" => isset($postData->ID) ? $postData->ID : null,
            "title" => isset($postData->post_title) ? $postData->post_title : null,
            "post_content" => isset($postData->post_content) ? $postData->post_content : null,
            "post_excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
            "slug" => isset($postData->post_name) ? $postData->post_name : null,
            "postStatus" => isset($postData->post_status) ? $postData->post_status : null,
            "featured" => $featured,
            "sku" => $sku,
            "price_type" => $price_type,
            "sale_price" => $sale_price,
            "regular_price" => $regular_price,
            "stock_status" => $stock_status,
            "stock_count" => $stock_count,
            "menu_order" => $menu_order,

            "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
            "tags" => !empty($tagsData) ? $tagsData : [],
            "categories" => !empty($categoriesData) ? $categoriesData : [],
            "brands" => !empty($brands) ? $brands : [],
            "addons" => !empty($addons) ? $addons : ["manageStock", "gallery", "dimensions", "categories", "tags"],
            "faq" => !empty($faq) ? $faq : [],
            "related_products" => !empty($related_products) ? $related_products : [],
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
     * @param WP_REST_Request $request Post data.
     */
    public function add_product($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        /* Any valid token used to be enough - a customer's too. Only the roles
           the Products menu is open to may. */
        if (!array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to add products', array('status' => 403));
        }


        $body = $request->get_body();
        $body_arr = json_decode($body, true);





        $data = [
            'post_title'      => 'New product',
            'post_type'      => 'cmb_product',
            'post_status'      => 'draft',

        ];



        $post_id = wp_insert_post($data);

        $ComboStoreProduct = new ComboStoreProduct();

        if (!$post_id || is_wp_error($post_id)) {
            die(wp_json_encode(array('success' => false, 'message' => 'The product could not be created.')));
        }

        /* insert_product reads product_id; this passed post_id, so a new product
           never got its row in cstore_products (prices, stock, SKU). */
        $body_arr = is_array($body_arr) ? $body_arr : array();
        $body_arr['product_id'] = $post_id;
        /* The live table will not take NULL ratings, which is what insert_product sends by default. */
        $body_arr += array('total_rate' => 0, 'avg_rating' => 0);

        $ComboStoreProduct->insert_product($body_arr);
        $response['id'] = (int) $post_id;

        $response['success'] = true;


        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_product($request)
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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_title = $request->get_param('title');
        $post_slug = $request->get_param('slug');
        $post_content = $request->get_param('post_content');
        $post_excerpt = $request->get_param('post_excerpt');
        $post_status = $request->get_param('postStatus');
        $comment_status = $request->get_param('comment_status');
        $post_parent = $request->get_param('post_parent');
        $menu_order = $request->get_param('menu_order');
        $categories = $request->get_param('categories');
        $visibility = $request->get_param('visibility');

        $tags = $request->get_param('tags');
        $brands = $request->get_param('brands');
        $badges = $request->get_param('badges');



        $tags = $tags ? $tags : [];
        $brands = $brands ? $brands : [];
        $badges = $badges ? $badges : [];
        $categories = $categories ? $categories : [];


        $post_thumbnail = $request->get_param('post_thumbnail');
        $post_thumbnail_id = isset($post_thumbnail['id']) ? $post_thumbnail['id'] : '';

        $featured = $request->get_param('featured');


        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $category['term_id'];
        }

        $visibilityIds = [];
        foreach ($visibility as $visibility) {
            $visibilityIds[] = $visibility['term_id'];
        }


        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[] = $tag['name'];
        }

        $brandIds = [];
        foreach ($brands as $brand) {
            $brandIds[] = isset($brand['term_id']) ? $brand['term_id'] : $brand['name'];
        }
        $badgeIds = [];
        foreach ($badges as $badge) {
            $badgeIds[] = isset($badge['term_id']) ? $badge['term_id'] : $badge['name'];
        }



        $response = [];
        $postData = [
            "ID" => $post_id,
            "post_title" => $post_title,
            "post_name" => $post_slug,
            "post_content" => $post_content,
            "post_excerpt" => $post_excerpt,
            "post_status" => $post_status,
            "comment_status" => $comment_status,
            "post_parent" => $post_parent,
            "menu_order" => $menu_order,
        ];
        wp_update_post($postData);

        wp_set_post_terms($post_id, $categoryIds, 'product_cat');
        wp_set_post_terms($post_id, $tagIds, 'product_tag');
        wp_set_post_terms($post_id, $brandIds, 'product_brand');
        wp_set_post_terms($post_id, $badgeIds, 'product_badge');

        wp_set_post_terms($post_id, $visibilityIds, 'product_visibility');



        set_post_thumbnail($post_id, $post_thumbnail_id);


        $body_arr['id'] = $post_id;

        $ComboStoreProduct = new ComboStoreProduct();


        $ComboStoreProduct->update_product($body_arr);




        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_post($request)
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
        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_title = $request->get_param('title');
        $post_slug = $request->get_param('slug');
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


        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $category['term_id'];
        }



        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[] = $tag['name'];
        }



        $response = [];
        $postData = [
            "ID" => $post_id,
            "post_title" => $post_title,
            "post_name" => $post_slug,
            "post_content" => $post_content,
            "post_excerpt" => $post_excerpt,
            "post_status" => $post_status,
            "comment_status" => $comment_status,
            "post_parent" => $post_parent,
            "menu_order" => $menu_order,
        ];
        wp_update_post($postData);

        wp_set_post_terms($post_id, $categoryIds, 'category');
        wp_set_post_terms($post_id, $tagIds, 'post_tag');




        set_post_thumbnail($post_id, $post_thumbnail_id);




        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_order($request)
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
        $body_arr = json_decode($body, true);


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreOrders = new ComboStoreOrders();
        // $has_purchased = $ComboStoreOrders->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreOrders->create_order($body_arr);
        $order_id = isset($order_response['order_id']) ? $order_response['order_id'] : 0;
        $order_hash = isset($order_response['order_hash']) ? $order_response['order_hash'] : "";


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['order_id'] = $order_id;
            $response['order_hash'] = $order_hash;
            $response['message'] = "Order Created Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * The dashboard's JWT, for the PayPal routes that are admin work.
     *
     * The same check the other admin routes inline, factored out because the
     * gateway adds four of them and a credential screen is the wrong place to
     * have a copy of an auth check drift.
     *
     * @param WP_REST_Request $request
     * @return int|WP_Error The user id, or the error to return.
     */
    private function paypal_admin_guard($request)
    {
        $token = $request->get_header('Authorization');

        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        return intval($user_id);
    }


    /**
     * PayPal: what the storefront is allowed to know about the gateway - is it
     * on, the client id for the SDK, and the currency it charges in.
     *
     * Deliberately its own route rather than part of get_settings_public: that
     * one returns combo_store_settings whole, and a client secret must never be
     * in anything handed to a browser.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_config($request)
    {
        $ComboStorePayPal = new ComboStorePayPal();

        die(wp_json_encode(array(
            'status' => 'success',
            'config' => $ComboStorePayPal->public_config(),
        )));
    }


    /**
     * PayPal: start a payment for an order that already exists.
     *
     * Takes only the order hash. The amount is read out of the order row, so
     * what the buyer is asked to pay cannot be set by the browser.
     *
     * POST { order_hash }
     *   -> { status, paypal_order_id, approve_url, amount, currency }
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_create_order($request)
    {
        $body_arr = json_decode($request->get_body(), true);

        $order_hash = isset($body_arr['order_hash']) ? sanitize_text_field($body_arr['order_hash']) : '';

        $ComboStorePayPal = new ComboStorePayPal();

        try {
            $result = $ComboStorePayPal->create_order($order_hash);
        } catch (Exception $e) {
            die(wp_json_encode(array(
                'status'  => 'failed',
                'message' => $e->getMessage(),
            )));
        }

        $result['status']  = 'success';
        $result['message'] = 'PayPal payment started.';

        die(wp_json_encode($result));
    }


    /**
     * PayPal: capture an approved payment and mark the order paid.
     *
     * Which order the money belongs to comes from PayPal's own response, not
     * from the request; `order_hash` is accepted only as a cross-check. Safe to
     * call twice - a payment already recorded answers success.
     *
     * POST { paypal_order_id, order_hash? }
     *   -> { status, order_id, order_hash, transaction_id, payment_status }
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_capture_order($request)
    {
        $body_arr = json_decode($request->get_body(), true);

        $paypal_order_id = isset($body_arr['paypal_order_id']) ? sanitize_text_field($body_arr['paypal_order_id']) : '';
        $order_hash      = isset($body_arr['order_hash']) ? sanitize_text_field($body_arr['order_hash']) : '';

        $ComboStorePayPal = new ComboStorePayPal();

        try {
            $result = $ComboStorePayPal->capture_order($paypal_order_id, $order_hash);
        } catch (Exception $e) {
            die(wp_json_encode(array(
                'status'  => 'failed',
                'message' => $e->getMessage(),
            )));
        }

        $result['status']  = 'success';
        $result['message'] = !empty($result['needs_review'])
            ? 'Payment received, but the amount does not match the order - it is on hold for review.'
            : 'Payment received.';

        die(wp_json_encode($result));
    }


    /**
     * PayPal: the webhook endpoint.
     *
     * Open by necessity - PayPal sends no token - and authenticated instead by
     * asking PayPal to verify the signature it sent. This one returns real
     * status codes rather than a 200 with a body, because PayPal reads the code
     * and retries anything that is not 2xx: a 500 here means the event comes
     * back, which is what should happen if the database was briefly unhappy.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_webhook($request)
    {
        $raw     = $request->get_body();
        $headers = $request->get_headers();

        $ComboStorePayPal = new ComboStorePayPal();

        if (!$ComboStorePayPal->verify_webhook($headers, $raw)) {
            return new \WP_REST_Response(array(
                'status'  => 'failed',
                'message' => 'Signature could not be verified.',
            ), 401);
        }

        $event = json_decode($raw, true);

        if (!is_array($event)) {
            return new \WP_REST_Response(array(
                'status'  => 'failed',
                'message' => 'Event body is not JSON.',
            ), 400);
        }

        try {
            $result = $ComboStorePayPal->handle_webhook($event);
        } catch (Exception $e) {
            error_log('[combostore-paypal] webhook handling failed: ' . $e->getMessage());

            /* Let PayPal retry rather than swallowing a payment event. */
            return new \WP_REST_Response(array(
                'status'  => 'failed',
                'message' => $e->getMessage(),
            ), 500);
        }

        return new \WP_REST_Response(array_merge(array('status' => 'success'), $result), 200);
    }


    /**
     * PayPal: the gateway settings, with the client secret masked.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_get_settings($request)
    {
        $guard = $this->paypal_admin_guard($request);

        if (is_wp_error($guard)) {
            return $guard;
        }

        $ComboStorePayPal = new ComboStorePayPal();

        die(wp_json_encode(array(
            'status'   => 'success',
            'settings' => $ComboStorePayPal->masked_settings(),
        )));
    }


    /**
     * PayPal: save the gateway settings.
     *
     * Send `client_secret` only when it changes - left out or empty, the stored
     * one is kept, which is what lets the settings screen save without ever
     * holding the secret.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_update_settings($request)
    {
        $guard = $this->paypal_admin_guard($request);

        if (is_wp_error($guard)) {
            return $guard;
        }

        $body_arr = json_decode($request->get_body(), true);

        if (!is_array($body_arr)) {
            die(wp_json_encode(array('status' => 'failed', 'message' => 'Request body is not valid JSON.')));
        }

        $ComboStorePayPal = new ComboStorePayPal();

        die(wp_json_encode(array(
            'status'   => 'success',
            'message'  => 'PayPal settings updated.',
            'settings' => $ComboStorePayPal->update_settings($body_arr),
        )));
    }


    /**
     * PayPal: check the stored credentials actually authenticate.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_test_connection($request)
    {
        $guard = $this->paypal_admin_guard($request);

        if (is_wp_error($guard)) {
            return $guard;
        }

        $ComboStorePayPal = new ComboStorePayPal();

        try {
            $result = $ComboStorePayPal->test_connection();
        } catch (Exception $e) {
            die(wp_json_encode(array('status' => 'failed', 'message' => $e->getMessage())));
        }

        die(wp_json_encode(array(
            'status'  => 'success',
            'message' => 'PayPal accepted these credentials (' . $result['mode'] . ').',
        )));
    }


    /**
     * PayPal: refund a captured payment, fully or in part.
     *
     * POST { order_id, amount?, reason? } - amount is in the currency PayPal
     * charged, and the whole payment is refunded when it is left out.
     *
     * @since 1.0.7
     * @param WP_REST_Request $request Post data.
     */
    public function paypal_refund($request)
    {
        $guard = $this->paypal_admin_guard($request);

        if (is_wp_error($guard)) {
            return $guard;
        }

        $body_arr = json_decode($request->get_body(), true);

        $order_id = isset($body_arr['order_id']) ? intval($body_arr['order_id']) : 0;
        $amount   = isset($body_arr['amount']) ? floatval($body_arr['amount']) : 0;
        $reason   = isset($body_arr['reason']) ? sanitize_text_field($body_arr['reason']) : '';

        if (empty($order_id)) {
            die(wp_json_encode(array('status' => 'failed', 'message' => 'An order id is required.')));
        }

        $ComboStorePayPal = new ComboStorePayPal();

        try {
            $result = $ComboStorePayPal->refund($order_id, $amount, $reason);
        } catch (Exception $e) {
            die(wp_json_encode(array('status' => 'failed', 'message' => $e->getMessage())));
        }

        $result['status']  = 'success';
        $result['message'] = 'Refund sent.';

        die(wp_json_encode($result));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_shipping_zone($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreShipping = new ComboStoreShipping();
        // $has_purchased = $ComboStoreShipping->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreShipping->create_shipping_zone($body_arr);
        $order_id = isset($order_response['order_id']) ? $order_response['order_id'] : 0;
        $order_hash = isset($order_response['order_hash']) ? $order_response['order_hash'] : "";


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['order_id'] = $order_id;
            $response['order_hash'] = $order_hash;
            $response['message'] = "Shipping zone created successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_shipping_method($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreShipping = new ComboStoreShipping();
        // $has_purchased = $ComboStoreShipping->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreShipping->create_shipping_method($body_arr);
        $order_id = isset($order_response['order_id']) ? $order_response['order_id'] : 0;
        $order_hash = isset($order_response['order_hash']) ? $order_response['order_hash'] : "";


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['order_id'] = $order_id;
            $response['order_hash'] = $order_hash;
            $response['message'] = "Order Created Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }











    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_expense($request)
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

        /* Any valid token used to be enough, a customer's too. Only the roles the
           Expenses menu is open to may create expenses. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to create expenses', array('status' => 403));
        }

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['created_by'] = $user_id;

        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreExpenses = new ComboStoreExpenses();
        // $has_purchased = $ComboStoreExpenses->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreExpenses->create_expense($body_arr);
        $order_id = isset($order_response['id']) ? $order_response['id'] : 0;


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['id'] = $order_id;
            $response['message'] = "Expense added Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_template_part($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['created_by'] = $user_id;

        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        // $has_purchased = $ComboStoreTemplateParts->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreTemplateParts->create_template_part($body_arr);
        $order_id = isset($order_response['id']) ? $order_response['id'] : 0;


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['id'] = $order_id;
            $response['message'] = "Template Part added Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_template($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['created_by'] = $user_id;

        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        // $has_purchased = $ComboStoreTemplateParts->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreTemplateParts->create_template($body_arr);
        $order_id = isset($order_response['id']) ? $order_response['id'] : 0;


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['id'] = $order_id;
            $response['message'] = "Template Installed Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }











    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_purchase($request)
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

        /* Any valid token used to be enough, a customer's too. Only the roles the
           Purchases menu is open to may create purchases. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to create purchases', array('status' => 403));
        }

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        /* The creator, as create_expense does. It was only stored when the request sent
           it - the page never does - so purchases had no owner and a store manager's
           list (filtered by user_id) stayed empty. */
        $body_arr['user_id'] = $user_id;


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStorePurchases = new ComboStorePurchases();
        // $has_purchased = $ComboStorePurchases->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStorePurchases->create_purchase($body_arr);
        $order_id = isset($order_response['id']) ? $order_response['id'] : 0;


        if (isset($order_response['success'])) {
            $response['status'] = "success";
            $response['id'] = $order_id;
            $response['message'] = "Purchase added Successful.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }











    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_subscriptions($request)
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
        $body_arr = json_decode($body, true);


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreSubscriptions = new ComboStoreSubscriptions();
        // $has_purchased = $ComboStoreOrders->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreSubscriptions->create_subscription($body_arr);
        $subscription_id = isset($order_response['subscription_id']) ? $order_response['subscription_id'] : 0;


        if ($order_response['success']) {
            $response['status'] = "success";
            $response['subscription_id'] = $subscription_id;
            $response['message'] = "Subscription created.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_subscription($request)
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



        $body = $request->get_body();

        $body_arr = json_decode($body, true);

        /* Changing a subscription is a staff action. This used to accept any
           signed-in user (and then overwrote the id with `$user_id = 1`). */
        $editor = get_user_by('id', $user_id);
        $editorRoles = $editor ? (array) $editor->roles : [];
        if (!in_array('administrator', $editorRoles, true) && !in_array('store_manager', $editorRoles, true)) {
            return new WP_Error('forbidden', 'Only store staff can update subscriptions', array('status' => 403));
        }

        $response = [];

        $ComboStoreSubscriptions = new ComboStoreSubscriptions();


        $_response = $ComboStoreSubscriptions->update_subscription(is_array($body_arr) ? $body_arr : []);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Subscription updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_settings($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);




        $response = [];

        $settings = get_option('combo_store_settings');


        if (empty($settings)) {
            $settings = [];
        }

        die(wp_json_encode($settings));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_settings_public($request)
    {


        $response = [];

        $settings = get_option('combo_store_settings');


        if (empty($settings)) {
            $settings = [];
        }

        die(wp_json_encode($settings));
    }












    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function couriers_webhook($request)
    {


        $headers = $request->get_headers();
        $body    = $request->get_body();
        $body_arr = json_decode($body, true);



        $user_agent = isset($headers['user_agent']) ? $headers['user_agent'] : [];
        $authorization = $request->get_header('Authorization');

        $courier = '';


        if (isset($user_agent[0]) && $user_agent[0] == 'Steadfast-Webhook/1.0') {
            $courier = 'steadfast';
            $body_arr['authorization'] = $authorization;
        }

        $response = apply_filters('couriers_webhook_' . $courier, $body_arr);

        // Debug echo so the sender can confirm what actually arrived.
        // Remove this before going live: it reflects auth headers back.
        // $response = [
        //     'received'      => true,
        //     'header_count'  => count($headers),
        //     'courier'       => $request->get_header('X-Combo-Courier'),
        //     'content_type'  => $request->get_header('Content-Type'),
        //     'authorization' => $request->get_header('Authorization'),
        //     'headers'       => $headers,
        //     'body'          => json_decode($body, true),
        // ];

        // return new \WP_REST_Response($response_, 200);
        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_settings($request)
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



        $body = $request->get_body();
        $body_arr = json_decode($body, true);




        $response = [];

        $status = update_option('combo_store_settings', $body_arr);



        if ($status) {
            $response['status'] = "success";
            $response['message'] = "Settings updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function active_template($request)
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



        $body = $request->get_body();
        $body_arr = json_decode($body, true);



        $response = [];

        $template_id       = isset($body_arr['id']) ? sanitize_text_field($body_arr['id']) : '';

        $combo_store_settings = get_option('combo_store_settings', []);
        $combo_store_settings['site_template']['template_id'] = $template_id;



        $status = update_option('combo_store_settings', $combo_store_settings);



        if ($status) {
            $response['status'] = "success";
            $response['message'] = "Settings updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }




        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function active_header($request)
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



        $body = $request->get_body();
        $body_arr = json_decode($body, true);




        $response = [];

        $template_id       = isset($body_arr['id']) ? sanitize_text_field($body_arr['id']) : '';

        $combo_store_settings = get_option('combo_store_settings', []);
        $combo_store_settings['site_template']['template_id'] = $template_id;


        $status = update_option('combo_store_settings', $combo_store_settings);



        if ($status) {
            $response['status'] = "success";
            $response['message'] = "Settings updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function active_footer($request)
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



        $body = $request->get_body();
        $body_arr = json_decode($body, true);




        $response = [];

        $template_id       = isset($params['id']) ? sanitize_text_field($params['id']) : '';

        $combo_store_settings = get_option('combo_store_settings', []);

        $combo_store_settings['site_template']['footer_id'] = $template_id;

        $status = update_option('combo_store_settings', $combo_store_settings);



        if ($status) {
            $response['status'] = "success";
            $response['message'] = "Settings updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }

        die(wp_json_encode($response));
    }











    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function steadfast_create_order($request)
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

        $settings = get_option('combo_store_settings');


        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $invoice     = isset($request['invoice']) ? sanitize_text_field($request['invoice']) : '';
        $recipient_name     = isset($request['recipient_name']) ? sanitize_text_field($request['recipient_name']) : '';
        $recipient_phone     = isset($request['recipient_phone']) ? sanitize_text_field($request['recipient_phone']) : '';
        $alternative_phone     = isset($request['alternative_phone']) ? sanitize_text_field($request['alternative_phone']) : '';
        $recipient_email     = isset($request['recipient_email']) ? sanitize_text_field($request['recipient_email']) : '';
        $recipient_address     = isset($request['recipient_address']) ? sanitize_text_field($request['recipient_address']) : '';
        $cod_amount     = isset($request['cod_amount']) ? sanitize_text_field($request['cod_amount']) : '';
        $note     = isset($request['note']) ? sanitize_text_field($request['note']) : '';
        $item_description     = isset($request['item_description']) ? sanitize_text_field($request['item_description']) : '';
        $total_lot     = isset($request['total_lot']) ? sanitize_text_field($request['total_lot']) : '';
        $delivery_type     = isset($request['delivery_type']) ? sanitize_text_field($request['delivery_type']) : '';





        $response = [];

        $steadfast = new SteadfastCourier();
        $status = $steadfast->createOrder([
            'invoice' => $invoice,
            'recipient_name' => $recipient_name,
            'recipient_phone' => $recipient_phone,
            'alternative_phone' => $alternative_phone,
            'recipient_address' => $recipient_address,
            'cod_amount' => $cod_amount,
            'note' => $note,
            'item_description' => $item_description,
            'total_lot' => $total_lot,
            'delivery_type' => $delivery_type,
        ]);




        die(wp_json_encode($status));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_order($request)
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

        $body = $request->get_body();


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreOrders = new ComboStoreOrders();


        $_response = $ComboStoreOrders->update_order($body_arr);



        if ($_response['success']) {
            $response['status'] = "success";
            $response['message'] = "Order updated.";

            /* Stock notes from completing (shortfalls) or refunding (what went back). */
            if (!empty($_response['stock_notes'])) {
                $response['message'] .= ' Stock: ' . implode(' ', $_response['stock_notes']);
            }
        } else {
            $response['status'] = "failed";
            $response['message'] = $_response['message'] ?? "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_shipping_method($request)
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

        $body = $request->get_body();


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreShipping = new ComboStoreShipping();


        $_response = $ComboStoreShipping->update_shipping_method($body_arr);



        if ($_response['success']) {
            $response['status'] = "success";
            $response['message'] = "Order updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = $_response['message'] ?? "There is an error.";
        }



        die(wp_json_encode($response));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_shipping_zone($request)
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

        $body = $request->get_body();


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreShipping = new ComboStoreShipping();


        $_response = $ComboStoreShipping->update_shipping_zone($body_arr);



        if ($_response['success']) {
            $response['status'] = "success";
            $response['message'] = "Shipping zone updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = $_response['message'] ?? "There is an error.";
        }



        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_order_status($request)
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


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreOrders = new ComboStoreOrders();


        $_response = $ComboStoreOrders->update_order_status($body_arr);



        if ($_response['success']) {
            $response['status'] = "success";
            $response['message'] = "Order status updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = $_response['message'] ?? "There is an error.";
        }



        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function bulk_update_orders($request)
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

        /* Any valid token used to be enough to change any order, a customer's too. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !$this->is_ticket_staff($requester)) {
            return new WP_Error('forbidden', 'You are not allowed to update orders', array('status' => 403));
        }

        $body = $request->get_body();


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreOrders = new ComboStoreOrders();


        $_response = $ComboStoreOrders->bulk_update_orders($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Order updated.";
            $response['updated'] = isset($_response['updated']) ? (int) $_response['updated'] : null;
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_purchase($request)
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

        /* Any valid token used to be enough to change any of these, a customer's
           too - and completing a purchase now moves stock. Only the roles the
           Purchases menu is open to may. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to change purchases', array('status' => 403));
        }

        $body = $request->get_body();


        $body_arr = json_decode($body, true);




        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStorePurchases = new ComboStorePurchases();


        $_response = $ComboStorePurchases->update_purchase($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Purchase updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_expense($request)
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

        /* Any valid token used to be enough to change any of these, a customer's
           too - and completing a purchase now moves stock. Only the roles the
           Expenses menu is open to may. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to change expenses', array('status' => 403));
        }

        $body = $request->get_body();


        $body_arr = json_decode($body, true);

        $body_arr['created_by'] = $user_id;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreExpenses = new ComboStoreExpenses();


        $_response = $ComboStoreExpenses->update_expense($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Expense updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_template_part($request)
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

        $body = $request->get_body();


        $body_arr = json_decode($body, true);

        $body_arr['created_by'] = $user_id;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreTemplateParts = new ComboStoreTemplateParts();


        $_response = $ComboStoreTemplateParts->update_template_part($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Template part updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_order_items($request)
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


        $body_arr = json_decode($body, true);


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreOrders = new ComboStoreOrders();


        $_response = $ComboStoreOrders->update_order_items($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Order items updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_delivery($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));



        $ComboStoreDeliveries = new ComboStoreDeliveries();


        $_response = $ComboStoreDeliveries->update_delivery($body_arr);


        if ($_response) {
            $response['status'] = "success";
            $response['message'] = "Delivery updated.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_subscriptions($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $id = isset($body_arr['id']) ? $body_arr['id'] : '';


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreSubscriptions = new ComboStoreSubscriptions();
        // $has_purchased = $ComboStoreOrders->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $_response = $ComboStoreSubscriptions->delete_subscription($id);


        if ($_response['status']) {
            $response['status'] = "success";
            $response['message'] = "Subscription deleted.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_activities($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        // $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreActivitiy = new ComboStoreActivitiy();



        if (!empty($ids)) {
            foreach ($ids as $id) {
                $_response = $ComboStoreActivitiy->delete(["id" => $id]);
            }
        }



        if ($_response['status']) {
            $_response['status'] = "success";
            $_response['message'] = "Subscription deleted.";
        } else {
            $_response['status'] = "failed";
            $_response['message'] = "There is an error.";
        }


        die(wp_json_encode($_response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_subscriptions_to_call($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $ids = isset($body_arr['ids']) ? $body_arr['ids'] : [];


        $response = [];



        $ComboStoreSubscriptionsToCall = new ComboStoreSubscriptionsToCall();
        // $has_purchased = $ComboStoreOrders->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        foreach ($ids as $id) {
            $_response = $ComboStoreSubscriptionsToCall->delete_subscription($id, $user_id);
        }




        if ($_response['status']) {
            $response['status'] = "success";
            $response['message'] = "Subscription deleted.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_subscription($request)
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $id = isset($body_arr['id']) ? $body_arr['id'] : '';
        $by = isset($body_arr['by']) ? $body_arr['by'] : 'id';

        $response = [];



        $ComboStoreSubscriptions = new ComboStoreSubscriptions();


        $response = $ComboStoreSubscriptions->get_subscription($id, $by === 'order_id' ? 'order_id' : 'id');

        /* Staff see any subscription; anyone else only their own. Without
           this any signed-in customer could read any customer's record. */
        $viewer = get_user_by('id', $user_id);
        $viewerRoles = $viewer ? (array) $viewer->roles : [];
        $isStaff = in_array('administrator', $viewerRoles, true) || in_array('store_manager', $viewerRoles, true);
        $ownerId = isset($response['subscription']['userid']) ? absint($response['subscription']['userid']) : 0;

        if (!$isStaff && !empty($response['subscription']) && $ownerId !== absint($user_id)) {
            return new WP_Error('forbidden', 'You cannot view this subscription', array('status' => 403));
        }

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function request_for_stock($request)
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
        $body_arr = json_decode($body, true);
        $id = isset($body_arr['id']) ? $body_arr['id'] : '';

        $response = [];



        $ComboStoreSubscriptions = new ComboStoreSubscriptions();


        $response = $ComboStoreSubscriptions->get_subscription($id);


        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function start_delivery($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreDeliveries = new ComboStoreDeliveries();
        // $has_purchased = $ComboStoreDeliveries->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreDeliveries->create_delivery($body_arr);
        $delivery_id = isset($order_response['delivery_id']) ? $order_response['delivery_id'] : 0;


        if ($order_response['success']) {
            $response['status'] = "success";
            $response['delivery_id'] = $delivery_id;
            $response['message'] = "Delivery created.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function start_subscription($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreSubscriptions = new ComboStoreSubscriptions();
        // $has_purchased = $ComboStoreDeliveries->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $order_response = $ComboStoreSubscriptions->create_subscription($body_arr);
        $delivery_id = isset($order_response['delivery_id']) ? $order_response['delivery_id'] : 0;


        if ($order_response['success']) {
            $response['status'] = "success";
            $response['delivery_id'] = $delivery_id;
            $response['message'] = "Delivery created.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }













    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_tracking($request)
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreDeliveries = new ComboStoreDeliveries();
        // $has_purchased = $ComboStoreDeliveries->has_purchased($prams);


        // if ($has_purchased) {
        //     $response['status'] = "exist";
        //     $response['message'] = "Already Purchased";
        // } else {

        // }

        $tracking_response = $ComboStoreDeliveries->insert_tracking($body_arr);
        $tracking_id = isset($order_response['tracking_id']) ? $order_response['tracking_id'] : 0;


        if ($tracking_response['success']) {
            $response['status'] = "success";
            $response['tracking_id'] = $tracking_id;
            $response['message'] = "tracking created.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_activity($request)
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
        $body_arr = json_decode($body, true);

        $response = [];



        $ComboStoreActivitiy = new ComboStoreActivitiy();


        $_response = $ComboStoreActivitiy->create($body_arr);
        $id = isset($_response['id']) ? $_response['id'] : 0;


        if (isset($_response['success'])) {
            $response['status'] = "success";
            $response['id'] = $id;
            $response['message'] = "Thank you for your subscription.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_subscribe_to_call($request)
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
        $body_arr = json_decode($body, true);
        $product_id = isset($body_arr['product_id']) ? $body_arr['product_id'] : '';


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        $has_prams = [];
        $has_prams['userid'] = $user_id;
        $has_prams['product_id'] = $product_id;


        $ComboStoreSubscriptionsToCall = new ComboStoreSubscriptionsToCall();
        $has_subscribed = $ComboStoreSubscriptionsToCall->has_subscribed($has_prams);


        if ($has_subscribed) {
            $response['status'] = "exist";
            $response['message'] = "Already subscribed";
            die(wp_json_encode($response));
        }

        $order_response = $ComboStoreSubscriptionsToCall->create($body_arr);
        $id = isset($order_response['id']) ? $order_response['id'] : 0;


        if ($order_response['success']) {
            $response['status'] = "success";
            $response['id'] = $id;
            $response['message'] = "Thank you for your subscription.";
        } else {
            $response['status'] = "failed";
            $response['message'] = "There is an error.";
        }



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function check_subscribe_to_call($request)
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
        $body_arr = json_decode($body, true);
        $product_id = isset($body_arr['product_id']) ? $body_arr['product_id'] : '';


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        $has_prams = [];
        $has_prams['userid'] = $user_id;
        $has_prams['product_id'] = $product_id;


        $ComboStoreSubscriptionsToCall = new ComboStoreSubscriptionsToCall();
        $has_subscribed = $ComboStoreSubscriptionsToCall->has_subscribed($has_prams);


        if ($has_subscribed) {
            $response['subscribed'] = true;
        } else {


            $response['subscribed'] = false;
        }
        die(wp_json_encode($response));
    }






    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function post_view($request)
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

        $response = [];

        $post_id     = isset($request['id']) ? absint($request['id']) : '';


        $prams = [];


        if (!empty($post_id)) {
            $prams['object_id'] = $post_id;
        }


        $ComboStoreViews = new ComboStoreViews();
        $response = $ComboStoreViews->add_view($prams);
        $ComboStoreViews->update_object_total_view($prams);


        // if (empty($action)) return;


        // $viewCount = (int) get_post_meta($post_id, 'viewCount', true);

        // $viewCount += 1;

        // $status =  update_post_meta($post_id, 'viewCount', $viewCount);

        // if ($status) {
        //     $response['success'] = true;
        // }
        // if (!$status) {
        //     $response['success'] = true;
        // }


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_order($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $order_id     = isset($request['id']) ? absint($request['id']) : 0;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_orders';
        $table_subscriptions = $prefix . 'cstore_subscriptions';
        $table_deliveries = $prefix . 'cstore_deliveries';
        //$table_licenses = $prefix . 'cstore_licenses';

        $response = [];




        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_orders WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $delivery_location = $ComboStoreObjectMeta->get_meta('orders', $order_id, "delivery_location");
        $advance_payment_note = $ComboStoreObjectMeta->get_meta('orders', $order_id, "advance_payment_note");
        $coupons = $ComboStoreObjectMeta->get_meta('orders', $order_id, "coupons");



        $orderRow['delivery_location'] = json_decode($delivery_location);
        $orderRow['advance_payment_note'] = $advance_payment_note;

        // $licenseRow = $wpdb->get_row(
        //     $wpdb->prepare(
        //         "SELECT * FROM $table_licenses WHERE order_id = %d",
        //         $order_id
        //     ),
        //     ARRAY_A
        // );


        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;



        $deliveryRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_deliveries WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $deliveryRow = empty($deliveryRow) ? [] : $deliveryRow;


        $line_items = [];

        $ComboStoreOrders = new ComboStoreOrders();


        $order_items = $ComboStoreOrders->get_order_items($order_id);

        foreach ($order_items as $order_item) {

            $product_id = isset($order_item->product_id) ? $order_item->product_id : 0;

            $product_title = get_the_title($product_id);

            if ($product_id) {
            }
            $post_thumbnail_url = get_the_post_thumbnail_url($product_id);

            $order_item->thumbnail = $post_thumbnail_url;
        }



        $response['line_items'] = $order_items;
        $response['order'] = $orderRow;
        $response['coupons'] = json_decode($coupons);
        $response['subscription'] = $subscriptionRow;
        $response['delivery'] = $deliveryRow;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_shipping_zone($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $id     = isset($request['id']) ? absint($request['id']) : 0;



        $response = [];

        $ComboStoreShipping = new ComboStoreShipping();

        $row = $ComboStoreShipping->get_shipping_zone($id);







        $response['zone'] = $row;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_shipping_method($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $id     = isset($request['id']) ? absint($request['id']) : 0;


        // global $wpdb;

        // $prefix = $wpdb->prefix;

        // $table_shipping_zones = $prefix . 'cstore_shipping_methods';

        $response = [];

        $ComboStoreShipping = new ComboStoreShipping();

        $orderRow = $ComboStoreShipping->get_shipping_method($id);


        // $orderRow = $wpdb->get_row(
        //     $wpdb->prepare(
        //         "SELECT * FROM $table_shipping_zones WHERE id = %d",
        //         $id
        //     ),
        //     ARRAY_A
        // );





        $response['method'] = $orderRow;


        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function combo_store_connection($request)
    {


        $response = [];


        $response = array(
            'success'      => true,

        );



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function send_sms($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $number     = isset($request['number']) ? sanitize_text_field($request['number']) : null;
        $message     = isset($request['message']) ? sanitize_text_field($request['message']) : null;




        $response = [];


        $api_key  = 'RISwX1DLM4jw5Ph90ATh';
        $senderid = '8809648908147';

        $url = add_query_arg(
            array(
                'api_key' => $api_key,
                'type'    => 'text',
                'number'  => $number,
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

        $response = array(
            'success'      => $status_code >= 200 && $status_code < 300,
            'status_code'  => $status_code,
            'response'     => $body,
        );



        die(wp_json_encode($response));
    }






    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function send_login_otp($request)
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
        $phone     = isset($request['phone']) ? sanitize_text_field($request['phone']) : null;



        $response = [];


        $ComboStoreSMS = new ComboStoreSMS();
        $ComboStoreRegister = new ComboStoreRegister();
        $otp = $ComboStoreRegister->generate_otp($phone);


        $response['otp'] = $otp;

        //$response = $ComboStoreSMS->send_bulksmsbd_sms(['phone' => $phone, 'template' => 'send_login_otp', 'otp' => $otp]);




        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function verify_login_otp($request)
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
        $otp     = isset($request['otp']) ? sanitize_text_field($request['otp']) : null;
        $phone     = isset($request['phone']) ? sanitize_text_field($request['phone']) : null;



        $response = [];


        $ComboStoreSMS = new ComboStoreSMS();
        $ComboStoreRegister = new ComboStoreRegister();
        $response = $ComboStoreRegister->verify_otp($phone, $otp);



        //$response = $ComboStoreSMS->send_bulksmsbd_sms(['phone' => $phone, 'template' => 'send_login_otp', 'otp' => $otp]);




        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function search_purchase_items($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : 0;




        $response = [];





        $line_items = [];

        $ComboStorePurchases = new ComboStorePurchases();


        $order_items = $ComboStorePurchases->search_purchase_items($keyword);




        die(wp_json_encode($order_items));
    }










    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function search_expense_items($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : 0;




        $response = [];





        $line_items = [];

        $ComboStoreExpenses = new ComboStoreExpenses();


        $order_items = $ComboStoreExpenses->search_expense_items($keyword);




        die(wp_json_encode($order_items));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_purchase($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $purchase_id     = isset($request['id']) ? absint($request['id']) : 0;


        $ComboStorePurchases = new ComboStorePurchases();
        $response = $ComboStorePurchases->get_purchase($purchase_id);

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_pages($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $purchase_id     = isset($request['id']) ? absint($request['id']) : 0;

        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $ComboStorePages = new ComboStorePages();
        $response = $ComboStorePages->get_pages($body_arr);

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function create_page($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;

        $body = $request->get_body();
        $body_arr = json_decode($body, true);
        $ComboStorePages = new ComboStorePages();

        $response = $ComboStorePages->create_page($body_arr);

        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_expense($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $purchase_id     = isset($request['id']) ? absint($request['id']) : 0;


        $ComboStoreExpenses = new ComboStoreExpenses();
        $response = $ComboStoreExpenses->get_expense($purchase_id);

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_template_part($request)
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


        // $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        // if (!$user_id) {
        //     return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        // }



        // $user = get_user_by('id', $user_id);

        // if (!$user) {
        //     return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        // }

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        // $purchase_id     = isset($request['id']) ? absint($request['id']) : 0;


        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_template_part($body_arr);

        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_template($request)
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
        $purchase_id     = isset($request['id']) ? absint($request['id']) : 0;


        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_template($purchase_id);



        die(wp_json_encode($response));
    }
    /**
     * Save a site template's name, description, thumbnail, style and meta (its
     * fonts and colors, from the builder's Template Settings). Requires a JWT,
     * as update_template_part does.
     *
     * The meta ends up in a <style> on every storefront page, so its strings
     * lose anything that could end a CSS rule or the tag (the storefront cleans
     * each value again, in lib/templateTheme.js).
     *
     * @param WP_REST_Request $request
     */
    public function update_template($request)
    {
        $token = $request->get_header('Authorization');
        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }
        $token = str_replace('Bearer ', '', $token);
        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;
        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $body_arr = json_decode($request->get_body(), true);
        if (!is_array($body_arr) || empty($body_arr['id'])) {
            die(wp_json_encode(['success' => false, 'message' => 'Template id is required.']));
        }

        if (isset($body_arr['meta']) && is_array($body_arr['meta'])) {
            array_walk_recursive($body_arr['meta'], function (&$value) {
                if (is_string($value)) {
                    /* Not ';' - Google Fonts links use it between weights (wght@400;700). */
                    $value = trim(str_replace(['<', '>', '{', '}', '\\'], '', $value));
                }
            });
        }

        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->update_template($body_arr);

        die(wp_json_encode($response));
    }
    /**
     * The active site template's settings: { id, meta }, meta being the
     * template's fonts, typography and colors from the builder's Template
     * Settings. Public and small, for the storefront's <head> on every page.
     *
     * @param WP_REST_Request $request
     */
    public function get_template_settings($request)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cstore_templates';
        $combo_store_settings = get_option('combo_store_settings', []);
        $template_id = isset($combo_store_settings['site_template']['template_id']) ? absint($combo_store_settings['site_template']['template_id']) : 0;

        $raw = $template_id ? $wpdb->get_var($wpdb->prepare("SELECT meta FROM $table WHERE id = %d", $template_id)) : '';
        $meta = $raw ? json_decode($raw, true) : [];

        die(wp_json_encode([
            'id'   => $template_id,
            'meta' => (object) (is_array($meta) ? $meta : []),
        ], JSON_UNESCAPED_UNICODE));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_active_template($request)
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


        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_active_template();



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_page_template($request)
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
        $body = $request->get_body();


        $body_arr = json_decode($body);

        $ComboStoreTemplateParts = new ComboStoreTemplateParts();
        $response = $ComboStoreTemplateParts->get_page_template($body_arr);



        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_order_by_hash($request)
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
        $order_hash     = isset($request['slug']) ? sanitize_text_field($request['slug']) : 0;


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_orders';
        $table_subscriptions = $prefix . 'cstore_subscriptions';
        $table_deliveries = $prefix . 'cstore_deliveries';
        //$table_licenses = $prefix . 'cstore_licenses';

        $response = [];


        $ComboStoreOrders = new ComboStoreOrders();

        $order_id = $ComboStoreOrders->get_order_id_by_hash($order_hash);



        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_orders WHERE id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();


        $delivery_location = $ComboStoreObjectMeta->get_meta('orders', $order_id, "delivery_location");


        $orderRow['delivery_location'] = json_decode($delivery_location);

        // $licenseRow = $wpdb->get_row(
        //     $wpdb->prepare(
        //         "SELECT * FROM $table_licenses WHERE order_id = %d",
        //         $order_id
        //     ),
        //     ARRAY_A
        // );


        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;



        $deliveryRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_deliveries WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );

        $deliveryRow = empty($deliveryRow) ? [] : $deliveryRow;


        $line_items = [];

        $ComboStoreOrders = new ComboStoreOrders();


        $order_items = $ComboStoreOrders->get_order_items($order_id);

        foreach ($order_items as $order_item) {

            $product_id = isset($order_item->product_id) ? $order_item->product_id : 0;

            $product_title = get_the_title($product_id);

            $order_item->title = $product_title;
        }



        $response['line_items'] = $order_items;
        $response['order'] = $orderRow;
        // $response['license'] = $licenseRow;
        $response['subscription'] = $subscriptionRow;
        $response['delivery'] = $deliveryRow;


        die(wp_json_encode($response));
    }








    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_delivery($request)
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



        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;
        $id     = isset($request['id']) ? absint($request['id']) : 0;
        $order_id     = isset($request['order_id']) ? absint($request['order_id']) : 0;
        $delivery_id     = isset($request['delivery_id']) ? absint($request['delivery_id']) : 0;


        $response = [];

        $ComboStoreDeliveries = new ComboStoreDeliveries();

        $delivery = $ComboStoreDeliveries->get_delivery($order_id, $delivery_id);



        $response['delivery'] = ($delivery);


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_licenses($request)
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



        $userRoles = isset($user->roles) ? $user->roles : [];
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? absint($request['order']) : "DESC";


        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_licenses = $prefix . 'cstore_licenses';
        $table_orders = $prefix . 'cstore_orders';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));


        if ($isAdmin) {

            $entries = $wpdb->get_results("SELECT * FROM $table_licenses ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_licenses");
        } else {

            $entries = $wpdb->get_results("SELECT * FROM $table_licenses WHERE userid='$user_id' ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_licenses WHERE userid='$user_id'");
        }



        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;

        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function valilast_dateken($request)
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
        }        // Return user details
        return rest_ensure_response(array(
            'success' => true,
            'user'    => array(
                'id'    => $user->ID,
                'email' => $user->user_email,
                'name'  => $user->display_name,
                // 'total_credit'  => $total_credit,
                // 'credit_used_cron'  => $total_credit_used,
                // 'credit_used_api'  => $total_credit_used,
                // 'credit_used'  => $total_credit_used,
                'avatar'  => get_avatar_url($user->user_email),
            ),
        ));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
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

        $first_name              = get_user_meta($user_id,  "first_name", true);
        $last_name              = get_user_meta($user_id,  "last_name", true);
        $email              = isset($userData->user_email) ? $userData->user_email : '';
        $phone              = get_user_meta($user_id,  "phone", true);
        $billing_address1              = get_user_meta($user_id,  "billing_address1", true);
        $billing_address2              = get_user_meta($user_id,  "billing_address2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);
        $addresses              = get_user_meta($user_id,  "addresses", true);
        $phones              = get_user_meta($user_id,  "phones", true);
        $kids              = get_user_meta($user_id,  "kids", true);


        error_log(wp_json_encode($addresses));

        // Return user details
        return rest_ensure_response(array(
            'success' => true,
            'user'    => array(
                'id'    => $user->ID,
                'email' => $user->user_email,
                'name'  => $user->display_name,
                //'total_credit'  => $total_credit,
                //'credit_used_api'  => $credit_used_api,
                //'total_credit_used'  => $total_credit_used,
                'avatar'  => get_avatar_url($user->user_email),
                'roles'  => $user->roles,
                'first_name'  => $first_name,
                'last_name'  => $last_name,
                'phone'  => $phone,
                'billing_address1'  => $billing_address1,
                'billing_address2'  => $billing_address2,
                'zip_code'  => $zip_code,
                'country'  => $country,
                'city'  => $city,
                'delivery_location'  => $delivery_location,
                'addresses'  => $addresses,
                'phones'  => $phones,
                'kids'  => $kids,

            ),
        ));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_user_data($request)
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

        $first_name              = get_user_meta($user_id,  "first_name", true);
        $last_name              = get_user_meta($user_id,  "last_name", true);
        $email              = isset($userData->user_email) ? $userData->user_email : '';
        $phone              = get_user_meta($user_id,  "phone", true);
        $billing_address1              = get_user_meta($user_id,  "billing_address1", true);
        $billing_address2              = get_user_meta($user_id,  "billing_address2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);


        // Return user details
        return rest_ensure_response(array(
            'success' => true,
            'user'    => array(
                'id'    => $user->ID,
                'email' => $user->user_email,
                'name'  => $user->display_name,
                //'total_credit'  => $total_credit,
                //'credit_used_api'  => $credit_used_api,
                //'total_credit_used'  => $total_credit_used,
                'avatar'  => get_avatar_url($user->user_email),
                'roles'  => $user->roles,
                'first_name'  => $first_name,
                'last_name'  => $last_name,
                'phone'  => $phone,
                'billing_address1'  => $billing_address1,
                'billing_address2'  => $billing_address2,
                'zip_code'  => $zip_code,
                'country'  => $country,
                'city'  => $city,
                'delivery_location'  => $delivery_location,

            ),
        ));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function check_mobile_fraud($request)
    {


        $responses = [];

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

        $phone     = isset($request['phone']) ? sanitize_text_field($request['phone']) : '';


        if (empty($phone)) {
            $responses['errors'] = true;
            $responses['message'] = 'Phone number is required';
            die(wp_json_encode($responses));
        }



        $settings = get_option('combo_store_settings');


        $apiKeys = $settings['apiKeys'] ?? [];
        $api_key = $apiKeys['bdcourier']['key'] ?? '';


        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.bdcourier.com/courier-check");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["phone" => $phone]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer $api_key"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);


        $responses['errors'] = false;
        $responses['data'] = $response;
        die(wp_json_encode($responses));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function check_mobile_fraud_bk($request)
    {


        $responses = [];

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

        $phone     = isset($request['phone']) ? sanitize_text_field($request['phone']) : '';


        if (empty($phone)) {
            $responses['errors'] = true;
            $responses['message'] = 'Phone number is required';
            die(wp_json_encode($responses));
        }


        $api_key = "8113cfb824443bc946ece74ed557c510";

        $url = "https://fraudchecker.link/api/v1/qc/";
        $data = array('phone' => $phone);

        $options = array(
            'http' => array(
                'header' => "Authorization: Bearer " . $api_key . "\r\n" .
                    "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);


        $result = json_decode($response, true);


        $responses['errors'] = false;
        $responses['data'] = $result;
        die(wp_json_encode($responses));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function post_loved($request)
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


        $source     = isset($request['source']) ? sanitize_text_field($request['source']) : '';
        $object_id     = isset($request['object_id']) ? sanitize_text_field($request['object_id']) : '';



        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        $prams = [];

        if (!empty($user_id)) {
            $prams['userid'] = $user_id;
        }
        if (!empty($object_id)) {
            $prams['object_id'] = $object_id;
        }
        die(wp_json_encode($response));
    }



    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_user_profile($request)
    {


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




        if (empty($user_id)) {
            $response['errors'] = 'User id empty';
            die(wp_json_encode($response));
        }

        $userData = get_user_by('id', $user_id);


        $first_name              = get_user_meta($user_id,  "first_name", true);
        $last_name              = get_user_meta($user_id,  "last_name", true);
        $email              = isset($userData->user_email) ? $userData->user_email : '';
        $name              = isset($userData->display_name) ? $userData->display_name : '';
        $phone              = get_user_meta($user_id,  "phone", true);
        $billing_address1              = get_user_meta($user_id,  "billing_address1", true);
        $billing_address2              = get_user_meta($user_id,  "billing_address2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);



        $response['name'] = $name;
        $response['first_name'] = $first_name;
        $response['last_name'] = $last_name;
        $response['email'] = $email;
        $response['phone'] = $phone;
        $response['billing_address1'] = $billing_address1;
        $response['billing_address2'] = $billing_address2;
        $response['zip_code'] = $zip_code;
        $response['country'] = $country;
        $response['city'] = $city;
        $response['delivery_location'] = $delivery_location;



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_user_by_id($request)
    {


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




        if (empty($user_id)) {
            $response['errors'] = 'User id empty';
            die(wp_json_encode($response));
        }

        $userData = get_user_by('id', $user_id);


        $first_name              = get_user_meta($user_id,  "first_name", true);
        $last_name              = get_user_meta($user_id,  "last_name", true);
        $email              = isset($userData->user_email) ? $userData->user_email : '';
        $name              = isset($userData->display_name) ? $userData->display_name : '';
        $phone              = get_user_meta($user_id,  "phone", true);
        $billing_address1              = get_user_meta($user_id,  "billing_address1", true);
        $billing_address2              = get_user_meta($user_id,  "billing_address2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);



        $response['name'] = $name;
        $response['first_name'] = $first_name;
        $response['last_name'] = $last_name;
        $response['email'] = $email;
        $response['phone'] = $phone;
        $response['billing_address1'] = $billing_address1;
        $response['billing_address2'] = $billing_address2;
        $response['zip_code'] = $zip_code;
        $response['country'] = $country;
        $response['city'] = $city;
        $response['delivery_location'] = $delivery_location;



        die(wp_json_encode($response));
    }







    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_rider($request)
    {


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



        //$user_id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';

        // if (empty($user_id)) {
        //     $response['errors'] = 'User id empty';
        //     die(wp_json_encode($response));
        // }

        // $userData = get_user_by('id', $user_id);


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';

        if (empty($id)) {
            $response['errors'] = 'User id empty';
            die(wp_json_encode($response));
        }

        $rider = get_user_by('id', $id);


        $first_name              = get_user_meta($id,  "first_name", true);
        $last_name              = get_user_meta($id,  "last_name", true);
        $phone              = get_user_meta($id,  "phone", true);

        $riderData = [];

        $riderData['id'] = $id;
        $riderData['avatar'] = get_avatar_url($rider->user_email);
        $riderData['name'] = isset($rider->display_name) ? $rider->display_name : "";
        $riderData['phone'] = $phone;
        // $riderData['phone'] = 01537034053;
        $riderData['email'] = isset($rider->user_email) ? $rider->user_email : "";

        $response['rider'] = $riderData;



        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_customer($request)
    {


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



        //$user_id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';

        // if (empty($user_id)) {
        //     $response['errors'] = 'User id empty';
        //     die(wp_json_encode($response));
        // }

        // $userData = get_user_by('id', $user_id);


        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';

        /*
         * Staff look customers up by id from the dashboard. Anyone else gets
         * their own profile, whatever id they send: this used to return any
         * user's details to any logged-in user who asked for their id.
         */
        $requester = get_user_by('id', $user_id);
        if (!$requester || !$this->is_ticket_staff($requester)) {
            $id = $user_id;
        }

        if (empty($id)) {
            $response['errors'] = 'User id empty';
            die(wp_json_encode($response));
        }

        $rider = get_user_by('id', $id);

        if (!$rider) {
            $response['errors'] = 'User not found';
            die(wp_json_encode($response));
        }

        $first_name              = get_user_meta($id,  "first_name", true);
        $last_name              = get_user_meta($id,  "last_name", true);
        $email              = isset($rider->user_email) ? $rider->user_email : '';
        $phone              = get_user_meta($id,  "phone", true);
        $phones              = get_user_meta($id,  "phones", true);
        $billing_address1              = get_user_meta($id,  "billing_address1", true);
        $billing_address2              = get_user_meta($id,  "billing_address2", true);
        $zip_code              = get_user_meta($id,  "zip_code", true);
        $billing_country              = get_user_meta($id,  "billing_country", true);
        $billing_city              = get_user_meta($id,  "billing_city", true);
        $billing_name              = get_user_meta($id,  "billing_name", true);
        $billing_email              = get_user_meta($id,  "billing_email", true);
        $billing_phone              = get_user_meta($id,  "billing_phone", true);
        $delivery_location              = get_user_meta($id,  "delivery_location", true);
        $addresses              = get_user_meta($id,  "addresses", true);
        $phones              = get_user_meta($id,  "phones", true);
        $kids              = get_user_meta($id,  "kids", true);




        $response['customer'] = array(
            'id' => $rider->ID,
            'email' => $rider->user_email,
            'name'  => $rider->display_name,
            'avatar'  => get_avatar_url($rider->user_email),
            'roles'  => $rider->roles,
            'first_name'  => $first_name,
            'last_name'  => $last_name,
            'phone'  => $phone,
            'phones'  => $phones ? $phones : [],
            'addresses'  => $addresses ? $addresses : [],
            'kids'  => $kids ? $kids : [],
            'billing_address1'  => $billing_address1,
            'billing_address2'  => $billing_address2,
            'zip_code'  => $zip_code,
            'billing_phone'  => $billing_phone,
            'billing_name'  => $billing_name,
            'billing_email'  => $billing_email,
            'billing_city'  => $billing_city,
            'billing_country'  => $billing_country,
            'delivery_location'  => $delivery_location,

        );

        error_log(wp_json_encode($response));;

        die(wp_json_encode($response));
    }








    /**
     * Change a password.
     *
     * The dashboard's and storefront's password boxes (CustomerProfile,
     * UserAccount) posted here for years, but nothing was registered at this
     * route, so every attempt was a 404 and no password ever changed.
     *
     *  - Your own password: the current one is required and checked.
     *  - Someone else's: administrators only, no current password needed,
     *    and never another administrator's.
     *
     * Answers 200 with { success } or { errors } (the boxes show either text);
     * a missing or bad login is a 401.
     *
     * @param WP_REST_Request $request id, old_password, new_password.
     */
    public function update_user_password($request)
    {
        $requester = $this->get_ticket_user($request);

        if (is_wp_error($requester)) {
            return $requester;
        }

        $target_id    = isset($request['id']) ? absint($request['id']) : 0;
        $old_password = isset($request['old_password']) ? (string) $request['old_password'] : '';
        $new_password = isset($request['new_password']) ? (string) $request['new_password'] : '';

        if (!$target_id) {
            $target_id = (int) $requester->ID;
        }

        $answer = function ($errors, $success = '') {
            die(wp_json_encode(['success' => $success, 'errors' => $errors]));
        };

        $target = get_user_by('id', $target_id);

        if (!$target) {
            $answer('User not found.');
        }

        /* Passwords are taken as typed: sanitising them would change them. */
        if (strlen($new_password) < 8) {
            $answer('The new password must be at least 8 characters.');
        }

        $is_self = (int) $target->ID === (int) $requester->ID;

        if ($is_self) {
            if ($old_password === '' || !wp_check_password($old_password, $target->user_pass, $target->ID)) {
                $answer('The current password is not correct.');
            }

            if (hash_equals($old_password, $new_password)) {
                $answer('The new password is the same as the current one.');
            }
        } else {
            if (!in_array('administrator', (array) $requester->roles, true)) {
                $answer("Only an administrator can set another person's password.");
            }

            if (in_array('administrator', (array) $target->roles, true)) {
                $answer("An administrator's password can only be changed by that administrator.");
            }
        }

        /* Also signs the account out of its WordPress sessions. */
        wp_set_password($new_password, $target->ID);

        $answer('', $is_self ? 'Your password was changed.' : 'The password was changed. Let them know their new password.');
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_user_profile($request)
    {
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);






        $id     = isset($body_arr['id']) ? sanitize_text_field($body_arr['id']) : '';
        $userData     = isset($body_arr['userData']) && is_array($body_arr['userData']) ? ($body_arr['userData']) : [];

        /*
         * Staff edit customers by id from the dashboard. Anyone else can only
         * save their own profile: this used to write to whatever id the
         * request named, so one customer could overwrite another's details.
         */
        $requester = get_user_by('id', $user_id);
        if (!$requester || !$this->is_ticket_staff($requester)) {
            $id = $user_id;
        }

        if (empty($id) || !get_user_by('id', $id)) {
            $response['error'] = true;
            $response['message'] = 'User not found';
            die(wp_json_encode($response));
        }

        /*
         * Only the fields the request actually carries are written. Every
         * field used to be saved, with a missing one stored as empty, so a
         * form that sent a few fields blanked all the rest - the storefront
         * profile page wiped shipping details, profession and the like on
         * every save. A field sent empty (or null) is still cleared.
         */
        $has = function ($key) use ($userData) {
            return array_key_exists($key, $userData);
        };

        $text_fields = array(
            'phone',
            'billing_name',
            'billing_address1',
            'billing_address2',
            'billing_phone',
            'billing_email',
            'billing_country',
            'billing_city',
            'shipping_name',
            'shipping_address1',
            'shipping_address2',
            'shipping_phone',
            'shipping_email',
            'shipping_country',
            'shipping_city',
            'zip_code',
            'customer_category',
            'income',
            'profession',
            'has_orderd',
        );

        $userdata = array('ID' => $id);

        if ($has('first_name')) {
            $userdata['first_name'] = sanitize_text_field((string) $userData['first_name']);
        }
        if ($has('last_name')) {
            $userdata['last_name'] = sanitize_text_field((string) $userData['last_name']);
        }
        if ($has('name') && trim((string) $userData['name']) !== '') {
            $userdata['display_name'] = sanitize_text_field((string) $userData['name']);
        }
        /* An empty email is never written: it is the account's login. */
        if ($has('email') && sanitize_email((string) $userData['email']) !== '') {
            $userdata['user_email'] = sanitize_email((string) $userData['email']);
        }

        $result = count($userdata) > 1 ? wp_update_user($userdata) : $id;

        if ($has('first_name')) {
            update_user_meta($id, 'first_name', $userdata['first_name']);
        }
        if ($has('last_name')) {
            update_user_meta($id, 'last_name', $userdata['last_name']);
        }

        foreach ($text_fields as $field) {
            if ($has($field)) {
                update_user_meta($id, $field, sanitize_text_field((string) $userData[$field]));
            }
        }

        if ($has('delivery_location')) {
            update_user_meta($id, 'delivery_location', $userData['delivery_location'] ? stripslashes_deep($userData['delivery_location']) : '');
        }

        if (isset($userData['phones']) && is_array($userData['phones'])) {
            update_user_meta($id, 'phones', $userData['phones']);
        }
        if (isset($userData['addresses']) && is_array($userData['addresses'])) {
            update_user_meta($id, 'addresses', $userData['addresses']);
        }
        if (isset($userData['kids']) && is_array($userData['kids'])) {
            update_user_meta($id, 'kids', $userData['kids']);
        }


        if (is_wp_error($result)) {

            $response['error'] = true;
            $response['message'] = $result->get_error_message();
        } else {








            $response['success'] = true;
        }
        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_users($request)
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


        $role     = isset($request['role']) ? sanitize_text_field($request['role']) : 'any';

        /*
            Any valid token used to be enough to list every account, emails
            included. Staff may list any role; a rider may list riders (the
            delivery screen's rider picker); nobody else may list users.
        */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !$this->is_ticket_staff($requester)) {
            $is_rider = $requester && in_array('rider', (array) $requester->roles, true);

            if (!$is_rider || $role !== 'rider') {
                return new WP_Error('forbidden', 'You are not allowed to list users', array('status' => 403));
            }
        }
        $search     = isset($request['search']) ? sanitize_text_field($request['search']) : "";
        $number     = isset($request['number']) ? sanitize_text_field($request['number']) : "";
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $paged      = isset($request['paged']) ? sanitize_text_field($request['paged']) : 1;
        $with_orders = !empty($request['with_orders']);

        $prevText = !empty($request['prevText']) ? $request['prevText'] : "";
        $nextText = !empty($request['nextText']) ? $request['nextText'] : "";
        $maxPageNum = !empty($request['maxPageNum']) ? $request['maxPageNum'] : 0;
        // $paged = 1;
        $query_args = [];


        if ($role) {
            $query_args['role'] = $role;
        }
        if ($search) {
            $query_args['search'] = '*' . $search . '*';
        }
        if ($number) {
            $query_args['number'] = $number;
        }
        if ($paged) {
            $query_args['paged'] = $paged;
        }
        if ($orderby) {
            $query_args['orderby'] = $orderby;
        }
        if ($order) {
            $query_args['order'] = $order;
        }

        // $query_args['fields'] = array( 'display_name' );



        $posts = [];
        $responses = [];
        // $terms = get_users($query_args);

        $user_search = new WP_User_Query($query_args);
        $users = $user_search->get_results();

        if ($users) :
            $responses['noPosts'] = false;
            foreach ($users as  $user) :
                $user_data = new stdClass();
                $user_data->id = $user->ID;
                $user_data->user_login = $user->user_login;
                $user_data->user_nicename = $user->user_nicename;
                $user_data->user_email = $user->user_email;
                //$user_data->registered = $user->user_registered;
                //$user_data->user_status = $user->user_status;
                $user_data->description = $user->description;
                $user_data->display_name = $user->display_name;
                $user_data->name = $user->display_name;
                $user_data->first_name = $user->first_name;
                $user_data->last_name = $user->last_name;
                $user_data->roles = $user->roles;
                //$user_data->caps = $user->caps;
                $user_data->url = $user->user_url;
                $user_data->avatar = get_avatar_url($user->ID);

                $phone = get_user_meta($user->ID, 'phone', true);
                $phones = get_user_meta($user->ID, 'phones', true);
                $addresses = get_user_meta($user->ID, 'addresses', true);
                $billing_name = get_user_meta($user->ID, 'billing_name', true);
                $billing_phone = get_user_meta($user->ID, 'billing_phone', true);
                $billing_email = get_user_meta($user->ID, 'billing_email', true);
                $billing_city = get_user_meta($user->ID, 'billing_city', true);
                $billing_country = get_user_meta($user->ID, 'billing_country', true);


                $user_data->phone = $phone ? $phone : "";
                $user_data->addresses = $addresses ? $addresses : [];
                $user_data->phones = $phones ? $phones : [];
                $user_data->billing_email = $billing_email ? $billing_email : $user->user_email;
                $user_data->billing_phone = $billing_phone ? $billing_phone : $phone;
                $user_data->billing_name = $billing_name ? $billing_name : $user->display_name;
                $user_data->billing_city = $billing_city ? $billing_city : '';
                $user_data->billing_country = $billing_country ? $billing_country : '';

                if ($with_orders) {
                    $user_data->registered = $user->user_registered;
                }




                error_log(wp_json_encode($addresses));

                $posts[] = $user_data;
            endforeach;

            /* Only the Customers list asks for this, so the pickers do not pay for it. */
            if ($with_orders) {
                global $wpdb;

                $ids = array_map('intval', wp_list_pluck($posts, 'id'));
                $placeholders = implode(',', array_fill(0, count($ids), '%d'));

                $order_counts = $wpdb->get_results($wpdb->prepare(
                    "SELECT userid, COUNT(*) AS total_orders FROM {$wpdb->prefix}cstore_orders WHERE userid IN ($placeholders) GROUP BY userid",
                    $ids
                ), OBJECT_K);

                foreach ($posts as $post) {
                    $post->total_orders = isset($order_counts[$post->id]) ? (int) $order_counts[$post->id]->total_orders : 0;
                }
            }

            $total = $user_search->get_total();




            /* `number` is optional; dividing by the empty string was a fatal error on PHP 8. */
            $max_pages = absint($number) > 0 ? ceil($total / absint($number)) : 1;


            $responses['total'] = $total;
            $responses['max_pages'] = $max_pages;
            $responses['posts'] = $posts;


        else :
            $responses['noPosts'] = true;
        endif;





        die(wp_json_encode($responses));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_object_meta($request)
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
     * @param WP_REST_Request $request Post data.
     */
    public function duplicate_object($request)
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
     * @param WP_REST_Request $request Post data.
     */
    public function register_user($request)
    {




        $email     = isset($request['email']) ? sanitize_email($request['email']) : '';
        $mobile     = isset($request['mobile']) ? sanitize_text_field($request['mobile']) : '';
        $name     = isset($request['name']) ? sanitize_text_field($request['name']) : '';

        $registerBy     = isset($request['registerBy']) ? sanitize_text_field($request['registerBy']) : '';
        $password     = isset($request['password']) ? sanitize_text_field($request['password']) : '';
        $requested_role     = isset($request['role']) ? sanitize_text_field($request['role']) : '';

        /*
         * Who may choose the new account's role. This endpoint is public (it is
         * the storefront's sign-up), and it used to give the account whatever
         * role the request named - so anyone could post "administrator" and
         * get an admin account. Now:
         *  - without a staff login, the account is always a customer;
         *  - only an administrator may create any other kind of account
         *    (rider, store manager, support agent, seller, supplier...).
         *    Any staff role used to be enough, so a support agent could make
         *    store managers; the dashboard lists that create staff are
         *    administrator-only anyway.
         */
        $role = 'customer';

        if ($requested_role !== '' && $requested_role !== 'customer') {

            $requester = $request->get_header('Authorization') ? $this->get_ticket_user($request) : null;

            if (!$requester || is_wp_error($requester) || !in_array('administrator', (array) $requester->roles, true)) {
                die(wp_json_encode(wp_json_encode(array('error' => true, 'messages' => array('Only an administrator can create this kind of account.')))));
            }

            if (!get_role($requested_role)) {
                die(wp_json_encode(wp_json_encode(array('error' => true, 'messages' => array('Unknown role.')))));
            }

            $role = $requested_role;
        }


        $ComboStoreRegister = new ComboStoreRegister();

        $response = [];


        $response = $ComboStoreRegister->create_user([
            'email' => $email,
            "password" => $password,
            "role" => $role,
            "mobile" => $mobile,
            "registerBy" => $registerBy,
            "name" => $name

        ]);



        die(wp_json_encode($response));
    }




    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_orders($request)
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

        /*
            Any valid token used to be enough, so a customer could permanently
            delete any order (a hard delete from cstore_orders - orders have no
            trash). Only the roles whose screens delete orders (Orders,
            Incomplete, Refunds) may: administrators and store managers.
        */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to delete orders', array('status' => 403));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $ids     = is_array($ids) ? array_filter(array_map('absint', $ids)) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_orders';
        // $table_orders_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        $deleted = 0;


        if (!empty($ids)) {

            foreach ($ids as $id) {

                //$wpdb->delete($table_subscriptions, array('task_id' => $id), array('%d'));
                $removed = (int) $wpdb->delete($table_orders, array('id' => $id), array('%d'));
                $deleted += $removed;

                /* The order's lines went with it; they used to be left behind. */
                if ($removed) {
                    $wpdb->delete($prefix . 'cstore_order_items', array('order_id' => $id), array('%d'));
                }
            }
        }

        $response['success'] = true;
        $response['deleted'] = $deleted;


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_shipping_zones($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_shipping_zones';


        if (!empty($ids)) {

            foreach ($ids as $id) {

                //$wpdb->delete($table_subscriptions, array('task_id' => $id), array('%d'));
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
     * @param WP_REST_Request $request Post data.
     */
    public function duplicate_order($request)
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

        /* Any valid token used to be enough to copy any order, a customer's too. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !$this->is_ticket_staff($requester)) {
            return new WP_Error('forbidden', 'You are not allowed to duplicate orders', array('status' => 403));
        }


        $order_id     = isset($request['id']) ? ($request['id']) : [];



        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        $ComboStoreOrders = new ComboStoreOrders();



        $response = $ComboStoreOrders->duplicate_order($order_id);



        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_expenses($request)
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

        /* Any valid token used to be enough, a customer's too. Only the roles the
           Expenses menu is open to may delete expenses. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to delete expenses', array('status' => 403));
        }


        $ids     = isset($request['ids']) && is_array($request['ids']) ? array_filter(array_map('absint', $request['ids'])) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_expenses';
        // $table_orders_meta = $prefix . 'cstore_expenses_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        $deleted = 0;

        if (!empty($ids)) {
            foreach ($ids as $id) {
                $removed = (int) $wpdb->delete($table_orders, array('id' => $id), array('%d'));
                $deleted += $removed;

                /* Its lines go with it; they used to be left behind. */
                if ($removed) {
                    $wpdb->delete($prefix . 'cstore_expenses_items', array('expense_id' => $id), array('%d'));
                }
            }
        }

        $response['success'] = true;
        $response['deleted'] = $deleted;


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_template_parts($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_template_parts';


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_template($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_templates';


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_purchases($request)
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

        /* Any valid token used to be enough, a customer's too. Only the roles the
           Purchases menu is open to may delete purchases. */
        $requester = get_user_by('id', $user_id);

        if (!$requester || !array_intersect(['administrator', 'store_manager'], (array) $requester->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to delete purchases', array('status' => 403));
        }


        $ids     = isset($request['ids']) && is_array($request['ids']) ? array_filter(array_map('absint', $request['ids'])) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_orders = $prefix . 'cstore_purchases';
        // $table_orders_meta = $prefix . 'cstore_purchases_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        $deleted = 0;

        if (!empty($ids)) {
            foreach ($ids as $id) {
                $removed = (int) $wpdb->delete($table_orders, array('id' => $id), array('%d'));
                $deleted += $removed;

                /* Its lines go with it; they used to be left behind. */
                if ($removed) {
                    $wpdb->delete($prefix . 'cstore_purchases_items', array('purchase_id' => $id), array('%d'));
                }
            }
        }

        $response['success'] = true;
        $response['deleted'] = $deleted;


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_deliveries($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table_deliveries = $prefix . 'cstore_deliveries';
        // $table_deliveries_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                //$wpdb->delete($table_subscriptions, array('task_id' => $id), array('%d'));
                $wpdb->delete($table_deliveries, array('id' => $id), array('%d'));
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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_trackings($request)
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));


        global $wpdb;
        $prefix = $wpdb->prefix;

        $table = $prefix . 'cstore_deliveries_trackings';
        // $table_deliveries_meta = $prefix . 'cstore_orders_meta';
        // $table_subscriptions = $prefix . 'cstore_subscriptions';
        // $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        // $table_licenses = $prefix . 'cstore_licenses';
        // $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        if (!empty($ids)) {

            foreach ($ids as $id) {


                //$wpdb->delete($table_subscriptions, array('task_id' => $id), array('%d'));
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
     * @param WP_REST_Request $request Post data.
     */
    public function submit_ticket($request)
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




        // $post_id     = isset($request['id']) ? sanitize_text_field($request['id']) : 0;

        $post_title = $request->get_param('title');
        $email = $request->get_param('email');
        $post_content = $request->get_param('post_content');
        $post_excerpt = $request->get_param('post_excerpt');
        $markedAs = $request->get_param('markedAs');
        $comment_status = $request->get_param('comment_status');
        $post_parent = $request->get_param('post_parent');
        $menu_order = $request->get_param('menu_order');
        $priority = $request->get_param('priority');
        $categories = $request->get_param('categories');
        $tags = $request->get_param('tags');

        $phone = $request->get_param('phone');
        $product_id = $request->get_param('product_id');

        $tags = $tags ? $tags : [];
        $categories = $categories ? $categories : [];

        $featured = $request->get_param('featured');


        $ComboStoreRegister = new ComboStoreRegister();
        $user_response = $ComboStoreRegister->create_user($email, "", 'customer');

        $user_response = json_decode($user_response);


        $user_id = isset($user_response->user_id) ? $user_response->user_id : null;


        // $tagNames = [];
        // foreach ($tags as $tag) {
        //     $tagNames[] = $tag;
        // }
        $response = [];

        if (empty($post_title)) {
            $response['errors']['post_title_missing'] = __("Post Title Missing", 'combostore-server');
        }
        if (empty($post_content)) {
            $response['errors']['prompt_missing'] = __("Details Text Missing", 'combostore-server');
        }
        if (empty($email)) {
            $response['errors']['email_missing'] = __("Email address Missing", 'combostore-server');
        }
        $postData = [
            "post_title" => $post_title,
            "post_content" => $post_content,
            "post_author" => $user_id,
            'post_type'      => 'ticket',
            'post_status'      => 'pending',
            "post_excerpt" => $post_excerpt,
            "comment_status" => $comment_status,
            "post_parent" => $post_parent,
            "menu_order" => $menu_order,
        ];
        // wp_update_post($postData);
        $post_id = wp_insert_post($postData);


        if ($post_id) {
            $response['success'] = true;
        }
        if (!$post_id) {
            $response['error'] = true;
            die(wp_json_encode($response));
        }

        wp_set_post_terms($post_id, $categories, 'ticket_cat');
        wp_set_post_terms($post_id, $tags, 'ticket_tag');




        if (!empty($menu_order)) {
            update_post_meta($post_id, 'menu_order', $menu_order);
        }
        if (!empty($featured)) {
            update_post_meta($post_id, 'featured', $featured);
        }
        if (!empty($priority)) {
            update_post_meta($post_id, 'priority', $priority);
        }
        if (!empty($phone)) {
            update_post_meta($post_id, 'phone', $phone);
        }
        if (!empty($product_id)) {
            update_post_meta($post_id, 'product_id', $product_id);
        }


        if (!empty($markedAs)) {
            update_post_meta($post_id, 'markedAs', $markedAs);
        }



        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function add_ticket($request)
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
            'post_title'      => 'Sample Ticket Title',
            'post_type'      => 'ticket',
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
     * @param WP_REST_Request $request Post data.
     */
    public function get_ticket($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        $user_id = $user->ID;
        $isAdmin = $this->is_ticket_staff($user);

        $post_id = isset($request['id']) ? absint($request['id']) : 0;
        $postData = $post_id ? get_post($post_id) : null;

        /* Only tickets: this endpoint must not be a way to read any other post. */
        if (!$postData || $postData->post_type !== 'ticket') {
            return new WP_Error('ticket_not_found', 'Ticket not found', array('status' => 404));
        }

        /* Staff read every ticket; a customer only their own. */
        if (!$isAdmin && (int) $postData->post_author !== (int) $user_id) {
            return new WP_Error('forbidden', 'You are not allowed to view this ticket', array('status' => 403));
        }

        $response = [];

        $prams = [];
        $prams['object_id'] = $post_id;
        $prams['userid'] = $user_id;

        // $ComboStoreViews = new ComboStoreViews();
        // $viewCount = $ComboStoreViews->get_object_view_count($prams);

        // $ComboStoreLove = new ComboStoreLove();
        // $loveCount = $ComboStoreLove->total_loved($prams);
        // $loved = $ComboStoreLove->has_loved($prams);


        // $ComboStorePurchase = new ComboStorePurchase();
        // $has_purchased = ($isAdmin) ? true : $ComboStorePurchase->has_purchased($prams);

        // $ComboStoreVote = new ComboStoreVote();
        // $get_vote = $ComboStoreVote->get_vote($prams);


        $promptData = [];

        $postData = get_post($post_id);


        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
        $post_thumbnail_id = get_post_thumbnail_id($post_id);

        $featured = get_post_meta($post_id, 'featured', true);
        $author_id = $postData->post_author;
        $author_name = get_the_author_meta("display_name", $author_id);
        $avatar_url = get_avatar_url($author_id, ["size" => 40]);

        $author =  ["name" => $author_name, "id" => $author_id, "avatar" => $avatar_url];



        $voteCount = get_post_meta($post_id, 'voteCount', true);
        // $loveCount = get_post_meta($post_id, 'loveCount', true);
        // $viewCount = get_post_meta($post_id, 'viewCount', true);




        $categories = get_the_terms($post_id, 'ticket_cat');
        $categories = $categories ? $categories : [];

        $tags = get_the_terms($post_id, 'ticket_tag');
        $tags = $tags ? $tags : [];



        $categoriesData = [];
        foreach ($categories as $index => $category) {

            $categoriesData[$index] = [
                "term_id" => $category->term_id,
                "name" => $category->name,
                "slug" => $category->slug,
            ];
        }


        $tagsData = [];
        foreach ($tags as $index => $tag) {

            // $tagsData[$index] = $tag->name;

            $tagsData[$index] = [
                "term_id" => $tag->term_id,
                "name" => $tag->name,
                "slug" => $tag->slug,
            ];
        }
        $promptData = [
            "id" => $postData->ID,
            "title" => $postData->post_title,
            "content" =>  $postData->post_content,
            "post_status" => $postData->post_status,
            "excerpt" => $postData->post_excerpt,
            "post_thumbnail" => ['id' => $post_thumbnail_id, 'src' => $post_thumbnail_url],
            "post_thumbnail_url" => $post_thumbnail_url,
            "featured" => $featured,
            "priority" => get_post_meta($post_id, 'priority', true),
            "markedAs" => get_post_meta($post_id, 'markedAs', true),
            "author" => $author,

            "categories" => $categoriesData,
            "tags" => $tagsData,

            "downloadCount" => !empty($downloadCount) ? $downloadCount : 0,
            "voteCount" => !empty($voteCount) ? (int)$voteCount : 0,
            // "has_voted" => !empty($get_vote->vote_type) ? $get_vote->vote_type : 0,
            "loveCount" => !empty($loveCount) ? (int)$loveCount : 0,
            "viewCount" => !empty($viewCount) ? (int)$viewCount : 0,
            // "loved" => $loved,
        ];



        $response['prompt'] = $promptData;


        die(wp_json_encode($response));
    }

    /**
     * The user behind this request's token.
     *
     * @param WP_REST_Request $request Post data.
     * @return WP_User|WP_Error
     */
    private function get_ticket_user($request)
    {
        $token = $request->get_header('Authorization');

        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            $decoded_token = JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }

        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;
        $user = $user_id ? get_user_by('id', $user_id) : false;

        if (!$user) {
            return new WP_Error('invalid_token', 'User not found for this token', array('status' => 401));
        }

        return $user;
    }

    /**
     * Whether this user is staff who may manage tickets.
     *
     * @param WP_User $user The user.
     * @return bool
     */
    private function is_ticket_staff($user)
    {
        $staff_roles = ['administrator', 'support_agent', 'shop_manager', 'store_manager'];

        return (bool) array_intersect($staff_roles, (array) $user->roles);
    }

    /**
     * The logged-in user, if they are staff who may manage tickets.
     *
     * @param WP_REST_Request $request Post data.
     * @return WP_User|WP_Error
     */
    private function get_ticket_staff_user($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        if (!$this->is_ticket_staff($user)) {
            return new WP_Error('forbidden', 'You are not allowed to manage tickets', array('status' => 403));
        }

        return $user;
    }

    /**
     * Update a ticket's status and priority.
     *
     * Staff only, and only the two fields below: the values are fixed lists,
     * and an id that is not a ticket is refused rather than given ticket meta.
     *
     * @param WP_REST_Request $request Post data.
     */
    public function update_ticket($request)
    {
        $user = $this->get_ticket_staff_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        $post_id = isset($request['id']) ? absint($request['id']) : 0;
        $post = $post_id ? get_post($post_id) : null;

        if (!$post || $post->post_type !== 'ticket') {
            return new WP_Error('ticket_not_found', 'Ticket not found', array('status' => 404));
        }

        $markedAs = $request->get_param('markedAs');
        $priority = $request->get_param('priority');

        if ($markedAs === null && $priority === null) {
            return new WP_Error('nothing_to_update', 'Nothing to update', array('status' => 400));
        }

        if ($markedAs !== null && !in_array($markedAs, ['open', 'processing', 'closed'], true)) {
            return new WP_Error('invalid_status', 'Invalid ticket status', array('status' => 400));
        }

        if ($priority !== null && !in_array((string) $priority, ['1', '10', '90'], true)) {
            return new WP_Error('invalid_priority', 'Invalid ticket priority', array('status' => 400));
        }

        if ($markedAs !== null) {
            update_post_meta($post_id, 'markedAs', $markedAs);
        }

        if ($priority !== null) {
            update_post_meta($post_id, 'priority', (string) $priority);
        }

        $response = [
            'success' => true,
            'markedAs' => get_post_meta($post_id, 'markedAs', true),
            'priority' => get_post_meta($post_id, 'priority', true),
        ];

        die(wp_json_encode($response));
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_tickets($request)
    {
        $user = $this->get_ticket_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        /* Staff list every ticket; the query below limits anyone else to their own. */
        $user_id = $user->ID;
        $isAdmin = $this->is_ticket_staff($user);

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 12;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";


        $query_args = [];
        $meta_query = [];
        $tax_query = [];


        if (!empty($category)) {
            $tax_query[] = array(
                'taxonomy' => 'ticket_cat',
                'field'    => 'term_id',
                'terms'    => [$category],
                'operator'    => 'IN',
            );
        }


        $query_args['post_type'] = 'ticket';
        $query_args['post_status'] = 'any';

        if (!$isAdmin) {
            $query_args['author'] = $user_id;
        }



        if (!empty($keyword)) {
            $query_args['s'] = $keyword;
        }


        if (!empty($per_page)) {
            $query_args['posts_per_page'] = $per_page;
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
                $postAuthorID = isset($postData->post_author) ? $postData->post_author : null;

                $markedAs = get_post_meta($post_id, 'markedAs', true);
                $priority = get_post_meta($post_id, 'priority', true);
                $featured = get_post_meta($post_id, 'featured', true);
                $author_name = isset($postAuthorID) ? get_the_author_meta("display_name", $postAuthorID) : null;
                $avatar_url = get_avatar_url($postAuthorID, ["size" => 40]);

                $author_id = get_the_author_meta("ID", $postAuthorID);
                $author =  ["name" => $author_name, "id" => $author_id, "avatar" => $avatar_url];


                $voteCount = get_post_meta($post_id, 'voteCount', true);
                // $viewCount = get_post_meta($post_id, 'viewCount', true);

                $categories = get_the_terms($post_id, 'ticket_cat');
                $categories = $categories ? $categories : [];

                $tags = get_the_terms($post_id, 'ticket_tag');
                $tags = $tags ? $tags : [];


                $categoriesData = [];
                foreach ($categories as $index => $category) {

                    $categoriesData[$index] = [
                        "term_id" => $category->term_id,
                        "name" => $category->name
                    ];
                }


                $tagsData = [];
                foreach ($tags as $index => $tag) {

                    $tagsData[$index] = [
                        "term_id" => $tag->term_id,
                        "name" => $tag->name
                    ];
                }
                $prams = [];
                $prams['object_id'] = $post_id;
                $prams['userid'] = $user_id;

                // $ComboStoreViews = new ComboStoreViews();
                // $viewCount = $ComboStoreViews->get_object_view_count($prams);

                // $ComboStoreLove = new ComboStoreLove();
                // $loveCount = $ComboStoreLove->total_loved($prams);
                // $loved = $ComboStoreLove->has_loved($prams);




                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "content" => isset($postData->post_content) ? $postData->post_content : null,
                    "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "featured" => $featured,
                    "priority" => $priority,
                    "markedAs" => $markedAs,
                    "author" => $author,

                    "categories" => $categoriesData,
                    "tags" => $tagsData,

                    "voteCount" => !empty($voteCount) ? $voteCount : 0,
                    "viewCount" => !empty($viewCount) ? $viewCount : 0,
                    "loveCount" => !empty($loveCount) ? $loveCount : 0,
                    // "loved" => $loved,
                ];

            endwhile;
            wp_reset_query();

        else:


        endif;

        $total = $wp_query->found_posts;

        $max_pages = ceil($total / $per_page);
        $response['total'] = $total;
        $response['max_pages'] = $max_pages;
        $response['posts'] = $entries;


        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_tickets($request)
    {
        $user = $this->get_ticket_staff_user($request);

        if (is_wp_error($user)) {
            return $user;
        }

        $ids = isset($request['ids']) && is_array($request['ids']) ? $request['ids'] : [];

        $deleted = [];

        /* Only tickets: this endpoint must not be a way to delete any other post. */
        foreach ($ids as $id) {
            $post_id = absint($id);
            $post = $post_id ? get_post($post_id) : null;

            if ($post && $post->post_type === 'ticket' && wp_delete_post($post_id, false)) {
                $deleted[] = $post_id;
            }
        }

        $response = [];
        $response['success'] = true;
        $response['deleted'] = $deleted;

        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_page($request)
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


        //Get user by ID
        $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? ($request['id']) : null;


        $response = [];



        if (!empty($id)) {

            wp_delete_post($id, false);
        }

        $response['success'] = true;




        die(wp_json_encode($response));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function duplicate_page($request)
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


        //Get user by ID
        $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $id     = isset($request['id']) ? ($request['id']) : null;


        $response = [];

        $post = get_post($id);

        if ($post) {

            $new_post = array(
                'post_title'   => $post->post_title . ' (Copy)',
                'post_content' => $post->post_content,
                'post_status'  => 'draft',
                'post_type'    => $post->post_type,
                'post_author'  => get_current_user_id(),
                'post_excerpt' => $post->post_excerpt
            );

            $new_post_id = wp_insert_post($new_post);


            // Copy post meta
            $post_meta = get_post_meta($id);
            foreach ($post_meta as $key => $values) {
                foreach ($values as $value) {
                    add_post_meta($new_post_id, $key, maybe_unserialize($value));
                }
            }

            $response['success'] = true;
        }





        die(wp_json_encode($response));
    }









    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function update_page($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);


        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);



        $id     = isset($request['id']) ? ($request['id']) : null;
        $blocks     = isset($request['blocks']) ? ($request['blocks']) : null;
        $css     = isset($request['css']) ? ($request['css']) : null;

        $post_content = wp_json_encode($blocks, JSON_UNESCAPED_UNICODE);

        $response = [];



        if (!empty($id)) {

            $my_post = array(
                'ID'           => $id,
                'post_content' => $post_content,
            );
            wp_update_post($my_post);

            update_post_meta($id, 'css', $css);

            /* The page's settings from the builder (header/footer toggles, SEO).
               They were sent but never stored, so they were lost on reload. Only
               known keys are kept, each sanitized for what it is. */
            if (isset($body_arr['meta']) && is_array($body_arr['meta'])) {
                update_post_meta($id, 'page_meta', $this->sanitize_page_meta($body_arr['meta']));
            }

            $response['success'] = true;
        } else {
            $response['success'] = false;
        }




        die(wp_json_encode($response));
    }



    /**
     * The builder's page settings, keeping only known keys: the header and
     * footer toggles, and `seo` - title, description, image (URL), canonical
     * (URL) and noindex - which the storefront's page metadata reads.
     *
     * @param array $meta Settings as the builder sent them.
     * @return array
     */
    private function sanitize_page_meta($meta)
    {
        $clean = [
            'disableHeader' => !empty($meta['disableHeader']),
            'disableFooter' => !empty($meta['disableFooter']),
        ];

        $seo = isset($meta['seo']) && is_array($meta['seo']) ? $meta['seo'] : [];
        $clean['seo'] = [
            'title'       => isset($seo['title']) ? sanitize_text_field($seo['title']) : '',
            'description' => isset($seo['description']) ? sanitize_textarea_field($seo['description']) : '',
            'image'       => isset($seo['image']) ? esc_url_raw($seo['image']) : '',
            'canonical'   => isset($seo['canonical']) ? esc_url_raw($seo['canonical']) : '',
            'noindex'     => !empty($seo['noindex']),
        ];

        return $clean;
    }


    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function delete_products($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        /*
            Any valid token used to be enough, so a customer could delete any
            post, product or coupon on the site. Only the roles whose dashboard
            screens call this (Products, Coupons, Reviews, Stocks, Posts,
            Comments) may use it.
        */
        $user = get_user_by('id', $user_id);
        $userRoles = ($user && isset($user->roles)) ? (array) $user->roles : [];

        if (!array_intersect(['administrator', 'store_manager'], $userRoles)) {
            return new WP_Error('forbidden', 'You are not allowed to delete these items', array('status' => 403));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $ids     = is_array($ids) ? array_filter(array_map('absint', $ids)) : [];


        $response = [];
        $deleted = 0;



        if (!empty($ids)) {
            foreach ($ids as $id) {
                if (wp_delete_post($id, false)) {
                    $deleted++;
                }
            }
        }

        $response['success'] = true;
        $response['deleted'] = $deleted;


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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_medias($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];



        if (!empty($ids)) {
            foreach ($ids as $id) {
                wp_delete_post($id, false);
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
     * @param WP_REST_Request $request Post data.
     */
    public function delete_users($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        /*
            Any valid token used to be enough, so a customer could permanently
            delete any account that was not an administrator. Now:
             - an administrator may delete anyone but administrators;
             - a store manager may delete customers (and plain subscribers) only;
             - nobody deletes their own account here;
             - everyone else is refused.
        */
        $requester = get_user_by('id', $user_id);
        $requester_roles = $requester ? (array) $requester->roles : [];
        $is_admin = in_array('administrator', $requester_roles, true);
        $is_store_manager = in_array('store_manager', $requester_roles, true);

        if (!$is_admin && !$is_store_manager) {
            return new WP_Error('forbidden', 'You are not allowed to delete users', array('status' => 403));
        }

        $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $ids     = is_array($ids) ? array_filter(array_map('absint', $ids)) : [];


        $response = [];
        $deleted = 0;
        $skipped = 0;



        if (!empty($ids)) {
            require_once(ABSPATH . 'wp-admin/includes/user.php');


            foreach ($ids as $id) {

                $user = get_user_by('id', $id);

                if (!$user || (int) $id === (int) $user_id) {
                    $skipped++;
                    continue;
                }

                $roles = (array) $user->roles;

                if (in_array('administrator', $roles, true)) {
                    $skipped++;
                    continue; // Skip deletion for administrators
                }

                /* A store manager's reach: accounts with no role but customer or subscriber. */
                if (!$is_admin && array_diff($roles, ['customer', 'subscriber'])) {
                    $skipped++;
                    continue;
                }

                if (wp_delete_user($id)) {
                    $deleted++;
                } else {
                    $skipped++;
                }
            }
        }

        $response['success'] = true;
        $response['deleted'] = $deleted;
        $response['skipped'] = $skipped;


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
     * @param WP_REST_Request $request Post data.
     */
    public function duplicate_product($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $user = get_user_by('id', $user_id);

        if (!$user) {
            return new WP_Error('user_not_found', 'User not found', array('status' => 404));
        }

        /* Any valid token used to be enough - a customer's too. Only the roles
           the Products menu is open to may. */
        if (!array_intersect(['administrator', 'store_manager'], (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to duplicate products', array('status' => 403));
        }

        $post_id     = isset($request['id']) ? absint($request['id']) : 0;

        /* It copied any post of any type - orders' notes, pages, other people's
           private posts. This screen only duplicates products. */
        if (!$post_id || get_post_type($post_id) !== 'cmb_product') {
            die(wp_json_encode(array('success' => false, 'message' => 'Product not found.')));
        }


        $response = [];

        if (!empty($post_id)) {


            $post = get_post($post_id);

            if (isset($post) && $post != null) {

                /*
                * new post data array
                */
                $args = array(
                    'comment_status' => $post->comment_status,
                    'ping_status'    => $post->ping_status,
                    'post_author'    => $user_id,
                    'post_content'   => $post->post_content,
                    'post_excerpt'   => $post->post_excerpt,
                    /* Left empty so WordPress gives the copy its own slug. */
                    'post_name'      => '',
                    'post_parent'    => $post->post_parent,
                    'post_password'  => $post->post_password,
                    'post_status'    => 'draft',
                    'post_title'     => $post->post_title . ' - Copy of #' . $post_id,
                    'post_type'      => $post->post_type,
                    'to_ping'        => $post->to_ping,
                    'menu_order'     => $post->menu_order
                );

                /*
                * insert the post by wp_insert_post() function
                */
                $new_post_id = wp_insert_post($args);

                if (!$new_post_id || is_wp_error($new_post_id)) {
                    die(wp_json_encode(array('success' => false, 'message' => 'The copy could not be created.')));
                }

                /*
                * get all current post terms ad set them to the new post draft
                */
                $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
                foreach ($taxonomies as $taxonomy) {
                    $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                }

                /*
                * duplicate all post meta just in two SQL queries
                */
                // Copy post metadata
                $data = get_post_custom($post_id);
                foreach ($data as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($new_post_id, $key, maybe_unserialize($value)); // it is important to unserialize data to avoid conflicts.
                    }
                }
            }
        }



        /*
           Prices, stock, SKU and variations live in cstore_products and
           cstore_products_variations, not post meta, so the copy used to have
           none of them. Copy them - but not the stock (the units are not
           doubled), the SKU (it has to stay unique) or the ratings.
        */
        if (!empty($new_post_id)) {
            global $wpdb;
            $table_products   = $wpdb->prefix . 'cstore_products';
            $table_variations = $wpdb->prefix . 'cstore_products_variations';

            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_products WHERE product_id = %d LIMIT 1", $post_id), ARRAY_A);

            if ($row) {
                unset($row['id'], $row['created_at'], $row['updated_at']);
                $row['product_id']  = (int) $new_post_id;
                $row['sku']         = '';
                $row['stock_count'] = 0;
                $row['stock_status'] = $row['stock_status'] === 'instock' ? 'outofstock' : $row['stock_status'];
                $row['total_rate']  = 0;
                $row['avg_rating']  = 0;
                $wpdb->insert($table_products, $row);
            }

            $variations = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_variations WHERE product_id = %d", $post_id), ARRAY_A);

            foreach ((array) $variations as $variation) {
                unset($variation['id']);
                $variation['product_id']  = (int) $new_post_id;
                $variation['sku']         = '';
                $variation['stock_count'] = 0;
                $variation['stock_status'] = $variation['stock_status'] === 'instock' ? 'outofstock' : $variation['stock_status'];
                $wpdb->insert($table_variations, $variation);
            }

            $response['id'] = (int) $new_post_id;
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
     * @param WP_REST_Request $request Post data.
     */
    public function get_post_type_objects($request)
    {
        global $wp_post_types;
        $postTypes = [];
        $post_types_all = get_post_types('', 'names');
        foreach ($post_types_all as $post_type) {
            $obj = $wp_post_types[$post_type];
            $postTypes[] = $post_type;
        }
        $post_types =  (!empty($request['postTypes'])) ? $request['postTypes'] : $postTypes;
        $search = isset($request['search']) ? $request['search'] : '';
        $taxonomies = get_object_taxonomies($post_types);
        $terms = [];
        $taxonomiesArr = [];
        foreach ($taxonomies as $taxonomy) {
            $taxDetails = get_taxonomy($taxonomy);
            $taxonomiesArr[] = ['label' => $taxDetails->label, 'value' => $taxonomy];
        }
        die(wp_json_encode($taxonomiesArr));
    }


    /**
     * Return terms for taxonomy.
     *
     * @since 1.0.0
     *
     * @param WP_REST_Request $tax_data The tax data.
     */
    public function get_post_types($request)
    {

        $args = array(
            'public'   => true,
        );

        global $wp_post_types;
        $post_types = [];
        $post_types_all = get_post_types($args, 'names');



        foreach ($post_types_all as $post_type) {


            $obj = $wp_post_types[$post_type];
            $post_types[$post_type] = $obj->labels->singular_name;
        }
        die(wp_json_encode($post_types));
    }

    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function duplicate_media($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);



        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }


        $post_id     = isset($request['id']) ? ($request['id']) : [];


        $response = [];

        if (!empty($post_id)) {


            $post = get_post($post_id);

            if (isset($post) && $post != null) {

                /*
                * new post data array
                */
                $args = array(
                    'comment_status' => $post->comment_status,
                    'ping_status'    => $post->ping_status,
                    'post_author'    => $user_id,
                    'post_content'   => $post->post_content,
                    'post_excerpt'   => $post->post_excerpt,
                    'post_name'      => $post->post_name,
                    'post_parent'    => $post->post_parent,
                    'post_password'  => $post->post_password,
                    'post_status'    => 'draft',
                    'post_title'     => $post->post_title . ' - Copy of #' . $post_id,
                    'post_type'      => $post->post_type,
                    'to_ping'        => $post->to_ping,
                    'menu_order'     => $post->menu_order
                );

                /*
                * insert the post by wp_insert_post() function
                */
                $new_post_id = wp_insert_post($args);

                /*
                * get all current post terms ad set them to the new post draft
                */
                $taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
                foreach ($taxonomies as $taxonomy) {
                    $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
                    wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
                }

                /*
                * duplicate all post meta just in two SQL queries
                */
                // Copy post metadata
                $data = get_post_custom($post_id);
                foreach ($data as $key => $values) {
                    foreach ($values as $value) {
                        add_post_meta($new_post_id, $key, maybe_unserialize($value)); // it is important to unserialize data to avoid conflicts.
                    }
                }
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
     * @param WP_REST_Request $request Post data.
     */
    public function update_product_bulk($request)
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


        //Get user by ID
        // $user = get_user_by('id', $decoded_token->sub);


        $user_id = $decoded_token->sub ?? $decoded_token->user_id ?? $decoded_token->data->user->id ?? null;

        if (!$user_id) {
            return new WP_Error('invalid_token', 'User ID not found in token', array('status' => 401));
        }

        $body = $request->get_body();

        // $body_arr = json_decode($body);
        $body_arr = json_decode($body, true);


        // $ids     = isset($request['ids']) ? ($request['ids']) : [];
        $ids = $request->get_param('ids');
        $post_title = $request->get_param('title');
        $post_content = $request->get_param('post_content');
        $post_excerpt = $request->get_param('post_excerpt');
        $post_status = $request->get_param('postStatus');
        $comment_status = $request->get_param('comment_status');
        $post_parent = $request->get_param('post_parent');
        $menu_order = $request->get_param('menu_order');
        $categories = $request->get_param('categories');
        $visibility = $request->get_param('visibility');
        $tags = $request->get_param('tags');
        $brands = $request->get_param('brands');


        $tags = $tags ? $tags : [];
        $brands = $brands ? $brands : [];
        $categories = $categories ? $categories : [];

        $featured = $request->get_param('featured');


        $categoryIds = [];
        foreach ($categories as $category) {
            $categoryIds[] = $category['term_id'];
        }

        $visibilityIds = [];
        foreach ($visibility as $visibility) {
            $visibilityIds[] = $visibility['term_id'];
        }




        $tagIds = [];
        foreach ($tags as $tag) {
            $tagIds[] = $tag['name'];
        }

        $brandIds = [];
        foreach ($brands as $brand) {
            $brandIds[] = $brand['term_id'];
        }


        $response = [];


        if (!empty($ids)) {

            foreach ($ids as $post_id) {
                //wp_delete_post($id, false);

                $postData = [];
                $postData['ID'] = $post_id;

                if (!empty($post_title)) {
                    $postData['post_title'] = $post_title;
                }
                if (!empty($post_content)) {
                    $postData['post_content'] = $post_content;
                }
                if (!empty($post_excerpt)) {
                    $postData['post_excerpt'] = $post_excerpt;
                }
                if (!empty($post_status)) {
                    $postData['post_status'] = $post_status;
                }
                if (!empty($comment_status)) {
                    $postData['comment_status'] = $comment_status;
                }
                if (!empty($post_status)) {
                    $postData['post_status'] = $post_status;
                }
                if (!empty($post_parent)) {
                    $postData['post_parent'] = $post_parent;
                }

                if (!empty($menu_order)) {
                    $postData['menu_order'] = $menu_order;
                }



                wp_update_post($postData);

                if (!empty($categoryIds)) {
                    wp_set_post_terms($post_id, $categoryIds, 'product_cat');
                }

                if (!empty($tagIds)) {
                    wp_set_post_terms($post_id, $tagIds, 'product_tag');
                }

                if (!empty($brandIds)) {
                    wp_set_post_terms($post_id, $brandIds, 'product_brand');
                }


                if (!empty($visibilityIds)) {
                    wp_set_post_terms($post_id, $visibilityIds, 'product_visibility');
                }


                $body_arr['id'] = $post_id;

                $ComboStoreProduct = new ComboStoreProduct();


                $ComboStoreProduct->update_product($body_arr);




                if (!empty($body_arr['featured'])) {
                    update_post_meta($post_id, 'featured', $featured);
                }
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
     * @param WP_REST_Request $request Post data.
     */
    public function email_export($request)
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


        $task_id     = isset($request['task_id']) ? ($request['task_id']) : [];
        $queryPrams     = isset($request['queryPrams']) ? ($request['queryPrams']) : [];



        $ComboStoreExport = new ComboStoreExport();

        $url = $ComboStoreExport->export_combo_store_data($task_id, $queryPrams);

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
        $datetime = get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s');

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


    function convert_tax_query($data)
    {




        $tax_query = [
            'relation' => 'AND',
        ];

        foreach ($data as $group) {

            $group_query = [
                'relation' => $group['relation'] ?? 'OR',
            ];

            foreach ($group['collections'] ?? [] as $collection) {

                // Skip empty conditions
                if (
                    empty($collection['taxonomy']) ||
                    empty($collection['field']) ||
                    empty($collection['terms'])
                ) {
                    continue;
                }

                $condition = [
                    'taxonomy' => $collection['taxonomy'],
                    'field'    => $collection['field'],
                    'terms'    => $collection['terms'],
                ];

                // Add operator if available
                if (!empty($collection['operator'])) {
                    $condition['operator'] = $collection['operator'];
                }

                $group_query[] = $condition;
            }

            // Only add groups that have conditions
            if (count($group_query) > 1) {
                $tax_query[] = $group_query;
            }
        }

        return $tax_query;
    }
}


new ComboStoreRest();
