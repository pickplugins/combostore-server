<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Which country a request comes from, for the Fraud Checker's country filter.
 *
 * 1. Cloudflare's CF-IPCountry header, but only when the request really came
 *    through Cloudflare (REMOTE_ADDR in Cloudflare's published ranges) -
 *    otherwise anyone could send the header and pick their own country.
 * 2. IPinfo Lite (https://ipinfo.io, free, country-level) with the store's
 *    token, cached a day per IP (a failed lookup 10 minutes).
 *
 * geoPlugin's free API, used before, now answers every lookup with 403.
 *
 * The token lives in its own option (combo_store_geoip), never in the store
 * settings blob: get_settings_public hands that blob out to anyone.
 * Routes (POST, Bearer JWT, administrator or store_manager):
 * get_geoip_settings, update_geoip_settings, geoip_test.
 */
class ComboStoreGeoIp
{
    const OPTION = 'combo_store_geoip';
    const ROLES = ['administrator', 'store_manager'];

    /* https://www.cloudflare.com/ips/ */
    const CLOUDFLARE_RANGES = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
        '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
        '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
        '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        foreach (['get_geoip_settings', 'update_geoip_settings', 'geoip_test'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods' => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }

    /* ---------------------------------------------------------------- */
    /* The lookup                                                        */

    /**
     * The country of the current request.
     *
     * @return array { country: "BD" or "", ip: string, source: cloudflare|ipinfo|none, error: string }
     */
    public static function request_country()
    {
        $remote = isset($_SERVER['REMOTE_ADDR']) ? trim(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $ip = $remote;

        if ($remote !== '' && self::is_cloudflare($remote)) {
            $header = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? strtoupper(trim(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']))) : '';

            /* XX = unknown, T1 = Tor: neither is a country. */
            if (preg_match('/^[A-Z]{2}$/', $header) && !in_array($header, ['XX', 'T1'], true)) {
                return ['country' => $header, 'ip' => $remote, 'source' => 'cloudflare', 'error' => ''];
            }

            /* Through Cloudflare, so its header names the shopper's address. */
            $connecting = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? trim(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP'])) : '';
            if (filter_var($connecting, FILTER_VALIDATE_IP)) {
                $ip = $connecting;
            }
        }

        $found = self::lookup($ip);

        return ['country' => $found['country'], 'ip' => $ip, 'source' => $found['country'] !== '' ? 'ipinfo' : 'none', 'error' => $found['error']];
    }

    /**
     * One IP through IPinfo Lite, cached.
     *
     * @return array { country: string, error: string }
     */
    public static function lookup($ip, $use_cache = true)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => '', 'error' => 'A local or private address has no country.'];
        }

        $token = self::token();
        if ($token === '') {
            return ['country' => '', 'error' => 'No IPinfo token is saved.'];
        }

        /* Keyed by token too, so saving a new token is not stuck behind failures cached under the old one. */
        $cache_key = 'cstore_ipcc_' . md5($token . '|' . $ip);
        if ($use_cache) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return ['country' => $cached === '-' ? '' : $cached, 'error' => $cached === '-' ? 'The last lookup failed (retried after 10 minutes).' : ''];
            }
        }

        $response = wp_remote_get('https://api.ipinfo.io/lite/' . rawurlencode($ip) . '?token=' . rawurlencode($token), array('timeout' => 4));

        $country = '';
        $error = '';

        if (is_wp_error($response)) {
            $error = 'IPinfo did not answer: ' . $response->get_error_message();
        } else {
            $code = wp_remote_retrieve_response_code($response);
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 && is_array($data)) {
                $country = strtoupper((string) ($data['country_code'] ?? ''));
                if (!preg_match('/^[A-Z]{2}$/', $country)) {
                    $country = '';
                    $error = 'IPinfo has no country for this address.';
                }
            } else {
                $error = 'IPinfo answered ' . $code . (isset($data['error']['title']) ? ': ' . $data['error']['title'] : '') . '.';
            }
        }

        set_transient($cache_key, $country !== '' ? $country : '-', $country !== '' ? DAY_IN_SECONDS : 10 * MINUTE_IN_SECONDS);

        return ['country' => $country, 'error' => $error];
    }

    public static function token()
    {
        $s = get_option(self::OPTION, []);
        return is_array($s) && !empty($s['ipinfo_token']) ? (string) $s['ipinfo_token'] : '';
    }

    private static function is_cloudflare($ip)
    {
        foreach (self::CLOUDFLARE_RANGES as $cidr) {
            if (self::in_cidr($ip, $cidr)) return true;
        }
        return false;
    }

    private static function in_cidr($ip, $cidr)
    {
        list($subnet, $bits) = explode('/', $cidr);
        $ip_bin = @inet_pton($ip);
        $net_bin = @inet_pton($subnet);
        if ($ip_bin === false || $net_bin === false || strlen($ip_bin) !== strlen($net_bin)) return false;

        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        if (substr($ip_bin, 0, $bytes) !== substr($net_bin, 0, $bytes)) return false;

        $rest = $bits % 8;
        if ($rest === 0) return true;

        $mask = chr((0xFF << (8 - $rest)) & 0xFF);
        return (($ip_bin[$bytes] & $mask) === ($net_bin[$bytes] & $mask));
    }

    /* ---------------------------------------------------------------- */
    /* Routes                                                            */

    public function get_geoip_settings($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;

        $this->respond($this->public_settings());
    }

    public function update_geoip_settings($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;

        $b = $this->body($request);
        $s = get_option(self::OPTION, []);
        $s = is_array($s) ? $s : [];

        if (!empty($b['clear_token'])) {
            $s['ipinfo_token'] = '';
        } elseif (isset($b['ipinfo_token']) && trim((string) $b['ipinfo_token']) !== '') {
            $token = sanitize_text_field(trim((string) $b['ipinfo_token']));
            if (!preg_match('/^[A-Za-z0-9_-]{6,100}$/', $token)) {
                return new WP_Error('geoip_error', 'That does not look like an IPinfo token.', array('status' => 400));
            }
            $s['ipinfo_token'] = $token;
        }

        update_option(self::OPTION, $s, false);

        $this->respond(array_merge(['status' => 'success'], $this->public_settings()));
    }

    /** Look up an IP (8.8.8.8 by default), skipping the cache, to prove the token works. */
    public function geoip_test($request)
    {
        $user = $this->auth($request);
        if (is_wp_error($user)) return $user;

        $b = $this->body($request);
        $ip = isset($b['ip']) && trim((string) $b['ip']) !== '' ? trim((string) $b['ip']) : '8.8.8.8';

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return new WP_Error('geoip_error', 'Enter a valid IP address.', array('status' => 400));
        }

        $found = self::lookup($ip, false);

        if ($found['country'] === '') {
            return new WP_Error('geoip_error', $found['error'] ?: 'No country found.', array('status' => 502));
        }

        $this->respond(['status' => 'success', 'ip' => $ip, 'country' => $found['country'], 'message' => $ip . ' is in ' . $found['country'] . '.']);
    }

    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */

    private function public_settings()
    {
        return ['provider' => 'ipinfo', 'has_token' => self::token() !== '', 'ipinfo_token' => ''];
    }

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
        if (!$user || !array_intersect(self::ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to manage the fraud checker', array('status' => 403));
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
}
