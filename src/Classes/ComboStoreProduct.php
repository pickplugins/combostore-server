<?php

namespace ComboStore\Classes;

use Error;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreProduct
{
    public $id = "";


    public function __construct($product_id)
    {


        $this->id = $product_id;
    }


    function get_prices()
    {

        $regularPrice = get_post_meta($this->id, 'regularPrice', true);
        $salePrice = get_post_meta($this->id, 'salePrice', true);

        $priceArr = [
            "salePrice" => $salePrice,
            "regularPrice" => $regularPrice,
        ];
    }

    function get_price()
    {

        $regularPrice = get_post_meta($this->id, 'regularPrice', true);
        $salePrice = get_post_meta($this->id, 'salePrice', true);

        if ($salePrice) {
            return $salePrice;
        } else {
            return $regularPrice;
        }
    }

    function update_product_data($prams)
    {

         $product_id   = isset($params['product_id']) ? floatval($params['product_id']) : 0;






        $data = array(
            'product_id'        => $product_id,
            'gallery'  => $gallery,
            'menuOrder'    => $menuOrder,
            'sku'    => $sku,
            'stockStatus'  => $stockStatus,
            'stockCount'  => $stockCount,
            'weight'  => $weight,
            'height'  => $height,
            'width'  => $width,
            'length'  => $length,
            'priceType'  => $priceType,
            'regularPrice'  => $regularPrice,
            'salePrice'  => $salePrice,
            'bulkPrices'  => $bulkPrices,
            'variablePrices'  => $variablePrices,
            'pwywMinPrice'      => $pwywMinPrice,
            'pwywDefaultPrice'     => $pwywDefaultPrice,
            'bargainMinPrice'            => $bargainMinPrice,
            'bargainDefaultPrice'            => $bargainDefaultPrice,
            'tradePrice'            => $tradePrice,
            'addons'            => $addons,
            'downloads'            => $downloads,
            'upsells'            => $upsells,
            'crosssells'            => $crosssells,
            'faq'            => $faq,
            'variations'            => $variations,
            'relatedProducts'            => $relatedProducts,
            'created_at'      => $created_at,
            'updated_at'      => get_date_from_gmt(current_time('mysql'), 'Y-m-d H:i:s'),
        );

        $format = array(
            '%d',   // product_id
            '%s',   // gallery
            '%d',   // menuOrder
            '%s',   // sku
            '%s',   // stockStatus
            '%d',   // stockCount
            '%f',   // weight
            '%f',   // height
            '%s',   // width
            '%s',   // length
            '%s',   // priceType
            '%f',   // regularPrice
            '%f',   // salePrice
            '%s',   // bulkPrices
            '%s',   // variablePrices
            '%f',   // pwywMinPrice
            '%f',   // pwywDefaultPrice
            '%f',   // bargainMinPrice
            '%f',   // bargainDefaultPrice
            '%f',   // tradePrice
            '%s',   // addons
            '%s',   // downloads
            '%s',   // upsells
            '%s',   // crosssells
            '%s',   // faq
            '%s',   // variations
            '%s',   // relatedProducts
            '%s',    // created_at
            '%s'    // updated_at
        );

        // ✅ WHERE clause
        $where = array('id' => $purchase_id);
        $where_format = array('%d');

        // ✅ Perform update
        $updated = $wpdb->update($table, $data, $where, $format, $where_format);







    }




    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
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
