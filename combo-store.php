<?php
/*
Plugin Name: Combo Store
Plugin URI: http://pickplugins.com/items/woocommerce-product-slider-for-wordpress/
Description: combo-store
Version: 1.0.0
Author: PickPlugins
Text Domain: email-verify
Author URI: http://pickplugins.com
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) exit;  // if direct access


define('email_verify_plugin_url', plugins_url('/', __FILE__));
define('email_verify_plugin_dir', plugin_dir_path(__FILE__));
define('email_verify_plugin_name', 'Email Verify');
define('email_verify_plugin_version', '1.0.0');
// define('JWT_AUTH_SECRET_KEY', '');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}



use EmailValidation\Classes\EmailValidationCore;
use EmailValidation\Classes\EmailValidationRest;
use EmailValidation\Classes\ComboRequestLemonsqueezy;
use EmailValidation\Classes\EmailValidationCrons;
use EmailValidation\Classes\EmailValidationDatabase;
use EmailValidation\Classes\EmailValidationPostTypes;

// Check if the class exists before using it
if (class_exists('EmailValidation\Classes\EmailValidationCore')) {
    new EmailValidationCore();
}
if (class_exists('EmailValidation\Classes\EmailValidationDatabase')) {
    new EmailValidationDatabase();
}

if (class_exists('EmailValidation\Classes\EmailValidationRest')) {
    new EmailValidationRest();
}

if (class_exists('EmailValidation\Classes\ComboRequestLemonsqueezy')) {
    new ComboRequestLemonsqueezy();
}
if (class_exists('EmailValidation\Classes\EmailValidationCrons')) {
    new EmailValidationCrons();
}
if (class_exists('EmailValidation\Classes\EmailValidationPostTypes')) {
    new EmailValidationPostTypes();
}


register_activation_hook(__FILE__, 'email_validation_activation');
register_deactivation_hook(__FILE__, 'email_validation_deactivation');


require_once(email_verify_plugin_dir . 'includes/functions.php');


function email_validation_activation()
{


    if (!wp_next_scheduled('run_check_tasks_status')) {
        wp_schedule_event(time(), '10minute', 'run_check_tasks_status');
    }

    if (!wp_next_scheduled('run_email_validation_task')) {
        wp_schedule_event(time(), '10minute', 'run_email_validation_task');
    }
    if (!wp_next_scheduled('run_email_validation_add_daily_credit')) {
        wp_schedule_event(time(), 'daily', 'run_email_validation_add_daily_credit');
    }

    do_action('email_validation_activation');
}



function email_validation_deactivation()
{

    wp_clear_scheduled_hook('run_check_tasks_status');
    wp_clear_scheduled_hook('run_email_validation_task');
    wp_clear_scheduled_hook('run_email_validation_add_daily_credit');


    /*
         * Custom action hook for plugin deactivation.
         * Action hook: email_validation_deactivation
         * */
    do_action('email_validation_deactivation');
}

// Add CORS headers for the REST API
function email_validation_add_cors_headers()
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
add_action('rest_api_init', 'email_validation_add_cors_headers');
