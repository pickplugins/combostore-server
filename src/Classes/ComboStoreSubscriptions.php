<?php

namespace ComboStore\Classes;

use Error;
use DateTime;
use DateInterval;
use ComboStore\Classes\ComboStoreObjectMeta;

if (!defined('ABSPATH')) exit;  // if direct access


class ComboStoreSubscriptions
{
    public $object_name = "";


    public function __construct() {}

    function create_subscription($params)
    {




        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions';

        $response = [];




        // ✅ Extract variables safely
        $user_id            = isset($params['user_id']) ? intval($params['user_id']) : 0;
        $order_id           = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $plan_id            = isset($params['plan_id']) ? intval($params['plan_id']) : 0;
        $status             = isset($params['status']) ? sanitize_text_field($params['status']) : 'active';

        $start_date         = isset($params['start_date']) ? sanitize_text_field($params['start_date']) : current_time('mysql');
        $end_date           = !empty($params['end_date']) ? sanitize_text_field($params['end_date']) : null;
        $next_billing_date  = !empty($params['next_billing_date']) ? sanitize_text_field($params['next_billing_date']) : null;

        $cancel_at_period_end = isset($params['cancel_at_period_end']) ? intval($params['cancel_at_period_end']) : 0;

        $renewal_interval   = isset($params['renewal_interval']) ? sanitize_text_field($params['renewal_interval']) : 'month';
        $interval_count      = isset($params['interval_count']) ? intval($params['interval_count']) : 1;

        $trial_start        = !empty($params['trial_start']) ? sanitize_text_field($params['trial_start']) : null;
        $trial_end          = !empty($params['trial_end']) ? sanitize_text_field($params['trial_end']) : null;

        $payment_method_id  = isset($params['payment_method_id']) ? intval($params['payment_method_id']) : 0;
        $last_payment_date  = !empty($params['last_payment_date']) ? sanitize_text_field($params['last_payment_date']) : null;
        $total_amount = isset($params['total_amount']) ? floatval($params['total_amount']) : 0.00;
        $subtotal_amount = isset($params['subtotal_amount']) ? floatval($params['subtotal_amount']) : 0.00;

        // ✅ Build insert data
        $data = array(
            'userid'              => $user_id,
            'order_id'            => $order_id,
            'plan_id'             => $plan_id,
            'status'              => $status,
            'start_date'          => $start_date,
            'end_date'            => $end_date,
            'next_billing_date'   => $next_billing_date,
            'cancel_at_period_end' => $cancel_at_period_end,
            'renewal_interval'    => $renewal_interval,
            'interval_count'       => $interval_count,
            'trial_start'         => $trial_start,
            'trial_end'           => $trial_end,
            'payment_method_id'   => $payment_method_id,
            'last_payment_date'   => $last_payment_date,
            'total_amount' => $total_amount,
            'subtotal_amount' => $subtotal_amount,
            'created_at'          => current_time('mysql'),
            'updated_at'          => current_time('mysql'),
        );

        $format = array(
            '%d',  // userid
            '%d',  // order_id
            '%d',  // plan_id
            '%s',  // status
            '%s',  // start_date
            '%s',  // end_date
            '%s',  // next_billing_date
            '%d',  // cancel_at_period_end
            '%s',  // renewal_interval
            '%d',  // interval_count
            '%s',  // trial_start
            '%s',  // trial_end
            '%d',  // payment_method_id
            '%s',  // last_payment_date
            '%f',  // total_amount
            '%f',  // subtotal_amount
            '%s',  // created_at
            '%s',  // updated_at
        );

        // ✅ Insert into table
        $wpdb->insert($table, $data, $format);
        $subscription_id = $wpdb->insert_id;



        // $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        // $ComboStoreObjectMeta->update_meta('orders', $order_id, "delivery_location", $delivery_location);


        // $userData = get_user_by("ID", $user_id);

        // $action_prams = array_merge($data, ["order_id" => $order_id, "email" => $userData->user_email, "name" => $userData->display_name]);


        if ($wpdb->last_error) {
            $response['errors'] = true;
            //do_action("combo_store_order_create_failed", $action_prams);
        } else {


            $response['success'] = true;
            $response['subscription_id'] = $subscription_id;

            //do_action("combo_store_order_created", $action_prams);
        }






        return $response;
    }





