<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access


/*
	Downloadable files a customer bought.

	A product's files are its `downloads` attribute (cstore_products.downloads,
	set in the dashboard from the media library - normally restricted uploads in
	uploads/combostore). A customer may download them for each of their orders
	that is completed or paid.

	The storefront cannot attach the login token to a plain link, so each
	download link is signed: it names the user, order and file, expires after
	LINK_TTL, and carries an HMAC keyed with the site's auth salt. Opening it
	re-checks everything - the order still belongs to that user and is still
	completed/paid, and the file is still one of the order's products'
	downloads - before the file is streamed.
*/
class ComboStoreDownloads
{
	/* How long a download link works, in seconds. */
	const LINK_TTL = HOUR_IN_SECONDS;

	public function __construct() {}


	/**
	 * Completed or paid. Same rule that issues license keys.
	 */
	function order_is_downloadable($order)
	{
		$order = (array) $order;
		$status = isset($order['status']) ? $order['status'] : '';
		$payment_status = isset($order['payment_status']) ? $order['payment_status'] : '';

		$downloadable = $status === 'completed' || $payment_status === 'paid';

		return (bool) apply_filters('combo_store_order_is_downloadable', $downloadable, $order);
	}


	/**
	 * A product's download files: [{ id, title }]. Entries without a media id
	 * (downloads picked as products before they were files) are skipped.
	 */
	function get_product_downloads($product_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cstore_products';

		$raw = $wpdb->get_var($wpdb->prepare("SELECT downloads FROM $table WHERE product_id = %d LIMIT 1", $product_id));
		$downloads = $raw ? json_decode($raw, true) : [];

		$files = [];
		foreach ((array) $downloads as $download) {
			$file_id = isset($download['id']) ? absint($download['id']) : 0;
			if (!$file_id || get_post_type($file_id) !== 'attachment') continue;

			$files[$file_id] = [
				'id'    => $file_id,
				'title' => !empty($download['title']) ? sanitize_text_field($download['title']) : get_the_title($file_id),
			];
		}

		return array_values($files);
	}


	/**
	 * The order's line items resolved to parent products (older orders saved
	 * a variation's id as the product id).
	 *
	 * @return int[] Distinct product ids.
	 */
	function get_order_product_ids($order_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cstore_order_items';

		$items = $wpdb->get_results($wpdb->prepare("SELECT product_id, variation_id FROM $table WHERE order_id = %d", $order_id), ARRAY_A);
		$licenses = new ComboStoreLicenses();

		$product_ids = [];
		foreach ((array) $items as $item) {
			$product_id = absint($item['product_id']);
			$variation_id = absint($item['variation_id']);

			$variation = $variation_id ? $licenses->get_variation($variation_id) : null;
			if ($variation && absint($variation['product_id'])) {
				$product_id = absint($variation['product_id']);
			}

			if ($product_id) $product_ids[$product_id] = $product_id;
		}

		return array_values($product_ids);
	}


	/**
	 * Downloads for one order, with signed links for $user_id.
	 *
	 * @return array [{ order_id, product_id, product_title, file_id, title, mime, size, extension, url, expires }]
	 */
	function get_order_downloads($order, $user_id)
	{
		$order = (array) $order;
		$order_id = absint($order['id']);

		if (!$this->order_is_downloadable($order)) return [];

		$rows = [];
		$seen = [];

		foreach ($this->get_order_product_ids($order_id) as $product_id) {
			foreach ($this->get_product_downloads($product_id) as $file) {
				if (isset($seen[$file['id']])) continue;
				$seen[$file['id']] = true;

				$path = get_attached_file($file['id']);
				$link = $this->signed_url($user_id, $order_id, $file['id']);

				$rows[] = [
					'order_id'      => $order_id,
					'product_id'    => $product_id,
					'product_title' => get_the_title($product_id),
					'file_id'       => $file['id'],
					'title'         => $file['title'],
					'mime'          => get_post_mime_type($file['id']),
					'size'          => ($path && file_exists($path)) ? filesize($path) : null,
					'extension'     => $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '',
					'restricted'    => (bool) get_post_meta($file['id'], '_combostore_secure', true),
					'url'           => $link['url'],
					'expires'       => $link['expires'],
				];
			}
		}

		return $rows;
	}


