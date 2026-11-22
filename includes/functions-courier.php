<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreOrders;
use ComboStore\Classes\ComboStoreDeliveries;

/*
 * Sending orders to couriers (cstore_send_to_courier_<id>) lives in each
 * courier class: SteadfastCourier, PathaoCourier, RedxCourier, Courrierfast,
 * CarrybeeCourier, PaperflyCourier. They are created in combo-store-server.php.
 */

/* ------------------------------------------------------------------ */
/* Courier webhooks                                                    */
/*                                                                     */
/* Every courier posts to the dashboard's /api/couriers-webhook, which  */
/* forwards to combo-store/v2/couriers_webhook. ComboStoreRest works    */
/* out which courier it is (?courier=<id>, X-Combo-Courier, or the      */
/* courier's own fingerprint) and fires couriers_webhook_<id> with the  */
/* body plus $data['authorization'] and $data['_webhook'] (headers,     */
/* query, raw body). Each handler is a couriers_webhook_<id> method   */
/* on its courier class; the shared steps below do the actual work.    */
/*                                                                     */
/* Each courier's token is settings.couriers.<id>.webhook_auth_token.   */
/* It can arrive as the Authorization header, the courier's signature   */
/* header, or ?token= in the webhook URL.                               */
/* ------------------------------------------------------------------ */



/** True when the request carries this courier's webhook token. */
function cstore_courier_webhook_authorized($courier, $data, $signature_headers = [])
{
    $settings = get_option('combo_store_settings', []);
    $courier_settings = $settings['couriers'][$courier] ?? [];
    // CourrierFast's panel had its own "webhook_secret" field before this one.
    $token = trim((string) ($courier_settings['webhook_auth_token'] ?? ''));
    if ($token === '') {
        $token = trim((string) ($courier_settings['webhook_secret'] ?? ''));
    }

    // No token set means no webhook: never accept unauthenticated updates.
    if ($token === '') {
        return false;
    }

    $meta = isset($data['_webhook']) && is_array($data['_webhook']) ? $data['_webhook'] : [];
    $headers = $meta['headers'] ?? [];
    $candidates = [];

    $authorization = trim((string) ($data['authorization'] ?? ''));
    if ($authorization !== '') {
        $candidates[] = $authorization;
        $candidates[] = trim(preg_replace('/^(Bearer|Token)\s+/i', '', $authorization));
    }

    foreach ($signature_headers as $header) {
        $value = trim((string) ($headers[$header] ?? ''));
        if ($value !== '') {
            $candidates[] = $value;
        }
    }

    $query_token = trim((string) ($meta['query']['token'] ?? ''));
    if ($query_token !== '') {
        $candidates[] = $query_token;
    }

    // Signature headers may hold the secret itself or an HMAC-SHA256 of the body.
    $raw = (string) ($meta['raw'] ?? '');
    $hmac_hex = hash_hmac('sha256', $raw, $token);
    $hmac_b64 = base64_encode(hash_hmac('sha256', $raw, $token, true));

    foreach ($candidates as $candidate) {
        $candidate = (string) $candidate;
        if (hash_equals($token, $candidate) || hash_equals($hmac_hex, strtolower($candidate)) || hash_equals($hmac_b64, $candidate)) {
            return true;
        }
    }

    return false;
}


/** First non-empty value among $keys (dot paths allowed, e.g. "data.status"). */
function cstore_courier_webhook_pick($data, $keys)
{
    foreach ($keys as $key) {
        $value = $data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                $value = null;
                break;
            }
            $value = $value[$part];
        }
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
    }
    return '';
}


/** Courier status → ComboStore order status ('' = leave the order status alone). */
function cstore_courier_webhook_order_status($courier, $courier_status)
{
    // "order.in-transit", "In Transit", "in_transit" all become "in-transit".
    $key = strtolower(trim((string) $courier_status));
    $key = preg_replace('/^order\./', '', $key);
    $key = trim(preg_replace('/[\s_]+/', '-', $key), '-');

    $map = [
        'picked'                    => 'processing',
        'picked-up'                 => 'processing',
        'pickup-done'               => 'processing',
        'at-the-sorting-hub'        => 'processing',
        'in-transit'                => 'processing',
        'received-at-last-mile-hub' => 'processing',
        'assigned-for-delivery'     => 'processing',
        'ready-for-delivery'        => 'processing',
        'delivery-in-progress'      => 'processing',
        'out-for-delivery'          => 'processing',
        'delivered'                 => 'delivered',
        'on-hold'                   => 'hold',
        'hold'                      => 'hold',
        'agent-hold'                => 'hold',
        'delivery-failed'           => 'failed',
        'failed'                    => 'failed',
        'returned'                  => 'returned',
        'paid-return'               => 'returned',
        'return-received'           => 'returned',
        'cancelled'                 => 'canceled',
        'canceled'                  => 'canceled',
        'pickup-cancelled'          => 'canceled',
    ];

    $status = $map[$key] ?? '';

    return apply_filters('cstore_courier_webhook_order_status', $status, $courier_status, $courier);
}


