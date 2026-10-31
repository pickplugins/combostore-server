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

    public function __construct()
    {
        $settings = get_option('combo_store_settings', []);

        $couriers = $settings['couriers'] ?? [];
        $courrierfast = $couriers['courrierfast'] ?? [];

        $this->api_key = $courrierfast['api_key'] ?? '';
        $this->secret_key = $courrierfast['secret_key'] ?? '';
        $this->store_id = $courrierfast['store_id'] ?? '';

        add_filter(
            'cstore_send_to_courier_courrierfast',
            [$this, 'cstore_send_to_courier_courrierfast']
        );

        add_filter(
            'couriers_webhook_courrierfast',
            [$this, 'couriers_webhook_courrierfast']
        );
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
     * Send a ComboStore order to CourrierFast.
     *
     * District, thana, and area must be valid CourrierFast
     * values/codes. Map your checkout fields to these values
     * before submitting the parcel.
     */
    public function cstore_send_to_courier_courrierfast($data)
    {
        $result = [
            'status' => 'error',
            'message' => 'Unable to send order to CourrierFast.',
        ];

        $order_id = $data['order_id'] ?? '';

        if (!$order_id) {
            $result['message'] = 'Order ID is required.';
            return $result;
        }

        if (empty($this->store_id)) {
            $result['message'] = 'CourrierFast store ID is missing.';
            return $result;
        }

        $orders = new ComboStoreOrders();
        $orderData = $orders->get_order($order_id);

        if (empty($orderData) || !is_array($orderData)) {
            $result['message'] = 'ComboStore order not found.';
            return $result;
        }

        /*
         * Resolve these location fields to actual CourrierFast
         * district/thana/area values before calling the API.
         *
         * Checkout field names may differ in your installation.
         */
        $location = [
            'district' => $data['district']
                ?? $orderData['district']
                ?? '',
            'thana' => $data['thana']
                ?? $orderData['thana']
                ?? '',
            'area' => $data['area']
                ?? $orderData['area']
                ?? '',
        ];

        if (
            empty($location['district'])
            || empty($location['thana'])
        ) {
            $result['message'] =
                'District and thana are required. Map the order address '
                . 'to CourrierFast location values first.';

            return $result;
        }

        $order_items = $orderData['order_items'] ?? [];
        $product_details = [];
        $total_quantity = 0;

        foreach ($order_items as $item) {
            $product_id = $item->product_id ?? 0;
            $quantity = (int) ($item->quantity ?? 1);
            $product_name = $product_id
                ? get_the_title($product_id)
                : '';

            if ($product_name !== '') {
                $product_details[] = $product_name . ' x ' . $quantity;
            }

            $total_quantity += $quantity;
        }

        $address = $orderData['billing_address'] ?? '';

        $parcel = [
            'store' => $this->store_id,
            'merchantOrderId' => (string) $order_id,
            'productType' => 'Regular Item',
            'customerName' => $orderData['billing_name'] ?? '',
            'mobileNumber' => $orderData['billing_phone'] ?? '',
            'district' => $location['district'],
            'thana' => $location['thana'],
            'area' => $location['area'],
            'detailedAddress' => $address,
            'codAmount' => (float) (
                $orderData['total_amount'] ?? 0
            ),
            'weight' => (string) (
                $data['weight'] ?? '0.5'
            ),
            'productDetails' => implode(', ', $product_details),
            'deliveryServiceType' => 'regular',
            'deliveryInstructions' => $orderData['note'] ?? '',
        ];

        /*
         * Allow integrations to customize parcel mapping without
         * modifying this class.
         */
        $parcel = apply_filters(
            'cstore_courrierfast_parcel_data',
            $parcel,
            $orderData,
            $data
        );

        $api_response = $this->createParcel($parcel);

        if (empty($api_response['status'])) {
            return [
                'status' => 'error',
                'message' => 'CourrierFast parcel creation failed.',
                'errors' => $api_response,
            ];
        }

        /*
         * CourrierFast's documented response fields were not
         * provided. Preserve the entire response instead of
         * assuming specific tracking or consignment keys.
         */
        $response_data = $api_response['response'] ?? [];

        $delivery_id = $response_data['consignment_id']
            ?? $response_data['parcel_id']
            ?? $response_data['name']
            ?? '';

        $tracking_code = $response_data['tracking_code']
            ?? $response_data['tracking_number']
            ?? '';

        $tracking_link = $response_data['tracking_link'] ?? '';

        $deliveries = new ComboStoreDeliveries();

        $newDelivery = [
            'customer_id' => $orderData['userid'] ?? '',
            'order_id' => $order_id,
            'rider_id' => '',
            'status' => 'started',
            'courier' => 'courrierfast',
            'consignment_id' => $delivery_id,
            'tracking_code' => $tracking_code,
            'tracking_link' => $tracking_link,
            'courier_data' => $response_data,
        ];

        $deliveries->create_delivery($newDelivery);

        return [
            'status' => 'success',
            'message' => 'Order sent to CourrierFast successfully.',
            'consignment_id' => $delivery_id,
            'tracking_code' => $tracking_code,
            'tracking_link' => $tracking_link,
            'courier_response' => $response_data,
        ];
    }

    /**
     * Webhook integration placeholder.
     *
     * Implement status mapping after obtaining the official
     * CourrierFast webhook payload and authentication rules.
     */
    public function couriers_webhook_courrierfast($data)
    {
        return [
            'status' => 'error',
            'message' => 'CourrierFast webhook handling is not configured yet.',
        ];
    }
}