    function get_subscription($subscription_id, $by = 'id')
    {

        global $wpdb;
        $prefix = $wpdb->prefix;
        $table_orders = $prefix . 'cstore_orders';
        $table_subscriptions = $prefix . 'cstore_subscriptions';

if($by == 'order_id'){
            $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE order_id = %d",
                $subscription_id
            ),
            ARRAY_A
        );
}else {
        $subscriptionRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_subscriptions WHERE id = %d",
                $subscription_id
            ),
            ARRAY_A
        );
}



        $oderId = isset($subscriptionRow['order_id']) ? $subscriptionRow['order_id'] : 0;


        $orderRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_orders WHERE id = %d",
                $oderId
            ),
            ARRAY_A
        );




        $subscriptionRow = empty($subscriptionRow) ? [] : $subscriptionRow;

        $orderRow = empty($orderRow) ? [] : $orderRow;



        $response['subscription'] = $subscriptionRow;
        $response['order'] = $orderRow;


        return $response;
    }
    function update_subscription($params)
    {

        /*
            Partial update: only fields present in $params change. Previously
            every missing field was overwritten with a default (total_amount 0,
            status ''), the format list was out of order (last_payment_date
            saved as %d, i.e. "2026"), and next_billing_date was recomputed
            from today on every save.

            next_billing_date: an explicitly changed date wins; otherwise it is
            recomputed only when the billing cycle (interval or count) changes.
        */
        global $wpdb;
        $table = $wpdb->prefix . 'cstore_subscriptions';

        $subscription_id = isset($params['id']) ? intval($params['id']) : 0;
        if (!$subscription_id) {
            return false;
        }

        $current = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $subscription_id), ARRAY_A);
        if (!$current) {
            return false;
        }

        $valid_date = function ($value) {
            $value = substr(sanitize_text_field((string) $value), 0, 10);
            $d = DateTime::createFromFormat('Y-m-d', $value);
            return ($d && $d->format('Y-m-d') === $value) ? $value : null;
        };

        $data = [];
        $format = [];

        if (isset($params['status']) && in_array($params['status'], ['active', 'paused', 'canceled', 'expired'], true)) {
            $data['status'] = $params['status'];
            $format[] = '%s';
            /* Ending a subscription records when, if no end date is set yet. */
            if (in_array($params['status'], ['canceled', 'expired'], true) && empty($current['end_date']) && !array_key_exists('end_date', $params)) {
                $data['end_date'] = current_time('Y-m-d');
                $format[] = '%s';
            }
        }
        if (isset($params['renewal_interval']) && in_array($params['renewal_interval'], ['day', 'week', 'month', 'year'], true)) {
            $data['renewal_interval'] = $params['renewal_interval'];
            $format[] = '%s';
        }
        if (isset($params['interval_count'])) {
            $data['interval_count'] = max(1, intval($params['interval_count']));
            $format[] = '%d';
        }
        if (isset($params['cancel_at_period_end'])) {
            $data['cancel_at_period_end'] = !empty($params['cancel_at_period_end']) && $params['cancel_at_period_end'] !== '0' ? 1 : 0;
            $format[] = '%d';
        }
        foreach (['total_amount', 'subtotal_amount'] as $money) {
            if (isset($params[$money]) && $params[$money] !== '') {
                $data[$money] = max(0, floatval($params[$money]));
                $format[] = '%f';
            }
        }
        foreach (['last_payment_date', 'end_date', 'trial_end'] as $date_field) {
            if (array_key_exists($date_field, $params)) {
                $data[$date_field] = $params[$date_field] ? $valid_date($params[$date_field]) : null;
                $format[] = '%s';
            }
        }

        $requested_next = array_key_exists('next_billing_date', $params) && $params['next_billing_date']
            ? $valid_date($params['next_billing_date'])
            : null;
        $cycle_changed = (isset($data['renewal_interval']) && $data['renewal_interval'] !== $current['renewal_interval'])
            || (isset($data['interval_count']) && (int) $data['interval_count'] !== (int) $current['interval_count']);

        if ($requested_next && $requested_next !== substr((string) $current['next_billing_date'], 0, 10)) {
            $data['next_billing_date'] = $requested_next;
            $format[] = '%s';
        } elseif ($cycle_changed) {
            /* From today in the store's timezone - an empty date makes the
               helper use PHP's (UTC) clock, which can land a day early. */
            $data['next_billing_date'] = $this->get_next_billing_date(
                current_time('Y-m-d'),
                $data['renewal_interval'] ?? $current['renewal_interval'],
                $data['interval_count'] ?? $current['interval_count']
            );
            $format[] = '%s';
        }

        if (empty($data)) {
            return true;
        }

        $data['updated_at'] = current_time('mysql');
        $format[] = '%s';

        $updated = $wpdb->update($table, $data, ['id' => $subscription_id], $format, ['%d']);

        /* 0 rows changed is still success; false is a database error. */
        return $updated !== false;
    }

    function get_orders($order_id)
    {


        global $wpdb;
        $table_orders = $wpdb->prefix . "cstore_orders";


        $query = $wpdb->prepare(
            "SELECT * FROM $table_orders WHERE order_id = %d",
            $order_id
        );

        $orders = $wpdb->get_results($query);

        return $orders;
    }



    function delete_subscription($subscription_id)
    {

        $userid = isset($prams['userid']) ? $prams['userid'] : '';
        $object_id = isset($prams['object_id']) ? $prams['object_id'] : '';


        global $wpdb;
        $prefix = $wpdb->prefix;
        $table = $prefix . 'cstore_subscriptions';


        $response = [];

        if (!$subscription_id) {
            $response['errors']["delete_failed"] = "Deleting failed";
        }


        $status = $wpdb->delete($table, array('id' => $subscription_id,), array('%d'));


        $response['status'] = $status;

        return $response;
    }









