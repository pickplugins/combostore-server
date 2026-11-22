<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit; // if direct access

use Exception;

/**
 * Carry Bee courier (Bangladesh) merchant API client.
 *
 * Credentials come from settings.couriers.carrybee (dashboard → Settings → Couriers):
 * sandbox, client_id, client_secret, client_context, store_id.
 *
 * UNVERIFIED: the base URLs, paths and field names below follow Carry Bee's v2
 * merchant API as known, not a fetched copy of their docs. Check them against a
 * sandbox order before going live. Everything API-specific lives in this class.
 */
class CarrybeeCourier
{
    const SANDBOX_URL = 'https://stage-sandbox.carrybee.com';
    const LIVE_URL    = 'https://developers.carrybee.com';

    private $client_id;
    private $client_secret;
    private $client_context;
    private $store_id;
    private $base_url;

    private static $hooks_added = false;

    public function __construct()
    {
        $settings = get_option('combo_store_settings', []);
        $carrybee = $settings['couriers']['carrybee'] ?? [];

        $sandbox        = filter_var($carrybee['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->base_url = $sandbox ? self::SANDBOX_URL : self::LIVE_URL;

        $this->client_id      = trim(wp_unslash((string) ($carrybee['client_id'] ?? '')));
        $this->client_secret  = trim(wp_unslash((string) ($carrybee['client_secret'] ?? '')));
        $this->client_context = trim(wp_unslash((string) ($carrybee['client_context'] ?? '')));
        $this->store_id       = trim(wp_unslash((string) ($carrybee['store_id'] ?? '')));

        // Once per request, however many times the class is instantiated.
        if (!self::$hooks_added) {
            self::$hooks_added = true;
            add_filter('cstore_send_to_courier_carrybee', array($this, 'cstore_send_to_courier_carrybee'));
            add_filter('couriers_webhook_carrybee', array($this, 'couriers_webhook_carrybee'));
        }
    }

    /**
     * Carry Bee webhook (filter couriers_webhook_carrybee).
     * UNVERIFIED: signature header X-CB-Webhook-Signature and the
     * "webhook.integration" test event assume a Pathao-style flow.
     * Fallback URL: .../api/couriers-webhook?courier=carrybee&token=<token>
     */
    public function couriers_webhook_carrybee($data)
    {
        return \cstore_courier_webhook_handle('carrybee', $data, [
            'signature_headers' => ['x_cb_webhook_signature'],
            'order_keys'        => ['merchant_order_id', 'data.merchant_order_id'],
            'consignment_keys'  => ['consignment_id', 'data.consignment_id'],
            'status_keys'       => ['event', 'status', 'data.status'],
            'message_keys'      => ['reason', 'message', 'data.reason'],
            'ignore_statuses'   => ['webhook.integration', 'webhook_integration'],
            'http_status'       => 202,
        ]);
    }

    /**
     * Send an order to Carry Bee (filter cstore_send_to_courier_carrybee).
     *
     * $data (courierData from the dashboard): order_id, plus optional
     * delivery_type (1 normal, 2 express), product_type (1 parcel, 2 book, 3 document),
     * city_id, zone_id, area_id, item_weight (grams), special_instruction.
     * When city_id/zone_id are missing they are resolved from the billing address.
     */
    public function cstore_send_to_courier_carrybee($data)
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
        $total_quantity = 0;

        foreach ($order_items as $order_item) {
            $product_id = isset($order_item->product_id) ? $order_item->product_id : '';
            $quantity = isset($order_item->quantity) ? (int) $order_item->quantity : 1;
            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id]);

            $total_weight += ((float) ($get_product->weight ?? 0)) * $quantity;
            $total_quantity += $quantity;

            $product_name = trim(preg_replace('/\s+/', ' ', html_entity_decode(get_the_title($product_id))));
            $item_description[] = $product_name . ' X ' . $quantity;
        }

        // Carry Bee wants grams; default to 500 g when products have no weight.
        $item_weight = isset($data['item_weight']) && $data['item_weight'] !== ''
            ? (int) $data['item_weight']
            : (int) round($total_weight * 1000);
        if ($item_weight <= 0) {
            $item_weight = 500;
        }

