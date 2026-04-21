<?php
/*
Plugin Name: Combo Store - Server
Plugin URI: http://pickplugins.com/items/woocommerce-product-slider-for-wordpress/
Description: combo-store
Version: 1.0.5
Author: PickPlugins
Text Domain: combo-store-server
Author URI: http://pickplugins.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;  // if direct access


define('combo_store_plugin_url', plugins_url('/', __FILE__));
define('combo_store_plugin_dir', plugin_dir_path(__FILE__));
define('combo_store_plugin_file', __FILE__);
define('combo_store_plugin_name', 'Combo Store');
define('combo_store_plugin_version', '1.0.5');
// define('JWT_AUTH_SECRET_KEY', '');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}



use ComboStore\Classes\ComboStoreCore;
use ComboStore\Classes\ComboStoreRest;
use ComboStore\Classes\ComboRequestLemonsqueezy;
use ComboStore\Classes\ComboStoreCrons;
use ComboStore\Classes\ComboStoreDatabase;
use ComboStore\Classes\ComboStorePostTypes;
use ComboStore\Classes\GithubPluginUpdater;

// Check if the class exists before using it
if (class_exists('ComboStore\Classes\ComboStoreCore')) {
    new ComboStoreCore();
}
if (class_exists('ComboStore\Classes\ComboStoreDatabase')) {
    new ComboStoreDatabase();
}

if (class_exists('ComboStore\Classes\ComboStoreRest')) {
    new ComboStoreRest();
}

if (class_exists('ComboStore\Classes\ComboRequestLemonsqueezy')) {
    new ComboRequestLemonsqueezy();
}
if (class_exists('ComboStore\Classes\ComboStoreCrons')) {
    new ComboStoreCrons();
}
if (class_exists('ComboStore\Classes\ComboStorePostTypes')) {
    new ComboStorePostTypes();
}


register_activation_hook(__FILE__, 'combo_store_activation');
register_deactivation_hook(__FILE__, 'combo_store_deactivation');


require_once(combo_store_plugin_dir . 'includes/functions.php');
require_once(combo_store_plugin_dir . 'includes/functions-courier.php');
require_once(combo_store_plugin_dir . 'includes/functions-mails.php');
require_once(combo_store_plugin_dir . 'includes/functions-shortcodes.php');
require_once(combo_store_plugin_dir . 'includes/functions-purchase.php');
require_once(combo_store_plugin_dir . 'includes/functions-roles.php');
require_once(combo_store_plugin_dir . 'includes/functions-comments.php');
require_once(combo_store_plugin_dir . 'includes/functions-email-subscribe.php');



add_action('init', function () {



    new GithubPluginUpdater(
        combo_store_plugin_file,
        'pickplugins/combo-store-server', // CHANGE THIS
        combo_store_plugin_version,
        '' // optional GitHub token 1
    );
});
// no secret 



function combo_store_activation()
{


    // if (!wp_next_scheduled('run_check_tasks_status')) {
    //     wp_schedule_event(time(), '10minute', 'run_check_tasks_status');
    // }

    // if (!wp_next_scheduled('run_combo_store_task')) {
    //     wp_schedule_event(time(), '10minute', 'run_combo_store_task');
    // }
    // if (!wp_next_scheduled('run_combo_store_add_daily_credit')) {
    //     wp_schedule_event(time(), 'daily', 'run_combo_store_add_daily_credit');
    // }

    if (!wp_next_scheduled('run_update_product_description')) {
        wp_schedule_event(time(), '30minute', 'run_update_product_description');
    }
    if (!wp_next_scheduled('run_update_blog_description')) {
        wp_schedule_event(time(), '15minute', 'run_update_blog_description');
    }
    if (!wp_next_scheduled('run_update_blog_slug')) {
        wp_schedule_event(time(), '15minute', 'run_update_blog_slug');
    }

    if (!wp_next_scheduled('run_total_counts')) {
        wp_schedule_event(time(), 'daily', 'run_total_counts');
    }





    do_action('combo_store_activation');
}



function combo_store_deactivation()
{

    // wp_clear_scheduled_hook('run_check_tasks_status');
    // wp_clear_scheduled_hook('run_combo_store_task');
    // wp_clear_scheduled_hook('run_combo_store_add_daily_credit');
    wp_clear_scheduled_hook('run_update_product_description');
    wp_clear_scheduled_hook('run_update_blog_description');
    wp_clear_scheduled_hook('run_update_blog_slug');


    /*
         * Custom action hook for plugin deactivation.
         * Action hook: combo_store_deactivation
         * */
    do_action('combo_store_deactivation');
}

// Add CORS headers for the REST API
function combo_store_add_cors_headers()
{
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter('rest_pre_serve_request', function ($value) {
        header('Access-Control-Allow-Origin: chrome-extension://*');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

        if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
            status_header(200);
            exit();
        }

        return $value;
    });
}
add_action('rest_api_init', 'combo_store_add_cors_headers');

add_filter('cron_schedules', 'combo_store_cron_schedules');


function combo_store_cron_schedules($schedules)
{



    $schedules['3minute'] = array(
        'interval'  => 180,
        'display'   => __('3 Minute', 'combo-store-server')
    );

    $schedules['5minute'] = array(
        'interval'  => 300,
        'display'   => __('5 Minute', 'combo-store-server')
    );

    $schedules['15minute'] = array(
        'interval'  => 900,
        'display'   => __('15 Minute', 'combo-store-server')
    );

    $schedules['30minute'] = array(
        'interval'  => 1800,
        'display'   => __('30 Minute', 'combo-store-server')
    );





    return $schedules;
}
