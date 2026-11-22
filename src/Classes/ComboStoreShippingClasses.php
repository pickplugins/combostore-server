<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit();

use WP_Error;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Shipping classes: groups of products that cost more to ship (bulky, fragile).
 *
 * Tables (created by ComboStoreDatabase::maybe_upgrade_shipping_schema):
 *   cstore_shipping_classes      { id, slug, name, description, cost, type, priority, status }
 *   cstore_shipping_class_rates  { method_id, class_id, cost, type }
 *
 * A product picks a class with its "Shipping" addon: cstore_products.shipping_class
 * holds the class slug, and a variation's own shipping_class wins over its
 * product's. The slug never changes once created, so renaming a class keeps
 * its products.
 *
 * Pricing, per shipping method: each class in the cart costs the method's rate
 * for that class, or the class's default cost when the method has no rate.
 * `fixed` is the amount itself, `percent` is that share of the cart subtotal.
 * The most expensive class in the cart is charged once per order, on top of
 * the method's own amount. Free shipping and local pickup methods charge no
 * class costs.
 *
 * Routes (POST, combo-store/v2, staff only):
 *   get_shipping_classes       - every class, with how many products use it
 *   save_shipping_class        - create ({ name, ... }) or update ({ id, ... })
 *   delete_shipping_class      - { id }: also its rates, and products / variations
 *                                using it go back to no class
 *   get_shipping_class_rates   - { method_id }: that method's rates
 *   save_shipping_class_rates  - { method_id, rates: [{ class_id, cost, type }] }:
 *                                replaces the method's rates; a class left out
 *                                uses its default cost
 *
 * find_shipping_methods (ComboStoreShipping) takes `items` and `subtotal` and
 * prices each method with with_class_charges() below.
 */
class ComboStoreShippingClasses
{
    const STAFF_ROLES = ['administrator', 'store_manager', 'shop_manager'];
    const TYPES = ['fixed', 'percent'];

