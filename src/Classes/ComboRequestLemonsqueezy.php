<?php

namespace EmailValidation\Classes;


if (!defined('ABSPATH')) exit;  // if direct access 


class ComboRequestLemonsqueezy
{

    public $email = "";

    public function __construct()
    {

        add_action('init', array($this, 'lemonsqueezy_request'), 999);
    }


    public function lemonsqueezy_request()
    {
        $payload   = file_get_contents('php://input');

        if (empty($payload)) return;

        $request = json_decode($payload);
        // echo json_encode([]);
        // exit(0);


        $response = array();
        $event_name = isset($request->meta->event_name) ?  sanitize_text_field($request->meta->event_name) : '';
        if (empty($event_name)) return;



        $webhook_id = isset($request->meta->webhook_id) ?  sanitize_text_field($request->meta->webhook_id) : '';
        $test_mode = isset($request->meta->test_mode) ?  sanitize_text_field($request->meta->test_mode) : '';

        $order_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';
        $subscription_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $store_id = isset($request->data->attributes->store_id) ?  sanitize_text_field($request->data->attributes->store_id) : '';
        $user_email = isset($request->data->attributes->user_email) ?  sanitize_email($request->data->attributes->user_email) : '';



        $this->email = $user_email;


        if ($event_name == 'order_created') {


            $order_data = $this->create_order($request);

            if (isset($order_data['success'])) {

                $order_id = isset($order_data['id']) ? $order_data['id'] : 0;

                $response['order_id'] = $order_id;
                $response['success'] = true;
            }
            if (isset($order_data['errors'])) {
                $response['order_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }

        if ($event_name == 'license_key_created') {


            $license_data = $this->create_license($request);

            if (isset($license_data['success'])) {

                $license_id = isset($license_data['id']) ? $license_data['id'] : 0;

                $response['license_id'] = $license_id;
                $response['success'] = true;
            }
            if (isset($license_data['errors'])) {
                $response['license_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }








        if ($event_name == 'order_refunded') {


            $order_data = $this->refund_order($request);

            if (isset($order_data['success'])) {

                $order_id = isset($order_data['id']) ? $order_data['id'] : 0;

                $response['order_id'] = $order_id;
                $response['success'] = true;
            }
            if (isset($order_data['errors'])) {
                $response['order_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }
        if ($event_name == 'license_key_updated') {


            $license_data = $this->license_update($request);

            if (isset($license_data['success'])) {

                $license_id = isset($license_data['id']) ? $license_data['id'] : 0;

                $response['license_id'] = $license_id;
                $response['success'] = true;
            }
            if (isset($license_data['errors'])) {
                $response['license_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }






        if ($event_name == 'subscription_created') {

            $subscription_data = $this->create_subscription($request);

            if (isset($subscription_data['success'])) {

                $subscription_id = isset($subscription_data['id']) ? $subscription_data['id'] : 0;
                $response['subscription_id'] = $subscription_id;
                $response['success'] = true;
            }
            if (isset($subscription_data['errors'])) {
                $response['order_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }
        if (
            $event_name == 'subscription_updated' ||
            $event_name == 'subscription_cancelled' ||
            $event_name == 'subscription_resumed' ||
            $event_name == 'subscription_expired' ||
            $event_name == 'subscription_payment_failed' ||
            $event_name == 'subscription_payment_recovered' ||
            $event_name == 'subscription_payment_refunded' ||
            $event_name == 'subscription_plan_changed' ||
            $event_name == 'subscription_paused' ||
            $event_name == 'subscription_unpaused'

        ) {

            $subscription_data = $this->update_subscription($request);

            if (isset($subscription_data['success'])) {

                $subscription_id = isset($subscription_data['id']) ? $subscription_data['id'] : 0;
                $response['subscription_id'] = $subscription_id;
                $response['success'] = true;
            }
            if (isset($subscription_data['errors'])) {
                $response['order_id'] = null;
                $response['errors'] = true;
            }

            echo json_encode($response);
            exit(0);
        }
    }

    public function is_order_exist($order_id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_orders'; // Replace with your actual table name

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE order_id = %d",
                $order_id
            ),
            ARRAY_A
        );


        if ($row) {
            return true;
        } else {
            return false;
        }
    }
    public function is_license_exist($license_id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_licenses'; // Replace with your actual table name

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE license_id = %d",
                $license_id
            ),
            ARRAY_A
        );


        if ($row) {
            return true;
        } else {
            return false;
        }
    }

    public function is_subscription_exist($subscription_id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'cstore_subscriptions'; // Replace with your actual table name

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE subscription_id = %d",
                $subscription_id
            ),
            ARRAY_A
        );



        if ($row) {
            return true;
        } else {
            return false;
        }
    }








    public function create_license($request)
    {

        $response = [];

        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $license_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $license_exist = $this->is_license_exist($license_id);

        if ($license_exist) {
            $response['license_id'] = $license_id;
            $response['success'] = true;
            return $response;
        }


        $license_key = isset($attributes->key) ?  sanitize_text_field($attributes->key) : '';
        $key_short = isset($attributes->key_short) ?  sanitize_text_field($attributes->key_short) : '';
        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';
        $disabled = isset($attributes->disabled) ?  sanitize_text_field($attributes->disabled) : '';
        $order_id = isset($attributes->order_id) ?  sanitize_text_field($attributes->order_id) : '';
        $instances_count = isset($attributes->instances_count) ?  sanitize_text_field($attributes->instances_count) : '';
        $activation_limit = isset($attributes->activation_limit) ?  sanitize_text_field($attributes->activation_limit) : '';


        $product_id = isset($attributes->product_id) ?  sanitize_text_field($attributes->product_id) : '';
        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $created_at = isset($attributes->created_at) ?  sanitize_text_field($attributes->created_at) : '';
        $expires_at = isset($attributes->expires_at) ?  sanitize_text_field($attributes->expires_at) : '';

        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';


        $product_name = isset($attributes->product_name) ?  sanitize_text_field($attributes->product_name) : '';
        $variant_name = isset($attributes->variant_name) ?  sanitize_text_field($attributes->variant_name) : '';


        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = $request;


        global $wpdb;
        $table = $wpdb->prefix . "cstore_licenses";


        $data = array(
            'license_id'       => $license_id,
            'userid'           => $userid,
            'order_id'         => $order_id,
            'customer_id'      => $customer_id,
            'product_id'       => $product_id,
            'user_email'       => $user_email,
            'user_name'        => $user_name,
            'license_key'      => $license_key,
            'key_short'        => $key_short,
            'activation_limit' => $activation_limit,
            'instances_count'  => $instances_count,
            'disabled'         => $disabled,
            'test_mode'        => $test_mode,
            'status'           => $status,
            'created_at'       => $created_at,
            'expires_at'      => $expires_at,
            'webhook_data'     => json_encode($webhook_data),
        );

        $format = array(
            '%d',
            '%d',
            '%d',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s'
        );

        $wpdb->insert($table, $data, $format);


        if ($wpdb->last_error) {
            $response['errors'] = true;
        } else {
            $response['id'] = $license_id;
            $response['success'] = true;
        }






        return $response;
    }
    public function create_order($request)
    {

        $response = [];

        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $order_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $order_exist = $this->is_order_exist($order_id);

        if ($order_exist) {
            $response['order_id'] = $order_id;
            $response['success'] = true;
            return $response;
        }

        $subscription_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';
        $discount_total = isset($attributes->discount_total) ?  sanitize_text_field($attributes->discount_total) : '';
        $subtotal_usd = isset($attributes->subtotal_usd) ?  sanitize_text_field($attributes->subtotal_usd) : '';
        $total_usd = isset($attributes->total_usd) ?  sanitize_text_field($attributes->total_usd) : '';
        $total_formatted = isset($attributes->total_formatted) ?  sanitize_text_field($attributes->total_formatted) : '';


        $currency = isset($attributes->currency) ?  sanitize_text_field($attributes->currency) : '';
        $tax_total = isset($attributes->tax_formatted) ?  sanitize_text_field($attributes->tax_formatted) : '';
        $refunded_total = isset($attributes->refunded_amount_formatted) ?  sanitize_text_field($attributes->refunded_amount_formatted) : '';
        $refunded_at = isset($attributes->refunded_at) ?  sanitize_text_field($attributes->refunded_at) : '';
        $subtotal = isset($attributes->subtotal_formatted) ?  sanitize_text_field($attributes->subtotal_formatted) : '';
        $setup_fee = isset($attributes->setup_fee_formatted) ?  sanitize_text_field($attributes->setup_fee_formatted) : '';

        $urls = isset($attributes->urls) ?  ($attributes->urls) : '';
        $pause = isset($attributes->pause) ?  sanitize_text_field($attributes->pause) : '';
        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';
        $ends_at = isset($attributes->ends_at) ?  sanitize_text_field($attributes->ends_at) : '';
        $cancelled = isset($attributes->cancelled) ?  sanitize_text_field($attributes->cancelled) : '';
        $card_last_four = isset($attributes->card_last_four) ?  sanitize_text_field($attributes->card_last_four) : '';
        $renews_at = isset($attributes->renews_at) ?  sanitize_text_field($attributes->renews_at) : '';
        $trial_ends_at = isset($attributes->trial_ends_at) ?  sanitize_text_field($attributes->trial_ends_at) : '';
        $billing_anchor = isset($attributes->billing_anchor) ?  sanitize_text_field($attributes->billing_anchor) : '';

        $product_name = isset($attributes->product_name) ?  sanitize_text_field($attributes->product_name) : '';
        $variant_name = isset($attributes->variant_name) ?  sanitize_text_field($attributes->variant_name) : '';
        $product_id = isset($meta->custom_data->product_id) ?  sanitize_text_field($meta->custom_data->product_id) : '';


        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = $request;

        global $wpdb;
        $table = $wpdb->prefix . "cstore_orders";

        $data = array(
            'userid'          => $userid,
            'order_id'        => $order_id,
            'customer_id'     => $customer_id,
            'user_email'      => $user_email,
            'user_name'       => $user_name,
            'total'           => $total_formatted,
            'currency'        => $currency,
            'tax_total'       => $tax_total,
            'discount_total'  => $discount_total,
            'refunded_total'  => $refunded_total,
            'refunded_at'     => $refunded_at,
            'subtotal'        => $subtotal,
            'setup_fee'       => $setup_fee,
            'payment_method'  => $payment_method,
            'test_mode'       => $test_mode,
            'status'          => $status,
            'datetime'        => $datetime,
            'webhook_data'    => wp_json_encode($webhook_data),
        );

        $format = array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s');

        $wpdb->insert($table, $data, $format);
        $inserted_id = $wpdb->insert_id; // Get the newly inserted row ID

        do_action('email_validation_order_created', $data);

        if ($inserted_id) {
            $response['id'] = $inserted_id;
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }



        return $response;
    }
    public function refund_order($request)
    {

        $response = [];

        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $order_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $order_exist = $this->is_order_exist($order_id);

        if ($order_exist) {
            $response['order_id'] = $order_id;
            $response['success'] = true;
            return $response;
        }

        $subscription_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';
        $discount_total = isset($attributes->discount_total) ?  sanitize_text_field($attributes->discount_total) : '';
        $subtotal_usd = isset($attributes->subtotal_usd) ?  sanitize_text_field($attributes->subtotal_usd) : '';
        $total_usd = isset($attributes->total_usd) ?  sanitize_text_field($attributes->total_usd) : '';
        $total_formatted = isset($attributes->total_formatted) ?  sanitize_text_field($attributes->total_formatted) : '';
        $currency = isset($attributes->currency) ?  sanitize_text_field($attributes->currency) : '';
        $tax_total = isset($attributes->tax_formatted) ?  sanitize_text_field($attributes->tax_formatted) : '';
        $refunded_total = isset($attributes->refunded_amount_formatted) ?  sanitize_text_field($attributes->refunded_amount_formatted) : '';
        $subtotal = isset($attributes->subtotal_formatted) ?  sanitize_text_field($attributes->subtotal_formatted) : '';
        $setup_fee = isset($attributes->setup_fee_formatted) ?  sanitize_text_field($attributes->setup_fee_formatted) : '';

        $urls = isset($attributes->urls) ?  ($attributes->urls) : '';
        $refunded = isset($attributes->refunded) ?  sanitize_text_field($attributes->refunded) : '';
        $refunded_amount = isset($attributes->refunded_amount) ?  sanitize_text_field($attributes->refunded_amount) : '';
        $refunded_at = isset($attributes->refunded_at) ?  sanitize_text_field($attributes->refunded_at) : '';
        $refunded_amount_formatted = isset($attributes->refunded_amount_formatted) ?  sanitize_text_field($attributes->refunded_amount_formatted) : '';


        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';



        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = $request;


        global $wpdb;
        $table = $wpdb->prefix . "cstore_orders";

        $updated_data = array(
            'status' => $status,
            'refunded' => $refunded,
            'refunded_total' => $refunded_amount_formatted,
            'refunded_at' => $refunded_at,

        );
        $where = array('order_id' => $order_id);

        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['id'] = $order_id;
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }





        return $response;
    }

    public function license_update($request)
    {

        $response = [];


        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $license_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        $order_exist = $this->is_order_exist($license_id);

        if ($order_exist) {
            $response['license_id'] = $license_id;
            $response['success'] = true;
            return $response;
        }


        $license_key = isset($attributes->key) ?  sanitize_text_field($attributes->key) : '';
        $key_short = isset($attributes->key_short) ?  sanitize_text_field($attributes->key_short) : '';
        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';
        $disabled = isset($attributes->disabled) ?  sanitize_text_field($attributes->disabled) : '';
        $order_id = isset($attributes->order_id) ?  sanitize_text_field($attributes->order_id) : '';
        $instances_count = isset($attributes->instances_count) ?  sanitize_text_field($attributes->instances_count) : '';
        $activation_limit = isset($attributes->activation_limit) ?  sanitize_text_field($attributes->activation_limit) : '';
        $expires_at = isset($attributes->expires_at) ?  sanitize_text_field($attributes->expires_at) : '';


        $product_id = isset($attributes->product_id) ?  sanitize_text_field($attributes->product_id) : '';
        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $created_at = isset($attributes->created_at) ?  sanitize_text_field($attributes->created_at) : '';

        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';




        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = $request;


        global $wpdb;
        $table = $wpdb->prefix . "cstore_licenses";

        $updated_data = array(
            'status' => $status,
            'disabled' => $disabled,
            'activation_limit' => $activation_limit,
            'instances_count' => $instances_count,
            'expires_at' => $expires_at,

        );
        $where = array('order_id' => $order_id);

        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['id'] = $order_id;
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }





        return $response;
    }





    public function create_subscription($request)
    {

        $response = [];

        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $subscription_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';
        $order_id = isset($attributes->order_id) ?  sanitize_text_field($attributes->order_id) : '';

        $subscription_exist = $this->is_subscription_exist($subscription_id);

        if ($subscription_exist) {

            $response['subscription_id'] = $subscription_id;
            $response['success'] = true;
            return $response;
        }

        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';
        $discount_total = isset($attributes->discount_total) ?  sanitize_text_field($attributes->discount_total) : '';
        $subtotal_usd = isset($attributes->subtotal_usd) ?  sanitize_text_field($attributes->subtotal_usd) : '';
        $total_usd = isset($attributes->total_usd) ?  sanitize_text_field($attributes->total_usd) : '';
        $total_formatted = isset($attributes->total_formatted) ?  sanitize_text_field($attributes->total_formatted) : '';

        $currency = isset($attributes->currency) ?  sanitize_text_field($attributes->currency) : '';
        $tax_total = isset($attributes->tax_formatted) ?  sanitize_text_field($attributes->tax_formatted) : '';
        $refunded_total = isset($attributes->refunded_amount_formatted) ?  sanitize_text_field($attributes->refunded_amount_formatted) : '';
        $subtotal = isset($attributes->subtotal_formatted) ?  sanitize_text_field($attributes->subtotal_formatted) : '';
        $setup_fee = isset($attributes->setup_fee_formatted) ?  sanitize_text_field($attributes->setup_fee_formatted) : '';

        $urls = isset($attributes->urls) ?  wp_json_encode($attributes->urls) : '';
        $pause = isset($attributes->pause) ?  sanitize_text_field($attributes->pause) : '';
        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';
        $ends_at = isset($attributes->ends_at) ?  sanitize_text_field($attributes->ends_at) : '';
        $cancelled = isset($attributes->cancelled) ?  sanitize_text_field($attributes->cancelled) : '';
        $card_last_four = isset($attributes->card_last_four) ?  sanitize_text_field($attributes->card_last_four) : '';
        $renews_at = isset($attributes->renews_at) ?  sanitize_text_field($attributes->renews_at) : '';
        $trial_ends_at = isset($attributes->trial_ends_at) ?  sanitize_text_field($attributes->trial_ends_at) : '';
        $billing_anchor = isset($attributes->billing_anchor) ?  sanitize_text_field($attributes->billing_anchor) : '';

        $product_name = isset($attributes->product_name) ?  sanitize_text_field($attributes->product_name) : '';
        $variant_name = isset($attributes->variant_name) ?  sanitize_text_field($attributes->variant_name) : '';
        $product_id = isset($meta->custom_data->product_id) ?  sanitize_text_field($meta->custom_data->product_id) : '';


        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = wp_json_encode($request);



        global $wpdb;
        $table = $wpdb->prefix . "cstore_subscriptions";

        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table 
    (userid, subscription_id, order_id, customer_id, user_email, user_name, total, currency, tax_total, discount_total, refunded_total, subtotal, setup_fee, billing_anchor, card_last_four, test_mode, pause, cancelled, trial_ends_at, renews_at, ends_at, urls, status, datetime, webhook_data) 
    VALUES (%d, %d, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %d, %s, %s, %s, %s, %s, %s, %s)",
            array($userid,  $subscription_id, $order_id, $customer_id, $user_email, $user_name, $total_usd, $currency, $tax_total, $discount_total, $refunded_total, $subtotal, $setup_fee, $billing_anchor, $card_last_four, $test_mode, $pause, $cancelled, $trial_ends_at, $renews_at, $ends_at, ($urls), $status, $datetime, ($webhook_data))
        ));
        $inserted_id = $wpdb->insert_id; // Get the newly inserted row ID

        if ($inserted_id) {
            $response['id'] = $inserted_id;
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }


        return $response;
    }

    public function update_subscription($request)
    {

        $response = [];

        $attributes = isset($request->data->attributes) ?  $request->data->attributes : [];
        $meta = isset($request->data->meta) ?  $request->data->meta : [];


        $webhook_id = isset($meta->webhook_id) ?  sanitize_text_field($meta->webhook_id) : '';
        $test_mode = isset($meta->test_mode) ?  sanitize_text_field($meta->test_mode) : '';

        $order_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';
        $subscription_id = isset($request->data->id) ?  sanitize_text_field($request->data->id) : '';

        // $subscription_exist = $this->is_subscription_exist($subscription_id);

        // if ($subscription_exist) {

        //     $response['subscription_id'] = $subscription_id;
        //     $response['success'] = true;
        //     return $response;
        // }

        $store_id = isset($attributes->store_id) ?  sanitize_text_field($attributes->store_id) : '';
        $user_email = isset($attributes->user_email) ?  sanitize_email($attributes->user_email) : '';
        $customer_id = isset($attributes->customer_id) ?  sanitize_text_field($attributes->customer_id) : '';
        $user_name = isset($attributes->user_name) ?  sanitize_text_field($attributes->user_name) : '';
        $discount_total = isset($attributes->discount_total) ?  sanitize_text_field($attributes->discount_total) : '';
        $subtotal_usd = isset($attributes->subtotal_usd) ?  sanitize_text_field($attributes->subtotal_usd) : '';
        $total_usd = isset($attributes->total_usd) ?  sanitize_text_field($attributes->total_usd) : '';
        $total_formatted = isset($attributes->total_formatted) ?  sanitize_text_field($attributes->total_formatted) : '';

        $currency = isset($attributes->currency) ?  sanitize_text_field($attributes->currency) : '';
        $tax_total = isset($attributes->tax_formatted) ?  sanitize_text_field($attributes->tax_formatted) : '';
        $refunded_total = isset($attributes->refunded_amount_formatted) ?  sanitize_text_field($attributes->refunded_amount_formatted) : '';
        $subtotal = isset($attributes->subtotal_formatted) ?  sanitize_text_field($attributes->subtotal_formatted) : '';
        $setup_fee = isset($attributes->setup_fee_formatted) ?  sanitize_text_field($attributes->setup_fee_formatted) : '';

        $urls = isset($attributes->urls) ?  ($attributes->urls) : '';
        $pause = isset($attributes->pause) ?  sanitize_text_field($attributes->pause) : '';
        $status = isset($attributes->status) ?  sanitize_text_field($attributes->status) : '';
        $ends_at = isset($attributes->ends_at) ?  sanitize_text_field($attributes->ends_at) : '';
        $cancelled = isset($attributes->cancelled) ?  sanitize_text_field($attributes->cancelled) : '';
        $card_last_four = isset($attributes->card_last_four) ?  sanitize_text_field($attributes->card_last_four) : '';
        $renews_at = isset($attributes->renews_at) ?  sanitize_text_field($attributes->renews_at) : '';
        $trial_ends_at = isset($attributes->trial_ends_at) ?  sanitize_text_field($attributes->trial_ends_at) : '';
        $billing_anchor = isset($attributes->billing_anchor) ?  sanitize_text_field($attributes->billing_anchor) : '';

        $product_name = isset($attributes->product_name) ?  sanitize_text_field($attributes->product_name) : '';
        $variant_name = isset($attributes->variant_name) ?  sanitize_text_field($attributes->variant_name) : '';
        $product_id = isset($meta->custom_data->product_id) ?  sanitize_text_field($meta->custom_data->product_id) : '';


        $userid = $this->get_user_id();
        $datetime = $this->get_datetime();
        $payment_method = 'lemonsqueezy';
        $webhook_data = $request;




        global $wpdb;
        $table = $wpdb->prefix . "cstore_subscriptions";


        $updated_data = array(
            'status' => $status,
            'pause' => $pause,
            'cancelled' => $cancelled,
            'trial_ends_at' => $trial_ends_at,
            'renews_at' => $renews_at,
            'ends_at' => $ends_at,
            'ends_at' => $ends_at,
        );
        $where = array('subscription_id' => $subscription_id);

        $updated = $wpdb->update($table, $updated_data, $where);


        if ($updated) {
            $response['id'] = $subscription_id;
            $response['success'] = true;
        } else {
            $response['errors'] = true;
        }



        return $response;
    }




    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = date('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }

    function get_user_id()
    {
        $email = $this->get_email();

        $user = get_user_by('email', $email);

        $user_id = isset($user->ID) ? $user->ID :  $this->create_user();;

        return $user_id;
    }
    function create_user()
    {

        $email = $this->get_email();

        $username = $this->generate_username_from_email($email);
        $random_password = $this->generate_password();

        $credentials['email'] = $email;
        $credentials['password'] = $random_password;
        $credentials['username'] = $username;


        $user_login = isset($credentials['user_login']) ? $credentials['user_login'] : '';
        $user = get_user_by('login', $user_login);
        $user_id = isset($user->ID) ? $user->ID : '';
        $user_id = wp_create_user($credentials['username'], $credentials['password'], $credentials['email']);
        if ($user_id) {
            return $user_id;
        } else {
            return false;
        }
    }
    private function generate_username_from_email($email)
    {
        $get_email = $this->get_email();
        $email_arr = explode('@', $get_email);

        $username = isset($email_arr[0]) ? $email_arr[0] : '';
        $username = $this->regenerate_username($username);

        return $username;
    }
    function regenerate_username($username)
    {
        if (username_exists($username)) {
            $x = 1;
            while (username_exists($username)) {
                $username = $username . $x;
                $x++;
            }
        }
        return $username;
    }
    function get_email()
    {

        return $this->email;
    }
    private function generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
    {

        $length = empty($length) ? 12 : $length;
        $special_chars = empty($special_chars) ? true : (bool)$special_chars;
        $extra_special_chars = empty($extra_special_chars) ? false : (bool)$extra_special_chars;

        $random_password = wp_generate_password($length, $special_chars, $extra_special_chars);

        return $random_password;
    }
}
