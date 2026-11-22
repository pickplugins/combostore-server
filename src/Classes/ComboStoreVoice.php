<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Voice: a cloud PBX / IVR behind one provider interface (Talkify first).
 *
 *  - Click-to-call: an agent on the Calls screen presses "Call via PBX"; the
 *    PBX rings the agent's extension (user meta cstore_pbx_extension), then
 *    the customer, and joins them (voice_click_to_call, get_voice_call).
 *  - Voice broadcast: marketing campaigns with channel 'voice'. Their message
 *    rows are placed here as calls (text-to-speech, optional keypress
 *    options), not by ComboStoreMarketing::process_queue.
 *  - Order confirmation: a new order gets a call, "press 1 to confirm, 2 to
 *    cancel"; the key moves a still-pending order and adds an order note.
 *
 * Table cstore_voice_calls (option combo_store_voice_schema): one row per call
 * attempt. kind click_to_call | broadcast | order_ivr; ref_id is the call-list
 * task, marketing message or order. status queued -> placing -> placed ->
 * (ringing | connected) -> completed | no_answer | busy | failed | cancelled.
 *
 * Results arrive at the public voice_webhook. Its URL is per call and signed
 * (c=<id>&s=<hmac>), so a forged result is refused even if the provider signs
 * nothing; when a webhook secret is set and the provider sends a signature
 * header, that must match too. A call stuck without a result is looked up
 * with the provider's status API by the dispatcher.
 *
 * Calls are placed by dispatch(), on the every-minute cstore_marketing_tick,
 * only within the calling hours and at most `concurrency` at a time.
 *
 * Settings live in option combo_store_voice; secrets never leave the server
 * (get_voice_settings blanks them and reports has_* flags).
 */
class ComboStoreVoice
{
    const OPTION = 'combo_store_voice';
    const SCHEMA_VERSION = 1;
    const SCHEMA_OPTION = 'combo_store_voice_schema';
    const LOCK = 'cstore_voice_dispatch';
    const ADMIN_ROLES = ['administrator'];
    const CALL_ROLES = ['administrator', 'store_manager', 'support_agent'];
    const KINDS = ['click_to_call', 'broadcast', 'order_ivr'];
    const FINAL = ['completed', 'no_answer', 'busy', 'failed', 'cancelled'];
    const LIVE = ['placing', 'placed', 'ringing', 'connected'];
    const SECRETS = [
        'talkify.api_key' => 'has_talkify_api_key',
        'talkify.webhook_secret' => 'has_talkify_webhook_secret',
        'hellopbx.api_token' => 'has_hellopbx_api_token',
        'hellopbx.webhook_secret' => 'has_hellopbx_webhook_secret',
        'epbx.api_token' => 'has_epbx_api_token',
    ];
    /* Built-in providers; more can be added with the combo_store_voice_providers filter. */
    const PROVIDERS = [
        'talkify' => TalkifyVoiceProvider::class,
        'hellopbx' => HelloPbxVoiceProvider::class,
        'epbx' => EpbxVoiceProvider::class,
    ];
    const ORDER_CONFIRM_STATUSES = ['processing', 'hold'];
    const ORDER_CANCEL_STATUSES = ['cancelled', 'hold'];

    /** @var ComboStoreVoice|null */
    public static $instance = null;

    public function __construct()
    {
        self::$instance = $this;
        add_action('rest_api_init', array($this, 'maybe_upgrade_schema'), 6);
        add_action('rest_api_init', array($this, 'register_routes'));
        /* The marketing cron runs every minute; place due calls on it. */
        add_action('cstore_marketing_tick', array($this, 'dispatch'), 20);
        add_action('cstore_voice_dispatch', array($this, 'dispatch'));
        add_action('combo_store_order_created', array($this, 'on_order_created'), 25);
    }


    /* ---------------------------------------------------------------- */
    /* Schema                                                            */