    /* Method types that never add class costs. */
    const NO_CLASS_COST_METHODS = ['free_shipping', 'local_pickup'];

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        foreach (['get_shipping_classes', 'save_shipping_class', 'delete_shipping_class', 'get_shipping_class_rates', 'save_shipping_class_rates'] as $route) {
            register_rest_route('combo-store/v2', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $route),
                'permission_callback' => '__return_true',
            ));
        }
    }

    /* ---------------------------------------------------------------- */
    /* Helpers                                                           */
    /* ---------------------------------------------------------------- */

    private static function classes_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_shipping_classes';
    }

    private static function rates_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'cstore_shipping_class_rates';
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

    private function refuse($message)
    {
        $this->respond(['errors' => true, 'status' => 'failed', 'message' => $message]);
    }

    private function staff($request)
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
        $user = $user_id ? get_user_by('id', $user_id) : null;
        if (!$user) {
            return new WP_Error('invalid_token', 'Invalid or expired token', array('status' => 401));
        }
        if (!array_intersect(self::STAFF_ROLES, (array) $user->roles)) {
            return new WP_Error('forbidden', 'You are not allowed to manage shipping classes', array('status' => 403));
        }
        return $user;
    }

    private static function money($value)
    {
        return round(max(0, (float) $value), 2);
    }

    private static function type($value)
    {
        return in_array($value, self::TYPES, true) ? $value : 'fixed';
    }

    /** A row as the dashboard and checkout read it. */
    private static function shape($row)
    {
        return [
            'id'          => (int) $row['id'],
            'slug'        => (string) $row['slug'],
            'name'        => (string) $row['name'],
            'description' => (string) ($row['description'] ?? ''),
            'cost'        => (float) $row['cost'],
            'type'        => self::type($row['type']),
            'priority'    => (int) $row['priority'],
            'status'      => (int) $row['status'],
        ];
    }

    /** A slug from the name, unused by any other class. */
    private static function unique_slug($name)
    {
        global $wpdb;
        $table = self::classes_table();
        $base = ComboStoreProduct::sanitize_shipping_class(sanitize_title($name));
        $base = substr($base !== '' ? $base : 'class', 0, 56);
        $slug = $base;
        $n = 2;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug))) {
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }

    /* ---------------------------------------------------------------- */
    /* Pricing                                                           */
    /* ---------------------------------------------------------------- */

    /**
     * The class slugs of the cart's lines, read from the products and
     * variations themselves (never from the browser).
     *
     * @param array $items [{ product_id, variation_id }]
     */
    public static function cart_class_slugs($items)
    {
        global $wpdb;
        if (!is_array($items)) return [];

        $product_ids = [];
        $variation_ids = [];
        foreach (array_slice($items, 0, 200) as $item) {
            if (!is_array($item)) continue;
            $product_id = absint($item['product_id'] ?? $item['id'] ?? 0);
            if ($product_id) $product_ids[$product_id] = true;
            $variation_id = absint($item['variation_id'] ?? 0);
            if ($variation_id) $variation_ids[$variation_id] = $product_id;
        }
        if (!$product_ids) return [];

        /* Only products with the Shipping addon on have a class. */
        $ids = array_keys($product_ids);
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT product_id, addons, shipping_class FROM {$wpdb->prefix}cstore_products WHERE product_id IN (" . implode(',', array_fill(0, count($ids), '%d')) . ")",
            ...$ids
        ), ARRAY_A);

        $product_class = [];
        foreach ($rows as $row) {
            $addons = json_decode((string) $row['addons'], true);
            if (!is_array($addons) || !in_array('shipping', $addons, true)) continue;
            $product_class[(int) $row['product_id']] = (string) $row['shipping_class'];
        }

        $slugs = [];
        $with_variation = [];
        if ($variation_ids) {
            $vids = array_keys($variation_ids);
            $vrows = $wpdb->get_results($wpdb->prepare(
                "SELECT id, product_id, shipping_class FROM {$wpdb->prefix}cstore_products_variations WHERE id IN (" . implode(',', array_fill(0, count($vids), '%d')) . ")",
                ...$vids
            ), ARRAY_A);
            foreach ($vrows as $vrow) {
                $pid = (int) $vrow['product_id'];
                if (!array_key_exists($pid, $product_class)) continue;
                $with_variation[$pid] = true;
                $slug = (string) $vrow['shipping_class'] !== '' ? (string) $vrow['shipping_class'] : $product_class[$pid];
                if ($slug !== '') $slugs[$slug] = true;
            }
        }

        foreach ($product_class as $pid => $slug) {
            /* A product whose line named a variation was priced by it above. */
            if (isset($with_variation[$pid])) continue;
            if ($slug !== '') $slugs[$slug] = true;
        }

        return array_keys($slugs);
    }

    /**
     * Each active class among $slugs, priced for this method: the method's
     * rate for it, else the class's default. [{ slug, name, cost, type }]
     */
    public static function rates_for_method($method_id, $slugs)
    {
        global $wpdb;
        if (!$slugs) return [];

        $classes = self::classes_table();
        $rates = self::rates_table();
        $placeholders = implode(',', array_fill(0, count($slugs), '%s'));

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT c.slug, c.name, c.cost AS default_cost, c.type AS default_type, r.cost AS rate_cost, r.type AS rate_type
             FROM $classes c
             LEFT JOIN $rates r ON r.class_id = c.id AND r.method_id = %d
             WHERE c.status = 1 AND c.slug IN ($placeholders)",
            (int) $method_id,
            ...$slugs
        ), ARRAY_A);

        $out = [];
        foreach ($rows as $row) {
            $has_rate = $row['rate_cost'] !== null;
            $out[] = [
                'slug' => (string) $row['slug'],
                'name' => (string) $row['name'],
                'cost' => (float) ($has_rate ? $row['rate_cost'] : $row['default_cost']),
                'type' => self::type($has_rate ? $row['rate_type'] : $row['default_type']),
            ];
        }
        return $out;
    }

    /** What one rate adds to an order with this subtotal. */
    public static function rate_charge($rate, $subtotal)
    {
        $cost = (float) ($rate['cost'] ?? 0);
        if ($cost <= 0) return 0.0;
        if (($rate['type'] ?? 'fixed') === 'percent') return round(max(0, (float) $subtotal) * $cost / 100, 2);
        return round($cost, 2);
    }

    /** The most expensive of the rates, charged once. */
    public static function class_charge($rates, $subtotal)
    {
        $highest = 0.0;
        foreach ((array) $rates as $rate) {
            $highest = max($highest, self::rate_charge($rate, $subtotal));
        }
        return $highest;
    }

    /**
     * find_shipping_methods' methods, each with `class_rates` (the cart's
     * classes priced for it), `class_charge` and `total` (amount + charge).
     */
    public static function with_class_charges($methods, $items, $subtotal)
    {
        $slugs = self::cart_class_slugs($items);
        foreach ($methods as $method) {
            $is_object = is_object($method);
            $type = $is_object ? ($method->type ?? '') : ($method['type'] ?? '');
            $id = $is_object ? ($method->id ?? 0) : ($method['id'] ?? 0);
            $amount = (float) ($is_object ? ($method->amount ?? 0) : ($method['amount'] ?? 0));

            $rates = in_array($type, self::NO_CLASS_COST_METHODS, true) ? [] : self::rates_for_method($id, $slugs);
            $charge = self::class_charge($rates, $subtotal);

            if ($is_object) {
                $method->class_rates = $rates;
                $method->class_charge = $charge;
                $method->total = round($amount + $charge, 2);
            }
        }
        return $methods;
    }

    /* ---------------------------------------------------------------- */
    /* Routes                                                            */
    /* ---------------------------------------------------------------- */

    public function get_shipping_classes($request)
    {
        $user = $this->staff($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $table = self::classes_table();
        $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY priority ASC, id ASC", ARRAY_A);

        $counts = [];
        foreach ($wpdb->get_results(
            "SELECT shipping_class, COUNT(*) AS n FROM {$wpdb->prefix}cstore_products WHERE shipping_class IS NOT NULL AND shipping_class <> '' GROUP BY shipping_class",
            ARRAY_A
        ) as $row) {
            $counts[$row['shipping_class']] = (int) $row['n'];
        }

        $classes = array_map(function ($row) use ($counts) {
            $class = self::shape($row);
            $class['product_count'] = $counts[$class['slug']] ?? 0;
            return $class;
        }, $rows);

        $this->respond(['status' => 'success', 'classes' => $classes]);
    }

    public function save_shipping_class($request)
    {
        $user = $this->staff($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $table = self::classes_table();
        $body = $this->body($request);

        $id = absint($body['id'] ?? 0);
        $name = mb_substr(sanitize_text_field($body['name'] ?? ''), 0, 190);
        if ($name === '') $this->refuse('Give the shipping class a name.');

        $data = [
            'name'        => $name,
            'description' => mb_substr(sanitize_text_field($body['description'] ?? ''), 0, 500),
            'cost'        => self::money($body['cost'] ?? 0),
            'type'        => self::type($body['type'] ?? 'fixed'),
            'priority'    => intval($body['priority'] ?? 0),
            'status'      => isset($body['status']) ? (rest_sanitize_boolean($body['status']) ? 1 : 0) : 1,
            'updated_at'  => current_time('mysql'),
        ];
        $format = ['%s', '%s', '%f', '%s', '%d', '%d', '%s'];

        if ($id) {
            if (!$wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE id = %d", $id))) $this->refuse('That shipping class no longer exists.');
            $ok = $wpdb->update($table, $data, ['id' => $id], $format, ['%d']);
        } else {
            $data['slug'] = self::unique_slug($name);
            $data['created_at'] = $data['updated_at'];
            $format[] = '%s';
            $format[] = '%s';
            $ok = $wpdb->insert($table, $data, $format);
            $id = (int) $wpdb->insert_id;
        }

        if ($ok === false) $this->refuse('Could not save the shipping class.');

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
        $this->respond(['status' => 'success', 'message' => 'Shipping class saved', 'class' => self::shape($row)]);
    }

    public function delete_shipping_class($request)
    {
        $user = $this->staff($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $table = self::classes_table();
        $id = absint($this->body($request)['id'] ?? 0);
        $slug = $id ? $wpdb->get_var($wpdb->prepare("SELECT slug FROM $table WHERE id = %d", $id)) : null;
        if (!$slug) $this->refuse('That shipping class no longer exists.');

        $wpdb->delete(self::rates_table(), ['class_id' => $id], ['%d']);
        $wpdb->delete($table, ['id' => $id], ['%d']);

        /* Nothing keeps pointing at a class that is gone. */
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}cstore_products SET shipping_class = NULL WHERE shipping_class = %s", $slug));
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}cstore_products_variations SET shipping_class = '' WHERE shipping_class = %s", $slug));

        $this->respond(['status' => 'success', 'message' => 'Shipping class deleted']);
    }

    public function get_shipping_class_rates($request)
    {
        $user = $this->staff($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $method_id = absint($this->body($request)['method_id'] ?? 0);
        if (!$method_id) $this->refuse('No shipping method given.');

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT class_id, cost, type FROM " . self::rates_table() . " WHERE method_id = %d",
            $method_id
        ), ARRAY_A);

        $rates = array_map(function ($row) {
            return ['class_id' => (int) $row['class_id'], 'cost' => (float) $row['cost'], 'type' => self::type($row['type'])];
        }, $rows);

        $this->respond(['status' => 'success', 'rates' => $rates]);
    }

    public function save_shipping_class_rates($request)
    {
        $user = $this->staff($request);
        if (is_wp_error($user)) return $user;

        global $wpdb;
        $body = $this->body($request);
        $method_id = absint($body['method_id'] ?? 0);
        if (!$method_id || !$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}cstore_shipping_methods WHERE id = %d", $method_id))) {
            $this->refuse('That shipping method no longer exists.');
        }

        $class_ids = array_map('intval', $wpdb->get_col("SELECT id FROM " . self::classes_table()));
        $table = self::rates_table();

        $wpdb->delete($table, ['method_id' => $method_id], ['%d']);

        $saved = 0;
        foreach ((array) ($body['rates'] ?? []) as $rate) {
            if (!is_array($rate)) continue;
            $class_id = absint($rate['class_id'] ?? 0);
            if (!in_array($class_id, $class_ids, true)) continue;
            /* A blank cost means "use the class's default": no row. */
            if (!isset($rate['cost']) || $rate['cost'] === '' || $rate['cost'] === null) continue;

            $wpdb->replace($table, [
                'method_id' => $method_id,
                'class_id'  => $class_id,
                'cost'      => self::money($rate['cost']),
                'type'      => self::type($rate['type'] ?? 'fixed'),
            ], ['%d', '%d', '%f', '%s']);
            $saved++;
        }

        $this->respond(['status' => 'success', 'message' => 'Class costs saved', 'saved' => $saved]);
    }
}
