<?php

namespace EmailValidation\Classes;


class EmailValidationCore
{

    public function __construct()
    {

        // define('email_verify_plugin_url', plugins_url('/', __FILE__));
        // define('email_verify_plugin_dir', plugin_dir_path(__FILE__));
        // define('email_verify_plugin_name', 'Email Verify');
        // define('email_verify_plugin_version', '1.0.0');

        // require_once(email_verify_plugin_dir . 'includes/class-email-verifier.php');
        // require_once(email_verify_plugin_dir . 'includes/class-api-requests.php');
        // require_once(email_verify_plugin_dir . 'includes/class-api-key.php');
        // require_once(email_verify_plugin_dir . 'includes/class-credits.php');
        // require_once(email_verify_plugin_dir . 'includes/class-register-user.php');
        // require_once(email_verify_plugin_dir . 'includes/class-export.php');
        // require_once(email_verify_plugin_dir . 'includes/class-stats.php');
        // require_once(email_verify_plugin_dir . 'includes/class-spammers.php');
        // require_once(email_verify_plugin_dir . 'includes/class-object-meta.php');
        // require_once(email_verify_plugin_dir . 'includes/class-request-lemonsqueezy.php');

        // require_once(email_verify_plugin_dir . 'includes/functions.php');
        // require_once(email_verify_plugin_dir . 'includes/functions-rest.php');
        // require_once(email_verify_plugin_dir . 'includes/functions-crons.php');
        // require_once(email_verify_plugin_dir . 'includes/JWT.php');
        // require_once(email_verify_plugin_dir . 'includes/Key.php');



        //add_action('admin_enqueue_scripts', array($this, '_admin_scripts'));
        register_activation_hook(__FILE__, array($this, '_activation'));
        register_deactivation_hook(__FILE__, array($this, '_deactivation'));
        add_filter('cron_schedules', array($this, '_cron_schedules'));
    }

    function _cron_schedules($schedules)
    {



        $schedules['1minute'] = array(
            'interval'  => 60,
            'display'   => __('1 Minute', 'user-verification')
        );

        $schedules['10minute'] = array(
            'interval'  => 600,
            'display'   => __('1 Minute', 'user-verification')
        );

        $schedules['30minute'] = array(
            'interval'  => 1800,
            'display'   => __('30 Minute', 'user-verification')
        );

        $schedules['6hours'] = array(
            'interval'  => 21600,
            'display'   => __('6 hours', 'user-verification')
        );





        return $schedules;
    }



    public function _activation()
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


        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        $table_api_keys = $prefix . 'cstore_api_keys';
        $table_requests = $prefix . 'cstore_api_requests';
        $table_validation_tasks = $prefix . 'cstore_validation_tasks';
        $table_validation_tasks_meta = $prefix . 'cstore_validation_tasks_meta';

        $table_validation_tasks_entries = $prefix . 'cstore_validation_tasks_entries';
        $table_spammers = $prefix . 'cstore_spammers';
        $table_spammers_domain = $prefix . 'cstore_spammers_domain';
        $table_credits = $prefix . 'cstore_credits';



        $table_products = $prefix . 'cstore_products';
        $table_products_meta = $prefix . 'cstore_products_meta';

        $table_varients = $prefix . 'cstore_varients';
        $table_varients_meta = $prefix . 'cstore_varients_meta';

        $table_orders = $prefix . 'cstore_orders';
        $table_orders_meta = $prefix . 'cstore_orders_meta';

        $table_subscriptions = $prefix . 'cstore_subscriptions';
        $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';

        $table_licenses = $prefix . 'cstore_licenses';
        $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        $table_downloads = $prefix . 'cstore_downloads';
        $table_downloads_meta = $prefix . 'cstore_downloads_meta';

        $table_refunds = $prefix . 'cstore_refunds';

        $table_affiliates = $prefix . 'cstore_affiliates';
        $table_affiliates_meta = $prefix . 'cstore_affiliates_meta';