    private function table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_voice_calls';
    }

    private function mkt_table($name)
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_mkt_' . $name;
    }

    public function maybe_upgrade_schema()
    {
        if ((int) get_option(self::SCHEMA_OPTION, 0) >= self::SCHEMA_VERSION) return;
        global $wpdb;
        $t = $this->table();
        $c = $wpdb->get_charset_collate();
        $ok = $wpdb->query("CREATE TABLE IF NOT EXISTS $t (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            provider VARCHAR(30) NOT NULL DEFAULT '',
            provider_call_id VARCHAR(100) NOT NULL DEFAULT '',
            kind VARCHAR(20) NOT NULL,
            ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            campaign_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            contact_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            agent_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            phone VARCHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            phase VARCHAR(40) NOT NULL DEFAULT '',
            attempt SMALLINT UNSIGNED NOT NULL DEFAULT 1,
            send_after DATETIME DEFAULT NULL,
            placed_at DATETIME DEFAULT NULL,
            ended_at DATETIME DEFAULT NULL,
            checked_at DATETIME DEFAULT NULL,
            answered TINYINT(1) NOT NULL DEFAULT 0,
            dtmf VARCHAR(10) NOT NULL DEFAULT '',
            dtmf_label VARCHAR(100) NOT NULL DEFAULT '',
            duration INT UNSIGNED NOT NULL DEFAULT 0,
            hangup VARCHAR(100) NOT NULL DEFAULT '',
            recording_url VARCHAR(500) NOT NULL DEFAULT '',
            error VARCHAR(500) NOT NULL DEFAULT '',
            verified VARCHAR(10) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status_due (status, send_after),
            KEY kind_ref (kind, ref_id),
            KEY provider_call (provider_call_id),
            KEY agent (agent_id),
            KEY campaign (campaign_id)
        ) $c");
        if ($ok === false) return;
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION, false);
    }

    public function register_routes()
    {
        $routes = [
            /* Admin: settings, test, the call log. */
            'get_voice_settings', 'update_voice_settings', 'voice_test', 'get_voice_calls',
            /* Agents (call roles): click-to-call from the Calls screen. */
            'get_voice_me', 'voice_click_to_call', 'get_voice_call', 'get_voice_recording',
        ];
        foreach ($routes as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods' => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
        /* The provider posts call results here; the URL is signed per call. */
        register_rest_route('combo-store/v2', '/voice_webhook', array(
            'methods' => 'POST',
            'callback' => array($this, 'voice_webhook'),
            'permission_callback' => '__return_true',
        ));
    }


    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */

    private function user_from_token($request)
    {
        $token = $request->get_header('Authorization');
        if (!$token) return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        try {
            $decoded = JWT::decode(str_replace('Bearer ', '', $token), new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        $user_id = $decoded->sub ?? $decoded->user_id ?? $decoded->data->user->id ?? null;
        $user = $user_id ? get_user_by('id', $user_id) : null;
        return $user ?: new WP_Error('invalid_token', 'Unknown user', array('status' => 401));
    }

    private function auth($request)
    {
        $user = $this->user_from_token($request);
        if (is_wp_error($user)) return $user;
        if (!array_intersect(self::ADMIN_ROLES, (array) $user->roles)) return new WP_Error('forbidden', 'You are not allowed to manage marketing', array('status' => 403));
        return $user;
    }

    private function call_auth($request)
    {
        $user = $this->user_from_token($request);
        if (is_wp_error($user)) return $user;
        if (!array_intersect(self::CALL_ROLES, (array) $user->roles)) return new WP_Error('forbidden', 'You are not allowed to work call lists', array('status' => 403));
        return $user;
    }

    private function is_admin_user($user)
    {
        return (bool) array_intersect(self::ADMIN_ROLES, (array) $user->roles);
    }

    private function body($request)
    {
        $b = json_decode($request->get_body(), true);
        return is_array($b) ? $b : [];
    }

    private function respond($data)
    {
        die(wp_json_encode($data));
    }

    private function fail($message, $status = 400)
    {
        return new WP_Error('voice_error', $message, array('status' => $status));
    }

    private function now()
    {
        return current_time('mysql');
    }

    private function later($minutes)
    {
        return gmdate('Y-m-d H:i:s', strtotime($this->now()) + (int) $minutes * MINUTE_IN_SECONDS);
    }

    private function sign($id)
    {
        return substr(hash_hmac('sha256', 'voice|' . (int) $id, wp_salt('auth') . '|cstore_voice'), 0, 32);
    }

    /** Our reference for a call at the provider: one per row (each attempt is a row), per site. */
    private function external_id($row)
    {
        return 'cs' . substr(md5(home_url()), 0, 6) . '-' . $row->kind . '-' . (int) $row->id;
    }

    private function webhook_url($id)
    {
        return add_query_arg(['c' => (int) $id, 's' => $this->sign($id)], rest_url('combo-store/v2/voice_webhook'));
    }

    private function marketing()
    {
        return class_exists(__NAMESPACE__ . '\\ComboStoreMarketing') ? ComboStoreMarketing::$instance : null;
    }

    /**
     * International digits for the PBX ("8801711000000"), or null. Uses the
     * marketing default country code (Settings > SMS) for local numbers.
     */
    public function provider_number($phone)
    {
        $phone = trim((string) $phone);
        if ($phone === '') return null;
        $digits = preg_replace('/\D+/', '', $phone);
        if (strpos($phone, '+') === 0) return strlen($digits) >= 8 ? $digits : null;
        if (strpos($digits, '00') === 0) return substr($digits, 2);
        $m = $this->marketing();
        $cc = $m ? preg_replace('/\D+/', '', (string) $m->setting('sms.country_code', '')) : '';
        if ($cc !== '' && strpos($digits, $cc) === 0 && strlen($digits) > 10) return $digits;
        if ($cc !== '' && strpos($digits, '0') === 0) return $cc . substr($digits, 1);
        return null;
    }


    /* ---------------------------------------------------------------- */
    /* Settings                                                          */

    private function default_settings()
    {
        return [
            'provider' => 'talkify',
            'talkify' => ['api_key' => '', 'webhook_secret' => '', 'base_url' => 'https://app.talkify.com.bd/api/v1'],
            /* HelloPBX plays IVRs built in its panel (no text-to-speech): order_ivr_id is the
               order-confirmation IVR (blank = its default one); caller_id is optional. */
            'hellopbx' => ['api_token' => '', 'webhook_secret' => '', 'base_url' => 'https://hellopbx.bd/api', 'caller_id' => '', 'order_ivr_id' => 0],
            /* ePBX: order calls speak our text with fixed keys (1 confirm, 2 cancel); no voice campaigns; webhooks unsigned. */
            'epbx' => ['api_token' => '', 'base_url' => 'https://epbx.bd/api', 'caller_id' => ''],
            /* Automatic calls (broadcasts, order confirmations) only in these store hours. */
            'hours' => ['from' => 9, 'to' => 21],
            /* Automatic calls in progress at once (Talkify's free plan allows 2). */
            'concurrency' => 2,
            'order_ivr' => [
                'enabled' => false,
                'delay_minutes' => 5,
                'text' => 'Hello {name}, this is {site_name}. We received your order number {order_id} for {total}. To confirm the order, press 1. To cancel it, press 2.',
                'confirm_key' => '1',
                'cancel_key' => '2',
                'on_confirm' => 'processing',
                'on_cancel' => 'cancelled',
                'attempts' => 2,
                'retry_minutes' => 30,
                'cod_only' => true,
            ],
        ];
    }

    public function settings()
    {
        $d = $this->default_settings();
        $saved = get_option(self::OPTION, []);
        $saved = is_array($saved) ? $saved : [];
        foreach ($d as $k => $v) {
            if (is_array($v)) $d[$k] = array_merge($v, is_array($saved[$k] ?? null) ? $saved[$k] : []);
            elseif (array_key_exists($k, $saved)) $d[$k] = $saved[$k];
        }
        return $d;
    }

    /** The configured provider, or null when none is set up. */
    public function provider($key = null)
    {
        $s = $this->settings();
        $key = $key ?: $s['provider'];
        $class = $this->provider_classes()[$key] ?? null;
        if (!$class || !class_exists($class)) return null;
        $p = new $class(is_array($s[$key] ?? null) ? $s[$key] : []);
        return $p instanceof VoiceProvider ? $p : null;
    }

    private function provider_classes()
    {
        return apply_filters('combo_store_voice_providers', self::PROVIDERS);
    }

    /** What the configured provider can do: ['tts' => speaks our text, 'ivr' => plays an IVR by id]. */
    public function capabilities()
    {
        $p = $this->provider();
        return ($p ? $p->capabilities() : []) + ['tts' => false, 'ivr' => false, 'broadcast' => false, 'fixed_keys' => null];
    }

    public function ready()
    {
        $p = $this->provider();
        return $p && $p->configured();
    }

    private function in_hours()
    {
        $h = $this->settings()['hours'];
        $now = (int) current_time('G');
        return $now >= (int) $h['from'] && $now < (int) $h['to'];
    }

    public function get_voice_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $s = $this->settings();
        foreach (self::SECRETS as $path => $flag) {
            [$g, $k] = explode('.', $path);
            $s[$flag] = (string) ($s[$g][$k] ?? '') !== '';
            $s[$g][$k] = '';
        }
        $s['ready'] = $this->ready();
        $s['providers'] = [];
        foreach ($this->provider_classes() as $key => $class) {
            if (!class_exists($class)) continue;
            $p = new $class(is_array($s[$key] ?? null) ? $s[$key] : []);
            if ($p instanceof VoiceProvider) $s['providers'][] = ['key' => $key, 'label' => $p->label(), 'capabilities' => $p->capabilities()];
        }
        $s['agents'] = array_map(function ($u) {
            return [
                'id' => (int) $u->ID,
                'name' => $u->display_name ?: $u->user_login,
                'roles' => array_values((array) $u->roles),
                'extension' => (string) get_user_meta($u->ID, 'cstore_pbx_extension', true),
            ];
        }, get_users(['role__in' => self::CALL_ROLES, 'orderby' => 'display_name', 'number' => 500]));
        $s['order_statuses'] = ['confirm' => self::ORDER_CONFIRM_STATUSES, 'cancel' => self::ORDER_CANCEL_STATUSES];
        $this->respond($s);
    }

    public function update_voice_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $b = $this->body($request);
        $cur = $this->settings();
        $out = $cur;
        $in = function ($g) use ($b) { return is_array($b[$g] ?? null) ? $b[$g] : null; };

        if (isset($b['provider'])) $out['provider'] = array_key_exists((string) $b['provider'], $this->provider_classes()) ? (string) $b['provider'] : $cur['provider'];

        if ($t = $in('talkify')) {
            $base = esc_url_raw(trim((string) ($t['base_url'] ?? '')));
            $out['talkify'] = [
                'api_key' => (string) ($t['api_key'] ?? '') !== '' ? preg_replace('/\s+/', '', sanitize_text_field((string) $t['api_key'])) : $cur['talkify']['api_key'],
                'webhook_secret' => (string) ($t['webhook_secret'] ?? '') !== '' ? sanitize_text_field((string) $t['webhook_secret']) : $cur['talkify']['webhook_secret'],
                'base_url' => preg_match('#^https://#', $base) ? untrailingslashit($base) : 'https://app.talkify.com.bd/api/v1',
            ];
            if (!empty($t['clear_webhook_secret'])) $out['talkify']['webhook_secret'] = '';
        }
        if ($t = $in('hellopbx')) {
            $base = esc_url_raw(trim((string) ($t['base_url'] ?? '')));
            $out['hellopbx'] = [
                'api_token' => (string) ($t['api_token'] ?? '') !== '' ? preg_replace('/\s+/', '', sanitize_text_field((string) $t['api_token'])) : $cur['hellopbx']['api_token'],
                'webhook_secret' => (string) ($t['webhook_secret'] ?? '') !== '' ? sanitize_text_field((string) $t['webhook_secret']) : $cur['hellopbx']['webhook_secret'],
                'base_url' => preg_match('#^https://#', $base) ? untrailingslashit($base) : 'https://hellopbx.bd/api',
                'caller_id' => preg_replace('/[^0-9+]/', '', (string) ($t['caller_id'] ?? '')),
                'order_ivr_id' => absint($t['order_ivr_id'] ?? 0),
            ];
            if (!empty($t['clear_webhook_secret'])) $out['hellopbx']['webhook_secret'] = '';
        }
        if ($t = $in('epbx')) {
            $base = esc_url_raw(trim((string) ($t['base_url'] ?? '')));
            $out['epbx'] = [
                'api_token' => (string) ($t['api_token'] ?? '') !== '' ? preg_replace('/\s+/', '', sanitize_text_field((string) $t['api_token'])) : $cur['epbx']['api_token'],
                'base_url' => preg_match('#^https://#', $base) ? untrailingslashit($base) : 'https://epbx.bd/api',
                'caller_id' => preg_replace('/[^0-9+]/', '', (string) ($t['caller_id'] ?? '')),
            ];
        }
        if ($h = $in('hours')) {
            $from = min(23, max(0, absint($h['from'] ?? 9)));
            $to = min(24, max(1, absint($h['to'] ?? 21)));
            $out['hours'] = ['from' => $from, 'to' => max($from + 1, $to)];
        }
        if (isset($b['concurrency'])) $out['concurrency'] = min(50, max(1, absint($b['concurrency'])));

        if ($o = $in('order_ivr')) {
            $key = function ($v, $default) { $v = (string) $v; return preg_match('/^[0-9*#]$/', $v) ? $v : $default; };
            $confirm = $key($o['confirm_key'] ?? '1', '1');
            $cancel = $key($o['cancel_key'] ?? '2', '2');
            if ($cancel === $confirm) return $this->fail('The confirm and cancel keys must be different.');
            $text = trim(mb_substr(sanitize_textarea_field((string) ($o['text'] ?? '')), 0, 1000));
            $out['order_ivr'] = [
                'enabled' => !empty($o['enabled']),
                'delay_minutes' => min(1440, absint($o['delay_minutes'] ?? 5)),
                'text' => $text !== '' ? $text : $this->default_settings()['order_ivr']['text'],
                'confirm_key' => $confirm,
                'cancel_key' => $cancel,
                'on_confirm' => in_array($o['on_confirm'] ?? '', self::ORDER_CONFIRM_STATUSES, true) ? $o['on_confirm'] : 'processing',
                'on_cancel' => in_array($o['on_cancel'] ?? '', self::ORDER_CANCEL_STATUSES, true) ? $o['on_cancel'] : 'cancelled',
                'attempts' => min(5, max(1, absint($o['attempts'] ?? 2))),
                'retry_minutes' => min(1440, max(5, absint($o['retry_minutes'] ?? 30))),
                'cod_only' => !empty($o['cod_only']),
            ];
        }

        /* Extensions: user id => extension, only for users who may make calls. */
        if (is_array($b['extensions'] ?? null)) {
            foreach ($b['extensions'] as $uid => $ext) {
                $u = get_user_by('id', absint($uid));
                if (!$u || !array_intersect(self::CALL_ROLES, (array) $u->roles)) continue;
                $ext = trim((string) $ext);
                if ($ext === '') {
                    delete_user_meta($u->ID, 'cstore_pbx_extension');
                } elseif (preg_match('/^[A-Za-z0-9@._+\-]{1,64}$/', $ext)) {
                    update_user_meta($u->ID, 'cstore_pbx_extension', $ext);
                } else {
                    return $this->fail('Extension for ' . ($u->display_name ?: $u->user_login) . ' is not valid: use the extension number (or the agent\'s email).');
                }
            }
        }

        update_option(self::OPTION, $out, false);
        $this->respond(['status' => 'success']);
    }

    public function voice_test($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        $p = $this->provider();
        if (!$p || !$p->configured()) return $this->fail('Add the API key first, then save.');
        $r = $p->test();
        if (!$r['ok']) return $this->fail($r['error']);
        $this->respond(['status' => 'success', 'message' => ($r['message'] ?? '') ?: 'Connected: the API key works.']);
    }


    /* ---------------------------------------------------------------- */
    /* Calls                                                             */

    private function row($id)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table()} WHERE id = %d", $id));
    }

    private function insert(array $data)
    {
        global $wpdb;
        $now = $this->now();
        $data += ['provider' => $this->settings()['provider'], 'status' => 'queued', 'created_at' => $now, 'updated_at' => $now];
        $wpdb->insert($this->table(), $data);
        return (int) $wpdb->insert_id;
    }

    private function update($id, array $data)
    {
        global $wpdb;
        $data['updated_at'] = $this->now();
        $wpdb->update($this->table(), $data, ['id' => (int) $id]);
    }

    /** A call for the dashboard. */
    private function shape($r)
    {
        if (!$r) return null;
        return [
            'id' => (int) $r->id,
            'kind' => $r->kind,
            'ref_id' => (int) $r->ref_id,
            'campaign_id' => (int) $r->campaign_id,
            'agent_id' => (int) $r->agent_id,
            'phone' => $r->phone,
            'status' => $r->status,
            'phase' => $r->phase,
            'final' => in_array($r->status, self::FINAL, true),
            'attempt' => (int) $r->attempt,
            'answered' => (bool) $r->answered,
            'dtmf' => $r->dtmf,
            'dtmf_label' => $r->dtmf_label,
            'duration' => (int) $r->duration,
            'hangup' => $r->hangup,
            'recording_url' => $r->recording_url,
            'error' => $r->error,
            'send_after' => $r->send_after,
            'placed_at' => $r->placed_at,
            'ended_at' => $r->ended_at,
            'created_at' => $r->created_at,
        ];
    }

    /**
     * Place one queued call (kind broadcast or order_ivr) with the provider.
     * $text and $dtmf are already filled for this person. $extra goes to the
     * provider too: purpose (broadcast | order), ivr_id, and for an order
     * order_id, customer_name, amount, store_name.
     */
    private function place($row, $text, array $dtmf, array $extra = [])
    {
        $p = $this->provider($row->provider);
        if (!$p) return $this->finish_failed($row, 'The voice provider is not set up.');
        $number = $this->provider_number($row->phone);
        if (!$number) return $this->finish_failed($row, 'The phone number has no country code. Set one in Marketing settings > SMS.');

        $this->update($row->id, ['status' => 'placing']);
        $res = $p->place_call($extra + [
            'phone' => $number,
            'text' => $text,
            'dtmf' => $dtmf,
            /* The provider ignores a repeated reference, so placing the same row twice never dials twice. */
            'ref' => $this->external_id($row),
            'webhook' => $this->webhook_url($row->id),
            'purpose' => $row->kind === 'order_ivr' ? 'order' : 'broadcast',
            'ivr_id' => 0,
        ]);
        if (!$res['ok']) return $this->finish_failed($this->row($row->id), $res['error']);
        $this->update($row->id, ['status' => 'placed', 'provider_call_id' => mb_substr((string) $res['id'], 0, 100), 'phase' => strtolower((string) $res['status']), 'placed_at' => $this->now()]);
        return true;
    }

    /** What a provider needs to look a call up. */
    private function call_info($row)
    {
        return [
            'kind' => $row->kind,
            'provider_id' => (string) $row->provider_call_id,
            'phone' => (string) $this->provider_number($row->phone),
            'extension' => $row->agent_id ? (string) get_user_meta($row->agent_id, 'cstore_pbx_extension', true) : '',
            'placed_at' => (string) ($row->placed_at ?: $row->created_at),
        ];
    }

    private function finish_failed($row, $error)
    {
        $this->apply_event($row, ['final' => true, 'state' => 'failed', 'answered' => false, 'error' => $error], '');
        return false;
    }

    /**
     * Record what the provider said about a call. A final event is applied
     * once (the provider retries webhooks; polling may race them): the row
     * moves to a final status only if it was not final yet, and only then
     * do orders and campaign messages change.
     */
    private function apply_event($row, array $ev, $verified)
    {
        global $wpdb;
        if (!$row) return;
        $ev += ['final' => false, 'state' => '', 'phase' => '', 'answered' => false, 'dtmf' => '', 'dtmf_label' => '', 'duration' => 0, 'hangup' => '', 'recording_url' => '', 'provider_id' => '', 'error' => '', 'intent' => ''];
        /* A provider that answers "confirmed" / "rejected" (HelloPBX's and ePBX's
           order calls): that decides it. The label records the decision; the digit
           stays what the provider reported (ePBX), or the configured key when it
           reports none (HelloPBX). */
        if ($ev['intent'] !== '' && $row->kind === 'order_ivr') {
            $o = $this->settings()['order_ivr'];
            if ($ev['dtmf'] === '') $ev['dtmf'] = $ev['intent'] === 'confirm' ? $o['confirm_key'] : $o['cancel_key'];
            $ev['dtmf_label'] = $ev['intent'] === 'confirm' ? 'Confirm' : 'Cancel';
            $ev['answered'] = true;
        }
        $now = $this->now();
        $common = ['checked_at' => $now, 'updated_at' => $now];
        if ($ev['provider_id'] !== '' && $row->provider_call_id === '') $common['provider_call_id'] = mb_substr((string) $ev['provider_id'], 0, 100);
        if ($ev['recording_url'] !== '') $common['recording_url'] = esc_url_raw(mb_substr((string) $ev['recording_url'], 0, 500));
        if ($verified !== '') $common['verified'] = $verified;

        if (!$ev['final']) {
            $set = $common + ['phase' => mb_substr((string) $ev['phase'], 0, 40)];
            if (in_array($ev['state'], ['ringing', 'connected'], true)) $set['status'] = $ev['state'];
            /* Only while not final: a late "ringing" never reopens an ended call. */
            $cols = implode(', ', array_map(function ($k) { return "$k = %s"; }, array_keys($set)));
            $wpdb->query($wpdb->prepare("UPDATE {$this->table()} SET $cols WHERE id = %d AND status NOT IN ('" . implode("','", self::FINAL) . "')", array_merge(array_values($set), [(int) $row->id])));
            return;
        }

        $state = in_array($ev['state'], self::FINAL, true) ? $ev['state'] : 'completed';
        $set = $common + [
            'status' => $state,
            'phase' => mb_substr((string) ($ev['phase'] ?: $state), 0, 40),
            'answered' => $ev['answered'] ? 1 : 0,
            'dtmf' => mb_substr((string) $ev['dtmf'], 0, 10),
            'dtmf_label' => mb_substr(sanitize_text_field((string) $ev['dtmf_label']), 0, 100),
            'duration' => max(0, (int) $ev['duration']),
            'hangup' => mb_substr(sanitize_text_field((string) $ev['hangup']), 0, 100),
            'error' => mb_substr(sanitize_text_field((string) $ev['error']), 0, 500),
            'ended_at' => $now,
        ];
        $cols = implode(', ', array_map(function ($k) { return "$k = %s"; }, array_keys($set)));
        $claimed = $wpdb->query($wpdb->prepare("UPDATE {$this->table()} SET $cols WHERE id = %d AND status NOT IN ('" . implode("','", self::FINAL) . "')", array_merge(array_values($set), [(int) $row->id])));
        if (!$claimed) return;

        $row = $this->row($row->id);
        if ($row->kind === 'broadcast') $this->broadcast_result($row);
        if ($row->kind === 'order_ivr') $this->order_result($row);
        do_action('combo_store_voice_call_ended', $this->shape($row));
    }


    /* ---------------------------------------------------------------- */
    /* Webhook                                                           */

    public function voice_webhook($request)
    {
        $id = absint($request->get_param('c'));
        $row = $id ? $this->row($id) : null;
        if (!$row || !hash_equals($this->sign($id), (string) $request->get_param('s'))) {
            return new WP_Error('forbidden', 'Unknown call.', array('status' => 403));
        }
        $p = $this->provider($row->provider);
        if (!$p) return new WP_Error('voice_error', 'Provider not set up.', array('status' => 503));

        $raw = $request->get_body();
        $headers = [];
        foreach (['x-talkify-signature', 'x-callcrm-signature', 'x-callcrm-timestamp', 'x-callcrm-event'] as $h) $headers[$h] = (string) $request->get_header($h);
        $verified = $p->verify_webhook($raw, $headers);
        if ($verified === false) return new WP_Error('forbidden', 'Bad signature.', array('status' => 403));

        $json = json_decode($raw, true);
        if (!is_array($json)) return new WP_Error('voice_error', 'Expected JSON.', array('status' => 400));

        /* The signed URL already names the call. (The provider's own call id is
           not compared: a click-to-call may report a different leg's id.) */
        $this->apply_event($row, $p->parse_webhook($json), $verified === true ? 'signed' : 'token');
        $this->respond(['ok' => true]);
    }


    /* ---------------------------------------------------------------- */
    /* Dispatcher                                                        */

    /**
     * Place due automatic calls: order confirmations first, then voice
     * broadcasts, oldest campaign first. Also looks up calls the provider
     * never reported on, and finishes voice campaigns with nothing left.
     */
    public function dispatch()
    {
        global $wpdb;
        if ((int) get_option(self::SCHEMA_OPTION, 0) < self::SCHEMA_VERSION) return;
        if (!$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', self::LOCK))) return;
        try {
            $this->reconcile();
            $this->finish_campaigns();
            if (!$this->ready() || !$this->in_hours()) return;

            $s = $this->settings();
            $t = $this->table();
            $live = (int) $wpdb->get_var("SELECT COUNT(*) FROM $t WHERE kind <> 'click_to_call' AND status IN ('" . implode("','", self::LIVE) . "')");
            $free = max(0, (int) $s['concurrency'] - $live);

            /* Order confirmations. */
            if ($free > 0) {
                $due = (array) $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE kind = 'order_ivr' AND status = 'queued' AND (send_after IS NULL OR send_after <= %s) ORDER BY send_after ASC, id ASC LIMIT %d", $this->now(), $free));
                foreach ($due as $row) {
                    if ($this->place_order_call($row)) $free--;
                }
            }

            /* Voice broadcasts. */
            $m = $this->marketing();
            if ($free > 0 && $m) {
                $campaigns = (array) $wpdb->get_results("SELECT * FROM {$this->mkt_table('campaigns')} WHERE channel = 'voice' AND status = 'sending' ORDER BY started_at ASC, id ASC");
                foreach ($campaigns as $c) {
                    if ($free <= 0) break;
                    $cfg = self::broadcast_config($c->body);
                    $rows = (array) $wpdb->get_results($wpdb->prepare(
                        "SELECT m.id, m.to_addr, c.id contact_id, c.first_name, c.last_name, c.email, c.phone, c.call_status
                         FROM {$this->mkt_table('messages')} m LEFT JOIN {$this->mkt_table('contacts')} c ON c.id = m.contact_id
                         WHERE m.campaign_id = %d AND m.status = 'queued' AND (m.send_after IS NULL OR m.send_after <= %s) ORDER BY m.id ASC LIMIT %d",
                        $c->id, $this->now(), $free
                    ));
                    foreach ($rows as $r) {
                        /* Claim the message, so two dispatchers never call the same person. */
                        if (!$wpdb->query($wpdb->prepare("UPDATE {$this->mkt_table('messages')} SET status = 'calling' WHERE id = %d AND status = 'queued'", $r->id))) continue;
                        if (!$r->contact_id || $r->call_status !== 'allowed' || !$r->phone) {
                            $wpdb->update($this->mkt_table('messages'), ['status' => 'skipped', 'error' => $r->call_status === 'wrong_number' ? 'Wrong number' : 'Asked not to be called, or removed'], ['id' => (int) $r->id]);
                            continue;
                        }
                        $values = $m->merge_values($r, (int) $r->id);
                        $text = trim($m->apply_merge($cfg['text'], $values, false));
                        $id = $this->insert(['kind' => 'broadcast', 'ref_id' => (int) $r->id, 'campaign_id' => (int) $c->id, 'contact_id' => (int) $r->contact_id, 'phone' => $r->to_addr]);
                        $this->place($this->row($id), $text, $cfg['dtmf_options'], ['ivr_id' => $cfg['ivr_id']]);
                        $free--;
                        if ($free <= 0) break;
                    }
                }
                $this->finish_campaigns();
            }
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', self::LOCK));
        }
    }

    /** Calls with no result: ask the provider; give up after 3 hours. */
    private function reconcile()
    {
        global $wpdb;
        $t = $this->table();
        $now = strtotime($this->now());
        $stale = gmdate('Y-m-d H:i:s', $now - 10 * MINUTE_IN_SECONDS);
        $dead = gmdate('Y-m-d H:i:s', $now - 3 * HOUR_IN_SECONDS);

        /* Died while placing (no provider id): the request never finished. */
        foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE status = 'placing' AND updated_at < %s LIMIT 20", gmdate('Y-m-d H:i:s', $now - 5 * MINUTE_IN_SECONDS))) as $row) {
            $this->finish_failed($row, 'The call could not be placed (no answer from the voice provider).');
        }
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $t WHERE status IN ('placed','ringing','connected') AND COALESCE(placed_at, created_at) < %s AND (checked_at IS NULL OR checked_at < %s) ORDER BY id ASC LIMIT 10",
            $stale, $stale
        ));
        foreach ($rows as $row) {
            $p = $this->provider($row->provider);
            /* No provider id is fine: some providers find calls by number and time (call history). */
            if ($p) {
                $r = $p->get_call($this->call_info($row));
                if ($r['ok']) {
                    $this->apply_event($row, $r['event'], '');
                    $row = $this->row($row->id);
                } else {
                    $this->update($row->id, ['checked_at' => $this->now()]);
                }
            }
            if (!in_array($row->status, self::FINAL, true) && (string) ($row->placed_at ?: $row->created_at) < $dead) {
                $this->finish_failed($row, 'No result from the voice provider.');
            }
        }
    }

    /** Voice campaigns with nobody waiting and no call in progress are done. */
    private function finish_campaigns()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->mkt_table('campaigns')} k SET k.status = 'sent', k.finished_at = %s, k.updated_at = %s
             WHERE k.channel = 'voice' AND k.status = 'sending'
               AND NOT EXISTS (SELECT 1 FROM {$this->mkt_table('messages')} m WHERE m.campaign_id = k.id AND m.status IN ('queued','sending','calling'))",
            $this->now(), $this->now()
        ));
    }


    /* ---------------------------------------------------------------- */
    /* Voice broadcasts (marketing channel 'voice')                      */

    /**
     * A voice campaign's settings, from its body (JSON):
     *  text          - what is spoken (text-to-speech); merge tags filled per contact;
     *  dtmf_options  - optional {"1": "Interested", "2": "Not now"}: keys the
     *                  listener may press and what each means in the report;
     *  ivr_id        - for providers without text-to-speech (HelloPBX): the IVR,
     *                  built in the provider's panel, that the call plays.
     */
    public static function broadcast_config($body)
    {
        $c = is_array($body) ? $body : json_decode((string) $body, true);
        $c = is_array($c) ? $c : [];
        $opts = [];
        foreach ((is_array($c['dtmf_options'] ?? null) ? $c['dtmf_options'] : []) as $k => $label) {
            $k = (string) $k;
            $label = trim(mb_substr(sanitize_text_field((string) $label), 0, 60));
            if (preg_match('/^[0-9*#]$/', $k) && $label !== '') $opts[$k] = $label;
            if (count($opts) >= 10) break;
        }
        return [
            'text' => trim(mb_substr(sanitize_textarea_field((string) ($c['text'] ?? '')), 0, 4096)),
            'dtmf_options' => $opts,
            'ivr_id' => absint($c['ivr_id'] ?? 0),
        ];
    }

    /** Why this message cannot be played by the configured provider, or null. */
    private function message_problem(array $cfg)
    {
        $caps = $this->capabilities();
        if (!$caps['broadcast']) {
            $p = $this->provider();
            return ($p ? $p->label() : 'This phone system') . ' has no API for voice campaigns, so they cannot be sent with it. Order confirmation calls and click-to-call still work.';
        }
        if ($caps['tts'] && $cfg['text'] === '') return 'The voice message has no text.';
        if (!$caps['tts'] && $caps['ivr'] && !$cfg['ivr_id']) return 'Choose the IVR this call plays (its ID from your phone system\'s IVR builder).';
        return null;
    }

    /**
     * "Send test" for a voice campaign: one call to $to now, ignoring the
     * calling hours. Logged as a broadcast call with no message (ref_id 0).
     */
    public function test_broadcast($c, $contact, $to)
    {
        if (!$this->ready()) return ['ok' => false, 'error' => 'Set up the voice provider in Marketing settings > Voice first.'];
        $cfg = self::broadcast_config($c->body);
        if ($problem = $this->message_problem($cfg)) return ['ok' => false, 'error' => $problem];
        $m = $this->marketing();
        $text = $m ? trim($m->apply_merge($cfg['text'], $m->merge_values($contact, 0), false)) : $cfg['text'];
        $id = $this->insert(['kind' => 'broadcast', 'ref_id' => 0, 'campaign_id' => (int) ($c->id ?? 0), 'contact_id' => (int) ($contact->id ?? 0), 'phone' => $to]);
        $ok = $this->place($this->row($id), $text, $cfg['dtmf_options'], ['ivr_id' => $cfg['ivr_id']]);
        $row = $this->row($id);
        return $ok ? ['ok' => true, 'error' => '', 'call_id' => $id] : ['ok' => false, 'error' => $row->error ?: 'The call was not placed.'];
    }

    /** Why a voice campaign cannot start, or null. */
    public function broadcast_problem($c)
    {
        if (!$this->ready()) return 'Set up the voice provider in Marketing settings > Voice first.';
        return $this->message_problem(self::broadcast_config($c->body));
    }

    /** The message row follows its call: answered = delivered, the key pressed is the outcome. */
    private function broadcast_result($row)
    {
        global $wpdb;
        $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->mkt_table('messages')} WHERE id = %d", $row->ref_id));
        if (!$msg) return;
        $label = $row->dtmf_label !== '' ? $row->dtmf_label : $row->dtmf;
        $now = $this->now();
        $update = [
            'status' => $row->answered ? 'sent' : 'failed',
            'sent_at' => $now,
            'provider_ref' => $row->provider_call_id,
            'outcome' => $row->dtmf !== '' ? 'key_' . $row->dtmf : ($row->answered ? 'answered' : $row->status),
            'notes' => $row->answered
                ? 'Answered' . ($row->duration ? ' (' . $row->duration . 's)' : '') . ($label !== '' ? ', pressed ' . $row->dtmf . ($row->dtmf_label !== '' ? ' - ' . $row->dtmf_label : '') : '')
                : '',
            'error' => $row->answered ? '' : ($row->error ?: $this->state_label($row->status) . ($row->hangup !== '' ? ' (' . $row->hangup . ')' : '')),
        ];
        if ($row->answered) $update['delivered_at'] = $now;
        $wpdb->update($this->mkt_table('messages'), $update, ['id' => (int) $msg->id]);
        if ($row->answered && $row->contact_id) $wpdb->update($this->mkt_table('contacts'), ['last_sent_at' => $now], ['id' => (int) $row->contact_id]);
        $this->finish_campaigns();
    }

    /** For a voice campaign's report: answered, keys pressed, calls in progress. */
    public function campaign_summary($campaign_id)
    {
        global $wpdb;
        $t = $this->table();
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) calls, SUM(answered = 1) answered, SUM(status = 'no_answer') no_answer, SUM(status = 'busy') busy,
                SUM(status = 'failed') failed, SUM(status IN ('" . implode("','", self::LIVE) . "')) in_progress, SUM(duration) seconds
             FROM $t WHERE kind = 'broadcast' AND campaign_id = %d", $campaign_id
        ), ARRAY_A);
        $keys = [];
        foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT dtmf, COUNT(*) n FROM $t WHERE kind = 'broadcast' AND campaign_id = %d AND dtmf <> '' GROUP BY dtmf", $campaign_id)) as $r) {
            $keys[$r->dtmf] = (int) $r->n;
        }
        $out = array_map('intval', (array) $row);
        $out['keys'] = $keys;
        return $out;
    }


    /* ---------------------------------------------------------------- */
    /* Order confirmation calls                                          */

    private function orders_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_orders';
    }

    /** A new order gets a confirmation call, after the wait set in Voice settings. */
    public function on_order_created($args)
    {
        global $wpdb;
        if ((int) get_option(self::SCHEMA_OPTION, 0) < self::SCHEMA_VERSION) return;
        $cfg = $this->settings()['order_ivr'];
        if (empty($cfg['enabled']) || !$this->ready()) return;
        $order_id = absint($args['order_id'] ?? 0);
        $o = $order_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->orders_table()} WHERE id = %d", $order_id)) : null;
        if (!$o || $o->status !== 'pending') return;
        if (!empty($cfg['cod_only']) && !in_array(strtolower((string) $o->payment_method), ['cod', 'cash_on_delivery', 'cash on delivery'], true)) return;

        $phone = trim((string) ($o->billing_phone ?: $o->shipping_phone));
        if ($phone === '' || !$this->provider_number($phone)) return;
        if ($wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table()} WHERE kind = 'order_ivr' AND ref_id = %d LIMIT 1", $order_id))) return;

        /* Someone who asked not to be called (or whose number reached someone else) is not called. */
        $contact = $this->contact_for_phone($phone);
        if ($contact && $contact->call_status !== 'allowed') return;

        $this->insert([
            'kind' => 'order_ivr',
            'ref_id' => $order_id,
            'contact_id' => $contact ? (int) $contact->id : 0,
            'phone' => $phone,
            'send_after' => $this->later((int) $cfg['delay_minutes']),
        ]);
        if ((int) $cfg['delay_minutes'] === 0) do_action('cstore_voice_dispatch');
    }

    private function contact_for_phone($phone)
    {
        global $wpdb;
        $m = $this->marketing();
        $clean = $m ? $m->clean_phone($phone) : $phone;
        if (!$clean) return null;
        return $wpdb->get_row($wpdb->prepare("SELECT id, call_status FROM {$this->mkt_table('contacts')} WHERE phone = %s", $clean));
    }

    private function place_order_call($row)
    {
        global $wpdb;
        $cfg = $this->settings()['order_ivr'];
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->orders_table()} WHERE id = %d", $row->ref_id));
        if (!$o || $o->status !== 'pending') {
            $this->update($row->id, ['status' => 'cancelled', 'error' => 'The order is no longer pending.', 'ended_at' => $this->now()]);
            return false;
        }
        $first = trim(strtok((string) $o->billing_name, ' ') ?: '');
        $values = [
            '{name}' => $first !== '' ? $first : 'there',
            '{full_name}' => trim((string) $o->billing_name),
            '{order_id}' => (string) $o->id,
            '{total}' => rtrim(rtrim(number_format((float) $o->total_amount, 2, '.', ''), '0'), '.') . ' ' . ($o->currency === 'BDT' ? 'taka' : $o->currency),
            '{site_name}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        ];
        $m = $this->marketing();
        $text = $m ? $m->apply_merge($cfg['text'], $values, false) : strtr($cfg['text'], $values);
        $dtmf = [$cfg['confirm_key'] => 'Confirm', $cfg['cancel_key'] => 'Cancel'];
        $s = $this->settings();
        return $this->place($row, trim($text), $dtmf, [
            'purpose' => 'order',
            /* Providers without text-to-speech play their own order-confirmation IVR. */
            'ivr_id' => absint($s[$row->provider]['order_ivr_id'] ?? 0),
            'order_id' => (string) $o->id,
            'customer_name' => trim((string) $o->billing_name),
            'amount' => (string) round((float) $o->total_amount, 2),
            'store_name' => $values['{site_name}'],
        ]);
    }

    private function order_note($order_id, $note)
    {
        if (!class_exists(__NAMESPACE__ . '\\ComboStoreOrders')) return;
        (new ComboStoreOrders())->add_order_note(['order_id' => (int) $order_id, 'note' => $note, 'author' => 'Phone confirmation', 'author_email' => '']);
    }

    /** The key pressed moves a still-pending order; anything else retries or gives up. Every attempt leaves an order note. */
    private function order_result($row)
    {
        global $wpdb;
        $cfg = $this->settings()['order_ivr'];
        $o = $wpdb->get_row($wpdb->prepare("SELECT id, status FROM {$this->orders_table()} WHERE id = %d", $row->ref_id));
        if (!$o) return;
        $attempts = max(1, (int) $cfg['attempts']);
        $result = 'none';

        /* "Confirm" / "Cancel" is the decision (a provider's confirmed / rejected, or the
           names we give the keys); otherwise the key against the configured ones. */
        $decided = in_array($row->dtmf_label, ['Confirm', 'Cancel'], true)
            ? $row->dtmf_label
            : ($row->dtmf !== '' && $row->dtmf === $cfg['confirm_key'] ? 'Confirm' : ($row->dtmf !== '' && $row->dtmf === $cfg['cancel_key'] ? 'Cancel' : ''));
        if ($row->answered && $decided !== '') {
            $confirm = $decided === 'Confirm';
            $result = $confirm ? 'confirmed' : 'cancelled';
            $to = $confirm ? $cfg['on_confirm'] : $cfg['on_cancel'];
            $moved = $wpdb->query($wpdb->prepare("UPDATE {$this->orders_table()} SET status = %s, updated_at = %s WHERE id = %d AND status = 'pending'", $to, $this->now(), $o->id));
            /* Providers that play their own IVR (HelloPBX) report "confirmed" / "rejected", not the key. */
            $p = $this->provider($row->provider);
            $what = $p && !$p->capabilities()['tts']
                ? ($confirm ? 'the customer confirmed on the call' : 'the customer cancelled on the call')
                : 'the customer pressed ' . $row->dtmf;
            $this->order_note($o->id, $moved
                ? sprintf('%s by phone: %s. Status set to %s.', $confirm ? 'Confirmed' : 'Cancelled', $what, $to)
                : sprintf('On the confirmation call %s, but the order was already "%s", so it was left as it is.', $what, $o->status));
        } else {
            $why = $row->answered
                ? ($row->dtmf !== '' ? 'answered but pressed ' . $row->dtmf : 'answered without pressing a key')
                : strtolower($this->state_label($row->status)) . ($row->error !== '' ? ': ' . $row->error : '');
            if ((int) $row->attempt < $attempts && $o->status === 'pending') {
                $at = $this->later((int) $cfg['retry_minutes']);
                $this->insert(['kind' => 'order_ivr', 'ref_id' => (int) $o->id, 'contact_id' => (int) $row->contact_id, 'phone' => $row->phone, 'attempt' => (int) $row->attempt + 1, 'send_after' => $at, 'provider' => $row->provider]);
                $result = 'retry';
                $this->order_note($o->id, sprintf('Confirmation call %d of %d: %s. Calling again at %s.', $row->attempt, $attempts, $why, mb_substr($at, 11, 5)));
            } else {
                $result = 'unconfirmed';
                $this->order_note($o->id, sprintf('Confirmation call %d of %d: %s. Not confirmed by phone; please check this order.', $row->attempt, $attempts, $why));
            }
        }
        do_action('combo_store_voice_order_result', (int) $o->id, $result, $this->shape($row));
    }

    private function state_label($status)
    {
        return [
            'completed' => 'Answered', 'no_answer' => 'No answer', 'busy' => 'Busy', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
            'queued' => 'Waiting', 'placing' => 'Placing', 'placed' => 'Placed', 'ringing' => 'Ringing', 'connected' => 'Connected',
        ][$status] ?? ucfirst((string) $status);
    }

    /** Admin: the call log, newest first. Body: kind, paged, per_page. */
    public function get_voice_calls($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        global $wpdb;
        $b = $this->body($request);
        $t = $this->table();
        $where = ['1=1'];
        if (in_array($b['kind'] ?? '', self::KINDS, true)) $where[] = $wpdb->prepare('v.kind = %s', $b['kind']);
        $where = implode(' AND ', $where);
        $per = min(100, max(1, absint($b['per_page'] ?? 20)));
        $paged = max(1, absint($b['paged'] ?? 1));
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $t v WHERE $where");
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, o.billing_name order_name, o.status order_status, c.first_name, c.last_name, u.display_name agent_name
             FROM $t v
             LEFT JOIN {$this->orders_table()} o ON v.kind = 'order_ivr' AND o.id = v.ref_id
             LEFT JOIN {$this->mkt_table('contacts')} c ON c.id = v.contact_id
             LEFT JOIN {$wpdb->users} u ON u.ID = v.agent_id
             WHERE $where ORDER BY v.id DESC LIMIT %d OFFSET %d",
            $per, ($paged - 1) * $per
        ));
        $posts = array_map(function ($r) {
            return $this->shape($r) + [
                'name' => trim((string) ($r->order_name ?: trim($r->first_name . ' ' . $r->last_name))),
                'order_status' => $r->order_status,
                'agent_name' => $r->agent_name,
            ];
        }, $rows);
        $this->respond(['posts' => $posts, 'total' => $total, 'max_pages' => max(1, (int) ceil($total / $per))]);
    }


    /* ---------------------------------------------------------------- */
    /* Click-to-call (Calls screen)                                      */

    /** Agents: can I call through the PBX? */
    public function get_voice_me($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;
        $ext = (string) get_user_meta($user->ID, 'cstore_pbx_extension', true);
        $p = $this->provider();
        $this->respond([
            'ready' => $this->ready(),
            'extension' => $ext,
            'enabled' => $this->ready() && $ext !== '',
            'provider' => $this->settings()['provider'],
            'provider_label' => $p ? $p->label() : '',
            /* tts: speaks the campaign's text; ivr: plays an IVR built in the provider's panel. */
            'capabilities' => $this->capabilities(),
        ]);
    }

    /** Agents: ring me, then the contact of the call task I have open. Body: task_id. */
    public function voice_click_to_call($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $task_id = absint($this->body($request)['task_id'] ?? 0);
        $task = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->mkt_table('messages')} WHERE id = %d AND channel = 'call'", $task_id));
        if (!$task || $task->status !== 'calling' || (int) $task->assigned_to !== (int) $user->ID) {
            return $this->fail('Open this contact on the Calls screen first (it may have timed out and gone to someone else).', 409);
        }
        $p = $this->provider();
        if (!$p || !$p->configured()) return $this->fail('The phone system is not set up yet. Ask an admin (Marketing settings > Voice).');
        $ext = (string) get_user_meta($user->ID, 'cstore_pbx_extension', true);
        if ($ext === '') return $this->fail('You have no PBX extension yet. Ask an admin to add it in Marketing settings > Voice.');
        $number = $this->provider_number($task->to_addr);
        if (!$number) return $this->fail('This phone number has no country code, so the PBX cannot dial it.');

        /* A second press while the first call is still ringing returns that call. */
        $open = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table()} WHERE kind = 'click_to_call' AND ref_id = %d AND agent_id = %d AND status IN ('" . implode("','", self::LIVE) . "') AND created_at > %s ORDER BY id DESC LIMIT 1",
            $task_id, $user->ID, gmdate('Y-m-d H:i:s', strtotime($this->now()) - 2 * MINUTE_IN_SECONDS)
        ));
        if ($open) $this->respond(['status' => 'success', 'call' => $this->shape($open)]);

        $id = $this->insert(['kind' => 'click_to_call', 'ref_id' => $task_id, 'campaign_id' => (int) $task->campaign_id, 'contact_id' => (int) $task->contact_id, 'agent_id' => (int) $user->ID, 'phone' => $task->to_addr, 'status' => 'placing']);
        $res = $p->click_to_call([
            'extension' => $ext,
            'agent_email' => $user->user_email,
            'phone' => $number,
            'ref' => $this->external_id($this->row($id)),
            'webhook' => $this->webhook_url($id),
        ]);
        if (!$res['ok']) {
            $this->finish_failed($this->row($id), $res['error']);
            return $this->fail('The PBX did not place the call: ' . $res['error']);
        }
        $this->update($id, ['status' => 'placed', 'provider_call_id' => mb_substr((string) $res['id'], 0, 100), 'phase' => strtolower((string) ($res['status'] ?: 'ringing_agent')), 'placed_at' => $this->now()]);
        $this->respond(['status' => 'success', 'call' => $this->shape($this->row($id))]);
    }

    /** Agents (their own calls) and admins: a call's state; asks the provider when the webhook is late. Body: id. */
    public function get_voice_call($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;
        $row = $this->row(absint($this->body($request)['id'] ?? 0));
        if (!$row || ((int) $row->agent_id !== (int) $user->ID && !$this->is_admin_user($user))) return $this->fail('Call not found.', 404);

        /* At most every 8 seconds per call, to stay inside the provider's rate limit. */
        if (in_array($row->status, ['placed', 'ringing', 'connected'], true) && (!$row->checked_at || strtotime($row->checked_at) < strtotime($this->now()) - 8)) {
            $p = $this->provider($row->provider);
            $r = $p ? $p->get_call($this->call_info($row)) : ['ok' => false];
            if ($r['ok']) $this->apply_event($row, $r['event'], '');
            else $this->update($row->id, ['checked_at' => $this->now()]);
            $row = $this->row($row->id);
        }
        $this->respond(['call' => $this->shape($row)]);
    }

    /**
     * Agents (their own calls) and admins: a call's recording, fetched from the
     * provider with its credentials (HelloPBX recordings need the API token),
     * streamed back as audio. Body: id.
     */
    public function get_voice_recording($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;
        $row = $this->row(absint($this->body($request)['id'] ?? 0));
        if (!$row || ((int) $row->agent_id !== (int) $user->ID && !$this->is_admin_user($user))) return $this->fail('Call not found.', 404);
        if ($row->recording_url === '') return $this->fail('This call has no recording.', 404);
        $p = $this->provider($row->provider);
        $r = $p ? $p->fetch_recording($row->recording_url) : ['ok' => false, 'error' => 'Provider not set up.'];
        if (!$r['ok']) return $this->fail('Recording not available: ' . $r['error'], 502);
        nocache_headers();
        header('Content-Type: ' . (preg_match('#^audio/[a-z0-9.+-]+$#i', (string) $r['type']) ? $r['type'] : 'audio/wav'));
        header('Content-Length: ' . strlen($r['body']));
        header('Content-Disposition: inline; filename="call-' . (int) $row->id . '.' . ($r['type'] === 'audio/mpeg' ? 'mp3' : 'wav') . '"');
        echo $r['body'];
        exit;
    }
}