        try {
            $city_id = isset($data['city_id']) ? absint($data['city_id']) : 0;
            $zone_id = isset($data['zone_id']) ? absint($data['zone_id']) : 0;
            $area_id = isset($data['area_id']) ? absint($data['area_id']) : 0;

            if (!$city_id || !$zone_id) {
                $address = $this->addressDetails($orderData['billing_address'] ?? '');
                $address_data = $address['data'] ?? [];
                $city_id = $city_id ?: absint($address_data['city_id'] ?? 0);
                $zone_id = $zone_id ?: absint($address_data['zone_id'] ?? 0);
            }

            if (!$city_id || !$zone_id) {
                return [
                    'success' => false,
                    'message' => 'Could not detect the Carry Bee city/zone from the address. Select them and try again.',
                    'errors' => [],
                ];
            }

            $order = [
                "store_id" => $this->store_id,
                "merchant_order_id" => (string) $order_id,
                "delivery_type" => isset($data['delivery_type']) && (int) $data['delivery_type'] ? (int) $data['delivery_type'] : 1,
                "product_type" => isset($data['product_type']) && (int) $data['product_type'] ? (int) $data['product_type'] : 1,
                "recipient_phone" => $orderData['billing_phone'] ?? '',
                "recipient_name" => $orderData['billing_name'] ?? '',
                "recipient_address" => $orderData['billing_address'] ?? '',
                "city_id" => $city_id,
                "zone_id" => $zone_id,
                "special_instruction" => isset($data['special_instruction']) ? sanitize_text_field($data['special_instruction']) : '',
                "product_description" => implode(', ', $item_description),
                "item_weight" => $item_weight,
                "item_quantity" => max(1, $total_quantity),
                "collectable_amount" => (float) ($orderData['total_amount'] ?? 0),
            ];

            if ($area_id) {
                $order['area_id'] = $area_id;
            }

            $carrybee_response = $this->createOrder($order);
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'errors' => []];
        }

        $result = $carrybee_response['data']['order'] ?? ($carrybee_response['data'] ?? []);
        $consignment_id = isset($result['consignment_id']) ? (string) $result['consignment_id'] : '';

        if (!empty($carrybee_response['error']) || $consignment_id === '') {
            return [
                'success' => false,
                'message' => $carrybee_response['message'] ?? 'Order sent to courier failed',
                'errors' => $carrybee_response['errors'] ?? [],
            ];
        }

        // Carry Bee tracks by consignment id.
        $tracking_code = $consignment_id;

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
            'courier' => 'carrybee',
            'consignment_id' => $consignment_id,
            'tracking_code' => $tracking_code,
            'courier_data' => $result,
        ]);

        return $response;
    }

    public function get_store_id()
    {
        return $this->store_id;
    }

    /** Create a parcel. */
    public function createOrder($data)
    {
        return $this->request('POST', '/api/v2/orders', $data);
    }

    /** Order details / tracking by consignment id. */
    public function getOrderDetails($consignment_id)
    {
        return $this->request('GET', '/api/v2/orders/' . rawurlencode($consignment_id) . '/details');
    }

    public function getStores()
    {
        return $this->request('GET', '/api/v2/stores');
    }

    public function getCities()
    {
        return $this->request('GET', '/api/v2/cities');
    }

    public function getZones($city_id)
    {
        return $this->request('GET', '/api/v2/cities/' . absint($city_id) . '/zones');
    }

    public function getAreas($city_id, $zone_id)
    {
        return $this->request('GET', '/api/v2/cities/' . absint($city_id) . '/zones/' . absint($zone_id) . '/areas');
    }

    /** Resolve a free-text address to city/zone ids. */
    public function addressDetails($address)
    {
        return $this->request('POST', '/api/v2/address-details', ['query' => (string) $address]);
    }

    private function request($method, $path, $data = [])
    {
        if ($this->client_id === '' || $this->client_secret === '' || $this->client_context === '') {
            throw new Exception('Carry Bee credentials are missing.');
        }

        $args = [
            'method'  => $method,
            'timeout' => 30,
            'headers' => [
                'Content-Type'   => 'application/json',
                'Accept'         => 'application/json',
                'Client-ID'      => $this->client_id,
                'Client-Secret'  => $this->client_secret,
                'Client-Context' => $this->client_context,
            ],
        ];

        if ($method !== 'GET' && !empty($data)) {
            $args['body'] = wp_json_encode($data);
        }

        $result = wp_remote_request($this->base_url . $path, $args);

        if (is_wp_error($result)) {
            throw new Exception('Carry Bee request failed: ' . esc_html($result->get_error_message()));
        }

        $status  = (int) wp_remote_retrieve_response_code($result);
        $body    = wp_remote_retrieve_body($result);
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            $message = is_array($decoded) && !empty($decoded['message']) ? $decoded['message'] : $body;
            throw new Exception('Carry Bee API error (' . $status . '): ' . esc_html(is_string($message) ? $message : wp_json_encode($message)));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
