<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Changing a password ends the user's existing sign-ins.
 *
 * Sign-ins are JWTs (the JWT Authentication for WP REST API plugin, and
 * ComboStoreRest's social / OTP login), valid for 7 days and with no way to
 * revoke one. So a password change records the time on the user
 * (cstore_tokens_valid_after), and every REST request carrying a token
 * issued at or before that time is refused with 401 jwt_auth_revoked.
 *
 * - Someone else (an administrator) sets the password: every sign-in ends.
 * - You change your own: every sign-in ends except the one you made the change
 *   from, whose token is remembered (as a hash) and still accepted.
 *
 * WordPress cookie sessions are ended the same way. Covers every route that
 * changes a password: wp_set_password() (ComboStore's update_user_password,
 * password reset) and wp_update_user() (the wp-admin profile screen).
 */
class ComboStoreTokenRevocation
{
    const VALID_AFTER_META = 'cstore_tokens_valid_after';
    const KEEP_META        = 'cstore_tokens_keep';

    public function __construct()
    {
        add_action('wp_set_password', array($this, 'on_set_password'), 10, 3);
        add_action('profile_update', array($this, 'on_profile_update'), 10, 2);

        /* After the JWT plugin's own rest_pre_dispatch (10), which reports bad tokens. */
        add_filter('rest_pre_dispatch', array($this, 'refuse_revoked_token'), 11, 3);
    }

    public function on_set_password($password, $user_id, $old_user_data = null)
    {
        $this->revoke((int) $user_id);
    }

    /* wp_update_user() hashes the password itself, without wp_set_password(). */
    public function on_profile_update($user_id, $old_user_data)
    {
        $user = get_userdata($user_id);
        if ($user && $old_user_data && isset($old_user_data->user_pass) && $old_user_data->user_pass !== $user->user_pass) {
            $this->revoke((int) $user_id);
        }
    }

    /** The token on the current request, if any (as sent, without "Bearer "). */
    private function request_token()
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if (!$header || stripos($header, 'Bearer ') !== 0) return '';
        return trim(substr($header, 7));
    }

    private function decode($token)
    {
        if ($token === '' || !defined('JWT_AUTH_SECRET_KEY')) return null;
        try {
            return JWT::decode($token, new Key(JWT_AUTH_SECRET_KEY, 'HS256'));
        } catch (Exception $e) {
            return null;
        }
    }

    private function revoke($user_id)
    {
        if (!$user_id) return;

        update_user_meta($user_id, self::VALID_AFTER_META, time());

        /*
            Your own change keeps the sign-in it was made from. Matched by the
            token's user id, not get_current_user_id(), which is not always set
            yet for a JWT request.
        */
        $current = $this->request_token();
        $claims  = $this->decode($current);
        $self    = $claims && (int) ($claims->data->user->id ?? 0) === $user_id;

        if ($self) {
            update_user_meta($user_id, self::KEEP_META, hash('sha256', $current));
        } else {
            delete_user_meta($user_id, self::KEEP_META);
        }

        if (class_exists('WP_Session_Tokens')) {
            $sessions = \WP_Session_Tokens::get_instance($user_id);
            $cookie   = function_exists('wp_get_session_token') ? wp_get_session_token() : '';
            if ($user_id === get_current_user_id() && $cookie) {
                $sessions->destroy_others($cookie);
            } else {
                $sessions->destroy_all();
            }
        }
    }

    public function refuse_revoked_token($result, $server, $request)
    {
        if ($result !== null) return $result; /* already answered, or already an error */

        $token = $this->request_token();
        if ($token === '') return $result;

        $claims = $this->decode($token);
        if (!$claims) return $result; /* not ours to judge: invalid tokens are refused elsewhere */

        $user_id     = (int) ($claims->data->user->id ?? ($claims->sub ?? 0));
        $valid_after = $user_id ? (int) get_user_meta($user_id, self::VALID_AFTER_META, true) : 0;
        if (!$valid_after) return $result;

        $issued = (int) ($claims->iat ?? ($claims->nbf ?? 0));
        if ($issued > $valid_after) return $result;

        $keep = (string) get_user_meta($user_id, self::KEEP_META, true);
        if ($keep !== '' && hash_equals($keep, hash('sha256', $token))) return $result;

        return new WP_Error(
            'jwt_auth_revoked',
            'Your sign-in has ended because the password was changed. Please sign in again.',
            array('status' => 401)
        );
    }
}