/**
 * A cloud PBX / IVR provider. Answers are arrays: ['ok' => bool, 'error' => string, ...].
 * Events (parse_webhook, get_call's 'event') are normalized:
 *  final (bool), state (ringing | connected | completed | no_answer | busy | failed),
 *  phase (the provider's own word, for display), answered (bool), dtmf, dtmf_label,
 *  intent ('confirm' | 'cancel' | '': a provider that reports an order call's
 *  answer as a word, not a key), duration (seconds), hangup, recording_url,
 *  provider_id, error.
 */
interface VoiceProvider
{
    public function __construct(array $settings);
    public function label();
    /**
     * ['tts' => speaks text we send, 'ivr' => plays an IVR built in its panel, by id,
     *  'broadcast' => can place voice-campaign calls, 'fixed_keys' => null, or
     *  ['confirm' => '1', 'cancel' => '2'] when the order call's keys are fixed]
     */
    public function capabilities();
    public function configured();
    /** -> + 'message' (optional, shown on success) */
    public function test();
    /**
     * ['phone' => intl digits, 'text', 'dtmf' => [key => label], 'ref', 'webhook',
     *  'purpose' => broadcast | order, 'ivr_id', and for an order: order_id,
     *  customer_name, amount, store_name] -> + 'id', 'status'
     */
    public function place_call(array $call);
    /** ['extension', 'agent_email', 'phone', 'ref', 'webhook'] -> + 'id', 'status' */
    public function click_to_call(array $call);
    /** ['kind', 'provider_id', 'phone' => intl digits, 'extension', 'placed_at'] -> + 'event' */
    public function get_call(array $call);
    /** headers: lower-case name => value. true = signature valid, false = invalid, null = nothing to check */
    public function verify_webhook($raw, array $headers);
    public function parse_webhook(array $json);
    /** A recording the provider serves: -> + 'body', 'type'. Only from the provider's own host. */
    public function fetch_recording($url);
}


