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

        $couriers = isset($settings['couriers']) ? $settings['couriers'] : [];
        $steadfast = isset($couriers['steadfast']) ? $couriers['steadfast'] : [];


        $this->api_key = isset($steadfast['api_key']) ? $steadfast['api_key'] : '';
        $this->secret_key = isset($steadfast['secret_key']) ? $steadfast['secret_key'] : '';

        add_filter('couriers_webhook_steadfast', array($this, 'couriers_webhook_steadfast'));
        add_filter('cstore_send_to_courier_steadfast', array($this, 'cstore_send_to_courier_steadfast'));
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





    function couriers_webhook_steadfast($data)
    {

        $response = [];
        $settings = get_option('combo_store_settings');


        $couriers = $settings['couriers'] ?? [];
        $webhook_auth_token = $couriers['steadfast']['webhook_auth_token'] ?? '';


        $authorization = isset($data['authorization']) ? $data['authorization'] : '';


        if ($webhook_auth_token != $authorization) {

            $response['error'] = true;
            $response['message'] = 'authorization failed';

            return $response;
        }


        $notification_type = isset($data['notification_type']) ? $data['notification_type'] : '';
        $consignment_id = isset($data['consignment_id']) ? $data['consignment_id'] : '';
        $invoice = isset($data['invoice']) ? $data['invoice'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $tracking_message = isset($data['tracking_message']) ? $data['tracking_message'] : '';
        $updated_at = isset($data['updated_at']) ? $data['updated_at'] : '';
        $cod_amount = isset($data['cod_amount']) ? $data['cod_amount'] : '';


        if ($notification_type == 'delivery_status') {
            $ComboStoreOrders = new ComboStoreOrders();


            $prams = [
                'order_id' => $invoice,
                'status' => $status,
                'consignment_id' => $consignment_id,
                'latlng' => null,
                'notes' => $tracking_message,
            ];

            $ComboStoreDeliveries = new ComboStoreDeliveries();
            $ComboStoreDeliveries->insert_tracking($prams);

            $response['tracking'] = true;

            if ($status) {

                $orderPrams = [
                    'id' => $invoice,
                    'status' => $status,
                    'consignment_id' => $consignment_id,
                    'notes' => $tracking_message,
                ];



                $ComboStoreOrders->update_order($orderPrams);
                $response['order'] = true;

                $orderNotePrams = [
                    'order_id' => $invoice,
                    'note' => $tracking_message,
                ];

                $ComboStoreOrders->add_order_note($orderNotePrams);
                $response['order_note'] = true;
            }
        }
        if ($notification_type == 'tracking_update') {

            $prams = [
                'order_id' => $invoice,
                'status' => $status,
                'consignment_id' => $consignment_id,
                'latlng' => null,
                'notes' => $tracking_message,
            ];


            error_log(wp_json_encode($prams));


            $ComboStoreDeliveries = new ComboStoreDeliveries();
            $ComboStoreDeliveries->insert_tracking($prams);

            $response['tracking'] = true;
        }
        if ($notification_type == 'consignment_update') {



            $prams = [
                'order_id' => $invoice,
                'status' => $status,
                'consignment_id' => $consignment_id,
                'latlng' => null,
                'notes' => $tracking_message,
            ];

            $ComboStoreDeliveries = new ComboStoreDeliveries();
            $ComboStoreDeliveries->insert_tracking($prams);

            $response['tracking'] = true;
        }
        if ($notification_type == 'payment_request') {
        }
        if ($notification_type == 'pickup_request') {
        }
        if ($notification_type == 'return_request') {
        }
        if ($notification_type == 'cancel_request') {
        }


        return $response;
    }






    function cstore_send_to_courier_steadfast($data)
    {
        $response = [];
        $order_id = isset($data['order_id']) ? $data['order_id'] : '';

        if ($order_id) {

            $ComboStoreOrders = new ComboStoreOrders();

            $orderData = $ComboStoreOrders->get_order($order_id);

            $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];
            $item_description = '';

            foreach ($order_items as $order_item) {
                $product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
                $product_name  = get_the_title($product_id);
                $quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
                $price  = isset($order_item->price) ? $order_item->price : '';
                $delivery_type  = isset($order_item->delivery_type) ? $order_item->delivery_type : '';
                $alternative_phone  = isset($order_item->alternative_phone) ? $order_item->alternative_phone : '';

                $item_description .= $product_name . ' X ' . $quantity . ', \n';
            }


            $order = [
                "invoice" => $order_id,
                "recipient_name" => $orderData['billing_name'],
                "recipient_phone" => $orderData['billing_phone'],
                "alternative_phone" => $orderData['alternative_phone'],
                "recipient_email" => $orderData['billing_email'],
                "recipient_address" => $orderData['billing_address'],
                "cod_amount" => $orderData['total_amount'],
                "note" => $orderData['note'],
                "item_description" => $item_description,
                "delivery_type" => $delivery_type,
                "total_lot" => $quantity,
            ];



            $steadfast_response = $this->createOrder($order);


            $status = isset($steadfast_response['status']) ? $steadfast_response['status'] : false;

            if ($status == true) {

                $response_args = isset($steadfast_response['response']) ? $steadfast_response['response'] : [];
                $consignment = isset($response_args['consignment']) ? $response_args['consignment'] : [];
                $errors   = isset($consignment['errors']) ? $consignment['errors'] : [];

                if ($errors) {
                    $response['success'] = false;
                    $response['message'] = 'Order sent to courier failed';
                    $response['errors'] = $errors;
                    return $response;
                }

                $tracking_code   = isset($consignment['tracking_code']) ? $consignment['tracking_code'] : '';
                $consignment_id   = isset($consignment['consignment_id']) ? $consignment['consignment_id'] : '';
                $tracking_link   = isset($consignment['tracking_link']) ? $consignment['tracking_link'] : '';

                $response['status'] = 'success';
                $response['message'] = 'Order sent to courier successfully';
                $response['consignment_id'] = $consignment_id;
                $response['tracking_code'] = $tracking_code;
                $response['tracking_link'] = $tracking_link;

                $ComboStoreDeliveries = new ComboStoreDeliveries();

                $newDelivery = [
                    'customer_id' => $orderData['userid'],
                    'order_id' => $order_id,
                    'rider_id' => '',
                    'status' => 'started',
                    'courier' => 'steadfast',
                    'consignment_id' => $consignment_id,
                    'tracking_code' => $tracking_code,
                    'tracking_link' => $tracking_link,
                    'courier_data' => $consignment,
                ];


                $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);
            }
        }

        return $response;
    }
}
