<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Facebook / Instagram ad campaigns (Meta Marketing API) in the marketing
 * campaign list: an ad account's campaigns with their spend and results, and
 * turning each one on (ACTIVE) or off (PAUSED).
 *
 *  - get_fb_ads_campaigns: GET /act_{id}/campaigns (fields below) merged with
 *    GET /act_{id}/insights?level=campaign for the chosen date_preset. Cached
 *    5 minutes per preset (refresh: true skips the cache), to stay well inside
 *    Meta's rate limits.
 *  - set_fb_campaign_status {id, status: ACTIVE|PAUSED}: POST /{campaign_id}
 *    status=..., only for a campaign of the configured ad account. Every change
 *    is logged (option combo_store_fb_ads_log, last 100): who, when, from, to.
 *
 * Settings: option combo_store_fb_ads {access_token, ad_account_id, api_version,
 * date_preset}. The token never leaves the server (has_access_token instead).
 * It is a System User token from Business Manager with ads_read (to read) and
 * ads_management (to turn campaigns on and off), and the system user must be
 * assigned to the ad account (MANAGE).
 *
 * Budgets (daily_budget, lifetime_budget, budget_remaining) are taken to be in
 * the account currency's minor units (paisa / cents), like spend_cap; Meta's
 * reference does not say it outright. Insight money fields are in major units.
 *
 * Admin only (routes POST, Bearer JWT), like the rest of marketing.
 */
class ComboStoreFacebookAds
{
    const OPTION = 'combo_store_fb_ads';
    const LOG_OPTION = 'combo_store_fb_ads_log';
    const ADMIN_ROLES = ['administrator'];
    const PRESETS = ['today', 'yesterday', 'last_7d', 'last_14d', 'last_30d', 'last_90d', 'this_month', 'last_month', 'maximum'];
    const CACHE_SECONDS = 300;
    const CAMPAIGN_FIELDS = 'id,name,status,effective_status,objective,daily_budget,lifetime_budget,budget_remaining,start_time,stop_time,created_time,updated_time';
    const INSIGHT_FIELDS = 'campaign_id,spend,impressions,reach,clicks,ctr,cpc,actions,cost_per_action_type';