/** The order this delivery belongs to, only if we sent it with this courier. */
function cstore_courier_webhook_find_order($courier, $order_ref, $consignment_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'cstore_deliveries';

    if ($consignment_id !== '') {
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM $table WHERE courier = %s AND consignment_id = %s ORDER BY id DESC LIMIT 1",
            $courier,
            $consignment_id
        ));
        if ($order_id) {
            return (int) $order_id;
        }
    }

    $order_ref = absint(preg_replace('/\D/', '', (string) $order_ref));
    if ($order_ref) {
        $order_id = $wpdb->get_var($wpdb->prepare(
            "SELECT order_id FROM $table WHERE courier = %s AND order_id = %d ORDER BY id DESC LIMIT 1",
            $courier,
            $order_ref
        ));
        if ($order_id) {
            return (int) $order_id;
        }
    }

    return 0;
}


/**
 * Shared webhook flow: authorise, find the order, log tracking, move the
 * order status, add an order note.
 *
 * $opts: signature_headers, order_keys, consignment_keys, status_keys,
 * message_keys, ignore_statuses (handshake/test events), http_status,
 * http_headers (added to every response).
 */
function cstore_courier_webhook_handle($courier, $data, $opts)
{
    $label = ucfirst($courier);
    $base  = [
        '_http_status'  => $opts['http_status'] ?? 200,
        '_http_headers' => $opts['http_headers'] ?? [],
    ];

    if (!cstore_courier_webhook_authorized($courier, $data, $opts['signature_headers'] ?? [])) {
        return array_merge($base, [
            '_http_status' => 401,
            'error'        => true,
            'message'      => 'authorization failed',
        ]);
    }

    $courier_status = cstore_courier_webhook_pick($data, $opts['status_keys'] ?? ['status']);

    if (in_array(strtolower($courier_status), $opts['ignore_statuses'] ?? [], true)) {
        return $base + ['success' => true, 'message' => 'webhook integration ok'];
    }

    $consignment_id = cstore_courier_webhook_pick($data, $opts['consignment_keys'] ?? ['consignment_id']);
    $order_ref      = cstore_courier_webhook_pick($data, $opts['order_keys'] ?? ['order_id']);
    $message        = cstore_courier_webhook_pick($data, $opts['message_keys'] ?? ['message']);

    $order_id = cstore_courier_webhook_find_order($courier, $order_ref, $consignment_id);

    if (!$order_id) {
        // 2xx so the courier does not retry forever for a parcel we never sent.
        return $base + [
            'error'   => true,
            'message' => 'No ' . $label . ' delivery found for this consignment',
        ];
    }

    $response = $base + ['order_id' => $order_id];

    (new ComboStoreDeliveries())->insert_tracking([
        'order_id'       => $order_id,
        'status'         => $courier_status,
        'consignment_id' => $consignment_id,
        'latlng'         => null,
        'notes'          => $message,
    ]);
    $response['tracking'] = true;

    $order_status = cstore_courier_webhook_order_status($courier, $courier_status);

    if ($order_status !== '') {
        global $wpdb;
        $current = (string) $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}cstore_orders WHERE id = %d",
            $order_id
        ));

        // A late courier event must not undo a finished order.
        $locked = apply_filters('cstore_courier_webhook_locked_statuses', ['completed', 'refunded'], $courier);

        if ($current !== $order_status && !in_array($current, $locked, true)) {
            (new ComboStoreOrders())->update_order([
                'id'     => $order_id,
                'status' => $order_status,
            ]);
            $response['order'] = true;
            $response['order_status'] = $order_status;
        }
    }

    if ($courier_status !== '' || $message !== '') {
        $note = $label . ': ' . ($courier_status !== '' ? $courier_status : 'update');
        if ($message !== '') {
            $note .= ' - ' . $message;
        }
        if ($consignment_id !== '') {
            $note .= ' (' . $consignment_id . ')';
        }

        (new ComboStoreOrders())->add_order_note([
            'order_id' => $order_id,
            'note'     => sanitize_text_field($note),
            'author'   => $label,
        ]);
        $response['order_note'] = true;
    }

    $response['success'] = true;

    return $response;
}