function get_next_billing_date(
     $currentDate,
     $renewalInterval,
     $intervalCount
) {
  if (empty($currentDate)) {
        $date = new DateTime(); // today
    } else {
        $date = new DateTime($currentDate);
    }
    switch (strtolower($renewalInterval)) {
        case 'day':
            $date->add(new DateInterval("P{$intervalCount}D"));
            break;

        case 'week':
            $days = $intervalCount * 7;
            $date->add(new DateInterval("P{$days}D"));
            break;

        case 'month':
            $date->add(new DateInterval("P{$intervalCount}M"));
            break;

        /* `year` is a valid renewal_interval in the table's enum but was
           missing here, so saving a yearly subscription threw. */
        case 'year':
            $date->add(new DateInterval("P{$intervalCount}Y"));
            break;

        default:
            throw new InvalidArgumentException("Invalid renewal interval");
    }

    return $date->format('Y-m-d');
}






    function get_datetime()
    {
        $gmt_offset = get_option('gmt_offset');
        $datetime = gmdate('Y-m-d H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $datetime;
    }





    function get_date()
    {
        $gmt_offset = get_option('gmt_offset');
        $date = gmdate('Y-m-d', strtotime('+' . $gmt_offset . ' hour'));

        return $date;
    }


    function get_time()
    {
        $gmt_offset = get_option('gmt_offset');
        $time = gmdate('H:i:s', strtotime('+' . $gmt_offset . ' hour'));

        return $time;
    }
}
