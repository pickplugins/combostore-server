<?php
if (! defined('ABSPATH')) exit;  // if direct access

/**
 * Process scheduled orders.
 *
 * Finds all orders whose scheduled_at <= current time
 * and updates their status.
 *
 * @return int Number of updated orders.
 */
function cstore_process_scheduled_ordersX()
{

  error_log('cstore_process_scheduled_orders called at ' . current_time('mysql'));


  global $wpdb;

  $table_orders      = $wpdb->prefix . 'cstore_orders';
  $table_orders_meta = $wpdb->prefix . 'cstore_orders_meta';

  $now = current_time('mysql');

  // Get scheduled order IDs
  // $order_ids = $wpdb->get_col(
  //   $wpdb->prepare(
  //     "
  //           SELECT DISTINCT object_id
  //           FROM {$table_orders_meta}
  //           WHERE meta_key = %s
  //             AND meta_value <= %s
  //           ",
  //     'scheduled_at',
  //     $now
  //   )
  // );


  $cache_key = 'scheduled_order_ids_' . md5($now);
  $cache_group = 'orders';

  $order_ids = wp_cache_get($cache_key, $cache_group);

  if (false === $order_ids) {
    $order_ids = $wpdb->get_col(
      $wpdb->prepare(
        "
			SELECT DISTINCT object_id
			FROM {$table_orders_meta}
			WHERE meta_key = %s
				AND meta_value <= %s
			",
        'scheduled_at',
        $now
      )
    );

    wp_cache_set($cache_key, $order_ids, $cache_group, MINUTE_IN_SECONDS);
  }

  if (empty($order_ids)) {
    return 0;
  }

  $placeholders = implode(',', array_fill(0, count($order_ids), '%d'));

  // Update orders
  $sql = $wpdb->prepare(
    "
        UPDATE {$table_orders}
        SET
            status = %s,
            created_at = %s,
            updated_at = %s
        WHERE id IN ($placeholders)
        ",
    array_merge(
      [
        'processing',
        $now,
        $now,
      ],
      $order_ids
    )
  );

  return $wpdb->query($sql);
}
