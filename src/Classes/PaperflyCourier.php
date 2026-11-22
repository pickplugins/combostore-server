<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit; // if direct access

use Exception;

/**
 * Paperfly courier (Bangladesh) merchant API client.
 *
 * Settings come from settings.couriers.paperfly (dashboard → Settings → Couriers):
 * sandbox, username, password, paperfly_key, merchant_code, store_name,
 * pickup_name, pickup_address, pickup_thana, pickup_district, pickup_phone.
 *
 * UNVERIFIED: the base URLs, paths and field names below follow Paperfly's
 * merchant API as known, not a fetched copy of their docs. Check them against a
 * test order before going live. Everything API-specific lives in this class.
 */
class PaperflyCourier
{
    const SANDBOX_URL = 'https://sandbox.paperfly.com.bd';
    const LIVE_URL    = 'https://api.paperfly.com.bd';

    const NEW_ORDER_PATH = '/merchant/api/service/new_order.php';
    const TRACKING_PATH  = '/API-Order-Tracking/';

    private $username;
    private $password;
    private $paperfly_key;
    private $base_url;
    private $config;

    private static $hooks_added = false;

    public function __construct()
    {
        $settings = get_option('combo_store_settings', []);
        $paperfly = $settings['couriers']['paperfly'] ?? [];

        $sandbox        = filter_var($paperfly['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->base_url = $sandbox ? self::SANDBOX_URL : self::LIVE_URL;

        $clean = function ($key) use ($paperfly) {
            return trim(wp_unslash((string) ($paperfly[$key] ?? '')));
        };

        $this->username     = $clean('username');
        $this->password     = wp_unslash((string) ($paperfly['password'] ?? '')); // not trimmed on purpose
        $this->paperfly_key = $clean('paperfly_key');

        $this->config = [
            'merchant_code'   => $clean('merchant_code'),
            'store_name'      => $clean('store_name'),
            'pickup_name'     => $clean('pickup_name'),
            'pickup_address'  => $clean('pickup_address'),
            'pickup_thana'    => $clean('pickup_thana'),
            'pickup_district' => $clean('pickup_district'),
            'pickup_phone'    => $clean('pickup_phone'),
        ];

        // Once per request, however many times the class is instantiated.
        if (!self::$hooks_added) {
            self::$hooks_added = true;
            add_filter('cstore_send_to_courier_paperfly', array($this, 'cstore_send_to_courier_paperfly'));
            add_filter('couriers_webhook_paperfly', array($this, 'couriers_webhook_paperfly'));
        }
    }

    /**
     * Paperfly webhook (filter couriers_webhook_paperfly).
     * UNVERIFIED payload: field names are tolerant guesses.
     * Webhook URL: .../api/couriers-webhook?courier=paperfly&token=<token>
     */
    public function couriers_webhook_paperfly($data)
    {
        return \cstore_courier_webhook_handle('paperfly', $data, [
            'order_keys'       => ['merchantOrderReference', 'ReferenceNumber', 'reference_number', 'merchant_order_id'],
            'consignment_keys' => ['tracking_number', 'trackingNumber', 'orderId', 'consignment_id'],
            'status_keys'      => ['status', 'event', 'orderStatus', 'delivery_status'],
            'message_keys'     => ['message', 'remarks', 'note'],
        ]);
    }

    /**
     * Send an order to Paperfly (filter cstore_send_to_courier_paperfly).
     *
     * $data (courierData from the dashboard): order_id, plus optional
     * delivery_option (regular|express), customer_thana, customer_district
     * (defaults to the billing city), max_weight (kg), product_size_weight (standard).
     */
    public function cstore_send_to_courier_paperfly($data)
    {
        $response = [];
        $order_id = isset($data['order_id']) ? absint($data['order_id']) : 0;

        if (!$order_id) {
            return ['success' => false, 'message' => 'Order id is required', 'errors' => []];
        }

        $ComboStoreProduct = new ComboStoreProduct();
        $ComboStoreOrders = new ComboStoreOrders();
        $orderData = $ComboStoreOrders->get_order($order_id);

        if (empty($orderData)) {
            return ['success' => false, 'message' => 'Order not found', 'errors' => []];
        }

        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];

        $item_description = [];
        $total_weight = 0; // product weight is a plain decimal, treated as kg

        foreach ($order_items as $order_item) {
            $product_id = isset($order_item->product_id) ? $order_item->product_id : '';
            $quantity = isset($order_item->quantity) ? (int) $order_item->quantity : 1;
            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id]);

            $total_weight += ((float) ($get_product->weight ?? 0)) * $quantity;

            $product_name = trim(preg_replace('/\s+/', ' ', html_entity_decode(get_the_title($product_id))));
            $item_description[] = $product_name . ' X ' . $quantity;
        }

