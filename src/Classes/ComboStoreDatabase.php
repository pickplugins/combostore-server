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


        $table_product_variations = $prefix . 'cstore_product_variations';


        $table_credits = $prefix . 'cstore_credits';
        $table_activities = $prefix . 'cstore_activities';

        $table_products = $prefix . 'cstore_products';
        $table_products_meta = $prefix . 'cstore_products_meta';
        $table_products_variations = $prefix . 'cstore_products_variations';
        $table_stock_history = $prefix . 'cstore_stock_history';


        $table_orders = $prefix . 'cstore_orders';
        $table_order_items = $prefix . 'cstore_order_items';
        $table_orders_meta = $prefix . 'cstore_orders_meta';

        $table_expenses  = $prefix . 'cstore_expenses';
        $table_expenses_items  = $prefix . 'cstore_expenses_items';
        $table_expenses_meta = $prefix . 'cstore_expenses_meta';

        $table_purchases  = $prefix . 'cstore_purchases';
        $table_purchases_meta = $prefix . 'cstore_purchases_meta';
        $table_purchases_items  = $prefix . 'cstore_purchases_items';



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

        $table_shipping_zones = $prefix . 'cstore_shipping_zones';
        $table_shipping_methods = $prefix . 'cstore_shipping_methods';
        $table_shipping_rates = $prefix . 'cstore_shipping_rates';
        $table_shipping_carriers = $prefix . 'cstore_shipping_carriers';
        $table_shipping_rules = $prefix . 'cstore_shipping_rules';


        $table_affiliates = $prefix . 'cstore_affiliates';
        $table_affiliate_sales = $prefix . 'cstore_affiliate_sales';
        $table_affiliate_clicks = $prefix . 'cstore_affiliate_clicks';
        $table_affiliate_payouts = $prefix . 'cstore_affiliate_payouts';

        $table_templates = $prefix . 'cstore_templates';
        $table_template_parts = $prefix . 'cstore_template_parts';




        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			name	VARCHAR( 50 )	DEFAULT NULL, 
                    description TEXT DEFAULT NULL,
        			thumbnail	bigint(20)		 DEFAULT NULL,
        			datetime  DATETIME NOT NULL,
        			style	longtext	DEFAULT NULL,
        			meta	longtext	DEFAULT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";

        $sql_template_parts = "CREATE TABLE IF NOT EXISTS $table_template_parts (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			template_id	bigint(20)		NOT NULL,
        			title	VARCHAR( 50 )	DEFAULT NULL, 
        			slug	VARCHAR( 255 )	DEFAULT NULL, 
        			type	VARCHAR( 50 )	DEFAULT NULL, 
        			content	longtext	DEFAULT NULL,
        			style	longtext	DEFAULT NULL,
        			meta	longtext	DEFAULT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_credits = "CREATE TABLE IF NOT EXISTS $table_credits (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			userid	bigint(20)		NOT NULL,
        			type	VARCHAR( 50 )	DEFAULT NULL, 
        			credit_type	VARCHAR( 50 )	DEFAULT NULL, 
        			source	VARCHAR( 50 )	DEFAULT NULL, 
        			amount	int(100)	DEFAULT NULL,
        			status	VARCHAR( 50 )	DEFAULT NULL,
        			notes	longtext	DEFAULT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_activities = "CREATE TABLE IF NOT EXISTS $table_activities (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			userid	bigint(20)		NOT NULL,
        			event	VARCHAR( 50 )	DEFAULT NULL, 
        			source	VARCHAR( 50 )	DEFAULT NULL, 
        			value	VARCHAR( 50 )	DEFAULT NULL, 
        			device	longtext	DEFAULT NULL, 
        			browser	VARCHAR( 50 )	DEFAULT NULL, 
        			platform	VARCHAR( 50 )	DEFAULT NULL, 
        			ip	VARCHAR( 50 )	DEFAULT NULL, 
        			city	VARCHAR( 50 )	DEFAULT NULL, 
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_deliveries = "CREATE TABLE IF NOT EXISTS $table_deliveries (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    customer_id BIGINT(20) NOT NULL,
                    order_id BIGINT(20) NOT NULL,
                    rider_id BIGINT(20) DEFAULT NULL,
                    courier VARCHAR(50) DEFAULT NULL,
                    consignment_id VARCHAR(100) DEFAULT NULL,
                    tracking_code VARCHAR(100) DEFAULT NULL,
                    tracking_link longtext DEFAULT NULL,
                    courier_data JSON DEFAULT NULL,
                    type VARCHAR(50) DEFAULT NULL,
                    status VARCHAR(50) DEFAULT NULL,
                    startLatLng JSON DEFAULT NULL,
                    endLatLng JSON DEFAULT NULL,
                    datetime DATETIME NOT NULL,
                    PRIMARY KEY (id)
                ) $charset_collate;";

        $sql_deliveries_trackings = "CREATE TABLE IF NOT EXISTS $table_deliveries_trackings (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			order_id	bigint(20)		NOT NULL,
        			delivery_id	bigint(20)		DEFAULT NULL,
        			status	VARCHAR( 50 )	NOT NULL,
        			latlng	VARCHAR( 255 )	DEFAULT NULL,
        			notes	longtext	DEFAULT NULL,
        			datetime  DATETIME NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";


        $sql_downloads = "CREATE TABLE IF NOT EXISTS $table_downloads (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			userid	bigint(20)		DEFAULT NULL,
        			order_id	bigint(20)		DEFAULT NULL,
        			downloads	longtext	DEFAULT NULL,
        			status	VARCHAR( 50 )	DEFAULT NULL,
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


        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            gallery JSON DEFAULT NULL,
            menu_order VARCHAR(100) DEFAULT NULL,
            sku VARCHAR(100) DEFAULT NULL,
            stock_status VARCHAR(50) DEFAULT NULL,
            stock_count BIGINT(20) DEFAULT NULL, 
            weight DECIMAL(10,2) DEFAULT NULL,
            height DECIMAL(10,2) DEFAULT NULL,
            width DECIMAL(10,2) DEFAULT NULL,
            length DECIMAL(10,2) DEFAULT NULL,
            price_type VARCHAR(50) DEFAULT NULL,
            regular_price DECIMAL(10,2)  DEFAULT 0.00,
            sale_price DECIMAL(10,2)  DEFAULT 0.00,
            trade_price DECIMAL(10,2) DEFAULT NULL,
            bulk_prices JSON DEFAULT NULL,
            pwyw_min_price DECIMAL(10,2) DEFAULT NULL,
            pwyw_default_price DECIMAL(10,2) DEFAULT NULL,
            bargain_min_price DECIMAL(10,2) DEFAULT NULL,
            bargain_default_price DECIMAL(10,2) DEFAULT NULL,
            addons JSON DEFAULT NULL,
            downloads JSON DEFAULT NULL,
            upsells JSON DEFAULT NULL,
            crosssells JSON DEFAULT NULL,
            faq JSON DEFAULT NULL,
            related_products JSON DEFAULT NULL,
            product_template JSON DEFAULT NULL,
            attributes JSON DEFAULT NULL,
            avg_rating DECIMAL(10,2) DEFAULT NULL,
            total_rate BIGINT(20) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)
        ) $charset_collate;";


        $sql_products_meta = "CREATE TABLE IF NOT EXISTS $table_products_meta (
                    id int(100) NOT NULL AUTO_INCREMENT,
        			object_id	bigint(20)		NOT NULL,
        			meta_key	VARCHAR( 255 )		NOT NULL,
        			meta_value	longtext	NOT NULL,
                    UNIQUE KEY id (id)
                ) $charset_collate;";



        $sql_products_variations = "CREATE TABLE IF NOT EXISTS $table_products_variations (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            title VARCHAR(200) DEFAULT NULL, 
            description longtext DEFAULT NULL,
            thumbnail JSON DEFAULT NULL, 
            sku VARCHAR(100) DEFAULT NULL,
            enable VARCHAR(50) DEFAULT NULL,
            downloadable VARCHAR(50) DEFAULT NULL,
            stock_status VARCHAR(50) DEFAULT NULL,
            stock_count BIGINT(20) DEFAULT NULL,             
            regular_price DECIMAL(10,2) DEFAULT NULL,
            sale_price DECIMAL(10,2) DEFAULT NULL,
            trade_price DECIMAL(10,2) DEFAULT NULL DEFAULT 0.00,
            weight DECIMAL(10,2)  DEFAULT NULL,
            height DECIMAL(10,2)  DEFAULT NULL,
            width DECIMAL(10,2)  DEFAULT NULL,
            length DECIMAL(10,2)  DEFAULT NULL,
            backorders VARCHAR(50) DEFAULT NULL,
            shipping_class VARCHAR(50) DEFAULT NULL,
            attributes JSON DEFAULT NULL,
            meta JSON DEFAULT NULL,
            UNIQUE KEY id (id)

        ) $charset_collate;";



        $sql_stock_history = "CREATE TABLE IF NOT EXISTS $table_stock_history (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED DEFAULT NULL,
            variation_id BIGINT UNSIGNED DEFAULT NULL,
            warehouse_id BIGINT UNSIGNED DEFAULT NULL,
            event_type VARCHAR(30) DEFAULT NULL,
            unit_cost DECIMAL(12,3) DEFAULT NULL,
            quantity BIGINT DEFAULT NULL,
            stock_before BIGINT DEFAULT NULL,
            stock_after BIGINT DEFAULT NULL,
            reference_type VARCHAR(30) DEFAULT NULL,
            reference_id BIGINT UNSIGNED DEFAULT NULL,
            event_id VARCHAR(100) DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY variation_id (variation_id),
            KEY reference_lookup (reference_type, reference_id),
            KEY created_at (created_at),
            UNIQUE KEY event_id (event_id)

        ) $charset_collate;";



        $sql_orders = "CREATE TABLE IF NOT EXISTS $table_orders (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            userid BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
            status VARCHAR(50) DEFAULT NULL,
            currency CHAR(3) DEFAULT NULL,
            total_amount DECIMAL(10,2)  DEFAULT 0.00,
            subtotal_amount DECIMAL(10,2)  DEFAULT 0.00,
            discount_amount DECIMAL(10,2)  DEFAULT 0.00,
            tax_amount DECIMAL(10,2)  DEFAULT 0.00,
            shipping_amount DECIMAL(10,2)  DEFAULT 0.00,
            advance_payment DECIMAL(10,2)  DEFAULT 0.00,
            trade_price DECIMAL(10,2)  DEFAULT 0.00,
            gross_profit DECIMAL(10,2)  DEFAULT 0.00,
            net_profit DECIMAL(10,2)  DEFAULT 0.00,
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_status VARCHAR(100) DEFAULT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            shipping_method VARCHAR(100) DEFAULT NULL,
            billing_name VARCHAR(150) DEFAULT NULL,
            billing_email VARCHAR(150) DEFAULT NULL,
            billing_phone VARCHAR(50) DEFAULT NULL,
            billing_address TEXT DEFAULT NULL,
            billing_city VARCHAR(150) DEFAULT NULL,
            billing_country VARCHAR(150) DEFAULT NULL,
            shipping_name VARCHAR(150) DEFAULT NULL,
            shipping_phone VARCHAR(50) DEFAULT NULL,
            shipping_address TEXT DEFAULT NULL,
            shipping_city VARCHAR(150) DEFAULT NULL,
            shipping_country VARCHAR(150) DEFAULT NULL,
            assign_to longtext DEFAULT NULL,
            meta longtext DEFAULT NULL,
            scheduled_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        $sql_order_items = "CREATE TABLE IF NOT EXISTS $table_order_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT(20) UNSIGNED NOT NULL, 
            product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            variation_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            product_name VARCHAR(200) DEFAULT NULL, 
            sku VARCHAR(100) DEFAULT NULL,
            quantity INT  DEFAULT 1,
            price DECIMAL(10,2)  DEFAULT 0.00,
            trade_price DECIMAL(10,2)  DEFAULT 0.00,
            subtotal DECIMAL(10,2)  DEFAULT 0.00,
            tax DECIMAL(10,2)  DEFAULT 0.00,
            discount_type VARCHAR(100) DEFAULT NULL,
            discount_amount DECIMAL(10,2)  DEFAULT 0.00,
            total DECIMAL(10,2)  DEFAULT 0.00,
            attributes JSON DEFAULT NULL,
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
            title VARCHAR(255) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            category VARCHAR(50) DEFAULT NULL,
            payment_method VARCHAR(50) DEFAULT NULL,
            payment_status VARCHAR(100) DEFAULT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            created_by BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
            created_for BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
            billing_name VARCHAR(150) DEFAULT NULL,
            billing_email VARCHAR(150) DEFAULT NULL,
            billing_phone VARCHAR(50) DEFAULT NULL,
            billing_address TEXT DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        $sql_expenses_items = "CREATE TABLE IF NOT EXISTS $table_expenses_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            expense_id BIGINT(20) UNSIGNED NOT NULL, 
            product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            product_name VARCHAR(200) DEFAULT NULL, 
            sku VARCHAR(100) DEFAULT NULL,
            quantity INT  DEFAULT 1,
            price DECIMAL(10,2)  DEFAULT 0.00,
            subtotal DECIMAL(10,2)  DEFAULT 0.00,
            tax DECIMAL(10,2)  DEFAULT 0.00,
            total DECIMAL(10,2)  DEFAULT 0.00,
            meta JSON DEFAULT NULL,
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
            status VARCHAR(50) NOT NULL,
            category VARCHAR(50) NOT NULL,
            total_amount DECIMAL(10,2)  DEFAULT 0.00,
            subtotal_amount DECIMAL(10,2)  DEFAULT 0.00,
            discount_amount DECIMAL(10,2)  DEFAULT 0.00,
            tax_amount DECIMAL(10,2)  DEFAULT 0.00,
            shipping_amount DECIMAL(10,2)  DEFAULT 0.00,
            advance_payment DECIMAL(10,2)  DEFAULT 0.00,
            payment_method VARCHAR(50) ,
            payment_status VARCHAR(100) DEFAULT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
            supplier_id BIGINT(20) UNSIGNED DEFAULT NULL, -- FK to wp_users.ID (or NULL for guests)
            billing_name VARCHAR(150) DEFAULT NULL,
            billing_email VARCHAR(150) DEFAULT NULL,
            billing_phone VARCHAR(50) DEFAULT NULL,
            billing_address TEXT DEFAULT NULL,
            note TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY id (id)
        ) $charset_collate;";



        $sql_purchases_meta = "CREATE TABLE IF NOT EXISTS $table_purchases_meta (
            id int(100) NOT NULL AUTO_INCREMENT,
            object_id	bigint(20)		NOT NULL,
            meta_key	VARCHAR( 255 )		NOT NULL,
            meta_value	longtext	NOT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";


        $sql_purchases_items = "CREATE TABLE IF NOT EXISTS $table_purchases_items (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            purchase_id BIGINT(20) UNSIGNED NOT NULL, 
            product_id BIGINT(20) UNSIGNED DEFAULT NULL, 
            product_name VARCHAR(200) DEFAULT NULL, 
            sku VARCHAR(100) DEFAULT NULL,
            quantity INT  DEFAULT 1,
            price DECIMAL(10,2)  DEFAULT 0.00,
            subtotal DECIMAL(10,2)  DEFAULT 0.00,
            tax DECIMAL(10,2)  DEFAULT 0.00,
            total DECIMAL(10,2)  DEFAULT 0.00,
            meta JSON DEFAULT NULL,
            UNIQUE KEY id (id)

        ) $charset_collate;";





        $sql_subscriptions_to_call = "CREATE TABLE IF NOT EXISTS $table_subscriptions_to_call (
            id int(100) NOT NULL AUTO_INCREMENT,
            userid	bigint(20)		NOT NULL,
            product_id	bigint(20)		NOT NULL,
            user_name	VARCHAR( 50 )	DEFAULT NULL,
            `interval`	VARCHAR( 50 )	DEFAULT NULL,
            interval_count	int(20)		DEFAULT NULL,
            start_date  DATETIME DEFAULT NULL,
            next_date  DATETIME DEFAULT NULL,
            phone	VARCHAR( 50 )	DEFAULT NULL,
            status	VARCHAR( 50 )	DEFAULT NULL,
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





        $sql_shipping_zones = "CREATE TABLE IF NOT EXISTS $table_shipping_zones (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            region VARCHAR(30) DEFAULT NULL,
            countries LONGTEXT NULL,
            settings LONGTEXT NULL,
            priority INT  DEFAULT 1,
            status INT  DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";





        $sql_shipping_methods = "CREATE TABLE IF NOT EXISTS $table_shipping_methods (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            zone_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            type VARCHAR(50) DEFAULT NULL,
            amount DECIMAL(10,2)  DEFAULT 0.00,
            description TEXT NULL,
            states LONGTEXT NULL,
            settings LONGTEXT NULL,
            priority INT  DEFAULT 1,
            status INT  DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY zone_id (zone_id)
        ) $charset_collate;";



        //         $sql_shipping_rates = "CREATE TABLE IF NOT EXISTS $table_shipping_rates (
        //     id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        //     method_id BIGINT(20) UNSIGNED NOT NULL,
        //     condition_type VARCHAR(50) NOT NULL,
        //     condition_min DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        //     condition_max DECIMAL(12,2) NOT NULL DEFAULT 99999999.99,
        //     shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
        //     free_shipping TINYINT(1) NOT NULL DEFAULT 0,
        //     status TINYINT(1) NOT NULL DEFAULT 1,
        //     created_at DATETIME NOT NULL,
        //     PRIMARY KEY (id),
        //     KEY method_id (method_id),
        //     KEY condition_type (condition_type),
        //     KEY status (status)
        // ) $charset_collate;";



        //         $sql_shipping_carriers = "CREATE TABLE IF NOT EXISTS $table_shipping_carriers (
        //     id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        //     name VARCHAR(255) NOT NULL,
        //     slug VARCHAR(100) NOT NULL,
        //     api_url VARCHAR(255) NULL,
        //     api_key TEXT NULL,
        //     api_secret TEXT NULL,
        //     webhook_secret TEXT NULL,
        //     tracking_url VARCHAR(255) NULL,
        //     settings LONGTEXT NULL,
        //     status TINYINT(1) NOT NULL DEFAULT 1,
        //     created_at DATETIME NOT NULL,
        //     updated_at DATETIME NOT NULL,
        //     PRIMARY KEY (id),
        //     UNIQUE KEY slug (slug),
        //     KEY status (status)
        // ) $charset_collate;";


        //         $sql_shipping_rules = "CREATE TABLE IF NOT EXISTS $table_shipping_rules (
        //     id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        //     method_id BIGINT(20) UNSIGNED NOT NULL,
        //     rule_type VARCHAR(50) NOT NULL,
        //     rule_operator VARCHAR(20) NOT NULL,
        //     rule_value VARCHAR(255) NOT NULL,
        //     action VARCHAR(50) NOT NULL,
        //     action_value VARCHAR(255) NULL,
        //     priority INT(11) NOT NULL DEFAULT 0,
        //     status TINYINT(1) NOT NULL DEFAULT 1,
        //     created_at DATETIME NOT NULL,
        //     PRIMARY KEY (id),
        //     KEY method_id (method_id),
        //     KEY rule_type (rule_type),
        //     KEY status (status)
        // ) $charset_collate;";





        $sql_affiliates = "CREATE TABLE IF NOT EXISTS $table_affiliates (

            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_code VARCHAR(100) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            commission_type VARCHAR(20) NOT NULL DEFAULT 'percentage',
            commission_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY id (id),
            UNIQUE KEY affiliate_code (affiliate_code),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";




        $sql_affiliate_sales = "CREATE TABLE IF NOT EXISTS $table_affiliate_sales (

            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            order_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_rate DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            approved_at DATETIME DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            UNIQUE KEY id (id),
            UNIQUE KEY order_affiliate (order_id, affiliate_id),
            KEY affiliate_id (affiliate_id),
            KEY order_id (order_id),
            KEY status (status),
            KEY created_at (created_at)

        ) $charset_collate;";


        $sql_affiliate_clicks = "CREATE TABLE IF NOT EXISTS $table_affiliate_clicks (

            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            affiliate_code VARCHAR(100) NOT NULL,
            session_id VARCHAR(100) DEFAULT NULL,
            landing_url TEXT DEFAULT NULL,
            referrer_url TEXT DEFAULT NULL,
            ip_hash VARCHAR(128) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY id (id),
            KEY affiliate_id (affiliate_id),
            KEY affiliate_code (affiliate_code),
            KEY session_id (session_id),
            KEY created_at (created_at)

        ) $charset_collate;";


        $sql_affiliate_payouts = "CREATE TABLE IF NOT EXISTS $table_affiliate_payouts (

            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            payment_method VARCHAR(50) DEFAULT NULL,
            transaction_id VARCHAR(150) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            paid_at DATETIME DEFAULT NULL,
            UNIQUE KEY id (id),
            KEY affiliate_id (affiliate_id),
            KEY status (status),
            KEY transaction_id (transaction_id),
            KEY created_at (created_at)
        ) $charset_collate;";


        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');



        dbDelta($sql_templates);
        dbDelta($sql_template_parts);

        dbDelta($sql_activities);
        dbDelta($sql_deliveries);
        dbDelta($sql_deliveries_trackings);


        dbDelta($sql_orders);
        dbDelta($sql_order_items);
        dbDelta($sql_orders_meta);

        dbDelta($sql_expenses);
        dbDelta($sql_expenses_items);
        dbDelta($sql_expenses_meta);

        dbDelta($sql_purchases);
        dbDelta($sql_purchases_meta);
        dbDelta($sql_purchases_items);




        dbDelta($sql_subscriptions_to_call);
        dbDelta($sql_subscriptions);
        dbDelta($sql_subscriptions_meta);


        dbDelta($sql_shipping_zones);
        dbDelta($sql_shipping_methods);



        // dbDelta($sql_shipping_rates);
        // dbDelta($sql_shipping_carriers);
        // dbDelta($sql_shipping_rules);

        dbDelta($sql_affiliates);
        dbDelta($sql_affiliate_sales);
        dbDelta($sql_affiliate_clicks);
        dbDelta($sql_affiliate_payouts);

        dbDelta($sql_products);
        dbDelta($sql_products_meta);
        dbDelta($sql_products_variations);
        dbDelta($sql_stock_history);



        // $combo_store_info = array();
        // $combo_store_info['db_version'] = 0;

    }
}
