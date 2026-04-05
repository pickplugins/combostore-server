<?php

namespace ComboStore\Classes;



if (!defined('ABSPATH')) exit;  // if direct access

use Exception;


class PathaoCourier
{
    private $client_id;
    private $client_secret;
    private $username;
    private $password;
    private $base_url;
    private $access_token;

    public function __construct()
    {


        $combo_store_settings = get_option('combo_store_settings');
        $courier = isset($combo_store_settings['courier']) ? $combo_store_settings['courier'] : [];
        $pathao = isset($courier['pathao']) ? $courier['pathao'] : [];
        $sandbox = isset($pathao['sandbox']) ? $pathao['sandbox'] : false;


error_log(wp_json_encode($pathao));

        if($sandbox){
        $base_url = 'https://courier-api-sandbox.pathao.com';
        }else{
        $base_url = 'https://courier-api.pathao.com';
        }

        $this->client_id     = $pathao['client_id'];
        $this->client_secret = $pathao['client_secret'];
        $this->username      = $pathao['username'];
        $this->password      = $pathao['password'];
        $this->base_url      = rtrim($base_url, '/');
    }

    /**
     * Generate Access Token
     */
    public function authenticate()
    {
        $url = $this->base_url . "/aladdin/api/v1/issue-token";

        $data = [
            "client_id"     => $this->client_id,
            "client_secret" => $this->client_secret,
            "grant_type"    => "password",
            "username"      => $this->username,
            "password"      => $this->password
        ];

        $response = $this->request("POST", $url, $data, false);

        if (!isset($response['access_token'])) {
            throw new Exception("Authentication failed: " . json_encode($response));
        }

        $this->access_token = $response['access_token'];
        return $this->access_token;
    }

    /**
     * Create Single Order
     */
    public function createOrder($orderData)
    {
        if (!$this->access_token) {
            $this->authenticate();
        }

        $url = $this->base_url . "/aladdin/api/v1/orders";

        return $this->request("POST", $url, $orderData, true);
    }

    /**
     * Create Bulk Orders
     */
    public function createBulkOrder($orders = [])
    {
        if (!$this->access_token) {
            $this->authenticate();
        }

        $url = $this->base_url . "/aladdin/api/v1/orders/bulk";

        return $this->request("POST", $url, ["orders" => $orders], true);
    }

    /**
     * CURL Request Handler
     */
    private function request($method, $url, $data = [], $auth = true)
    {
        $ch = curl_init();

        $headers = [
            "Content-Type: application/json"
        ];

        if ($auth && $this->access_token) {
            $headers[] = "Authorization: Bearer " . $this->access_token;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            throw new Exception("API Error ({$status}): " . $response);
        }

        return $decoded;
    }
}