        $max_weight = isset($data['max_weight']) && $data['max_weight'] !== '' ? (float) $data['max_weight'] : $total_weight;
        if ($max_weight <= 0) {
            $max_weight = 0.5;
        }

        $customer_district = isset($data['customer_district']) && $data['customer_district'] !== ''
            ? sanitize_text_field($data['customer_district'])
            : (string) ($orderData['billing_city'] ?? '');
        $customer_thana = isset($data['customer_thana']) ? sanitize_text_field($data['customer_thana']) : '';

        if ($customer_district === '' || $customer_thana === '') {
            return [
                'success' => false,
                'message' => 'Paperfly needs the customer thana and district. Enter them and try again.',
                'errors' => [],
            ];
        }

        $delivery_option = isset($data['delivery_option']) && $data['delivery_option'] === 'express' ? 'express' : 'regular';

        $order = [
            "merchantCode" => $this->get_config('merchant_code'),
            "merchantOrderReference" => (string) $order_id,
            "storeName" => $this->get_config('store_name'),
            "pickMerchantName" => $this->get_config('pickup_name'),
            "pickMerchantAddress" => $this->get_config('pickup_address'),
            "pickMerchantThana" => $this->get_config('pickup_thana'),
            "pickMerchantDistrict" => $this->get_config('pickup_district'),
            "pickupMerchantPhone" => $this->get_config('pickup_phone'),
            "productSizeWeight" => isset($data['product_size_weight']) && $data['product_size_weight'] !== '' ? sanitize_text_field($data['product_size_weight']) : 'standard',
            "productBrief" => implode(', ', $item_description),
            "packagePrice" => (string) ((float) ($orderData['total_amount'] ?? 0)),
            "deliveryOption" => $delivery_option,
            "custname" => $orderData['billing_name'] ?? '',
            "custaddress" => $orderData['billing_address'] ?? '',
            "customerThana" => $customer_thana,
            "customerDistrict" => $customer_district,
            "custPhone" => $orderData['billing_phone'] ?? '',
            "max_weight" => (string) $max_weight,
        ];

        try {
            $paperfly_response = $this->createOrder($order);
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'errors' => []];
        }

        $success = $paperfly_response['success'] ?? [];
        $tracking_code = is_array($success) && isset($success['tracking_number']) ? (string) $success['tracking_number'] : '';

        if ((int) ($paperfly_response['response_code'] ?? 0) !== 200 || $tracking_code === '') {
            $error = $paperfly_response['error'] ?? [];
            return [
                'success' => false,
                'message' => is_array($error) && !empty($error['message']) ? $error['message'] : 'Order sent to courier failed',
                'errors' => is_array($error) ? $error : [$error],
            ];
        }

        // Paperfly has no separate consignment id; the tracking number is the reference.
        $consignment_id = $tracking_code;

        $response['status'] = 'success';
        $response['message'] = 'Order sent to courier successfully';
        $response['consignment_id'] = $consignment_id;
        $response['tracking_code'] = $tracking_code;

        $ComboStoreDeliveries = new ComboStoreDeliveries();
        $ComboStoreDeliveries->create_delivery([
            'customer_id' => $orderData['userid'],
            'order_id' => $order_id,
            'rider_id' => '',
            'status' => 'started',
            'courier' => 'paperfly',
            'consignment_id' => $consignment_id,
            'tracking_code' => $tracking_code,
            'courier_data' => $success,
        ]);

        return $response;
    }

    /** Merchant code and pickup details from settings. */
    public function get_config($key)
    {
        return $this->config[$key] ?? '';
    }

    /** Create a parcel. */
    public function createOrder($data)
    {
        return $this->request('POST', self::NEW_ORDER_PATH, $data);
    }

    /** Track an order by its merchant reference (our order id). */
    public function trackOrder($reference)
    {
        return $this->request('POST', self::TRACKING_PATH, [
            'ReferenceNumber' => (string) $reference,
            'merchantCode'    => $this->config['merchant_code'],
        ]);
    }

    private function request($method, $path, $data = [])
    {
        if ($this->username === '' || $this->password === '' || $this->paperfly_key === '') {
            throw new Exception('Paperfly credentials are missing.');
        }

        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->password),
                'paperflykey'   => $this->paperfly_key,
            ],
        ];

        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        $result = wp_remote_request($this->base_url . $path, $args);

        if (is_wp_error($result)) {
            throw new Exception('Paperfly request failed: ' . esc_html($result->get_error_message()));
        }

        $status  = (int) wp_remote_retrieve_response_code($result);
        $body    = wp_remote_retrieve_body($result);
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            $message = $decoded['error']['message'] ?? ($decoded['message'] ?? $body);
            throw new Exception('Paperfly API error (' . $status . '): ' . esc_html(is_string($message) ? $message : wp_json_encode($message)));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