/** Shared HTTP plumbing for providers. */
abstract class VoiceProviderBase implements VoiceProvider
{
    protected $s;

    protected function host_of($url)
    {
        return strtolower((string) parse_url((string) $url, PHP_URL_HOST));
    }

    /** GET a recording with the provider's credentials, only from the provider's own host. */
    protected function download($url, array $headers, $base)
    {
        if (!$this->host_of($url) || $this->host_of($url) !== $this->host_of($base) || strpos((string) $url, 'https://') !== 0) {
            return ['ok' => false, 'error' => 'The recording is not on the provider\'s own address.'];
        }
        $res = wp_remote_get($url, ['timeout' => 30, 'headers' => $headers, 'limit_response_size' => 25 * MB_IN_BYTES]);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code < 200 || $code >= 300) return ['ok' => false, 'error' => 'The provider answered ' . $code . '.'];
        $type = strtolower(trim(explode(';', (string) wp_remote_retrieve_header($res, 'content-type'))[0]));
        return ['ok' => true, 'error' => '', 'body' => wp_remote_retrieve_body($res), 'type' => strpos($type, 'audio/') === 0 ? $type : 'audio/wav'];
    }
}


/**
 * Talkify (talkify.com.bd), REST API at app.talkify.com.bd/api/v1, key in X-API-Key.
 * Built from the public docs (talkify.com.bd/docs). Field names the public docs
 * do not show are in CLICK_TO_CALL; confirm them against the dashboard API
 * reference (Developers, or app.talkify.com.bd/api/docs) after the first real call.
 */
