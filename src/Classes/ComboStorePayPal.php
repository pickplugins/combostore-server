<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access

use Exception;

use ComboStore\Classes\ComboStoreOrders;
use ComboStore\Classes\ComboStoreObjectMeta;

/**
 * PayPal Orders v2 gateway.
 *
 * The other gateways in this store are manual - cash on delivery, bank
 * transfer, bKash personal - so the server never had to talk to a payment
 * provider. PayPal does, and the whole point of this class is that the money
 * side of a checkout is decided here and never by the browser:
 *
 *   1. The storefront creates the local order first (create_order), so there
 *      is always a row to attach a payment to, even if the buyer abandons.
 *   2. It calls paypal_create_order with the order hash. This class reads the
 *      order out of the database and derives the amount from
 *      `total_amount`/`currency`. No amount ever arrives from the client.
 *   3. The buyer approves in the PayPal SDK (or on paypal.com).
 *   4. paypal_capture_order captures, re-checks that what PayPal captured is
 *      what we asked for, and only then marks the order paid.
 *   5. The webhook is the safety net for the buyer who approves and then
 *      closes the tab, and for refunds and reversals made inside PayPal.
 *
 * Two things about this integration are specific to this store and worth
 * knowing before changing anything:
 *
 *   - **PayPal does not support BDT**, which is this store's currency. So a
 *     PayPal charge is made in a supported currency (USD by default) and the
 *     order total is converted with `exchange_rate` from the settings. The
 *     rate used is written into the order's meta and into an order note, so a
 *     payment can still be reconciled months later when the rate has moved.
 *   - **Credentials live in their own option**, not in `combo_store_settings`.
 *     That blob is handed out whole by the `get_settings_public` route, so a
 *     client secret stored there would be one API call away from anybody who
 *     has the storefront's API key. `paypal_config` exposes only the fields
 *     the browser genuinely needs (the client id is public by design - it goes
 *     in the PayPal SDK URL).
 *
 * The REST routes live in ComboStoreRest, as they do for every other
 * subsystem; this class holds the logic they call.
 */
class ComboStorePayPal
{
    /** Option holding the gateway configuration, including the client secret. */
    const OPTION = 'combo_store_paypal';

    /** Access tokens are good for hours, so they are cached rather than re-issued per request. */
    const TOKEN_TRANSIENT = 'combo_store_paypal_token_';

    /**
     * Currencies PayPal rejects decimals for. An amount of "1000.00" in JPY is
     * a 422, so these have to be sent as whole units.
     */
    const ZERO_DECIMAL_CURRENCIES = array('HUF', 'JPY', 'TWD');

    private $settings;

    public function __construct()
    {
        $this->settings = $this->get_settings();
    }


    /* ------------------------------------------------------------ settings */

    /**
     * The gateway configuration, with every key present.
     *
     * @return array
     */
    public function get_settings()
    {
        $defaults = array(
            'enabled'              => false,
            'mode'                 => 'sandbox',     // sandbox | live
            'client_id'            => '',
            'client_secret'        => '',
            'currency'             => 'USD',         // what PayPal is charged in
            'exchange_rate'        => 0,             // store-currency units per 1 PayPal-currency unit
            'brand_name'           => '',
            'webhook_id'           => '',            // from the PayPal dashboard; required to trust webhooks
            'order_status_on_paid' => 'processing',
            'label'                => 'PayPal',
        );

        $saved = get_option(self::OPTION, array());

        if (!is_array($saved)) {
            $saved = array();
        }

        return array_merge($defaults, $saved);
    }

