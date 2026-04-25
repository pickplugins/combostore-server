<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access


class SteadfastCourier
{
    private $api_key;
    private $secret_key;
    private $base_url = "https://portal.packzy.com/api/v1";

    public function __construct()
    {

        $settings = get_option('combo_store_settings');

        $apiKeys = isset($settings['apiKeys']) ? $settings['apiKeys'] : [];
        $steadfast = isset($apiKeys['steadfast']) ? $apiKeys['steadfast'] : [];

        error_log($steadfast['key']);
        error_log($steadfast['secret']);

        $this->api_key = isset($steadfast['key']) ? $steadfast['key'] : '';
        $this->secret_key = isset($steadfast['secret']) ? $steadfast['secret'] : '';
    }

    /**
     * CURL Request Handler
     */
    private function request($endpoint, $data = [])
    {
        $url = $this->base_url . $endpoint;

        $headers = [
            "Api-Key: {$this->api_key}",
            "Secret-Key: {$this->secret_key}",
            "Content-Type: application/json"
        ];

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            return [
                "status" => false,
                "error" => curl_error($ch)
            ];
        }

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "status" => $httpcode == 200,
            "http_code" => $httpcode,
            "response" => json_decode($response, true)
        ];
    }

    /**
     * Create Single Order
     */
    public function createOrder($orderData)
    {
        /*
        Required fields:
        invoice
        recipient_name
        recipient_phone
        recipient_address
        cod_amount
        note (optional)
        */

        return $this->request("/create_order", $orderData);
    }

    /**
     * Create Bulk Orders
     */
    public function createBulkOrder($orders = [])
    {
        /*
        $orders must be an array of order objects
        */

        return $this->request("/create_order/bulk-order", $orders);
    }
}