class TalkifyVoiceProvider extends VoiceProviderBase
{
    const CLICK_TO_CALL = [
        'path' => '/click-to-call',
        'status_path' => '/click-to-call/%s',
        'agent_field' => 'agentExtension',
        'agent_email_field' => 'agentEmail',
        'phone_field' => 'phone',
    ];

    public function __construct(array $settings)
    {
        $this->s = $settings + ['api_key' => '', 'webhook_secret' => '', 'base_url' => ''];
    }

    public function label()
    {
        return 'Talkify (talkify.com.bd)';
    }

    public function capabilities()
    {
        return ['tts' => true, 'ivr' => false, 'broadcast' => true, 'fixed_keys' => null];
    }

    public function configured()
    {
        return (string) $this->s['api_key'] !== '';
    }

    private function base()
    {
        return rtrim((string) ($this->s['base_url'] ?: 'https://app.talkify.com.bd/api/v1'), '/');
    }

    private function api($method, $path, $body = null, array $query = [])
    {
        $url = $this->base() . $path;
        if ($query) $url = add_query_arg($query, $url);
        $args = ['timeout' => 20, 'method' => $method, 'headers' => ['X-API-Key' => $this->s['api_key'], 'Accept' => 'application/json']];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message(), 'data' => null, 'code' => 0];
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if ($code >= 200 && $code < 300) return ['ok' => true, 'error' => '', 'data' => is_array($data) ? $data : [], 'code' => $code];
        $msg = is_array($data) ? ($data['message'] ?? ($data['error'] ?? '')) : '';
        if (is_array($msg)) $msg = implode(' ', array_map('strval', $msg));
        $msg = trim((string) $msg);
        if ($msg === '') {
            $msg = in_array($code, [401, 403], true) ? 'Talkify refused the API key (' . $code . '). Check the key and its scopes.'
                : ($code === 429 ? 'Talkify rate limit reached; it will be retried.' : 'Talkify answered ' . $code . '.');
        }
        return ['ok' => false, 'error' => $msg, 'data' => $data, 'code' => $code];
    }

    private function data(array $r)
    {
        $d = $r['data'] ?? [];
        return is_array($d['data'] ?? null) ? $d['data'] : (is_array($d) ? $d : []);
    }

    public function test()
    {
        $r = $this->api('GET', '/outbound/calls', null, ['limit' => 1]);
        return ['ok' => $r['ok'], 'error' => $r['error']];
    }

    public function place_call(array $c)
    {
        $body = ['phone' => $c['phone'], 'text' => $c['text'], 'externalId' => $c['ref'], 'webhookUrl' => $c['webhook']];
        if (!empty($c['dtmf'])) $body['dtmfOptions'] = (object) array_map('strval', $c['dtmf']);
        $r = $this->api('POST', '/outbound/call', $body);
        if (!$r['ok']) return $r;
        $d = $this->data($r);
        return ['ok' => true, 'error' => '', 'id' => (string) ($d['callId'] ?? $d['id'] ?? ''), 'status' => (string) ($d['status'] ?? 'QUEUED')];
    }

    public function click_to_call(array $c)
    {
        $f = self::CLICK_TO_CALL;
        $body = [$f['phone_field'] => $c['phone'], 'externalId' => $c['ref'], 'webhookUrl' => $c['webhook']];
        /* "Chosen by extension or email": an email in the extension field means the email. */
        if (strpos((string) $c['extension'], '@') !== false) $body[$f['agent_email_field']] = $c['extension'];
        else $body[$f['agent_field']] = (string) $c['extension'];
        $r = $this->api('POST', $f['path'], $body);
        if (!$r['ok']) return $r;
        $d = $this->data($r);
        return ['ok' => true, 'error' => '', 'id' => (string) ($d['callId'] ?? $d['id'] ?? ''), 'status' => (string) ($d['status'] ?? 'RINGING_AGENT')];
    }

    public function get_call(array $c)
    {
        $id = rawurlencode((string) $c['provider_id']);
        $path = $c['kind'] === 'click_to_call' ? sprintf(self::CLICK_TO_CALL['status_path'], $id) : '/outbound/call/' . $id;
        $r = $this->api('GET', $path);
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
        return ['ok' => true, 'error' => '', 'event' => $this->parse_webhook(['data' => $this->data($r)])];
    }

    public function verify_webhook($raw, array $headers)
    {
        $sig = trim((string) ($headers['x-talkify-signature'] ?? ''));
        $secret = (string) $this->s['webhook_secret'];
        if ($sig === '' || $secret === '') return null;
        return hash_equals('sha256=' . hash_hmac('sha256', (string) $raw, $secret), $sig);
    }

    public function parse_webhook(array $j)
    {
        $d = is_array($j['data'] ?? null) ? $j['data'] : $j;
        $event = strtolower((string) ($j['event'] ?? $j['type'] ?? ''));
        $raw = strtoupper((string) ($d['status'] ?? $d['outcome'] ?? ''));
        $dtmf = (string) ($d['dtmfPressed'] ?? $d['dtmf'] ?? '');
        $duration = (int) ($d['billsec'] ?? $d['duration'] ?? 0);
        $hangup = (string) ($d['hangupCause'] ?? $d['hangup_cause'] ?? '');

        $has = function ($words) use ($raw) {
            foreach ($words as $w) if (strpos($raw, $w) !== false) return true;
            return false;
        };
        $final = in_array($event, ['call.completed', 'call.missed'], true)
            || $has(['COMPLETE', 'ENDED', 'NO_ANSWER', 'NOANSWER', 'UNANSWERED', 'MISSED', 'BUSY', 'FAIL', 'ERROR', 'REJECT', 'CANCEL', 'INVALID', 'HANGUP']);

        if ($has(['BUSY'])) $state = 'busy';
        elseif ($event === 'call.missed' || $has(['NO_ANSWER', 'NOANSWER', 'UNANSWERED', 'MISSED'])) $state = 'no_answer';
        elseif ($has(['FAIL', 'ERROR', 'REJECT', 'CANCEL', 'INVALID'])) $state = 'failed';
        elseif ($final) $state = 'completed';
        elseif ($has(['CONNECTED', 'IN_PROGRESS', 'ANSWERED', 'BRIDGED'])) $state = 'connected';
        else $state = 'ringing';

        $answered = $state === 'completed' && ($duration > 0 || $dtmf !== '' || $has(['ANSWER', 'COMPLETE']));
        if ($final && $state === 'completed' && !$answered) $state = 'no_answer';

        return [
            'final' => $final,
            'state' => $state,
            'phase' => strtolower($raw),
            'answered' => $answered,
            'dtmf' => preg_match('/^[0-9*#]$/', $dtmf) ? $dtmf : '',
            'dtmf_label' => (string) ($d['dtmfLabel'] ?? ''),
            'intent' => '',
            'duration' => $duration,
            'hangup' => $hangup,
            'recording_url' => (string) ($d['recordingUrl'] ?? $d['recording_url'] ?? ''),
            'provider_id' => (string) ($d['callId'] ?? $d['id'] ?? ''),
            'error' => '',
        ];
    }

    public function fetch_recording($url)
    {
        return $this->download($url, ['X-API-Key' => $this->s['api_key']], $this->base());
    }
}


