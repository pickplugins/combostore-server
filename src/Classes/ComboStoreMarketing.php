<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Email and SMS marketing: one global contact list, lists (segments) of those
 * contacts, and campaigns sent to them.
 *
 * Tables (created by maybe_upgrade_schema, option combo_store_marketing_schema):
 *  - cstore_mkt_contacts       one row per person. email and phone are each
 *                              unique (NULL when unknown). email_status and
 *                              sms_status are tracked separately, because
 *                              someone can want one channel and not the other:
 *                              subscribed / unsubscribed / bounced (email only).
 *                              tags is stored ",a,b," so a tag matches with LIKE.
 *  - cstore_mkt_lists          named lists. `public` lists accept sign-ups
 *                              from the storefront (mkt_subscribe).
 *  - cstore_mkt_list_contacts  membership.
 *  - cstore_mkt_campaigns      a campaign is email or sms. audience is JSON
 *                              {all: bool, lists: [ids], tags: [..]}.
 *  - cstore_mkt_messages       one row per recipient once a campaign is sent:
 *                              the send queue and the delivery / open / click log.
 *
 * Sending: send_mkt_campaign snapshots the audience into cstore_mkt_messages
 * (status queued) and marks the campaign `sending`; a scheduled campaign is
 * snapshotted when its time comes. Batches go out from the
 * cstore_marketing_tick cron (every minute) and from process_mkt_queue, which
 * the dashboard calls while someone watches a sending campaign, so progress
 * does not wait for WP-cron. Both run under one MySQL lock.
 *
 * Email goes over SMTP (PHPMailer, settings in option combo_store_marketing),
 * or through wp_mail when the mailer is set to "wordpress". SMS goes through
 * the configured provider: bulksmsbd, twilio, or a custom HTTP API.
 *
 * Credentials never leave the server: get_marketing_settings blanks secrets
 * and reports has_* flags; a blank secret in update_marketing_settings keeps
 * the stored one.
 *
 * Every dashboard route is POST with a Bearer JWT, administrators only (the
 * Marketing menu is administrators only). mkt_open / mkt_click /
 * mkt_unsubscribe are public GETs signed with wp_hash; mkt_subscribe is a
 * public POST.
 */
class ComboStoreMarketing
{
    /** The one instance (ComboStoreVoice reuses its helpers). @var ComboStoreMarketing|null */
    public static $instance = null;

    const SCHEMA_VERSION = 10;
    /* Meta Graph API version for WhatsApp; Meta retires each about two years after release. */
    const WA_API_VERSION = 'v25.0';

    /* Automated campaigns (campaigns.automation). They are never "sent" as a whole:
       status is active / inactive, and one message per contact is queued when
       the event happens. */
    const AUTOMATIONS = ['welcome', 'abandoned_cart', 'abandoned_cart_whatsapp'];

    /* The automations that send a shopper's abandoned cart (each with its own campaign, wait and limit). */
    const CART_AUTOMATIONS = ['abandoned_cart', 'abandoned_cart_whatsapp'];

    /* Orders that count as placed (an abandoned checkout is "recovered" by one of these). */
    const PLACED_SQL = "COALESCE(status,'') NOT IN ('incomplete','draft','trash','failed','cancelled','canceled')";
    const CONFIRM_DAYS = 7;
    const ADMIN_ROLES = ['administrator'];
    const OPTION = 'combo_store_marketing';
    const LOCK = 'cstore_mkt_queue';

    const EMAIL_STATUSES = ['subscribed', 'unsubscribed', 'bounced'];
    const SMS_STATUSES = ['subscribed', 'unsubscribed'];
    const CAMPAIGN_STATUSES = ['draft', 'scheduled', 'sending', 'paused', 'sent', 'cancelled'];
    /* 'voice': automated calls placed by ComboStoreVoice (text-to-speech, keypress options). */
    const CHANNELS = ['email', 'sms', 'whatsapp', 'call', 'voice'];

    /* Who works call lists (the Calls screen). Each call list can narrow it to chosen people. */
    const CALL_ROLES = ['administrator', 'store_manager', 'support_agent'];

    /*
        Call outcomes. "final" ends the task; "retry" puts it back after
        retry_hours until max_attempts; "callback" comes back at the time the
        agent chose, reserved for them. do_not_call and wrong_number also mark the contact (off future lists).
    */
    const CALL_OUTCOMES = [
        'interested'     => ['label' => 'Interested', 'kind' => 'final'],
        'ordered'        => ['label' => 'Placed an order', 'kind' => 'final'],
        'not_interested' => ['label' => 'Not interested', 'kind' => 'final'],
        'callback'       => ['label' => 'Call back later', 'kind' => 'callback'],
        'no_answer'      => ['label' => 'No answer', 'kind' => 'retry'],
        'busy'           => ['label' => 'Busy / switched off', 'kind' => 'retry'],
        'wrong_number'   => ['label' => 'Wrong number', 'kind' => 'final'],
        'do_not_call'    => ['label' => 'Asked not to be called', 'kind' => 'final'],
    ];

    /* A claimed call left this long without an outcome goes back to the list. */
    const CALL_CLAIM_MINUTES = 30;

    /* WhatsApp needs its own opt-in (Meta's policy): "none" until they give it. */
    const WA_STATUSES = ['subscribed', 'unsubscribed'];
    /* Calls: allowed, asked not to be called, or the number reached someone else (cleared when the phone changes). */
    const CALL_STATUSES = ['allowed', 'do_not_call', 'wrong_number'];

    /* Words that opt a contact out of WhatsApp when they reply with them. */
    const WA_STOP_WORDS = ['stop', 'unsubscribe', 'stop promotions', 'cancel', 'end', 'quit', 'বন্ধ'];

    /* Secrets: [settings path] => has_* flag reported instead. */
    const SECRETS = [
        'smtp.password'        => 'has_smtp_password',
        'bulksmsbd.api_key'    => 'has_bulksmsbd_api_key',
        'twilio.auth_token'    => 'has_twilio_auth_token',
        'whatsapp.access_token' => 'has_whatsapp_access_token',
        'whatsapp.app_secret'   => 'has_whatsapp_app_secret',
    ];

    public function __construct()
    {
        self::$instance = $this;
        add_action('rest_api_init', array($this, 'maybe_upgrade_schema'), 5);
        add_action('rest_api_init', array($this, 'register_routes'));

        add_filter('cron_schedules', array($this, 'cron_schedules'));
        add_action('cstore_marketing_tick', array($this, 'cron_tick'));
        add_action('init', function () {
            if (!wp_next_scheduled('cstore_marketing_tick')) {
                wp_schedule_event(time() + 60, 'cstore_every_minute', 'cstore_marketing_tick');
            }
        });

        /* New customers and order billing details join the contact list when
           Settings > Contacts > "Add new customers" is on. */
        add_action('combo_store_order_created', array($this, 'on_order_created'));
        add_action('combo_store_order_created', array($this, 'on_checkout_whatsapp_optin'), 15);
        add_action('combo_store_order_created', array($this, 'on_checkout_abandoned'), 20);
        add_action('user_register', array($this, 'on_user_register'), 20);
    }

    public function cron_schedules($schedules)
    {
        $schedules['cstore_every_minute'] = ['interval' => 60, 'display' => 'Every minute (ComboStore marketing)'];
        return $schedules;
    }


    /* ---------------------------------------------------------------- */
    /* Schema                                                            */

    private function tables()
    {
        global $wpdb;
        $p = $wpdb->prefix . 'cstore_mkt_';
        return [
            'contacts'  => $p . 'contacts',
            'lists'     => $p . 'lists',
            'members'   => $p . 'list_contacts',
            'campaigns' => $p . 'campaigns',
            'messages'  => $p . 'messages',
            'calls'     => $p . 'calls',
        ];
    }

