<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\SteadfastCourier;
use ComboStore\Classes\PathaoCourier;
use ComboStore\Classes\ComboStoreOrders;
use ComboStore\Classes\ComboStoreDeliveries;
use ComboStore\Classes\RedxCourier;


add_filter("cstore_send_to_courier_steadfast", "cstore_send_to_courier_steadfast");

function cstore_send_to_courier_steadfast($data)
{
$response = [];
    $order_id = isset($data['order_id']) ? $data['order_id'] : '';

    if ($order_id) {

$ComboStoreOrders = new ComboStoreOrders();

$orderData = $ComboStoreOrders->get_order($order_id);

$order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];
$item_description = '';
error_log(wp_json_encode($order_items));

foreach($order_items as $order_item){
$product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
$product_name  = get_the_title($product_id);
$quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
$price  = isset($order_item->price) ? $order_item->price : '';

   $item_description .= $product_name.' X '.$quantity.', \n'; 
}


$steadfast = new SteadfastCourier();

$order = [
    "invoice" => $order_id,
    "recipient_name" => $orderData['billing_name'],
    "recipient_phone" => $orderData['billing_phone'],
    // "recipient_email" => $orderData['billing_email'],
    "recipient_email" => "public.nurhasan@gmail.com",
    "recipient_address" => $orderData['billing_address'],
    "cod_amount" => $orderData['total_amount'],
    "note" => "",
    "item_description" => $item_description,
    "delivery_type" => "",
];

error_log(wp_json_encode($order));


$steadfast_response = $steadfast->createOrder($order);

error_log(wp_json_encode($steadfast_response));

$status = isset($steadfast_response['status']) ? $steadfast_response['status'] : false;

if($status == true){

    $response_args = isset($steadfast_response['response']) ? $steadfast_response['response'] : [];
    $consignment = isset($response_args['consignment']) ? $response_args['consignment'] : [];
    $errors   = isset($consignment['errors']) ? $consignment['errors'] :[];

    if($errors){
        $response['success'] = false;
        $response['message'] = 'Order sent to courier failed';
        $response['errors'] = $errors;
        return $response;
    }

    $tracking_code   = isset($consignment['tracking_code']) ? $consignment['tracking_code'] : '';
    $consignment_id   = isset($consignment['consignment_id']) ? $consignment['consignment_id'] : '';

    $response['status'] = 'success';
    $response['message'] = 'Order sent to courier successfully';
    $response['consignment_id'] = $consignment_id;
    $response['tracking_code'] = $tracking_code;

    $ComboStoreDeliveries = new ComboStoreDeliveries();

    $newDelivery = [
    'customer_id'=>$orderData['userid'],
    'order_id'=>$order_id,
    'rider_id'=>'',
    'status'=>'started',
    'courier'=>'steadfast',
    'consignment_id'=>$consignment_id,
    'tracking_code'=>$tracking_code,
    'courier_data'=>$consignment,
    ];

error_log(wp_json_encode($newDelivery));

    $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);




}




    }

    return $response;
}

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

        $ComboStoreOrders = new ComboStoreOrders();

        $orderData = $ComboStoreOrders->get_order($order_id);

        $order_items = isset($orderData['order_items']) ? $orderData['order_items'] : [];
        $item_description = '';
        error_log(wp_json_encode($order_items));

        foreach($order_items as $order_item){
        $product_id  = isset($order_item->product_id) ? $order_item->product_id : '';
        $product_name  = get_the_title($product_id);
        $quantity  = isset($order_item->quantity) ? $order_item->quantity : '';
        $price  = isset($order_item->price) ? $order_item->price : '';

$product_name = html_entity_decode($product_name);
$product_name = trim(preg_replace('/\s+/', ' ', $product_name));

        $item_description .= $product_name.' X '.$quantity.','; 
        }


        $PathaoCourier = new PathaoCourier();

        $order = [
            "store_id" => $store_id,
            "merchant_order_id" => $order_id,
            "recipient_name" => $orderData['billing_name'],
            // "recipient_phone" => $orderData['billing_phone'],
            "recipient_phone" => '01537034053',
            // "recipient_email" => $orderData['billing_email'],
            "recipient_email" => "public.nurhasan@gmail.com",
            "recipient_address" => $orderData['billing_address'],
            "amount_to_collect" => $orderData['total_amount'],
            "item_weight" => '1',
            "item_quantity" => 1,
            "item_type" => $item_type,
            "note" => "",
            "item_description" => $item_description,
            "item_description" => 'family pack diaper',
            "delivery_type" => $delivery_type,
        ];

        error_log(wp_json_encode($order));

        $order = [
            "store_id" => 12345,
            "merchant_order_id" => "INV-1001",
            "recipient_name" => "Demo Customer",
            "recipient_phone" => "017XXXXXXXX",
            "recipient_address" => "Uttara Sector 10, Dhaka",
            "delivery_type" => 48,
            "item_type" => 2,
            "special_instruction" => "Call before delivery",
            "item_quantity" => 1,
            "item_weight" => "0.5",
            "item_description" => "T-shirt",
            "amount_to_collect" => 900
        ];

        $pathao_response = $PathaoCourier->createOrder($order);


        error_log(wp_json_encode($pathao_response));

        $type = isset($pathao_response['type']) ? $pathao_response['type'] : false;

        if($type == 'success'){

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
            'customer_id'=>$orderData['userid'],
            'order_id'=>$order_id,
            'rider_id'=>'',
            'status'=>'started',
            'courier'=>'pathao',
            'consignment_id'=>$consignment_id,
            'tracking_code'=>$tracking_code,
            'courier_data'=>$consignment,
            ];

        error_log(wp_json_encode($newDelivery));

    $new_delivery = $ComboStoreDeliveries->create_delivery($newDelivery);




}
else{
    
        $response['success'] = false;
        $response['message'] = 'Order sent to courier failed';
        $response['errors'] = $errors;
        return $response;
   

}



    }

    return $response;
}
