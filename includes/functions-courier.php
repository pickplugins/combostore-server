<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreProduct;
use ComboStore\Classes\PathaoCourier;
use ComboStore\Classes\ComboStoreOrders;
use ComboStore\Classes\ComboStoreDeliveries;
use ComboStore\Classes\RedxCourier;
use ComboStore\Classes\Courrierfast;




add_filter("cstore_send_to_courier_pathao", "cstore_send_to_courier_pathao");

function cstore_send_to_courier_pathao($data)
{

    $combo_store_settings = get_option('combo_store_settings');
    $courier = isset($combo_store_settings['courier']) ? $combo_store_settings['courier'] : [];
    $pathao = isset($courier['pathao']) ? $courier['pathao'] : [];
    $store_id = isset($pathao['store_id']) ? $pathao['store_id'] : '';


    $response = [];
    $order_id = isset($data['order_id']) ? $data['order_id'] : '';
    $item_type = isset($data['item_type']) ? $data['item_type'] : 2;
    $delivery_type = isset($data['delivery_type']) ? $data['delivery_type'] : '48';

    if ($order_id) {

        $ComboStoreProduct = new ComboStoreProduct();
        $ComboStoreOrders = new ComboStoreOrders();

        $orderData = $ComboStoreOrders->get_order($order_id);




        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];

        $item_description = '';

        $total_weight = 0;
        $total_quantity = 0;

        foreach ($order_items as $order_item) {
            $product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
            $product_name  = get_the_title($product_id);

            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id,]);
            $quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
            $weight = $get_product->weight ?? 0;



            $total_weight += $get_product->weight ?? 0;
            $total_weight = $weight * $quantity;
            $price  = isset($order_item->price) ? $order_item->price : '';

            $total_quantity += $quantity;

            $product_name = html_entity_decode($product_name);
            $product_name = trim(preg_replace('/\s+/', ' ', $product_name));

            $item_description .= $product_name . ' X ' . $quantity . ',';
        }


        $PathaoCourier = new PathaoCourier();

        $order = [
            "store_id" => $store_id,
            "merchant_order_id" => $order_id,
            "recipient_name" => $orderData['billing_name'],
            "recipient_phone" => $orderData['billing_phone'],
            "recipient_email" => $orderData['billing_email'],
            "recipient_address" => $orderData['billing_address'],
            "amount_to_collect" => $orderData['total_amount'],
            "item_weight" => $total_weight,
            "item_quantity" => $total_quantity,
            "item_type" => $item_type,
            "note" => '',
            "item_description" => $item_description,
            "delivery_type" => $delivery_type,
        ];


        // $order = [
        //     "store_id" => 12345,
        //     "merchant_order_id" => "INV-1001",
        //     "recipient_name" => "Demo Customer",
        //     "recipient_phone" => "017XXXXXXXX",
        //     "recipient_address" => "Uttara Sector 10, Dhaka",
        //     "delivery_type" => 48,
        //     "item_type" => 2,
        //     "special_instruction" => "Call before delivery",
        //     "item_quantity" => 1,
        //     "item_weight" => "0.5",
        //     "item_description" => "T-shirt",
        //     "amount_to_collect" => 900
        // ];



        $pathao_response = $PathaoCourier->createOrder($order);


        error_log(wp_json_encode($pathao_response));

        $type = isset($pathao_response['type']) ? $pathao_response['type'] : false;

        if ($type == 'success') {

            $data = isset($pathao_response['data']) ? $pathao_response['data'] : [];

            error_log(wp_json_encode($data));



            $consignment_id   = isset($data['consignment_id']) ? $data['consignment_id'] : '';

            $tracking_code   = isset($consignment['tracking_code']) ? $consignment['tracking_code'] : '';

            $response['status'] = 'success';
            $response['message'] = 'Order sent to courier successfully';
            $response['consignment_id'] = $consignment_id;
            $response['tracking_code'] = $tracking_code;

            $ComboStoreDeliveries = new ComboStoreDeliveries();

            $newDelivery = [
                'customer_id' => $orderData['userid'],
                'order_id' => $order_id,
                'rider_id' => '',
                'status' => 'started',
                'courier' => 'pathao',
                'consignment_id' => $consignment_id,
                'tracking_code' => $tracking_code,
                'courier_data' => $data,
            ];

            error_log(wp_json_encode($newDelivery));

            $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);
        } else {

            $errors = [];

            $response['success'] = false;
            $response['message'] = 'Order sent to courier failed';
            $response['errors'] = $errors;
            return $response;
        }
    }

    return $response;
}

