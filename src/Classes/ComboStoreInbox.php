<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Messages: one inbox for WhatsApp and Facebook Messenger conversations.
 *
 *  - WhatsApp: incoming messages and receipts come from the marketing webhook
 *    (ComboStoreMarketing::mkt_whatsapp_webhook fires combo_store_whatsapp_message
 *    and combo_store_whatsapp_status); replies go out with the WhatsApp settings
 *    (wa_api). Free-form replies only within 24 hours of the customer's last
 *    message; after that WhatsApp only takes an approved template.
 *  - Messenger: the public inbox_messenger_webhook (GET verify token, POST signed
 *    with the app secret) receives messages, echoes (replies sent from the Page's
 *    own inbox), deliveries and reads; replies go out with the Page token,
 *    messaging_type RESPONSE within 24 hours, or MESSAGE_TAG HUMAN_AGENT up to
 *    7 days when that setting is on (it needs Meta's approval).
 *
 * Tables (option combo_store_inbox_schema):
 *  - cstore_inbox_conversations: one per customer per channel (channel +
 *    external_id unique: WhatsApp number digits / Messenger PSID).
 *  - cstore_inbox_messages: provider_id (wamid / mid) is unique, so webhook
 *    retries and echoes of our own replies never duplicate.
 *
 * Access (like Tickets): administrators see and do everything; support agents
 * see unassigned conversations and their own, may take an unassigned one (a
 * reply takes it) or hand back their own. Routes are POST with a Bearer JWT.
 */
class ComboStoreInbox
{
    const OPTION = 'combo_store_inbox';
    const SCHEMA_VERSION = 1;
    const SCHEMA_OPTION = 'combo_store_inbox_schema';
    const ROLES = ['administrator', 'support_agent'];
    const ADMIN_ROLES = ['administrator'];
    const CHANNELS = ['whatsapp', 'messenger'];
    const WINDOW_HOURS = 24;
    const HUMAN_AGENT_DAYS = 7;
    const PER_PAGE = 30;
    const STATUS_RANK = ['sending' => 0, 'sent' => 1, 'delivered' => 2, 'read' => 3];

    /** @var ComboStoreInbox|null */
    public static $instance = null;

    public function __construct()
    {
        self::$instance = $this;
        add_action('rest_api_init', array($this, 'maybe_upgrade_schema'), 7);
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('combo_store_whatsapp_message', array($this, 'on_whatsapp_message'), 10, 2);
        add_action('combo_store_whatsapp_status', array($this, 'on_whatsapp_status'), 10, 2);
    }


    /* ---------------------------------------------------------------- */
    /* Schema and routes                                                 */

    private function t($name)
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_inbox_' . $name;
    }

    public function maybe_upgrade_schema()
    {
        if ((int) get_option(self::SCHEMA_OPTION, 0) >= self::SCHEMA_VERSION) return;
        global $wpdb;
        $c = $wpdb->get_charset_collate();
        $ok = $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->t('conversations')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            channel VARCHAR(20) NOT NULL,
            external_id VARCHAR(64) NOT NULL,
            account_id VARCHAR(64) NOT NULL DEFAULT '',
            contact_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            name VARCHAR(190) NOT NULL DEFAULT '',
            avatar_url VARCHAR(500) NOT NULL DEFAULT '',
            linked_phone VARCHAR(32) NOT NULL DEFAULT '',
            status VARCHAR(10) NOT NULL DEFAULT 'open',
            assigned_to BIGINT UNSIGNED DEFAULT NULL,
            assigned_at DATETIME DEFAULT NULL,
            unread INT UNSIGNED NOT NULL DEFAULT 0,
            preview VARCHAR(255) NOT NULL DEFAULT '',
            last_message_at DATETIME DEFAULT NULL,
            last_inbound_at DATETIME DEFAULT NULL,
            closed_at DATETIME DEFAULT NULL,
            closed_by BIGINT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY channel_external (channel, external_id),
            KEY status_last (status, last_message_at),
            KEY assigned (assigned_to, status)
        ) $c");
        if ($ok === false) return;
        $ok = $wpdb->query("CREATE TABLE IF NOT EXISTS {$this->t('messages')} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id BIGINT UNSIGNED NOT NULL,
            direction VARCHAR(3) NOT NULL,
            provider_id VARCHAR(190) DEFAULT NULL,
            type VARCHAR(20) NOT NULL DEFAULT 'text',
            text TEXT NULL,
            media TEXT NULL,
            status VARCHAR(12) NOT NULL DEFAULT 'received',
            error VARCHAR(500) NOT NULL DEFAULT '',
            author_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY provider_id (provider_id),
            KEY conversation (conversation_id, id)
        ) $c");
        if ($ok === false) return;
        update_option(self::SCHEMA_OPTION, self::SCHEMA_VERSION, false);
    }

    public function register_routes()
    {
        $routes = [
            'get_inbox_conversations', 'get_inbox_conversation', 'inbox_send', 'inbox_assign', 'inbox_set_status',
            'inbox_mark_read', 'inbox_link_phone', 'get_inbox_counts', 'get_inbox_media',
            'get_inbox_settings', 'update_inbox_settings', 'inbox_test',
        ];
        foreach ($routes as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods' => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
        register_rest_route('combo-store/v2', '/inbox_messenger_webhook', array(
            'methods' => ['GET', 'POST'],
            'callback' => array($this, 'inbox_messenger_webhook'),
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
        if (!array_intersect(self::ROLES, (array) $user->roles)) return new WP_Error('forbidden', 'You are not allowed to use Messages', array('status' => 403));
        return $user;
    }

    private function admin_auth($request)
    {
        $user = $this->user_from_token($request);
        if (is_wp_error($user)) return $user;
        if (!array_intersect(self::ADMIN_ROLES, (array) $user->roles)) return new WP_Error('forbidden', 'Only administrators can change Messages settings', array('status' => 403));
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
        return new WP_Error('inbox_error', $message, array('status' => $status));
    }

    private function now()
    {
        return current_time('mysql');
    }

    private function marketing()
    {
        return class_exists(__NAMESPACE__ . '\\ComboStoreMarketing') ? ComboStoreMarketing::$instance : null;
    }

    private function graph_version()
    {
        return defined(__NAMESPACE__ . '\\ComboStoreMarketing::WA_API_VERSION') ? ComboStoreMarketing::WA_API_VERSION : 'v25.0';
    }

    private function settings()
    {
        $s = get_option(self::OPTION, []);
        $s = is_array($s) ? $s : [];
        return [
            'whatsapp' => array_merge(['enabled' => true], is_array($s['whatsapp'] ?? null) ? $s['whatsapp'] : []),
            'messenger' => array_merge(['enabled' => false, 'page_id' => '', 'page_token' => '', 'app_secret' => '', 'verify_token' => '', 'human_agent_tag' => false], is_array($s['messenger'] ?? null) ? $s['messenger'] : []),
        ];
    }

    /** WhatsApp is usable when marketing's WhatsApp connection is set up. */
    private function whatsapp_ready()
    {
        $m = $this->marketing();
        return $m && (string) $m->setting('whatsapp.phone_number_id', '') !== '' && (string) $m->setting('whatsapp.access_token', '') !== '';
    }

    private function messenger_ready()
    {
        $s = $this->settings()['messenger'];
        return !empty($s['enabled']) && $s['page_id'] !== '' && $s['page_token'] !== '';
    }

    /** Messenger Graph call with the Page token. */
    private function page_api($method, $path, array $body = null, array $query = [])
    {
        $s = $this->settings()['messenger'];
        $url = 'https://graph.facebook.com/' . $this->graph_version() . '/' . ltrim($path, '/');
        if ($query) $url = add_query_arg(array_map('rawurlencode', $query), $url);
        $args = ['timeout' => 20, 'method' => $method, 'headers' => ['Authorization' => 'Bearer ' . $s['page_token'], 'Accept' => 'application/json']];
        if ($body !== null) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message(), 'data' => null, 'code' => 0];
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $data = is_array($data) ? $data : [];
        if ($code >= 200 && $code < 300 && !isset($data['error'])) return ['ok' => true, 'error' => '', 'data' => $data, 'code' => $code];
        $e = is_array($data['error'] ?? null) ? $data['error'] : [];
        $c = (int) ($e['code'] ?? 0);
        $msg = trim((string) (($e['error_user_msg'] ?? '') ?: ($e['message'] ?? '')));
        if ($c === 190) $msg = 'Meta refused the Page token (expired or revoked). Make a new one and save it in Marketing settings › Facebook Messenger.';
        elseif ($c === 10 || $c === 200 || ($c >= 200 && $c <= 299)) $msg = 'The Page token is missing a permission (pages_messaging, pages_manage_metadata).' . ($msg !== '' ? ' ' . $msg : '');
        if ($msg === '') $msg = 'Meta answered ' . $code . '.';
        return ['ok' => false, 'error' => $msg, 'data' => $data, 'code' => $c ?: $code];
    }

    private function preview_of($type, $text)
    {
        $labels = ['image' => 'Photo', 'video' => 'Video', 'audio' => 'Voice message', 'document' => 'File', 'sticker' => 'Sticker', 'location' => 'Location'];
        $t = trim(preg_replace('/\s+/', ' ', (string) $text));
        if ($t !== '') return mb_substr(($type !== 'text' && isset($labels[$type]) ? $labels[$type] . ': ' : '') . $t, 0, 250);
        return $labels[$type] ?? 'Message';
    }

    private function contact_id_for_phone($phone)
    {
        global $wpdb;
        $m = $this->marketing();
        $clean = $m ? $m->clean_phone($phone) : $phone;
        if (!$clean) return 0;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}cstore_mkt_contacts WHERE phone = %s", $clean));
    }


    /* ---------------------------------------------------------------- */
    /* Storing messages                                                  */

    /** The conversation for a customer on a channel, created on first contact. */
    private function conversation_for($channel, $external_id, $account_id, $name = '', $avatar = '')
    {
        global $wpdb;
        $ct = $this->t('conversations');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ct WHERE channel = %s AND external_id = %s", $channel, $external_id));
        if ($row) return $row;
        $contact = $channel === 'whatsapp' ? $this->contact_id_for_phone('+' . $external_id) : 0;
        /* INSERT IGNORE: two webhooks for a new customer at once make one row. */
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $ct (channel, external_id, account_id, contact_id, name, avatar_url, status, created_at) VALUES (%s, %s, %s, %d, %s, %s, 'open', %s)",
            $channel, $external_id, (string) $account_id, $contact, mb_substr((string) $name, 0, 190), mb_substr((string) $avatar, 0, 500), $this->now()
        ));
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $ct WHERE channel = %s AND external_id = %s", $channel, $external_id));
    }

    /**
     * Store one message (once per provider id) and move its conversation on.
     * An incoming one reopens a closed conversation and counts as unread.
     */
    private function store($conv, $direction, $provider_id, $type, $text, array $media, $at, $status, $author = 0)
    {
        global $wpdb;
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$this->t('messages')} (conversation_id, direction, provider_id, type, text, media, status, author_id, created_at) VALUES (%d, %s, %s, %s, %s, %s, %s, %d, %s)",
            (int) $conv->id, $direction, $provider_id, $type, (string) $text, $media ? wp_json_encode($media) : '', $status, (int) $author, $at
        ));
        if (!$inserted) return 0;
        $id = (int) $wpdb->insert_id;
        $preview = $this->preview_of($type, $text !== '' ? $text : ($media['caption'] ?? ''));
        if ($direction === 'in') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->t('conversations')} SET unread = unread + 1, status = 'open', closed_at = NULL, closed_by = NULL, preview = %s,
                    last_message_at = GREATEST(COALESCE(last_message_at, %s), %s), last_inbound_at = GREATEST(COALESCE(last_inbound_at, %s), %s) WHERE id = %d",
                $preview, $at, $at, $at, $at, $conv->id
            ));
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->t('conversations')} SET preview = %s, last_message_at = GREATEST(COALESCE(last_message_at, %s), %s) WHERE id = %d",
                'You: ' . $preview, $at, $at, $conv->id
            ));
        }
        return $id;
    }

    /** A receipt only moves a message forward (sent -> delivered -> read), or marks it failed. */
    private function receipt($where_sql, array $args, $status, $error = '')
    {
        global $wpdb;
        $mt = $this->t('messages');
        if ($status === 'failed') {
            $wpdb->query($wpdb->prepare("UPDATE $mt SET status = 'failed', error = %s WHERE direction = 'out' AND $where_sql", array_merge([mb_substr($error, 0, 500)], $args)));
            return;
        }
        $rank = self::STATUS_RANK[$status] ?? null;
        if ($rank === null) return;
        $lower = array_keys(array_filter(self::STATUS_RANK, function ($r) use ($rank) { return $r < $rank; }));
        if (!$lower) return;
        $in = "'" . implode("','", $lower) . "'";
        $wpdb->query($wpdb->prepare("UPDATE $mt SET status = %s WHERE direction = 'out' AND status IN ($in) AND $where_sql", array_merge([$status], $args)));
    }


    /* ---------------------------------------------------------------- */
    /* WhatsApp in (from the marketing webhook)                          */

    public function on_whatsapp_message($msg, $value)
    {
        if ((int) get_option(self::SCHEMA_OPTION, 0) < self::SCHEMA_VERSION) return;
        if (empty($this->settings()['whatsapp']['enabled'])) return;
        $from = preg_replace('/\D+/', '', (string) ($msg['from'] ?? ''));
        $wamid = (string) ($msg['id'] ?? '');
        if ($from === '' || $wamid === '') return;

        $name = '';
        foreach ((array) ($value['contacts'] ?? []) as $c) {
            if (preg_replace('/\D+/', '', (string) ($c['wa_id'] ?? '')) === $from) $name = (string) ($c['profile']['name'] ?? '');
        }
        $type = (string) ($msg['type'] ?? 'text');
        $text = '';
        $media = [];
        switch ($type) {
            case 'text':
                $text = (string) ($msg['text']['body'] ?? '');
                break;
            case 'image': case 'video': case 'audio': case 'document': case 'sticker':
                $m = (array) ($msg[$type] ?? []);
                $media = ['id' => (string) ($m['id'] ?? ''), 'mime' => (string) ($m['mime_type'] ?? ''), 'caption' => (string) ($m['caption'] ?? ''), 'filename' => (string) ($m['filename'] ?? '')];
                $text = $media['caption'];
                break;
            case 'location':
                $l = (array) ($msg['location'] ?? []);
                $text = trim(($l['name'] ?? '') . ' ' . ($l['address'] ?? ''));
                $media = ['lat' => (float) ($l['latitude'] ?? 0), 'lng' => (float) ($l['longitude'] ?? 0)];
                break;
            case 'button':
                $type = 'text';
                $text = (string) ($msg['button']['text'] ?? '');
                break;
            case 'interactive':
                $type = 'text';
                $text = (string) ($msg['interactive']['button_reply']['title'] ?? $msg['interactive']['list_reply']['title'] ?? '');
                break;
            case 'reaction':
                $type = 'text';
                $text = 'Reacted ' . (string) ($msg['reaction']['emoji'] ?? '');
                break;
            case 'contacts':
                $type = 'text';
                $text = 'Shared a contact: ' . implode(', ', array_map(function ($c) { return (string) ($c['name']['formatted_name'] ?? ''); }, (array) ($msg['contacts'] ?? [])));
                break;
            default:
                $type = 'other';
                $text = '[A message this inbox cannot show: ' . sanitize_key($msg['type'] ?? 'unknown') . ']';
        }
        $at = isset($msg['timestamp']) ? wp_date('Y-m-d H:i:s', (int) $msg['timestamp']) : $this->now();
        $conv = $this->conversation_for('whatsapp', $from, (string) ($value['metadata']['phone_number_id'] ?? ''), $name);
        if (!$conv) return;
        if ($name !== '' && $conv->name === '') {
            global $wpdb;
            $wpdb->update($this->t('conversations'), ['name' => mb_substr($name, 0, 190)], ['id' => (int) $conv->id]);
        }
        $this->store($conv, 'in', 'wa:' . $wamid, $type, mb_substr($text, 0, 4096), array_filter($media, function ($v) { return $v !== '' && $v !== 0.0; }), $at, 'received');
    }

    public function on_whatsapp_status($st, $value = null)
    {
        if ((int) get_option(self::SCHEMA_OPTION, 0) < self::SCHEMA_VERSION) return;
        $wamid = (string) ($st['id'] ?? '');
        if ($wamid === '') return;
        $status = (string) ($st['status'] ?? '');
        $error = '';
        if ($status === 'failed') {
            $e = $st['errors'][0] ?? [];
            $error = trim(($e['error_data']['details'] ?? '') ?: ($e['title'] ?? 'Not delivered'));
        }
        $this->receipt('provider_id = %s', ['wa:' . $wamid], $status, $error);
    }


    /* ---------------------------------------------------------------- */
    /* Messenger in                                                      */

    public function inbox_messenger_webhook($request)
    {
        $s = $this->settings()['messenger'];
        if ($request->get_method() === 'GET') {
            $mode = (string) ($request['hub_mode'] ?? $request['hub.mode'] ?? '');
            $token = (string) ($request['hub_verify_token'] ?? $request['hub.verify_token'] ?? '');
            $challenge = (string) ($request['hub_challenge'] ?? $request['hub.challenge'] ?? '');
            if ($mode === 'subscribe' && $s['verify_token'] !== '' && hash_equals($s['verify_token'], $token)) {
                header('Content-Type: text/plain');
                echo preg_replace('/[^A-Za-z0-9_.-]/', '', $challenge);
                exit;
            }
            status_header(403);
            exit;
        }

        $raw = $request->get_body();
        $m = $this->marketing();
        $secret = $s['app_secret'] !== '' ? $s['app_secret'] : ($m ? (string) $m->setting('whatsapp.app_secret', '') : '');
        $sig = (string) $request->get_header('x_hub_signature_256');
        if ($secret === '' || !hash_equals('sha256=' . hash_hmac('sha256', $raw, $secret), $sig)) {
            status_header(403);
            exit;
        }
        if ((int) get_option(self::SCHEMA_OPTION, 0) >= self::SCHEMA_VERSION && !empty($s['enabled'])) {
            $data = json_decode($raw, true);
            if (($data['object'] ?? '') === 'page') {
                foreach ((array) ($data['entry'] ?? []) as $entry) {
                    foreach ((array) ($entry['messaging'] ?? []) as $ev) $this->messenger_event((string) ($entry['id'] ?? ''), (array) $ev);
                }
            }
        }
        status_header(200);
        echo 'EVENT_RECEIVED';
        exit;
    }

    private function messenger_event($page_id, array $ev)
    {
        global $wpdb;
        $sender = (string) ($ev['sender']['id'] ?? '');
        $recipient = (string) ($ev['recipient']['id'] ?? '');
        $at = isset($ev['timestamp']) ? wp_date('Y-m-d H:i:s', (int) floor($ev['timestamp'] / 1000)) : $this->now();

        if (isset($ev['message'])) {
            $msg = (array) $ev['message'];
            $echo = !empty($msg['is_echo']);
            /* An echo is the Page's own reply: the customer is the recipient. */
            $psid = $echo ? $recipient : $sender;
            if ($psid === '' || $psid === $page_id) return;
            $conv = $this->conversation_for('messenger', $psid, $page_id);
            if (!$conv) return;
            if ($conv->name === '') $this->messenger_profile($conv);
            $mid = (string) ($msg['mid'] ?? '');
            $parts = [];
            $text = (string) ($msg['text'] ?? ($msg['quick_reply']['payload'] ?? ''));
            if ($text !== '') $parts[] = ['text', $text, []];
            foreach ((array) ($msg['attachments'] ?? []) as $a) {
                $kind = (string) ($a['type'] ?? 'file');
                $map = ['image' => 'image', 'video' => 'video', 'audio' => 'audio', 'file' => 'document', 'location' => 'location'];
                $url = (string) ($a['payload']['url'] ?? '');
                $parts[] = [$map[$kind] ?? 'other', $kind === 'fallback' ? (string) ($a['title'] ?? $url) : '', $url !== '' ? ['url' => esc_url_raw($url)] : []];
            }
            if (!$parts) $parts[] = ['other', '[A message this inbox cannot show]', []];
            foreach ($parts as $i => [$type, $body, $media]) {
                /* Echoes of replies sent from here carry the same mid and are skipped as duplicates. */
                $this->store($conv, $echo ? 'out' : 'in', 'fb:' . $mid . ($i ? '#' . $i : ''), $type, mb_substr($body, 0, 4096), $media, $at, $echo ? 'sent' : 'received');
            }
            return;
        }

        /* Receipts for our replies: the customer is the sender. */
        $conv = $sender !== '' ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t('conversations')} WHERE channel = 'messenger' AND external_id = %s", $sender)) : null;
        if (!$conv) return;
        if (isset($ev['delivery'])) {
            $mids = array_map(function ($m) { return 'fb:' . $m; }, array_filter((array) ($ev['delivery']['mids'] ?? []), 'is_string'));
            if ($mids) {
                $this->receipt('conversation_id = %d AND provider_id IN (' . implode(',', array_fill(0, count($mids), '%s')) . ')', array_merge([(int) $conv->id], $mids), 'delivered');
            }
            if (!empty($ev['delivery']['watermark'])) {
                $this->receipt('conversation_id = %d AND created_at <= %s', [(int) $conv->id, wp_date('Y-m-d H:i:s', (int) floor($ev['delivery']['watermark'] / 1000))], 'delivered');
            }
        }
        if (isset($ev['read']['watermark'])) {
            $this->receipt('conversation_id = %d AND created_at <= %s', [(int) $conv->id, wp_date('Y-m-d H:i:s', (int) floor($ev['read']['watermark'] / 1000))], 'read');
        }
    }

    /** The sender's name and picture, once (Meta may not allow it; then "Facebook user"). */
    private function messenger_profile($conv)
    {
        global $wpdb;
        $r = $this->messenger_ready() ? $this->page_api('GET', $conv->external_id, null, ['fields' => 'first_name,last_name,profile_pic']) : ['ok' => false];
        $name = $r['ok'] ? trim(($r['data']['first_name'] ?? '') . ' ' . ($r['data']['last_name'] ?? '')) : '';
        $wpdb->update($this->t('conversations'), [
            'name' => mb_substr($name !== '' ? $name : 'Facebook user', 0, 190),
            'avatar_url' => $r['ok'] ? esc_url_raw(mb_substr((string) ($r['data']['profile_pic'] ?? ''), 0, 500)) : '',
        ], ['id' => (int) $conv->id]);
    }


    /* ---------------------------------------------------------------- */
    /* Access                                                            */

    /** SQL (alias c) for the conversations this user may see. */
    private function visible_sql($user)
    {
        global $wpdb;
        if ($this->is_admin_user($user)) return '1=1';
        return $wpdb->prepare('(c.assigned_to IS NULL OR c.assigned_to = %d)', (int) $user->ID);
    }

    private function conversation_for_user($id, $user)
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT c.* FROM {$this->t('conversations')} c WHERE c.id = %d AND " . $this->visible_sql($user), absint($id)));
        return $row ?: null;
    }

    private function user_name($id)
    {
        static $cache = [];
        $id = (int) $id;
        if (!$id) return '';
        if (!isset($cache[$id])) {
            $u = get_user_by('id', $id);
            $cache[$id] = $u ? ($u->display_name ?: $u->user_login) : '#' . $id;
        }
        return $cache[$id];
    }

    /** Can a reply go out now, and how (Messenger: RESPONSE or the HUMAN_AGENT tag). */
    private function reply_window($conv)
    {
        $last = $conv->last_inbound_at ? strtotime($conv->last_inbound_at) : 0;
        $now = strtotime($this->now());
        $hours = $last ? ($now - $last) / HOUR_IN_SECONDS : INF;
        /* Times are store-time strings throughout (current_time('mysql')), so plain arithmetic keeps them comparable. */
        $ends = $last ? gmdate('Y-m-d H:i:s', $last + self::WINDOW_HOURS * HOUR_IN_SECONDS) : null;
        if ($conv->channel === 'whatsapp') {
            if (!$this->whatsapp_ready()) return ['can_reply' => false, 'mode' => '', 'ends_at' => $ends, 'reason' => 'WhatsApp is not connected (Marketing settings › WhatsApp).'];
            if ($hours <= self::WINDOW_HOURS) return ['can_reply' => true, 'mode' => 'session', 'ends_at' => $ends, 'reason' => ''];
            return ['can_reply' => false, 'mode' => '', 'ends_at' => $ends, 'reason' => 'More than 24 hours have passed since the customer\'s last message, so WhatsApp only allows an approved template. Send one from a WhatsApp campaign, or wait for the customer to write again.'];
        }
        if (!$this->messenger_ready()) return ['can_reply' => false, 'mode' => '', 'ends_at' => $ends, 'reason' => 'Facebook Messenger is not connected (Marketing settings › Facebook Messenger).'];
        if ($hours <= self::WINDOW_HOURS) return ['can_reply' => true, 'mode' => 'RESPONSE', 'ends_at' => $ends, 'reason' => ''];
        if (!empty($this->settings()['messenger']['human_agent_tag']) && $hours <= self::HUMAN_AGENT_DAYS * 24) {
            return ['can_reply' => true, 'mode' => 'HUMAN_AGENT', 'ends_at' => gmdate('Y-m-d H:i:s', $last + self::HUMAN_AGENT_DAYS * DAY_IN_SECONDS), 'reason' => ''];
        }
        return ['can_reply' => false, 'mode' => '', 'ends_at' => $ends, 'reason' => 'More than 24 hours have passed since the customer\'s last message, so Messenger does not allow a reply' . (!empty($this->settings()['messenger']['human_agent_tag']) ? ' (or 7 days with the human agent tag)' : '') . '. It opens again when they write.'];
    }


    /* ---------------------------------------------------------------- */
    /* Agent routes                                                      */

    private function shape_conversation($c)
    {
        return [
            'id' => (int) $c->id,
            'channel' => $c->channel,
            'name' => $c->name !== '' ? $c->name : ($c->channel === 'whatsapp' ? '+' . $c->external_id : 'Facebook user'),
            'avatar_url' => $c->avatar_url,
            'phone' => $c->channel === 'whatsapp' ? '+' . $c->external_id : $c->linked_phone,
            'status' => $c->status,
            'assigned_to' => $c->assigned_to ? (int) $c->assigned_to : null,
            'assigned_name' => $this->user_name($c->assigned_to),
            'unread' => (int) $c->unread,
            'preview' => $c->preview,
            'last_message_at' => $c->last_message_at,
            'last_inbound_at' => $c->last_inbound_at,
        ];
    }

    /** Body: view (mine | unassigned | all | closed), channel, search, paged. */
    public function get_inbox_conversations($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $ct = $this->t('conversations');
        $me = (int) $user->ID;
        $view = in_array($b['view'] ?? '', ['mine', 'unassigned', 'all', 'closed'], true) ? $b['view'] : 'all';
        $views = [
            'mine' => $wpdb->prepare("c.status = 'open' AND c.assigned_to = %d", $me),
            'unassigned' => "c.status = 'open' AND c.assigned_to IS NULL",
            'all' => "c.status = 'open'",
            'closed' => "c.status = 'closed'",
        ];
        $where = [$this->visible_sql($user), $views[$view]];
        if (in_array($b['channel'] ?? '', self::CHANNELS, true)) $where[] = $wpdb->prepare('c.channel = %s', $b['channel']);
        $kw = trim((string) ($b['search'] ?? ''));
        if ($kw !== '') {
            $like = '%' . $wpdb->esc_like($kw) . '%';
            $where[] = $wpdb->prepare('(c.name LIKE %s OR c.external_id LIKE %s OR c.linked_phone LIKE %s OR c.preview LIKE %s)', $like, $like, $like, $like);
        }
        $w = implode(' AND ', $where);
        $paged = max(1, absint($b['paged'] ?? 1));
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $ct c WHERE $w");
        $rows = (array) $wpdb->get_results($wpdb->prepare("SELECT c.* FROM $ct c WHERE $w ORDER BY c.last_message_at DESC, c.id DESC LIMIT %d OFFSET %d", self::PER_PAGE, ($paged - 1) * self::PER_PAGE));

        $counts = [];
        foreach ($views as $k => $sql) {
            $counts[$k] = (int) $wpdb->get_var("SELECT COUNT(*) FROM $ct c WHERE {$this->visible_sql($user)} AND $sql");
        }
        $this->respond([
            'conversations' => array_map([$this, 'shape_conversation'], $rows),
            'total' => $total,
            'max_pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'counts' => $counts,
            'channels' => ['whatsapp' => !empty($this->settings()['whatsapp']['enabled']) && $this->whatsapp_ready(), 'messenger' => $this->messenger_ready()],
        ]);
    }

    private function shape_message($m)
    {
        $media = $m->media ? json_decode($m->media, true) : [];
        return [
            'id' => (int) $m->id,
            'direction' => $m->direction,
            'type' => $m->type,
            'text' => (string) $m->text,
            'media' => [
                'has_file' => !empty($media['id']) || !empty($media['url']),
                'url' => (string) ($media['url'] ?? ''),
                'mime' => (string) ($media['mime'] ?? ''),
                'filename' => (string) ($media['filename'] ?? ''),
                'lat' => $media['lat'] ?? null,
                'lng' => $media['lng'] ?? null,
            ],
            'status' => $m->status,
            'error' => $m->error,
            'author' => $m->direction === 'out' ? ($m->author_id ? $this->user_name($m->author_id) : 'Page / phone') : '',
            'created_at' => $m->created_at,
        ];
    }

    /** Who the customer is, and their orders, by the conversation's phone. */
    private function customer_panel($c)
    {
        global $wpdb;
        $phone = $c->channel === 'whatsapp' ? '+' . $c->external_id : (string) $c->linked_phone;
        $out = ['phone' => $phone, 'contact' => null, 'orders' => [], 'order_count' => 0, 'order_total' => 0];
        if ($c->contact_id) {
            $k = $wpdb->get_row($wpdb->prepare("SELECT id, first_name, last_name, email, phone, tags, notes FROM {$wpdb->prefix}cstore_mkt_contacts WHERE id = %d", $c->contact_id));
            if ($k) $out['contact'] = ['id' => (int) $k->id, 'name' => trim($k->first_name . ' ' . $k->last_name), 'email' => (string) $k->email, 'tags' => array_values(array_filter(explode(',', (string) $k->tags))), 'notes' => (string) $k->notes];
        }
        $digits = preg_replace('/\D+/', '', $phone);
        $m = $this->marketing();
        if (strlen($digits) >= 10 && $m) {
            $ot = $wpdb->prefix . 'cstore_orders';
            $tail = substr($digits, -10);
            $match = "(" . $m->phone_tail_sql('billing_phone') . " = %s OR " . $m->phone_tail_sql('shipping_phone') . " = %s) AND COALESCE(status,'') NOT IN ('incomplete','draft','trash')";
            $sum = $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) n, COALESCE(SUM(total_amount), 0) total, MAX(currency) currency FROM $ot WHERE $match", $tail, $tail));
            $out['order_count'] = (int) ($sum->n ?? 0);
            $out['order_total'] = round((float) ($sum->total ?? 0), 2);
            $out['currency'] = (string) ($sum->currency ?? '');
            $out['orders'] = array_map(function ($o) {
                return ['id' => (int) $o->id, 'status' => $o->status, 'total' => round((float) $o->total_amount, 2), 'currency' => $o->currency, 'created_at' => $o->created_at];
            }, (array) $wpdb->get_results($wpdb->prepare("SELECT id, status, total_amount, currency, created_at FROM $ot WHERE $match ORDER BY id DESC LIMIT 5", $tail, $tail)));
        }
        return $out;
    }

    /** Body: id, before (message id, for older pages). Opening it marks it read. */
    public function get_inbox_conversation($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $c = $this->conversation_for_user($b['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found (it may have been assigned to someone else).', 404);

        $before = absint($b['before'] ?? 0);
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->t('messages')} WHERE conversation_id = %d" . ($before ? ' AND id < %d' : ' AND %d = %d') . " ORDER BY id DESC LIMIT 40",
            $c->id, $before ?: 0, $before ?: 0
        ));
        if (!$before && (int) $c->unread > 0) {
            $wpdb->update($this->t('conversations'), ['unread' => 0], ['id' => (int) $c->id]);
            $c->unread = 0;
        }
        $agents = [];
        if ($this->is_admin_user($user)) {
            foreach (get_users(['role__in' => self::ROLES, 'orderby' => 'display_name', 'number' => 200]) as $u) $agents[] = ['id' => (int) $u->ID, 'name' => $u->display_name ?: $u->user_login];
        }
        $this->respond([
            'conversation' => $this->shape_conversation($c),
            'messages' => array_map([$this, 'shape_message'], array_reverse($rows)),
            'has_older' => count($rows) === 40,
            'window' => $this->reply_window($c),
            'customer' => $before ? null : $this->customer_panel($c),
            'agents' => $agents,
            'me' => ['id' => (int) $user->ID, 'is_admin' => $this->is_admin_user($user)],
        ]);
    }

    /** Body: id, text. A reply to an unassigned conversation takes it. */
    public function inbox_send($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $c = $this->conversation_for_user($b['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found (it may have been assigned to someone else).', 404);
        $text = trim(str_replace("\r\n", "\n", (string) ($b['text'] ?? '')));
        if ($text === '') return $this->fail('Write a message first.');
        $max = $c->channel === 'messenger' ? 2000 : 4096;
        if (mb_strlen($text) > $max) return $this->fail('The message is too long (' . $max . ' characters at most).');
        $win = $this->reply_window($c);
        if (!$win['can_reply']) return $this->fail($win['reason'], 409);

        if (!$c->assigned_to) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->t('conversations')} SET assigned_to = %d, assigned_at = %s WHERE id = %d AND assigned_to IS NULL", $user->ID, $this->now(), $c->id));
        }
        $now = $this->now();
        $wpdb->insert($this->t('messages'), ['conversation_id' => (int) $c->id, 'direction' => 'out', 'provider_id' => null, 'type' => 'text', 'text' => $text, 'media' => '', 'status' => 'sending', 'author_id' => (int) $user->ID, 'created_at' => $now]);
        $mid = (int) $wpdb->insert_id;

        if ($c->channel === 'whatsapp') {
            $m = $this->marketing();
            $r = $m->wa_api($m->setting('whatsapp.phone_number_id', '') . '/messages', 'POST', [
                'messaging_product' => 'whatsapp', 'recipient_type' => 'individual', 'to' => $c->external_id,
                'type' => 'text', 'text' => ['preview_url' => false, 'body' => $text],
            ]);
            $pid = $r['ok'] ? 'wa:' . (string) ($r['data']['messages'][0]['id'] ?? '') : '';
            $err = $r['ok'] ? '' : (strpos((string) $r['error'], '131047') !== false || stripos((string) $r['error'], 're-engagement') !== false
                ? 'WhatsApp refused it: more than 24 hours since the customer last wrote, so only a template can be sent.' : $r['error']);
        } else {
            $payload = ['recipient' => ['id' => $c->external_id], 'message' => ['text' => $text]];
            $payload += $win['mode'] === 'HUMAN_AGENT' ? ['messaging_type' => 'MESSAGE_TAG', 'tag' => 'HUMAN_AGENT'] : ['messaging_type' => 'RESPONSE'];
            $r = $this->page_api('POST', $this->settings()['messenger']['page_id'] . '/messages', $payload);
            $pid = $r['ok'] ? 'fb:' . (string) ($r['data']['message_id'] ?? '') : '';
            $err = $r['ok'] ? '' : $r['error'];
        }

        if ($r['ok']) {
            /* A provider id clash means its echo/receipt already stored it: keep this row, drop the copy. */
            $wpdb->query($wpdb->prepare("DELETE FROM {$this->t('messages')} WHERE provider_id = %s AND id <> %d", $pid, $mid));
            $wpdb->update($this->t('messages'), ['status' => 'sent', 'provider_id' => $pid !== 'wa:' && $pid !== 'fb:' ? $pid : null], ['id' => $mid]);
        } else {
            $wpdb->update($this->t('messages'), ['status' => 'failed', 'error' => mb_substr((string) $err, 0, 500)], ['id' => $mid]);
        }
        $wpdb->query($wpdb->prepare("UPDATE {$this->t('conversations')} SET preview = %s, last_message_at = %s, unread = 0 WHERE id = %d", 'You: ' . $this->preview_of('text', $text), $now, $c->id));
        $msg = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t('messages')} WHERE id = %d", $mid));
        if (!$r['ok']) return $this->fail('Not sent: ' . $err, 502);
        $this->respond(['status' => 'success', 'message' => $this->shape_message($msg)]);
    }

    /** Body: id, user_id (0 = unassign). Agents: take an unassigned one, or hand back their own. */
    public function inbox_assign($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $c = $this->conversation_for_user($b['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found.', 404);
        $to = absint($b['user_id'] ?? 0);
        if (!$this->is_admin_user($user)) {
            $take = $to === (int) $user->ID && !$c->assigned_to;
            $hand_back = $to === 0 && (int) $c->assigned_to === (int) $user->ID;
            if (!$take && !$hand_back) return $this->fail('You can take an unassigned conversation, or hand back your own.', 403);
        }
        if ($to) {
            $u = get_user_by('id', $to);
            if (!$u || !array_intersect(self::ROLES, (array) $u->roles)) return $this->fail('Choose an administrator or support agent.');
        }
        $wpdb->update($this->t('conversations'), ['assigned_to' => $to ?: null, 'assigned_at' => $to ? $this->now() : null], ['id' => (int) $c->id]);
        $this->respond(['status' => 'success', 'assigned_to' => $to ?: null, 'assigned_name' => $this->user_name($to)]);
    }

    /** Body: id, status (open | closed). */
    public function inbox_set_status($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $c = $this->conversation_for_user($b['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found.', 404);
        $status = ($b['status'] ?? '') === 'closed' ? 'closed' : 'open';
        $wpdb->update($this->t('conversations'), $status === 'closed'
            ? ['status' => 'closed', 'closed_at' => $this->now(), 'closed_by' => (int) $user->ID, 'unread' => 0]
            : ['status' => 'open', 'closed_at' => null, 'closed_by' => null], ['id' => (int) $c->id]);
        $this->respond(['status' => 'success', 'conversation_status' => $status]);
    }

    public function inbox_mark_read($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $c = $this->conversation_for_user($this->body($request)['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found.', 404);
        $wpdb->update($this->t('conversations'), ['unread' => 0], ['id' => (int) $c->id]);
        $this->respond(['status' => 'success']);
    }

    /** Body: id, phone. Messenger shares no phone: an agent links one to see the customer and orders. */
    public function inbox_link_phone($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $b = $this->body($request);
        $c = $this->conversation_for_user($b['id'] ?? 0, $user);
        if (!$c) return $this->fail('Conversation not found.', 404);
        if ($c->channel !== 'messenger') return $this->fail('WhatsApp conversations already have the customer\'s number.');
        $raw = trim((string) ($b['phone'] ?? ''));
        $m = $this->marketing();
        $phone = $raw === '' ? '' : ($m ? (string) $m->clean_phone($raw) : $raw);
        if ($raw !== '' && $phone === '') return $this->fail('That phone number is not valid.');
        $wpdb->update($this->t('conversations'), ['linked_phone' => $phone, 'contact_id' => $phone ? $this->contact_id_for_phone($phone) : 0], ['id' => (int) $c->id]);
        $c = $this->conversation_for_user($c->id, $user);
        $this->respond(['status' => 'success', 'customer' => $this->customer_panel($c), 'conversation' => $this->shape_conversation($c)]);
    }

    /** For the menu badge: conversations with unread messages the user can see. */
    public function get_inbox_counts($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        if ((int) get_option(self::SCHEMA_OPTION, 0) < self::SCHEMA_VERSION) $this->respond(['unread' => 0, 'mine' => 0, 'unassigned' => 0]);
        $ct = $this->t('conversations');
        $vis = $this->visible_sql($user);
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(c.unread > 0) unread, SUM(c.assigned_to = %d) mine, SUM(c.assigned_to IS NULL) unassigned FROM $ct c WHERE c.status = 'open' AND $vis",
            $user->ID
        ), ARRAY_A);
        $this->respond(array_map('intval', (array) $row));
    }

    /** A message's photo / file, fetched with the channel's credentials (WhatsApp media needs the token). Body: message_id. */
    public function get_inbox_media($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;
        global $wpdb;
        $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->t('messages')} WHERE id = %d", absint($this->body($request)['message_id'] ?? 0)));
        $c = $m ? $this->conversation_for_user($m->conversation_id, $user) : null;
        if (!$m || !$c) return $this->fail('Message not found.', 404);
        $media = $m->media ? json_decode($m->media, true) : [];

        if ($c->channel === 'whatsapp' && !empty($media['id'])) {
            $mk = $this->marketing();
            $r = $mk ? $mk->wa_api(rawurlencode($media['id'])) : ['ok' => false, 'error' => 'WhatsApp is not set up.'];
            if (!$r['ok'] || empty($r['data']['url'])) return $this->fail('The file is no longer available from WhatsApp' . (!empty($r['error']) ? ': ' . $r['error'] : '.'), 502);
            $url = (string) $r['data']['url'];
            $headers = ['Authorization' => 'Bearer ' . (string) $mk->setting('whatsapp.access_token', '')];
        } elseif (!empty($media['url'])) {
            $url = (string) $media['url'];
            $headers = [];
        } else {
            return $this->fail('This message has no file.', 404);
        }
        /* Only Meta's own file hosts. */
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $ok_host = strpos($url, 'https://') === 0 && preg_match('/(^|\.)(fbsbx\.com|fbcdn\.net|facebook\.com|whatsapp\.net)$/', $host);
        if (!$ok_host) return $this->fail('The file is not on Meta\'s servers.', 502);
        $res = wp_remote_get($url, ['timeout' => 30, 'headers' => $headers, 'limit_response_size' => 25 * MB_IN_BYTES]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return $this->fail('The file could not be downloaded.', 502);
        $type = strtolower(trim(explode(';', (string) wp_remote_retrieve_header($res, 'content-type'))[0])) ?: ((string) ($media['mime'] ?? '') ?: 'application/octet-stream');
        $body = wp_remote_retrieve_body($res);
        nocache_headers();
        header('Content-Type: ' . (preg_match('#^[a-z]+/[a-z0-9.+-]+$#i', $type) ? $type : 'application/octet-stream'));
        header('Content-Length: ' . strlen($body));
        header('X-Content-Type-Options: nosniff');
        $name = sanitize_file_name((string) ($media['filename'] ?? '')) ?: 'file-' . (int) $m->id;
        header('Content-Disposition: ' . (preg_match('#^(image|video|audio)/#', $type) ? 'inline' : 'attachment') . '; filename="' . $name . '"');
        echo $body;
        exit;
    }


    /* ---------------------------------------------------------------- */
    /* Settings (admin)                                                  */

    public function get_inbox_settings($request)
    {
        $user = $this->admin_auth($request);
        if (is_wp_error($user)) return $user;
        $s = $this->settings();
        if ($s['messenger']['verify_token'] === '') {
            $saved = get_option(self::OPTION, []);
            $saved = is_array($saved) ? $saved : [];
            $saved['messenger'] = array_merge($s['messenger'], ['verify_token' => wp_generate_password(32, false, false)]);
            update_option(self::OPTION, $saved, false);
            $s = $this->settings();
        }
        $out = $s;
        $out['messenger']['has_page_token'] = $s['messenger']['page_token'] !== '';
        $out['messenger']['has_app_secret'] = $s['messenger']['app_secret'] !== '';
        $out['messenger']['page_token'] = '';
        $out['messenger']['app_secret'] = '';
        $out['messenger']['webhook_url'] = rest_url('combo-store/v2/inbox_messenger_webhook');
        $out['messenger']['ready'] = $this->messenger_ready();
        $m = $this->marketing();
        $out['whatsapp']['connected'] = $this->whatsapp_ready();
        $out['whatsapp']['has_app_secret'] = $m && (string) $m->setting('whatsapp.app_secret', '') !== '';
        $this->respond($out);
    }

    public function update_inbox_settings($request)
    {
        $user = $this->admin_auth($request);
        if (is_wp_error($user)) return $user;
        $b = $this->body($request);
        $cur = $this->settings();
        $out = $cur;
        if (is_array($b['whatsapp'] ?? null)) $out['whatsapp']['enabled'] = !empty($b['whatsapp']['enabled']);
        if (is_array($b['messenger'] ?? null)) {
            $f = $b['messenger'];
            $token = preg_replace('/\s+/', '', (string) ($f['page_token'] ?? ''));
            $secret = trim((string) ($f['app_secret'] ?? ''));
            $out['messenger'] = [
                'enabled' => !empty($f['enabled']),
                'page_id' => preg_replace('/\D+/', '', (string) ($f['page_id'] ?? $cur['messenger']['page_id'])),
                'page_token' => $token !== '' ? sanitize_text_field($token) : $cur['messenger']['page_token'],
                'app_secret' => !empty($f['clear_app_secret']) ? '' : ($secret !== '' ? sanitize_text_field($secret) : $cur['messenger']['app_secret']),
                'verify_token' => $cur['messenger']['verify_token'] ?: wp_generate_password(32, false, false),
                'human_agent_tag' => !empty($f['human_agent_tag']),
            ];
            if ($out['messenger']['enabled'] && ($out['messenger']['page_id'] === '' || $out['messenger']['page_token'] === '')) {
                return $this->fail('To turn Messenger on, add the Page ID and a Page access token.');
            }
        }
        update_option(self::OPTION, $out, false);
        $this->respond(['status' => 'success']);
    }

    /** Checks the Page token and subscribes the Page to this app's webhook (messages, echoes, deliveries, reads). */
    public function inbox_test($request)
    {
        $user = $this->admin_auth($request);
        if (is_wp_error($user)) return $user;
        $s = $this->settings()['messenger'];
        if ($s['page_id'] === '' || $s['page_token'] === '') return $this->fail('Add the Page ID and Page access token first, then save.');
        $r = $this->page_api('GET', $s['page_id'], null, ['fields' => 'name']);
        if (!$r['ok']) return $this->fail($r['error']);
        $name = (string) ($r['data']['name'] ?? 'the Page');
        $sub = $this->page_api('POST', $s['page_id'] . '/subscribed_apps', null, ['subscribed_fields' => 'messages,message_echoes,message_deliveries,message_reads']);
        if (!$sub['ok']) return $this->fail('Connected to "' . $name . '", but subscribing the Page to the webhook failed: ' . $sub['error']);
        $this->respond(['status' => 'success', 'message' => 'Connected to "' . $name . '" and subscribed it to messages.']);
    }
}
