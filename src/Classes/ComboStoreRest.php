<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use WP_REST_Request;
use WP_User_Query;
use Exception;
use WC_Order_Query;
use WC_Order;
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
use ComboStore\Classes\RedxCourier;
use ComboStore\Classes\ComboStorePages;


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
            '/update_settings',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'update_settings'),
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
            '/get_ticket',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_ticket'),
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
            '/delete_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'delete_products'),
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
            '/get_low_stock_products',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_low_stock_products'),
                'permission_callback' => '__return_true',
            )
        );




        register_rest_route(
            'combo-store/v2',
            '/get_product_table',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_product_table'),
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
            '/get_expenses',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_expenses'),
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
            '/get_order',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_order'),
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
            '/get_customer',
            array(
                'methods'  => 'POST',
                'callback' => array($this, 'get_customer'),
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
                    'post_type'      => 'product',
                    'post_status'      => 'draft',
                ];
                // wp_update_post($postData);
                $post_id = wp_insert_post($postData);

                if (!empty($price2)) {
                    update_post_meta($post_id, 'regularPrice', $price2);
                    update_post_meta($post_id, 'salePrice', $price);
                } else {
                    update_post_meta($post_id, 'regularPrice', $price);
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
                    'post_type'      => 'product',
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
                $response['errors']['prompt_missing'] = __("Prompt titles Missing", 'combo-store-server');
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

                    update_user_meta($user_id, 'address_1', $address);
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
            $response['errors']['prompt_missing'] = __("Prompt titles Missing", 'combo-store-server');
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


        $object     = isset($request['object']) ? sanitize_email($request['object']) : '';
        $page     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';

        $isAdmin = true;
        $user_id = 1;
        global $wpdb;

        $prefix = $wpdb->prefix;

        $table_subscriptions = $prefix . 'cstore_subscriptions';

        $response = [];
        $offset = ($page - 1) * $per_page;

        $last_day = gmdate("Y-m-d");
        $first_date = gmdate("Y-m-d", strtotime("7 days ago"));

        //$entries = $wpdb->get_results("SELECT * FROM $table_subscriptions WHERE datetime BETWEEN '$first_date' AND '$last_day' ORDER BY id $order limit $offset, $per_page");

        if ($isAdmin) {

            $entries = $wpdb->get_results("SELECT * FROM $table_subscriptions ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_subscriptions");
        } else {

            $entries = $wpdb->get_results("SELECT * FROM $table_subscriptions WHERE userid='$user_id' ORDER BY id $order limit $offset, $per_page");
            $total = $wpdb->get_var("SELECT COUNT(`id`) FROM $table_subscriptions WHERE userid='$user_id'");
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
        $isAdmin = in_array('administrator', $userRoles) ? true : false;



        $body = $request->get_body();
        $body_arr = json_decode($body, true);

        $body_arr['isAdmin'] = $isAdmin;
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

        $courier     = isset($request['courier']) ? sanitize_text_field($request['courier']) : '';
        $order_id     = isset($request['order_id']) ? sanitize_text_field($request['order_id']) : "";



        $app_response = apply_filters('cstore_send_to_courier_' . $courier, ["order_id" => $order_id]);

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
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 20;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";


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
            "post_status" => isset($postData->post_status) ? $postData->post_status : null,
            "post_type" => isset($postData->post_type) ? $postData->post_type : null,
            "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,
            "post_thumbnail" => [
                'id' => $post_thumbnail_id,
                'src' => $post_thumbnail_url
            ],
            "post_thumbnail_url" => $post_thumbnail_url,
            "featured" => $featured,
            "author" => $author,

            "categories" => $categoriesData,
            // "tags" => $tagsData,

            "gallery" => $gallery,
            "addons" => $addons,
            "downloads" => $downloads,
            "downloadCount" => !empty($downloadCount) ? $downloadCount : 0,
            "voteCount" => !empty($voteCount) ? (int)$voteCount : 0,
            "loveCount" => !empty($loveCount) ? (int)$loveCount : 0,
            "viewCount" => !empty($viewCount) ? (int)$viewCount : 0,
            // "loved" => $loved,
            "faq" => $faq,
            "relatedPrompts" => $relatedPrompts,
            "variations" => $variations,
        ];



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
        $post_id     = isset($request['id']) ? absint($request['id']) : 0;

        $promptData = [];

        $postData = get_post($post_id);

        $content = isset($postData->post_content) ? $postData->post_content : null;



        $promptData = [
            "id" => isset($postData->ID) ? $postData->ID : null,
            "title" => isset($postData->post_title) ? $postData->post_title : null,
            "content" => !empty($content) ? $content : null,
            "post_status" => isset($postData->post_status) ? $postData->post_status : null,
            "post_type" => isset($postData->post_type) ? $postData->post_type : null,
            "excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : null,

        ];



        $response['post'] = $promptData;


        die(wp_json_encode($response));
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


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 12;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "rand";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? ($request['categories']) : [];


        $query_args = [];
        $meta_query = [];
        $tax_query = [];


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


        $query_args['post_type'] = 'post';
        $query_args['post_status'] = 'publish';



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


                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
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


        $taxonomy     = isset($request['taxonomy']) ? sanitize_text_field($request['taxonomy']) : '';
        $per_page     = isset($request['per_page']) ? sanitize_text_field($request['per_page']) : 10;
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : 'DESC';
        $parent     = isset($request['parent']) ? (int) $request['parent'] : 0;
        $hierarchical     = isset($request['hierarchical']) ? (bool) $request['hierarchical'] : false;


        $tax_obj = get_taxonomy($taxonomy);


        $termsQuery = [];
        $termsQuery['taxonomy'] = $taxonomy;
        $termsQuery['hide_empty'] = false;
        $termsQuery['number'] = $per_page;
        $termsQuery['order'] = $order;

        if (!empty($parent)) {
            $termsQuery['parent'] = $parent;
        }



        $terms = get_terms($termsQuery);
        $terms = array_values($terms);



        // foreach ($terms as $term) {

        //     $term_id = isset($term->term_id) ? $term->term_id : "";
        //     //$thumbnail = get_term_meta($term_id, "thumbnail", true);

        //     //$term->thumbnail = $thumbnail;
        // }

        if ($tax_obj && $hierarchical) {
            $category_tree = build_category_tree($terms);
            $response['terms'] = $category_tree;
        } else {
            $response['terms'] = $terms;
        }










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
                'post_type'   => 'product',
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

            $parentTerm = get_term($term->parent, $taxonomy);

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
        $stockStatus     = isset($request['stockStatus']) ? ($request['stockStatus']) : null;

        $is_admin     = isset($request['is_admin']) ? ($request['is_admin']) : false;



        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'product';
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

        if ($stockStatus) {

            $meta_query[] = array(
                'key' => 'stockStatus',
                'value'    => $stockStatus,
                'operator'    => '=',
            );
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
                $sku = get_post_meta($post_id, 'sku', true);
                $bulkPrices = get_post_meta($post_id, 'bulkPrices', true);
                $variablePrices = get_post_meta($post_id, 'variablePrices', true);
                $priceType = get_post_meta($post_id, 'priceType', true);
                $salePrice = get_post_meta($post_id, 'salePrice', true);
                $regularPrice = get_post_meta($post_id, 'regularPrice', true);
                $tradePrice = get_post_meta($post_id, 'tradePrice', true);




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
                // $new_entry['salePrice'] = $salePrice;
                // $new_entry['regularPrice'] = $regularPrice;
                // $new_entry['stockCount'] = $stock_quantity;
                // $new_entry['stockStatus'] = $stock_status;
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
                    "tradePrice" => $tradePrice,
                    "salePrice" => $salePrice,
                    "regularPrice" => $regularPrice,
                    "price" => !empty($salePrice) ? $salePrice : $regularPrice,
                    "priceType" => $priceType,
                    "bulkPrices" => $bulkPrices,
                    "variablePrices" => $variablePrices,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brandsData,
                    "visibility" => $visibilityData,
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



        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'product';
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
                'key'     => 'stockStatus',
                'value'   => 'instock',
                'compare' => '='
            ),
            array(
                'key'     => 'stockCount',
                'value'   => 5,
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
                $bulkPrices = get_post_meta($post_id, 'bulkPrices', true);
                $variablePrices = get_post_meta($post_id, 'variablePrices', true);
                $priceType = get_post_meta($post_id, 'priceType', true);
                $salePrice = get_post_meta($post_id, 'salePrice', true);
                $regularPrice = get_post_meta($post_id, 'regularPrice', true);
                $tradePrice = get_post_meta($post_id, 'tradePrice', true);




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
                // $new_entry['salePrice'] = $salePrice;
                // $new_entry['regularPrice'] = $regularPrice;
                // $new_entry['stockCount'] = $stock_quantity;
                // $new_entry['stockStatus'] = $stock_status;
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
                    "tradePrice" => $tradePrice,
                    "salePrice" => $salePrice,
                    "regularPrice" => $regularPrice,
                    "price" => !empty($salePrice) ? $salePrice : $regularPrice,
                    "priceType" => $priceType,
                    "bulkPrices" => $bulkPrices,
                    "variablePrices" => $variablePrices,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
                    "brands" => $brandsData,
                    "visibility" => $visibilityData,
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
    public function get_product_table($request)
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


        $paged     = isset($request['paged']) ? absint($request['paged']) : 1;
        $per_page     = isset($request['per_page']) ? absint($request['per_page']) : 100;
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $keyword     = isset($request['keyword']) ? sanitize_text_field($request['keyword']) : "";
        $category     = isset($request['category']) ? sanitize_text_field($request['category']) : "";
        $categories     = isset($request['categories']) ? ($request['categories']) : "";
        $brand     = isset($request['brand']) ? sanitize_text_field($request['brand']) : "";
        $tag     = isset($request['tag']) ? sanitize_text_field($request['tag']) : "";
        $post__in     = isset($request['post__in']) ? ($request['post__in']) : [];
        $post__not_in     = isset($request['post__not_in']) ? ($request['post__not_in']) : [];


        $query_args = [];
        $meta_query = [];
        $tax_query = [];

        $query_args['post_type'] = 'product';
        $query_args['post_status'] = 'publish';



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
                $salePrice = get_post_meta($post_id, 'salePrice', true);
                $regularPrice = get_post_meta($post_id, 'regularPrice', true);




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

                $post_thumbnail = [
                    "id" => "",
                    "title" => "",
                    "src" => $post_thumbnail_url,
                ];

                $categories = [];
                $tags = [];
                $brands = [];
                $brands = [];

                $entries[] = [
                    "id" => isset($postData->ID) ? $postData->ID : null,
                    "title" => isset($postData->post_title) ? $postData->post_title : null,
                    "post_excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : "",
                    "description" => isset($postData->post_content) ? $postData->post_content : "",

                    "slug" => isset($postData->post_name) ? $postData->post_name : null,
                    "status" => isset($postData->post_status) ? $postData->post_status : null,
                    "sku" => $sku,
                    "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
                    "post_thumbnail_url" => $post_thumbnail_url,
                    "featured" => $featured,
                    "salePrice" => $salePrice,
                    "regularPrice" => $regularPrice,
                    "price" => !empty($salePrice) ? $salePrice : $regularPrice,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
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
                    "google_product_category" => "Baby & Toddler > Diapering > Diapers",
                    "fb_product_category" => "Baby Products > Diapering & Potty Training",
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
                $salePrice = get_post_meta($post_id, 'salePrice', true);
                $sku = get_post_meta($post_id, 'sku', true);
                $regularPrice = get_post_meta($post_id, 'regularPrice', true);
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
                    "salePrice" => $salePrice,
                    "regularPrice" => $regularPrice,
                    "categories" => $categoriesData,
                    "tags" => $tagsData,
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
                $response['message'] = __("Coupon doesn't exist.", 'combo-store-server');
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
            $response['message'] = __("Oppps sorry! Coupon is waiting to publish.", 'combo-store-server');
            die(wp_json_encode($response));
        } else if ($today >= $end) {
            $status = "expired";

            $response['errors'] = true;
            $response['message'] = __("Oppps sorry! Coupon has expired.", 'combo-store-server');
            die(wp_json_encode($response));
        } else if ($today >= $start && $today <= $end) {
            $status = "active";
        } else {
            $status = "error";

            $response['errors'] = true;
            $response['message'] = __("There is an error.", 'combo-store-server');
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

        // Get the date range (last 7 days)
        $days_ago = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = gmdate('Y-m-d 23:59:59');

        $query_args = [
            'start_date' => $days_ago,
            'end_date' => $now,
            'user_id' => $user_id,
        ];


        $ComboStoreStats = new ComboStoreStats();

        // $get_vote_range = $ComboStoreStats->get_vote_range($request);
        $get_orders_range = $ComboStoreStats->get_orders_range($request);
        $get_purchases_range = $ComboStoreStats->get_purchases_range($request);
        $get_refunds_range = $ComboStoreStats->get_refunds_range($request);

        $response['labels'] = $get_orders_range['labels'];
        // $response['datasets'][0] = $get_views_range['dataset'];;
        // $response['datasets'][1] = $get_loved_range['dataset'];;
        // $response['datasets'][2] = $get_vote_range['dataset'];;
        $response['datasets'][0] = $get_orders_range['dataset'];;
        $response['datasets'][1] = $get_purchases_range['dataset'];;
        $response['datasets'][2] = $get_refunds_range['dataset'];;

        $total_products = $ComboStoreStats->total_products($user_id);
        $total_customers = $ComboStoreStats->total_customers($user_id);
        $total_orders = $ComboStoreStats->total_orders($query_args);
        $total_subscriptions = $ComboStoreStats->total_subscriptions($query_args);
        $total_orders_amount = $ComboStoreStats->get_orders_total_amount($query_args);
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
        $userid     = isset($request['userid']) ? sanitize_text_field($request['userid']) : null;
        $product_id     = isset($request['product_id']) ? sanitize_text_field($request['product_id']) : null;
        $orderStatus     = isset($request['orderStatus']) ? sanitize_text_field($request['orderStatus']) : null;
        $paymentStatus     = isset($request['paymentStatus']) ? sanitize_text_field($request['paymentStatus']) : null;
        $paymentMethod     = isset($request['paymentMethod']) ? sanitize_text_field($request['paymentMethod']) : null;


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

        // Get the date range (last 7 days)
        $days_ago = gmdate('Y-m-d 00:00:00', strtotime("-$days days"));
        $now = gmdate('Y-m-d 23:59:59');

        $query_args = [
            'date_from' => $days_ago,
            'date_to' => $now,
            'userid' => $userid,
            'product_id' => $product_id,
            'orderStatus' => $orderStatus,
            'paymentStatus' => $paymentStatus,
            'paymentMethod' => $paymentMethod,
        ];


        $ComboStoreStats = new ComboStoreStats();

        $stats = $ComboStoreStats->get_orders_stats($query_args);
        // $get_purchases_range = $ComboStoreStats->get_purchases_range($request);
        // $get_refunds_range = $ComboStoreStats->get_refunds_range($request);

        // $response['labels'] = $get_orders_range['labels'];
        // $response['datasets'][0] = $get_orders_range['dataset'];;




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

        // $get_vote_range = $ComboStoreStats->get_vote_range($request);
        // $get_orders_range = $ComboStoreStats->get_orders_range($request);
        // $get_purchases_range = $ComboStoreStats->get_purchases_range($request);

        // $response['labels'] = $get_views_range['labels'];
        // $response['datasets'][0] = $get_views_range['dataset'];;
        // $response['datasets'][1] = $get_loved_range['dataset'];;
        // $response['datasets'][2] = $get_vote_range['dataset'];;
        // $response['datasets'][3] = $get_orders_range['dataset'];;
        // $response['datasets'][4] = $get_purchases_range['dataset'];;

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
        $postId = $request->get_param('postId');
        $parentId = $request->get_param('parentId');
        $rate = $request->get_param('rate');
        $response = [];
        if ($rate) {
            $has_rated_prams = [
                "userid" => $user_id,
                "postId" => $postId,
            ];


            $has_rated = combo_store_has_rated($has_rated_prams);

            if ($has_rated) {
                $response['errors']['already_rate'] = __("You already rated.", 'combo-store-server');

                $response['message'] = __("Thanks for trying, you already rated.", 'combo-store-server');
                die(wp_json_encode($response));
            }
        }




        if (empty($content)) {
            $response['errors']['content_missing'] = __("Comment Missing", 'combo-store-server');
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


        // if (isset($body_arr['menuOrder'])) {
        //     update_post_meta($post_id, 'menuOrder', $body_arr['menuOrder']);
        // }



        die(wp_json_encode($response));
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
            $response['errors']['content_missing'] = __("Comment Missing", 'combo-store-server');
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


        // if (isset($body_arr['menuOrder'])) {
        //     update_post_meta($post_id, 'menuOrder', $body_arr['menuOrder']);
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


        $body = $request->get_body();
        $body_arr = json_decode($body, true);


        $comment_id     = isset($request['comment_id']) ? absint($request['comment_id']) : 0;

        $comment_args = [];
        $meta_query = [];





        if (empty($comment_id)) {
            $response['success'] = false;
            die(wp_json_encode($response));
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

        $response['success'] = true;


        die(wp_json_encode($response));
    }
    /**
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_coupons($request)
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
            $post = get_page_by_path($post_slug, OBJECT, "product");
            $post_id = $post ? $post->ID : null;
        }


        $productData = [];

        $postData = get_post($post_id);


        $post_thumbnail_url = get_the_post_thumbnail_url($post_id);
        $featured = get_post_meta($post_id, 'featured', true);
        $priceType = get_post_meta($post_id, 'priceType', true);
        $salePrice = get_post_meta($post_id, 'salePrice', true);
        $regularPrice = get_post_meta($post_id, 'regularPrice', true);
        $bulkPrices = get_post_meta($post_id, 'bulkPrices', true);
        $variablePrices = get_post_meta($post_id, 'variablePrices', true);

        $pwywMinPrice = get_post_meta($post_id, 'pwywMinPrice', true);
        $pwywDefaultPrice = get_post_meta($post_id, 'pwywDefaultPrice', true);
        $bargainMinPrice = get_post_meta($post_id, 'bargainMinPrice', true);
        $bargainDefaultPrice = get_post_meta($post_id, 'bargainDefaultPrice', true);

        $tradePrice = get_post_meta($post_id, 'tradePrice', true);

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



        if (!empty($upsells)) {

            $upsellsX = [];

            foreach ($upsells as $upsell) {
                $id = isset($upsell['id']) ? $upsell['id'] : '';
                $title = isset($upsell['title']) ? $upsell['title'] : '';
                $upsell_thumbnail_url = get_the_post_thumbnail_url($id);

                $upsell_salePrice = get_post_meta($id, 'salePrice', true);
                $upsell_regularPrice = get_post_meta($id, 'regularPrice', true);
                $upsell_salePrice = get_post_meta($id, 'salePrice', true);


                $thumbnail = [
                    "id" => "",
                    "title" => "",
                    "src" => $upsell_thumbnail_url,
                ];

                $upsellsX[] = [
                    "id" => $id,
                    "title" => $title,
                    "price" => !empty($upsell_salePrice) ? $upsell_salePrice : $upsell_regularPrice,

                    "regularPrice" => $upsell_regularPrice,
                    "salePrice" => $upsell_salePrice,
                    "post_thumbnail" => $thumbnail,
                ];
            }
        }
        if (!empty($relatedProducts)) {

            $relatedProductsX = [];

            foreach ($relatedProducts as $rel_post) {
                $id = isset($rel_post['id']) ? $rel_post['id'] : '';
                $title = isset($rel_post['title']) ? $rel_post['title'] : '';
                $rel_post_thumbnail_url = get_the_post_thumbnail_url($id);

                $rel_post_salePrice = get_post_meta($id, 'salePrice', true);
                $rel_post_regularPrice = get_post_meta($id, 'regularPrice', true);
                $rel_post_salePrice = get_post_meta($id, 'salePrice', true);

                $rel_postData = get_post($id);


                $thumbnail = [
                    "id" => "",
                    "title" => "",
                    "src" => $rel_post_thumbnail_url,
                ];

                $relatedProductsX[] = [
                    "id" => $id,
                    "title" => $title,
                    "slug" => $rel_postData->post_name,
                    "price" => !empty($rel_post_salePrice) ? $rel_post_salePrice : $rel_post_regularPrice,

                    "regularPrice" => $rel_post_regularPrice,
                    "salePrice" => $rel_post_salePrice,
                    "post_thumbnail" => $thumbnail,
                ];
            }
        }


        $productData = [
            "id" => isset($postData->ID) ? $postData->ID : '',
            "title" => isset($postData->post_title) ? $postData->post_title : '',
            "post_content" => isset($postData->post_content) ? $postData->post_content : '',
            "post_content_html" => isset($post_content_html) ? $post_content_html : '',
            "post_excerpt" => isset($postData->post_excerpt) ? $postData->post_excerpt : "",
            "slug" => isset($postData->post_name) ? $postData->post_name : '',
            "postStatus" => isset($postData->post_status) ? $postData->post_status : '',
            "featured" => $featured,
            "sku" => $sku,
            "priceType" => $priceType,
            "salePrice" => $salePrice,
            "regularPrice" => $regularPrice,
            "bulkPrices" => $bulkPrices ? $bulkPrices : [],
            "variablePrices" => $variablePrices ? $variablePrices : [],
            "pwywMinPrice" => $pwywMinPrice,
            "pwywDefaultPrice" => $pwywDefaultPrice,

            "bargainMinPrice" => $bargainMinPrice,
            "bargainDefaultPrice" => $bargainDefaultPrice,

            "price" => !empty($salePrice) ? $salePrice : $regularPrice,
            "tradePrice" =>  $tradePrice,

            "stockStatus" => $stockStatus,
            "stockCount" => $stockCount,
            "menuOrder" => $menuOrder,

            "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
            "tags" => !empty($tagsData) ? $tagsData : [],
            "categories" => !empty($categoriesData) ? $categoriesData : [],
            "visibility" => !empty($visibilityData) ? $visibilityData : [],
            "brands" => !empty($brandsData) ? $brandsData : [],
            "addons" => !empty($addons) ? $addons : ["manageStock", "gallery", "dimensions", "categories", "tags"],
            "faq" => !empty($faq) ? $faq : [],
            "relatedProducts" => !empty($relatedProducts) ? $relatedProductsX : [],
            "crosssells" => !empty($crosssells) ? $crosssells : [],
            "upsells" => !empty($upsells) ? $upsellsX : [],
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
        $priceType = get_post_meta($post_id, 'priceType', true);
        $salePrice = get_post_meta($post_id, 'salePrice', true);
        $regularPrice = get_post_meta($post_id, 'regularPrice', true);
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
            "priceType" => $priceType,
            "salePrice" => $salePrice,
            "regularPrice" => $regularPrice,
            "stockStatus" => $stockStatus,
            "stockCount" => $stockCount,
            "menuOrder" => $menuOrder,

            "post_thumbnail" => !empty($post_thumbnail) ? $post_thumbnail : [],
            "tags" => !empty($tagsData) ? $tagsData : [],
            "categories" => !empty($categoriesData) ? $categoriesData : [],
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
        $tradePrice = $request->get_param('tradePrice');


        $tags = $tags ? $tags : [];
        $brands = $brands ? $brands : [];
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
            $brandIds[] = $brand['term_id'];
        }



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

        wp_set_post_terms($post_id, $categoryIds, 'product_cat');
        wp_set_post_terms($post_id, $tagIds, 'product_tag');
        wp_set_post_terms($post_id, $brandIds, 'product_brand');

        wp_set_post_terms($post_id, $visibilityIds, 'product_visibility');



        set_post_thumbnail($post_id, $post_thumbnail_id);

        // $ComboStoreProduct = new ComboStoreProduct();

        // $response = $ComboStoreProduct->update_product_data($body_arr);


        if (!empty($body_arr['sku'])) {
            update_post_meta($post_id, 'sku', $body_arr['sku']);
        }
        if (!empty($body_arr['menuOrder'])) {
            update_post_meta($post_id, 'menuOrder', $body_arr['menuOrder']);
        }
        if (!empty($body_arr['featured'])) {
            update_post_meta($post_id, 'featured', $featured);
        }
        if (empty($body_arr['featured'])) {
            delete_post_meta($post_id, 'featured', $featured);
        }



        if (!empty($body_arr['priceType'])) {
            update_post_meta($post_id, 'priceType', $body_arr['priceType']);
        }
        if (!empty($body_arr['salePrice'])) {
            update_post_meta($post_id, 'salePrice', $body_arr['salePrice']);
        }
        if (!empty($body_arr['regularPrice'])) {
            update_post_meta($post_id, 'regularPrice', $body_arr['regularPrice']);
        }


        if (!empty($body_arr['bulkPrices'])) {
            update_post_meta($post_id, 'bulkPrices', $body_arr['bulkPrices']);
        }
        if (!empty($body_arr['variablePrices'])) {
            update_post_meta($post_id, 'variablePrices', $body_arr['variablePrices']);
        }


        if (!empty($body_arr['pwywMinPrice'])) {
            update_post_meta($post_id, 'pwywMinPrice', $body_arr['pwywMinPrice']);
        }
        if (!empty($body_arr['pwywDefaultPrice'])) {
            update_post_meta($post_id, 'pwywDefaultPrice', $body_arr['pwywDefaultPrice']);
        }
        if (!empty($body_arr['bargainMinPrice'])) {
            update_post_meta($post_id, 'bargainMinPrice', $body_arr['bargainMinPrice']);
        }
        if (!empty($body_arr['bargainDefaultPrice'])) {
            update_post_meta($post_id, 'bargainDefaultPrice', $body_arr['bargainDefaultPrice']);
        }


        if (!empty($body_arr['tradePrice'])) {
            update_post_meta($post_id, 'tradePrice', $body_arr['tradePrice']);
        }



        if (!empty($body_arr['stockStatus'])) {
            update_post_meta($post_id, 'stockStatus', $body_arr['stockStatus']);
        }


        if (!empty($body_arr['stockCount'])) {
            update_post_meta($post_id, 'stockCount', $body_arr['stockCount']);
        } else {
            delete_post_meta($post_id, 'stockCount');
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
            $response['message'] = "Purchased Successful.";
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

        $body = $request->get_body();
        $body_arr = json_decode($body, true);


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
            $response['message'] = "Purchased Successful.";
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


        $user_id = 1;


        $response = [];

        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));
        $ComboStoreSubscriptions = new ComboStoreSubscriptions();


        $_response = $ComboStoreSubscriptions->update_subscription($body_arr);


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


        $response = $ComboStoreSubscriptions->get_subscription($id, $by);


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

            $order_item->title = $product_title;
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
        $address_1              = get_user_meta($user_id,  "address_1", true);
        $address_2              = get_user_meta($user_id,  "address_2", true);
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
                'address_1'  => $address_1,
                'address_2'  => $address_2,
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
        $address_1              = get_user_meta($user_id,  "address_1", true);
        $address_2              = get_user_meta($user_id,  "address_2", true);
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
                'address_1'  => $address_1,
                'address_2'  => $address_2,
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


        $api_key = "6ZRPQgVScjihVVdqpInz1Oyik6Gwlx2mpenMHCGTYnV9okKca7kFcohYFicf";
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
        $address_1              = get_user_meta($user_id,  "address_1", true);
        $address_2              = get_user_meta($user_id,  "address_2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);



        $response['name'] = $name;
        $response['first_name'] = $first_name;
        $response['last_name'] = $last_name;
        $response['email'] = $email;
        $response['phone'] = $phone;
        $response['address_1'] = $address_1;
        $response['address_2'] = $address_2;
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
        $address_1              = get_user_meta($user_id,  "address_1", true);
        $address_2              = get_user_meta($user_id,  "address_2", true);
        $zip_code              = get_user_meta($user_id,  "zip_code", true);
        $country              = get_user_meta($user_id,  "country", true);
        $city              = get_user_meta($user_id,  "city", true);
        $delivery_location              = get_user_meta($user_id,  "delivery_location", true);



        $response['name'] = $name;
        $response['first_name'] = $first_name;
        $response['last_name'] = $last_name;
        $response['email'] = $email;
        $response['phone'] = $phone;
        $response['address_1'] = $address_1;
        $response['address_2'] = $address_2;
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
        $address_1              = get_user_meta($id,  "address_1", true);
        $address_2              = get_user_meta($id,  "address_2", true);
        $zip_code              = get_user_meta($id,  "zip_code", true);
        $country              = get_user_meta($id,  "country", true);
        $city              = get_user_meta($id,  "city", true);
        $delivery_location              = get_user_meta($id,  "delivery_location", true);



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
            'address_1'  => $address_1,
            'address_2'  => $address_2,
            'zip_code'  => $zip_code,
            'country'  => $country,
            'city'  => $city,
            'delivery_location'  => $delivery_location,

        );



        die(wp_json_encode($response));
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



        $id     = isset($request['id']) ? sanitize_text_field($request['id']) : '';
        $userData     = isset($request['userData']) ? ($request['userData']) : [];
        $name     = isset($userData['name']) ? sanitize_text_field($userData['name']) : '';
        $first_name     = isset($userData['first_name']) ? sanitize_text_field($userData['first_name']) : '';
        $last_name     = isset($userData['last_name']) ? sanitize_text_field($userData['last_name']) : '';
        $email     = isset($userData['email']) ? sanitize_email($userData['email']) : '';
        $customer_category     = isset($userData['customer_category']) ? sanitize_text_field($userData['customer_category']) : '';
        $income     = isset($userData['income']) ? sanitize_text_field($userData['income']) : '';
        $profession     = isset($userData['profession']) ? sanitize_text_field($userData['profession']) : '';
        $has_orderd     = isset($userData['has_orderd']) ? sanitize_text_field($userData['has_orderd']) : '';


        $phone     = isset($userData['phone']) ? sanitize_text_field($userData['phone']) : '';
        $phones     = isset($userData['phones']) ? stripslashes_deep($userData['phones']) : [];
        $kids     = isset($userData['kids']) ? stripslashes_deep($userData['kids']) : [];
        $address_1     = isset($userData['address_1']) ? sanitize_text_field($userData['address_1']) : '';
        $address_2     = isset($userData['address_2']) ? sanitize_text_field($userData['address_2']) : '';
        $zip_code     = isset($userData['zip_code']) ? sanitize_text_field($userData['zip_code']) : '';
        $country     = isset($userData['country']) ? sanitize_text_field($userData['country']) : '';
        $city     = isset($userData['city']) ? sanitize_text_field($userData['city']) : '';
        $delivery_location     = isset($userData['delivery_location']) ? stripslashes_deep($userData['delivery_location']) : '';



        $userdata = array(
            'ID'          => $id,
            'first_name'  => $first_name,
            'last_name'   => $last_name,
            'display_name' => $name,
            'user_email' => $email,
        );

        // $userdata['ID'] = $id;
        // $userdata['first_name'] = $first_name;
        // $userdata['last_name'] = $last_name;
        // $userdata['display_name'] = $name;
        // $userdata['user_email'] = $email;


        $result = wp_update_user($userdata);


        if (is_wp_error($result)) {

            $response['error'] = true;
            $response['message'] = $result->get_error_message();
        } else {
            update_user_meta($id, 'first_name', $first_name);
            update_user_meta($id, 'last_name', $last_name);
            update_user_meta($id, 'last_name', $last_name);
            update_user_meta($id, 'phone', $phone);
            update_user_meta($id, 'address_1', $address_1);
            update_user_meta($id, 'address_2', $address_2);
            update_user_meta($id, 'zip_code', $zip_code);
            update_user_meta($id, 'country', $country);
            update_user_meta($id, 'city', $city);
            update_user_meta($id, 'delivery_location', $delivery_location);

            update_user_meta($id, 'phones', $phones);
            update_user_meta($id, 'kids', $kids);
            update_user_meta($id, 'customer_category', $customer_category);
            update_user_meta($id, 'income', $income);
            update_user_meta($id, 'profession', $profession);
            update_user_meta($id, 'has_orderd', $has_orderd);


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
        $search     = isset($request['search']) ? sanitize_text_field($request['search']) : "";
        $number     = isset($request['number']) ? sanitize_text_field($request['number']) : "";
        $orderby     = isset($request['orderby']) ? sanitize_text_field($request['orderby']) : "";
        $order     = isset($request['order']) ? sanitize_text_field($request['order']) : "";
        $paged      = isset($request['paged']) ? sanitize_text_field($request['paged']) : 1;

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
        $terms = $user_search->get_results();

        if ($terms) :
            $responses['noPosts'] = false;
            foreach ($terms as  $term) :
                $user_data = new stdClass();
                $user_data->ID = $term->ID;
                $user_data->user_login = $term->user_login;
                $user_data->user_nicename = $term->user_nicename;
                $user_data->user_email = $term->user_email;
                //$user_data->registered = $term->user_registered;
                //$user_data->user_status = $term->user_status;
                $user_data->description = $term->description;
                $user_data->display_name = $term->display_name;
                $user_data->first_name = $term->first_name;
                $user_data->last_name = $term->last_name;
                $user_data->roles = $term->roles;
                //$user_data->caps = $term->caps;
                $user_data->url = $term->user_url;
                $user_data->avatar = get_avatar_url($term->ID);

                $posts[] = $user_data;
            endforeach;

            $total = $user_search->get_total();




            $max_pages = ceil($total / $number);


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

        $registerBy     = isset($request['registerBy']) ? sanitize_text_field($request['registerBy']) : '';
        $password     = isset($request['password']) ? sanitize_text_field($request['password']) : '';
        $role     = isset($request['role']) ? sanitize_text_field($request['role']) : '';


        $ComboStoreRegister = new ComboStoreRegister();

        $response = [];


        $response = $ComboStoreRegister->create_user(['email' => $email, "password" => $password, "role" => $role, "mobile" => $mobile, "registerBy" => $registerBy]);



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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


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
            $response['errors']['post_title_missing'] = __("Post Title Missing", 'combo-store-server');
        }
        if (empty($post_content)) {
            $response['errors']['prompt_missing'] = __("Details Text Missing", 'combo-store-server');
        }
        if (empty($email)) {
            $response['errors']['email_missing'] = __("Email address Missing", 'combo-store-server');
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




        if (!empty($menuOrder)) {
            update_post_meta($post_id, 'menuOrder', $menuOrder);
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
        $token = $request->get_header('Authorization');
        $user_id = null;
        $isAdmin = false;


        if (!empty($token)) {
            // return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));


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


        $response = [];
        $post_id     = isset($request['id']) ? absint($request['id']) : 0;

        if (!$post_id) {

            die(wp_json_encode($response));
        }

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
     * Return Posts
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Post data.
     */
    public function get_tickets($request)
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

        $user_id = 1;
        $isAdmin = true;
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


        $ids     = isset($request['ids']) ? ($request['ids']) : [];


        $response = [];



        if (!empty($ids)) {

            foreach ($ids as $id) {
                wp_delete_post($id, false);
            }
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


        $response = [];



        if (!empty($id)) {

            $my_post = array(
                'ID'           => $id,
                'post_content' => wp_json_encode($blocks),
            );
            wp_update_post($my_post);
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
                if (!empty($body_arr['salePrice'])) {

                    update_post_meta($post_id, 'salePrice', $body_arr['salePrice']);
                }
                if (!empty($body_arr['regularPrice'])) {
                    update_post_meta($post_id, 'regularPrice', $body_arr['regularPrice']);
                }


                if (!empty($body_arr['bulkPrices'])) {
                    update_post_meta($post_id, 'bulkPrices', $body_arr['bulkPrices']);
                }
                if (!empty($body_arr['pwywMinPrice'])) {
                    update_post_meta($post_id, 'pwywMinPrice', $body_arr['pwywMinPrice']);
                }
                if (!empty($body_arr['pwywDefaultPrice'])) {
                    update_post_meta($post_id, 'pwywDefaultPrice', $body_arr['pwywDefaultPrice']);
                }
                if (!empty($body_arr['tradePrice'])) {
                    update_post_meta($post_id, 'tradePrice', $body_arr['tradePrice']);
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
}


new ComboStoreRest();
