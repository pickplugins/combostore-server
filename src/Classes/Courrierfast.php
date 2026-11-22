<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class Courrierfast
{
    private $api_key;
    private $secret_key;
    private $store_id;
    private $base_url = 'https://app.courrierfast.com/api/method';

    private static $hooks_added = false;

    public function __construct()
    {
        $settings = get_option('combo_store_settings', []);

        $couriers = $settings['couriers'] ?? [];
        $courrierfast = $couriers['courrierfast'] ?? [];

        $this->api_key = $courrierfast['api_key'] ?? '';
        $this->secret_key = $courrierfast['secret_key'] ?? '';
        $this->store_id = $courrierfast['store_id'] ?? '';

        // Once per request, however many times the class is instantiated.
        if (!self::$hooks_added) {
            self::$hooks_added = true;
            add_filter(
                'cstore_send_to_courier_courrierfast',
                [$this, 'cstore_send_to_courier_courrierfast']
            );
            add_filter(
                'couriers_webhook_courrierfast',
                [$this, 'couriers_webhook_courrierfast']
            );
        }
    }

    /**
     * CourrierFast webhook (filter couriers_webhook_courrierfast).
     * UNVERIFIED payload: field names are tolerant guesses.
     * Webhook URL: .../api/couriers-webhook?courier=courrierfast&token=<token>
     */
    public function couriers_webhook_courrierfast($data)
    {
        return \cstore_courier_webhook_handle('courrierfast', $data, [
            'order_keys'       => ['merchant_order_id', 'invoice', 'order_id', 'data.merchant_order_id'],
            'consignment_keys' => ['delivery_id', 'consignment_id', 'tracking_code', 'name', 'data.name'],
            'status_keys'      => ['status', 'delivery_status', 'event', 'data.status'],
            'message_keys'     => ['message', 'remarks', 'note', 'data.message'],
        ]);
    }

    /**
     * Send an authenticated JSON POST request to CourrierFast.
     */
    private function request($endpoint, $data = [], $timeout = 60)
    {
        if (empty($this->api_key) || empty($this->secret_key)) {
            return [
                'status' => false,
                'error' => 'CourrierFast API key or secret is missing.',
            ];
        }

        $url = $this->base_url . '/' . ltrim($endpoint, '/');

        $headers = [
            'Authorization: token '
                . $this->api_key . ':' . $this->secret_key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $json = wp_json_encode($data);

        if ($json === false) {
            return [
                'status' => false,
                'error' => 'Failed to encode request data as JSON.',
            ];
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $body = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body === false) {
            return [
                'status' => false,
                'error' => $curl_error ?: 'CourrierFast request failed.',
                'http_code' => $http_code,
            ];
        }

        $decoded = json_decode($body, true);
        $json_error = json_last_error();

        return [
            'status' => $http_code >= 200 && $http_code < 300
                && $json_error === JSON_ERROR_NONE,
            'http_code' => $http_code,
            'response' => $decoded,
            'raw_response' => $json_error === JSON_ERROR_NONE
                ? null
                : $body,
        ];
    }

    /**
     * Create one parcel.
     *
     * Required:
     * store, productType, customerName, mobileNumber,
     * district, thana, detailedAddress, codAmount, weight.
     *
     * area is optional for a single parcel according to the API docs.
     */
    public function createParcel($parcelData)
    {
        if (!is_array($parcelData)) {
            return [
                'status' => false,
                'error' => 'Parcel data must be an array.',
            ];
        }

        if (empty($parcelData['store'])) {
            $parcelData['store'] = $this->store_id;
        }

        $required = [
            'store',
            'productType',
            'customerName',
            'mobileNumber',
            'district',
            'thana',
            'detailedAddress',
            'codAmount',
            'weight',
        ];

        $missing = [];

        foreach ($required as $field) {
            if (
                !array_key_exists($field, $parcelData)
                || $parcelData[$field] === ''
                || $parcelData[$field] === null
            ) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return [
                'status' => false,
                'error' => 'Missing required parcel fields.',
                'missing_fields' => $missing,
            ];
        }

        return $this->request(
            'merchant_portal.api.parcel.merchant_parcel_create',
            $parcelData
        );
    }

    /**
     * Create multiple parcels in one synchronous request.
     *
     * IMPORTANT:
     * The API expects "parcels" to be a JSON STRING,
     * not a nested JSON array.
     *
     * Maximum recommended batch size: 100 parcels.
     */
    public function createBulkParcels($parcels = [])
    {
        if (!is_array($parcels) || empty($parcels)) {
            return [
                'status' => false,
                'error' => 'Provide at least one parcel.',
            ];
        }

        if (count($parcels) > 100) {
            return [
                'status' => false,
                'error' => 'Maximum 100 parcels per synchronous request.',
            ];
        }

        $required = [
            'store',
            'productType',
            'customerName',
            'mobileNumber',
            'district',
            'thana',
            'area',
            'detailedAddress',
            'codAmount',
            'weight',
        ];

        foreach ($parcels as $index => &$parcel) {
            if (!is_array($parcel)) {
                unset($parcel);

                return [
                    'status' => false,
                    'error' => 'Each parcel must be an array.',
                    'parcel_index' => $index,
                ];
            }

            if (empty($parcel['store'])) {
                $parcel['store'] = $this->store_id;
            }

            $missing = [];

            foreach ($required as $field) {
                if (
                    !array_key_exists($field, $parcel)
                    || $parcel[$field] === ''
                    || $parcel[$field] === null
                ) {
                    $missing[] = $field;
                }
            }

            if (!empty($missing)) {
                unset($parcel);

                return [
                    'status' => false,
                    'error' => 'Missing required bulk parcel fields.',
                    'parcel_index' => $index,
                    'missing_fields' => $missing,
                ];
            }
        }

        unset($parcel);

        $parcels_json = wp_json_encode($parcels);

        if ($parcels_json === false) {
            return [
                'status' => false,
                'error' => 'Failed to encode bulk parcels.',
            ];
        }

        return $this->request(
            'merchant_portal.api.parcel.bulk_create_parcels',
            [
                'parcels' => $parcels_json,
            ],
            60
        );
    }

    /**
     * Send a ComboStore order to CourrierFast (filter cstore_send_to_courier_courrierfast).
     *
     * $data (courierData from the dashboard): order_id, item_type,
     * deliveryServiceType (regular), deliveryInstructions, and optional
     * district / thana / area (CourrierFast location values).
     */
    public function cstore_send_to_courier_courrierfast($data)
    {
        $response = [];
        $order_id = isset($data['order_id']) ? sanitize_text_field($data['order_id']) : '';
        $item_type = isset($data['item_type']) ? $data['item_type'] : 2;
        $deliveryServiceType = isset($data['deliveryServiceType']) ? $data['deliveryServiceType'] : 'regular';
        $deliveryInstructions = isset($data['deliveryInstructions']) ? $data['deliveryInstructions'] : '';

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
            "store" => $this->store_id,
            "merchantOrderId" => $order_id,
            "customerName" => $orderData['billing_name'],
            "mobileNumber" => $orderData['billing_phone'],
            "recipient_email" => $orderData['billing_email'],
            "detailedAddress" => $orderData['billing_address'],
            "codAmount" => $orderData['total_amount'],
            "weight" => "$total_weight",
            "item_quantity" => $total_quantity,
            "productType" => $item_type,
            // Defaults kept from the old function until the order screen sends a location.
            "district" => !empty($data['district']) ? sanitize_text_field($data['district']) : 'rangpur',
            "thana" => !empty($data['thana']) ? sanitize_text_field($data['thana']) : 'rangpur sadar',
            "area" => !empty($data['area']) ? sanitize_text_field($data['area']) : '',
            "deliveryInstructions" => $deliveryInstructions,
            "productDetails" => $item_description,
            "deliveryServiceType" => $deliveryServiceType,
        ];

        $order = apply_filters('cstore_courrierfast_parcel_data', $order, $orderData, $data);

        $api_response = $this->createParcel($order);

        if (empty($api_response['status'])) {
            $response['success'] = false;
            $response['message'] = !empty($api_response['error']) ? $api_response['error'] : 'Order sent to courier failed';
            $response['errors'] = $api_response['missing_fields'] ?? [];
            return $response;
        }

        // CourrierFast is a Frappe API: the result is usually wrapped in "message".
        $body = $api_response['response'] ?? [];
        $result = isset($body['message']) && is_array($body['message']) ? $body['message'] : (is_array($body) ? $body : []);

        $consignment_id = $result['consignment_id'] ?? ($result['parcel_id'] ?? ($result['name'] ?? ''));
        $tracking_code = $result['tracking_code'] ?? ($result['tracking_number'] ?? ($result['tracking_id'] ?? ''));
        $tracking_link = $result['tracking_link'] ?? '';

        $response['status'] = 'success';
        $response['message'] = 'Order sent to courier successfully';
        $response['consignment_id'] = $consignment_id;
        $response['tracking_code'] = $tracking_code;
        $response['tracking_link'] = $tracking_link;

        $ComboStoreDeliveries = new ComboStoreDeliveries();
        $ComboStoreDeliveries->create_delivery([
            'customer_id' => $orderData['userid'],
            'order_id' => $order_id,
            'rider_id' => '',
            'status' => 'started',
            'courier' => 'courrierfast',
            'consignment_id' => $consignment_id,
            'tracking_code' => $tracking_code,
            'tracking_link' => $tracking_link,
            'courier_data' => $result,
        ]);

        return $response;
    }
}
