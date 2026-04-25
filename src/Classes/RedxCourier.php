<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access

use Exception;



class RedxCourier
{
    private $api_key;
    private $base_url;

    /**
     * Constructor
     */
    public function __construct()
    {

        $combo_store_settings = get_option('combo_store_settings');
        $courier = isset($combo_store_settings['courier']) ? $combo_store_settings['courier'] : [];
        $redx = isset($courier['redx']) ? $courier['redx'] : [];
        $sandbox = isset($redx['sandbox']) ? $redx['sandbox'] : false;
        $api_key = isset($redx['token']) ? $redx['token'] : '';


        error_log(wp_json_encode($redx));




        $this->api_key = $api_key;

        // Base URLs
        $this->base_url = $sandbox
            ? "https://sandbox.redx.com.bd/v1.0.0-beta"
            : "https://openapi.redx.com.bd/v1.0.0-beta";
    }

    /**
     * Create Parcel (Single Order)
     */
    public function createParcel($data)
    {
        $url = $this->base_url . "/parcel";

        return $this->request("POST", $url, $data);
    }

    public function getAreas()
    {
        $url = $this->base_url . "/areas";

        return $this->request("POST", $url, []);
    }

    /**
     * CURL Request Handler
     */
    private function request($method, $url, $data = [])
    {
        $ch = curl_init();

        $headers = [
            "Content-Type: application/json",
            "Accept: application/json",
            "x-api-key: " . $this->api_key
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            throw new Exception("REDX API Error ({$status}): " . $response);
        }

        return $decoded;
    }
}