    public static function tables_sql()
    {
        global $wpdb;
        $p = $wpdb->prefix . 'cstore_mkt_';
        $c = $wpdb->get_charset_collate();

        return [
            $p . 'contacts' => "CREATE TABLE IF NOT EXISTS {$p}contacts (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                email VARCHAR(190) DEFAULT NULL,
                phone VARCHAR(32) DEFAULT NULL,
                first_name VARCHAR(100) NOT NULL DEFAULT '',
                last_name VARCHAR(100) NOT NULL DEFAULT '',
                user_id BIGINT UNSIGNED DEFAULT NULL,
                email_status VARCHAR(20) NOT NULL DEFAULT 'subscribed',
                sms_status VARCHAR(20) NOT NULL DEFAULT 'subscribed',
                source VARCHAR(30) NOT NULL DEFAULT 'manual',
                tags TEXT NULL,
                notes TEXT NULL,
                email_unsubscribed_at DATETIME DEFAULT NULL,
                sms_unsubscribed_at DATETIME DEFAULT NULL,
                last_sent_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY email (email),
                UNIQUE KEY phone (phone),
                KEY user_id (user_id),
                KEY email_status (email_status),
                KEY sms_status (sms_status)
            ) $c",
            $p . 'lists' => "CREATE TABLE IF NOT EXISTS {$p}lists (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(150) NOT NULL,
                description TEXT NULL,
                public TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id)
            ) $c",
            $p . 'list_contacts' => "CREATE TABLE IF NOT EXISTS {$p}list_contacts (
                list_id BIGINT UNSIGNED NOT NULL,
                contact_id BIGINT UNSIGNED NOT NULL,
                added_at DATETIME NOT NULL,
                PRIMARY KEY (list_id, contact_id),
                KEY contact_id (contact_id)
            ) $c",
            $p . 'campaigns' => "CREATE TABLE IF NOT EXISTS {$p}campaigns (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(190) NOT NULL,
                channel VARCHAR(10) NOT NULL DEFAULT 'email',
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                subject VARCHAR(255) NOT NULL DEFAULT '',
                preheader VARCHAR(255) NOT NULL DEFAULT '',
                from_name VARCHAR(150) NOT NULL DEFAULT '',
                from_email VARCHAR(190) NOT NULL DEFAULT '',
                reply_to VARCHAR(190) NOT NULL DEFAULT '',
                body LONGTEXT NULL,
                audience TEXT NULL,
                track_opens TINYINT(1) NOT NULL DEFAULT 1,
                track_clicks TINYINT(1) NOT NULL DEFAULT 1,
                scheduled_at DATETIME DEFAULT NULL,
                started_at DATETIME DEFAULT NULL,
                finished_at DATETIME DEFAULT NULL,
                recipients INT UNSIGNED NOT NULL DEFAULT 0,
                created_by BIGINT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY status (status),
                KEY channel (channel)
            ) $c",
            $p . 'messages' => "CREATE TABLE IF NOT EXISTS {$p}messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                contact_id BIGINT UNSIGNED NOT NULL,
                channel VARCHAR(10) NOT NULL,
                to_addr VARCHAR(190) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'queued',
                error VARCHAR(500) NOT NULL DEFAULT '',
                provider_ref VARCHAR(190) NOT NULL DEFAULT '',
                sent_at DATETIME DEFAULT NULL,
                opened_at DATETIME DEFAULT NULL,
                clicked_at DATETIME DEFAULT NULL,
                unsubscribed_at DATETIME DEFAULT NULL,
                opens INT UNSIGNED NOT NULL DEFAULT 0,
                clicks INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY campaign_contact (campaign_id, contact_id),
                KEY campaign_status (campaign_id, status),
                KEY contact_id (contact_id)
            ) $c",
            /* v7: one row per logged call, so reports can say who made each attempt
               (a call task's assigned_to is cleared when it goes back for a retry). */
            $p . 'calls' => "CREATE TABLE IF NOT EXISTS {$p}calls (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                campaign_id BIGINT UNSIGNED NOT NULL,
                message_id BIGINT UNSIGNED NOT NULL,
                contact_id BIGINT UNSIGNED NOT NULL,
                agent_id BIGINT UNSIGNED NOT NULL,
                outcome VARCHAR(30) NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY campaign_agent (campaign_id, agent_id),
                KEY agent_created (agent_id, created_at),
                KEY message_id (message_id)
            ) $c",
        ];
    }

    public function maybe_upgrade_schema()
    {
        if ((int) get_option('combo_store_marketing_schema', 0) >= self::SCHEMA_VERSION) return;

        global $wpdb;
        foreach (self::tables_sql() as $table => $sql) {
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table) continue;
            if ($wpdb->query($sql) === false) return;
        }

        /* v2: double opt-in. The token is stored hashed; the link carries the plain one. */
        $t = $this->tables();
        $add = [];
        foreach ([
            'email_confirm_token' => "VARCHAR(64) NOT NULL DEFAULT ''",
            'email_confirm_sent_at' => 'DATETIME DEFAULT NULL',
            'email_confirmed_at' => 'DATETIME DEFAULT NULL',
            'consent_ip' => "VARCHAR(45) NOT NULL DEFAULT ''",
        ] as $col => $def) {
            if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['contacts']} LIKE %s", $col))) $add[] = "ADD COLUMN $col $def";
        }
        if ($add && $wpdb->query("ALTER TABLE {$t['contacts']} " . implode(', ', $add)) === false) return;

        /* v3: automated campaigns, starting with the welcome email (on, with default content). */
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['campaigns']} LIKE %s", 'automation'))) {
            if ($wpdb->query("ALTER TABLE {$t['campaigns']} ADD COLUMN automation VARCHAR(30) NOT NULL DEFAULT '' AFTER status, ADD KEY automation (automation)") === false) return;
        }
        $this->automation_campaign('welcome');

        /* v4: one message per campaign + contact + reference (an abandoned order),
           so a contact can get an abandoned-cart email per cart; send_after delays it. */
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['messages']} LIKE %s", 'ref_id'))) {
            $ok = $wpdb->query("ALTER TABLE {$t['messages']}
                ADD COLUMN ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER contact_id,
                ADD COLUMN send_after DATETIME DEFAULT NULL AFTER status,
                DROP INDEX campaign_contact,
                ADD UNIQUE KEY campaign_contact (campaign_id, contact_id, ref_id),
                ADD KEY send_after (send_after)");
            if ($ok === false) return;
        }
        $this->automation_campaign('abandoned_cart');

        /* v5: WhatsApp. Its own consent per contact, and delivery receipts per message. */
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['contacts']} LIKE %s", 'whatsapp_status'))) {
            if ($wpdb->query("ALTER TABLE {$t['contacts']} ADD COLUMN whatsapp_status VARCHAR(20) NOT NULL DEFAULT 'none' AFTER sms_status, ADD COLUMN whatsapp_unsubscribed_at DATETIME DEFAULT NULL AFTER sms_unsubscribed_at, ADD KEY whatsapp_status (whatsapp_status)") === false) return;
        }
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['messages']} LIKE %s", 'delivered_at'))) {
            if ($wpdb->query("ALTER TABLE {$t['messages']} ADD COLUMN delivered_at DATETIME DEFAULT NULL AFTER sent_at, ADD KEY provider_ref (provider_ref)") === false) return;
        }

        /* v6: call lists. A contact can ask not to be called; a message row is then a call task. */
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['contacts']} LIKE %s", 'call_status'))) {
            if ($wpdb->query("ALTER TABLE {$t['contacts']} ADD COLUMN call_status VARCHAR(20) NOT NULL DEFAULT 'allowed' AFTER whatsapp_status") === false) return;
        }
        if (!$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$t['messages']} LIKE %s", 'assigned_to'))) {
            $ok = $wpdb->query("ALTER TABLE {$t['messages']}
                ADD COLUMN assigned_to BIGINT UNSIGNED DEFAULT NULL AFTER status,
                ADD COLUMN claimed_at DATETIME DEFAULT NULL AFTER assigned_to,
                ADD COLUMN attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER claimed_at,
                ADD COLUMN outcome VARCHAR(30) NOT NULL DEFAULT '' AFTER attempts,
                ADD COLUMN notes TEXT NULL AFTER outcome,
                ADD KEY assigned_to (assigned_to),
                ADD KEY outcome (outcome)");
            if ($ok === false) return;
        }

        /* v8: "wrong number" marks the contact. Back-fill it from calls logged before that:
           a contact whose latest logged call was wrong_number, to the phone they still have. */
        if ((int) get_option('combo_store_marketing_schema', 0) < 8) {
            $wpdb->query("UPDATE {$t['contacts']} c
                JOIN {$t['messages']} m ON m.contact_id = c.id AND m.channel = 'call' AND m.outcome = 'wrong_number' AND m.to_addr = c.phone
                SET c.call_status = 'wrong_number'
                WHERE c.call_status = 'allowed'
                  AND m.sent_at = (SELECT latest FROM (SELECT contact_id, MAX(sent_at) latest FROM {$t['messages']}
                                   WHERE channel = 'call' AND outcome <> '' AND sent_at IS NOT NULL GROUP BY contact_id) x WHERE x.contact_id = c.id)");
        }

        /* v9: automations created twice by racing requests (before automation_campaign
           took a lock). Keep the oldest row - the one the dashboard shows and edits -
           move the copies' messages to it (a contact already there keeps theirs),
           and delete the copies. */
        if ((int) get_option('combo_store_marketing_schema', 0) < 9) {
            foreach (self::AUTOMATIONS as $key) {
                $ids = array_map('intval', (array) $wpdb->get_col($wpdb->prepare("SELECT id FROM {$t['campaigns']} WHERE automation = %s ORDER BY id ASC", $key)));
                if (count($ids) < 2) continue;
                $keep = array_shift($ids);
                $in = $this->in_ids($ids);
                $wpdb->query($wpdb->prepare("UPDATE IGNORE {$t['messages']} SET campaign_id = %d WHERE campaign_id IN ($in)", $keep));
                $wpdb->query("DELETE FROM {$t['messages']} WHERE campaign_id IN ($in)");
                $wpdb->query("DELETE FROM {$t['calls']} WHERE campaign_id IN ($in)");
                $wpdb->query("DELETE FROM {$t['campaigns']} WHERE id IN ($in)");
                foreach ($ids as $id) delete_option('cstore_mkt_links_' . $id);
            }
        }

        /* v10: WhatsApp's Graph API version. v21.0 (the old default) stops working on
           2027-01-21: a saved version older than WA_API_VERSION is raised to it;
           a newer one set on purpose is kept. */
        if ((int) get_option('combo_store_marketing_schema', 0) < 10) {
            $saved = $this->settings();
            $v = (string) ($saved['whatsapp']['api_version'] ?? '');
            if (is_array($saved['whatsapp'] ?? null) && ($v === '' || version_compare(ltrim($v, 'v'), ltrim(self::WA_API_VERSION, 'v'), '<'))) {
                $saved['whatsapp']['api_version'] = self::WA_API_VERSION;
                update_option(self::OPTION, $saved, false);
            }
        }

        update_option('combo_store_marketing_schema', self::SCHEMA_VERSION, false);
    }


    /* ---------------------------------------------------------------- */
    /* Routes                                                            */

    public function register_routes()
    {
        $routes = [
            'get_mkt_overview',
            'get_mkt_contacts', 'get_mkt_contact', 'save_mkt_contact', 'delete_mkt_contacts',
            'bulk_mkt_contacts', 'import_mkt_contacts', 'sync_mkt_contacts', 'get_mkt_tags',
            'get_mkt_lists', 'save_mkt_list', 'delete_mkt_list',
            'get_mkt_campaigns', 'get_mkt_campaign', 'save_mkt_campaign', 'delete_mkt_campaign',
            'duplicate_mkt_campaign', 'estimate_mkt_audience', 'send_mkt_campaign',
            'pause_mkt_campaign', 'resume_mkt_campaign', 'cancel_mkt_campaign',
            'get_mkt_campaign_messages', 'send_mkt_test', 'process_mkt_queue',
            'get_marketing_settings', 'update_marketing_settings', 'resend_mkt_confirmation',
            'get_mkt_automation', 'set_mkt_automation', 'get_mkt_wa_templates',
            /* Call lists: get_mkt_call_agents is admin-only; the rest are the agents' Calls screen. */
            'get_mkt_call_agents', 'get_mkt_call_lists', 'mkt_call_next', 'mkt_call_log', 'mkt_call_release',
        ];
        foreach ($routes as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }

        foreach (['mkt_open', 'mkt_click', 'mkt_unsubscribe', 'mkt_confirm'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => ['GET', 'POST'],
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
        /* Meta calls this: GET to verify the webhook, POST with delivery receipts and replies. */
        register_rest_route('combo-store/v2', '/mkt_whatsapp_webhook', array(
            'methods'  => ['GET', 'POST'],
            'callback' => array($this, 'mkt_whatsapp_webhook'),
            'permission_callback' => '__return_true',
        ));
        /* The abandoned-cart email's link: the cart rebuilt at today's prices. */
        register_rest_route('combo-store/v2', '/mkt_cart', array(
            'methods'  => 'POST',
            'callback' => array($this, 'mkt_cart'),
            'permission_callback' => '__return_true',
        ));
        register_rest_route('combo-store/v2', '/mkt_subscribe', array(
            'methods'  => 'POST',
            'callback' => array($this, 'mkt_subscribe'),
            'permission_callback' => '__return_true',
        ));
    }


    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */

    private function auth($request)
    {
        $token = $request->get_header('Authorization');
        if (!$token) {
            return new WP_Error('missing_token', 'Authorization token is required', array('status' => 401));
        }
        try {
            $decoded = JWT::decode(str_replace('Bearer ', '', $token), new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        $user_id = $decoded->sub ?? $decoded->user_id ?? $decoded->data->user->id ?? null;
        $user    = $user_id ? get_user_by('id', $user_id) : null;

        if (!$user || !array_intersect(self::ADMIN_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to manage marketing', array('status' => 403));
        }
        return $user;
    }

    private function body($request)
    {
        $body = json_decode($request->get_body(), true);
        return is_array($body) ? $body : [];
    }

    private function respond($data)
    {
        die(wp_json_encode($data));
    }

    private function fail($message, $status = 400)
    {
        return new WP_Error('marketing_error', $message, array('status' => $status));
    }

    private function now()
    {
        return current_time('mysql');
    }

    private function ids($value, $max = 1000)
    {
        if (!is_array($value)) return [];
        return array_slice(array_values(array_unique(array_filter(array_map('absint', $value)))), 0, $max);
    }

    private function in_ids(array $ids)
    {
        return implode(',', array_map('intval', $ids ?: [0]));
    }

    /** Lowercased, valid email or null. */
    private function clean_email($email)
    {
        $email = strtolower(trim((string) $email));
        if ($email === '' || preg_match('/@mo\.lo$/', $email)) return null;
        return is_email($email) ? sanitize_email($email) : null;
    }

    /** Digits with an optional leading +, or null (fewer than 6 digits is not a phone). */
    public function clean_phone($phone)
    {
        $phone = trim((string) $phone);
        if ($phone === '') return null;
        $plus  = strpos($phone, '+') === 0;
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) < 6 || strlen($digits) > 20) return null;
        if ($plus) return '+' . $digits;
        if (strpos($digits, '00') === 0) return '+' . substr($digits, 2);

        /* With a default country code, local numbers are stored international
           so "01711..." and "+8801711..." are the same contact. */
        $cc = preg_replace('/\D+/', '', (string) $this->setting('sms.country_code', ''));
        if ($cc !== '') {
            if ($digits[0] === '0') return '+' . $cc . substr($digits, 1);
            if (strpos($digits, $cc) === 0 && strlen($digits) > 10) return '+' . $digits;
        }
        return $digits;
    }

    private function clean_tags($tags)
    {
        if (is_string($tags)) $tags = explode(',', $tags);
        if (!is_array($tags)) return [];
        $out = [];
        foreach ($tags as $t) {
            $t = strtolower(trim(sanitize_text_field((string) $t)));
            $t = str_replace(',', '', $t);
            if ($t !== '' && strlen($t) <= 50) $out[$t] = true;
        }
        return array_slice(array_keys($out), 0, 30);
    }

    private function tags_to_db(array $tags)
    {
        return $tags ? ',' . implode(',', $tags) . ',' : '';
    }

    private function tags_from_db($value)
    {
        return array_values(array_filter(explode(',', (string) $value), 'strlen'));
    }

    private function shape_contact($row)
    {
        if (!$row) return null;
        $row = (array) $row;
        $row['id'] = (int) $row['id'];
        $row['user_id'] = $row['user_id'] ? (int) $row['user_id'] : null;
        $row['tags'] = $this->tags_from_db($row['tags'] ?? '');
        unset($row['email_confirm_token']);
        return $row;
    }

    private function settings()
    {
        $s = get_option(self::OPTION, []);
        return is_array($s) ? $s : [];
    }

    public function setting($path, $default = null)
    {
        $node = $this->settings();
        foreach (explode('.', $path) as $key) {
            if (!is_array($node) || !array_key_exists($key, $node)) return $default;
            $node = $node[$key];
        }
        return $node;
    }


    /* ---------------------------------------------------------------- */
    /* Contacts                                                          */

    /**
     * Insert or update one contact, matched by email then phone.
     *
     * $opts: list_ids (added, never removed), overwrite (replace names and
     * statuses on an existing contact; otherwise only blanks are filled and a
     * status is never raised from unsubscribed back to subscribed),
     * source, new_email_status (email status for a contact this creates, default subscribed).
     *
     * Answers [id, 'created'|'updated'|'unchanged'] or a WP_Error.
     */
    public function upsert_contact(array $data, array $opts = [])
    {
        global $wpdb;
        $t = $this->tables();

        $email = $this->clean_email($data['email'] ?? '');
        $phone = $this->clean_phone($data['phone'] ?? '');
        if (!$email && !$phone) return $this->fail('A contact needs an email address or a phone number.');

        $overwrite = !empty($opts['overwrite']);
        $now = $this->now();

        $existing = null;
        if ($email) $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE email = %s", $email));
        if (!$existing && $phone) $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE phone = %s", $phone));

        $fields = [];
        foreach (['first_name', 'last_name'] as $k) {
            if (isset($data[$k])) $fields[$k] = mb_substr(sanitize_text_field((string) $data[$k]), 0, 100);
        }
        if (isset($data['notes'])) $fields['notes'] = mb_substr(sanitize_textarea_field((string) $data['notes']), 0, 2000);
        if (!empty($data['user_id'])) $fields['user_id'] = absint($data['user_id']);

        $email_status = in_array($data['email_status'] ?? '', self::EMAIL_STATUSES, true) ? $data['email_status'] : null;
        $sms_status   = in_array($data['sms_status'] ?? '', self::SMS_STATUSES, true) ? $data['sms_status'] : null;
        $call_status  = in_array($data['call_status'] ?? '', self::CALL_STATUSES, true) ? $data['call_status'] : null;
        $wa_status    = in_array($data['whatsapp_status'] ?? '', self::WA_STATUSES, true) ? $data['whatsapp_status'] : null;
        $tags = array_key_exists('tags', $data) ? $this->clean_tags($data['tags']) : null;

        if (!$existing) {
            $row = array_merge(['first_name' => '', 'last_name' => '', 'notes' => ''], $fields, [
                'email' => $email,
                'phone' => $phone,
                /* new_email_status: double opt-in creates contacts as pending. */
                'email_status' => $email_status ?: ($opts['new_email_status'] ?? 'subscribed'),
                'sms_status' => $sms_status ?: ($opts['new_sms_status'] ?? 'subscribed'),
                'source' => sanitize_key($opts['source'] ?? 'manual') ?: 'manual',
                'tags' => $this->tags_to_db($tags ?: []),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            if ($row['email_status'] === 'unsubscribed') $row['email_unsubscribed_at'] = $now;
            if ($row['sms_status'] === 'unsubscribed') $row['sms_unsubscribed_at'] = $now;
            /* Never assumed: only an explicit opt-in subscribes someone to WhatsApp. */
            $row['whatsapp_status'] = $wa_status ?: 'none';
            if ($call_status) $row['call_status'] = $call_status;
            if ($row['whatsapp_status'] === 'unsubscribed') $row['whatsapp_unsubscribed_at'] = $now;

            if ($wpdb->insert($t['contacts'], $row) === false) {
                /* A parallel insert of the same email/phone, or a phone owned by another contact. */
                return $this->fail('The contact could not be saved: ' . ($wpdb->last_error ?: 'database error'));
            }
            $id = (int) $wpdb->insert_id;
            $state = 'created';
        } else {
            $id = (int) $existing->id;
            $update = [];

            foreach ($fields as $k => $v) {
                if ($overwrite ? (string) $existing->$k !== (string) $v : ((string) $existing->$k === '' && $v !== '')) $update[$k] = $v;
            }
            /* Fill a missing email/phone, unless another contact already has it. */
            foreach (['email' => $email, 'phone' => $phone] as $k => $v) {
                if (!$v || (string) $existing->$k === $v) continue;
                if ((string) $existing->$k !== '' && !$overwrite) continue;
                $owner = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['contacts']} WHERE $k = %s AND id <> %d", $v, $id));
                if (!$owner) $update[$k] = $v;
            }
            /* "Do not call" / "wrong number" always stick; lifting them needs overwrite, like an unsubscribe. */
            if ($call_status && $call_status !== $existing->call_status && ($call_status !== 'allowed' || $overwrite)) $update['call_status'] = $call_status;
            /* "Wrong number" belongs to the old number: a new phone makes them callable again. */
            if (isset($update['phone']) && $existing->call_status === 'wrong_number' && !isset($update['call_status'])) $update['call_status'] = 'allowed';
            foreach (['email_status' => $email_status, 'sms_status' => $sms_status, 'whatsapp_status' => $wa_status] as $k => $v) {
                if (!$v || $existing->$k === $v) continue;
                /* Without overwrite, an import never re-subscribes someone who opted out.
                   "none" (never asked) is not an opt-out, so it can become subscribed. */
                if (!$overwrite && !in_array($existing->$k, ['subscribed', 'none'], true) && $v === 'subscribed') continue;
                $update[$k] = $v;
                $at = str_replace('_status', '_unsubscribed_at', $k);
                $update[$at] = $v === 'subscribed' ? null : $now;
            }
            if ($tags !== null) {
                $merged = $overwrite ? $tags : array_values(array_unique(array_merge($this->tags_from_db($existing->tags), $tags)));
                $db = $this->tags_to_db($merged);
                if ($db !== (string) $existing->tags) $update['tags'] = $db;
            }

            if ($update) {
                $update['updated_at'] = $now;
                if ($wpdb->update($t['contacts'], $update, ['id' => $id]) === false) {
                    return $this->fail('The contact could not be saved: ' . ($wpdb->last_error ?: 'database error'));
                }
            }
            $state = $update ? 'updated' : 'unchanged';
        }

        $list_ids = $this->ids($opts['list_ids'] ?? [], 100);
        if ($list_ids) $this->add_to_lists([$id], $list_ids);

        return [$id, $state];
    }

    private function add_to_lists(array $contact_ids, array $list_ids)
    {
        global $wpdb;
        $t = $this->tables();
        $list_ids = array_map('intval', $wpdb->get_col("SELECT id FROM {$t['lists']} WHERE id IN ({$this->in_ids($list_ids)})"));
        if (!$list_ids || !$contact_ids) return;
        $now = esc_sql($this->now());
        foreach (array_chunk($contact_ids, 500) as $chunk) {
            $values = [];
            foreach ($list_ids as $l) foreach ($chunk as $c) $values[] = '(' . (int) $l . ',' . (int) $c . ",'$now')";
            $wpdb->query("INSERT IGNORE INTO {$t['members']} (list_id, contact_id, added_at) VALUES " . implode(',', $values));
        }
    }

    /** SQL WHERE for the contact list filters (alias c). */
    private function contacts_where(array $b)
    {
        global $wpdb;
        $t = $this->tables();
        $w = ['1=1'];

        $kw = trim((string) ($b['keyword'] ?? ''));
        if ($kw !== '') {
            $like = '%' . $wpdb->esc_like($kw) . '%';
            $w[] = $wpdb->prepare("(c.email LIKE %s OR c.phone LIKE %s OR c.first_name LIKE %s OR c.last_name LIKE %s OR CONCAT(c.first_name, ' ', c.last_name) LIKE %s)", $like, $like, $like, $like, $like);
        }
        if (!empty($b['list_id'])) {
            $w[] = $wpdb->prepare("c.id IN (SELECT contact_id FROM {$t['members']} WHERE list_id = %d)", absint($b['list_id']));
        }
        if (in_array($b['email_status'] ?? '', array_merge(self::EMAIL_STATUSES, ['pending']), true)) $w[] = $wpdb->prepare('c.email_status = %s AND c.email IS NOT NULL', $b['email_status']);
        if (in_array($b['sms_status'] ?? '', self::SMS_STATUSES, true)) $w[] = $wpdb->prepare('c.sms_status = %s AND c.phone IS NOT NULL', $b['sms_status']);
        if (in_array($b['call_status'] ?? '', self::CALL_STATUSES, true)) $w[] = $wpdb->prepare('c.call_status = %s AND c.phone IS NOT NULL', $b['call_status']);
        if (in_array($b['whatsapp_status'] ?? '', array_merge(self::WA_STATUSES, ['none']), true)) $w[] = $wpdb->prepare('c.whatsapp_status = %s AND c.phone IS NOT NULL', $b['whatsapp_status']);
        if (($b['has'] ?? '') === 'email') $w[] = 'c.email IS NOT NULL';
        if (($b['has'] ?? '') === 'phone') $w[] = 'c.phone IS NOT NULL';
        $tag = $this->clean_tags([$b['tag'] ?? '']);
        if ($tag) $w[] = $wpdb->prepare('c.tags LIKE %s', '%,' . $wpdb->esc_like($tag[0]) . ',%');
        if (!empty($b['source'])) $w[] = $wpdb->prepare('c.source = %s', sanitize_key($b['source']));

        return implode(' AND ', $w);
    }

    public function get_mkt_contacts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);

        $per_page = min(200, max(1, absint($b['per_page'] ?? 20)));
        $paged    = max(1, absint($b['paged'] ?? 1));
        $order    = strtoupper($b['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $where    = $this->contacts_where($b);

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['contacts']} c WHERE $where");
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM {$t['contacts']} c WHERE $where ORDER BY c.id $order LIMIT %d OFFSET %d",
            $per_page,
            ($paged - 1) * $per_page
        ));

        $posts = array_map([$this, 'shape_contact'], (array) $rows);

        /* List names per contact on this page. */
        $ids = array_column($posts, 'id');
        if ($ids) {
            $member = $wpdb->get_results("SELECT m.contact_id, l.id, l.name FROM {$t['members']} m JOIN {$t['lists']} l ON l.id = m.list_id WHERE m.contact_id IN ({$this->in_ids($ids)})");
            $by = [];
            foreach ((array) $member as $m) $by[(int) $m->contact_id][] = ['id' => (int) $m->id, 'name' => $m->name];
            foreach ($posts as &$p) $p['lists'] = $by[$p['id']] ?? [];
            unset($p);
        }

        $this->respond([
            'posts' => $posts,
            'total' => $total,
            'max_pages' => max(1, (int) ceil($total / $per_page)),
        ]);
    }

    public function get_mkt_contact($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $contact = $this->shape_contact($wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE id = %d", $id)));
        if (!$contact) return $this->fail('Contact not found.', 404);

        $contact['list_ids'] = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT list_id FROM {$t['members']} WHERE contact_id = %d", $id)));
        $contact['messages'] = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id, m.campaign_id, c.name campaign_name, m.channel, m.status, m.error, m.sent_at, m.opened_at, m.clicked_at, m.unsubscribed_at
             FROM {$t['messages']} m LEFT JOIN {$t['campaigns']} c ON c.id = m.campaign_id
             WHERE m.contact_id = %d ORDER BY m.id DESC LIMIT 50",
            $id
        ));
        $this->respond($contact);
    }

    /** Body: id (0 = new), email, phone, first_name, last_name, email_status, sms_status, tags, notes, list_ids (replaces membership). */
    public function save_mkt_contact($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $id = absint($b['id'] ?? 0);

        if ($id) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE id = %d", $id));
            if (!$existing) return $this->fail('Contact not found.', 404);

            $email = $this->clean_email($b['email'] ?? '');
            $phone = $this->clean_phone($b['phone'] ?? '');
            if (trim((string) ($b['email'] ?? '')) !== '' && !$email) return $this->fail('That email address is not valid.');
            if (trim((string) ($b['phone'] ?? '')) !== '' && !$phone) return $this->fail('That phone number is not valid.');
            if (!$email && !$phone) return $this->fail('A contact needs an email address or a phone number.');

            foreach (['email' => $email, 'phone' => $phone] as $k => $v) {
                if ($v && $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['contacts']} WHERE $k = %s AND id <> %d", $v, $id))) {
                    return $this->fail("Another contact already has this " . ($k === 'email' ? 'email address' : 'phone number') . '.');
                }
            }

            $now = $this->now();
            $update = [
                'email' => $email,
                'phone' => $phone,
                'first_name' => mb_substr(sanitize_text_field((string) ($b['first_name'] ?? '')), 0, 100),
                'last_name' => mb_substr(sanitize_text_field((string) ($b['last_name'] ?? '')), 0, 100),
                'notes' => mb_substr(sanitize_textarea_field((string) ($b['notes'] ?? '')), 0, 2000),
                'tags' => $this->tags_to_db($this->clean_tags($b['tags'] ?? [])),
                'updated_at' => $now,
            ];
            if (in_array($b['call_status'] ?? '', self::CALL_STATUSES, true)) $update['call_status'] = $b['call_status'];
            /* "Wrong number" belongs to the old number: a new phone makes them callable again. */
            $call_now = $update['call_status'] ?? $existing->call_status;
            if ($call_now === 'wrong_number' && (string) $phone !== (string) $existing->phone) $update['call_status'] = 'allowed';
            foreach (['email_status' => self::EMAIL_STATUSES, 'sms_status' => self::SMS_STATUSES, 'whatsapp_status' => self::WA_STATUSES] as $k => $allowed) {
                $v = $b[$k] ?? '';
                if (!in_array($v, $allowed, true) || $v === $existing->$k) continue;
                $update[$k] = $v;
                $update[str_replace('_status', '_unsubscribed_at', $k)] = $v === 'subscribed' ? null : $now;
            }
            if ($wpdb->update($t['contacts'], $update, ['id' => $id]) === false) return $this->fail('The contact could not be saved.');
        } else {
            $res = $this->upsert_contact($b, ['overwrite' => false, 'source' => 'manual']);
            if (is_wp_error($res)) return $res;
            [$id, $state] = $res;
            if ($state !== 'created') {
                return $this->fail('A contact with this email address or phone number already exists (#' . $id . ').');
            }
        }

        if (isset($b['list_ids']) && is_array($b['list_ids'])) {
            $wpdb->delete($t['members'], ['contact_id' => $id]);
            $this->add_to_lists([$id], $this->ids($b['list_ids'], 100));
        }

        $this->respond(['status' => 'success', 'id' => $id]);
    }

    public function delete_mkt_contacts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $ids = $this->ids($this->body($request)['ids'] ?? []);
        if (!$ids) return $this->fail('Nothing to delete.');

        $in = $this->in_ids($ids);
        $wpdb->query("DELETE FROM {$t['members']} WHERE contact_id IN ($in)");
        /* Queued messages to them are dropped; sent ones stay in campaign reports. */
        $wpdb->query("DELETE FROM {$t['messages']} WHERE contact_id IN ($in) AND status = 'queued'");
        $deleted = (int) $wpdb->query("DELETE FROM {$t['contacts']} WHERE id IN ($in)");

        $this->respond(['status' => 'success', 'deleted' => $deleted]);
    }

    /**
     * Bulk actions on contacts. Body: ids, action:
     * add_to_list / remove_from_list (list_id), add_tag / remove_tag (tag),
     * set_email_status / set_sms_status (status).
     */
    public function bulk_mkt_contacts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $ids = $this->ids($b['ids'] ?? [], 5000);
        if (!$ids) return $this->fail('Select some contacts first.');
        $in  = $this->in_ids($ids);
        $now = $this->now();
        $action = $b['action'] ?? '';
        $n = 0;

        switch ($action) {
            case 'add_to_list':
                $this->add_to_lists($ids, [absint($b['list_id'] ?? 0)]);
                $n = count($ids);
                break;
            case 'remove_from_list':
                $n = (int) $wpdb->query($wpdb->prepare("DELETE FROM {$t['members']} WHERE list_id = %d AND contact_id IN ($in)", absint($b['list_id'] ?? 0)));
                break;
            case 'add_tag':
            case 'remove_tag':
                $tag = $this->clean_tags([$b['tag'] ?? '']);
                if (!$tag) return $this->fail('Enter a tag.');
                foreach ((array) $wpdb->get_results("SELECT id, tags FROM {$t['contacts']} WHERE id IN ($in)") as $row) {
                    $tags = $this->tags_from_db($row->tags);
                    $next = $action === 'add_tag' ? array_values(array_unique(array_merge($tags, $tag))) : array_values(array_diff($tags, $tag));
                    if ($next === $tags) continue;
                    $wpdb->update($t['contacts'], ['tags' => $this->tags_to_db($next), 'updated_at' => $now], ['id' => (int) $row->id]);
                    $n++;
                }
                break;
            case 'set_email_status':
            case 'set_sms_status':
            case 'set_call_status':
                $status = $b['status'] ?? '';
                if (!in_array($status, self::CALL_STATUSES, true)) return $this->fail('Unknown status.');
                $n = (int) $wpdb->query($wpdb->prepare("UPDATE {$t['contacts']} SET call_status = %s, updated_at = %s WHERE id IN ($in) AND call_status <> %s", $status, $now, $status));
                break;
            case 'set_whatsapp_status':
                $col = substr($action, 4);
                $at  = str_replace('_status', '_unsubscribed_at', $col);
                $status = $b['status'] ?? '';
                $allowed = ['email_status' => self::EMAIL_STATUSES, 'sms_status' => self::SMS_STATUSES, 'whatsapp_status' => self::WA_STATUSES][$col];
                if (!in_array($status, $allowed, true)) return $this->fail('Unknown status.');
                $n = (int) $wpdb->query($wpdb->prepare(
                    "UPDATE {$t['contacts']} SET $col = %s, $at = " . ($status === 'subscribed' ? 'NULL' : "'" . esc_sql($now) . "'") . ", updated_at = %s WHERE id IN ($in) AND $col <> %s",
                    $status,
                    $now,
                    $status
                ));
                break;
            default:
                return $this->fail('Unknown action.');
        }

        $this->respond(['status' => 'success', 'affected' => $n]);
    }

    /**
     * Rows from a CSV the dashboard parsed. Body: rows [{email, phone,
     * first_name, last_name, tags, email_status, sms_status}], list_ids,
     * tags (added to every row), overwrite, subscribe (bool, default true:
     * status for rows that do not say). Max 1000 rows per call.
     */
    public function import_mkt_contacts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $b = $this->body($request);
        $rows = is_array($b['rows'] ?? null) ? array_slice($b['rows'], 0, 1000) : [];
        $list_ids = $this->ids($b['list_ids'] ?? [], 100);
        $extra_tags = $this->clean_tags($b['tags'] ?? []);
        $overwrite = !empty($b['overwrite']);
        $default_status = (isset($b['subscribe']) && !$b['subscribe']) ? 'unsubscribed' : 'subscribed';

        $out = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'errors' => []];
        foreach ($rows as $i => $row) {
            if (!is_array($row)) { $out['skipped']++; continue; }
            $row['tags'] = array_merge($this->clean_tags($row['tags'] ?? []), $extra_tags);
            foreach (['email_status', 'sms_status'] as $k) {
                $v = strtolower(trim((string) ($row[$k] ?? '')));
                $row[$k] = in_array($v, ['subscribed', 'unsubscribed', 'bounced'], true) ? $v : (in_array($v, ['no', '0', 'false'], true) ? 'unsubscribed' : $default_status);
            }
            /* WhatsApp only from an explicit column: a general "agreed to marketing" is not WhatsApp consent. */
            $wv = strtolower(trim((string) ($row['whatsapp_status'] ?? '')));
            $row['whatsapp_status'] = in_array($wv, ['subscribed', 'yes', '1', 'true'], true) ? 'subscribed' : (in_array($wv, ['unsubscribed', 'no', '0', 'false'], true) ? 'unsubscribed' : null);
            $res = $this->upsert_contact($row, ['list_ids' => $list_ids, 'overwrite' => $overwrite, 'source' => 'import']);
            if (is_wp_error($res)) {
                $out['skipped']++;
                if (count($out['errors']) < 20) $out['errors'][] = ['row' => (int) ($row['_row'] ?? $i + 1), 'message' => $res->get_error_message()];
                continue;
            }
            $out[$res[1]]++;
        }
        $this->respond($out);
    }

    /**
     * Pull contacts from store data, one page per call. Body: source
     * (customers | orders), offset, list_ids, subscribe (default true).
     * Answers counts and next_offset (null when done).
     */
    public function sync_mkt_contacts($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $b = $this->body($request);
        $source = ($b['source'] ?? 'customers') === 'orders' ? 'orders' : 'customers';
        $offset = absint($b['offset'] ?? 0);
        $limit  = 300;
        $status = (isset($b['subscribe']) && !$b['subscribe']) ? 'unsubscribed' : 'subscribed';
        $opts   = ['list_ids' => $this->ids($b['list_ids'] ?? [], 100), 'overwrite' => false, 'source' => $source === 'orders' ? 'order' : 'customer'];

        $rows = [];
        if ($source === 'customers') {
            $users = get_users(['role__in' => ['customer', 'subscriber'], 'number' => $limit, 'offset' => $offset, 'orderby' => 'ID', 'order' => 'ASC']);
            $total = (int) (new \WP_User_Query(['role__in' => ['customer', 'subscriber'], 'count_total' => true, 'number' => 1, 'fields' => 'ID']))->get_total();
            foreach ($users as $u) {
                $phone = get_user_meta($u->ID, 'mobile', true) ?: get_user_meta($u->ID, 'billing_phone', true) ?: get_user_meta($u->ID, 'phone', true);
                if (!$phone && preg_match('/^(.+)@mo\.lo$/i', $u->user_email, $m)) $phone = $m[1];
                $rows[] = [
                    'email' => $u->user_email,
                    'phone' => $phone,
                    'first_name' => get_user_meta($u->ID, 'first_name', true) ?: $u->display_name,
                    'last_name' => get_user_meta($u->ID, 'last_name', true),
                    'user_id' => $u->ID,
                    'tags' => ['customer'],
                ];
            }
        } else {
            $o = $wpdb->prefix . 'cstore_orders';
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM (SELECT 1 FROM $o WHERE COALESCE(billing_email,'') <> '' OR COALESCE(billing_phone,'') <> '' GROUP BY billing_email, billing_phone) x");
            $found = $wpdb->get_results($wpdb->prepare(
                "SELECT billing_email, billing_phone, MAX(billing_name) billing_name, MAX(userid) userid FROM $o
                 WHERE COALESCE(billing_email,'') <> '' OR COALESCE(billing_phone,'') <> ''
                 GROUP BY billing_email, billing_phone ORDER BY MIN(id) ASC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ));
            foreach ((array) $found as $f) {
                $name = trim((string) $f->billing_name);
                $parts = preg_split('/\s+/', $name, 2);
                $rows[] = [
                    'email' => $f->billing_email,
                    'phone' => $f->billing_phone,
                    'first_name' => $parts[0] ?? '',
                    'last_name' => $parts[1] ?? '',
                    'user_id' => (int) $f->userid ?: null,
                    'tags' => ['buyer'],
                ];
            }
        }

        $out = ['created' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        foreach ($rows as $row) {
            $row['email_status'] = $status;
            $row['sms_status'] = $status;
            $res = $this->upsert_contact($row, $opts);
            if (is_wp_error($res)) { $out['skipped']++; continue; }
            $out[$res[1]]++;
        }
        $next = $offset + $limit;
        $out['total'] = $total;
        $out['next_offset'] = count($rows) === $limit && $next < $total ? $next : null;
        $this->respond($out);
    }

    public function get_mkt_tags($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $counts = [];
        foreach ((array) $wpdb->get_col("SELECT tags FROM {$t['contacts']} WHERE tags <> ''") as $tags) {
            foreach ($this->tags_from_db($tags) as $tag) $counts[$tag] = ($counts[$tag] ?? 0) + 1;
        }
        arsort($counts);
        $out = [];
        foreach ($counts as $tag => $n) $out[] = ['tag' => (string) $tag, 'count' => $n];
        $this->respond(['tags' => $out]);
    }


    /* ---------------------------------------------------------------- */
    /* Lists                                                             */

    public function get_mkt_lists($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $rows = $wpdb->get_results(
            "SELECT l.*,
                COUNT(m.contact_id) contacts,
                SUM(c.email IS NOT NULL AND c.email_status = 'subscribed') email_subscribed,
                SUM(c.phone IS NOT NULL AND c.sms_status = 'subscribed') sms_subscribed,
                SUM(c.phone IS NOT NULL AND c.whatsapp_status = 'subscribed') whatsapp_subscribed,
                SUM(c.phone IS NOT NULL AND c.call_status = 'allowed') callable
             FROM {$t['lists']} l
             LEFT JOIN {$t['members']} m ON m.list_id = l.id
             LEFT JOIN {$t['contacts']} c ON c.id = m.contact_id
             GROUP BY l.id ORDER BY l.name ASC"
        );
        $lists = array_map(function ($r) {
            return [
                'id' => (int) $r->id,
                'name' => $r->name,
                'description' => (string) $r->description,
                'public' => (bool) $r->public,
                'contacts' => (int) $r->contacts,
                'email_subscribed' => (int) $r->email_subscribed,
                'sms_subscribed' => (int) $r->sms_subscribed,
                'whatsapp_subscribed' => (int) $r->whatsapp_subscribed,
                'callable' => (int) $r->callable,
                'created_at' => $r->created_at,
            ];
        }, (array) $rows);
        $this->respond(['lists' => $lists]);
    }

    public function save_mkt_list($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $id = absint($b['id'] ?? 0);
        $name = mb_substr(sanitize_text_field((string) ($b['name'] ?? '')), 0, 150);
        if ($name === '') return $this->fail('Give the list a name.');

        $row = [
            'name' => $name,
            'description' => mb_substr(sanitize_textarea_field((string) ($b['description'] ?? '')), 0, 1000),
            'public' => !empty($b['public']) ? 1 : 0,
            'updated_at' => $this->now(),
        ];
        if ($id) {
            if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['lists']} WHERE id = %d", $id))) return $this->fail('List not found.', 404);
            $wpdb->update($t['lists'], $row, ['id' => $id]);
        } else {
            $row['created_at'] = $row['updated_at'];
            $wpdb->insert($t['lists'], $row);
            $id = (int) $wpdb->insert_id;
        }
        $this->respond(['status' => 'success', 'id' => $id]);
    }

    /** Deletes the list and its memberships, never the contacts. */
    public function delete_mkt_list($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $wpdb->delete($t['members'], ['list_id' => $id]);
        $deleted = (int) $wpdb->delete($t['lists'], ['id' => $id]);
        $this->respond(['status' => $deleted ? 'success' : 'not_found']);
    }


    /* ---------------------------------------------------------------- */
    /* Audience                                                          */

    private function clean_audience($a)
    {
        $a = is_array($a) ? $a : (json_decode((string) $a, true) ?: []);
        return [
            'all' => !empty($a['all']),
            'lists' => $this->ids($a['lists'] ?? [], 100),
            'tags' => $this->clean_tags($a['tags'] ?? []),
            'exclude_lists' => $this->ids($a['exclude_lists'] ?? [], 100),
        ];
    }

    /** SQL WHERE (alias c) for the subscribed contacts a campaign reaches. */
    private function audience_where($channel, array $a)
    {
        global $wpdb;
        $t = $this->tables();
        $w = $channel === 'sms'
            ? ["c.phone IS NOT NULL", "c.sms_status = 'subscribed'"]
            : ($channel === 'whatsapp'
                ? ["c.phone IS NOT NULL", "c.whatsapp_status = 'subscribed'"]
                : ($channel === 'call' || $channel === 'voice'
                    /* A call is not a subscription: anyone with a number not marked "do not call" or "wrong number". */
                    ? ["c.phone IS NOT NULL", "c.call_status = 'allowed'"]
                    : ["c.email IS NOT NULL", "c.email_status = 'subscribed'"]));

        if (!$a['all']) {
            $or = [];
            if ($a['lists']) $or[] = "c.id IN (SELECT contact_id FROM {$t['members']} WHERE list_id IN ({$this->in_ids($a['lists'])}))";
            foreach ($a['tags'] as $tag) $or[] = $wpdb->prepare('c.tags LIKE %s', '%,' . $wpdb->esc_like($tag) . ',%');
            $w[] = $or ? '(' . implode(' OR ', $or) . ')' : '0=1';
        }
        if ($a['exclude_lists']) {
            $w[] = "c.id NOT IN (SELECT contact_id FROM {$t['members']} WHERE list_id IN ({$this->in_ids($a['exclude_lists'])}))";
        }
        return implode(' AND ', $w);
    }

    public function estimate_mkt_audience($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $channel = in_array($b['channel'] ?? '', self::CHANNELS, true) ? $b['channel'] : 'email';
        $where = $this->audience_where($channel, $this->clean_audience($b['audience'] ?? []));
        $this->respond(['count' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['contacts']} c WHERE $where")]);
    }


    /* ---------------------------------------------------------------- */
    /* Campaigns                                                         */

    private function campaign_stats(array $ids)
    {
        global $wpdb;
        $t = $this->tables();
        if (!$ids) return [];
        $rows = $wpdb->get_results(
            "SELECT campaign_id,
                COUNT(*) total,
                SUM(status IN ('queued', 'sending', 'calling')) queued,
                SUM(status = 'sent') sent,
                SUM(status = 'failed') failed,
                SUM(status = 'skipped') skipped,
                SUM(opened_at IS NOT NULL) opened,
                SUM(clicked_at IS NOT NULL) clicked,
                SUM(unsubscribed_at IS NOT NULL) unsubscribed,
                SUM(delivered_at IS NOT NULL) delivered
             FROM {$t['messages']} WHERE campaign_id IN ({$this->in_ids($ids)}) GROUP BY campaign_id"
        );
        $out = [];
        foreach ((array) $rows as $r) {
            $out[(int) $r->campaign_id] = array_map('intval', array_diff_key((array) $r, ['campaign_id' => 1]));
        }
        return $out;
    }

    private function shape_campaign($row, $stats = null, $with_body = false)
    {
        $row = (array) $row;
        $out = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'channel' => $row['channel'],
            'status' => $row['status'],
            'subject' => $row['subject'],
            'preheader' => $row['preheader'],
            'from_name' => $row['from_name'],
            'from_email' => $row['from_email'],
            'reply_to' => $row['reply_to'],
            'audience' => $this->clean_audience($row['audience'] ?? ''),
            'track_opens' => (bool) $row['track_opens'],
            'track_clicks' => (bool) $row['track_clicks'],
            'scheduled_at' => $row['scheduled_at'],
            'started_at' => $row['started_at'],
            'finished_at' => $row['finished_at'],
            'recipients' => (int) $row['recipients'],
            'automation' => (string) ($row['automation'] ?? ''),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'stats' => $stats ?: ['total' => 0, 'queued' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'opened' => 0, 'clicked' => 0, 'unsubscribed' => 0, 'delivered' => 0],
        ];
        if ($with_body) $out['body'] = (string) $row['body'];
        return $out;
    }

    public function get_mkt_campaigns($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $per_page = min(100, max(1, absint($b['per_page'] ?? 20)));
        $paged = max(1, absint($b['paged'] ?? 1));
        $order = strtoupper($b['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        /* Automations have their own place (get_mkt_automation); this lists broadcasts. */
        $w = ["automation = ''"];
        if (in_array($b['channel'] ?? '', self::CHANNELS, true)) $w[] = $wpdb->prepare('channel = %s', $b['channel']);
        if (in_array($b['status'] ?? '', self::CAMPAIGN_STATUSES, true)) $w[] = $wpdb->prepare('status = %s', $b['status']);
        $kw = trim((string) ($b['keyword'] ?? ''));
        if ($kw !== '') {
            $like = '%' . $wpdb->esc_like($kw) . '%';
            $w[] = $wpdb->prepare('(name LIKE %s OR subject LIKE %s)', $like, $like);
        }
        $where = implode(' AND ', $w);

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['campaigns']} WHERE $where");
        $rows = (array) $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$t['campaigns']} WHERE $where ORDER BY id $order LIMIT %d OFFSET %d",
            $per_page,
            ($paged - 1) * $per_page
        ));
        $stats = $this->campaign_stats(array_map(function ($r) { return (int) $r->id; }, $rows));

        $this->respond([
            'posts' => array_map(function ($r) use ($stats) { return $this->shape_campaign($r, $stats[(int) $r->id] ?? null); }, $rows),
            'total' => $total,
            'max_pages' => max(1, (int) ceil($total / $per_page)),
        ]);
    }

    public function get_mkt_campaign($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $id));
        if (!$row) return $this->fail('Campaign not found.', 404);

        $stats = $this->campaign_stats([$id]);
        $out = $this->shape_campaign($row, $stats[$id] ?? null, true);

        if (in_array($row->automation, self::CART_AUTOMATIONS, true)) $out['recovered'] = $this->recovered_stats($id);
        if ($row->channel === 'call') $out['calls'] = $this->call_summary($id);
        if ($row->channel === 'voice' && ComboStoreVoice::$instance) $out['voice'] = ComboStoreVoice::$instance->campaign_summary($id);

        /* Most clicked links (counted by mkt_click). */
        $out['links'] = $this->top_links($id);
        $this->respond($out);
    }

    /** Clicked links are counted in an option per campaign, url => clicks (capped at 200 urls). */
    private function top_links($campaign_id)
    {
        $links = get_option('cstore_mkt_links_' . (int) $campaign_id, []);
        if (!is_array($links)) return [];
        arsort($links);
        $out = [];
        foreach (array_slice($links, 0, 20, true) as $url => $n) $out[] = ['url' => (string) $url, 'clicks' => (int) $n];
        return $out;
    }

    /**
     * Create or update a campaign. Body: id, name, channel, subject,
     * preheader, from_name, from_email, reply_to, body, audience,
     * track_opens, track_clicks, scheduled_at (site time, "Y-m-d H:i" or empty).
     * Only draft, scheduled and paused campaigns can be edited (a paused one
     * keeps its recipients; only the content changes).
     */
    public function save_mkt_campaign($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $id = absint($b['id'] ?? 0);

        $existing = null;
        if ($id) {
            $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $id));
            if (!$existing) return $this->fail('Campaign not found.', 404);
            /* A running call list can still get a better script or more agents (its people are already set). */
            $running_calls = $existing->channel === 'call' && $existing->status === 'sending';
            if ($existing->automation === '' && !$running_calls && !in_array($existing->status, ['draft', 'scheduled', 'paused'], true)) {
                return $this->fail('A campaign that is ' . $existing->status . ' can no longer be edited. Duplicate it instead.');
            }
        }

        $channel = $existing ? $existing->channel : (in_array($b['channel'] ?? '', self::CHANNELS, true) ? $b['channel'] : 'email');
        $name = mb_substr(sanitize_text_field((string) ($b['name'] ?? '')), 0, 190);
        if ($name === '') $name = ['email' => 'Untitled email campaign', 'sms' => 'Untitled SMS campaign', 'whatsapp' => 'Untitled WhatsApp campaign', 'call' => 'Untitled call list', 'voice' => 'Untitled voice call'][$channel] ?? 'Untitled campaign';

        $body = $this->clean_body($channel, $b['body'] ?? '');

        $from_email = sanitize_email((string) ($b['from_email'] ?? ''));
        $reply_to = sanitize_email((string) ($b['reply_to'] ?? ''));

        $row = [
            'name' => $name,
            'subject' => mb_substr(sanitize_text_field((string) ($b['subject'] ?? '')), 0, 255),
            'preheader' => mb_substr(sanitize_text_field((string) ($b['preheader'] ?? '')), 0, 255),
            'from_name' => mb_substr(sanitize_text_field((string) ($b['from_name'] ?? '')), 0, 150),
            'from_email' => is_email($from_email) ? $from_email : '',
            'reply_to' => is_email($reply_to) ? $reply_to : '',
            'body' => $body,
            'audience' => wp_json_encode($this->clean_audience($b['audience'] ?? [])),
            'track_opens' => array_key_exists('track_opens', $b) ? (!empty($b['track_opens']) ? 1 : 0) : 1,
            'track_clicks' => array_key_exists('track_clicks', $b) ? (!empty($b['track_clicks']) ? 1 : 0) : 1,
            'updated_at' => $this->now(),
        ];

        /* Who is on a started list was fixed when it started. */
        if ($existing && $existing->channel === 'call' && $existing->status === 'sending') unset($row['audience']);

        if ($existing && $existing->status === 'scheduled') {
            /* Editing a scheduled campaign may move its time; clearing it makes it a draft again. */
            $at = $this->clean_datetime($b['scheduled_at'] ?? '');
            $row['scheduled_at'] = $at;
            if (!$at) $row['status'] = 'draft';
        }

        if ($existing) {
            $wpdb->update($t['campaigns'], $row, ['id' => $id]);
        } else {
            $row += ['channel' => $channel, 'status' => 'draft', 'created_by' => $auth->ID, 'created_at' => $row['updated_at']];
            if ($wpdb->insert($t['campaigns'], $row) === false) return $this->fail('The campaign could not be created: ' . ($wpdb->last_error ?: 'database error'));
            $id = (int) $wpdb->insert_id;
        }
        $this->respond(['status' => 'success', 'id' => $id]);
    }

    private function clean_datetime($value)
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $ts = strtotime(str_replace('T', ' ', $value));
        return $ts ? gmdate('Y-m-d H:i:s', $ts) : null;
    }

    public function delete_mkt_campaign($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $c = $wpdb->get_row($wpdb->prepare("SELECT status, automation FROM {$t['campaigns']} WHERE id = %d", $id));
        if (!$c) return $this->fail('Campaign not found.', 404);
        if ($c->automation !== '') return $this->fail('An automated email cannot be deleted. Turn it off instead.');
        if ($c->status === 'sending') return $this->fail('Pause or cancel the campaign before deleting it.');

        $wpdb->delete($t['messages'], ['campaign_id' => $id]);
        $wpdb->delete($t['calls'], ['campaign_id' => $id]);
        $wpdb->delete($t['campaigns'], ['id' => $id]);
        delete_option('cstore_mkt_links_' . $id);
        $this->respond(['status' => 'success']);
    }

    public function duplicate_mkt_campaign($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $id), ARRAY_A);
        if (!$row) return $this->fail('Campaign not found.', 404);

        $now = $this->now();
        unset($row['id']);
        $row = array_merge($row, [
            'name' => mb_substr('Copy of ' . $row['name'], 0, 190),
            'status' => 'draft',
            /* A copy of an automated email is an ordinary draft broadcast. */
            'automation' => '',
            'scheduled_at' => null,
            'started_at' => null,
            'finished_at' => null,
            'recipients' => 0,
            'created_by' => $auth->ID,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $wpdb->insert($t['campaigns'], $row);
        $this->respond(['status' => 'success', 'id' => (int) $wpdb->insert_id]);
    }

    /** What a campaign is missing before it can go out, or null. */
    private function campaign_problem($c)
    {
        if ($c->channel === 'email') {
            if (trim($c->subject) === '') return 'Add a subject line.';
            if (trim(wp_strip_all_tags((string) $c->body)) === '' && stripos((string) $c->body, '<img') === false) return 'The email has no content.';
            $from = $c->from_email ?: $this->setting('email.from_email', '');
            if (!is_email($from)) return 'Set a "from" email address on the campaign or in Marketing settings.';
            if ($this->setting('email.mailer', 'smtp') === 'smtp' && trim((string) $this->setting('smtp.host', '')) === '') return 'Set up SMTP in Marketing settings first.';
        } elseif ($c->channel === 'whatsapp') {
            $w = $this->merged_settings()['whatsapp'];
            if ($w['phone_number_id'] === '' || $w['access_token'] === '') return 'Connect WhatsApp in Marketing settings first.';
            $cfg = $this->wa_config($c->body);
            if ($cfg['template'] === '') return 'Choose an approved WhatsApp template.';
            if (in_array($cfg['header']['format'], ['IMAGE', 'VIDEO', 'DOCUMENT'], true) && $cfg['header']['url'] === '') return 'This template needs a header ' . strtolower($cfg['header']['format']) . ': add its link.';
            foreach ($cfg['body_params'] as $i => $v) {
                if (trim($v) === '') return 'Fill in variable {{' . ($i + 1) . '}} of the template.';
            }
        } else {
            if ($c->channel === 'call') return null;
            if ($c->channel === 'voice') return ComboStoreVoice::$instance ? ComboStoreVoice::$instance->broadcast_problem($c) : 'Voice calls are not available.';
            if (trim((string) $c->body) === '') return 'The SMS has no text.';
            $provider = $this->setting('sms.provider', '');
            if (!$provider) return 'Choose an SMS provider in Marketing settings first.';
        }
        return null;
    }

    /**
     * Send now, or schedule. Body: id, scheduled_at (optional, site time).
     * A draft or scheduled campaign is snapshotted now (or when due).
     */
    public function send_mkt_campaign($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $id = absint($b['id'] ?? 0);
        $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $id));
        if (!$c) return $this->fail('Campaign not found.', 404);
        if (!in_array($c->status, ['draft', 'scheduled'], true)) return $this->fail('This campaign has already been sent.');

        $problem = $this->campaign_problem($c);
        if ($problem) return $this->fail($problem);

        $at = $this->clean_datetime($b['scheduled_at'] ?? '');
        if ($at && strtotime($at) > strtotime($this->now()) + 60) {
            $wpdb->update($t['campaigns'], ['status' => 'scheduled', 'scheduled_at' => $at, 'updated_at' => $this->now()], ['id' => $id]);
            $this->respond(['status' => 'scheduled', 'scheduled_at' => $at]);
        }

        $n = $this->enqueue($c);
        if ($n === 0) {
            $wpdb->update($t['campaigns'], ['status' => 'draft', 'updated_at' => $this->now()], ['id' => $id]);
            return $this->fail('Nobody in this audience is subscribed to ' . ['email' => 'email', 'sms' => 'SMS', 'whatsapp' => 'WhatsApp', 'call' => 'phone calls', 'voice' => 'phone calls'][$c->channel] . '.');
        }
        if ($c->channel === 'voice') do_action('cstore_voice_dispatch');
        else $this->process_queue(15);
        $this->respond(['status' => 'sending', 'recipients' => $n]);
    }

    /** Snapshot the audience into the message queue and mark the campaign sending. Answers the recipient count. */
    private function enqueue($c)
    {
        global $wpdb;
        $t = $this->tables();
        $id = (int) $c->id;

        /* Only one caller moves a campaign from draft/scheduled to sending. */
        $claimed = $wpdb->query($wpdb->prepare(
            "UPDATE {$t['campaigns']} SET status = 'sending', started_at = %s, updated_at = %s WHERE id = %d AND status IN ('draft','scheduled')",
            $this->now(),
            $this->now(),
            $id
        ));
        if (!$claimed) return (int) $wpdb->get_var($wpdb->prepare("SELECT recipients FROM {$t['campaigns']} WHERE id = %d", $id));

        $where = $this->audience_where($c->channel, $this->clean_audience($c->audience));
        $col = $c->channel === 'email' ? 'c.email' : 'c.phone';
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$t['messages']} (campaign_id, contact_id, channel, to_addr, status, created_at)
             SELECT %d, c.id, %s, $col, 'queued', %s FROM {$t['contacts']} c WHERE $where",
            $id,
            $c->channel,
            $this->now()
        ));
        $n = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['messages']} WHERE campaign_id = %d", $id));
        $wpdb->update($t['campaigns'], ['recipients' => $n], ['id' => $id]);
        return $n;
    }

    public function pause_mkt_campaign($request)
    {
        return $this->move_status($request, ['sending'], 'paused');
    }

    public function resume_mkt_campaign($request)
    {
        return $this->move_status($request, ['paused'], 'sending');
    }

    /** Cancel stops the rest of the queue (marked skipped) or unschedules. */
    public function cancel_mkt_campaign($request)
    {
        return $this->move_status($request, ['sending', 'paused', 'scheduled'], 'cancelled');
    }

    private function move_status($request, array $from, $to)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $id));
        if (!$c) return $this->fail('Campaign not found.', 404);

        if ($to === 'cancelled' && $c->status === 'scheduled') $to = 'draft';
        if ($to === 'sending' && $c->channel && ($problem = $this->campaign_problem($c))) return $this->fail($problem);

        $done = $wpdb->query($wpdb->prepare(
            "UPDATE {$t['campaigns']} SET status = %s, updated_at = %s" . ($to === 'cancelled' ? ", finished_at = '" . esc_sql($this->now()) . "'" : '') . ($to === 'draft' ? ', scheduled_at = NULL' : '') . "
             WHERE id = %d AND status IN ('" . implode("','", array_map('esc_sql', $from)) . "')",
            $to,
            $this->now(),
            $id
        ));
        if (!$done) return $this->fail('The campaign is ' . $c->status . ' and cannot be changed that way.');

        if ($to === 'cancelled') {
            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET status = 'skipped', error = 'Campaign cancelled' WHERE campaign_id = %d AND status IN ('queued', 'calling')", $id));
        }
        if ($to === 'sending') $this->process_queue(10);
        $this->respond(['status' => $to]);
    }

    public function get_mkt_campaign_messages($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $id = absint($b['campaign_id'] ?? 0);
        $per_page = min(200, max(1, absint($b['per_page'] ?? 20)));
        $paged = max(1, absint($b['paged'] ?? 1));

        $w = [$wpdb->prepare('m.campaign_id = %d', $id)];
        $f = $b['filter'] ?? '';
        if (in_array($f, ['queued', 'sent', 'failed', 'skipped'], true)) $w[] = $wpdb->prepare('m.status = %s', $f);
        if ($f === 'opened') $w[] = 'm.opened_at IS NOT NULL';
        if ($f === 'clicked') $w[] = 'm.clicked_at IS NOT NULL';
        if ($f === 'unsubscribed') $w[] = 'm.unsubscribed_at IS NOT NULL';
        if (strpos($f, 'outcome:') === 0 && isset(self::CALL_OUTCOMES[substr($f, 8)])) $w[] = $wpdb->prepare('m.outcome = %s', substr($f, 8));
        if ($f === 'calling') $w[] = "m.status = 'calling'";
        $kw = trim((string) ($b['keyword'] ?? ''));
        if ($kw !== '') $w[] = $wpdb->prepare('m.to_addr LIKE %s', '%' . $wpdb->esc_like($kw) . '%');
        $where = implode(' AND ', $w);

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['messages']} m WHERE $where");
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, c.first_name, c.last_name, u.display_name agent_name FROM {$t['messages']} m LEFT JOIN {$t['contacts']} c ON c.id = m.contact_id LEFT JOIN {$wpdb->users} u ON u.ID = m.assigned_to
             WHERE $where ORDER BY m.id ASC LIMIT %d OFFSET %d",
            $per_page,
            ($paged - 1) * $per_page
        ));
        $this->respond(['posts' => (array) $rows, 'total' => $total, 'max_pages' => max(1, (int) ceil($total / $per_page))]);
    }

    /**
     * Send one test. Body: either campaign_id (its content) or channel +
     * subject + body (unsaved editor content), and `to` (email or phone).
     * Merge tags are filled from the contact with that address, or samples.
     */
    public function send_mkt_test($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);

        $c = (object) [
            'id' => 0,
            'channel' => in_array($b['channel'] ?? '', self::CHANNELS, true) ? $b['channel'] : 'email',
            'subject' => sanitize_text_field((string) ($b['subject'] ?? 'Test message')),
            'preheader' => sanitize_text_field((string) ($b['preheader'] ?? '')),
            'from_name' => sanitize_text_field((string) ($b['from_name'] ?? '')),
            'from_email' => sanitize_email((string) ($b['from_email'] ?? '')),
            'reply_to' => sanitize_email((string) ($b['reply_to'] ?? '')),
            'body' => (string) ($b['body'] ?? ''),
            'track_opens' => 0,
            'track_clicks' => 0,
        ];
        if (!empty($b['campaign_id'])) {
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", absint($b['campaign_id'])));
            if (!$row) return $this->fail('Campaign not found.', 404);
            if (!array_key_exists('body', $b)) $c = $row;
            $c->channel = $row->channel;
        }
        $c->body = $this->clean_body($c->channel, $c->body);
        if ($c->channel === 'call') return $this->fail('A call list has nothing to send: agents call from the Calls screen.');
        $c->track_opens = 0;
        $c->track_clicks = 0;

        if ($c->channel === 'email') {
            $to = $this->clean_email($b['to'] ?? '');
            if (!$to) return $this->fail('Enter a valid email address to send the test to.');
            $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE email = %s", $to));
        } else {
            $to = $this->clean_phone($b['to'] ?? '');
            if (!$to) return $this->fail('Enter a valid phone number to send the test to.');
            $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE phone = %s", $to));
        }
        $contact = $contact ?: (object) ['id' => 0, 'first_name' => 'Jane', 'last_name' => 'Doe', 'email' => $c->channel === 'email' ? $to : 'jane@example.com', 'phone' => $c->channel !== 'email' ? $to : ''];

        if ($c->channel === 'whatsapp' && ($problem = $this->campaign_problem($c))) return $this->fail($problem);
        if ($c->channel === 'voice') {
            if (!ComboStoreVoice::$instance) return $this->fail('Voice calls are not available.');
            /* A real call to that number now, whatever the calling hours. */
            $result = ComboStoreVoice::$instance->test_broadcast($c, $contact, $to);
            if (!$result['ok']) return $this->fail('Test call not placed: ' . $result['error']);
            $this->respond(['status' => 'success', 'call_id' => $result['call_id']]);
        }
        $result = $c->channel === 'email'
            ? $this->send_email_message($c, $contact, $to, null, true)
            : ($c->channel === 'whatsapp' ? $this->send_whatsapp_message($c, $contact, $to) : $this->send_sms_message($c, $contact, $to, true));

        if (!$result['ok']) return $this->fail('Test not sent: ' . $result['error']);
        $this->respond(['status' => 'success']);
    }

    public function process_mkt_queue($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $sent = $this->process_queue(20);
        global $wpdb;
        /* A voice campaign on screen: place its due calls too, without waiting for WP-cron. */
        $voice_id = absint($this->body($request)['id'] ?? 0);
        if ($voice_id && $wpdb->get_var($wpdb->prepare("SELECT channel FROM {$this->tables()['campaigns']} WHERE id = %d", $voice_id)) === 'voice') do_action('cstore_voice_dispatch');
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $out = ['processed' => $sent];
        if ($id) {
            $out['status'] = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$t['campaigns']} WHERE id = %d", $id));
            $out['stats'] = $this->campaign_stats([$id])[$id] ?? null;
        }
        $this->respond($out);
    }

    public function get_mkt_overview($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $since = gmdate('Y-m-d H:i:s', strtotime($this->now()) - 30 * DAY_IN_SECONDS);

        $contacts = $wpdb->get_row(
            "SELECT COUNT(*) total,
                SUM(email IS NOT NULL) with_email,
                SUM(phone IS NOT NULL) with_phone,
                SUM(email IS NOT NULL AND email_status = 'subscribed') email_subscribed,
                SUM(phone IS NOT NULL AND sms_status = 'subscribed') sms_subscribed,
                SUM(email_status = 'unsubscribed' AND email IS NOT NULL) email_unsubscribed,
                SUM(sms_status = 'unsubscribed' AND phone IS NOT NULL) sms_unsubscribed,
                SUM(email_status = 'bounced') bounced,
                SUM(email IS NOT NULL AND email_status = 'pending') email_pending,
                SUM(phone IS NOT NULL AND whatsapp_status = 'subscribed') whatsapp_subscribed,
                SUM(phone IS NOT NULL AND whatsapp_status = 'unsubscribed') whatsapp_unsubscribed,
                SUM(phone IS NOT NULL AND call_status = 'allowed') callable,
                SUM(phone IS NOT NULL AND call_status = 'do_not_call') do_not_call,
                SUM(phone IS NOT NULL AND call_status = 'wrong_number') wrong_number,
                SUM(created_at >= '" . esc_sql($since) . "') new_30
             FROM {$t['contacts']}",
            ARRAY_A
        );
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT channel, SUM(status = 'sent') sent, SUM(status = 'failed') failed, SUM(opened_at IS NOT NULL) opened, SUM(clicked_at IS NOT NULL) clicked, SUM(unsubscribed_at IS NOT NULL) unsubscribed, SUM(delivered_at IS NOT NULL) delivered
             FROM {$t['messages']} WHERE sent_at >= %s GROUP BY channel",
            $since
        ), ARRAY_A);
        $by_channel = ['email' => null, 'sms' => null, 'whatsapp' => null];
        foreach ((array) $messages as $m) $by_channel[$m['channel']] = array_map('intval', array_diff_key($m, ['channel' => 1]));

        $recent = (array) $wpdb->get_results("SELECT * FROM {$t['campaigns']} WHERE automation = '' ORDER BY COALESCE(started_at, scheduled_at, updated_at) DESC LIMIT 6");
        $stats = $this->campaign_stats(array_map(function ($r) { return (int) $r->id; }, $recent));

        $this->respond([
            'contacts' => array_map('intval', (array) $contacts),
            'lists' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$t['lists']}"),
            'campaigns' => array_map('intval', array_column((array) $wpdb->get_results("SELECT status, COUNT(*) n FROM {$t['campaigns']} WHERE automation = '' GROUP BY status", ARRAY_A), 'n', 'status')),
            'last_30' => $by_channel,
            'recent' => array_map(function ($r) use ($stats) { return $this->shape_campaign($r, $stats[(int) $r->id] ?? null); }, $recent),
            'automations' => $this->automations_summary(),
            'configured' => [
                'email' => $this->setting('email.mailer', 'smtp') === 'wordpress' || trim((string) $this->setting('smtp.host', '')) !== '',
                'sms' => (bool) $this->setting('sms.provider', ''),
                'whatsapp' => $this->setting('whatsapp.phone_number_id', '') !== '' && $this->setting('whatsapp.access_token', '') !== '',
            ],
        ]);
    }


    /* ---------------------------------------------------------------- */
    /* Settings                                                          */

    private function default_settings()
    {
        return [
            'email' => [
                'mailer' => 'smtp',
                'from_name' => get_bloginfo('name'),
                'from_email' => get_option('admin_email'),
                'reply_to' => '',
                'footer' => '',
                'batch_size' => 50,
                /* The storefront's address (the Next app), for links in emails. WordPress's own home_url is the API host. */
                'store_url' => '',
            ],
            'smtp' => ['host' => '', 'port' => 587, 'encryption' => 'tls', 'auth' => true, 'username' => '', 'password' => ''],
            'sms' => ['provider' => '', 'sender_id' => '', 'country_code' => '', 'batch_size' => 50, 'footer' => ''],
            'bulksmsbd' => ['api_key' => ''],
            /* WhatsApp Business Platform (Meta Cloud API). verify_token is ours, made up once, for the webhook. */
            'whatsapp' => ['phone_number_id' => '', 'waba_id' => '', 'access_token' => '', 'app_secret' => '', 'verify_token' => '', 'api_version' => self::WA_API_VERSION, 'batch_size' => 50],
            'twilio' => ['account_sid' => '', 'auth_token' => '', 'from' => '', 'messaging_service_sid' => ''],
            'http' => ['url' => '', 'method' => 'POST', 'params' => "to={to}\nmessage={message}\nsender={sender_id}", 'headers' => '', 'body_format' => 'form', 'success_contains' => ''],
            'contacts' => ['auto_add' => false, 'auto_subscribe_email' => true, 'auto_subscribe_sms' => true, 'auto_list' => 0],
            /* The abandoned-cart automation: wait, who may get it, and how often. */
            'abandoned' => ['delay_minutes' => 60, 'audience' => 'anyone', 'cooldown_days' => 7],
            /* The WhatsApp one. Only shoppers who opted in to WhatsApp, so there is no audience choice. */
            'abandoned_whatsapp' => ['delay_minutes' => 180, 'cooldown_days' => 7],
            /* Double opt-in for storefront email sign-ups (mkt_subscribe). */
            'optin' => [
                'enabled' => true,
                'subject' => 'Please confirm your subscription to {site_name}',
                'body' => '<p>Hi {name},</p>'
                    . '<p>Thanks for signing up for news and offers from {site_name}. Please confirm your email address so we can start sending them:</p>'
                    . '<p><a href="{confirm_url}" style="display:inline-block;background:#1b0e9f;color:#ffffff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px">Confirm my subscription</a></p>'
                    . '<p style="color:#6b7280;font-size:13px">If you did not sign up, ignore this email and you will not hear from us.</p>',
                'redirect_url' => '',
            ],
        ];
    }

    private function merged_settings()
    {
        $defaults = $this->default_settings();
        $saved = $this->settings();
        foreach ($defaults as $group => $values) {
            $defaults[$group] = array_merge($values, is_array($saved[$group] ?? null) ? $saved[$group] : []);
        }
        return $defaults;
    }

    public function get_marketing_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $s = $this->merged_settings();
        foreach (self::SECRETS as $path => $flag) {
            [$g, $k] = explode('.', $path);
            $s[$flag] = (string) ($s[$g][$k] ?? '') !== '';
            $s[$g][$k] = '';
        }
        /* The webhook needs a verify token from the start, so make it on first read. */
        if ($s['whatsapp']['verify_token'] === '') {
            $saved = $this->settings();
            $saved['whatsapp'] = array_merge($this->default_settings()['whatsapp'], is_array($saved['whatsapp'] ?? null) ? $saved['whatsapp'] : []);
            $saved['whatsapp']['verify_token'] = wp_generate_password(32, false, false);
            update_option(self::OPTION, $saved, false);
            $s['whatsapp']['verify_token'] = $saved['whatsapp']['verify_token'];
        }
        $s['public_endpoints'] = [
            'whatsapp_webhook' => rest_url('combo-store/v2/mkt_whatsapp_webhook'),
            'unsubscribe' => rest_url('combo-store/v2/mkt_unsubscribe'),
            'subscribe' => rest_url('combo-store/v2/mkt_subscribe'),
        ];
        $this->respond($s);
    }

    public function update_marketing_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $b = $this->body($request);
        $current = $this->merged_settings();
        $out = $current;

        $text = function ($v, $max = 255) { return mb_substr(sanitize_text_field((string) $v), 0, $max); };
        $in = function ($g) use ($b) { return is_array($b[$g] ?? null) ? $b[$g] : []; };

        $e = $in('email');
        if ($e) {
            $out['email'] = [
                'mailer' => ($e['mailer'] ?? '') === 'wordpress' ? 'wordpress' : 'smtp',
                'from_name' => $text($e['from_name'] ?? ''),
                'from_email' => is_email($e['from_email'] ?? '') ? sanitize_email($e['from_email']) : '',
                'reply_to' => is_email($e['reply_to'] ?? '') ? sanitize_email($e['reply_to']) : '',
                'footer' => wp_kses_post((string) ($e['footer'] ?? '')),
                'batch_size' => min(500, max(1, absint($e['batch_size'] ?? 50))),
                'store_url' => untrailingslashit(esc_url_raw((string) ($e['store_url'] ?? ''))),
            ];
        }
        $smtp = $in('smtp');
        if ($smtp) {
            $out['smtp'] = [
                'host' => $text($smtp['host'] ?? ''),
                'port' => min(65535, max(1, absint($smtp['port'] ?? 587))),
                'encryption' => in_array($smtp['encryption'] ?? '', ['ssl', 'tls', 'none'], true) ? $smtp['encryption'] : 'tls',
                'auth' => !empty($smtp['auth']),
                'username' => $text($smtp['username'] ?? ''),
                'password' => (string) ($smtp['password'] ?? '') !== '' ? (string) $smtp['password'] : $current['smtp']['password'],
            ];
        }
        $sms = $in('sms');
        if ($sms) {
            $out['sms'] = [
                'provider' => in_array($sms['provider'] ?? '', ['bulksmsbd', 'twilio', 'http'], true) ? $sms['provider'] : '',
                'sender_id' => $text($sms['sender_id'] ?? '', 50),
                'country_code' => preg_replace('/\D+/', '', (string) ($sms['country_code'] ?? '')),
                'batch_size' => min(500, max(1, absint($sms['batch_size'] ?? 50))),
                'footer' => mb_substr(sanitize_textarea_field((string) ($sms['footer'] ?? '')), 0, 160),
            ];
        }
        $bulk = $in('bulksmsbd');
        if ($bulk) {
            $out['bulksmsbd'] = ['api_key' => (string) ($bulk['api_key'] ?? '') !== '' ? $text($bulk['api_key']) : $current['bulksmsbd']['api_key']];
        }
        $wa = $in('whatsapp');
        if ($wa) {
            $out['whatsapp'] = [
                'phone_number_id' => preg_replace('/\D+/', '', (string) ($wa['phone_number_id'] ?? '')),
                'waba_id' => preg_replace('/\D+/', '', (string) ($wa['waba_id'] ?? '')),
                'access_token' => (string) ($wa['access_token'] ?? '') !== '' ? preg_replace('/\s+/', '', (string) $wa['access_token']) : $current['whatsapp']['access_token'],
                'app_secret' => (string) ($wa['app_secret'] ?? '') !== '' ? $text($wa['app_secret'], 128) : $current['whatsapp']['app_secret'],
                'verify_token' => $current['whatsapp']['verify_token'] ?: wp_generate_password(32, false, false),
                'api_version' => preg_match('/^v\d{1,2}\.\d$/', (string) ($wa['api_version'] ?? '')) ? $wa['api_version'] : self::WA_API_VERSION,
                'batch_size' => min(500, max(1, absint($wa['batch_size'] ?? 50))),
            ];
        }
        $tw = $in('twilio');
        if ($tw) {
            $out['twilio'] = [
                'account_sid' => $text($tw['account_sid'] ?? '', 64),
                'auth_token' => (string) ($tw['auth_token'] ?? '') !== '' ? $text($tw['auth_token'], 128) : $current['twilio']['auth_token'],
                'from' => $text($tw['from'] ?? '', 32),
                'messaging_service_sid' => $text($tw['messaging_service_sid'] ?? '', 64),
            ];
        }
        $http = $in('http');
        if ($http) {
            $url = esc_url_raw((string) ($http['url'] ?? ''));
            $out['http'] = [
                'url' => $url,
                'method' => strtoupper($http['method'] ?? '') === 'GET' ? 'GET' : 'POST',
                'params' => mb_substr(sanitize_textarea_field((string) ($http['params'] ?? '')), 0, 2000),
                'headers' => mb_substr(sanitize_textarea_field((string) ($http['headers'] ?? '')), 0, 2000),
                'body_format' => ($http['body_format'] ?? '') === 'json' ? 'json' : 'form',
                'success_contains' => $text($http['success_contains'] ?? '', 100),
            ];
        }
        $ct = $in('contacts');
        if ($ct) {
            $out['contacts'] = [
                'auto_add' => !empty($ct['auto_add']),
                'auto_subscribe_email' => !empty($ct['auto_subscribe_email']),
                'auto_subscribe_sms' => !empty($ct['auto_subscribe_sms']),
                'auto_list' => absint($ct['auto_list'] ?? 0),
            ];
        }
        $ab = $in('abandoned');
        if ($ab) {
            $out['abandoned'] = [
                'delay_minutes' => min(4320, max(15, absint($ab['delay_minutes'] ?? 60))),
                'audience' => ($ab['audience'] ?? '') === 'subscribers' ? 'subscribers' : 'anyone',
                'cooldown_days' => min(90, absint($ab['cooldown_days'] ?? 7)),
            ];
        }
        $abw = $in('abandoned_whatsapp');
        if ($abw) {
            $out['abandoned_whatsapp'] = [
                'delay_minutes' => min(4320, max(15, absint($abw['delay_minutes'] ?? 180))),
                'cooldown_days' => min(90, absint($abw['cooldown_days'] ?? 7)),
            ];
        }
        $oi = $in('optin');
        if ($oi) {
            $body = wp_kses_post((string) ($oi['body'] ?? ''));
            $out['optin'] = [
                'enabled' => !empty($oi['enabled']),
                'subject' => $text($oi['subject'] ?? '') ?: $current['optin']['subject'],
                /* Without {confirm_url} nobody could ever confirm; fall back to the default text. */
                'body' => strpos($body, '{confirm_url}') !== false ? $body : $this->default_settings()['optin']['body'],
                'redirect_url' => esc_url_raw((string) ($oi['redirect_url'] ?? '')),
            ];
        }

        update_option(self::OPTION, $out, false);
        $this->respond(['status' => 'success']);
    }


    /* ---------------------------------------------------------------- */
    /* Queue                                                             */

    public function cron_tick()
    {
        global $wpdb;
        $t = $this->tables();
        if ((int) get_option('combo_store_marketing_schema', 0) < self::SCHEMA_VERSION) return;

        /* Scheduled campaigns whose time has come. */
        $due = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE status = 'scheduled' AND scheduled_at <= %s", $this->now()));
        foreach ((array) $due as $c) {
            if ($this->campaign_problem($c)) {
                $wpdb->update($t['campaigns'], ['status' => 'paused', 'updated_at' => $this->now()], ['id' => (int) $c->id]);
                continue;
            }
            if ($this->enqueue($c) === 0) {
                $wpdb->update($t['campaigns'], ['status' => 'sent', 'finished_at' => $this->now()], ['id' => (int) $c->id]);
            }
        }
        $this->process_queue(40);
    }

    /**
     * Send queued messages for sending campaigns, oldest campaign first, for
     * up to $seconds. Answers how many were processed.
     */
    public function process_queue($seconds = 20)
    {
        global $wpdb;
        $t = $this->tables();

        if (!$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', self::LOCK))) return 0;

        $start = microtime(true);
        $processed = 0;
        @set_time_limit(max(30, $seconds + 15));

        try {
            /* Only the lock holder claims messages, so any still marked sending
               belong to a request that died mid-batch: put them back. */
            $wpdb->query("UPDATE {$t['messages']} SET status = 'queued' WHERE status = 'sending'");

            $campaigns = (array) $wpdb->get_results("SELECT * FROM {$t['campaigns']} WHERE channel NOT IN ('call', 'voice') AND (status = 'sending' OR (automation <> '' AND status = 'active')) ORDER BY automation <> '' DESC, started_at ASC, id ASC");
            foreach ($campaigns as $c) {
                $batch = (int) $this->setting(['email' => 'email', 'sms' => 'sms', 'whatsapp' => 'whatsapp'][$c->channel] . '.batch_size', 50);
                $batch = max(1, $batch);

                while (microtime(true) - $start < $seconds) {
                    /* Paused or cancelled from another request. */
                    $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$t['campaigns']} WHERE id = %d", $c->id));
                    $live = $c->automation !== '' ? 'active' : 'sending';
                    if ($status !== $live) break;

                    $rows = (array) $wpdb->get_results($wpdb->prepare(
                        "SELECT m.id, m.to_addr, m.ref_id, c.id contact_id, c.email, c.phone, c.first_name, c.last_name, c.email_status, c.sms_status, c.whatsapp_status
                         FROM {$t['messages']} m LEFT JOIN {$t['contacts']} c ON c.id = m.contact_id
                         WHERE m.campaign_id = %d AND m.status = 'queued' AND (m.send_after IS NULL OR m.send_after <= %s) ORDER BY m.id ASC LIMIT %d",
                        $c->id,
                        $this->now(),
                        $batch
                    ));
                    if (!$rows) {
                        if ($c->automation !== '') break;
                        $wpdb->query($wpdb->prepare("UPDATE {$t['campaigns']} SET status = 'sent', finished_at = %s, updated_at = %s WHERE id = %d AND status = 'sending'", $this->now(), $this->now(), $c->id));
                        break;
                    }
                    $wpdb->query("UPDATE {$t['messages']} SET status = 'sending' WHERE id IN ({$this->in_ids(array_map(function ($r) { return (int) $r->id; }, $rows))})");

                    $mailer = null;
                    foreach ($rows as $r) {
                        $now = $this->now();
                        $allowed = $this->sendable_statuses($c);
                        $still = $c->channel === 'sms'
                            ? ($r->contact_id && $r->sms_status === 'subscribed' && $r->phone)
                            : ($c->channel === 'whatsapp'
                                ? ($r->contact_id && $r->whatsapp_status === 'subscribed' && $r->phone)
                                : ($r->contact_id && in_array($r->email_status, $allowed, true) && $r->email));
                        if (!$still) {
                            $wpdb->update($t['messages'], ['status' => 'skipped', 'error' => 'Unsubscribed or removed before sending'], ['id' => (int) $r->id]);
                            $processed++;
                            continue;
                        }
                        /* Abandoned cart: skip a cart that was bought or emptied meanwhile, and fill in the cart. */
                        if (in_array($c->automation, self::CART_AUTOMATIONS, true)) {
                            $cart = $this->abandoned_cart_context((int) $r->ref_id);
                            if (is_string($cart)) {
                                $wpdb->update($t['messages'], ['status' => 'skipped', 'error' => $cart], ['id' => (int) $r->id]);
                                $processed++;
                                continue;
                            }
                            $r->extra = $cart;
                        }
                        $res = $c->channel === 'sms'
                            ? $this->send_sms_message($c, $r, $r->to_addr)
                            : ($c->channel === 'whatsapp'
                                ? $this->send_whatsapp_message($c, $r, $r->to_addr)
                                : $this->send_email_message($c, $r, $r->to_addr, (int) $r->id, false, $mailer));

                        $wpdb->update($t['messages'], $res['ok']
                            ? ['status' => 'sent', 'sent_at' => $now, 'error' => '', 'provider_ref' => mb_substr((string) ($res['ref'] ?? ''), 0, 190)]
                            : ['status' => 'failed', 'error' => mb_substr((string) $res['error'], 0, 500)], ['id' => (int) $r->id]);
                        if ($res['ok']) $wpdb->update($t['contacts'], ['last_sent_at' => $now], ['id' => (int) $r->contact_id]);
                        $processed++;

                        if (microtime(true) - $start >= $seconds) break;
                    }
                    if ($mailer && method_exists($mailer, 'smtpClose')) $mailer->smtpClose();

                    /* Anything this pass claimed but did not reach goes back to the queue. */
                    $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET status = 'queued' WHERE campaign_id = %d AND status = 'sending'", $c->id));
                }
                if (microtime(true) - $start >= $seconds) break;
            }
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', self::LOCK));
        }
        return $processed;
    }


    /* ---------------------------------------------------------------- */
    /* Rendering                                                         */

    private function sign($value)
    {
        return substr(hash_hmac('sha256', (string) $value, wp_salt('auth') . '|cstore_mkt'), 0, 24);
    }

    private function b64url($s)
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function b64url_decode($s)
    {
        return base64_decode(strtr((string) $s, '-_', '+/'));
    }

    private function unsubscribe_url($message_id)
    {
        if (!$message_id) return add_query_arg(['m' => 0, 's' => 'test'], rest_url('combo-store/v2/mkt_unsubscribe'));
        return add_query_arg(['m' => $message_id, 's' => $this->sign('u' . $message_id)], rest_url('combo-store/v2/mkt_unsubscribe'));
    }

    /** {tag} => value for a contact. Values are plain text. */
    public function merge_values($contact, $message_id)
    {
        $first = trim((string) ($contact->first_name ?? ''));
        $last = trim((string) ($contact->last_name ?? ''));
        return [
            '{first_name}' => $first,
            '{last_name}' => $last,
            '{full_name}' => trim("$first $last"),
            '{name}' => $first !== '' ? $first : 'there',
            '{email}' => (string) ($contact->email ?? ''),
            '{phone}' => (string) ($contact->phone ?? ''),
            '{site_name}' => wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
            '{site_url}' => $this->store_url('/'),
            '{unsubscribe_url}' => $this->unsubscribe_url($message_id),
            '{date}' => wp_date(get_option('date_format')),
            /* Only set on the double opt-in email (send_confirmation). */
            '{confirm_url}' => (string) ($contact->confirm_url ?? ''),
        ] + (isset($contact->extra) && is_array($contact->extra) ? $contact->extra : []);
    }

    /** The storefront's address (settings email.store_url), else WordPress's. */
    private function store_url($path = '/')
    {
        $base = (string) $this->setting('email.store_url', '');
        return $base !== '' ? $base . '/' . ltrim($path, '/') : home_url($path);
    }

    /** {first_name|friend} falls back to "friend" when blank. */
    public function apply_merge($text, array $values, $escape)
    {
        return preg_replace_callback('/\{([a-z_]+)(?:\|([^}]*))?\}/', function ($m) use ($values, $escape) {
            $key = '{' . $m[1] . '}';
            if (!array_key_exists($key, $values)) return $m[0];
            $v = $values[$key];
            if ($v === '' && isset($m[2])) $v = $m[2];
            if (!$escape) return $v;
            /* {cart_items} is markup this class builds (cart_items_html), already escaped. */
            if ($m[1] === 'cart_items') return $v;
            return in_array($m[1], ['unsubscribe_url', 'site_url', 'confirm_url', 'cart_url'], true) ? esc_url($v) : esc_html($v);
        }, (string) $text);
    }

    /** The full HTML email for one recipient. */
    private function render_email($c, $contact, $message_id)
    {
        $values = $this->merge_values($contact, $message_id);
        $body = $this->apply_merge((string) $c->body, $values, true);

        if ($message_id && !empty($c->track_clicks)) {
            $body = preg_replace_callback('/(<a\b[^>]*\bhref\s*=\s*)(["\'])(https?:\/\/[^"\']+)\2/i', function ($m) use ($message_id) {
                $url = html_entity_decode($m[3], ENT_QUOTES);
                if (strpos($url, 'mkt_unsubscribe') !== false) return $m[0];
                $u = $this->b64url($url);
                $href = add_query_arg(['m' => $message_id, 'u' => $u, 's' => $this->sign('c' . $message_id . '|' . $u)], rest_url('combo-store/v2/mkt_click'));
                return $m[1] . $m[2] . esc_url($href) . $m[2];
            }, $body);
        }

        $footer = $this->apply_merge((string) $this->setting('email.footer', ''), $values, true);
        $unsub = esc_url($values['{unsubscribe_url}']);
        /* A transactional email (the opt-in confirmation) goes to someone not yet subscribed: no unsubscribe link. */
        $has_unsub = !empty($c->transactional) || stripos((string) $c->body, '{unsubscribe_url}') !== false;
        $preheader = $this->apply_merge((string) ($c->preheader ?? ''), $values, true);
        $pixel = ($message_id && !empty($c->track_opens))
            ? '<img src="' . esc_url(add_query_arg(['m' => $message_id, 's' => $this->sign('o' . $message_id)], rest_url('combo-store/v2/mkt_open'))) . '" width="1" height="1" alt="" style="display:block;border:0;width:1px;height:1px" />'
            : '';
        $site = esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $subject = esc_html($this->apply_merge((string) $c->subject, $values, false));

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $subject . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2937">'
            . ($preheader !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0">' . $preheader . '</div>' : '')
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7"><tr><td align="center" style="padding:24px 12px">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px"><tr><td style="padding:28px 28px 20px;font-size:15px;line-height:1.6">'
            . $body
            . '</td></tr></table>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px"><tr><td style="padding:16px 28px;font-size:12px;line-height:1.5;color:#6b7280;text-align:center">'
            . ($footer !== '' ? '<div style="margin-bottom:8px">' . $footer . '</div>' : '<div style="margin-bottom:8px">' . $site . '</div>')
            . ($has_unsub ? '' : '<a href="' . $unsub . '" style="color:#6b7280;text-decoration:underline">Unsubscribe</a>')
            . '</td></tr></table>'
            . $pixel
            . '</td></tr></table></body></html>';
    }

    /** The SMS text for one recipient. */
    private function render_sms($c, $contact)
    {
        $text = $this->apply_merge((string) $c->body, $this->merge_values($contact, 0), false);
        $footer = trim((string) $this->setting('sms.footer', ''));
        if ($footer !== '') $text = rtrim($text) . "\n" . $footer;
        return $text;
    }


    /* ---------------------------------------------------------------- */
    /* Transports                                                        */

    /**
     * Send one email. $mailer is a PHPMailer reused across a batch (SMTP
     * keep-alive). Answers ['ok' => bool, 'error' => string].
     */
    private function send_email_message($c, $contact, $to, $message_id, $is_test = false, &$mailer = null)
    {
        $from_email = $c->from_email ?: $this->setting('email.from_email', '') ?: get_option('admin_email');
        $from_name = $c->from_name ?: $this->setting('email.from_name', '') ?: get_bloginfo('name');
        $reply_to = $c->reply_to ?: $this->setting('email.reply_to', '');
        $values = $this->merge_values($contact, $message_id);
        $subject = ($is_test ? '[Test] ' : '') . $this->apply_merge((string) $c->subject, $values, false);
        $html = $this->render_email($c, $contact, $message_id);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", wp_strip_all_tags(preg_replace('/<(br|\/p|\/div|\/h[1-6]|\/li|\/tr)\b[^>]*>/i', "\n", $html))));
        $unsub = $values['{unsubscribe_url}'];

        if ($this->setting('email.mailer', 'smtp') === 'wordpress') {
            $headers = ['Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>'];
            if ($reply_to) $headers[] = 'Reply-To: ' . $reply_to;
            if ($message_id) {
                $headers[] = 'List-Unsubscribe: <' . $unsub . '>';
                $headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
            }
            $error = '';
            $catch = function ($e) use (&$error) { $error = $e->get_error_message(); };
            add_action('wp_mail_failed', $catch);
            $ok = wp_mail($to, $subject, $html, $headers);
            remove_action('wp_mail_failed', $catch);
            return ['ok' => (bool) $ok, 'error' => $ok ? '' : ($error ?: 'wp_mail failed')];
        }

        try {
            if (!$mailer) {
                if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
                    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
                    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
                }
                $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                $smtp = $this->merged_settings()['smtp'];
                $mailer->isSMTP();
                $mailer->Host = $smtp['host'];
                $mailer->Port = (int) $smtp['port'];
                $mailer->SMTPAuth = !empty($smtp['auth']);
                $mailer->Username = $smtp['username'];
                $mailer->Password = $smtp['password'];
                $mailer->SMTPSecure = $smtp['encryption'] === 'none' ? '' : $smtp['encryption'];
                $mailer->SMTPAutoTLS = $smtp['encryption'] !== 'none';
                $mailer->SMTPKeepAlive = true;
                $mailer->Timeout = 20;
                $mailer->CharSet = 'UTF-8';
            }
            $mailer->clearAllRecipients();
            $mailer->clearReplyTos();
            $mailer->clearCustomHeaders();
            $mailer->setFrom($from_email, $from_name, false);
            if ($reply_to) $mailer->addReplyTo($reply_to);
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = $text;
            if ($message_id) {
                $mailer->addCustomHeader('List-Unsubscribe', '<' . $unsub . '>');
                $mailer->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            }
            $mailer->send();
            return ['ok' => true, 'error' => '', 'ref' => $mailer->getLastMessageID()];
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            /* A dropped connection would fail the rest of the batch; start a fresh one. */
            if ($mailer) { try { $mailer->smtpClose(); } catch (\Throwable $_) {} }
            $mailer = null;
            return ['ok' => false, 'error' => $error ?: 'SMTP error'];
        }
    }

    /** Answers ['ok' => bool, 'error' => string, 'ref' => provider id]. */
    private function send_sms_message($c, $contact, $to, $is_test = false)
    {
        $s = $this->merged_settings();
        $text = $this->render_sms($c, $contact);
        $sender = $s['sms']['sender_id'];

        switch ($s['sms']['provider']) {
            case 'bulksmsbd':
                if ($s['bulksmsbd']['api_key'] === '') return ['ok' => false, 'error' => 'BulkSMSBD API key is not set'];
                $res = wp_remote_post('https://bulksmsbd.net/api/smsapi', [
                    'timeout' => 30,
                    'body' => ['api_key' => $s['bulksmsbd']['api_key'], 'type' => 'text', 'number' => ltrim($to, '+'), 'senderid' => $sender, 'message' => $text],
                ]);
                if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];
                $json = json_decode(wp_remote_retrieve_body($res), true);
                $code = (int) ($json['response_code'] ?? 0);
                if ($code === 202) return ['ok' => true, 'error' => '', 'ref' => (string) ($json['message_id'] ?? '')];
                return ['ok' => false, 'error' => trim(($json['error_message'] ?? '') ?: ('BulkSMSBD answered ' . ($code ?: wp_remote_retrieve_response_code($res))))];

            case 'twilio':
                $tw = $s['twilio'];
                if ($tw['account_sid'] === '' || $tw['auth_token'] === '') return ['ok' => false, 'error' => 'Twilio credentials are not set'];
                $body = ['To' => $to, 'Body' => $text];
                if ($tw['messaging_service_sid'] !== '') $body['MessagingServiceSid'] = $tw['messaging_service_sid'];
                else $body['From'] = $tw['from'] ?: $sender;
                $res = wp_remote_post('https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($tw['account_sid']) . '/Messages.json', [
                    'timeout' => 30,
                    'headers' => ['Authorization' => 'Basic ' . base64_encode($tw['account_sid'] . ':' . $tw['auth_token'])],
                    'body' => $body,
                ]);
                if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];
                $json = json_decode(wp_remote_retrieve_body($res), true);
                $code = wp_remote_retrieve_response_code($res);
                if ($code >= 200 && $code < 300) return ['ok' => true, 'error' => '', 'ref' => (string) ($json['sid'] ?? '')];
                return ['ok' => false, 'error' => (string) ($json['message'] ?? 'Twilio answered ' . $code)];

            case 'http':
                $h = $s['http'];
                if ($h['url'] === '') return ['ok' => false, 'error' => 'The SMS API URL is not set'];
                $vars = ['{to}' => $to, '{to_digits}' => ltrim($to, '+'), '{message}' => $text, '{sender_id}' => $sender];
                $params = [];
                foreach (preg_split('/\r?\n/', (string) $h['params']) as $line) {
                    if (strpos($line, '=') === false) continue;
                    [$k, $v] = array_map('trim', explode('=', $line, 2));
                    if ($k !== '') $params[$k] = strtr($v, $vars);
                }
                $headers = [];
                foreach (preg_split('/\r?\n/', (string) $h['headers']) as $line) {
                    if (strpos($line, ':') === false) continue;
                    [$k, $v] = array_map('trim', explode(':', $line, 2));
                    if ($k !== '') $headers[$k] = $v;
                }
                if ($h['method'] === 'GET') {
                    $res = wp_remote_get(add_query_arg(array_map('rawurlencode', $params), $h['url']), ['timeout' => 30, 'headers' => $headers]);
                } else {
                    if ($h['body_format'] === 'json') $headers['Content-Type'] = 'application/json';
                    $res = wp_remote_post($h['url'], ['timeout' => 30, 'headers' => $headers, 'body' => $h['body_format'] === 'json' ? wp_json_encode($params) : $params]);
                }
                if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message()];
                $code = wp_remote_retrieve_response_code($res);
                $body = (string) wp_remote_retrieve_body($res);
                $ok = $code >= 200 && $code < 300 && ($h['success_contains'] === '' || stripos($body, $h['success_contains']) !== false);
                return ['ok' => $ok, 'error' => $ok ? '' : mb_substr('HTTP ' . $code . ': ' . wp_strip_all_tags($body), 0, 300), 'ref' => ''];
        }
        return ['ok' => false, 'error' => 'No SMS provider is set up'];
    }


    /* ---------------------------------------------------------------- */
    /* Public endpoints                                                  */

    private function message($id)
    {
        global $wpdb;
        $t = $this->tables();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE id = %d", absint($id)));
    }

    public function mkt_open($request)
    {
        $id = absint($request['m'] ?? 0);
        if ($id && hash_equals($this->sign('o' . $id), (string) ($request['s'] ?? ''))) {
            global $wpdb;
            $t = $this->tables();
            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET opens = opens + 1, opened_at = COALESCE(opened_at, %s) WHERE id = %d", $this->now(), $id));
        }
        nocache_headers();
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    public function mkt_click($request)
    {
        $id = absint($request['m'] ?? 0);
        $u = (string) ($request['u'] ?? '');
        $url = $this->b64url_decode($u);
        if (!$id || !$url || !hash_equals($this->sign('c' . $id . '|' . $u), (string) ($request['s'] ?? '')) || !preg_match('#^https?://#i', $url)) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
        global $wpdb;
        $t = $this->tables();
        $now = $this->now();
        /* A click proves an open, even with images off. */
        $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET clicks = clicks + 1, clicked_at = COALESCE(clicked_at, %s), opened_at = COALESCE(opened_at, %s) WHERE id = %d", $now, $now, $id));
        $m = $this->message($id);
        if ($m) {
            $key = 'cstore_mkt_links_' . (int) $m->campaign_id;
            $links = get_option($key, []);
            if (!is_array($links)) $links = [];
            $url_key = mb_substr($url, 0, 500);
            if (isset($links[$url_key]) || count($links) < 200) {
                $links[$url_key] = ($links[$url_key] ?? 0) + 1;
                update_option($key, $links, false);
            }
        }
        wp_redirect($url, 302);
        exit;
    }

    /** GET shows a confirmation page; POST (List-Unsubscribe one-click) answers 200. */
    public function mkt_unsubscribe($request)
    {
        $id = absint($request['m'] ?? 0);
        $valid = $id && hash_equals($this->sign('u' . $id), (string) ($request['s'] ?? ''));
        $m = $valid ? $this->message($id) : null;

        if ($m) {
            global $wpdb;
            $t = $this->tables();
            $now = $this->now();
            $col = in_array($m->channel, ['sms', 'whatsapp'], true) ? $m->channel : 'email';
            $wpdb->query($wpdb->prepare("UPDATE {$t['contacts']} SET {$col}_status = 'unsubscribed', {$col}_unsubscribed_at = %s, updated_at = %s WHERE id = %d AND {$col}_status IN ('subscribed','none','pending')", $now, $now, $m->contact_id));
            /* An old opt-in link must not re-subscribe them; signing up again sends a new one. */
            if ($col === 'email') $wpdb->update($t['contacts'], ['email_confirm_token' => ''], ['id' => (int) $m->contact_id]);
            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET unsubscribed_at = COALESCE(unsubscribed_at, %s) WHERE id = %d", $now, $id));
        }

        if ($request->get_method() === 'POST') {
            status_header(200);
            exit;
        }

        $site = esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        $title = $m ? 'You have been unsubscribed' : ($id === 0 && ($request['s'] ?? '') === 'test' ? 'Unsubscribe link (test)' : 'This link is not valid');
        $text = $m
            ? 'You will no longer receive marketing emails from ' . $site . '.'
            : ($id === 0 ? 'This is where recipients land when they unsubscribe. Nothing was changed.' : 'The unsubscribe link is incomplete or has expired.');

        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html($title) . '</title></head>'
            . '<body style="margin:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2937">'
            . '<div style="max-width:460px;margin:80px auto;padding:32px;background:#fff;border-radius:10px;text-align:center">'
            . '<h1 style="font-size:20px;margin:0 0 12px">' . esc_html($title) . '</h1>'
            . '<p style="margin:0 0 20px;color:#4b5563;line-height:1.5">' . $text . '</p>'
            . '<a href="' . esc_url($this->store_url('/')) . '" style="color:#634FE5">Back to ' . $site . '</a>'
            . '</div></body></html>';
        exit;
    }

    /**
     * Storefront sign-up form (the newsletterSignup page-builder element).
     * Body: email and/or phone, name, list_id (must be a public list), tag
     * (one extra tag, e.g. where the form sits), website (honeypot: bots fill
     * it, people never see it).
     *
     * With double opt-in on (settings optin.enabled, the default), an email
     * is not subscribed until its owner clicks the link in a confirmation
     * email: a new address is saved as `pending`; an address that
     * unsubscribed before stays unsubscribed until confirmed, so confirming is
     * also how someone comes back. Already-subscribed and bounced addresses
     * get no email. A phone number is subscribed straight away (there is no
     * SMS confirmation).
     *
     * With it off, a new email is subscribed straight away and someone who
     * unsubscribed is never re-subscribed.
     *
     * Every outcome answers the same way, so the form does not reveal who is
     * on the list: {status: success, confirm: true|false, message}.
     * Limited to 10 sign-ups per IP per hour.
     */
    public function mkt_subscribe($request)
    {
        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request) ?: $request->get_params();
        $optin = !empty($this->merged_settings()['optin']['enabled']);
        $thanks = ['status' => 'success', 'confirm' => false, 'message' => 'Thank you for subscribing.'];

        /* A filled honeypot looks like success to the bot, and saves nothing. */
        if (trim((string) ($b['website'] ?? '')) !== '') $this->respond($thanks);

        $ip = preg_replace('/[^0-9a-f:.]/i', '', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $key = 'cstore_mkt_sub_' . md5($ip);
        $hits = (int) get_transient($key);
        if ($hits >= 10) return $this->fail('Too many sign-ups from your connection. Please try again later.', 429);
        set_transient($key, $hits + 1, HOUR_IN_SECONDS);

        $raw_email = trim((string) ($b['email'] ?? ''));
        $raw_phone = trim((string) ($b['phone'] ?? ''));
        $email = $this->clean_email($raw_email);
        $phone = $this->clean_phone($raw_phone);
        if ($raw_email !== '' && !$email) return $this->fail('Please enter a valid email address.');
        if ($raw_phone !== '' && !$phone) return $this->fail('Please enter a valid phone number.');
        if (!$email && !$phone) return $this->fail('Enter a valid email address or phone number.');
        $extra_tag = $this->clean_tags([(string) ($b['tag'] ?? '')]);

        $list_ids = [];
        if (!empty($b['list_id'])) {
            $list = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['lists']} WHERE id = %d AND public = 1", absint($b['list_id'])));
            if (!$list) return $this->fail('That list does not accept sign-ups.');
            $list_ids[] = (int) $list;
        }

        /* Whether we knew this address before decides what double opt-in does with it. */
        $known = $email ? $wpdb->get_row($wpdb->prepare("SELECT id, email_status FROM {$t['contacts']} WHERE email = %s", $email)) : null;

        $name = trim(sanitize_text_field((string) ($b['name'] ?? '')));
        $parts = preg_split('/\s+/', $name, 2);
        $res = $this->upsert_contact([
            'email' => $email,
            'phone' => $phone,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            /* With opt-in, an existing contact's email status is left for the logic below. */
            'email_status' => $optin ? null : 'subscribed',
            'sms_status' => 'subscribed',
            /* The form's "send me offers on WhatsApp" box: explicit WhatsApp consent. */
            'whatsapp_status' => $phone && !empty($b['whatsapp']) ? 'subscribed' : null,
            'tags' => array_merge(['newsletter'], $extra_tag, $phone && !empty($b['whatsapp']) ? ['whatsapp'] : []),
        ], ['list_ids' => $list_ids, 'overwrite' => false, 'source' => 'form', 'new_email_status' => $optin ? 'pending' : 'subscribed']);
        if (is_wp_error($res)) return $res;
        [$contact_id] = $res;

        if (!$optin || !$email) {
            /* Without confirmation, a newly subscribed email is welcomed straight away. */
            if (!$optin && $email && (!$known || $known->email_status !== 'subscribed')) {
                $now_subscribed = $wpdb->get_var($wpdb->prepare("SELECT email_status FROM {$t['contacts']} WHERE id = %d AND email = %s", $contact_id, $email));
                if ($now_subscribed === 'subscribed') $this->trigger_automation('welcome', $contact_id);
            }
            $this->respond($thanks);
        }

        $pending = ['status' => 'success', 'confirm' => true, 'message' => 'Almost done: check your inbox and click the link to confirm.'];

        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE id = %d", $contact_id));
        if (!$contact || $contact->email !== $email) $this->respond($pending);

        /* A new address (even on a contact we knew by phone) starts pending. */
        if (!$known && $contact->email_status !== 'pending') {
            $wpdb->update($t['contacts'], ['email_status' => 'pending', 'email_unsubscribed_at' => null, 'updated_at' => $this->now()], ['id' => $contact_id]);
            $contact->email_status = 'pending';
        }

        /* A shopper we knew only from checkout ("none") never subscribed: they confirm like anyone new. */
        if ($contact->email_status === 'none') {
            $wpdb->update($t['contacts'], ['email_status' => 'pending', 'updated_at' => $this->now()], ['id' => $contact_id]);
            $contact->email_status = 'pending';
        }

        /* Subscribed already, or a bounced address: nothing to confirm, same answer. */
        if (!in_array($contact->email_status, ['pending', 'unsubscribed'], true)) $this->respond($pending);

        $wpdb->update($t['contacts'], ['consent_ip' => mb_substr($ip, 0, 45)], ['id' => $contact_id]);

        /* One confirmation email per address per 10 minutes, whoever asks. */
        if ($contact->email_confirm_sent_at && strtotime($contact->email_confirm_sent_at) > strtotime($this->now()) - 10 * MINUTE_IN_SECONDS) {
            $this->respond($pending);
        }

        $sent = $this->send_confirmation($contact);
        if (!$sent['ok']) {
            error_log('[ComboStore marketing] opt-in confirmation to contact #' . $contact_id . ' failed: ' . $sent['error']);
            return $this->fail("We couldn't send the confirmation email just now. Please try again later.", 503);
        }
        $this->respond($pending);
    }

    /**
     * Issue a fresh confirmation token for a contact and email the link.
     * Answers ['ok' => bool, 'error' => string].
     */
    private function send_confirmation($contact)
    {
        global $wpdb;
        $t = $this->tables();

        $token = wp_generate_password(40, false, false);
        $wpdb->update($t['contacts'], [
            'email_confirm_token' => hash('sha256', $token),
            'email_confirm_sent_at' => $this->now(),
        ], ['id' => (int) $contact->id]);

        $o = $this->merged_settings()['optin'];
        $contact = clone $contact;
        $contact->confirm_url = add_query_arg(['c' => (int) $contact->id, 't' => $token], rest_url('combo-store/v2/mkt_confirm'));

        $c = (object) [
            'id' => 0,
            'channel' => 'email',
            'subject' => $o['subject'],
            'preheader' => 'Confirm your email address to start receiving our updates.',
            'from_name' => '',
            'from_email' => '',
            'reply_to' => '',
            'body' => $o['body'],
            'track_opens' => 0,
            'track_clicks' => 0,
            'transactional' => true,
        ];
        return $this->send_email_message($c, $contact, $contact->email, null);
    }

    /**
     * The link in the confirmation email. GET shows a page with a button and
     * changes nothing, because mail scanners open links on their own; the
     * button POSTs back here, which confirms. Links last CONFIRM_DAYS.
     * After confirming, optin.redirect_url (if set) is where the browser goes.
     */
    public function mkt_confirm($request)
    {
        global $wpdb;
        $t = $this->tables();
        $id = absint($request['c'] ?? 0);
        $token = (string) ($request['t'] ?? '');
        $site = esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));

        $contact = $id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE id = %d", $id)) : null;
        $valid = $contact && $token !== '' && $contact->email_confirm_token !== ''
            && hash_equals((string) $contact->email_confirm_token, hash('sha256', $token));
        $expired = $valid && strtotime((string) $contact->email_confirm_sent_at) < strtotime($this->now()) - self::CONFIRM_DAYS * DAY_IN_SECONDS;

        /* Only the real link may tell anything about the contact, so a guessed id learns nothing. */
        if (!$valid) {
            $this->public_page('This link is not valid', 'The confirmation link is incomplete or has been replaced by a newer one. Sign up again to get a new one.');
        }
        /* The token is kept after confirming, so opening the link again says so. */
        if ($contact->email_status === 'subscribed') {
            $this->public_page('You are already subscribed', 'This email address already receives updates from ' . $site . '.');
        }
        if ($expired) {
            $this->public_page('This link has expired', 'Confirmation links work for ' . self::CONFIRM_DAYS . ' days. Sign up again to get a new one.');
        }
        if ($contact->email_status === 'bounced') {
            $this->public_page('We could not confirm this address', 'Emails to this address have been bouncing. Please contact ' . $site . '.');
        }

        if ($request->get_method() !== 'POST') {
            $action = esc_url(add_query_arg(['c' => $id, 't' => $token], rest_url('combo-store/v2/mkt_confirm')));
            $this->public_page(
                'Confirm your subscription',
                'Click below to start receiving news and offers from ' . $site . ' at <strong>' . esc_html($contact->email) . '</strong>.',
                '<form method="post" action="' . $action . '" style="margin:0 0 20px"><button type="submit" style="cursor:pointer;border:0;background:#1b0e9f;color:#fff;font-weight:600;font-size:15px;padding:12px 24px;border-radius:8px">Confirm my subscription</button></form>'
            );
        }

        $now = $this->now();
        $wpdb->update($t['contacts'], [
            'email_status' => 'subscribed',
            'email_unsubscribed_at' => null,
            'email_confirmed_at' => $now,
            'updated_at' => $now,
        ], ['id' => $id]);
        do_action('combo_store_marketing_contact_confirmed', $id);
        $this->trigger_automation('welcome', $id);

        $redirect = (string) $this->merged_settings()['optin']['redirect_url'];
        if ($redirect !== '') {
            wp_redirect($redirect, 303);
            exit;
        }
        $this->public_page('You are subscribed', 'Thanks for confirming. You will now receive news and offers from ' . $site . '.');
    }

    /** Send a pending contact a fresh confirmation email. Body: id. */
    public function resend_mkt_confirmation($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['id'] ?? 0);
        $contact = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['contacts']} WHERE id = %d", $id));
        if (!$contact) return $this->fail('Contact not found.', 404);
        if (!$contact->email) return $this->fail('This contact has no email address.');
        if ($contact->email_status !== 'pending') return $this->fail('Only contacts awaiting confirmation can be sent a confirmation email.');

        $sent = $this->send_confirmation($contact);
        if (!$sent['ok']) return $this->fail('Not sent: ' . $sent['error']);
        $this->respond(['status' => 'success']);
    }

    /** A small standalone HTML page for the public links. $text may hold markup; $extra is raw HTML. */
    private function public_page($title, $text, $extra = '')
    {
        $site = esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
        nocache_headers();
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex');
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>' . esc_html($title) . '</title></head>'
            . '<body style="margin:0;background:#f4f4f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2937">'
            . '<div style="max-width:460px;margin:80px auto;padding:32px;background:#fff;border-radius:10px;text-align:center">'
            . '<h1 style="font-size:20px;margin:0 0 12px">' . esc_html($title) . '</h1>'
            . '<p style="margin:0 0 20px;color:#4b5563;line-height:1.5">' . $text . '</p>'
            . $extra
            . '<a href="' . esc_url($this->store_url('/')) . '" style="color:#634FE5">Back to ' . $site . '</a>'
            . '</div></body></html>';
        exit;
    }


    /* ---------------------------------------------------------------- */
    /* Call lists                                                        */

    /**
     * A call list's settings, from its body (JSON): script (HTML talking
     * points), agents (user ids allowed to work it; empty = everyone with a
     * CALL_ROLES role), max_attempts and retry_hours for no answer / busy.
     */
    private function call_config($body)
    {
        $c = is_array($body) ? $body : json_decode((string) $body, true);
        $c = is_array($c) ? $c : [];
        return [
            'script' => wp_kses_post((string) ($c['script'] ?? '')),
            'agents' => $this->ids($c['agents'] ?? [], 200),
            'max_attempts' => min(10, max(1, absint($c['max_attempts'] ?? 3))),
            'retry_hours' => min(168, max(1, absint($c['retry_hours'] ?? 4))),
        ];
    }

    /** The signed-in user if they may work call lists, else a WP_Error. */
    private function call_auth($request)
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
        if (!$user || !array_intersect(self::CALL_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to work call lists', array('status' => 403));
        }
        return $user;
    }

    /** SQL (alias m) for tasks free to take now: waiting and due, or a claim left too long. Callbacks stay with their agent for a day. */
    private function call_available_sql($user_id)
    {
        global $wpdb;
        $now = $this->now();
        $stale = gmdate('Y-m-d H:i:s', strtotime($now) - self::CALL_CLAIM_MINUTES * MINUTE_IN_SECONDS);
        $grace = gmdate('Y-m-d H:i:s', strtotime($now) - DAY_IN_SECONDS);
        return $wpdb->prepare(
            "((m.status = 'queued' AND (m.send_after IS NULL OR m.send_after <= %s)
                AND (m.outcome <> 'callback' OR m.assigned_to IS NULL OR m.assigned_to = %d OR m.send_after <= %s))
              OR (m.status = 'calling' AND m.claimed_at < %s))",
            $now, $user_id, $grace, $stale
        );
    }

    /** Active call lists this user may work. */
    private function call_lists_for($user)
    {
        global $wpdb;
        $t = $this->tables();
        $admin = in_array('administrator', (array) $user->roles, true);
        $out = [];
        foreach ((array) $wpdb->get_results("SELECT * FROM {$t['campaigns']} WHERE channel = 'call' AND status = 'sending' ORDER BY started_at ASC") as $c) {
            $cfg = $this->call_config($c->body);
            if (!$admin && $cfg['agents'] && !in_array((int) $user->ID, $cfg['agents'], true)) continue;
            $c->cfg = $cfg;
            $out[] = $c;
        }
        return $out;
    }

    /** Admin: who can be put on a call list. */
    public function get_mkt_call_agents($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        $users = get_users(['role__in' => self::CALL_ROLES, 'orderby' => 'display_name', 'number' => 500]);
        $this->respond(['agents' => array_map(function ($u) {
            return ['id' => (int) $u->ID, 'name' => $u->display_name ?: $u->user_login, 'roles' => array_values((array) $u->roles)];
        }, $users)]);
    }

    /**
     * Agents: the call lists they may work, with what is left, their own
     * callbacks due, what they finished today, and any call they have open.
     */
    public function get_mkt_call_lists($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $t = $this->tables();
        $me = (int) $user->ID;
        $today = substr($this->now(), 0, 10) . ' 00:00:00';
        $avail = $this->call_available_sql($me);

        $lists = [];
        foreach ($this->call_lists_for($user) as $c) {
            $id = (int) $c->id;
            $row = $wpdb->get_row($wpdb->prepare(
                "SELECT COUNT(*) total,
                    SUM(m.status = 'sent') done,
                    SUM($avail) available,
                    SUM(m.status = 'queued' AND m.send_after > %s) later,
                    SUM(m.status = 'queued' AND m.outcome = 'callback' AND m.assigned_to = %d AND m.send_after <= %s) my_callbacks_due,
                    SUM(m.status = 'sent' AND m.assigned_to = %d AND m.sent_at >= %s) my_done_today,
                    SUM(m.status = 'calling' AND m.assigned_to = %d) my_open
                 FROM {$t['messages']} m WHERE m.campaign_id = %d",
                $this->now(), $me, $this->now(), $me, $today, $me, $id
            ), ARRAY_A);
            $lists[] = ['id' => $id, 'name' => $c->name, 'started_at' => $c->started_at] + array_map('intval', (array) $row);
        }

        /* Callbacks this agent booked that are coming up, across lists. */
        $callbacks = $wpdb->get_results($wpdb->prepare(
            "SELECT m.id, m.campaign_id, k.name campaign_name, m.send_after due, m.notes, c.first_name, c.last_name, c.phone
             FROM {$t['messages']} m JOIN {$t['campaigns']} k ON k.id = m.campaign_id LEFT JOIN {$t['contacts']} c ON c.id = m.contact_id
             WHERE m.channel = 'call' AND m.status = 'queued' AND m.outcome = 'callback' AND m.assigned_to = %d AND k.status = 'sending'
             ORDER BY m.send_after ASC LIMIT 50",
            $me
        ));

        $this->respond(['lists' => $lists, 'callbacks' => (array) $callbacks, 'outcomes' => self::CALL_OUTCOMES]);
    }

    /**
     * Agents: take the next contact to call on a list. Body: campaign_id,
     * task_id (optional: a callback of theirs, or their own open call).
     * Resumes their open call first, then their callbacks due, then the
     * oldest free task. Claimed with a conditional UPDATE, so two agents
     * never get the same person.
     */
    public function mkt_call_next($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $me = (int) $user->ID;
        $cid = absint($b['campaign_id'] ?? 0);

        $list = null;
        foreach ($this->call_lists_for($user) as $c) if ((int) $c->id === $cid) $list = $c;
        if (!$list) {
            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$t['campaigns']} WHERE id = %d AND channel = 'call'", $cid));
            if ($status === 'sent') $this->respond(['status' => 'empty', 'message' => 'This call list is finished: everyone on it has been called.']);
            if ($status === 'paused') return $this->fail('This call list is paused.', 409);
            return $this->fail('This call list is not running or not assigned to you.', 404);
        }

        $now = $this->now();
        $avail = $this->call_available_sql($me);

        /* Their own open call comes back first, so a reload does not lose it. */
        $open = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['messages']} m WHERE m.campaign_id = %d AND m.status = 'calling' AND m.assigned_to = %d ORDER BY m.claimed_at DESC LIMIT 1", $cid, $me));
        $task_id = $open ? (int) $open : 0;

        for ($try = 0; !$task_id && $try < 5; $try++) {
            $want = absint($b['task_id'] ?? 0);
            $candidates = $want && $try === 0
                ? [$want]
                : $wpdb->get_col($wpdb->prepare(
                    "SELECT m.id FROM {$t['messages']} m WHERE m.campaign_id = %d AND $avail
                     ORDER BY (m.outcome = 'callback' AND m.assigned_to = %d) DESC, m.send_after IS NULL DESC, m.send_after ASC, m.id ASC LIMIT 5",
                    $cid, $me
                ));
            if (!$candidates) break;
            foreach ($candidates as $cand) {
                $won = $wpdb->query($wpdb->prepare(
                    "UPDATE {$t['messages']} m SET m.status = 'calling', m.assigned_to = %d, m.claimed_at = %s WHERE m.id = %d AND m.campaign_id = %d AND $avail",
                    $me, $now, (int) $cand, $cid
                ));
                if (!$won) continue;
                /* Someone who said "do not call" since the list was made is skipped, not called. */
                $dnc = $wpdb->get_var($wpdb->prepare("SELECT c.call_status FROM {$t['messages']} m JOIN {$t['contacts']} c ON c.id = m.contact_id WHERE m.id = %d", (int) $cand));
                if ($dnc !== 'allowed') {
                    $wpdb->update($t['messages'], ['status' => 'skipped', 'error' => $dnc === 'do_not_call' ? 'Asked not to be called' : ($dnc === 'wrong_number' ? 'Wrong number' : 'Contact removed')], ['id' => (int) $cand]);
                    continue;
                }
                $task_id = (int) $cand;
                break;
            }
        }

        if (!$task_id) {
            $this->finish_call_list($cid);
            $this->respond(['status' => 'empty', 'message' => 'Nobody left to call right now on this list.']);
        }
        $this->respond(['status' => 'success', 'task' => $this->call_task($task_id, $list)]);
    }

    /** Everything an agent needs on screen for one call. */
    private function call_task($task_id, $list)
    {
        global $wpdb;
        $t = $this->tables();
        $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE id = %d", $task_id));
        $c = $wpdb->get_row($wpdb->prepare("SELECT id, first_name, last_name, email, phone, tags, notes, email_status, whatsapp_status, created_at FROM {$t['contacts']} WHERE id = %d", $m->contact_id));

        $lists = $wpdb->get_col($wpdb->prepare("SELECT l.name FROM {$t['members']} x JOIN {$t['lists']} l ON l.id = x.list_id WHERE x.contact_id = %d", $m->contact_id));

        /* Earlier calls to this person, on any list. */
        $history = $wpdb->get_results($wpdb->prepare(
            "SELECT m.sent_at, m.outcome, m.notes, m.attempts, k.name campaign_name, u.display_name agent_name
             FROM {$t['messages']} m JOIN {$t['campaigns']} k ON k.id = m.campaign_id LEFT JOIN {$wpdb->users} u ON u.ID = m.assigned_to
             WHERE m.contact_id = %d AND m.channel = 'call' AND m.outcome <> '' ORDER BY COALESCE(m.sent_at, m.created_at) DESC LIMIT 10",
            $m->contact_id
        ));

        /* What they have bought: by email, or by phone (last 10 digits). */
        $orders = $wpdb->prefix . 'cstore_orders';
        $who = [];
        if (!empty($c->email)) $who[] = $wpdb->prepare('LOWER(billing_email) = %s', strtolower($c->email));
        $digits = substr(preg_replace('/\D+/', '', (string) ($c->phone ?? '')), -10);
        if (strlen($digits) >= 6) $who[] = $wpdb->prepare($this->phone_tail_sql('billing_phone') . ' = %s', $digits);
        $order_summary = ['count' => 0, 'total' => 0, 'last' => null, 'recent' => []];
        if ($who) {
            $where = '(' . implode(' OR ', $who) . ') AND ' . self::PLACED_SQL;
            $agg = $wpdb->get_row("SELECT COUNT(*) n, COALESCE(SUM(total_amount), 0) total, MAX(created_at) last, MAX(currency) currency FROM $orders WHERE $where");
            $order_summary = [
                'count' => (int) $agg->n,
                'total' => $this->money((float) $agg->total, (string) $agg->currency),
                'last' => $agg->last,
                'recent' => $wpdb->get_results("SELECT id, status, total_amount, currency, created_at FROM $orders WHERE $where ORDER BY id DESC LIMIT 5"),
            ];
        }

        return [
            'id' => (int) $m->id,
            'campaign_id' => (int) $m->campaign_id,
            'campaign_name' => $list->name,
            /* Merge tags filled for this person ({first_name|there}, {site_name}...), escaped into the HTML. */
            'script' => $c ? $this->apply_merge($list->cfg['script'], $this->merge_values($c, 0), true) : $list->cfg['script'],
            'max_attempts' => $list->cfg['max_attempts'],
            'attempts' => (int) $m->attempts,
            'outcome' => $m->outcome,
            'notes' => (string) $m->notes,
            'due' => $m->send_after,
            'contact' => $c ? [
                'id' => (int) $c->id,
                'name' => trim($c->first_name . ' ' . $c->last_name),
                'phone' => $c->phone,
                'email' => $c->email,
                'tags' => $this->tags_from_db($c->tags),
                'lists' => $lists,
                'notes' => (string) $c->notes,
                'whatsapp' => $c->whatsapp_status === 'subscribed',
                'since' => $c->created_at,
            ] : null,
            'history' => (array) $history,
            'orders' => $order_summary,
        ];
    }

    /**
     * Agents: record a call. Body: task_id, outcome (CALL_OUTCOMES), notes,
     * callback_at (for "callback", store time), contact_notes (optional,
     * replaces the contact's notes). Then the task is done, or back on the
     * list for a retry or the booked callback.
     */
    public function mkt_call_log($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $me = (int) $user->ID;
        $m = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['messages']} WHERE id = %d AND channel = 'call'", absint($b['task_id'] ?? 0)));
        if (!$m) return $this->fail('Call not found.', 404);
        if ($m->status !== 'calling' || (int) $m->assigned_to !== $me) return $this->fail('This call is not open for you any more (it may have timed out and gone to someone else).', 409);

        $outcome = (string) ($b['outcome'] ?? '');
        if (!isset(self::CALL_OUTCOMES[$outcome])) return $this->fail('Choose how the call went.');
        $kind = self::CALL_OUTCOMES[$outcome]['kind'];

        $campaign = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", $m->campaign_id));
        $cfg = $this->call_config($campaign->body ?? '');
        $now = $this->now();
        $attempts = (int) $m->attempts + 1;

        /* Notes build up across attempts, newest first, each with when and who. */
        $note = trim(mb_substr(sanitize_textarea_field((string) ($b['notes'] ?? '')), 0, 2000));
        $line = mb_substr($now, 0, 16) . ' · ' . ($user->display_name ?: $user->user_login) . ' · ' . self::CALL_OUTCOMES[$outcome]['label'] . ($note !== '' ? ': ' . $note : '');
        $notes = trim($line . "
" . (string) $m->notes);

        $update = ['outcome' => $outcome, 'notes' => mb_substr($notes, 0, 10000), 'attempts' => $attempts, 'sent_at' => $now, 'claimed_at' => null];

        if ($kind === 'callback') {
            $at = $this->clean_datetime($b['callback_at'] ?? '');
            if (!$at || strtotime($at) <= strtotime($now)) return $this->fail('Choose when to call back (a time in the future).');
            $update += ['status' => 'queued', 'send_after' => $at, 'assigned_to' => $me];
        } elseif ($kind === 'retry' && $attempts < $cfg['max_attempts']) {
            $update += ['status' => 'queued', 'send_after' => gmdate('Y-m-d H:i:s', strtotime($now) + $cfg['retry_hours'] * HOUR_IN_SECONDS), 'assigned_to' => null];
        } else {
            /* Done. A retry outcome on the last attempt ends it as unreachable. */
            $update += ['status' => 'sent', 'send_after' => null, 'error' => $kind === 'retry' ? 'Unreachable after ' . $attempts . ' attempts' : ''];
        }
        $wpdb->update($t['messages'], $update, ['id' => (int) $m->id]);
        $wpdb->insert($t['calls'], ['campaign_id' => (int) $m->campaign_id, 'message_id' => (int) $m->id, 'contact_id' => (int) $m->contact_id, 'agent_id' => $me, 'outcome' => $outcome, 'created_at' => $now]);

        $contact_update = ['last_sent_at' => $now, 'updated_at' => $now];
        if ($outcome === 'do_not_call' || $outcome === 'wrong_number') $contact_update['call_status'] = $outcome;
        if (array_key_exists('contact_notes', $b)) $contact_update['notes'] = mb_substr(sanitize_textarea_field((string) $b['contact_notes']), 0, 2000);
        $wpdb->update($t['contacts'], $contact_update, ['id' => (int) $m->contact_id]);

        $this->finish_call_list((int) $m->campaign_id);
        $this->respond(['status' => 'success', 'task_status' => $update['status']]);
    }

    /** Agents: put an open call back without an outcome (no attempt counted). Body: task_id. */
    public function mkt_call_release($request)
    {
        $user = $this->call_auth($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $t = $this->tables();
        $id = absint($this->body($request)['task_id'] ?? 0);
        /* Stamped with now so a skipped contact goes behind the never-tried ones
           (the queue serves send_after NULL first); a callback keeps its time. */
        $done = $wpdb->query($wpdb->prepare(
            "UPDATE {$t['messages']} SET status = 'queued', claimed_at = NULL, assigned_to = IF(outcome = 'callback', assigned_to, NULL),
                send_after = IF(outcome = 'callback', send_after, %s) WHERE id = %d AND status = 'calling' AND assigned_to = %d",
            $this->now(), $id, (int) $user->ID
        ));
        $this->respond(['status' => $done ? 'success' : 'not_open']);
    }

    /** A call list with nothing waiting, open or booked is finished. */
    private function finish_call_list($campaign_id)
    {
        global $wpdb;
        $t = $this->tables();
        $left = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['messages']} WHERE campaign_id = %d AND status IN ('queued', 'calling')", $campaign_id));
        if ($left === 0) {
            $wpdb->query($wpdb->prepare("UPDATE {$t['campaigns']} SET status = 'sent', finished_at = %s, updated_at = %s WHERE id = %d AND channel = 'call' AND status = 'sending'", $this->now(), $this->now(), $campaign_id));
        }
    }

    /** For a call list's report: counts by outcome, and per agent. */
    private function call_summary($campaign_id)
    {
        global $wpdb;
        $t = $this->tables();
        $by_outcome = [];
        foreach ((array) $wpdb->get_results($wpdb->prepare("SELECT outcome, COUNT(*) n FROM {$t['messages']} WHERE campaign_id = %d AND outcome <> '' AND status = 'sent' GROUP BY outcome", $campaign_id)) as $r) {
            $by_outcome[$r->outcome] = (int) $r->n;
        }
        /* Calls and positives per agent come from the call log (every attempt, by
           whoever made it); done and callbacks from the tasks they closed or hold. */
        $agents = [];
        $agent = function ($id) use (&$agents) {
            if (!isset($agents[$id])) {
                $u = get_userdata($id);
                $agents[$id] = ['id' => $id, 'name' => $u ? ($u->display_name ?: $u->user_login) : '#' . $id, 'done' => 0, 'positive' => 0, 'calls' => 0, 'callbacks' => 0];
            }
            return $id;
        };
        foreach ((array) $wpdb->get_results($wpdb->prepare(
            "SELECT agent_id, COUNT(*) calls, SUM(outcome IN ('interested', 'ordered')) positive FROM {$t['calls']} WHERE campaign_id = %d GROUP BY agent_id",
            $campaign_id
        )) as $r) {
            $id = $agent((int) $r->agent_id);
            $agents[$id]['calls'] = (int) $r->calls;
            $agents[$id]['positive'] = (int) $r->positive;
        }
        foreach ((array) $wpdb->get_results($wpdb->prepare(
            "SELECT assigned_to, SUM(status = 'sent') done, SUM(status = 'queued' AND outcome = 'callback') callbacks
             FROM {$t['messages']} WHERE campaign_id = %d AND assigned_to IS NOT NULL GROUP BY assigned_to",
            $campaign_id
        )) as $r) {
            $id = $agent((int) $r->assigned_to);
            $agents[$id]['done'] = (int) $r->done;
            $agents[$id]['callbacks'] = (int) $r->callbacks;
        }
        $agents = array_values(array_filter($agents, function ($a) { return $a['calls'] || $a['done'] || $a['callbacks']; }));
        usort($agents, function ($a, $b) { return $b['calls'] <=> $a['calls'] ?: $b['done'] <=> $a['done']; });
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT SUM(attempts) calls, SUM(attempts > 0) reached, SUM(status = 'calling') open_now, SUM(status = 'queued' AND outcome = 'callback') callbacks, SUM(status = 'queued' AND outcome IN ('no_answer', 'busy')) retrying
             FROM {$t['messages']} WHERE campaign_id = %d",
            $campaign_id
        ), ARRAY_A);
        return [
            'outcomes' => $by_outcome,
            'labels' => array_map(function ($o) { return $o['label']; }, self::CALL_OUTCOMES),
            'agents' => $agents,
            'calls' => (int) ($row['calls'] ?? 0),
            'reached' => (int) ($row['reached'] ?? 0),
            'open_now' => (int) ($row['open_now'] ?? 0),
            'callbacks' => (int) ($row['callbacks'] ?? 0),
            'retrying' => (int) ($row['retrying'] ?? 0),
        ];
    }


    /* ---------------------------------------------------------------- */
    /* WhatsApp                                                          */

    /** A campaign body for its channel: HTML for email, text for SMS, template JSON for WhatsApp. */
    private function clean_body($channel, $body)
    {
        if ($channel === 'email') return wp_kses_post((string) $body);
        if ($channel === 'whatsapp') return wp_json_encode($this->wa_config($body));
        if ($channel === 'call') return wp_json_encode($this->call_config($body));
        if ($channel === 'voice') return wp_json_encode(ComboStoreVoice::broadcast_config($body));
        return mb_substr(sanitize_textarea_field((string) $body), 0, 1600);
    }

    /**
     * A WhatsApp campaign's settings, from its body (JSON):
     *  template, language   - an approved template's name and language code;
     *  header {format, url, text} - format from the template (TEXT / IMAGE /
     *                         VIDEO / DOCUMENT / ''); url is the media link;
     *                         text fills a TEXT header's one variable;
     *  body_params []       - {{1}}, {{2}}... values; may hold merge tags;
     *  button_params []     - by button index: the dynamic part of a URL button;
     *  preview {header, body, footer, buttons} - the template's text, kept for
     *                         the editor's preview and the report.
     */
    private function wa_config($body)
    {
        $c = is_array($body) ? $body : json_decode((string) $body, true);
        $c = is_array($c) ? $c : [];
        $text = function ($v, $max = 1024) { return mb_substr(sanitize_textarea_field((string) $v), 0, $max); };
        $params = function ($list) use ($text) {
            return array_slice(array_map($text, is_array($list) ? array_values($list) : []), 0, 20);
        };
        $h = is_array($c['header'] ?? null) ? $c['header'] : [];
        $format = strtoupper((string) ($h['format'] ?? ''));
        $pv = is_array($c['preview'] ?? null) ? $c['preview'] : [];
        $buttons = [];
        foreach (array_slice(is_array($pv['buttons'] ?? null) ? $pv['buttons'] : [], 0, 10) as $btn) {
            $buttons[] = ['type' => sanitize_key($btn['type'] ?? ''), 'text' => $text($btn['text'] ?? '', 60), 'url' => esc_url_raw((string) ($btn['url'] ?? ''))];
        }
        return [
            'template' => preg_match('/^[a-z0-9_]{1,512}$/', (string) ($c['template'] ?? '')) ? $c['template'] : '',
            'language' => preg_match('/^[A-Za-z]{2,3}(_[A-Za-z]{2,4})?$/', (string) ($c['language'] ?? '')) ? $c['language'] : 'en_US',
            'category' => sanitize_key($c['category'] ?? ''),
            'header' => [
                'format' => in_array($format, ['TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'], true) ? $format : '',
                /* A link, or a merge tag with an optional fallback link: {cart_image|https://...}. */
                'url' => preg_match('/^\{[a-z_]+(\|https?:\/\/[^\s{}]+)?\}$/', trim((string) ($h['url'] ?? ''))) ? trim((string) $h['url']) : esc_url_raw((string) ($h['url'] ?? '')),
                'text' => $text($h['text'] ?? '', 60),
            ],
            'body_params' => $params($c['body_params'] ?? []),
            'button_params' => $params($c['button_params'] ?? []),
            'preview' => [
                'header' => $text($pv['header'] ?? '', 60),
                'body' => $text($pv['body'] ?? ''),
                'footer' => $text($pv['footer'] ?? '', 60),
                'buttons' => $buttons,
            ],
        ];
    }

    /* Public: ComboStoreInbox sends replies and fetches media with it. */
    public function wa_api($path, $method = 'GET', $body = null)
    {
        $w = $this->merged_settings()['whatsapp'];
        $args = [
            'timeout' => 30,
            'method' => $method,
            'headers' => ['Authorization' => 'Bearer ' . $w['access_token'], 'Content-Type' => 'application/json'],
        ];
        if ($body !== null) $args['body'] = wp_json_encode($body);
        $res = wp_remote_request('https://graph.facebook.com/' . $w['api_version'] . '/' . ltrim($path, '/'), $args);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message(), 'data' => null];
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $code = wp_remote_retrieve_response_code($res);
        if ($code >= 200 && $code < 300) return ['ok' => true, 'error' => '', 'data' => $data];
        $err = $data['error'] ?? [];
        $msg = trim(($err['error_user_msg'] ?? '') ?: ($err['error_data']['details'] ?? '') ?: ($err['message'] ?? ('WhatsApp answered ' . $code)));
        return ['ok' => false, 'error' => $msg, 'data' => $data];
    }

    /** International digits for WhatsApp ("8801711000000"), or null when the number has no country code. */
    public function wa_number($phone)
    {
        $phone = trim((string) $phone);
        if (strpos($phone, '+') === 0) return substr(preg_replace('/\D+/', '', $phone), 0) ?: null;
        $cc = preg_replace('/\D+/', '', (string) $this->setting('sms.country_code', ''));
        $digits = preg_replace('/\D+/', '', $phone);
        if ($cc !== '' && strpos($digits, '0') === 0) return $cc . substr($digits, 1);
        if ($cc !== '' && strpos($digits, $cc) === 0) return $digits;
        return null;
    }

    /**
     * Send one WhatsApp template message. Merge tags in the variables are
     * filled per contact; WhatsApp refuses an empty variable, so a blank one
     * becomes "-" (use a fallback, {first_name|there}). Answers ['ok', 'error', 'ref' => wamid].
     */
    private function send_whatsapp_message($c, $contact, $to)
    {
        $cfg = $this->wa_config($c->body);
        $number = $this->wa_number($to);
        if (!$number) return ['ok' => false, 'error' => 'The phone number has no country code. Set a default country code in Marketing settings > SMS.'];

        $values = $this->merge_values($contact, 0);
        $fill = function ($v) use ($values) {
            $out = trim(preg_replace('/\s*\n\s*/', ' ', $this->apply_merge($v, $values, false)));
            return $out === '' ? '-' : mb_substr($out, 0, 1024);
        };

        $components = [];
        $h = $cfg['header'];
        if (in_array($h['format'], ['IMAGE', 'VIDEO', 'DOCUMENT'], true) && $h['url'] !== '') {
            $kind = strtolower($h['format']);
            $link = esc_url_raw($this->apply_merge($h['url'], $values, false));
            if ($link === '') return ['ok' => false, 'error' => 'The header ' . $kind . ' link came out empty (add a fallback link to the merge tag).'];
            $components[] = ['type' => 'header', 'parameters' => [['type' => $kind, $kind => ['link' => $link]]]];
        } elseif ($h['format'] === 'TEXT' && $h['text'] !== '') {
            $components[] = ['type' => 'header', 'parameters' => [['type' => 'text', 'text' => $fill($h['text'])]]];
        }
        if ($cfg['body_params']) {
            $components[] = ['type' => 'body', 'parameters' => array_map(function ($v) use ($fill) { return ['type' => 'text', 'text' => $fill($v)]; }, $cfg['body_params'])];
        }
        foreach ($cfg['button_params'] as $i => $v) {
            if (trim($v) === '') continue;
            $components[] = ['type' => 'button', 'sub_type' => 'url', 'index' => (string) $i, 'parameters' => [['type' => 'text', 'text' => $fill($v)]]];
        }

        $template = ['name' => $cfg['template'], 'language' => ['code' => $cfg['language']]];
        if ($components) $template['components'] = $components;

        $res = $this->wa_api($this->merged_settings()['whatsapp']['phone_number_id'] . '/messages', 'POST', [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $number,
            'type' => 'template',
            'template' => $template,
        ]);
        if (!$res['ok']) return ['ok' => false, 'error' => $res['error']];
        return ['ok' => true, 'error' => '', 'ref' => (string) ($res['data']['messages'][0]['id'] ?? '')];
    }

    /**
     * The WhatsApp Business Account's message templates, for the campaign
     * editor. Each: name, language, status, category, and its parts (header
     * format and text, body text with {{n}}, footer, buttons).
     */
    public function get_mkt_wa_templates($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $w = $this->merged_settings()['whatsapp'];
        if ($w['waba_id'] === '' || $w['access_token'] === '') return $this->fail('Add your WhatsApp Business Account ID and access token in Marketing settings first.');

        $res = $this->wa_api($w['waba_id'] . '/message_templates?fields=name,language,status,category,components&limit=200');
        if (!$res['ok']) return $this->fail('WhatsApp: ' . $res['error'], 502);

        $out = [];
        foreach ((array) ($res['data']['data'] ?? []) as $tpl) {
            $parts = ['header' => null, 'body' => '', 'footer' => '', 'buttons' => []];
            foreach ((array) ($tpl['components'] ?? []) as $comp) {
                $type = strtoupper($comp['type'] ?? '');
                if ($type === 'HEADER') $parts['header'] = ['format' => strtoupper($comp['format'] ?? 'TEXT'), 'text' => (string) ($comp['text'] ?? '')];
                if ($type === 'BODY') $parts['body'] = (string) ($comp['text'] ?? '');
                if ($type === 'FOOTER') $parts['footer'] = (string) ($comp['text'] ?? '');
                if ($type === 'BUTTONS') {
                    foreach ((array) ($comp['buttons'] ?? []) as $btn) {
                        $parts['buttons'][] = ['type' => strtolower($btn['type'] ?? ''), 'text' => (string) ($btn['text'] ?? ''), 'url' => (string) ($btn['url'] ?? '')];
                    }
                }
            }
            preg_match_all('/\{\{(\d+)\}\}/', $parts['body'], $m);
            $out[] = [
                'name' => (string) ($tpl['name'] ?? ''),
                'language' => (string) ($tpl['language'] ?? ''),
                'status' => (string) ($tpl['status'] ?? ''),
                'category' => (string) ($tpl['category'] ?? ''),
                'parts' => $parts,
                'body_vars' => $m[1] ? max(array_map('intval', $m[1])) : 0,
            ];
        }
        $this->respond(['templates' => $out]);
    }

    /**
     * Meta's webhook. GET: subscription check (hub.verify_token must match
     * settings whatsapp.verify_token; answers hub.challenge). POST: signed with
     * the app secret (X-Hub-Signature-256); carries
     *  - statuses: sent / delivered / read / failed for messages we sent,
     *    matched by wamid (messages.provider_ref). read counts as opened;
     *  - messages: replies. A reply of STOP (and similar, or the template's
     *    "Stop promotions" quick reply) opts that number out of WhatsApp.
     */
    public function mkt_whatsapp_webhook($request)
    {
        $w = $this->merged_settings()['whatsapp'];

        if ($request->get_method() === 'GET') {
            $mode = (string) ($request['hub_mode'] ?? $request['hub.mode'] ?? '');
            $token = (string) ($request['hub_verify_token'] ?? $request['hub.verify_token'] ?? '');
            $challenge = (string) ($request['hub_challenge'] ?? $request['hub.challenge'] ?? '');
            if ($mode === 'subscribe' && $w['verify_token'] !== '' && hash_equals($w['verify_token'], $token)) {
                header('Content-Type: text/plain');
                echo preg_replace('/[^A-Za-z0-9_.-]/', '', $challenge);
                exit;
            }
            status_header(403);
            exit;
        }

        $raw = $request->get_body();
        $sig = (string) $request->get_header('x_hub_signature_256');
        if ($w['app_secret'] === '' || !hash_equals('sha256=' . hash_hmac('sha256', $raw, $w['app_secret']), $sig)) {
            status_header(403);
            exit;
        }

        global $wpdb;
        $t = $this->tables();
        $data = json_decode($raw, true);
        foreach ((array) ($data['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = $change['value'] ?? [];

                foreach ((array) ($value['statuses'] ?? []) as $st) {
                    $wamid = (string) ($st['id'] ?? '');
                    if ($wamid === '') continue;
                    /* Delivery / read receipts for inbox replies (ComboStoreInbox). */
                    do_action('combo_store_whatsapp_status', $st, $value);
                    $at = isset($st['timestamp']) ? wp_date('Y-m-d H:i:s', (int) $st['timestamp']) : $this->now();
                    switch ($st['status'] ?? '') {
                        case 'delivered':
                            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET delivered_at = COALESCE(delivered_at, %s) WHERE provider_ref = %s", $at, $wamid));
                            break;
                        case 'read':
                            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET delivered_at = COALESCE(delivered_at, %s), opened_at = COALESCE(opened_at, %s), opens = GREATEST(opens, 1) WHERE provider_ref = %s", $at, $at, $wamid));
                            break;
                        case 'failed':
                            $err = $st['errors'][0] ?? [];
                            $msg = trim(($err['error_data']['details'] ?? '') ?: ($err['title'] ?? 'Failed') . (isset($err['code']) ? ' (' . (int) $err['code'] . ')' : ''));
                            $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET status = 'failed', error = %s WHERE provider_ref = %s", mb_substr($msg, 0, 500), $wamid));
                            break;
                    }
                }

                foreach ((array) ($value['messages'] ?? []) as $msg) {
                    /* Every incoming message goes to the Messages inbox (ComboStoreInbox), STOP included. */
                    do_action('combo_store_whatsapp_message', $msg, $value);
                    $text = strtolower(trim((string) ($msg['text']['body'] ?? $msg['button']['text'] ?? $msg['interactive']['button_reply']['title'] ?? '')));
                    if (!in_array($text, self::WA_STOP_WORDS, true)) continue;
                    $from = preg_replace('/\D+/', '', (string) ($msg['from'] ?? ''));
                    if ($from === '') continue;
                    $now = $this->now();
                    $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$t['contacts']} WHERE phone IN (%s, %s)", '+' . $from, $from));
                    foreach ($ids as $cid) {
                        $wpdb->query($wpdb->prepare("UPDATE {$t['contacts']} SET whatsapp_status = 'unsubscribed', whatsapp_unsubscribed_at = %s, updated_at = %s WHERE id = %d AND whatsapp_status <> 'unsubscribed'", $now, $now, $cid));
                        /* Credit the latest WhatsApp campaign they got with the opt-out. */
                        $wpdb->query($wpdb->prepare("UPDATE {$t['messages']} SET unsubscribed_at = COALESCE(unsubscribed_at, %s) WHERE contact_id = %d AND channel = 'whatsapp' AND status = 'sent' ORDER BY id DESC LIMIT 1", $now, $cid));
                    }
                }
            }
        }
        status_header(200);
        echo 'ok';
        exit;
    }


    /* ---------------------------------------------------------------- */
    /* Automations                                                       */

    private function automation_defaults($key)
    {
        $defaults = [
            'welcome' => [
                'name' => 'Welcome email',
                'subject' => 'Welcome to {site_name}, {first_name|friend}!',
                'preheader' => "You're on the list. Here's what to expect.",
                'body' => '<h2>Welcome, {name}!</h2>'
                    . '<p>Thanks for joining the {site_name} newsletter. From now on you will be the first to hear about new arrivals, restocks and offers.</p>'
                    . '<p><a href="{site_url}" style="display:inline-block;background:#1b0e9f;color:#ffffff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px">Start shopping</a></p>'
                    . '<p>Talk soon,<br>The {site_name} team</p>',
            ],
            'abandoned_cart' => [
                'name' => 'Abandoned cart email',
                'subject' => 'You left something in your cart, {first_name|there}',
                'preheader' => 'Your items are saved. Finish your order in a click.',
                'body' => '<h2>Still thinking it over?</h2>'
                    . '<p>Hi {name}, you left these in your cart at {site_name}:</p>'
                    . '<p>{cart_items}</p>'
                    . '<p><strong>Total: {cart_total}</strong></p>'
                    . '<p><a href="{cart_url}" style="display:inline-block;background:#1b0e9f;color:#ffffff;text-decoration:none;font-weight:600;padding:12px 22px;border-radius:8px">Finish my order</a></p>'
                    . '<p style="color:#6b7280;font-size:13px">Questions about an item? Just reply to this email.</p>',
                /* Off until the store reviews it: it reaches shoppers who never subscribed. */
                'status' => 'inactive',
            ],
            'abandoned_cart_whatsapp' => [
                'name' => 'Abandoned cart WhatsApp',
                'channel' => 'whatsapp',
                'subject' => '',
                'preheader' => '',
                /* No template yet: the store picks one of its approved templates. */
                'body' => '',
                'status' => 'inactive',
            ],
        ];
        return $defaults[$key] ?? null;
    }

    /** The campaign row for an automation, created (active, default content) the first time. */
    private function automation_campaign($key)
    {
        global $wpdb;
        $t = $this->tables();
        if (!in_array($key, self::AUTOMATIONS, true)) return null;

        $find = function () use ($wpdb, $t, $key) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE automation = %s ORDER BY id ASC LIMIT 1", $key));
        };
        $row = $find();
        if ($row) return $row;

        /* Requests running at the same moment (the schema upgrade runs on every
           REST request until it finishes) each saw no row and each inserted one.
           Create under a lock, and look again once it is held. */
        $lock = 'cstore_mkt_auto_' . $key;
        if (!$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 10)', $lock))) return $find();
        try {
            $row = $find();
            if ($row) return $row;
            return $this->insert_automation_campaign($key);
        } finally {
            $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock));
        }
    }

    private function insert_automation_campaign($key)
    {
        global $wpdb;
        $t = $this->tables();
        $d = $this->automation_defaults($key);
        $now = $this->now();
        $wpdb->insert($t['campaigns'], [
            'name' => $d['name'],
            'channel' => $d['channel'] ?? 'email',
            'status' => $d['status'] ?? 'active',
            'automation' => $key,
            'subject' => $d['subject'],
            'preheader' => $d['preheader'],
            'body' => $this->clean_body($d['channel'] ?? 'email', $d['body']),
            'audience' => wp_json_encode(['all' => false, 'lists' => [], 'tags' => [], 'exclude_lists' => []]),
            'track_opens' => 1,
            'track_clicks' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", (int) $wpdb->insert_id));
    }

    /**
     * Queue an automation's email for one contact, if it is on and they are
     * subscribed, then try to send it now. Once per contact, ever (the
     * messages table is unique on campaign + contact), so someone who
     * unsubscribes and comes back is not welcomed twice.
     */
    public function trigger_automation($key, $contact_id)
    {
        global $wpdb;
        $t = $this->tables();
        $c = $this->automation_campaign($key);
        if (!$c || $c->status !== 'active' || $this->campaign_problem($c)) return false;

        $contact = $wpdb->get_row($wpdb->prepare("SELECT id, email, email_status FROM {$t['contacts']} WHERE id = %d", absint($contact_id)));
        if (!$contact || !$contact->email || $contact->email_status !== 'subscribed') return false;

        $added = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$t['messages']} (campaign_id, contact_id, channel, to_addr, status, created_at) VALUES (%d, %d, 'email', %s, 'queued', %s)",
            (int) $c->id,
            (int) $contact->id,
            $contact->email,
            $this->now()
        ));
        if (!$added) return false;

        $wpdb->query($wpdb->prepare("UPDATE {$t['campaigns']} SET recipients = recipients + 1, started_at = COALESCE(started_at, %s) WHERE id = %d", $this->now(), (int) $c->id));

        /* A few seconds at most; if another request holds the queue, the next cron tick sends it. */
        $this->process_queue(8);
        return true;
    }

    /** Email statuses a campaign may still send to. Broadcasts and the welcome: subscribed only. */
    private function sendable_statuses($c)
    {
        if (($c->automation ?? '') === 'abandoned_cart' && $this->merged_settings()['abandoned']['audience'] === 'anyone') {
            /* Shoppers who typed their email at checkout ("none"), or have not confirmed a sign-up yet. */
            return ['subscribed', 'none', 'pending'];
        }
        return ['subscribed'];
    }

    /**
     * combo_store_order_created: an "incomplete" order is a checkout the
     * shopper left (the storefront saves one when the checkout tab is hidden
     * with their details filled in). Queue the abandoned-cart email for it,
     * to go out after settings abandoned.delay_minutes.
     *
     * The shopper becomes a contact if they were not one: email status "none"
     * (not subscribed, so broadcasts never reach them). Nobody who
     * unsubscribed or bounced is emailed. A contact gets at most one per
     * abandoned.cooldown_days; a newer abandoned cart replaces one still
     * waiting to go out.
     */
    public function on_checkout_abandoned($args)
    {
        global $wpdb;
        $t = $this->tables();
        $order_id = absint($args['order_id'] ?? 0);
        if (!$order_id || (int) get_option('combo_store_marketing_schema', 0) < 4) return;

        $o = $wpdb->get_row($wpdb->prepare("SELECT id, status, billing_name, billing_email, billing_phone FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $order_id));
        if (!$o || $o->status !== 'incomplete') return;

        $this->queue_abandoned_whatsapp($o);

        $c = $this->automation_campaign('abandoned_cart');
        if (!$c || $c->status !== 'active' || $this->campaign_problem($c)) return;

        $email = $this->clean_email($o->billing_email);
        if (!$email) return;

        $settings = $this->merged_settings()['abandoned'];
        $parts = preg_split('/\s+/', trim((string) $o->billing_name), 2);
        $res = $this->upsert_contact([
            'email' => $email,
            'phone' => $o->billing_phone,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'tags' => ['abandoned-cart'],
        ], ['overwrite' => false, 'source' => 'checkout', 'new_email_status' => 'none', 'new_sms_status' => 'unsubscribed']);
        if (is_wp_error($res)) return;
        $contact = $wpdb->get_row($wpdb->prepare("SELECT id, email, email_status FROM {$t['contacts']} WHERE id = %d", $res[0]));
        if (!$contact || $contact->email !== $email || !in_array($contact->email_status, $this->sendable_statuses($c), true)) return;

        $this->queue_cart_message($c, (int) $contact->id, $email, $order_id, $settings);
    }

    /**
     * The WhatsApp abandoned-cart message for an incomplete order: only to a
     * contact who opted in to WhatsApp (at this checkout's box, the sign-up
     * form, or by hand), at their number. Nobody is added or opted in here.
     */
    private function queue_abandoned_whatsapp($o)
    {
        global $wpdb;
        $t = $this->tables();
        $c = $this->automation_campaign('abandoned_cart_whatsapp');
        if (!$c || $c->status !== 'active' || $this->campaign_problem($c)) return;

        $phone = $this->clean_phone($o->billing_phone);
        $email = $this->clean_email($o->billing_email);
        if (!$phone && !$email) return;

        /* The contact with this number, else the one with this email. */
        $contact = $wpdb->get_row($wpdb->prepare(
            "SELECT id, phone, whatsapp_status FROM {$t['contacts']} WHERE phone = %s OR email = %s ORDER BY (phone = %s) DESC LIMIT 1",
            (string) $phone, (string) $email, (string) $phone
        ));
        if (!$contact || $contact->whatsapp_status !== 'subscribed') return;

        $to = $contact->phone ?: $phone;
        if (!$to) return;
        $this->queue_cart_message($c, (int) $contact->id, $to, (int) $o->id, $this->merged_settings()['abandoned_whatsapp']);
    }

    /**
     * Queue one abandoned-cart message after settings delay_minutes. A newer
     * cart replaces one still waiting; at most one per cooldown_days per contact.
     */
    private function queue_cart_message($c, $contact_id, $to, $order_id, array $settings)
    {
        global $wpdb;
        $t = $this->tables();
        $now = $this->now();
        $send_after = gmdate('Y-m-d H:i:s', strtotime($now) + (int) $settings['delay_minutes'] * MINUTE_IN_SECONDS);

        $waiting = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['messages']} WHERE campaign_id = %d AND contact_id = %d AND status = 'queued' ORDER BY id DESC LIMIT 1", $c->id, $contact_id));
        if ($waiting) {
            $wpdb->update($t['messages'], ['ref_id' => $order_id, 'send_after' => $send_after, 'to_addr' => $to], ['id' => (int) $waiting]);
            return;
        }

        if ((int) $settings['cooldown_days'] > 0) {
            $since = gmdate('Y-m-d H:i:s', strtotime($now) - (int) $settings['cooldown_days'] * DAY_IN_SECONDS);
            $recent = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$t['messages']} WHERE campaign_id = %d AND contact_id = %d AND status = 'sent' AND sent_at >= %s LIMIT 1", $c->id, $contact_id, $since));
            if ($recent) return;
        }

        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$t['messages']} (campaign_id, contact_id, ref_id, channel, to_addr, status, send_after, created_at) VALUES (%d, %d, %d, %s, %s, 'queued', %s, %s)",
            (int) $c->id, $contact_id, $order_id, $c->channel, $to, $send_after, $now
        ));
        if ($wpdb->rows_affected) {
            $wpdb->query($wpdb->prepare("UPDATE {$t['campaigns']} SET recipients = recipients + 1, started_at = COALESCE(started_at, %s) WHERE id = %d", $now, (int) $c->id));
        }
    }

    /**
     * combo_store_order_created, any status: the checkout's "send me updates
     * on WhatsApp" box (create_order's whatsapp_optin, passed on by
     * ComboStoreOrders). A tick is the shopper's own consent, so it
     * subscribes the number to WhatsApp, even one that opted out before.
     * Runs before on_checkout_abandoned, which needs that consent.
     */
    public function on_checkout_whatsapp_optin($args)
    {
        global $wpdb;
        $t = $this->tables();
        if (empty($args['whatsapp_optin']) || (int) get_option('combo_store_marketing_schema', 0) < 5) return;
        $order_id = absint($args['order_id'] ?? 0);
        $o = $order_id ? $wpdb->get_row($wpdb->prepare("SELECT billing_name, billing_email, billing_phone FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $order_id)) : null;
        if (!$o || !$this->clean_phone($o->billing_phone)) return;

        $parts = preg_split('/\s+/', trim((string) $o->billing_name), 2);
        $res = $this->upsert_contact([
            'email' => $o->billing_email,
            'phone' => $o->billing_phone,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'whatsapp_status' => 'subscribed',
            'tags' => ['whatsapp'],
        ], ['overwrite' => false, 'source' => 'checkout', 'new_email_status' => 'none', 'new_sms_status' => 'unsubscribed']);
        if (is_wp_error($res)) return;

        /* The contact may have been found by email with no number yet, or have opted out before. */
        $now = $this->now();
        $wpdb->query($wpdb->prepare(
            "UPDATE {$t['contacts']} SET whatsapp_status = 'subscribed', whatsapp_unsubscribed_at = NULL, consent_ip = %s, updated_at = %s WHERE id = %d AND phone IS NOT NULL",
            mb_substr(preg_replace('/[^0-9a-f:.]/i', '', (string) ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 45), $now, (int) $res[0]
        ));
    }

    /**
     * The abandoned order's cart for the email: merge values {cart_items},
     * {cart_total}, {cart_url}, {order_id}. Answers a reason string instead
     * when the email should not go: they ordered since, the order moved on,
     * or nothing in it can still be bought.
     */
    private function abandoned_cart_context($order_id)
    {
        global $wpdb;
        $orders = $wpdb->prefix . 'cstore_orders';
        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM $orders WHERE id = %d", $order_id));
        if (!$o) return 'The abandoned checkout was deleted';
        if ($o->status !== 'incomplete') return 'The checkout became an order';

        $email = $this->clean_email($o->billing_email);
        $phone = trim((string) $o->billing_phone);
        $who = [];
        if ($email) $who[] = $wpdb->prepare('LOWER(billing_email) = %s', $email);
        $digits = substr(preg_replace('/\D+/', '', $phone), -10);
        if (strlen($digits) >= 6) $who[] = $wpdb->prepare($this->phone_tail_sql('billing_phone') . ' = %s', $digits);
        if ($who) {
            $placed = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $orders WHERE id <> %d AND created_at >= %s AND " . self::PLACED_SQL . " AND (" . implode(' OR ', $who) . ") LIMIT 1",
                $order_id,
                $o->created_at
            ));
            if ($placed) return 'Ordered before the message went out (order #' . (int) $placed . ')';
        }

        $lines = $this->cart_lines($order_id);
        if (!$lines) return 'Nothing in the cart can still be bought';

        $total = 0;
        foreach ($lines as $l) $total += $l['price'] * $l['quantity'];
        $currency = trim((string) $o->currency);

        $path = 'recover-cart?' . http_build_query(['o' => $order_id, 's' => $this->sign('cart' . $order_id)]);
        $names = array_map(function ($l) { return ($l['quantity'] > 1 ? $l['quantity'] . ' x ' : '') . $l['title']; }, $lines);
        $first_image = '';
        foreach ($lines as $l) { if (!empty($l['thumbnail'])) { $first_image = $l['thumbnail']; break; } }

        return [
            '{cart_items}' => $this->cart_items_html($lines, $currency),
            /* Plain text, for WhatsApp: "2 x Shirt, Mug and 1 more". */
            '{cart_items_text}' => count($names) > 3 ? implode(', ', array_slice($names, 0, 2)) . ' and ' . (count($names) - 2) . ' more' : implode(', ', $names),
            '{cart_count}' => (string) array_sum(array_column($lines, 'quantity')),
            '{cart_total}' => $this->money($total, $currency),
            '{cart_url}' => $this->store_url('/' . $path),
            /* For a template URL button like https://yourstore.com/{{1}}. */
            '{cart_path}' => $path,
            /* The first item's picture, for a template's image header. */
            '{cart_image}' => $first_image,
            '{order_id}' => (string) $order_id,
        ];
    }

    private function money($amount, $currency)
    {
        return trim(($currency !== '' ? $currency . ' ' : '') . number_format_i18n((float) $amount, 2));
    }

    /**
     * The abandoned order's lines as storefront cart lines, at today's prices
     * (the server takes a cart's prices as sent, so stale ones must not come
     * back). Subscription lines, unpublished products, disabled or
     * out-of-stock variations and products without a price are left out.
     */
    private function cart_lines($order_id)
    {
        global $wpdb;
        $items = $wpdb->get_results($wpdb->prepare("SELECT product_id, variation_id, quantity, meta FROM {$wpdb->prefix}cstore_order_items WHERE order_id = %d ORDER BY id ASC", $order_id));
        $lines = [];
        foreach ((array) $items as $it) {
            $meta = json_decode((string) $it->meta, true);
            if (($meta['purchase_type'] ?? 'one_time') !== 'one_time') continue;
            $pid = (int) $it->product_id;
            if (!$pid || get_post_status($pid) !== 'publish') continue;

            /* Older / imported products can lack a cstore_products row; a variation still carries its own price. */
            $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_products WHERE product_id = %d", $pid));
            if ($p && !in_array((string) $p->price_type, ['', 'simple'], true)) continue;

            $vid = (int) $it->variation_id;
            if (!$p && !$vid) continue;
            $src = $p;
            $title = get_the_title($pid);
            $thumb = get_the_post_thumbnail_url($pid, 'thumbnail') ?: '';
            if ($vid) {
                $v = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_products_variations WHERE id = %d AND product_id = %d", $vid, $pid));
                /* enable has been stored as "1"/"0" and as "yes"/"no". */
                if (!$v || in_array(strtolower((string) $v->enable), ['0', 'no', 'false'], true)) continue;
                $src = $v;
                if ($v->title !== '') $title .= ' - ' . $v->title;
                $vt = json_decode((string) $v->thumbnail, true);
                if (!empty($vt['src'])) $thumb = $vt['src'];
            }
            if (($src->stock_status ?? '') === 'outofstock') continue;

            $sale = (float) $src->sale_price;
            $regular = (float) $src->regular_price;
            $price = $sale > 0 ? $sale : $regular;
            if ($price <= 0) continue;

            $moq = max(1, (int) ($p->moq ?? 1), (int) ($src->moq ?? 1));
            $lines[] = [
                'id' => $pid,
                'product_id' => $pid,
                'variation_id' => $vid ?: null,
                'title' => wp_specialchars_decode($title, ENT_QUOTES),
                'quantity' => max((int) $it->quantity, $moq),
                'moq' => $moq,
                'price' => $price,
                'regular_price' => $regular,
                'sale_price' => $sale,
                'purchase_type' => 'one_time',
                /* The storefront cart renders this straight into an image src: a plain URL. */
                'thumbnail' => $thumb ?: null,
            ];
        }
        return $lines;
    }

    private function cart_items_html(array $lines, $currency)
    {
        $rows = '';
        foreach ($lines as $l) {
            $img = !empty($l['thumbnail'])
                ? '<img src="' . esc_url($l['thumbnail']) . '" width="56" height="56" alt="" style="display:block;width:56px;height:56px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb" />'
                : '';
            $rows .= '<tr>'
                . '<td style="padding:8px 12px 8px 0;width:56px;vertical-align:middle">' . $img . '</td>'
                . '<td style="padding:8px 0;vertical-align:middle;font-size:14px;color:#111827">' . esc_html($l['title']) . '<br><span style="color:#6b7280;font-size:13px">Qty ' . (int) $l['quantity'] . '</span></td>'
                . '<td style="padding:8px 0 8px 12px;vertical-align:middle;text-align:right;white-space:nowrap;font-size:14px;color:#111827">' . esc_html($this->money($l['price'] * $l['quantity'], $currency)) . '</td>'
                . '</tr>';
        }
        return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e5e7eb;border-bottom:1px solid #e5e7eb">' . $rows . '</table>';
    }

    /**
     * Public: the cart behind an abandoned-cart email link, for the
     * storefront's /recover-cart page. Body: o (order id), s (signature from
     * the email). Answers {lines, customer} or an error. customer holds the
     * checkout details they had typed, so they do not type them again.
     */
    public function mkt_cart($request)
    {
        global $wpdb;
        $b = $this->body($request) ?: $request->get_params();
        $id = absint($b['o'] ?? 0);
        if (!$id || !hash_equals($this->sign('cart' . $id), (string) ($b['s'] ?? ''))) return $this->fail('This link is not valid.', 404);

        $o = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $id));
        if (!$o) return $this->fail('This cart is no longer available.', 404);
        if ($o->status !== 'incomplete') return $this->fail('This cart has already been ordered.', 409);

        $lines = $this->cart_lines($id);
        if (!$lines) return $this->fail('None of these items can be bought right now.', 410);

        $customer = [];
        foreach (['billing_name', 'billing_phone', 'billing_email', 'billing_address', 'billing_city', 'billing_country', 'shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city', 'shipping_country'] as $k) {
            if (isset($o->$k) && (string) $o->$k !== '') $customer[$k] = (string) $o->$k;
        }
        $this->respond(['status' => 'success', 'lines' => $lines, 'customer' => $customer]);
    }

    /**
     * Abandoned-cart results: messages sent, and the orders their shoppers
     * placed (same email, or same phone by its last 10 digits) within 7 days
     * after one, each order counted once, with that revenue.
     */
    private function recovered_stats($campaign_id)
    {
        global $wpdb;
        $t = $this->tables();
        $orders = $wpdb->prefix . 'cstore_orders';

        $sent = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$t['messages']} WHERE campaign_id = %d AND status = 'sent'", $campaign_id));

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) orders, COALESCE(SUM(total_amount), 0) revenue FROM $orders WHERE id IN (
                SELECT o.id
                FROM {$t['messages']} m
                JOIN {$t['contacts']} c ON c.id = m.contact_id
                JOIN $orders o ON (LOWER(o.billing_email) = m.to_addr
                    OR (c.phone IS NOT NULL AND " . $this->phone_tail_sql('o.billing_phone') . ' = ' . $this->phone_tail_sql('c.phone') . "))
                    AND o.created_at >= m.sent_at AND o.created_at < m.sent_at + INTERVAL 7 DAY AND " . str_replace("COALESCE(status", "COALESCE(o.status", self::PLACED_SQL) . "
                WHERE m.campaign_id = %d AND m.status = 'sent'
            )",
            $campaign_id
        ));
        $currency = (string) $wpdb->get_var("SELECT currency FROM $orders WHERE currency <> '' ORDER BY id DESC LIMIT 1");
        return [
            'carts' => $sent,
            'orders' => (int) ($row->orders ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'revenue_label' => $this->money((float) ($row->revenue ?? 0), $currency),
        ];
    }

    /**
     * SQL for a phone column's last 10 digits, so "01711 000000", "+8801711000000"
     * and "8801711-000000" compare equal. Plain REPLACE: MySQL 5.7 has no REGEXP_REPLACE.
     */
    public function phone_tail_sql($col)
    {
        $expr = $col;
        foreach ([' ', '-', '+', '(', ')', '.'] as $ch) $expr = "REPLACE($expr, '$ch', '')";
        return "RIGHT($expr, 10)";
    }

    private function automations_summary()
    {
        $out = [];
        foreach (self::AUTOMATIONS as $key) {
            $c = $this->automation_campaign($key);
            if (!$c) continue;
            $stats = $this->campaign_stats([(int) $c->id]);
            $out[] = $this->shape_campaign($c, $stats[(int) $c->id] ?? null);
        }
        return $out;
    }

    /** Body: key (welcome). Answers the automation's campaign (created if missing). */
    public function get_mkt_automation($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $key = sanitize_key($this->body($request)['key'] ?? 'welcome');
        $c = $this->automation_campaign($key);
        if (!$c) return $this->fail('Unknown automation.', 404);
        $stats = $this->campaign_stats([(int) $c->id]);
        $this->respond($this->shape_campaign($c, $stats[(int) $c->id] ?? null));
    }

    /** Turn an automated email on or off. Body: id, active (bool). Turning on checks it can send. */
    public function set_mkt_automation($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t = $this->tables();
        $b = $this->body($request);
        $c = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$t['campaigns']} WHERE id = %d", absint($b['id'] ?? 0)));
        if (!$c || $c->automation === '') return $this->fail('Automation not found.', 404);

        $active = !empty($b['active']);
        if ($active && ($problem = $this->campaign_problem($c))) return $this->fail($problem);

        /* Its links open the storefront, so it needs the store's address. The dashboard
           runs on the storefront's own address and sends it (admin-only route). */
        if ($active && in_array($c->automation, self::CART_AUTOMATIONS, true) && (string) $this->setting('email.store_url', '') === '') {
            $url = untrailingslashit(esc_url_raw((string) ($b['store_url'] ?? '')));
            if ($url === '' || !preg_match('#^https?://#i', $url)) return $this->fail("Set your store's web address in Marketing settings first.");
            $s = $this->merged_settings();
            $s['email']['store_url'] = $url;
            update_option(self::OPTION, $s, false);
        }

        $wpdb->update($t['campaigns'], ['status' => $active ? 'active' : 'inactive', 'updated_at' => $this->now()], ['id' => (int) $c->id]);
        $this->respond(['status' => $active ? 'active' : 'inactive']);
    }


    /* ---------------------------------------------------------------- */
    /* Auto-add                                                          */

    private function auto_add(array $row, $source)
    {
        $c = $this->merged_settings()['contacts'];
        if (empty($c['auto_add'])) return;
        if ((int) get_option('combo_store_marketing_schema', 0) < self::SCHEMA_VERSION) return;
        $row['email_status'] = !empty($c['auto_subscribe_email']) ? 'subscribed' : 'unsubscribed';
        $row['sms_status'] = !empty($c['auto_subscribe_sms']) ? 'subscribed' : 'unsubscribed';
        $this->upsert_contact($row, ['list_ids' => $c['auto_list'] ? [(int) $c['auto_list']] : [], 'overwrite' => false, 'source' => $source]);
    }

    public function on_order_created($args)
    {
        global $wpdb;
        $order_id = absint($args['order_id'] ?? 0);
        if (!$order_id) return;
        $o = $wpdb->get_row($wpdb->prepare("SELECT status, billing_name, billing_email, billing_phone, userid FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $order_id));
        if (!$o || $o->status === 'incomplete') return;
        $parts = preg_split('/\s+/', trim((string) $o->billing_name), 2);
        $this->auto_add([
            'email' => $o->billing_email,
            'phone' => $o->billing_phone,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'user_id' => (int) $o->userid ?: null,
            'tags' => ['buyer'],
        ], 'order');
    }

    public function on_user_register($user_id)
    {
        $u = get_userdata($user_id);
        if (!$u || array_diff((array) $u->roles, ['customer', 'subscriber'])) return;
        $phone = get_user_meta($user_id, 'mobile', true);
        if (!$phone && preg_match('/^(.+)@mo\.lo$/i', $u->user_email, $m)) $phone = $m[1];
        $this->auto_add([
            'email' => $u->user_email,
            'phone' => $phone,
            'first_name' => get_user_meta($user_id, 'first_name', true) ?: $u->display_name,
            'user_id' => $user_id,
            'tags' => ['customer'],
        ], 'customer');
    }
}
