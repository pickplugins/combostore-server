<?php

namespace ComboStore\Classes;


if (!defined('ABSPATH')) exit;  // if direct access

use Exception;



class RedxCourier
{
    private $api_key;
    private $base_url;
    private $store_id;

    private static $hooks_added = false;

    /**
     * Constructor
     */
    public function __construct()
    {

        $combo_store_settings = get_option('combo_store_settings');

        // settings.couriers is where the dashboard saves; settings.courier is the old key.
        $redx = $combo_store_settings['couriers']['redx'] ?? ($combo_store_settings['courier']['redx'] ?? []);
        $sandbox = filter_var($redx['sandbox'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $this->api_key = trim((string) ($redx['token'] ?? ''));
        $this->store_id = trim((string) ($redx['store_id'] ?? ''));

        // Base URLs
        $this->base_url = $sandbox
            ? "https://sandbox.redx.com.bd/v1.0.0-beta"
            : "https://openapi.redx.com.bd/v1.0.0-beta";

        // Once per request, however many times the class is instantiated.
        if (!self::$hooks_added) {
            self::$hooks_added = true;
            add_filter('cstore_send_to_courier_redx', array($this, 'cstore_send_to_courier_redx'));
            add_filter('couriers_webhook_redx', array($this, 'couriers_webhook_redx'));
        }
    }

    /**
     * Redx webhook (filter couriers_webhook_redx). Redx sends no signature, so
     * the token goes in the callback URL:
     * .../api/couriers-webhook?courier=redx&token=<webhook_auth_token>
     */
    public function couriers_webhook_redx($data)
    {
        return \cstore_courier_webhook_handle('redx', $data, [
            'order_keys'       => ['invoice_number', 'merchant_invoice_id'],
            'consignment_keys' => ['tracking_number', 'tracking_id'],
            'status_keys'      => ['status'],
            'message_keys'     => ['message_en', 'message'],
        ]);
    }

    /**
     * Send a ComboStore order to Redx (filter cstore_send_to_courier_redx).
     *
     * $data (courierData from the dashboard): order_id, item_type, delivery_type.
     */
    public function cstore_send_to_courier_redx($data)
    {
        $response = [];
        $order_id = isset($data['order_id']) ? $data['order_id'] : '';
        $item_type = isset($data['item_type']) ? $data['item_type'] : 2;
        $delivery_type = isset($data['delivery_type']) ? $data['delivery_type'] : '48';

        if (!$order_id) {
            return $response;
        }

        $ComboStoreProduct = new ComboStoreProduct();
        $ComboStoreOrders = new ComboStoreOrders();

        $orderData = $ComboStoreOrders->get_order($order_id);

        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];

        $item_description = '';
        $total_weight = 0;
        $total_quantity = 0;

        foreach ($order_items as $order_item) {
            $product_id = isset($order_item->product_id) ? $order_item->product_id : '';
            $quantity = isset($order_item->quantity) ? (int) $order_item->quantity : 0;
            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id]);

            $total_weight += ((float) ($get_product->weight ?? 0)) * $quantity;
            $total_quantity += $quantity;

            $product_name = trim(preg_replace('/\s+/', ' ', html_entity_decode(get_the_title($product_id))));
            $item_description .= $product_name . ' X ' . $quantity . ',';
        }

        $order = [
            "pickup_store_id" => $this->store_id,
            "merchant_invoice_id" => $order_id,
            "customer_name" => $orderData['billing_name'],
            "customer_phone" => $orderData['billing_phone'],
            "recipient_email" => $orderData['billing_email'],
            "customer_address" => $orderData['billing_address'],
            "cash_collection_amount" => $orderData['total_amount'],
            "parcel_weight" => $total_weight,
            "item_quantity" => $total_quantity,
            "item_type" => $item_type,
            "instruction" => '',
            "item_description" => $item_description,
            "delivery_type" => $delivery_type,
        ];

        try {
            $redx_response = $this->createParcel($order);
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'errors' => []];
        }

        $type = isset($redx_response['type']) ? $redx_response['type'] : false;

        if ($type != 'success') {
            $response['success'] = false;
            $response['message'] = 'Order sent to courier failed';
            $response['errors'] = [];
            return $response;
        }

        $result = isset($redx_response['data']) ? $redx_response['data'] : [];

        $consignment_id = isset($result['consignment_id']) ? $result['consignment_id'] : '';
        $tracking_code = isset($result['tracking_id']) ? $result['tracking_id'] : '';

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
            'courier' => 'redx',
            'consignment_id' => $consignment_id,
            'tracking_code' => $tracking_code,
            'courier_data' => $result,
        ]);

        return $response;
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
            throw new Exception("cURL Error: " . esc_html($error));
        }

        $decoded = json_decode($response, true);

        if ($status >= 400) {
            throw new Exception("REDX API Error (" . esc_html($status) . "): " . esc_html($response));
        }

        return $decoded;
    }
}