add_filter("cstore_send_to_courier_redx", "cstore_send_to_courier_redx");

function cstore_send_to_courier_redx($data)
{

    $combo_store_settings = get_option('combo_store_settings');
    $courier = isset($combo_store_settings['courier']) ? $combo_store_settings['courier'] : [];
    $redx = isset($courier['redx']) ? $courier['redx'] : [];
    $store_id = isset($redx['store_id']) ? $redx['store_id'] : '';


    $response = [];
    $order_id = isset($data['order_id']) ? $data['order_id'] : '';
    $item_type = isset($data['item_type']) ? $data['item_type'] : 2;
    $delivery_type = isset($data['delivery_type']) ? $data['delivery_type'] : '48';

    if ($order_id) {

        $ComboStoreProduct = new ComboStoreProduct();
        $ComboStoreOrders = new ComboStoreOrders();

        $orderData = $ComboStoreOrders->get_order($order_id);




        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];

        $item_description = '';

        $total_weight = 0;
        $total_quantity = 0;

        foreach ($order_items as $order_item) {
            $product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
            $product_name  = get_the_title($product_id);

            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id,]);
            $quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
            $weight = $get_product->weight ?? 0;



            $total_weight += $get_product->weight ?? 0;
            $total_weight = $weight * $quantity;
            $price  = isset($order_item->price) ? $order_item->price : '';

            $total_quantity += $quantity;

            $product_name = html_entity_decode($product_name);
            $product_name = trim(preg_replace('/\s+/', ' ', $product_name));

            $item_description .= $product_name . ' X ' . $quantity . ',';
        }


        $RedxCourier = new RedxCourier();

        $order = [
            "pickup_store_id" => $store_id,
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


        // $order = [
        //     "store_id" => 12345,
        //     "merchant_order_id" => "INV-1001",
        //     "recipient_name" => "Demo Customer",
        //     "recipient_phone" => "017XXXXXXXX",
        //     "recipient_address" => "Uttara Sector 10, Dhaka",
        //     "delivery_type" => 48,
        //     "item_type" => 2,
        //     "special_instruction" => "Call before delivery",
        //     "item_quantity" => 1,
        //     "item_weight" => "0.5",
        //     "item_description" => "T-shirt",
        //     "amount_to_collect" => 900
        // ];



        $redx_response = $RedxCourier->createParcel($order);


        error_log(wp_json_encode($redx_response));

        $type = isset($redx_response['type']) ? $redx_response['type'] : false;

        if ($type == 'success') {

            $data = isset($redx_response['data']) ? $redx_response['data'] : [];

            error_log(wp_json_encode($data));



            $consignment_id   = isset($data['consignment_id']) ? $data['consignment_id'] : '';

            $tracking_code   = isset($consignment['tracking_id']) ? $consignment['tracking_id'] : '';

            $response['status'] = 'success';
            $response['message'] = 'Order sent to courier successfully';
            $response['consignment_id'] = $consignment_id;
            $response['tracking_code'] = $tracking_code;

            $ComboStoreDeliveries = new ComboStoreDeliveries();

            $newDelivery = [
                'customer_id' => $orderData['userid'],
                'order_id' => $order_id,
                'rider_id' => '',
                'status' => 'started',
                'courier' => 'redx',
                'consignment_id' => $consignment_id,
                'tracking_code' => $tracking_code,
                'courier_data' => $data,
            ];

            error_log(wp_json_encode($newDelivery));

            $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);
        } else {

            $errors = [];

            $response['success'] = false;
            $response['message'] = 'Order sent to courier failed';
            $response['errors'] = $errors;
            return $response;
        }
    }

    return $response;
}




add_filter("cstore_send_to_courier_courrierfast", "cstore_send_to_courier_courrierfast");