    /*
     * The action counted as a campaign's "result", by objective (newer OUTCOME_*
     * objectives and older ones). The first action type the insights report wins.
     */
    const RESULT_ACTIONS = [
        'sales' => [['omni_purchase', 'offsite_conversion.fb_pixel_purchase', 'purchase'], 'Purchases'],
        'leads' => [['lead', 'onsite_conversion.lead_grouped', 'offsite_conversion.fb_pixel_lead'], 'Leads'],
        'messages' => [['onsite_conversion.messaging_conversation_started_7d'], 'Conversations'],
        'traffic' => [['link_click', 'landing_page_view'], 'Link clicks'],
        'engagement' => [['post_engagement', 'page_engagement'], 'Engagements'],
        'awareness' => [[], 'Reach'],
        'app' => [['mobile_app_install', 'app_install'], 'App installs'],
        'video' => [['video_view'], 'Video views'],
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        foreach (['get_fb_ads_settings', 'update_fb_ads_settings', 'fb_ads_test', 'get_fb_ads_campaigns', 'set_fb_campaign_status'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods' => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }


    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */

    private function auth($request)
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
        if (!$user || !array_intersect(self::ADMIN_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to manage marketing', array('status' => 403));
        }
        return $user;
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
        return new WP_Error('fb_ads_error', $message, array('status' => $status));
    }

    private function settings()
    {
        $s = get_option(self::OPTION, []);
        return array_merge(['access_token' => '', 'ad_account_id' => '', 'api_version' => 'v25.0', 'date_preset' => 'last_30d'], is_array($s) ? $s : []);
    }

    private function ready()
    {
        $s = $this->settings();
        return $s['access_token'] !== '' && $s['ad_account_id'] !== '';
    }

    /** "act_123", "123" -> "123" (digits only). */
    private function account_digits($id)
    {
        return preg_replace('/\D+/', '', (string) $id);
    }

    private function account_path()
    {
        return 'act_' . $this->account_digits($this->settings()['ad_account_id']);
    }

    /** A Graph API call. Answers ['ok', 'error', 'data', 'code']. */
    private function graph($method, $path, array $params = [])
    {
        $s = $this->settings();
        $url = 'https://graph.facebook.com/' . $s['api_version'] . '/' . ltrim($path, '/');
        $args = ['timeout' => 25, 'method' => $method, 'headers' => ['Authorization' => 'Bearer ' . $s['access_token'], 'Accept' => 'application/json']];
        if ($method === 'GET') {
            if ($params) $url = add_query_arg(array_map('rawurlencode', $params), $url);
        } else {
            $args['body'] = $params;
        }
        return $this->send($url, $args);
    }

    /** Follow a paging.next link (only on graph.facebook.com). */
    private function graph_next($url)
    {
        if (strpos((string) $url, 'https://graph.facebook.com/') !== 0) return ['ok' => false, 'error' => 'Unexpected paging link.', 'data' => null, 'code' => 0];
        $s = $this->settings();
        return $this->send($url, ['timeout' => 25, 'method' => 'GET', 'headers' => ['Authorization' => 'Bearer ' . $s['access_token'], 'Accept' => 'application/json']]);
    }

    private function send($url, array $args)
    {
        $res = wp_remote_request($url, $args);
        if (is_wp_error($res)) return ['ok' => false, 'error' => $res->get_error_message(), 'data' => null, 'code' => 0];
        $code = (int) wp_remote_retrieve_response_code($res);
        $data = json_decode(wp_remote_retrieve_body($res), true);
        $data = is_array($data) ? $data : [];
        if ($code >= 200 && $code < 300 && !isset($data['error'])) return ['ok' => true, 'error' => '', 'data' => $data, 'code' => $code];

        $e = is_array($data['error'] ?? null) ? $data['error'] : [];
        $c = (int) ($e['code'] ?? 0);
        $msg = trim((string) (($e['error_user_msg'] ?? '') ?: ($e['message'] ?? '')));
        if ($c === 190) $msg = 'Meta refused the access token (expired or revoked). Make a new System User token in Business Manager.';
        elseif (in_array($c, [17, 4, 613, 80000, 80004], true)) $msg = 'Meta\'s rate limit for this ad account was reached; try again in a few minutes.';
        elseif ($c === 10 || ($c >= 200 && $c <= 299)) $msg = 'The token is missing a permission for this (reading needs ads_read; turning campaigns on and off needs ads_management), or the system user is not assigned to the ad account.';
        elseif ($c === 2635) $msg = 'That Graph API version is no longer supported: set a newer one in Facebook Ads settings.';
        if ($msg === '') $msg = 'Meta answered ' . $code . '.';
        if (!empty($e['fbtrace_id'])) $msg .= ' (trace ' . sanitize_text_field($e['fbtrace_id']) . ')';
        return ['ok' => false, 'error' => $msg, 'data' => $data, 'code' => $c ?: $code];
    }


    /* ---------------------------------------------------------------- */
    /* Settings                                                          */

    public function get_fb_ads_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        $s = $this->settings();
        $s['has_access_token'] = $s['access_token'] !== '';
        $s['access_token'] = '';
        $s['ready'] = $this->ready();
        $s['presets'] = self::PRESETS;
        $this->respond($s);
    }

    public function update_fb_ads_settings($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        $b = $this->body($request);
        $cur = $this->settings();
        $token = preg_replace('/\s+/', '', (string) ($b['access_token'] ?? ''));
        $account = $this->account_digits($b['ad_account_id'] ?? $cur['ad_account_id']);
        $version = (string) ($b['api_version'] ?? $cur['api_version']);
        $out = [
            'access_token' => !empty($b['clear_access_token']) ? '' : ($token !== '' ? sanitize_text_field($token) : $cur['access_token']),
            'ad_account_id' => $account,
            'api_version' => preg_match('/^v\d{1,2}\.\d$/', $version) ? $version : 'v25.0',
            'date_preset' => in_array($b['date_preset'] ?? '', self::PRESETS, true) ? $b['date_preset'] : $cur['date_preset'],
        ];
        if ((string) ($b['ad_account_id'] ?? '') !== '' && $account === '') return $this->fail('The ad account ID is the number in Ads Manager (act_123456789 or 123456789).');
        update_option(self::OPTION, $out, false);
        $this->clear_cache();
        $this->respond(['status' => 'success']);
    }

    public function fb_ads_test($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        if (!$this->ready()) return $this->fail('Add the access token and the ad account ID first, then save.');
        $r = $this->graph('GET', $this->account_path(), ['fields' => 'name,currency,account_status,timezone_name']);
        if (!$r['ok']) return $this->fail($r['error']);
        $d = $r['data'];
        $states = [1 => 'active', 2 => 'disabled', 3 => 'unsettled', 7 => 'pending risk review', 8 => 'pending settlement', 9 => 'in grace period', 100 => 'pending closure', 101 => 'closed'];
        $state = $states[(int) ($d['account_status'] ?? 0)] ?? 'status ' . (int) ($d['account_status'] ?? 0);
        $this->respond(['status' => 'success', 'message' => sprintf('Connected to "%s" (%s, %s, %s).', $d['name'] ?? 'ad account', $d['currency'] ?? '', $state, $d['timezone_name'] ?? '')]);
    }


    /* ---------------------------------------------------------------- */
    /* Campaigns                                                         */

    private function cache_key($preset)
    {
        return 'cstore_fb_ads_' . md5($this->account_digits($this->settings()['ad_account_id']) . '|' . $preset);
    }

    private function clear_cache()
    {
        foreach (self::PRESETS as $p) delete_transient($this->cache_key($p));
    }

    /** Every page of a list edge (at most $pages pages). */
    private function all_pages($path, array $params, $pages = 10)
    {
        $r = $this->graph('GET', $path, $params);
        if (!$r['ok']) return $r;
        $rows = (array) ($r['data']['data'] ?? []);
        $next = $r['data']['paging']['next'] ?? '';
        for ($i = 1; $next && $i < $pages; $i++) {
            $r = $this->graph_next($next);
            if (!$r['ok']) return $r;
            $rows = array_merge($rows, (array) ($r['data']['data'] ?? []));
            $next = $r['data']['paging']['next'] ?? '';
        }
        return ['ok' => true, 'error' => '', 'data' => $rows];
    }

    /** Which result an objective counts: [action types, label]. */
    private function result_kind($objective)
    {
        $o = strtoupper((string) $objective);
        if (strpos($o, 'SALES') !== false || in_array($o, ['CONVERSIONS', 'PRODUCT_CATALOG_SALES'], true)) return self::RESULT_ACTIONS['sales'];
        if (strpos($o, 'LEAD') !== false) return self::RESULT_ACTIONS['leads'];
        if ($o === 'MESSAGES') return self::RESULT_ACTIONS['messages'];
        if (strpos($o, 'TRAFFIC') !== false || $o === 'LINK_CLICKS') return self::RESULT_ACTIONS['traffic'];
        if (strpos($o, 'APP') !== false) return self::RESULT_ACTIONS['app'];
        if ($o === 'VIDEO_VIEWS') return self::RESULT_ACTIONS['video'];
        if (strpos($o, 'AWARENESS') !== false || in_array($o, ['REACH', 'BRAND_AWARENESS'], true)) return self::RESULT_ACTIONS['awareness'];
        return self::RESULT_ACTIONS['engagement'];
    }

    private function shape_campaign(array $c, array $ins, $currency_offset)
    {
        $money = function ($minor) use ($currency_offset) {
            return ($minor === null || $minor === '') ? null : round(((float) $minor) / $currency_offset, 2);
        };
        $spend = isset($ins['spend']) ? (float) $ins['spend'] : 0.0;
        [$types, $label] = $this->result_kind($c['objective'] ?? '');
        $results = null;
        if (!$types) {
            $results = isset($ins['reach']) ? (int) $ins['reach'] : null;
        } else {
            foreach ($types as $t) {
                foreach ((array) ($ins['actions'] ?? []) as $a) {
                    if (($a['action_type'] ?? '') === $t) { $results = (int) round((float) $a['value']); break 2; }
                }
            }
        }
        return [
            'id' => (string) $c['id'],
            'name' => (string) ($c['name'] ?? ''),
            'status' => (string) ($c['status'] ?? ''),
            'effective_status' => (string) ($c['effective_status'] ?? ''),
            'objective' => (string) ($c['objective'] ?? ''),
            'daily_budget' => $money($c['daily_budget'] ?? null),
            'lifetime_budget' => $money($c['lifetime_budget'] ?? null),
            'budget_remaining' => $money($c['budget_remaining'] ?? null),
            'start_time' => $c['start_time'] ?? null,
            'stop_time' => $c['stop_time'] ?? null,
            'updated_time' => $c['updated_time'] ?? null,
            /* Turning on/off is only offered for campaigns that are on or off now. */
            'can_toggle' => in_array($c['status'] ?? '', ['ACTIVE', 'PAUSED'], true),
            'spend' => round($spend, 2),
            'impressions' => isset($ins['impressions']) ? (int) $ins['impressions'] : 0,
            'reach' => isset($ins['reach']) ? (int) $ins['reach'] : 0,
            'clicks' => isset($ins['clicks']) ? (int) $ins['clicks'] : 0,
            'ctr' => isset($ins['ctr']) ? round((float) $ins['ctr'], 2) : null,
            'cpc' => isset($ins['cpc']) ? round((float) $ins['cpc'], 2) : null,
            'results' => $results,
            'result_label' => $label,
            'cost_per_result' => $results ? round($spend / $results, 2) : null,
        ];
    }

    /** Admin: the ad account's campaigns with results. Body: date_preset, refresh. */
    public function get_fb_ads_campaigns($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        if (!$this->ready()) $this->respond(['ready' => false, 'campaigns' => []]);

        $b = $this->body($request);
        $preset = in_array($b['date_preset'] ?? '', self::PRESETS, true) ? $b['date_preset'] : $this->settings()['date_preset'];
        $key = $this->cache_key($preset);
        if (empty($b['refresh'])) {
            $cached = get_transient($key);
            if (is_array($cached)) $this->respond($cached + ['cached' => true]);
        }

        $acct = $this->graph('GET', $this->account_path(), ['fields' => 'name,currency']);
        if (!$acct['ok']) return $this->fail($acct['error'], 502);
        $currency = strtoupper((string) ($acct['data']['currency'] ?? ''));
        /* Currencies without minor units (Meta's "offset 1" list); everything else is 100. */
        $offset = in_array($currency, ['CLP', 'COP', 'CRC', 'HUF', 'ISK', 'IDR', 'JPY', 'KRW', 'PYG', 'TWD', 'VND'], true) ? 1 : 100;

        $campaigns = $this->all_pages($this->account_path() . '/campaigns', ['fields' => self::CAMPAIGN_FIELDS, 'limit' => '100']);
        if (!$campaigns['ok']) return $this->fail($campaigns['error'], 502);
        $insights = $this->all_pages($this->account_path() . '/insights', ['level' => 'campaign', 'fields' => self::INSIGHT_FIELDS, 'date_preset' => $preset, 'limit' => '500']);
        $by_id = [];
        if ($insights['ok']) foreach ($insights['data'] as $row) $by_id[(string) ($row['campaign_id'] ?? '')] = $row;

        $rows = [];
        foreach ($campaigns['data'] as $c) {
            if (!is_array($c) || empty($c['id'])) continue;
            $rows[] = $this->shape_campaign($c, $by_id[(string) $c['id']] ?? [], $offset);
        }
        /* Running first, then by spend. */
        usort($rows, function ($x, $y) {
            return [($y['effective_status'] === 'ACTIVE'), $y['spend']] <=> [($x['effective_status'] === 'ACTIVE'), $x['spend']];
        });

        $out = [
            'ready' => true,
            'account' => ['id' => $this->account_path(), 'name' => (string) ($acct['data']['name'] ?? ''), 'currency' => $currency],
            'date_preset' => $preset,
            'campaigns' => $rows,
            'insights_error' => $insights['ok'] ? '' : $insights['error'],
            'fetched_at' => current_time('mysql'),
            'log' => array_slice((array) get_option(self::LOG_OPTION, []), 0, 10),
        ];
        set_transient($key, $out, self::CACHE_SECONDS);
        $this->respond($out + ['cached' => false]);
    }

    /** Admin: turn a campaign on (ACTIVE) or off (PAUSED). Body: id, status. */
    public function set_fb_campaign_status($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;
        if (!$this->ready()) return $this->fail('Connect Facebook Ads in Marketing settings first.');
        $b = $this->body($request);
        $id = preg_replace('/\D+/', '', (string) ($b['id'] ?? ''));
        $to = (string) ($b['status'] ?? '');
        if ($id === '' || !in_array($to, ['ACTIVE', 'PAUSED'], true)) return $this->fail('Choose a campaign and ACTIVE or PAUSED.');

        /* Only campaigns of the configured ad account, and only between on and off. */
        $c = $this->graph('GET', $id, ['fields' => 'id,name,status,account_id']);
        if (!$c['ok']) return $this->fail($c['error'], 502);
        if ($this->account_digits($c['data']['account_id'] ?? '') !== $this->account_digits($this->settings()['ad_account_id'])) {
            return $this->fail('That campaign is not in the connected ad account.', 403);
        }
        $from = (string) ($c['data']['status'] ?? '');
        if (!in_array($from, ['ACTIVE', 'PAUSED'], true)) return $this->fail('This campaign is ' . strtolower($from) . '; it can only be changed in Ads Manager.');
        if ($from === $to) $this->respond(['status' => 'success', 'campaign_status' => $to, 'unchanged' => true]);

        $r = $this->graph('POST', $id, ['status' => $to]);
        if (!$r['ok']) return $this->fail($r['error'], 502);

        $log = (array) get_option(self::LOG_OPTION, []);
        array_unshift($log, [
            'campaign_id' => $id,
            'campaign' => (string) ($c['data']['name'] ?? ''),
            'from' => $from,
            'to' => $to,
            'user' => $auth->display_name ?: $auth->user_login,
            'at' => current_time('mysql'),
        ]);
        update_option(self::LOG_OPTION, array_slice($log, 0, 100), false);
        $this->clear_cache();
        $this->respond(['status' => 'success', 'campaign_status' => $to]);
    }
}
