<?php
if (! defined('ABSPATH')) exit;  // if direct access



add_action("combo_store_activation", "combo_store_add_role_affiliates");

function combo_store_add_role_affiliates()
{

  add_role(
    'affiliate',
    'Affiliates',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );

  add_role(
    'customer',
    'Customer',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );
  add_role(
    'support_agent',
    'Support Agent',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );

  add_role(
    'supplier',
    'Supplier',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );

  add_role(
    'seller',
    'Seller',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );

  add_role(
    'rider',
    'Rider',
    [
      'read' => true,
      'edit_posts' => false,
      'publish_posts' => false,
      'manage_options' => false,
    ]
  );
}
