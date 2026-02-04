<?php

namespace ComboStore\Classes;


class ComboStoreDatabase
{

    public function __construct()
    {


        add_action('combo_store_activation', array($this, 'generate_sql_tables'));
    }




    public function generate_sql_tables()
    {


        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        $table_credits = $prefix . 'cstore_credits';
        $table_activities = $prefix . 'cstore_activities';

        $table_orders = $prefix . 'cstore_orders';
        $table_order_items = $prefix . 'cstore_order_items';
        $table_orders_meta = $prefix . 'cstore_orders_meta';

        $table_expenses  = $prefix . 'cstore_expenses';
        $table_expenses_meta = $prefix . 'cstore_expenses_meta';

        $table_purchases  = $prefix . 'cstore_purchases';
        $table_purchases_meta = $prefix . 'cstore_purchases_meta';



        $table_deliveries = $prefix . 'cstore_deliveries';
        $table_deliveries_trackings = $prefix . 'cstore_deliveries_trackings';



        $table_subscriptions = $prefix . 'cstore_subscriptions';
        $table_subscriptions_meta = $prefix . 'cstore_subscriptions_meta';
        $table_subscriptions_to_call = $prefix . 'cstore_subscriptions_to_call';



        $table_licenses = $prefix . 'cstore_licenses';
        $table_licenses_meta = $prefix . 'cstore_licenses_meta';

        $table_downloads = $prefix . 'cstore_downloads';
        $table_downloads_meta = $prefix . 'cstore_downloads_meta';

        $table_source_links = $prefix . 'cstore_source_links';




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


        $sql_activities = "CREATE TABLE IF NOT EXISTS $table_activities (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			userid	bigint(20)		NOT NULL,
        			event	VARCHAR( 50 )	NOT NULL, 
        			source	VARCHAR( 50 )	NOT NULL, 
        			value	VARCHAR( 50 )	NOT NULL, 
        			device	VARCHAR( 50 )	NOT NULL, 
        			browser	VARCHAR( 50 )	NOT NULL, 
        			platform	VARCHAR( 50 )	NOT NULL, 
        			ip	VARCHAR( 50 )	NOT NULL, 
        			city	VARCHAR( 50 )	NOT NULL, 
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_deliveries = "CREATE TABLE IF NOT EXISTS $table_deliveries (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    customer_id BIGINT(20) NOT NULL,
                    order_id BIGINT(20) NOT NULL,
                    rider_id BIGINT(20) NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    status VARCHAR(50) NOT NULL,
                    startLatLng JSON NOT NULL,
                    endLatLng JSON NOT NULL,
                    notes LONGTEXT NOT NULL,
                    datetime DATETIME NOT NULL,
                    PRIMARY KEY (id)
                ) $charset_collate;";

        $sql_deliveries_trackings = "CREATE TABLE IF NOT EXISTS $table_deliveries_trackings (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			order_id	bigint(20)		NOT NULL,
        			delivery_id	bigint(20)		NOT NULL,
        			status	VARCHAR( 50 )	NOT NULL,
        			latlng	VARCHAR( 255 )	NOT NULL,
        			notes	longtext	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";





        // status => for daily credit, expired
        // type => credit, debit
        // credit_type => instant, daily, bonus, register, monthly, login
        // source => debit => API, Dashboard




        //         $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
        //                     id int(100) NOT NULL AUTO_INCREMENT,
        //         			slug	VARCHAR( 255 )	NOT NULL,
        //         			userid	bigint(20)		NOT NULL,
        //         			parent_id	bigint(20)		NOT NULL,
        //         			title	VARCHAR( 255 )	NOT NULL,
        //         			content	longtext	NOT NULL,
        //         			images	longtext	NOT NULL,
        //         			status	VARCHAR( 50 )	NOT NULL,
        //         			datetime  DATETIME NOT NULL,
        //                     UNIQUE KEY id (id)
        //                 ) $charset_collate;";

        //         $sql_products_meta = "CREATE TABLE IF NOT EXISTS $table_products_meta (
        //                     id int(100) NOT NULL AUTO_INCREMENT,
        //         			object_id	bigint(20)		NOT NULL,
        //         			meta_key	VARCHAR( 255 )		NOT NULL,
        //         			meta_value	longtext	NOT NULL,
        //                     UNIQUE KEY id (id)
        //                 ) $charset_collate;";

        //         $sql_products_cat = "CREATE TABLE IF NOT EXISTS $table_products_cat (
        //                     id int(100) NOT NULL AUTO_INCREMENT,
        //         			name 	VARCHAR( 255 )	NOT NULL,
        //         			slug  	VARCHAR( 255 )	NOT NULL,
        //         			parent_id 	bigint(20)		NOT NULL,
        //         			description 	longtext	NOT NULL,
        //         			datetime  DATETIME NOT NULL,
        //                     UNIQUE KEY id (id)
        //                 ) $charset_collate;";

        //         $sql_products_cat_rel = "CREATE TABLE IF NOT EXISTS $table_products_cat_rel (
        //                     object_id  int(100) NOT NULL AUTO_INCREMENT,
        //         			category_id  	bigint(20)		NOT NULL,
        //         			object_type  	VARCHAR( 50 )	NOT NULL,
        //   PRIMARY KEY (object_id, category_id, object_type),
        //   FOREIGN KEY (category_id) REFERENCES wp_cstore_products_cat(id) ON DELETE CASCADE
        //                 ) $charset_collate;";



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
        			meta_key	VARCHAR( 255 )		NOT NULL,
        			meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_orders = "CREATE TABLE IF NOT EXISTS $table_orders (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    userid BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)

