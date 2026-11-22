<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Rider-only data for the dashboard's Riders list and rider page.
 *
 * Deliveries are cstore_deliveries rows with rider_id = the rider's user id;
 * their status history is cstore_deliveries_trackings, and the money comes
 * from the linked cstore_orders row (which may have been deleted since).
 *
 * Rider details that are not on a plain user (availability, vehicle, zone,
 * emergency contact, notes) are user meta, prefixed cstore_rider_.
 *
 * Every route is POST with a Bearer JWT, administrators and store managers only.
 */
class ComboStoreRiders
{
    const STAFF_ROLES = ['administrator', 'store_manager'];

    /* Delivery statuses, grouped. Anything else counts as in progress. */
    const DONE_STATUSES     = ['delivered', 'partial_delivered', 'delivered_approval_pending', 'partial_delivered_approval_pending'];
    const FAILED_STATUSES   = ['returned', 'cancelled', 'canceled', 'cancelled_approval_pending'];

    const AVAILABILITY = ['available', 'busy', 'off_duty'];
    const VEHICLES     = ['bike', 'bicycle', 'car', 'van', 'on_foot', 'other'];

    /* Editable rider fields => [meta key, max length]. */
    const FIELDS = [
        'availability'    => ['cstore_rider_availability', 20],
        'vehicle'         => ['cstore_rider_vehicle', 20],
        'vehicle_no'      => ['cstore_rider_vehicle_no', 40],
        'zone'            => ['cstore_rider_zone', 100],
        'emergency_name'  => ['cstore_rider_emergency_name', 100],
        'emergency_phone' => ['cstore_rider_emergency_phone', 30],
        'notes'           => ['cstore_rider_notes', 2000],
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        foreach (['get_riders_summary', 'get_rider_overview', 'update_rider_details'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }


    /* ---------------------------------------------------------------- */

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

        if (!$user || !array_intersect(self::STAFF_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view rider data', array('status' => 403));
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

    private function tables()
    {
        global $wpdb;
        return [
            'deliveries' => $wpdb->prefix . 'cstore_deliveries',
            'trackings'  => $wpdb->prefix . 'cstore_deliveries_trackings',
            'orders'     => $wpdb->prefix . 'cstore_orders',
        ];
    }

    private function in_list(array $values)
    {
        return "'" . implode("','", array_map('esc_sql', $values)) . "'";
    }

    /** The rider fields of one user. */
    private function details($user_id)
    {
        $out = [];
        foreach (self::FIELDS as $key => [$meta]) {
            $out[$key] = (string) get_user_meta($user_id, $meta, true);
        }
        if ($out['availability'] === '') $out['availability'] = 'available';
        return $out;
    }

    /** Cash still to collect on an order: total less advance, never negative. */
    private function cod_sql($alias = 'o')
    {
        return "CASE WHEN $alias.payment_method = 'cod' THEN GREATEST(COALESCE($alias.total_amount,0) - COALESCE($alias.advance_payment,0), 0) ELSE 0 END";
    }


    /* ---------------------------------------------------------------- */

    /**
     * Per-rider figures for the Riders list. Body: ids (user ids, max 200).
     * Answers {riders: {id: {total, active, done, failed, last_activity, ...details}}}.
     */
    public function get_riders_summary($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);
        $ids  = isset($body['ids']) && is_array($body['ids']) ? array_slice(array_values(array_filter(array_map('absint', $body['ids']))), 0, 200) : [];

        if (!$ids) $this->respond(['status' => 'success', 'riders' => new \stdClass()]);

        $in     = implode(',', $ids);
        $done   = $this->in_list(self::DONE_STATUSES);
        $failed = $this->in_list(self::FAILED_STATUSES);

        $rows = $wpdb->get_results(
            "SELECT d.rider_id,
                    COUNT(*) total,
                    SUM(d.status IN ($done)) done,
                    SUM(d.status IN ($failed)) failed,
                    GREATEST(MAX(d.datetime), COALESCE(MAX(tr.last_track), MAX(d.datetime))) last_activity
             FROM {$t['deliveries']} d
             LEFT JOIN (SELECT delivery_id, MAX(datetime) last_track FROM {$t['trackings']} GROUP BY delivery_id) tr ON tr.delivery_id = d.id
             WHERE d.rider_id IN ($in)
             GROUP BY d.rider_id",
            ARRAY_A
        );

        $by = [];
        foreach ($rows as $r) $by[(int) $r['rider_id']] = $r;

        $out = [];
        foreach ($ids as $id) {
            $r = $by[$id] ?? null;
            $total  = $r ? (int) $r['total'] : 0;
            $doneN  = $r ? (int) $r['done'] : 0;
            $failN  = $r ? (int) $r['failed'] : 0;
            $out[$id] = array_merge($this->details($id), [
                'total'         => $total,
                'done'          => $doneN,
                'failed'        => $failN,
                'active'        => $total - $doneN - $failN,
                'last_activity' => $r['last_activity'] ?? null,
            ]);
        }

        $this->respond(['status' => 'success', 'riders' => $out]);
    }

    /**
     * Everything the rider page shows. Body: rider_id, from_date, to_date (yyyy-mm-dd;
     * both empty = all time).
     */
    public function get_rider_overview($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $t    = $this->tables();
        $body = $this->body($request);

        $rider_id = absint($body['rider_id'] ?? 0);
        $rider    = $rider_id ? get_user_by('id', $rider_id) : null;
        if (!$rider) $this->respond(['status' => 'error', 'message' => 'Rider not found']);

        $from = !empty($body['from_date']) ? substr(sanitize_text_field($body['from_date']), 0, 10) . ' 00:00:00' : '';
        $to   = !empty($body['to_date']) ? substr(sanitize_text_field($body['to_date']), 0, 10) . ' 23:59:59' : '';

        $range  = '';
        $params = [$rider_id];
        if ($from) { $range .= ' AND d.datetime >= %s'; $params[] = $from; }
        if ($to)   { $range .= ' AND d.datetime <= %s'; $params[] = $to; }

        $done   = $this->in_list(self::DONE_STATUSES);
        $failed = $this->in_list(self::FAILED_STATUSES);
        $cod    = $this->cod_sql();

        /* Range figures */
        $by_status = $wpdb->get_results($wpdb->prepare(
            "SELECT d.status, COUNT(*) n FROM {$t['deliveries']} d WHERE d.rider_id = %d $range GROUP BY d.status",
            $params
        ), ARRAY_A);

        $counts = ['total' => 0, 'done' => 0, 'failed' => 0, 'active' => 0];
        $statuses = [];
        foreach ($by_status as $row) {
            $n = (int) $row['n'];
            $statuses[$row['status'] ?: 'unknown'] = $n;
            $counts['total'] += $n;
            if (in_array($row['status'], self::DONE_STATUSES, true)) $counts['done'] += $n;
            elseif (in_array($row['status'], self::FAILED_STATUSES, true)) $counts['failed'] += $n;
            else $counts['active'] += $n;
        }
        $finished = $counts['done'] + $counts['failed'];

        $collected = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM($cod) FROM {$t['deliveries']} d JOIN {$t['orders']} o ON o.id = d.order_id
             WHERE d.rider_id = %d AND d.status IN ($done) $range",
            $params
        ));

        /* Time from assignment to the delivered tracking, for deliveries finished in range. */
        $avg_minutes = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, d.datetime, tr.done_at)) FROM {$t['deliveries']} d
             JOIN (SELECT delivery_id, MIN(datetime) done_at FROM {$t['trackings']} WHERE status IN ($done) GROUP BY delivery_id) tr ON tr.delivery_id = d.id
             WHERE d.rider_id = %d AND tr.done_at >= d.datetime $range",
            $params
        ));

        /* Lists: the deliveries in hand right now (any date), and the latest in range. */
        $select = "d.id, d.order_id, d.status, d.type, d.courier, d.tracking_code, d.datetime,
                   (SELECT MAX(tr.datetime) FROM {$t['trackings']} tr WHERE tr.delivery_id = d.id) last_update,
                   (SELECT tr.status FROM {$t['trackings']} tr WHERE tr.delivery_id = d.id ORDER BY tr.datetime DESC, tr.id DESC LIMIT 1) last_step,
                   o.id AS order_exists, o.billing_name, o.billing_phone, o.shipping_address, o.billing_address, o.shipping_city, o.billing_city,
                   o.total_amount, o.payment_method, o.payment_status, ($cod) AS cod_due";

        $active = $wpdb->get_results($wpdb->prepare(
            "SELECT $select FROM {$t['deliveries']} d LEFT JOIN {$t['orders']} o ON o.id = d.order_id
             WHERE d.rider_id = %d AND d.status NOT IN ($done) AND d.status NOT IN ($failed)
             ORDER BY d.datetime ASC LIMIT 50",
            $rider_id
        ), ARRAY_A);

        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT $select FROM {$t['deliveries']} d LEFT JOIN {$t['orders']} o ON o.id = d.order_id
             WHERE d.rider_id = %d $range ORDER BY d.datetime DESC LIMIT 10",
            $params
        ), ARRAY_A);

        $cash_in_hand_active = 0;
        foreach ($active as $a) $cash_in_hand_active += (float) $a['cod_due'];

        $last_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT GREATEST(COALESCE(MAX(d.datetime),'1970-01-01'), COALESCE(MAX(tr.datetime),'1970-01-01'))
             FROM {$t['deliveries']} d LEFT JOIN {$t['trackings']} tr ON tr.delivery_id = d.id WHERE d.rider_id = %d",
            $rider_id
        ));

        $this->respond([
            'status' => 'success',
            'rider'  => array_merge([
                'id'         => (int) $rider->ID,
                'name'       => $rider->display_name,
                'email'      => $rider->user_email,
                'phone'      => (string) get_user_meta($rider->ID, 'phone', true),
                'avatar'     => get_avatar_url($rider->user_email, ['size' => 96]),
                'registered' => $rider->user_registered,
                'roles'      => array_values((array) $rider->roles),
                'is_rider'   => in_array('rider', (array) $rider->roles, true),
                'last_activity' => ($last_activity && $last_activity !== '1970-01-01 00:00:00') ? $last_activity : null,
            ], $this->details($rider->ID)),
            'counts'       => $counts,
            'statuses'     => $statuses,
            'success_rate' => $finished ? round($counts['done'] / $finished * 100, 1) : null,
            'avg_minutes'  => $avg_minutes !== null ? (int) round($avg_minutes) : null,
            'cod_collected' => round($collected, 2),
            'cod_to_collect' => round($cash_in_hand_active, 2),
            'active'       => $active ?: [],
            'recent'       => $recent ?: [],
        ]);
    }

    /**
     * Save rider fields. Body: id (user id) and any of availability, vehicle,
     * vehicle_no, zone, emergency_name, emergency_phone, notes.
     */
    public function update_rider_details($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);
        if (!$id || !get_user_by('id', $id)) $this->respond(['status' => 'error', 'message' => 'Rider not found']);

        if (isset($body['availability']) && !in_array($body['availability'], self::AVAILABILITY, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown availability']);
        }
        if (isset($body['vehicle']) && $body['vehicle'] !== '' && !in_array($body['vehicle'], self::VEHICLES, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown vehicle']);
        }

        foreach (self::FIELDS as $key => [$meta, $max]) {
            if (!array_key_exists($key, $body)) continue;
            $value = $key === 'notes' ? sanitize_textarea_field((string) $body[$key]) : sanitize_text_field((string) $body[$key]);
            update_user_meta($id, $meta, mb_substr($value, 0, $max));
        }

        $this->respond(['status' => 'success', 'id' => $id, 'details' => $this->details($id)]);
    }
}
