<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit; // if direct access

use Exception;

class PathaoCourier
{
    const SANDBOX_URL = 'https://courier-api-sandbox.pathao.com';
    const LIVE_URL    = 'https://api-hermes.pathao.com';

    private $client_id;
    private $client_secret;
    private $username;
    private $password;
    private $base_url;
    private $sandbox;
    private $access_token;

    public function __construct()
    {
        $settings = get_option('combo_store_settings', []);
        $pathao   = $settings['couriers']['pathao'] ?? [];

        // "0", "false", "" — সবই সঠিকভাবে false হবে
        $this->sandbox  = filter_var($pathao['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->base_url = $this->sandbox ? self::SANDBOX_URL : self::LIVE_URL;

        // wp_unslash: WordPress $_POST-এ slash যোগ করে (' " \ থাকলে পাসওয়ার্ড বদলে যায়)
        // trim: কপি-পেস্টের সময় আসা স্পেস/নিউলাইন বাদ
        $this->client_id     = trim(wp_unslash((string) ($pathao['client_id'] ?? '')));
        $this->client_secret = trim(wp_unslash((string) ($pathao['client_secret'] ?? '')));
        $this->username      = trim(wp_unslash((string) ($pathao['username'] ?? '')));
        $this->password      = wp_unslash((string) ($pathao['password'] ?? '')); // পাসওয়ার্ড trim করা হয়নি ইচ্ছাকৃতভাবে

        // ⚠️ আগের কোডে error_log(wp_json_encode($pathao)) ছিল — এতে client_secret ও
        // পাসওয়ার্ড debug.log-এ plain text-এ লেখা হচ্ছিল। সরিয়ে দেওয়া হয়েছে।
    }

    /**
     * Token cache key — মোড ও client_id অনুযায়ী আলাদা
     */
    private function token_cache_key()
    {
        return 'combo_store_pathao_token_' . md5(($this->sandbox ? 'sandbox' : 'live') . '|' . $this->client_id);
    }

    /**
     * Generate Access Token (cache সহ — প্রতি অর্ডারে নতুন token নেওয়ার দরকার নেই)
     */
    public function authenticate($force = false)
    {
        $cache_key = $this->token_cache_key();

        if (!$force) {
            $cached = get_transient($cache_key);
            if ($cached) {
                $this->access_token = $cached;
                return $this->access_token;
            }
        }

        if ($this->client_id === '' || $this->client_secret === '' || $this->username === '' || $this->password === '') {
            throw new Exception('Pathao credentials are missing.');
        }

        $response = $this->request('POST', '/aladdin/api/v1/issue-token', [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'    => 'password',
            'username'      => $this->username,
            'password'      => $this->password,
        ], false);

        if (empty($response['access_token'])) {
            throw new Exception('Pathao authentication failed: ' . esc_html(wp_json_encode($response)));
        }

        $this->access_token = $response['access_token'];

        // expires_in সেকেন্ডে আসে; একটু আগেই মেয়াদ শেষ ধরে নিচ্ছি
        $ttl = isset($response['expires_in']) ? max(60, (int) $response['expires_in'] - 300) : DAY_IN_SECONDS;
        set_transient($cache_key, $this->access_token, $ttl);

        return $this->access_token;
    }

    /**
     * Create Single Order
     */
    public function createOrder($orderData)
    {
        return $this->authorized_request('POST', '/aladdin/api/v1/orders', $orderData);
    }

    /**
     * Create Bulk Orders
     */
    public function createBulkOrder($orders = [])
    {
        return $this->authorized_request('POST', '/aladdin/api/v1/orders/bulk', ['orders' => $orders]);
    }

    /**
     * Token লাগানো request; 401 এলে একবার নতুন token নিয়ে আবার চেষ্টা করে
     */
    private function authorized_request($method, $path, $data = [])
    {
        if (!$this->access_token) {
            $this->authenticate();
        }

        try {
            return $this->request($method, $path, $data, true);
        } catch (Exception $e) {
            if ($e->getCode() === 401) {
                delete_transient($this->token_cache_key());
                $this->authenticate(true);
                return $this->request($method, $path, $data, true);
            }
            throw $e;
        }
    }

    /**
     * HTTP Request Handler (WordPress HTTP API — raw cURL-এর বদলে)
     */
    private function request($method, $path, $data = [], $auth = true)
    {
        $headers = [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Accept'       => 'application/json',
        ];

        if ($auth && $this->access_token) {
            $headers['Authorization'] = 'Bearer ' . $this->access_token;
        }

        $args = [
            'method'  => $method,
            'headers' => $headers,
            'timeout' => 30,
        ];

        if (!empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($this->base_url . $path, $args);

        if (is_wp_error($response)) {
            throw new Exception('Pathao HTTP error: ' . esc_html($response->get_error_message()));
        }

        $status  = (int) wp_remote_retrieve_response_code($response);
        $body    = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            // 52x = Cloudflare: Pathao-র সার্ভার সাড়া দেয়নি, আপনার কোডের সমস্যা নয়
            if ($status >= 520 && $status <= 527) {
                throw new Exception(
                    esc_html(sprintf('Pathao server unreachable (Cloudflare %d). Try again later.', $status)),
                    $status // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                );
            }

            $message = is_array($decoded) && isset($decoded['message']) ? $decoded['message'] : $body;

            throw new Exception(
                esc_html(sprintf('Pathao API error (%d): %s', $status, $message)),
                $status // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        return $decoded;
    }
}
