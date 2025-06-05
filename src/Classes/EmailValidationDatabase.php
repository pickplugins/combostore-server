<?php

namespace EmailValidation\Classes;


class EmailValidationDatabase
{

    public function __construct()
    {


        add_action('email_validation_activation', array($this, 'generate_sql_tables'));
    }




    public function generate_sql_tables()
    {


        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;
        // $table_api_keys = $prefix . 'cstore_api_keys';
        // $table_requests = $prefix . 'cstore_api_requests';

        // $table_stats_counter = $prefix . 'cstore_stats_counter';

        // $table_credits = $prefix . 'cstore_credits';

        $table_products = $prefix . 'cstore_products';
        $table_products_meta = $prefix . 'cstore_products_meta';

        // $table_varients = $prefix . 'cstore_varients';
        // $table_varients_meta = $prefix . 'cstore_varients_meta';

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
        $table_api_rate_limits = $wpdb->prefix . 'cstore_api_rate_limits';
        $table_source_links = $prefix . 'cstore_source_links';





        // $sql_stats_counter = "CREATE TABLE IF NOT EXISTS $table_stats_counter (
        //             id int(100) NOT NULL AUTO_INCREMENT,
        // 			title	VARCHAR( 50 )	NOT NULL,
        // 			apikey	VARCHAR( 50 )	NOT NULL,
        // 			userid	bigint(20)		NOT NULL,
        // 			datetime  DATETIME NOT NULL,
        // 			status	VARCHAR( 50 )	NOT NULL,
        //             UNIQUE KEY id (id)
        //         ) $charset_collate;";


        // $sql_api_keys = "CREATE TABLE IF NOT EXISTS $table_api_keys (
        //             id int(100) NOT NULL AUTO_INCREMENT,
        // 			title	VARCHAR( 50 )	NOT NULL,
        // 			apikey	VARCHAR( 50 )	NOT NULL,
        // 			userid	bigint(20)		NOT NULL,
        // 			datetime  DATETIME NOT NULL,
        // 			status	VARCHAR( 50 )	NOT NULL,
        //             UNIQUE KEY id (id)
        //         ) $charset_collate;";


        // $sql_requests = "CREATE TABLE IF NOT EXISTS $table_requests (
        //             id int(100) NOT NULL AUTO_INCREMENT,
        // 			userid	bigint(20)		NOT NULL,
        // 			apikeyid	VARCHAR( 50 )	NOT NULL,
        // 			email	VARCHAR( 255 )	NOT NULL,
        // 			result	longtext	NOT NULL,
        // 			datetime  DATETIME NOT NULL,
        //             UNIQUE KEY id (id)
        //         ) $charset_collate;";



        // $sql_credits = "CREATE TABLE IF NOT EXISTS $table_credits (
        //             id int(100) NOT NULL AUTO_INCREMENT,
        // 			userid	bigint(20)		NOT NULL,
        // 			type	VARCHAR( 50 )	NOT NULL, 
        // 			credit_type	VARCHAR( 50 )	NOT NULL, 
        // 			source	VARCHAR( 50 )	NOT NULL, 
        // 			amount	int(100)	NOT NULL,
        // 			status	VARCHAR( 50 )	NOT NULL,
        // 			notes	longtext	NOT NULL,
        // 			datetime  DATETIME NOT NULL,
        //             UNIQUE KEY id (id)
        //         ) $charset_collate;";

        // status => for daily credit, expired
        // type => credit, debit
        // credit_type => instant, daily, bonus, register, monthly, login
        // source => debit => API, Dashboard




        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			userid	bigint(20)		NOT NULL,
        			parent_id	bigint(20)		NOT NULL,
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

        $sql_licenses_meta = "CREATE TABLE IF NOT EXISTS $table_licenses_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	bigint(20)		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        //         $sql_api_rate_limits = "CREATE TABLE IF NOT EXISTS $table_api_rate_limits (
        //     id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        //     ip VARCHAR(45) NOT NULL,
        //     request_time DATETIME NOT NULL
        // ) $charset_collate;";

        //         $sql_source_links = "CREATE TABLE IF NOT EXISTS $table_source_links (
        //             id bigint(20) NOT NULL AUTO_INCREMENT,
        //             link text NOT NULL,
        //             target varchar(50) NOT NULL,
        //             datetime datetime NOT NULL,
        //             PRIMARY KEY (id)
        //         ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // dbDelta($sql_stats_counter);

        // dbDelta($sql_api_keys);
        // dbDelta($sql_requests);


        // dbDelta($sql_credits);

        dbDelta($sql_products);
        dbDelta($sql_products_meta);

        dbDelta($sql_orders);
        dbDelta($sql_orders_meta);

        dbDelta($sql_subscriptions);
        dbDelta($sql_subscriptions_meta);


        // dbDelta($sql_api_rate_limits);
        // dbDelta($sql_source_links);

        dbDelta($sql_licenses);
        dbDelta($sql_licenses_meta);

        dbDelta($sql_downloads);
        dbDelta($sql_downloads_meta);

        // $email_validation_info = array();
        // $email_validation_info['db_version'] = 0;

    }
}