function cstore_send_to_courier_courrierfast($data)
{


    $order_id     = isset($data['order_id']) ? sanitize_text_field($data['order_id']) : "";

    $combo_store_settings = get_option('combo_store_settings');
    $courier = isset($combo_store_settings['courier']) ? $combo_store_settings['courier'] : [];
    $courrierfast = isset($courier['courrierfast']) ? $courier['courrierfast'] : [];
    $store_id = isset($courrierfast['store_id']) ? $courrierfast['store_id'] : '';


    $response = [];
    $order_id = isset($data['order_id']) ? $data['order_id'] : '';
    $item_type = isset($data['item_type']) ? $data['item_type'] : 2;
    $deliveryServiceType = isset($data['deliveryServiceType']) ? $data['deliveryServiceType'] : 'regular';
    $deliveryInstructions = isset($data['deliveryInstructions']) ? $data['deliveryInstructions'] : '';

    if ($order_id) {

        $ComboStoreProduct = new ComboStoreProduct();
        $ComboStoreOrders = new ComboStoreOrders();

        $orderData = $ComboStoreOrders->get_order($order_id);




        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];

        $item_description = '';

        $total_weight = 0;
        $total_quantity = 0;

        foreach ($order_items as $order_item) {
            $product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
            $product_name  = get_the_title($product_id);

            $get_product = $ComboStoreProduct->get_product(['product_id' => $product_id,]);
            $quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
            $weight = $get_product->weight ?? 0;



            $total_weight += $get_product->weight ?? 1;
            $total_weight = $weight * $quantity;
            $price  = isset($order_item->price) ? $order_item->price : '';

            $total_quantity += $quantity;

            $product_name = html_entity_decode($product_name);
            $product_name = trim(preg_replace('/\s+/', ' ', $product_name));

            $item_description .= $product_name . ' X ' . $quantity . ',';
        }


        $Courrierfast = new Courrierfast();

        $order = [
            "store" => $store_id,
            "merchantOrderId" => $order_id,
            "customerName" => $orderData['billing_name'],
            "mobileNumber" => $orderData['billing_phone'],
            "recipient_email" => $orderData['billing_email'],
            "detailedAddress" => $orderData['billing_address'],
            "codAmount" => $orderData['total_amount'],
            "weight" => "$total_weight",
            "item_quantity" => $total_quantity,
            "productType" => $item_type,
            "district" => 'rangpur',
            "thana" => 'rangpur sadar',
            "area" => '',
            "deliveryInstructions" => $deliveryInstructions,
            "productDetails" => $item_description,
            "deliveryServiceType" => $deliveryServiceType,
        ];
        error_log(wp_json_encode($order));


        // $order = [
        //     "store_id" => 12345,
        //     "merchant_order_id" => "INV-1001",
        //     "recipient_name" => "Demo Customer",
        //     "recipient_phone" => "017XXXXXXXX",
        //     "recipient_address" => "Uttara Sector 10, Dhaka",
        //     "delivery_type" => 48,
        //     "item_type" => 2,
        //     "special_instruction" => "Call before delivery",
        //     "item_quantity" => 1,
        //     "item_weight" => "0.5",
        //     "item_description" => "T-shirt",
        //     "amount_to_collect" => 900
        // ];


        //return;

        $redx_response = $Courrierfast->createParcel($order);

        error_log(wp_json_encode($redx_response));


        $status = isset($redx_response['status']) ? $redx_response['status'] : false;

        if ($status) {

            $data = isset($redx_response['data']) ? $redx_response['data'] : [];

            error_log(wp_json_encode($data));



            $consignment_id   = isset($data['consignment_id']) ? $data['consignment_id'] : '';

            $tracking_code   = isset($consignment['tracking_id']) ? $consignment['tracking_id'] : '';

            $response['status'] = 'success';
            $response['message'] = 'Order sent to courier successfully';
            $response['consignment_id'] = $consignment_id;
            $response['tracking_code'] = $tracking_code;

            $ComboStoreDeliveries = new ComboStoreDeliveries();

            $newDelivery = [
                'customer_id' => $orderData['userid'],
                'order_id' => $order_id,
                'rider_id' => '',
                'status' => 'started',
                'courier' => 'redx',
                'consignment_id' => $consignment_id,
                'tracking_code' => $tracking_code,
                'courier_data' => $data,
            ];

            error_log(wp_json_encode($newDelivery));

            $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);
        } else {

            $errors = [];

            $response['success'] = false;
            $response['message'] = 'Order sent to courier failed';
            $response['errors'] = $errors;
            return $response;
        }
    }

    return $response;
}