    /**
     * Save the configuration, leaving out anything not recognised.
     *
     * An empty `client_secret` means "keep the one already stored" - the
     * settings screen never receives the secret back, so it cannot send it
     * again, and treating empty as a deletion would wipe the gateway every
     * time somebody changed the brand name.
     *
     * @param array $input
     * @return array The stored settings, secret masked.
     */
    public function update_settings($input)
    {
        $current = $this->get_settings();

        $clean = array(
            'enabled'              => !empty($input['enabled']),
            'mode'                 => (isset($input['mode']) && $input['mode'] === 'live') ? 'live' : 'sandbox',
            'client_id'            => isset($input['client_id']) ? trim(sanitize_text_field($input['client_id'])) : $current['client_id'],
            'client_secret'        => $current['client_secret'],
            'currency'             => isset($input['currency']) ? strtoupper(sanitize_text_field($input['currency'])) : $current['currency'],
            'exchange_rate'        => isset($input['exchange_rate']) ? floatval($input['exchange_rate']) : $current['exchange_rate'],
            'brand_name'           => isset($input['brand_name']) ? sanitize_text_field($input['brand_name']) : $current['brand_name'],
            'webhook_id'           => isset($input['webhook_id']) ? trim(sanitize_text_field($input['webhook_id'])) : $current['webhook_id'],
            'order_status_on_paid' => isset($input['order_status_on_paid']) ? sanitize_text_field($input['order_status_on_paid']) : $current['order_status_on_paid'],
            'label'                => isset($input['label']) ? sanitize_text_field($input['label']) : $current['label'],
        );

        if (!empty($input['client_secret'])) {
            $clean['client_secret'] = trim(sanitize_text_field($input['client_secret']));
        }

        if (empty($clean['currency'])) {
            $clean['currency'] = 'USD';
        }

        /* Changing the account or the environment invalidates any cached token. */
        if ($clean['client_id'] !== $current['client_id'] || $clean['mode'] !== $current['mode'] || $clean['client_secret'] !== $current['client_secret']) {
            $this->forget_access_token($current);
        }

        update_option(self::OPTION, $clean);

        $this->settings = $clean;

        return $this->masked_settings();
    }

    /**
     * The configuration as the dashboard may see it: everything except the
     * secret, which is reported as present or absent and never echoed back.
     *
     * @return array
     */
    public function masked_settings()
    {
        $settings = $this->get_settings();
        $secret   = $settings['client_secret'];

        unset($settings['client_secret']);

        $settings['has_client_secret'] = !empty($secret);
        $settings['client_secret_hint'] = !empty($secret) ? str_repeat('*', 6) . substr($secret, -4) : '';
        $settings['webhook_url'] = rest_url('combo-store/v2/paypal_webhook');
        $settings['configured'] = $this->is_configured();

        return $settings;
    }

    /**
     * What the storefront is allowed to know. The client id belongs in the
     * browser - it is half of the PayPal SDK URL - but nothing else here does.
     *
     * @return array
     */
    public function public_config()
    {
        $settings = $this->get_settings();

        return array(
            'enabled'       => (bool) ($settings['enabled'] && $this->is_configured()),
            'mode'          => $settings['mode'],
            'client_id'     => $settings['client_id'],
            'currency'      => $settings['currency'],
            'exchange_rate' => floatval($settings['exchange_rate']),
            'brand_name'    => $settings['brand_name'],
            'label'         => $settings['label'],
            'sdk_url'       => 'https://www.paypal.com/sdk/js?client-id=' . rawurlencode($settings['client_id']) . '&currency=' . rawurlencode($settings['currency']) . '&intent=capture',
        );
    }

    public function is_configured()
    {
        $settings = $this->get_settings();

        return !empty($settings['client_id']) && !empty($settings['client_secret']);
    }

    public function is_enabled()
    {
        $settings = $this->get_settings();

        return !empty($settings['enabled']) && $this->is_configured();
    }

    private function api_base()
    {
        return $this->settings['mode'] === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }


    /* ----------------------------------------------------------------- http */

    /**
     * An OAuth2 access token, cached until shortly before it expires.
     *
     * @return string
     * @throws Exception
     */
    private function access_token()
    {
        if (!$this->is_configured()) {
            throw new Exception('PayPal is not configured: add the client id and secret first.');
        }

        $key    = $this->token_key($this->settings);
        $cached = get_transient($key);

        if (!empty($cached)) {
            return $cached;
        }

        $result = $this->request(
            'POST',
            '/v1/oauth2/token',
            'grant_type=client_credentials',
            array(
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($this->settings['client_id'] . ':' . $this->settings['client_secret']),
            ),
            false
        );

        if ($result['status'] >= 400 || empty($result['body']['access_token'])) {
            $this->log('authentication failed', $result);

            throw new Exception('PayPal rejected these credentials. Check the client id, the secret, and whether they belong to the ' . $this->settings['mode'] . ' environment.');
        }

        $token   = $result['body']['access_token'];
        $expires = isset($result['body']['expires_in']) ? intval($result['body']['expires_in']) : 3600;

        /* Expire the cache a few minutes early so a token is never used in the
           second it stops being valid. */
        set_transient($key, $token, max(60, $expires - 300));

        return $token;
    }

    private function token_key($settings)
    {
        return self::TOKEN_TRANSIENT . md5($settings['mode'] . '|' . $settings['client_id']);
    }