	/**
	 * Downloads for several of a user's orders, keyed by order id. Orders that
	 * are not theirs are left out (staff may read any).
	 */
	function get_downloads_for_orders($order_ids, $user_id, $is_staff = false)
	{
		global $wpdb;
		$table = $wpdb->prefix . 'cstore_orders';

		$order_ids = array_slice(array_values(array_filter(array_map('absint', (array) $order_ids))), 0, 100);
		if (empty($order_ids)) return [];

		$placeholders = implode(', ', array_fill(0, count($order_ids), '%d'));
		$sql = "SELECT id, userid, status, payment_status FROM $table WHERE id IN ($placeholders)";
		$params = $order_ids;
		if (!$is_staff) {
			$sql .= " AND userid = %d";
			$params[] = absint($user_id);
		}

		$orders = $wpdb->get_results($wpdb->prepare($sql, $params), ARRAY_A);

		$result = [];
		foreach ((array) $orders as $order) {
			$downloads = $this->get_order_downloads($order, $user_id);
			if (!empty($downloads)) $result[$order['id']] = $downloads;
		}

		return $result;
	}



	/* ---------- Signed links ---------- */


	function signature($user_id, $order_id, $file_id, $expires)
	{
		return hash_hmac('sha256', implode('|', [absint($user_id), absint($order_id), absint($file_id), absint($expires)]), wp_salt('auth'));
	}


	function signed_url($user_id, $order_id, $file_id)
	{
		$expires = time() + self::LINK_TTL;

		$url = add_query_arg([
			'u'       => absint($user_id),
			'order'   => absint($order_id),
			'file'    => absint($file_id),
			'expires' => $expires,
			'sig'     => $this->signature($user_id, $order_id, $file_id, $expires),
		], rest_url('combo-store/v2/download_file'));

		return ['url' => $url, 'expires' => $expires];
	}


	/**
	 * Check a download link and that the file may still be downloaded.
	 *
	 * @return int|\WP_Error The attachment id.
	 */
	function verify_link($args)
	{
		global $wpdb;

		$user_id  = isset($args['u']) ? absint($args['u']) : 0;
		$order_id = isset($args['order']) ? absint($args['order']) : 0;
		$file_id  = isset($args['file']) ? absint($args['file']) : 0;
		$expires  = isset($args['expires']) ? absint($args['expires']) : 0;
		$sig      = isset($args['sig']) ? (string) $args['sig'] : '';

		if (!$order_id || !$file_id || !$expires || $sig === '') {
			return new \WP_Error('invalid_link', 'This download link is incomplete.', ['status' => 400]);
		}
		if (!hash_equals($this->signature($user_id, $order_id, $file_id, $expires), $sig)) {
			return new \WP_Error('invalid_link', 'This download link is not valid.', ['status' => 403]);
		}
		if ($expires < time()) {
			return new \WP_Error('link_expired', 'This download link has expired. Open your orders again to get a new one.', ['status' => 410]);
		}

		$order = $wpdb->get_row($wpdb->prepare("SELECT id, userid, status, payment_status FROM {$wpdb->prefix}cstore_orders WHERE id = %d", $order_id), ARRAY_A);

		$user = $user_id ? get_user_by('id', $user_id) : null;
		$is_staff = $user && (in_array('administrator', (array) $user->roles, true) || in_array('store_manager', (array) $user->roles, true));

		if (!$order || (!$is_staff && absint($order['userid']) !== $user_id)) {
			return new \WP_Error('forbidden', 'This order is not yours.', ['status' => 403]);
		}
		if (!$this->order_is_downloadable($order)) {
			return new \WP_Error('forbidden', 'Downloads are available once the order is paid.', ['status' => 403]);
		}

		$allowed = false;
		foreach ($this->get_order_product_ids($order_id) as $product_id) {
			foreach ($this->get_product_downloads($product_id) as $file) {
				if ($file['id'] === $file_id) $allowed = true;
			}
		}
		if (!$allowed) {
			return new \WP_Error('forbidden', 'This file is no longer part of your order.', ['status' => 403]);
		}

		return $file_id;
	}


	/**
	 * Send the file as an attachment and stop. Read in chunks so large files
	 * do not have to fit in memory.
	 */
	function stream($file_id)
	{
		$path = get_attached_file($file_id);
		if (!$path || !is_file($path) || !is_readable($path)) {
			return new \WP_Error('not_found', 'The file is missing on the server.', ['status' => 404]);
		}

		$name = sanitize_file_name(basename($path));
		/* Drop the random prefix restricted uploads get, so the buyer sees the real name. */
		if (get_post_meta($file_id, '_combostore_secure', true)) {
			$name = preg_replace('/^[A-Za-z0-9]{12}-/', '', $name);
		}

		do_action('combo_store_file_downloaded', $file_id);

		while (ob_get_level()) ob_end_clean();
		nocache_headers();
		header('Content-Type: ' . (get_post_mime_type($file_id) ?: 'application/octet-stream'));
		header('Content-Disposition: attachment; filename="' . str_replace('"', '', $name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
		header('Content-Length: ' . filesize($path));
		header('X-Content-Type-Options: nosniff');

		$handle = fopen($path, 'rb');
		while (!feof($handle)) {
			echo fread($handle, 1024 * 1024);
			flush();
		}
		fclose($handle);
		exit;
	}
}