    status VARCHAR(50) NOT NULL,

    currency CHAR(3) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    shipping_method VARCHAR(100) DEFAULT NULL,

    billing_name VARCHAR(150) NOT NULL,
    billing_email VARCHAR(150) NOT NULL,
    billing_phone VARCHAR(50) DEFAULT NULL,
    billing_address TEXT NOT NULL,

    shipping_name VARCHAR(150) NOT NULL,
    shipping_phone VARCHAR(50) DEFAULT NULL,
    shipping_address TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,

    UNIQUE KEY id (id)
) $charset_collate;";

        $sql_order_items = "CREATE TABLE IF NOT EXISTS $table_order_items (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT(20) UNSIGNED NOT NULL, 
    product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
    product_name VARCHAR(200) NOT NULL, 
    sku VARCHAR(100) DEFAULT NULL,

    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    meta JSON DEFAULT NULL,
    UNIQUE KEY id (id)

) $charset_collate;";










        $sql_orders_meta = "CREATE TABLE IF NOT EXISTS $table_orders_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			object_id	bigint(20)		NOT NULL,
        			meta_key	VARCHAR( 255 )		NOT NULL,
        			meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";






        $sql_expenses = "CREATE TABLE IF NOT EXISTS $table_expenses (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,


    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50) NOT NULL,

    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,

    craeted_by BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
    craeted_for BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
    note TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY id (id)
) $charset_collate;";




        $sql_expenses_meta = "CREATE TABLE IF NOT EXISTS $table_expenses_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			object_id	bigint(20)		NOT NULL,
        			meta_key	VARCHAR( 255 )		NOT NULL,
        			meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";




        $sql_purchases = "CREATE TABLE IF NOT EXISTS $table_purchases (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,


    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(50) NOT NULL,
    subcategory VARCHAR(50) NOT NULL,

    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,

    craeted_by BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
    craeted_for BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
    note TEXT NOT NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY id (id)
) $charset_collate;";




        $sql_purchases_meta = "CREATE TABLE IF NOT EXISTS $table_purchases_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			object_id	bigint(20)		NOT NULL,
        			meta_key	VARCHAR( 255 )		NOT NULL,
        			meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";







        $sql_subscriptions_to_call = "CREATE TABLE IF NOT EXISTS $table_subscriptions_to_call (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					product_id	bigint(20)		NOT NULL,
        			user_name	VARCHAR( 50 )	NOT NULL,
        			`interval`	VARCHAR( 50 )	NOT NULL,
					interval_count	int(20)		NOT NULL,
        			start_date  DATETIME NOT NULL,
        			next_date  DATETIME NOT NULL,
        			phone	VARCHAR( 50 )	NOT NULL,
					status	VARCHAR( 50 )	NOT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_subscriptions = "CREATE TABLE IF NOT EXISTS $table_subscriptions (
                    id int(100) NOT NULL AUTO_INCREMENT,
					userid	bigint(20)		NOT NULL,
					order_id	bigint(20)		NOT NULL,
                    plan_id              BIGINT NOT NULL, -- reference to a subscription_plan table
                    status               ENUM('active', 'paused', 'canceled', 'expired') NOT NULL DEFAULT 'active',
                    
                    start_date           DATE NOT NULL,
                    end_date             DATE DEFAULT NULL,  -- null if ongoing until canceled
                    next_billing_date    DATE DEFAULT NULL,
                    cancel_at_period_end BOOLEAN DEFAULT FALSE,
                    
                    renewal_interval     ENUM('day', 'week', 'month', 'year') NOT NULL DEFAULT 'month',
                    interval_count        INT DEFAULT 1, -- e.g., every 3 months
                    
                    trial_start          DATE DEFAULT NULL,
                    trial_end            DATE DEFAULT NULL,
                    
                    payment_method_id    BIGINT DEFAULT NULL, -- link to saved payment method
                    last_payment_date    DATE DEFAULT NULL,
                    total_amount  DECIMAL(10,2) DEFAULT NULL,
                    subtotal_amount  DECIMAL(10,2) DEFAULT NULL,
                    
                    created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                    UNIQUE KEY id (id)
                ) $charset_collate;";



        $sql_subscriptions_meta = "CREATE TABLE IF NOT EXISTS $table_subscriptions_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
					object_id	bigint(20)		NOT NULL,
					meta_key	VARCHAR( 255 )		NOT NULL,
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
					meta_key	VARCHAR( 255 )		NOT NULL,
					meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";



        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');


        // dbDelta($sql_credits);
        dbDelta($sql_activities);
        dbDelta($sql_deliveries);
        dbDelta($sql_deliveries_trackings);

        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_orders_meta);

        dbDelta($sql_expenses);
        dbDelta($sql_expenses_meta);

        dbDelta($sql_purchases);
        dbDelta($sql_purchases_meta);




        dbDelta($sql_subscriptions_to_call);
        dbDelta($sql_subscriptions);
        dbDelta($sql_subscriptions_meta);


        // dbDelta($sql_api_rate_limits);

        // dbDelta($sql_licenses);
        // dbDelta($sql_licenses_meta);

        // dbDelta($sql_downloads);
        // dbDelta($sql_downloads_meta);

        // $combo_store_info = array();
        // $combo_store_info['db_version'] = 0;

    }
}