    private function forget_access_token($settings = null)
    {
        delete_transient($this->token_key($settings ? $settings : $this->settings));
    }

    /**
     * One HTTP call to PayPal.
     *
     * Returns the status alongside the decoded body rather than throwing on a
     * 4xx, because PayPal says useful things in those bodies - which field was
     * wrong, whether an order was already captured - and the callers branch on
     * them. Only a transport failure throws.
     *
     * @param string       $method
     * @param string       $path   Path, or an absolute URL.
     * @param array|string|null $body
     * @param array        $headers
     * @param bool         $auth   Send a bearer token.
     * @return array{status:int,body:array,raw:string}
     * @throws Exception
     */
    private function request($method, $path, $body = null, $headers = array(), $auth = true)
    {
        $url = (strpos($path, 'http') === 0) ? $path : $this->api_base() . $path;

        if (empty($headers)) {
            $headers = array('Content-Type: application/json');
        }

        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . $this->access_token();
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? wp_json_encode($body) : $body);
        } elseif ($method === 'POST') {
            /* PayPal wants a body on a POST even where it carries nothing. */
            curl_setopt($ch, CURLOPT_POSTFIELDS, '{}');
        }

        $raw    = curl_exec($ch);
        $error  = curl_error($ch);
        $status = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));

        curl_close($ch);

        if ($error) {
            throw new Exception('Could not reach PayPal: ' . esc_html($error));
        }

        $decoded = json_decode($raw, true);

        return array(
            'status' => $status,
            'body'   => is_array($decoded) ? $decoded : array(),
            'raw'    => is_string($raw) ? $raw : '',
        );
    }


    /* --------------------------------------------------------------- amount */

    /**
     * What to charge through PayPal for a local order.
     *
     * The order is in the store's currency (BDT), which PayPal will not take,
     * so this converts with the configured rate. The rate is returned with the
     * amount so it can be recorded against the payment.
     *
     * @param array $order
     * @return array{value:string,currency:string,rate:float,order_total:float,order_currency:string}
     * @throws Exception
     */
    public function amount_for_order($order)
    {
        $settings       = $this->settings;
        $target         = strtoupper($settings['currency']);
        $order_currency = strtoupper(!empty($order['currency']) ? $order['currency'] : 'BDT');
        $total          = floatval($order['total_amount']);

        if ($total <= 0) {
            throw new Exception('This order has no amount to pay.');
        }

        $rate = floatval($settings['exchange_rate']);

        if ($order_currency === $target) {
            $converted = $total;
            $rate      = 1.0;
        } else {
            if ($rate <= 0) {
                throw new Exception(sprintf(
                    'This order is in %s but PayPal is set to charge in %s, and no exchange rate is configured. Set the rate to how many %s make 1 %s.',
                    esc_html($order_currency),
                    esc_html($target),
                    esc_html($order_currency),
                    esc_html($target)
                ));
            }

            $converted = $total / $rate;
        }

        return array(
            'value'          => $this->money($converted, $target),
            'currency'       => $target,
            'rate'           => $rate,
            'order_total'    => $total,
            'order_currency' => $order_currency,
        );
    }

    /**
     * Format an amount the way PayPal's schema wants it: a string, with the
     * number of decimals that currency allows.
     *
     * @param float  $amount
     * @param string $currency
     * @return string
     */
    private function money($amount, $currency)
    {
        $decimals = in_array(strtoupper($currency), self::ZERO_DECIMAL_CURRENCIES, true) ? 0 : 2;

        /* Round up to the cent. Rounding down could leave the charge a cent
           short of the order, and a payment that does not cover the order is
           worse than one that is a cent over. */
        $factor  = pow(10, $decimals);
        $rounded = ceil($amount * $factor) / $factor;

        return number_format($rounded, $decimals, '.', '');
    }


    /* ------------------------------------------------------- order lifecyle */

    /**
     * Create the PayPal order for a local order and hand back what the browser
     * needs to get it approved.
     *
     * No line items are sent, only the total. PayPal validates that an item
     * breakdown adds up to the total exactly, and a converted, rounded total
     * will not reconcile against per-item conversions - the 422 that causes is
     * not worth the itemisation.
     *
     * @param string $order_hash
     * @return array
     * @throws Exception
     */
    public function create_order($order_hash)
    {
        $order_hash = sanitize_text_field($order_hash);

        if (empty($order_hash)) {
            throw new Exception('An order reference is required.');
        }

        if (!$this->is_enabled()) {
            throw new Exception('PayPal is not available.');
        }

        $ComboStoreOrders = new ComboStoreOrders();
        $order_id         = $ComboStoreOrders->get_order_id_by_hash($order_hash);

        if (empty($order_id)) {
            throw new Exception('That order could not be found.');
        }

        $order = $ComboStoreOrders->get_order($order_id);

        if (empty($order['id'])) {
            throw new Exception('That order could not be found.');
        }

        if ($order['payment_status'] === 'paid') {
            throw new Exception('This order has already been paid.');
        }

        if (in_array($order['status'], array('canceled', 'refunded', 'returned', 'failed'), true)) {
            throw new Exception('This order is ' . esc_html($order['status']) . ' and cannot be paid.');
        }

        $amount = $this->amount_for_order($order);

        $experience = array(
            'shipping_preference' => 'NO_SHIPPING', // the store collects the address at checkout
            'user_action'         => 'PAY_NOW',
        );

        if (!empty($this->settings['brand_name'])) {
            $experience['brand_name'] = $this->settings['brand_name'];
        }

        $payload = array(
            'intent'         => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'reference_id' => 'order-' . $order_id,
                    /* custom_id is the one field that comes back on the capture
                       and on every webhook, so the order hash goes here: it is
                       how a payment is matched to an order later. */
                    'custom_id'    => $order_hash,
                    'description'  => $this->order_description($order),
                    'amount'       => array(
                        'currency_code' => $amount['currency'],
                        'value'         => $amount['value'],
                    ),
                ),
            ),
            'payment_source' => array(
                'paypal' => array(
                    'experience_context' => $experience,
                ),
            ),
        );

        $result = $this->request('POST', '/v2/checkout/orders', $payload);

        if ($result['status'] >= 400 || empty($result['body']['id'])) {
            $this->log('create order failed for order ' . $order_id, $result);

            throw new Exception($this->error_message($result, 'PayPal could not start this payment.'));
        }

        $paypal_order_id = $result['body']['id'];

        $ComboStoreObjectMeta = new ComboStoreObjectMeta();
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_order_id', $paypal_order_id);
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_mode', $this->settings['mode']);

        return array(
            'paypal_order_id' => $paypal_order_id,
            'status'          => isset($result['body']['status']) ? $result['body']['status'] : '',
            'approve_url'     => $this->link($result['body'], 'approve'),
            'amount'          => $amount['value'],
            'currency'        => $amount['currency'],
            'exchange_rate'   => $amount['rate'],
            'order_id'        => intval($order_id),
        );
    }

    /**
     * Capture an approved PayPal order and mark the local order paid.
     *
     * Which local order this belongs to is read from PayPal's own response
     * (`custom_id`), not from the request, so a caller cannot point a real
     * payment at a different order. An `order_hash` in the request is only
     * used to cross-check.
     *
     * @param string $paypal_order_id
     * @param string $expected_hash Optional cross-check.
     * @return array
     * @throws Exception
     */
    public function capture_order($paypal_order_id, $expected_hash = '')
    {
        $paypal_order_id = sanitize_text_field($paypal_order_id);

        if (empty($paypal_order_id)) {
            throw new Exception('A PayPal order id is required.');
        }

        if (!$this->is_enabled()) {
            throw new Exception('PayPal is not available.');
        }

        $result = $this->request('POST', '/v2/checkout/orders/' . rawurlencode($paypal_order_id) . '/capture');

        /* A second capture of the same order is not an error from where the
           buyer is standing - they clicked twice, or the webhook got there
           first. Read the order back and settle from that. */
        if ($result['status'] >= 400 && $this->has_issue($result, 'ORDER_ALREADY_CAPTURED')) {
            $result = $this->request('GET', '/v2/checkout/orders/' . rawurlencode($paypal_order_id));
        }

        if ($result['status'] >= 400) {
            $this->log('capture failed for paypal order ' . $paypal_order_id, $result);

            throw new Exception($this->error_message($result, 'PayPal could not complete this payment.'));
        }

        $body    = $result['body'];
        $capture = $this->first_capture($body);

        if (empty($capture['id'])) {
            throw new Exception('PayPal did not return a completed payment for this order.');
        }

        $capture_status = isset($capture['status']) ? $capture['status'] : '';

        if (!in_array($capture_status, array('COMPLETED', 'PENDING'), true)) {
            throw new Exception('PayPal reported this payment as ' . esc_html($capture_status) . '.');
        }

        $order_hash = $this->custom_id($body, $capture);

        if (!empty($expected_hash) && !empty($order_hash) && !hash_equals($order_hash, sanitize_text_field($expected_hash))) {
            $this->log('capture ' . $capture['id'] . ' custom_id does not match the submitted order hash', array());
        }

        if (empty($order_hash)) {
            throw new Exception('This payment is not linked to an order.');
        }

        return $this->settle_capture($order_hash, $capture, $capture_status === 'PENDING' ? 'pending' : 'paid');
    }

    /**
     * Record a capture against the order it belongs to.
     *
     * Idempotent in both directions: a capture already recorded returns the
     * same answer, and an order already paid by something else is left alone.
     *
     * @param string $order_hash
     * @param array  $capture
     * @param string $payment_status
     * @return array
     * @throws Exception
     */
    public function settle_capture($order_hash, $capture, $payment_status = 'paid')
    {
        global $wpdb;

        $ComboStoreOrders     = new ComboStoreOrders();
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $order_id = $ComboStoreOrders->get_order_id_by_hash($order_hash);

        if (empty($order_id)) {
            throw new Exception('This payment is not linked to a known order.');
        }

        $order = $ComboStoreOrders->get_order($order_id);

        if (empty($order['id'])) {
            throw new Exception('This payment is not linked to a known order.');
        }

        $capture_id = isset($capture['id']) ? sanitize_text_field($capture['id']) : '';

        /* Already recorded - a double-clicked button, or the webhook arriving
           after the browser did. Report success rather than paying twice. */
        if ($order['payment_status'] === 'paid' && $order['transaction_id'] === $capture_id) {
            return array(
                'order_id'       => intval($order_id),
                'order_hash'     => $order_hash,
                'transaction_id' => $capture_id,
                'payment_status' => $order['payment_status'],
                'status'         => $order['status'],
                'already_paid'   => true,
            );
        }

        $paid_amount   = isset($capture['amount']['value']) ? $capture['amount']['value'] : '';
        $paid_currency = isset($capture['amount']['currency_code']) ? strtoupper($capture['amount']['currency_code']) : '';

        /* What PayPal took has to be what this order is worth. A mismatch means
           the order changed after the payment started, or the payment belongs
           somewhere else; either way a human needs to look before the order
           ships.

           If the expectation cannot even be worked out - the order total was
           edited to zero, or the exchange rate was cleared between approval
           and capture - the payment is still recorded and flagged. PayPal has
           the buyer's money by this point, and an unrecorded capture is the one
           outcome that cannot be recovered from the order. */
        try {
            $expected = $this->amount_for_order($order);
            $mismatch = ($paid_currency !== $expected['currency']) || (abs(floatval($paid_amount) - floatval($expected['value'])) > 0.01);
        } catch (Exception $e) {
            $expected = array(
                'value'          => '',
                'currency'       => $paid_currency,
                'rate'           => 0,
                'order_currency' => isset($order['currency']) ? $order['currency'] : '',
            );
            $mismatch = true;

            $this->log('could not price order ' . $order_id . ' while recording a capture: ' . $e->getMessage(), array());
        }

        $table = $wpdb->prefix . 'cstore_orders';

        $update = array(
            'payment_method' => 'paypal',
            'payment_status' => $mismatch ? 'partial' : $payment_status,
            'transaction_id' => $capture_id,
        );

        /* Only an order still waiting to be dealt with is advanced - including
           an incomplete one, which is an abandoned checkout that just got paid.
           An order a human has already moved on (canceled, on hold, confirmed,
           delivered) keeps the status they gave it. */
        if ($payment_status === 'paid' && !$mismatch && in_array($order['status'], array('pending', 'incomplete', 'failed', ''), true)) {
            $update['status'] = !empty($this->settings['order_status_on_paid']) ? $this->settings['order_status_on_paid'] : 'processing';
        }

        $wpdb->update($table, $update, array('id' => $order_id));

        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_capture_id', $capture_id);
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_capture', wp_json_encode($capture));
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_paid_amount', $paid_amount);
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_paid_currency', $paid_currency);
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_exchange_rate', $expected['rate']);
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_mode', $this->settings['mode']);

        $note = sprintf(
            'PayPal payment %s: %s %s captured (transaction %s)%s.',
            $mismatch ? 'needs review' : 'received',
            $paid_amount,
            $paid_currency,
            $capture_id,
            $expected['rate'] != 1 ? sprintf(' at %s %s per 1 %s', $expected['rate'], $expected['order_currency'], $expected['currency']) : ''
        );

        if ($mismatch) {
            $note .= sprintf(' Expected %s %s for this order - do not fulfil before checking.', $expected['value'], $expected['currency']);

            $this->log('captured amount does not match order ' . $order_id, array(
                'paid'     => $paid_amount . ' ' . $paid_currency,
                'expected' => $expected['value'] . ' ' . $expected['currency'],
            ));
        }

        $ComboStoreOrders->add_order_note(array(
            'order_id' => $order_id,
            'note'     => $note,
            'author'   => 'PayPal',
        ));

        /*
         * Nothing in this plugin mails on payment yet - the order mails hang
         * off `combo_store_order_created` and `combo_store_order_updated`. This
         * is the hook to add that to, and it is deliberately not one of those
         * two: their handlers expect the full checkout payload, which a
         * capture does not have.
         */
        do_action('combo_store_order_paid', array(
            'order_id'       => intval($order_id),
            'order_hash'     => $order_hash,
            'gateway'        => 'paypal',
            'transaction_id' => $capture_id,
            'amount'         => $paid_amount,
            'currency'       => $paid_currency,
            'needs_review'   => $mismatch,
        ));

        return array(
            'order_id'       => intval($order_id),
            'order_hash'     => $order_hash,
            'transaction_id' => $capture_id,
            'payment_status' => $update['payment_status'],
            'status'         => isset($update['status']) ? $update['status'] : $order['status'],
            'needs_review'   => $mismatch,
            'already_paid'   => false,
        );
    }

    /**
     * Refund a captured PayPal payment, in full or in part.
     *
     * A PayPal capture can only be refunded through PayPal, so this is the only
     * way the dashboard's refund flow can give the money back for a PayPal
     * order. Amounts are in the currency that was charged, not the store's.
     *
     * @param int    $order_id
     * @param float  $amount Optional; the whole capture when omitted.
     * @param string $reason
     * @return array
     * @throws Exception
     */
    public function refund($order_id, $amount = 0, $reason = '')
    {
        global $wpdb;

        $order_id = intval($order_id);

        $ComboStoreOrders     = new ComboStoreOrders();
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        $order = $ComboStoreOrders->get_order($order_id);

        if (empty($order['id'])) {
            throw new Exception('That order could not be found.');
        }

        $capture_id = $ComboStoreObjectMeta->get_meta('orders', $order_id, 'paypal_capture_id');

        if (empty($capture_id)) {
            throw new Exception('This order has no PayPal payment to refund.');
        }

        $payload = array();

        if (floatval($amount) > 0) {
            $currency = $ComboStoreObjectMeta->get_meta('orders', $order_id, 'paypal_paid_currency');

            if (empty($currency)) {
                $currency = $this->settings['currency'];
            }

            $payload['amount'] = array(
                'currency_code' => strtoupper($currency),
                'value'         => $this->money(floatval($amount), $currency),
            );
        }

        if (!empty($reason)) {
            $payload['note_to_payer'] = substr(sanitize_text_field($reason), 0, 255);
        }

        $result = $this->request('POST', '/v2/payments/captures/' . rawurlencode($capture_id) . '/refund', empty($payload) ? null : $payload);

        if ($result['status'] >= 400 || empty($result['body']['id'])) {
            $this->log('refund failed for order ' . $order_id, $result);

            throw new Exception($this->error_message($result, 'PayPal could not refund this payment.'));
        }

        $refund = $result['body'];
        $full   = floatval($amount) <= 0;

        $wpdb->update(
            $wpdb->prefix . 'cstore_orders',
            array('payment_status' => $full ? 'refunded' : 'partial'),
            array('id' => $order_id)
        );

        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_refund_id', sanitize_text_field($refund['id']));
        $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_refund', wp_json_encode($refund));

        $ComboStoreOrders->add_order_note(array(
            'order_id' => $order_id,
            'note'     => sprintf(
                'PayPal refund %s: %s %s (refund %s).',
                $full ? 'in full' : 'in part',
                isset($refund['amount']['value']) ? $refund['amount']['value'] : '',
                isset($refund['amount']['currency_code']) ? $refund['amount']['currency_code'] : '',
                $refund['id']
            ),
            'author'   => 'PayPal',
        ));

        do_action('combo_store_order_refunded_paypal', array(
            'order_id'  => $order_id,
            'refund_id' => $refund['id'],
            'full'      => $full,
        ));

        return array(
            'order_id'       => $order_id,
            'refund_id'      => $refund['id'],
            'status'         => isset($refund['status']) ? $refund['status'] : '',
            'payment_status' => $full ? 'refunded' : 'partial',
        );
    }


    /* -------------------------------------------------------------- webhook */

    /**
     * Check a webhook really came from PayPal.
     *
     * PayPal verifies its own signatures through an API call, which needs the
     * webhook id from the PayPal dashboard. Without that id configured there is
     * no way to tell a real event from a forged one, so an unverifiable webhook
     * is refused rather than trusted.
     *
     * @param array  $headers  As given by WP_REST_Request::get_headers().
     * @param string $raw_body
     * @return bool
     */
    public function verify_webhook($headers, $raw_body)
    {
        $webhook_id = $this->settings['webhook_id'];

        if (empty($webhook_id)) {
            $this->log('webhook rejected: no webhook id configured', array());

            return false;
        }

        $get = function ($name) use ($headers) {
            $key = strtolower(str_replace('-', '_', $name));

            if (isset($headers[$key])) {
                return is_array($headers[$key]) ? reset($headers[$key]) : $headers[$key];
            }

            return '';
        };

        $event = json_decode($raw_body, true);

        if (!is_array($event)) {
            return false;
        }

        $payload = array(
            'transmission_id'   => $get('paypal_transmission_id'),
            'transmission_time' => $get('paypal_transmission_time'),
            'cert_url'          => $get('paypal_cert_url'),
            'auth_algo'         => $get('paypal_auth_algo'),
            'transmission_sig'  => $get('paypal_transmission_sig'),
            'webhook_id'        => $webhook_id,
            'webhook_event'     => $event,
        );

        foreach (array('transmission_id', 'transmission_time', 'cert_url', 'auth_algo', 'transmission_sig') as $required) {
            if (empty($payload[$required])) {
                $this->log('webhook rejected: missing ' . $required, array());

                return false;
            }
        }

        try {
            $result = $this->request('POST', '/v1/notifications/verify-webhook-signature', $payload);
        } catch (Exception $e) {
            $this->log('webhook verification call failed: ' . $e->getMessage(), array());

            return false;
        }

        $verified = isset($result['body']['verification_status']) && $result['body']['verification_status'] === 'SUCCESS';

        if (!$verified) {
            $this->log('webhook rejected: signature not verified', $result);
        }

        return $verified;
    }

    /**
     * Act on a verified webhook event.
     *
     * Only the events that change what the store knows are handled; anything
     * else is acknowledged so PayPal stops retrying it.
     *
     * @param array $event
     * @return array
     */
    public function handle_webhook($event)
    {
        $type     = isset($event['event_type']) ? $event['event_type'] : '';
        $resource = isset($event['resource']) ? $event['resource'] : array();

        $ComboStoreOrders     = new ComboStoreOrders();
        $ComboStoreObjectMeta = new ComboStoreObjectMeta();

        switch ($type) {
            case 'PAYMENT.CAPTURE.COMPLETED':
            case 'PAYMENT.CAPTURE.PENDING':
                $order_hash = isset($resource['custom_id']) ? sanitize_text_field($resource['custom_id']) : '';

                if (empty($order_hash)) {
                    return array('handled' => false, 'reason' => 'no custom_id on the capture');
                }

                $this->settle_capture(
                    $order_hash,
                    $resource,
                    $type === 'PAYMENT.CAPTURE.PENDING' ? 'pending' : 'paid'
                );

                return array('handled' => true, 'event' => $type);

            case 'CHECKOUT.ORDER.APPROVED':
                /* The buyer approved but the browser never came back - capture
                   it here so the order is not left unpaid with the money
                   authorised. */
                $paypal_order_id = isset($resource['id']) ? sanitize_text_field($resource['id']) : '';

                if (empty($paypal_order_id)) {
                    return array('handled' => false, 'reason' => 'no order id on the event');
                }

                $order_hash = '';

                if (!empty($resource['purchase_units'][0]['custom_id'])) {
                    $order_hash = sanitize_text_field($resource['purchase_units'][0]['custom_id']);
                }

                if (!empty($order_hash)) {
                    $order_id = $ComboStoreOrders->get_order_id_by_hash($order_hash);
                    $order    = $order_id ? $ComboStoreOrders->get_order($order_id) : array();

                    if (!empty($order['payment_status']) && $order['payment_status'] === 'paid') {
                        return array('handled' => true, 'event' => $type, 'note' => 'already paid');
                    }
                }

                $this->capture_order($paypal_order_id);

                return array('handled' => true, 'event' => $type, 'note' => 'captured from webhook');

            case 'PAYMENT.CAPTURE.DENIED':
            case 'PAYMENT.CAPTURE.REVERSED':
            case 'PAYMENT.CAPTURE.REFUNDED':
                $order_hash = isset($resource['custom_id']) ? sanitize_text_field($resource['custom_id']) : '';
                $order_id   = $order_hash ? $ComboStoreOrders->get_order_id_by_hash($order_hash) : 0;

                if (empty($order_id)) {
                    return array('handled' => false, 'reason' => 'no order for this event');
                }

                global $wpdb;

                $payment_status = ($type === 'PAYMENT.CAPTURE.DENIED') ? 'failed' : 'refunded';

                $wpdb->update(
                    $wpdb->prefix . 'cstore_orders',
                    array('payment_status' => $payment_status),
                    array('id' => $order_id)
                );

                $ComboStoreObjectMeta->update_meta('orders', $order_id, 'paypal_last_event', $type);

                $ComboStoreOrders->add_order_note(array(
                    'order_id' => $order_id,
                    'note'     => 'PayPal reported ' . $type . '. Payment marked ' . $payment_status . '.',
                    'author'   => 'PayPal',
                ));

                do_action('combo_store_order_payment_failed', array(
                    'order_id' => intval($order_id),
                    'gateway'  => 'paypal',
                    'event'    => $type,
                ));

                return array('handled' => true, 'event' => $type);
        }

        return array('handled' => false, 'reason' => 'event not handled', 'event' => $type);
    }


    /* --------------------------------------------------------------- shared */

    /**
     * Prove the stored credentials work, for a "test connection" button.
     *
     * @return array
     */
    public function test_connection()
    {
        $this->forget_access_token();

        $token = $this->access_token();

        return array(
            'ok'   => !empty($token),
            'mode' => $this->settings['mode'],
        );
    }

    private function order_description($order)
    {
        $site  = get_bloginfo('name');
        $label = sprintf('Order #%s', $order['id']);

        $description = !empty($site) ? $site . ' - ' . $label : $label;

        /* PayPal caps the description at 127 characters. */
        return substr($description, 0, 127);
    }

    private function link($body, $rel)
    {
        if (empty($body['links']) || !is_array($body['links'])) {
            return '';
        }

        foreach ($body['links'] as $link) {
            if (isset($link['rel']) && $link['rel'] === $rel) {
                return isset($link['href']) ? $link['href'] : '';
            }
        }

        return '';
    }

    /**
     * The capture out of a capture or order-read response, wherever PayPal put
     * it this time.
     */
    private function first_capture($body)
    {
        if (!empty($body['purchase_units'][0]['payments']['captures'][0])) {
            return $body['purchase_units'][0]['payments']['captures'][0];
        }

        /* A capture webhook hands over the capture itself. */
        if (!empty($body['id']) && isset($body['amount']['value'])) {
            return $body;
        }

        return array();
    }

    private function custom_id($body, $capture)
    {
        if (!empty($capture['custom_id'])) {
            return sanitize_text_field($capture['custom_id']);
        }

        if (!empty($body['purchase_units'][0]['custom_id'])) {
            return sanitize_text_field($body['purchase_units'][0]['custom_id']);
        }

        return '';
    }

    private function has_issue($result, $issue)
    {
        if (empty($result['body']['details']) || !is_array($result['body']['details'])) {
            return false;
        }

        foreach ($result['body']['details'] as $detail) {
            if (isset($detail['issue']) && $detail['issue'] === $issue) {
                return true;
            }
        }

        return false;
    }

    /**
     * A message worth showing a buyer, built from PayPal's error body.
     */
    private function error_message($result, $fallback)
    {
        $body = $result['body'];

        if (!empty($body['details'][0]['description'])) {
            return $fallback . ' ' . $body['details'][0]['description'];
        }

        if (!empty($body['message'])) {
            return $fallback . ' ' . $body['message'];
        }

        return $fallback;
    }

    /**
     * Failures only, and never the credentials: the client secret and the
     * access token must not reach the log.
     */
    private function log($message, $context = array())
    {
        $line = '[combostore-paypal] ' . $message;

        if (!empty($context)) {
            $safe = isset($context['body']) ? $context['body'] : $context;

            $line .= ' ' . wp_json_encode($safe);
        }

        error_log($line);
    }
}