/**
 * HelloPBX (hellopbx.bd), REST API at hellopbx.bd/api, personal API token as a
 * Bearer token (docs: hellopbx.bd/docs/api). No text-to-speech: automated
 * calls play an IVR built in the HelloPBX panel (IVR Builder), by id.
 *
 *  - click-to-call  POST /v1/calls/originate {extension, phone_number, caller_id}
 *                   (rings the extension, then the customer). No per-call
 *                   webhook: the result is looked up in /v1/calls/history.
 *  - broadcast      POST /v1/auto-calls {to, ivr_id, webhook_url, metadata, max_attempts}
 *                   then GET /v1/auto-calls/{id}.
 *  - order call     POST /v1/calls/verify {phone_number, webhook_url, order_id,
 *                   customer_name, amount, store_name, ivr_id?}: answers
 *                   "confirmed" / "rejected" (intent), or no answer / busy.
 *  - webhooks       X-CallCRM-Signature = hex HMAC-SHA256 of "{timestamp}.{raw body}"
 *                   with the endpoint secret, X-CallCRM-Timestamp within 5 minutes.
 *  - numbers        local 11-digit form (01XXXXXXXXX), as in every example.
 *  Writes send an Idempotency-Key (our call reference), so a retry never dials twice.
 * Needs a plan with Developer API Access (calls) and Auto Call API & Voice OTP
 * (auto-calls, order calls): Standard PBX and above.
 */
