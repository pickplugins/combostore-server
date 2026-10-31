<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreProduct
{
    public $id = "";


    // public function __construct($product_id)
    // {


    //     $this->id = $product_id;
    // }

    function insert_product($prams)
    {



        $response = [];


        global $wpdb;
        $table_products = $wpdb->prefix . "cstore_products";



        $product_id = isset($prams['product_id'])  ? intval($prams['product_id']) : null;


        if (!$product_id) {
            $response['status'] = 'failed';
            return $response;
        }




        $gallery = isset($prams['gallery'])  ? wp_json_encode($prams['gallery']) : null;
        $menu_order = isset($prams['menu_order'])  ? sanitize_text_field($prams['menu_order']) : null;
        $sku = isset($prams['sku'])  ? sanitize_text_field($prams['sku']) : null;
        $stock_status = isset($prams['stock_status'])  ? sanitize_text_field($prams['stock_status']) : '';
        $stock_count = isset($prams['stock_count'])  ? intval($prams['stock_count']) : null;
        $weight = isset($prams['weight'])  ? floatval($prams['weight']) : 0.00;
        $height = isset($prams['height'])  ? floatval($prams['height']) : 0.00;
        $width = isset($prams['width'])  ? floatval($prams['width']) : 0.00;
        $length = isset($prams['length'])  ? floatval($prams['length']) : 0.00;
        $price_type = isset($prams['price_type'])  ? sanitize_text_field($prams['price_type']) : '';
        $regular_price = isset($prams['regular_price'])  ? floatval($prams['regular_price']) : '';
        $sale_price = isset($prams['sale_price'])  ? floatval($prams['sale_price']) : '';
        $bulk_prices = isset($prams['bulk_prices'])  ? wp_json_encode($prams['bulk_prices']) : null;
        $pwyw_min_price = isset($prams['pwyw_min_price'])  ? floatval($prams['pwyw_min_price']) : null;
        $pwyw_default_price = isset($prams['pwyw_default_price'])  ? floatval($prams['pwyw_default_price']) : null;
        $bargain_min_price = isset($prams['bargain_min_price'])  ? floatval($prams['bargain_min_price']) : null;
        $bargain_default_price = isset($prams['bargain_default_price'])  ? floatval($prams['bargain_default_price'])   : null;

        $trade_price = isset($prams['trade_price'])  ? floatval($prams['trade_price']) : null;
        $addons = isset($prams['addons'])  ? wp_json_encode($prams['addons']) : null;
        $downloads = isset($prams['downloads'])  ? wp_json_encode($prams['downloads']) : null;
        $upsells = isset($prams['upsells'])  ? wp_json_encode($prams['upsells']) : null;
        $crosssells = isset($prams['crosssells'])  ? wp_json_encode($prams['crosssells']) : null;
        $faq = isset($prams['faq'])  ? wp_json_encode($prams['faq']) : null;
        $attributes = isset($prams['attributes'])  ? wp_json_encode($prams['attributes']) : null;
        $variations = isset($prams['variations'])  ? wp_json_encode($prams['variations']) : null;
        $product_template = isset($prams['product_template'])  ? wp_json_encode($prams['product_template']) : null;
        $total_rate = isset($prams['total_rate'])  ? intval($prams['total_rate'])   : null;
        $avg_rating = isset($prams['avg_rating'])  ?  floatval($prams['avg_rating'])   : null;

        // Insert product data
        $data = array(
            'product_id'              => $product_id,
            'gallery'              => $gallery,
            'menu_order'            => $menu_order,
            'sku'                  => $sku,
            'stock_status'         => $stock_status,
            'stock_count'          => $stock_count,
            'weight'               => $weight,
            'height'               => $height,
            'width'                => $width,
            'length'               => $length,
            'price_type'           => $price_type,
            'regular_price'        => $regular_price,
            'sale_price'           => $sale_price,
            'bulk_prices'          => $bulk_prices,
            'pwyw_min_price'       => $pwyw_min_price,
            'pwyw_default_price'   => $pwyw_default_price,
            'bargain_min_price'    => $bargain_min_price,
            'bargain_default_price' => $bargain_default_price,
            'trade_price'          => $trade_price,
            'addons'               => $addons,
            'downloads'            => $downloads,
            'upsells'              => $upsells,
            'crosssells'           => $crosssells,
            'faq'                  => $faq,
            'attributes'           => $attributes,
            'avg_rating'           => $avg_rating,
            'total_rate'           => $total_rate,
            'product_template'           => $product_template,
        );


        // Format must match $data order exactly
        $format = array(
            '%d', // product_id
            '%s', // gallery
            '%s', // menu_order
            '%s', // sku
            '%s', // stock_status
            '%d', // stock_count
            '%f', // weight
            '%f', // height
            '%f', // width
            '%f', // length
            '%s', // price_type
            '%s', // regular_price
            '%s', // sale_price
            '%s', // bulk_prices
            '%s', // pwyw_min_price
            '%s', // pwyw_default_price
            '%s', // bargain_min_price
            '%s', // bargain_default_price
            '%f', // trade_price
            '%s', // addons
            '%s', // downloads
            '%s', // upsells
            '%s', // crosssells
            '%s', // faq
            '%s', // attributes
            '%f', // avg_rating
            '%d', // total_rate
            '%s', // product_template
        );


        $wpdb->insert($table_products, $data, $format);

        // Get inserted row ID
        $item_id = $wpdb->insert_id;



        if ($wpdb->last_error) {


            $response['status'] = 'failed';
        } else {


            if ($variations) {

                $prams = [
                    "product_id" => $item_id,
                    "variations" => $variations,
                ];

                // foreach ($variations as $variation) {

                //     $this->update_variation($variation);
                // }

                $this->update_product_variations($prams);
            }









            $response['status'] = 'success';
        }

        return $response;

        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    function update_product($prams)
    {



        $responses = [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products';

        error_log(wp_json_encode($prams));

        $product_id = isset($prams['id'])  ? intval($prams['id']) : null;

        $gallery = isset($prams['gallery'])  ? wp_json_encode($prams['gallery']) : null;
        $menu_order = isset($prams['menu_order'])  ? sanitize_text_field($prams['menu_order']) : null;
        $slugLock = isset($prams['slugLock'])  ? (bool)($prams['slugLock']) : null;
        $sku = isset($prams['sku'])  ? sanitize_text_field($prams['sku']) : null;
        $stock_status = isset($prams['stock_status'])  ? sanitize_text_field($prams['stock_status']) : null;
        $stock_count = isset($prams['stock_count'])  ? intval($prams['stock_count']) : null;
        $weight = isset($prams['weight'])  ? floatval($prams['weight']) : null;
        $height = isset($prams['height'])  ? floatval($prams['height']) : null;
        $width = isset($prams['width'])  ? floatval($prams['width']) : null;
        $length = isset($prams['length'])  ? floatval($prams['length']) : null;
        $price_type = isset($prams['price_type'])  ? sanitize_text_field($prams['price_type']) : null;
        $regular_price = isset($prams['regular_price'])  ? sanitize_text_field($prams['regular_price']) : null;
        $sale_price = isset($prams['sale_price'])  ? sanitize_text_field($prams['sale_price']) : null;
        $bulk_prices = isset($prams['bulk_prices'])  ? wp_json_encode($prams['bulk_prices']) : null;
        $pwyw_min_price = isset($prams['pwyw_min_price'])  ? sanitize_text_field($prams['pwyw_min_price']) : null;
        $pwyw_default_price = isset($prams['pwyw_default_price'])  ? sanitize_text_field($prams['pwyw_default_price']) : null;
        $bargain_min_price = isset($prams['bargain_min_price'])  ? sanitize_text_field($prams['bargain_min_price']) : null;
        $bargain_default_price = isset($prams['bargain_default_price'])  ? sanitize_text_field($prams['bargain_default_price'])   : null;
        $trade_price = isset($prams['trade_price'])  ? sanitize_text_field($prams['trade_price']) : null;
        $addons = isset($prams['addons'])  ? wp_json_encode($prams['addons']) : null;
        $downloads = isset($prams['downloads'])  ? wp_json_encode($prams['downloads']) : null;
        $upsells = isset($prams['upsells'])  ? wp_json_encode($prams['upsells']) : null;
        $crosssells = isset($prams['crosssells'])  ? wp_json_encode($prams['crosssells']) : null;
        $faq = isset($prams['faq'])  ? wp_json_encode($prams['faq']) : null;
        $attributes = isset($prams['attributes'])  ? wp_json_encode($prams['attributes']) : null;
        $product_template = isset($prams['product_template'])  ? wp_json_encode($prams['product_template']) : null;
        $variations = isset($prams['variations'])  ? ($prams['variations']) : null;

        $total_rate = isset($prams['total_rate'])  ? intval($prams['total_rate'])   : null;
        $avg_rating = isset($prams['avg_rating'])  ?  floatval($prams['avg_rating'])   : null;


        $product = $this->get_product(["product_id" => $product_id,]);



        if (!$product) {

            $prams['product_id'] = $product_id;

            $this->insert_product($prams);

            return;
        }









        // Insert product data
        $data = array(
            'product_id'           => $product_id,
            'gallery'              => $gallery,
            'menu_order'            => $menu_order,
            'sku'                  => $sku,
            'stock_status'         => $stock_status,
            'stock_count'          => $stock_count,
            'weight'               => $weight,
            'height'               => $height,
            'width'                => $width,
            'length'               => $length,
            'price_type'           => $price_type,
            'regular_price'        => $regular_price,
            'sale_price'           => $sale_price,
            'bulk_prices'          => $bulk_prices,
            'pwyw_min_price'       => $pwyw_min_price,
            'pwyw_default_price'   => $pwyw_default_price,
            'bargain_min_price'    => $bargain_min_price,
            'bargain_default_price' => $bargain_default_price,
            'trade_price'          => $trade_price,
            'addons'               => $addons,
            'downloads'            => $downloads,
            'upsells'              => $upsells,
            'crosssells'           => $crosssells,
            'faq'                  => $faq,
            'attributes'                  => $attributes,
            'total_rate'                  => $total_rate,
            'avg_rating'                  => $avg_rating,
            'product_template'                  => $product_template,
        );


        // // Format must match $data order exactly
        // $format = array(
        //     '%d', // product_id
        //     '%s', // gallery
        //     '%d', // menu_order
        //     '%s', // sku
        //     '%s', // stock_status
        //     '%d', // stock_count
        //     '%f', // weight
        //     '%f', // height
        //     '%f', // width
        //     '%f', // length
        //     '%s', // price_type
        //     '%s', // regular_price
        //     '%s', // sale_price
        //     '%s', // bulk_prices
        //     '%s', // pwyw_min_price
        //     '%s', // pwyw_default_price
        //     '%s', // bargain_min_price
        //     '%s', // bargain_default_price
        //     '%f', // trade_price
        //     '%s', // addons
        //     '%s', // downloads
        //     '%s', // upsells
        //     '%s', // crosssells
        //     '%s', // faq
        //     '%s', // attributes
        //     '%d', // total_rate
        //     '%f', // avg_rating
        //     '%s', // product_template
        // );


        // Format map
        $format_map = array(
            'product_id'           => '%d',
            'gallery'              => '%s',
            'menu_order'           => '%d',
            'sku'                  => '%s',
            'stock_status'         => '%s',
            'stock_count'          => '%d',
            'weight'               => '%f',
            'height'               => '%f',
            'width'                => '%f',
            'length'               => '%f',
            'price_type'           => '%s',
            'regular_price'        => '%s',
            'sale_price'           => '%s',
            'bulk_prices'          => '%s',
            'pwyw_min_price'       => '%s',
            'pwyw_default_price'   => '%s',
            'bargain_min_price'    => '%s',
            'bargain_default_price' => '%s',
            'trade_price'          => '%f',
            'addons'               => '%s',
            'downloads'            => '%s',
            'upsells'              => '%s',
            'crosssells'           => '%s',
            'faq'                  => '%s',
            'attributes'           => '%s',
            'total_rate'           => '%d',
            'avg_rating'           => '%f',
            'product_template'     => '%s',
        );


        // Filter only non-empty values
        $filtered_data   = array();
        $filtered_format = array();

        foreach ($data as $key => $value) {

            // Skip NULL or empty string
            if ($value !== 0 && $value !== null && $value !== '') {

                $filtered_data[$key]   = $value;
                $filtered_format[]     = $format_map[$key];
            }
        }


        $where = array('product_id' => $product_id);
        $where_format = array('%d');

        $updated = $wpdb->update(
            $table,
            $filtered_data,
            $where,
            $filtered_format,
            $where_format
        );

        // ✅ WHERE clause
        // $where = array('product_id' => $product_id);
        // $where_format = array('%d');

        // // ✅ Perform update
        // $updated = $wpdb->update($table, $data, $where, $format, $where_format);


        error_log(wp_json_encode($updated));


        if ($variations) {
            $product = $this->get_product(["product_id" => $product_id,]);


            $prams = [
                "product_id" => $product_id,
                "variations" => $variations,
            ];

            // foreach ($variations as $variation) {

            //     $this->update_variation($variation);
            // }

            $this->update_product_variations($prams);
        }





        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        $product_data_id = isset($product['id']) ? $product['id'] : null;



        if ($slugLock) {

            if ($product_data_id) {
                $ComboStoreObjectMeta->update_meta('products', $product_data_id, "slugLock", $slugLock);
            }
        } else {
            $ComboStoreObjectMeta->delete_meta('products', $product_data_id, "slugLock");
        }


        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }



        return $responses;
    }





    function insert_product_variations($prams)
    {


        $response = [];

        $variations = isset($prams['variations']) ? $prams['variations'] : '';
        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_products_variations";

        foreach ($variations as $variation) {

            $title = isset($variation['title'])        ? sanitize_text_field($variation['title'])        : '';
            $description = isset($variation['description'])        ? sanitize_text_field($variation['description'])        : '';

            $thumbnail = isset($variation['thumbnail'])        ? intval($variation['thumbnail'])        : null;
            $sku = isset($variation['sku'])        ? sanitize_text_field($variation['sku'])        : null;
            $enable = isset($variation['enable'])        ? sanitize_text_field($variation['enable'])        : 'yes';
            $downloadable = isset($variation['downloadable'])        ? sanitize_text_field($variation['downloadable'])        : 'no';
            $stock_status = isset($variation['stock_status'])        ? sanitize_text_field($variation['stock_status'])        : 'instock';
            $stock_count = isset($variation['stock_count'])        ? intval($variation['stock_count'])        : null;
            $regular_price = isset($variation['regular_price'])        ? sanitize_text_field($variation['regular_price'])        : '';
            $sale_price = isset($variation['sale_price'])        ? sanitize_text_field($variation['sale_price'])        : '';
            $trade_price = isset($variation['trade_price'])        ? floatval($variation['trade_price'])        : 0.00;
            $weight = isset($variation['weight'])        ? floatval($variation['weight'])        : null;
            $height = isset($variation['height'])        ? floatval($variation['height'])        : null;
            $width = isset($variation['width'])        ? floatval($variation['width'])        : null;
            $length = isset($variation['length'])        ? floatval($variation['length'])        : null;
            $backorders = isset($variation['backorders'])        ? sanitize_text_field($variation['backorders'])        : 'no';
            $shipping_class = isset($variation['shipping_class'])        ? sanitize_text_field($variation['shipping_class'])        : '';
            $attributes = isset($variation['attributes'])        ? wp_json_encode($variation['attributes'])        : null;
            $meta = isset($variation['meta'])        ? wp_json_encode($variation['meta'])        : null;


            // Insert variation Tarminal, Bottala
            $data = array(
                'product_id'      => $product_id,
                'thumbnail'       => $thumbnail,
                'title' => $title,
                'description' => $description,
                'sku'             => $sku,
                'enable'          => $enable,
                'downloadable'    => $downloadable,
                'stock_status'    => $stock_status,
                'stock_count'     => $stock_count,
                'regular_price'   => $regular_price,
                'sale_price'      => $sale_price,
                'trade_price'     => $trade_price,
                'weight'          => $weight,
                'height'          => $height,
                'width'           => $width,
                'length'          => $length,
                'backorders'      => $backorders,
                'shipping_class'  => $shipping_class,
                'attributes'      => $attributes,
                'meta'            => $meta,
            );


            // Format must match $data order exactly
            $format = array(
                '%d', // product_id
                '%d', // thumbnail
                '%s', // title
                '%s', // description
                '%s', // sku
                '%s', // enable
                '%s', // downloadable
                '%s', // stock_status
                '%d', // stock_count
                '%s', // regular_price
                '%s', // sale_price
                '%f', // trade_price
                '%f', // weight
                '%f', // height
                '%f', // width
                '%f', // length
                '%s', // backorders
                '%s', // shipping_class
                '%s', // attributes
                '%s', // meta
            );


            $wpdb->insert($table, $data, $format);

            // Get inserted item_id
            $item_id = $wpdb->insert_id;
        }


        if ($wpdb->last_error) {
            $response['status'] = 'failed';
        } else {
            $response['status'] = 'success';
        }

        return $response;

        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    function update_product_variations($prams)
    {

        $variations = isset($prams['variations']) ? $prams['variations'] : '';
        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';


        global $wpdb;
        $table = $wpdb->prefix . "cstore_products_variations";



        $this->delete_product_variations($product_id);


        foreach ($variations as $variation) {

            $title = isset($variation['title'])        ? sanitize_text_field($variation['title'])        : '';
            $description = isset($variation['description'])        ? wp_kses_post($variation['description'])        : '';

            $thumbnail = isset($variation['thumbnail'])        ? wp_json_encode($variation['thumbnail'])        : null;
            $sku = isset($variation['sku'])        ? sanitize_text_field($variation['sku'])        : null;
            $enable = isset($variation['enable'])        ? sanitize_text_field($variation['enable'])        : 'yes';
            $downloadable = isset($variation['downloadable'])        ? sanitize_text_field($variation['downloadable'])        : 'no';
            $stock_status = isset($variation['stock_status'])        ? sanitize_text_field($variation['stock_status'])        : 'instock';
            $stock_count = isset($variation['stock_count'])        ? intval($variation['stock_count'])        : null;
            $regular_price = isset($variation['regular_price'])        ? sanitize_text_field($variation['regular_price'])        : '';
            $sale_price = isset($variation['sale_price'])        ? sanitize_text_field($variation['sale_price'])        : '';
            $trade_price = isset($variation['trade_price'])        ? floatval($variation['trade_price'])        : 0.00;
            $weight = isset($variation['weight'])        ? floatval($variation['weight'])        : null;
            $height = isset($variation['height'])        ? floatval($variation['height'])        : null;
            $width = isset($variation['width'])        ? floatval($variation['width'])        : null;
            $length = isset($variation['length'])        ? floatval($variation['length'])        : null;
            $backorders = isset($variation['backorders'])        ? sanitize_text_field($variation['backorders'])        : 'no';
            $shipping_class = isset($variation['shipping_class'])        ? sanitize_text_field($variation['shipping_class'])        : '';
            $attributes = isset($variation['attributes'])        ? wp_json_encode($variation['attributes'])        : null;
            $meta = isset($variation['meta'])        ? wp_json_encode($variation['meta'])        : null;


            // Insert variation
            $data = array(
                'product_id'      => $product_id,
                'thumbnail'       => $thumbnail,
                'title' => $title,
                'description' => $description,
                'sku'             => $sku,
                'enable'          => $enable,
                'downloadable'    => $downloadable,
                'stock_status'    => $stock_status,
                'stock_count'     => $stock_count,
                'regular_price'   => $regular_price,
                'sale_price'      => $sale_price,
                'trade_price'     => $trade_price,
                'weight'          => $weight,
                'height'          => $height,
                'width'           => $width,
                'length'          => $length,
                'backorders'      => $backorders,
                'shipping_class'  => $shipping_class,
                'attributes'            => $attributes,
                'meta'            => $meta,
            );


            // Format must match $data order exactly
            $format = array(
                '%d', // product_id
                '%s', // thumbnail
                '%s', // title
                '%s', // description
                '%s', // sku
                '%s', // enable
                '%s', // downloadable
                '%s', // stock_status
                '%d', // stock_count
                '%s', // regular_price
                '%s', // sale_price
                '%f', // trade_price
                '%f', // weight
                '%f', // height
                '%f', // width
                '%f', // length
                '%s', // backorders
                '%s', // shipping_class
                '%s', // attributes
                '%s', // meta
            );


            $wpdb->insert($table, $data, $format);

            // Get inserted item_id
            $item_id = $wpdb->insert_id;
        }



        if ($wpdb->last_error) {
            $response['status'] = 'failed';
        } else {
            $response['status'] = 'success';
        }

        return $response;


        // if ($purchase_count > 0) {
        //     return true;
        // } else {
        //     return false;
        // }
    }

    function update_variation($prams)
    {



        $responses = [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . "cstore_products_variations";


        $variation_id = isset($prams['id'])  ? intval($prams['id']) : null;
        $product_id = isset($prams['product_id'])  ? intval($prams['product_id']) : null;

        $title = isset($prams['title'])        ? sanitize_text_field($prams['title'])        : '';
        $description = isset($prams['description'])        ? sanitize_text_field($prams['description'])        : '';

        $thumbnail = isset($prams['thumbnail'])        ? wp_json_encode($prams['thumbnail'])        : null;
        $sku = isset($prams['sku'])        ? sanitize_text_field($prams['sku'])        : null;
        $enable = isset($prams['enable'])        ? sanitize_text_field($prams['enable'])        : 'yes';
        $downloadable = isset($prams['downloadable'])        ? sanitize_text_field($prams['downloadable'])        : 'no';
        $stock_status = isset($prams['stock_status'])        ? sanitize_text_field($prams['stock_status'])        : 'instock';
        $stock_count = isset($prams['stock_count'])        ? intval($prams['stock_count'])        : null;
        $regular_price = isset($prams['regular_price'])        ? sanitize_text_field($prams['regular_price'])        : '';
        $sale_price = isset($prams['sale_price'])        ? sanitize_text_field($prams['sale_price'])        : '';
        $trade_price = isset($prams['trade_price'])        ? floatval($prams['trade_price'])        : 0.00;
        $weight = isset($prams['weight'])        ? floatval($prams['weight'])        : null;
        $height = isset($prams['height'])        ? floatval($prams['height'])        : null;
        $width = isset($prams['width'])        ? floatval($prams['width'])        : null;
        $length = isset($prams['length'])        ? floatval($prams['length'])        : null;
        $backorders = isset($prams['backorders'])        ? sanitize_text_field($prams['backorders'])        : 'no';
        $shipping_class = isset($prams['shipping_class'])        ? sanitize_text_field($prams['shipping_class'])        : '';
        $attributes = isset($prams['attributes'])        ? wp_json_encode($prams['attributes'])        : null;
        $meta = isset($prams['meta'])        ? wp_json_encode($prams['meta'])        : null;


        // Insert product data
        $data = array(
            'thumbnail'       => $thumbnail,
            'title' => $title,
            'description' => $description,
            'sku'             => $sku,
            'enable'          => $enable,
            'downloadable'    => $downloadable,
            'stock_status'    => $stock_status,
            'stock_count'     => $stock_count,
            'regular_price'   => $regular_price,
            'sale_price'      => $sale_price,
            'trade_price'     => $trade_price,
            'weight'          => $weight,
            'height'          => $height,
            'width'           => $width,
            'length'          => $length,
            'backorders'      => $backorders,
            'shipping_class'  => $shipping_class,
            'attributes'            => $attributes,
            'meta'            => $meta,
        );


        // Format must match $data order exactly
        $format = array(
            '%s', // thumbnail
            '%s', // title
            '%s', // description
            '%s', // sku
            '%s', // enable
            '%s', // downloadable
            '%s', // stock_status
            '%d', // stock_count
            '%s', // regular_price
            '%s', // sale_price
            '%f', // trade_price
            '%f', // weight
            '%f', // height
            '%f', // width
            '%f', // length
            '%s', // backorders
            '%s', // shipping_class
            '%s', // meta
            '%s', // meta
        );



        // ✅ WHERE clause
        $where = array('id' => $variation_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);




        if ($updated) {
            $responses['success'] = true;
        } else {
            $responses['success'] = false;
        }



        return $responses;
    }
    function add_variation($prams)
    {



        $responses = [];


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . "cstore_products_variations";

        $product_id = isset($prams['product_id'])  ? intval($prams['product_id']) : null;

        $title = isset($prams['title'])        ? sanitize_text_field($prams['title'])        : '';
        $description = isset($prams['description'])        ? sanitize_text_field($prams['description'])        : '';

        $thumbnail = isset($prams['thumbnail'])        ? intval($prams['thumbnail'])        : null;
        $sku = isset($prams['sku'])        ? sanitize_text_field($prams['sku'])        : null;
        $enable = isset($prams['enable'])        ? sanitize_text_field($prams['enable'])        : 'yes';
        $downloadable = isset($prams['downloadable'])        ? sanitize_text_field($prams['downloadable'])        : 'no';
        $stock_status = isset($prams['stock_status'])        ? sanitize_text_field($prams['stock_status'])        : 'instock';
        $stock_count = isset($prams['stock_count'])        ? intval($prams['stock_count'])        : null;
        $regular_price = isset($prams['regular_price'])        ? sanitize_text_field($prams['regular_price'])        : '';
        $sale_price = isset($prams['sale_price'])        ? sanitize_text_field($prams['sale_price'])        : '';
        $trade_price = isset($prams['trade_price'])        ? floatval($prams['trade_price'])        : 0.00;
        $weight = isset($prams['weight'])        ? floatval($prams['weight'])        : null;
        $height = isset($prams['height'])        ? floatval($prams['height'])        : null;
        $width = isset($prams['width'])        ? floatval($prams['width'])        : null;
        $length = isset($prams['length'])        ? floatval($prams['length'])        : null;
        $backorders = isset($prams['backorders'])        ? sanitize_text_field($prams['backorders'])        : 'no';
        $shipping_class = isset($prams['shipping_class'])        ? sanitize_text_field($prams['shipping_class'])        : '';
        $attributes = isset($prams['attributes'])        ? wp_json_encode($prams['attributes'])        : null;
        $meta = isset($prams['meta'])        ? wp_json_encode($prams['meta'])        : null;


        // Insert variation
        $data = array(
            'product_id'      => $product_id,
            'thumbnail'       => $thumbnail,
            'title' => $title,
            'description' => $description,
            'sku'             => $sku,
            'enable'          => $enable,
            'downloadable'    => $downloadable,
            'stock_status'    => $stock_status,
            'stock_count'     => $stock_count,
            'regular_price'   => $regular_price,
            'sale_price'      => $sale_price,
            'trade_price'     => $trade_price,
            'weight'          => $weight,
            'height'          => $height,
            'width'           => $width,
            'length'          => $length,
            'backorders'      => $backorders,
            'shipping_class'  => $shipping_class,
            'attributes'            => $attributes,
            'meta'            => $meta,
        );


        // Format must match $data order exactly
        $format = array(
            '%d', // product_id
            '%d', // thumbnail
            '%s', // title
            '%s', // description
            '%s', // sku
            '%s', // enable
            '%s', // downloadable
            '%s', // stock_status
            '%d', // stock_count
            '%s', // regular_price
            '%s', // sale_price
            '%f', // trade_price
            '%f', // weight
            '%f', // height
            '%f', // width
            '%f', // length
            '%s', // backorders
            '%s', // shipping_class
            '%s', // meta
            '%s', // meta
        );


        $wpdb->insert($table, $data, $format);

        // Get inserted item_id
        $item_id = $wpdb->insert_id;

        if ($wpdb->last_error) {
            $response['status'] = 'failed';
        } else {
            $response['status'] = 'success';
        }



        return $response;
    }


    function get_variations($prams)
    {

        $product_id = isset($prams['product_id'])  ? intval($prams['product_id']) : null;

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products_variations';

        if ($product_id) {
            $query = $wpdb->prepare(
                "SELECT * FROM $table WHERE product_id = %d",
                $product_id
            );
        }



        $order_items = $wpdb->get_results($query);


        return $order_items;
    }

    function get_variation($variation_id)
    {


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products_variations';


        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $variation_id
        );

        $order_items = $wpdb->get_results($query);





        return $order_items;
    }



    function delete_product_variations($product_id)
    {

        // $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';
        // $item_id = isset($prams['item_id']) ? $prams['item_id'] : '';




        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products_variations';


        $response = [];

        if (!$product_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('product_id' => $product_id), array('%d'));



        $response['status'] = $status;

        return $response;
    }


    function delete_variation($prams)
    {

        $id = isset($prams['id']) ? $prams['id'] : '';
        // $item_id = isset($prams['item_id']) ? $prams['item_id'] : '';




        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products_variations';


        $response = [];

        if (!$id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $id), array('%d'));



        $response['status'] = $status;

        return $response;
    }









    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }






    function get_product($prams)
    {

        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products';

        $product = null;



        if ($product_id) {
            $product = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE product_id = %d",
                    $product_id
                ),
                ARRAY_A
            );
        }

        if ($product) {
            $ComboStoreObjectMeta = new ComboStoreObjectMeta();

            $slugLock = $ComboStoreObjectMeta->get_meta('products', $product_id, "slugLock");

            $product['slugLock'] = (bool)$slugLock;
        }




        return $product;
    }
    function get_product_props($prams, $props)
    {

        $product_id = isset($prams['product_id']) ? $prams['product_id'] : '';
        $props = isset($prams['props']) ? $prams['props'] : [];

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_products';

        $product = null;
        $response = [];



        if ($product_id) {
            $product = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE product_id = %d",
                    $product_id
                ),
                ARRAY_A
            );
        }

        if ($product) {
            $ComboStoreObjectMeta = new ComboStoreObjectMeta();

            $slugLock = $ComboStoreObjectMeta->get_meta('products', $product_id, "slugLock");

            $product['slugLock'] = (bool)$slugLock;
        }


        error_log(wp_json_encode($props));
        error_log(wp_json_encode($product));


        foreach ($props as $prop) {


            $response[$prop] = $product->{$prop};
        }



        return $response;
    }


    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = gmdate('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = gmdate('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}
