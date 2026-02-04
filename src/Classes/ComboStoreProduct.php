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
