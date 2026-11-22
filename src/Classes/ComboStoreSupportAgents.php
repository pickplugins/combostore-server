<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Support-agent data for the dashboard's Support agents list and agent page,
 * and ticket assignment.
 *
 * Tickets are `ticket` posts; their status is post meta markedAs (open,
 * processing, closed; empty = open) and their priority is meta priority
 * (1 normal, 10 high, 90 urgent). Tickets had no owner, so this class adds:
 *  - cstore_assigned_to / cstore_assigned_at: who handles the ticket, since when;
 *  - cstore_closed_at / cstore_closed_by: written whenever markedAs becomes
 *    "closed" (and cleared when it is reopened), however it is changed.
 *
 * Agent details that are not on a plain user (availability, skills, languages,
 * shift, capacity, notes) are user meta, prefixed cstore_agent_.
 *
 * Routes are POST with a Bearer JWT. Administrators and store managers may do
 * everything; a support agent may only take an unassigned ticket for
 * themselves or hand back one of their own.
 */
class ComboStoreSupportAgents
{
    const STAFF_ROLES = ['administrator', 'store_manager'];
    const AGENT_ROLE  = 'support_agent';

    const AVAILABILITY = ['online', 'away', 'offline'];

    /* Editable agent fields => [meta key, max length]. */
    const FIELDS = [
        'availability' => ['cstore_agent_availability', 20],
        'skills'       => ['cstore_agent_skills', 200],
        'languages'    => ['cstore_agent_languages', 100],
        'shift'        => ['cstore_agent_shift', 100],
        'max_open'     => ['cstore_agent_max_open', 4],
        'notes'        => ['cstore_agent_notes', 2000],
    ];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));

        /* Closing time, however markedAs is written (update_ticket, wp-admin...). */
        add_action('added_post_meta', array($this, 'on_status_meta'), 10, 4);
        add_action('updated_post_meta', array($this, 'on_status_meta'), 10, 4);

        /* A deleted agent's tickets go back to the unassigned queue. */
        add_action('delete_user', array($this, 'on_delete_user'));
    }

    public function on_delete_user($user_id)
    {
        global $wpdb;
        $tickets = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'cstore_assigned_to' AND meta_value = %s",
            (string) (int) $user_id
        ));
        foreach ($tickets as $ticket_id) {
            delete_post_meta((int) $ticket_id, 'cstore_assigned_to');
            delete_post_meta((int) $ticket_id, 'cstore_assigned_at');
        }
    }

    public function register_routes()
    {
        foreach (['get_support_agents_summary', 'get_support_agent_overview', 'update_support_agent_details', 'assign_ticket'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }


    /* ---------------------------------------------------------------- */

    public function on_status_meta($meta_id, $post_id, $meta_key, $value)
    {
        if ($meta_key !== 'markedAs' || get_post_type($post_id) !== 'ticket') return;

        if ($value === 'closed') {
            if (get_post_meta($post_id, 'cstore_closed_at', true)) return;
            $by = get_current_user_id() ?: (int) get_post_meta($post_id, 'cstore_assigned_to', true);
            update_post_meta($post_id, 'cstore_closed_at', current_time('mysql'));
            update_post_meta($post_id, 'cstore_closed_by', (int) $by);
        } else {
            delete_post_meta($post_id, 'cstore_closed_at');
            delete_post_meta($post_id, 'cstore_closed_by');
        }
    }

    /** The token's user, or a WP_Error. $staff_only: administrators and store managers. */
    private function auth($request, $staff_only = true)
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

        $allowed = $staff_only ? self::STAFF_ROLES : array_merge(self::STAFF_ROLES, [self::AGENT_ROLE]);
        if (!$user || !array_intersect($allowed, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to view support agent data', array('status' => 403));
        }
        return $user;
    }

    private function is_staff($user)
    {
        return (bool) array_intersect(self::STAFF_ROLES, (array) $user->roles);
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

    /** The agent fields of one user. */
    private function details($user_id)
    {
        $out = [];
        foreach (self::FIELDS as $key => [$meta]) {
            $out[$key] = (string) get_user_meta($user_id, $meta, true);
        }
        if ($out['availability'] === '') $out['availability'] = 'offline';
        return $out;
    }

    /**
     * Every ticket as one row: id, title, created, status, priority,
     * assigned_to, assigned_at, closed_at, closed_by. Used as a derived table.
     */
    private function tickets_sql()
    {
        global $wpdb;
        $meta = function ($key) use ($wpdb) {
            return "(SELECT MAX(m.meta_value) FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = '$key')";
        };
        return "SELECT p.ID id, p.post_title title, p.post_date created,
                       COALESCE(NULLIF({$meta('markedAs')}, ''), 'open') status,
                       CAST(COALESCE(NULLIF({$meta('priority')}, ''), '1') AS UNSIGNED) priority,
                       CAST(COALESCE(NULLIF({$meta('cstore_assigned_to')}, ''), '0') AS UNSIGNED) assigned_to,
                       {$meta('cstore_assigned_at')} assigned_at,
                       {$meta('cstore_closed_at')} closed_at,
                       CAST(COALESCE(NULLIF({$meta('cstore_closed_by')}, ''), '0') AS UNSIGNED) closed_by
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'ticket' AND p.post_status NOT IN ('trash', 'auto-draft')";
    }


    /* ---------------------------------------------------------------- */

    /**
     * Per-agent figures for the Support agents list. Body: ids (user ids, max 200).
     * Answers {agents: {id: {open, processing, closed_30, last_activity, ...details}},
     * unassigned: n}.
     */
    public function get_support_agents_summary($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $body = $this->body($request);
        $ids  = isset($body['ids']) && is_array($body['ids']) ? array_slice(array_values(array_filter(array_map('absint', $body['ids']))), 0, 200) : [];

        $t = $this->tickets_sql();
        $unassigned = (int) $wpdb->get_var("SELECT COUNT(*) FROM ($t) t WHERE t.status <> 'closed' AND t.assigned_to = 0");

        if (!$ids) $this->respond(['status' => 'success', 'agents' => new \stdClass(), 'unassigned' => $unassigned]);

        $in    = implode(',', $ids);
        $since = gmdate('Y-m-d H:i:s', current_time('timestamp') - 30 * DAY_IN_SECONDS);

        $workload = $wpdb->get_results(
            "SELECT t.assigned_to id, SUM(t.status = 'open') open_n, SUM(t.status = 'processing') processing_n,
                    SUM(t.status <> 'closed' AND t.priority >= 90) urgent_n, MAX(t.assigned_at) last_assigned
             FROM ($t) t WHERE t.assigned_to IN ($in) GROUP BY t.assigned_to",
            ARRAY_A
        );
        $closed = $wpdb->get_results($wpdb->prepare(
            "SELECT t.closed_by id, SUM(t.closed_at >= %s) closed_30, COUNT(*) closed_all, MAX(t.closed_at) last_closed
             FROM ($t) t WHERE t.status = 'closed' AND t.closed_by IN ($in) GROUP BY t.closed_by",
            $since
        ), ARRAY_A);

        $w = []; foreach ($workload as $r) $w[(int) $r['id']] = $r;
        $c = []; foreach ($closed as $r) $c[(int) $r['id']] = $r;

        $out = [];
        foreach ($ids as $id) {
            $wr = $w[$id] ?? null;
            $cr = $c[$id] ?? null;
            $last = max((string) ($wr['last_assigned'] ?? ''), (string) ($cr['last_closed'] ?? ''));
            $out[$id] = array_merge($this->details($id), [
                'open'          => $wr ? (int) $wr['open_n'] : 0,
                'processing'    => $wr ? (int) $wr['processing_n'] : 0,
                'urgent'        => $wr ? (int) $wr['urgent_n'] : 0,
                'closed_30'     => $cr ? (int) $cr['closed_30'] : 0,
                'closed_all'    => $cr ? (int) $cr['closed_all'] : 0,
                'last_activity' => $last !== '' ? $last : null,
            ]);
        }

        $this->respond(['status' => 'success', 'agents' => $out, 'unassigned' => $unassigned]);
    }

    /**
     * Everything the agent page shows. Body: agent_id, from_date, to_date
     * (yyyy-mm-dd; both empty = all time). The queue is always as of now.
     */
    public function get_support_agent_overview($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        global $wpdb;
        $body = $this->body($request);

        $agent_id = absint($body['agent_id'] ?? 0);
        $agent    = $agent_id ? get_user_by('id', $agent_id) : null;
        if (!$agent) $this->respond(['status' => 'error', 'message' => 'Support agent not found']);

        $from = !empty($body['from_date']) ? substr(sanitize_text_field($body['from_date']), 0, 10) . ' 00:00:00' : '';
        $to   = !empty($body['to_date']) ? substr(sanitize_text_field($body['to_date']), 0, 10) . ' 23:59:59' : '';

        $t = $this->tickets_sql();
        $cols = "t.id, t.title, t.created, t.status, t.priority, t.assigned_at, t.closed_at";

        /* Their queue now, most urgent then oldest first. */
        $queue = $wpdb->get_results($wpdb->prepare(
            "SELECT $cols FROM ($t) t WHERE t.assigned_to = %d AND t.status <> 'closed'
             ORDER BY t.priority DESC, COALESCE(t.assigned_at, t.created) ASC LIMIT 100",
            $agent_id
        ), ARRAY_A);

        /* Waiting for an owner, for the "assign" list. */
        $unassigned = $wpdb->get_results(
            "SELECT $cols FROM ($t) t WHERE t.assigned_to = 0 AND t.status <> 'closed'
             ORDER BY t.priority DESC, t.created ASC LIMIT 20",
            ARRAY_A
        );
        $unassigned_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM ($t) t WHERE t.assigned_to = 0 AND t.status <> 'closed'");

        /* Closed by them in range. */
        $range = ''; $params = [$agent_id];
        if ($from) { $range .= ' AND t.closed_at >= %s'; $params[] = $from; }
        if ($to)   { $range .= ' AND t.closed_at <= %s'; $params[] = $to; }

        $closed_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) n,
                    AVG(TIMESTAMPDIFF(MINUTE, COALESCE(t.assigned_at, t.created), t.closed_at)) avg_minutes,
                    SUM(t.priority >= 90) urgent_n
             FROM ($t) t WHERE t.status = 'closed' AND t.closed_by = %d $range",
            $params
        ), ARRAY_A);

        $recent_closed = $wpdb->get_results($wpdb->prepare(
            "SELECT $cols FROM ($t) t WHERE t.status = 'closed' AND t.closed_by = %d $range ORDER BY t.closed_at DESC LIMIT 10",
            $params
        ), ARRAY_A);

        /* Assigned to them in range (any status now). */
        $arange = ''; $aparams = [$agent_id];
        if ($from) { $arange .= ' AND t.assigned_at >= %s'; $aparams[] = $from; }
        if ($to)   { $arange .= ' AND t.assigned_at <= %s'; $aparams[] = $to; }
        $assigned_n = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM ($t) t WHERE t.assigned_to = %d $arange", $aparams
        ));

        $by_priority = ['1' => 0, '10' => 0, '90' => 0];
        $by_status   = ['open' => 0, 'processing' => 0];
        $oldest = null;
        foreach ($queue as $q) {
            $p = (string) $q['priority'];
            $by_priority[$p] = ($by_priority[$p] ?? 0) + 1;
            $by_status[$q['status']] = ($by_status[$q['status']] ?? 0) + 1;
            $since = $q['assigned_at'] ?: $q['created'];
            if ($oldest === null || $since < $oldest) $oldest = $since;
        }

        $last_activity = $wpdb->get_var($wpdb->prepare(
            "SELECT GREATEST(COALESCE(MAX(CASE WHEN t.assigned_to = %d THEN t.assigned_at END), ''), COALESCE(MAX(CASE WHEN t.closed_by = %d THEN t.closed_at END), ''))
             FROM ($t) t",
            $agent_id, $agent_id
        ));

        $this->respond([
            'status' => 'success',
            'agent'  => array_merge([
                'id'         => (int) $agent->ID,
                'name'       => $agent->display_name,
                'email'      => $agent->user_email,
                'phone'      => (string) get_user_meta($agent->ID, 'phone', true),
                'avatar'     => get_avatar_url($agent->user_email, ['size' => 96]),
                'registered' => $agent->user_registered,
                'roles'      => array_values((array) $agent->roles),
                'is_agent'   => in_array(self::AGENT_ROLE, (array) $agent->roles, true),
                'last_activity' => $last_activity !== '' ? $last_activity : null,
            ], $this->details($agent->ID)),
            'queue'          => $queue ?: [],
            'queue_by_priority' => $by_priority,
            'queue_by_status'   => $by_status,
            'oldest_in_queue'   => $oldest,
            'unassigned'        => $unassigned ?: [],
            'unassigned_total'  => $unassigned_total,
            'assigned'          => $assigned_n,
            'closed'            => (int) ($closed_stats['n'] ?? 0),
            'closed_urgent'     => (int) ($closed_stats['urgent_n'] ?? 0),
            'avg_minutes'       => isset($closed_stats['avg_minutes']) && $closed_stats['avg_minutes'] !== null ? (int) round($closed_stats['avg_minutes']) : null,
            'recent_closed'     => $recent_closed ?: [],
        ]);
    }

    /**
     * Save agent fields. Body: id (user id) and any of FIELDS' keys.
     */
    public function update_support_agent_details($request)
    {
        $auth = $this->auth($request);
        if (is_wp_error($auth)) return $auth;

        $body = $this->body($request);
        $id   = absint($body['id'] ?? 0);
        if (!$id || !get_user_by('id', $id)) $this->respond(['status' => 'error', 'message' => 'Support agent not found']);

        if (isset($body['availability']) && !in_array($body['availability'], self::AVAILABILITY, true)) {
            $this->respond(['status' => 'error', 'message' => 'Unknown availability']);
        }
        if (isset($body['max_open']) && $body['max_open'] !== '') {
            if (!is_numeric($body['max_open']) || (int) $body['max_open'] < 1 || (int) $body['max_open'] > 999) {
                $this->respond(['status' => 'error', 'message' => 'Capacity must be 1 to 999 tickets']);
            }
            $body['max_open'] = (string) (int) $body['max_open'];
        }

        foreach (self::FIELDS as $key => [$meta, $max]) {
            if (!array_key_exists($key, $body)) continue;
            $value = $key === 'notes' ? sanitize_textarea_field((string) $body[$key]) : sanitize_text_field((string) $body[$key]);
            update_user_meta($id, $meta, mb_substr($value, 0, $max));
        }

        $this->respond(['status' => 'success', 'id' => $id, 'details' => $this->details($id)]);
    }

    /**
     * Give a ticket an owner, or none. Body: ticket_id, agent_id (0 = unassign).
     * Staff may assign anyone with the support agent role; a support agent may
     * take an unassigned ticket for themselves or unassign their own.
     */
    public function assign_ticket($request)
    {
        $user = $this->auth($request, false);
        if (is_wp_error($user)) return $user;

        $body      = $this->body($request);
        $ticket_id = absint($body['ticket_id'] ?? 0);
        $agent_id  = absint($body['agent_id'] ?? 0);

        $ticket = $ticket_id ? get_post($ticket_id) : null;
        if (!$ticket || $ticket->post_type !== 'ticket') {
            $this->respond(['status' => 'error', 'message' => 'Ticket not found']);
        }

        $current = (int) get_post_meta($ticket_id, 'cstore_assigned_to', true);

        if (!$this->is_staff($user)) {
            $taking  = $agent_id === (int) $user->ID && $current === 0;
            $handing = $agent_id === 0 && $current === (int) $user->ID;
            if (!$taking && !$handing) {
                return new WP_Error('forbidden', 'You can only take unassigned tickets or hand back your own', array('status' => 403));
            }
        }

        if ($agent_id) {
            $agent = get_user_by('id', $agent_id);
            if (!$agent || !in_array(self::AGENT_ROLE, (array) $agent->roles, true)) {
                $this->respond(['status' => 'error', 'message' => 'That user is not a support agent']);
            }
            if ($agent_id !== $current) {
                update_post_meta($ticket_id, 'cstore_assigned_to', $agent_id);
                update_post_meta($ticket_id, 'cstore_assigned_at', current_time('mysql'));
            }
        } else {
            delete_post_meta($ticket_id, 'cstore_assigned_to');
            delete_post_meta($ticket_id, 'cstore_assigned_at');
        }

        $this->respond([
            'status'      => 'success',
            'ticket_id'   => $ticket_id,
            'assigned_to' => $agent_id ?: null,
            'assigned_at' => $agent_id ? get_post_meta($ticket_id, 'cstore_assigned_at', true) : null,
        ]);
    }
}