class HelloPbxVoiceProvider extends VoiceProviderBase
{
    public function __construct(array $settings)
    {
        $this->s = $settings + ['api_token' => '', 'webhook_secret' => '', 'base_url' => '', 'caller_id' => '', 'order_ivr_id' => 0];
    }

    public function label()
    {
        return 'HelloPBX (hellopbx.bd)';
    }

    public function capabilities()
    {
        return ['tts' => false, 'ivr' => true, 'broadcast' => true, 'fixed_keys' => null];
    }

    public function configured()
    {
        return (string) $this->s['api_token'] !== '';
    }

    /* Overridden by providers on the same platform (ePBX). */
    protected function brand()
    {
        return 'HelloPBX';
    }

    protected function default_base()
    {
        return 'https://hellopbx.bd/api';
    }

    protected function token_help()
    {
        return 'Tokens expire after 90 days: make a new one.';
    }

    protected function plan_help()
    {
        return 'needs Developer API / Auto Call API, Standard PBX or above';
    }

    protected function base()
    {
        return rtrim((string) ($this->s['base_url'] ?: $this->default_base()), '/');
    }

    /** "8801711000000" -> "01711000000" (HelloPBX examples are all local numbers). */
    protected function local($intl)
    {
        $d = preg_replace('/\D+/', '', (string) $intl);
        if (strlen($d) === 13 && strpos($d, '880') === 0) return '0' . substr($d, 3);
        return $d;
    }

    protected function api($method, $path, $body = null, array $query = [], $idempotency = '')
    {
        $url = $this->base() . $path;
        if ($query) $url = add_query_arg(array_map('rawurlencode', $query), $url);
        $args = ['timeout' => 20, 'method' => $method, 'headers' => ['Authorization' => 'Bearer ' . $this->s['api_token'], 'Accept' => 'application/json']];
        if ($idempotency !== '') $args['headers']['Idempotency-Key'] = mb_substr($idempotency, 0, 100);
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message(), 'data' => null, 'code' => 0];
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $data = is_array($data) ? $data : [];
        if ($code >= 200 && $code < 300) return ['ok' => true, 'error' => '', 'data' => $data, 'code' => $code];

        $b = $this->brand();
        $known = [
            'insufficient_balance' => 'Not enough balance in the ' . $b . ' wallet.',
            'extension_not_found' => 'That extension is not active in ' . $b . '.',
            'number_on_dnc_list' => 'This number is on ' . $b . '\'s do-not-call list.',
            'telephony_unavailable' => $b . ' telephony is unavailable right now; try again shortly.',
        ];
        $err = (string) ($data['error'] ?? '');
        $msg = $known[$err] ?? '';
        if ($msg === '' && is_array($data['errors'] ?? null)) {
            $first = reset($data['errors']);
            $msg = is_array($first) ? (string) reset($first) : (string) $first;
        }
        if ($msg === '') $msg = trim((string) ($data['message'] ?? ''));
        /* "Unauthenticated." alone does not say what to do. */
        if ($code === 401) $msg = '';
        if ($msg === '') {
            $msg = $code === 401 ? $b . ' refused the API token (401). ' . $this->token_help()
                : ($code === 402 ? $known['insufficient_balance']
                : ($code === 403 ? $b . ' does not allow this for the token or plan (' . $this->plan_help() . ').'
                : ($code === 429 ? $b . ' rate limit reached; it will be retried.' : $b . ' answered ' . $code . '.')));
        }
        return ['ok' => false, 'error' => $msg, 'data' => $data, 'code' => $code];
    }

    public function test()
    {
        $r = $this->api('GET', '/v1/ping');
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
        $b = $this->api('GET', '/v1/balance/global');
        $msg = 'Connected to HelloPBX' . (!empty($b['data']['tenant']) ? ' (' . $b['data']['tenant'] . ')' : '') . '.';
        if ($b['ok'] && isset($b['data']['balance'])) $msg .= ' Wallet: ' . ($b['data']['currency'] ?? 'BDT') . ' ' . $b['data']['balance'] . '.';
        return ['ok' => true, 'error' => '', 'message' => $msg];
    }

    public function place_call(array $c)
    {
        $caller = (string) $this->s['caller_id'];
        if (($c['purpose'] ?? '') === 'order') {
            /* The dedicated order-confirmation call; ivr_id blank = the default one in HelloPBX's Voice Settings. */
            $body = [
                'phone_number' => $this->local($c['phone']),
                'webhook_url' => $c['webhook'],
                'order_id' => (string) ($c['order_id'] ?? ''),
                'customer_name' => (string) ($c['customer_name'] ?? ''),
                'amount' => (string) ($c['amount'] ?? ''),
                'store_name' => (string) ($c['store_name'] ?? ''),
            ];
            if (!empty($c['ivr_id'])) $body['ivr_id'] = (int) $c['ivr_id'];
            if ($caller !== '') $body['caller_id'] = $caller;
            $r = $this->api('POST', '/v1/calls/verify', $body, [], (string) $c['ref']);
            if (!$r['ok']) return $r;
            return ['ok' => true, 'error' => '', 'id' => (string) ($r['data']['call_id'] ?? ''), 'status' => 'queued'];
        }

        if (empty($c['ivr_id'])) return ['ok' => false, 'error' => 'HelloPBX plays an IVR, not text: choose the IVR for this call.'];
        $body = [
            'to' => $this->local($c['phone']),
            'ivr_id' => (int) $c['ivr_id'],
            'webhook_url' => $c['webhook'],
            'metadata' => ['ref' => (string) $c['ref']],
            'max_attempts' => 1,
        ];
        if ($caller !== '') $body['caller_id'] = $caller;
        $r = $this->api('POST', '/v1/auto-calls', $body, [], (string) $c['ref']);
        if (!$r['ok']) return $r;
        return ['ok' => true, 'error' => '', 'id' => (string) ($r['data']['auto_call_id'] ?? ''), 'status' => (string) ($r['data']['status'] ?? 'queued')];
    }

    public function click_to_call(array $c)
    {
        $ext = (string) $c['extension'];
        if (!preg_match('/^\d{2,8}$/', $ext)) return ['ok' => false, 'error' => $this->brand() . ' extensions are 2 to 8 digits; set the agent\'s extension number in Voice settings.'];
        $body = ['extension' => $ext, 'phone_number' => $this->local($c['phone'])];
        if ((string) $this->s['caller_id'] !== '') $body['caller_id'] = (string) $this->s['caller_id'];
        $r = $this->api('POST', '/v1/calls/originate', $body, [], (string) $c['ref']);
        if (!$r['ok']) return $r;
        return ['ok' => true, 'error' => '', 'id' => (string) ($r['data']['attempt_id'] ?? ''), 'status' => 'ringing_agent'];
    }

    public function get_call(array $c)
    {
        if ($c['kind'] !== 'click_to_call') {
            $r = $this->api('GET', '/v1/auto-calls/' . rawurlencode((string) $c['provider_id']));
            if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
            $d = is_array($r['data']['data'] ?? null) ? $r['data']['data'] : $r['data'];
            return ['ok' => true, 'error' => '', 'event' => $this->parse_webhook(['data' => $d])];
        }

        /* A click-to-call has no status call: its record shows up in the call
           history when it ends. Find this extension's call to this number since
           it was placed (a minute of slack for clock differences). */
        $since = strtotime((string) $c['placed_at']) - 60;
        $r = $this->api('GET', '/v1/calls/history', null, [
            'extension' => (string) $c['extension'],
            'phone' => $this->local($c['phone']),
            'start_date' => gmdate('Y-m-d', $since),
            'direction' => 'outbound',
            'per_page' => '10',
        ]);
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];
        $match = null;
        $want = substr($this->local($c['phone']), -10);
        foreach ((array) ($r['data']['data'] ?? []) as $rec) {
            if (!is_array($rec) || strtotime((string) ($rec['call_date'] ?? '')) < $since) continue;
            if (substr(preg_replace('/\D+/', '', (string) ($rec['destination'] ?? '')), -10) !== $want) continue;
            if (!$match || strtotime($rec['call_date']) < strtotime($match['call_date'])) $match = $rec;
        }
        /* No record yet: the call is still going (or just ended and is being logged). */
        if (!$match) return ['ok' => true, 'error' => '', 'event' => ['final' => false, 'state' => 'ringing', 'phase' => 'awaiting_record', 'answered' => false]];
        return ['ok' => true, 'error' => '', 'event' => $this->parse_webhook(['data' => $match])];
    }

    public function verify_webhook($raw, array $headers)
    {
        $sig = strtolower(trim((string) ($headers['x-callcrm-signature'] ?? '')));
        $secret = (string) $this->s['webhook_secret'];
        if ($sig === '' || $secret === '') return null;
        $ts = trim((string) ($headers['x-callcrm-timestamp'] ?? ''));
        $when = ctype_digit($ts) ? (int) $ts : strtotime($ts);
        if (!$when || abs(time() - $when) > 300) return false;
        if (strpos($sig, 'sha256=') === 0) $sig = substr($sig, 7);
        return hash_equals(hash_hmac('sha256', $ts . '.' . (string) $raw, $secret), $sig);
    }

    public function parse_webhook(array $j)
    {
        $d = is_array($j['data'] ?? null) ? $j['data'] : $j;
        $event = strtolower((string) ($j['event'] ?? ''));
        $status = strtolower(trim((string) ($d['status'] ?? $d['disposition'] ?? '')));
        $value = strtolower(trim((string) ($d['option_value'] ?? '')));
        $digits = (string) ($d['digits_pressed'] ?? $d['digit'] ?? '');
        $duration = (int) ($d['billsec'] ?? $d['duration'] ?? 0);

        $intent = '';
        if ($status === 'confirmed' || $value === 'confirmed') $intent = 'confirm';
        elseif (in_array($status, ['rejected', 'cancelled', 'canceled'], true) || in_array($value, ['rejected', 'cancelled', 'canceled', 'cancel'], true)) $intent = 'cancel';

        $base = [
            'dtmf' => preg_match('/^[0-9*#]$/', $digits) ? $digits : '',
            'dtmf_label' => (string) ($d['option_label'] ?? ''),
            'intent' => $intent,
            'duration' => $duration,
            'hangup' => strtoupper($status),
            'recording_url' => !empty($d['has_recording']) || !empty($d['recording_url']) ? (string) ($d['recording_url'] ?? '') : '',
            'provider_id' => (string) ($d['auto_call_id'] ?? $d['attempt_id'] ?? ''),
            'error' => '',
        ];

        /* A key pressed during the call: not the end yet. */
        if ($event === 'ivr.option_selected') return $base + ['final' => false, 'state' => 'connected', 'phase' => 'key_pressed', 'answered' => true];
        if (in_array($status, ['', 'queued', 'calling', 'dispatched', 'ringing', 'in_progress'], true)) {
            return $base + ['final' => false, 'state' => $status === 'in_progress' ? 'connected' : 'ringing', 'phase' => $status ?: 'queued', 'answered' => false];
        }

        if (in_array($status, ['no_answer', 'noanswer', 'no answer'], true)) $state = 'no_answer';
        elseif ($status === 'busy') $state = 'busy';
        elseif (in_array($status, ['failed', 'congestion', 'cancelled_by_system', 'error'], true)) $state = 'failed';
        else $state = 'completed'; /* answered_with_input, answered_no_input, "no response", ANSWERED (call history), confirmed, rejected, or an option's value */

        $answered = $state === 'completed' && ($status !== 'answered' || $duration > 0);
        return $base + ['final' => true, 'state' => $answered || $state !== 'completed' ? $state : 'no_answer', 'phase' => $status, 'answered' => $answered];
    }

    public function fetch_recording($url)
    {
        return $this->download($url, ['Authorization' => 'Bearer ' . $this->s['api_token'], 'Accept' => 'audio/*'], $this->base());
    }
}