        $sql_api_keys = "CREATE TABLE IF NOT EXISTS $table_api_keys (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			title	VARCHAR( 50 )	NOT NULL,
        			apikey	VARCHAR( 50 )	NOT NULL,
					userid	bigint(20)		NOT NULL,
        			datetime  DATETIME NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_requests = "CREATE TABLE IF NOT EXISTS $table_requests (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
        			apikeyid	VARCHAR( 50 )	NOT NULL,
					email	VARCHAR( 255 )	NOT NULL,
					result	longtext	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";




        $sql_validation_tasks = "CREATE TABLE IF NOT EXISTS $table_validation_tasks (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
        			title	VARCHAR( 50 )	NOT NULL,
					status	VARCHAR( 255 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_validation_tasks_meta = "CREATE TABLE IF NOT EXISTS $table_validation_tasks_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_validation_tasks_entries = "CREATE TABLE IF NOT EXISTS $table_validation_tasks_entries (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
        			task_id	VARCHAR( 50 )	NOT NULL,
					email	VARCHAR( 255 )	NOT NULL,
					result	longtext	NOT NULL,
					status	VARCHAR( 255 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";




        $sql_spammers = "CREATE TABLE IF NOT EXISTS $table_spammers (
                    id int(100) NOT NULL AUTO_INCREMENT,
					email	VARCHAR( 255 )	NOT NULL,
					domains	longtext	NOT NULL,
					report_count	int(20)		NOT NULL,
					level	int(20)		NOT NULL,
        			last_date  DATETIME NOT NULL,
        			date_created  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_spammers_domain = "CREATE TABLE IF NOT EXISTS $table_spammers_domain (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					contact_email	VARCHAR( 255 )	NOT NULL,
					domain	VARCHAR( 255 )	NOT NULL,
					report_count	int(20)		NOT NULL,
        			last_date  DATETIME NOT NULL,
        			date_created  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";



        $sql_credits = "CREATE TABLE IF NOT EXISTS $table_credits (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					type	VARCHAR( 50 )	NOT NULL, 
					credit_type	VARCHAR( 50 )	NOT NULL, 
					source	VARCHAR( 50 )	NOT NULL, 
					amount	int(100)	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
					notes	longtext	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        // status => for daily credit, expired
        // type => credit, debit
        // credit_type => instant, daily, bonus, register, monthly, login
        // source => debit => API, Dashboard




        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
        			title	VARCHAR( 255 )	NOT NULL,
					content	longtext	NOT NULL,
					images	longtext	NOT NULL,
					downloads	longtext	NOT NULL,
        			price	VARCHAR( 50 )	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_products_meta = "CREATE TABLE IF NOT EXISTS $table_products_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";





        $sql_downloads = "CREATE TABLE IF NOT EXISTS $table_downloads (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					order_id	bigint(20)		NOT NULL,
					downloads	longtext	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_downloads_meta = "CREATE TABLE IF NOT EXISTS $table_downloads_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";









        $sql_orders = "CREATE TABLE IF NOT EXISTS $table_orders (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					order_id	bigint(20)		NOT NULL,
					customer_id	bigint(20)		NOT NULL,
        			user_email	VARCHAR( 50 )	NOT NULL,
        			user_name	VARCHAR( 50 )	NOT NULL,
        			total	VARCHAR( 50 )	NOT NULL,
        			currency	VARCHAR( 50 )	NOT NULL,
        			tax_total	VARCHAR( 50 )	NOT NULL,
        			discount_total	VARCHAR( 50 )	NOT NULL,
        			refunded_total	VARCHAR( 50 )	NOT NULL,
        			refunded_at  DATETIME NOT NULL,
        			subtotal	VARCHAR( 50 )	NOT NULL,
        			setup_fee	VARCHAR( 50 )	NOT NULL,
        			payment_method	VARCHAR( 50 )	NOT NULL,
        			test_mode	VARCHAR( 50 )	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
					webhook_data	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_orders_meta = "CREATE TABLE IF NOT EXISTS $table_orders_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_subscriptions = "CREATE TABLE IF NOT EXISTS $table_subscriptions (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					subscription_id	bigint(20)		NOT NULL,
					order_id	bigint(20)		NOT NULL,
					customer_id	bigint(20)		NOT NULL,
        			user_email	VARCHAR( 50 )	NOT NULL,
        			user_name	VARCHAR( 50 )	NOT NULL,
        			total	VARCHAR( 50 )	NOT NULL,
        			currency	VARCHAR( 50 )	NOT NULL,
        			tax_total	VARCHAR( 50 )	NOT NULL,
        			discount_total	VARCHAR( 50 )	NOT NULL,
        			refunded_total	VARCHAR( 50 )	NOT NULL,
        			subtotal	VARCHAR( 50 )	NOT NULL,
        			setup_fee	VARCHAR( 50 )	NOT NULL,
        			billing_anchor	VARCHAR( 50 )	NOT NULL,
        			card_last_four	VARCHAR( 50 )	NOT NULL,
        			test_mode	VARCHAR( 50 )	NOT NULL,
        			pause	VARCHAR( 50 )	NOT NULL,
        			cancelled	VARCHAR( 50 )	NOT NULL,
        			trial_ends_at	VARCHAR( 50 )	NOT NULL,
        			renews_at	VARCHAR( 50 )	NOT NULL,
        			ends_at	VARCHAR( 50 )	NOT NULL,
					urls	longtext	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
					webhook_data	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_subscriptions_meta = "CREATE TABLE IF NOT EXISTS $table_subscriptions_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_licenses = "CREATE TABLE IF NOT EXISTS $table_licenses (
                    id int(100) NOT NULL AUTO_INCREMENT,
					license_id	bigint(20)		NOT NULL,
					userid	bigint(20)		NOT NULL,
					order_id	bigint(20)		NOT NULL,
					customer_id	bigint(20)		NOT NULL,
					product_id	bigint(20)		NOT NULL,
        			user_email	VARCHAR( 50 )	NOT NULL,
        			user_name	VARCHAR( 50 )	NOT NULL,
        			license_key	VARCHAR( 50 )	NOT NULL,
        			key_short	VARCHAR( 50 )	NOT NULL,
        			activation_limit	VARCHAR( 50 )	NOT NULL,
        			instances_count	VARCHAR( 50 )	NOT NULL,
        			disabled	VARCHAR( 50 )	NOT NULL,
        			test_mode	VARCHAR( 50 )	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			created_at  DATETIME NOT NULL,
        			expires_at  DATETIME NOT NULL,
					webhook_data	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";




        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_api_keys);
        dbDelta($sql_requests);
        dbDelta($sql_validation_tasks);
        dbDelta($sql_validation_tasks_meta);
        dbDelta($sql_validation_tasks_entries);
        dbDelta($sql_spammers);
        dbDelta($sql_spammers_domain);

        dbDelta($sql_credits);


        dbDelta($sql_products);
        dbDelta($sql_products_meta);

        dbDelta($sql_orders);
        dbDelta($sql_orders_meta);

        dbDelta($sql_subscriptions);
        dbDelta($sql_subscriptions_meta);

        dbDelta($sql_licenses);
        dbDelta($sql_downloads);

        // $email_validation_info = array();
        // $email_validation_info['db_version'] = 0;

        $table_name = $wpdb->prefix . 'cstore_api_rate_limits';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    request_time DATETIME NOT NULL
) $charset_collate;";

        dbDelta($sql);


        do_action('email_validation_activation');
    }


    public function _deactivation()
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


    public function _admin_scripts()
    {

        wp_register_script('email_verify_js', plugins_url('assets/admin/js/scripts-layouts.js', __FILE__), array('jquery'));
        wp_localize_script(
            'email_verify_js',
            'email_verify_ajax',
            array(
                'email_verify_ajaxurl' => admin_url('admin-ajax.php'),
                'ajax_nonce' => wp_create_nonce('email_verify_ajax_nonce'),
            )
        );

        wp_register_style('font-awesome-4', email_verify_plugin_url . 'assets/global/css/font-awesome-4.css');
        wp_register_style('font-awesome-5', email_verify_plugin_url . 'assets/global/css/font-awesome-5.css');

        wp_enqueue_script('email_verify_js');
    }
}
