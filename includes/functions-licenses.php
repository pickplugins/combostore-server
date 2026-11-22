<?php

use ComboStore\Classes\ComboStoreOrders;

if (!defined('ABSPATH')) exit;  // if direct access


/*
	License keys and subscriptions start when an order is completed or paid.

	ComboStoreOrders::create_order, update_order and bulk_update_orders call
	ComboStoreOrders::fulfil_order directly. Payment gateways that write the
	order row themselves (PayPal's settle_capture) announce it with
	`combo_store_order_paid`, so they are covered here. Fulfilling is idempotent,
	so a gateway also going through update_order does not double-issue.
*/
add_action('combo_store_order_paid', 'combo_store_issue_licenses_on_paid', 20);

function combo_store_issue_licenses_on_paid($args)
{
	$order_id = isset($args['order_id']) ? absint($args['order_id']) : 0;

	/* A capture that did not match the order total is held for review. */
	if (!$order_id || !empty($args['needs_review'])) return;

	$ComboStoreOrders = new ComboStoreOrders();
	$ComboStoreOrders->fulfil_order($order_id);
}