/**
 * ePBX (epbx.bd, by EiconBD), REST API at epbx.bd/api/v1, Bearer token from the
 * dashboard's Developer API Settings (docs: epbx.bd/docs/api). The same platform
 * family as HelloPBX (calls/originate, calls/history, recordings and balance
 * routes match), with these differences:
 *  - the order call (POST /v1/calls/verify) speaks our text (custom_text,
 *    text-to-speech) and its keys are fixed: 1 confirms, 2 cancels;
 *  - no voice-campaign (broadcast) API, and no status lookup for verify calls:
 *    their result comes from the webhook; call history only settles calls that
 *    were never answered;
 *  - webhooks are not signed: the per-call signed URL is the check;
 *  - field names: history `date`, `billable_seconds`, `duration_seconds`,
 *    `phone_number` filter; webhook `digit_pressed`, `call_id`.
 * Needs a plan with API Access (Business AI PBX and above).
 */
class EpbxVoiceProvider extends HelloPbxVoiceProvider
{
    public function __construct(array $settings)
    {
        $this->s = $settings + ['api_token' => '', 'base_url' => '', 'caller_id' => '', 'webhook_secret' => ''];
    }

    public function label()
    {
        return 'ePBX (epbx.bd)';
    }

    public function capabilities()
    {
        return ['tts' => true, 'ivr' => false, 'broadcast' => false, 'fixed_keys' => ['confirm' => '1', 'cancel' => '2']];
    }

    protected function brand()
    {
        return 'ePBX';
    }

    protected function default_base()
    {
        return 'https://epbx.bd/api';
    }

    protected function token_help()
    {
        return 'Make a new token in ePBX Developer API Settings.';
    }

    protected function plan_help()
    {
        return 'needs a plan with API Access, Business AI PBX or above';
    }

    public function test()
    {
        $b = $this->api('GET', '/v1/balance/global');
        if (!$b['ok']) return ['ok' => false, 'error' => $b['error']];
        $msg = 'Connected to ePBX.';
        if (isset($b['data']['balance'])) $msg .= ' Wallet: ' . ($b['data']['currency'] ?? 'BDT') . ' ' . $b['data']['balance'] . '.';
        return ['ok' => true, 'error' => '', 'message' => $msg];
    }

    private function result_id(array $r)
    {
        $d = is_array($r['data']['data'] ?? null) ? $r['data']['data'] : $r['data'];
        return (string) ($d['call_id'] ?? $d['attempt_id'] ?? $d['id'] ?? '');
    }

    public function place_call(array $c)
    {
        if (($c['purpose'] ?? '') !== 'order') return ['ok' => false, 'error' => 'ePBX has no voice-campaign API: it places order confirmation calls and click-to-call only.'];
        $body = ['phone_number' => $this->local($c['phone']), 'webhook_url' => $c['webhook']];
        if (trim((string) $c['text']) !== '') $body['custom_text'] = (string) $c['text'];
        /* In the docs' example request (not its parameter table). */
        foreach (['order_id', 'customer_name', 'amount', 'store_name'] as $k) {
            if (isset($c[$k]) && (string) $c[$k] !== '') $body[$k] = (string) $c[$k];
        }
        $r = $this->api('POST', '/v1/calls/verify', $body, [], (string) $c['ref']);
        if (!$r['ok']) return $r;
        return ['ok' => true, 'error' => '', 'id' => $this->result_id($r), 'status' => 'queued'];
    }

    public function click_to_call(array $c)
    {
        $ext = (string) $c['extension'];
        if (!preg_match('/^\d{2,8}$/', $ext)) return ['ok' => false, 'error' => 'ePBX extensions are numbers (e.g. 101); set the agent\'s extension in Voice settings.'];
        $body = ['extension' => $ext, 'phone_number' => $this->local($c['phone'])];
        if ((string) $this->s['caller_id'] !== '') $body['caller_id'] = (string) $this->s['caller_id'];
        $r = $this->api('POST', '/v1/calls/originate', $body, [], (string) $c['ref']);
        if (!$r['ok']) return $r;
        return ['ok' => true, 'error' => '', 'id' => $this->result_id($r), 'status' => 'ringing_agent'];
    }

    public function get_call(array $c)
    {
        $since = strtotime((string) $c['placed_at']) - 60;
        $query = ['phone_number' => $this->local($c['phone']), 'start_date' => gmdate('Y-m-d', $since), 'per_page' => '20'];
        if ($c['kind'] === 'click_to_call') $query['extension'] = (string) $c['extension'];
        $r = $this->api('GET', '/v1/calls/history', null, $query);
        if (!$r['ok']) return ['ok' => false, 'error' => $r['error']];

        $match = null;
        $match_at = 0;
        $want = substr($this->local($c['phone']), -10);
        foreach ((array) ($r['data']['data'] ?? []) as $rec) {
            if (!is_array($rec)) continue;
            $at = strtotime((string) ($rec['date'] ?? $rec['call_date'] ?? ''));
            if (!$at || $at < $since) continue;
            if (substr(preg_replace('/\D+/', '', (string) ($rec['destination'] ?? '')), -10) !== $want) continue;
            if (!$match || $at < $match_at) {
                $match = $rec;
                $match_at = $at;
            }
        }
        $waiting = ['final' => false, 'state' => 'ringing', 'phase' => 'awaiting_record', 'answered' => false];
        if (!$match) return ['ok' => true, 'error' => '', 'event' => $waiting];

        $ev = $this->parse_webhook(['data' => $match]);
        /* An answered order call's key comes only in its webhook: keep waiting for it. */
        if ($c['kind'] !== 'click_to_call' && $ev['final'] && $ev['answered']) {
            return ['ok' => true, 'error' => '', 'event' => ['phase' => 'awaiting_result'] + $waiting];
        }
        return ['ok' => true, 'error' => '', 'event' => $ev];
    }

    /** ePBX does not sign webhooks; the per-call signed URL is checked instead. */
    public function verify_webhook($raw, array $headers)
    {
        return null;
    }

    public function parse_webhook(array $j)
    {
        $d = is_array($j['data'] ?? null) ? $j['data'] : $j;
        /* ePBX's names -> the HelloPBX names the shared parser reads. */
        foreach (['digit_pressed' => 'digits_pressed', 'billable_seconds' => 'billsec', 'duration_seconds' => 'duration', 'date' => 'call_date'] as $from => $to) {
            if (array_key_exists($from, $d) && !array_key_exists($to, $d)) $d[$to] = $d[$from];
        }
        $d['digits_pressed'] = (string) ($d['digits_pressed'] ?? '');
        unset($d['auto_call_id'], $d['attempt_id']);
        $ev = parent::parse_webhook(['data' => $d]);
        $ev['provider_id'] = (string) ($d['call_id'] ?? '');
        return $ev;
    }
}
