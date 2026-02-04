<?php
if (! defined('ABSPATH')) exit;  // if direct access




add_action("combo_store_purchased", "combo_store_purchased_email_subscribe",);

function combo_store_purchased_email_subscribe($prams)
{
  $email     = isset($prams['email']) ? sanitize_email($prams['email']) : "";
  $name     = isset($prams['name']) ? sanitize_text_field($prams['name']) : "";
  $list = "1146381";


  $args = [];
  $args['email'] = $email;
  $args['name'] = $name;
  $args['list'] = $list;

  combo_store_email_subscribe($args);
}
