<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreMails;

add_action('combo_store_order_created', 'combo_store_order_created');

function combo_store_order_created($prams)
{




  $billing_name    = isset($prams['billing_name']) ? sanitize_text_field($prams['billing_name']) : '';
  $billing_email    = isset($prams['billing_email']) ? sanitize_email($prams['billing_email']) : '';
  $order_id    = isset($prams['order_id']) ? sanitize_text_field($prams['order_id']) : '';
  $cartItems    = isset($prams['cartItems']) ? stripslashes_deep($prams['cartItems']) : [];
  $total_amount    = isset($prams['total_amount']) ? sanitize_text_field($prams['total_amount']) : 0;
  $email_from = "contact@kidobazar.com";
  $email_from_name = "KidoBazar";
  $reply_to = "contact@kidobazar.com";
  $reply_to_name = "KidoBazar";
  $email_bcc = "public.nurhasan@gmail.com";

  include combo_store_plugin_dir . 'includes/mails/order-created.php';

  $email_body = $templates_data_html['order-created'];


  $subject = "Order Created #$order_id";

  $site_name = get_bloginfo('name');
  $site_description = get_bloginfo('description');
  $site_url = get_bloginfo('url');
  $site_logo_url = "https://kidobazar.com/_next/image?url=%2Flogo-h.png&w=256&q=75";
  //  $site_logo_url = wp_get_attachment_url($logo_id);



  $cartItems_html = '';

  if (!empty($cartItems)) {

    foreach ($cartItems as $cartItem) {

      $product_name  = isset($cartItem['title']) ? sanitize_text_field($cartItem['title']) : '';
      $quantity      = isset($cartItem['quantity']) ? intval($cartItem['quantity']) : 1;

      $cartItems_html .= "<li>$product_name X $quantity</li>";
    }
  }



  $vars = array(
    '{site_name}' => "KidoBazar",
    '{site_description}' => esc_html($site_description),
    '{site_url}' => "https://kidobazar.com/",
    '{site_logo_url}' => "https://kidobazar.com/_next/image?url=%2Flogo-h.png&w=256&q=75",

    '{customer_name}' => esc_html($billing_name),
    '{order_number}' => esc_html($order_id),
    '{date}' =>  gmdate('d-m-Y'),
    '{list_of_items}' => $cartItems_html,
    '{total_amount}' => $total_amount . "Tk",
    '{contact_phone}' => "01537034053",
    '{contact_email}' => "public.nurhasan@gmail.com",


  );




  $mail_prams = [
    'email_to' => $billing_email,
    'email_bcc' => $email_bcc,
    'email_from' => $email_from,
    'email_from_name' => $email_from_name,
    'reply_to' => $reply_to,
    'reply_to_name' => $reply_to_name,
    'subject' => $subject,
    'html' =>  strtr($email_body, $vars),
    'attachments' => [],
  ];



  $ComboStoreMails = new ComboStoreMails();
  $status = $ComboStoreMails->send_email($mail_prams);
}



function combo_store_order_updated($prams)
{




  $status    = isset($prams['status']) ? sanitize_text_field($prams['status']) : '';
  $payment_method    = isset($prams['payment_method']) ? sanitize_text_field($prams['payment_method']) : '';
  $payment_status    = isset($prams['payment_status']) ? sanitize_text_field($prams['payment_status']) : '';
  $transaction_id    = isset($prams['transaction_id']) ? sanitize_text_field($prams['transaction_id']) : '';
  $shipping_method    = isset($prams['shipping_method']) ? sanitize_text_field($prams['shipping_method']) : '';

  $billing_name    = isset($prams['billing_name']) ? sanitize_text_field($prams['billing_name']) : '';
  $billing_email    = isset($prams['billing_email']) ? sanitize_email($prams['billing_email']) : '';
  $order_id    = isset($prams['order_id']) ? sanitize_text_field($prams['order_id']) : '';
  $cartItems    = isset($prams['cartItems']) ? stripslashes_deep($prams['cartItems']) : [];
  $total_amount    = isset($prams['total_amount']) ? sanitize_text_field($prams['total_amount']) : 0;
  $email_from = "public.nurhasan@gmail.com";
  $email_from_name = "KidoBazar";
  $reply_to = "public.nurhasan@gmail.com";
  $reply_to_name = "KidoBazar";
  $email_bcc = "public.nurhasan@gmail.com";





  if ($status == 'refunded') {
    $subject = "Order Refunded #$order_id";

    include combo_store_plugin_dir . 'includes/mails/order-refunded.php';

    $email_body = $templates_data_html['order-refunded'];
  } elseif ($status == 'canceled') {
    $subject = "Order Canceled #$order_id";

    include combo_store_plugin_dir . 'includes/mails/order-canceled.php';

    $email_body = $templates_data_html['order-canceled'];
  } else {
    $subject = "Order Updated #$order_id - $status";

    include combo_store_plugin_dir . 'includes/mails/order-updated.php';

    $email_body = $templates_data_html['order-updated'];
  }




  $site_name = get_bloginfo('name');
  $site_description = get_bloginfo('description');
  $site_url = get_bloginfo('url');
  $site_logo_url = "https://kidobazar.com/_next/image?url=%2Flogo-h.png&w=256&q=75";
  //  $site_logo_url = wp_get_attachment_url($logo_id);



  $cartItems_html = '';

  if (!empty($cartItems)) {

    foreach ($cartItems as $cartItem) {

      $product_name  = isset($cartItem['title']) ? sanitize_text_field($cartItem['title']) : '';
      $quantity      = isset($cartItem['quantity']) ? intval($cartItem['quantity']) : 1;

      $cartItems_html .= "<li>$product_name X $quantity</li>";
    }
  }



  $vars = array(
    '{site_name}' => "KidoBazar",
    '{site_description}' => esc_html($site_description),
    '{site_url}' => esc_url_raw($site_url),
    '{site_logo_url}' => esc_url_raw($site_logo_url),

    '{customer_name}' => esc_html($billing_name),
    '{order_number}' => esc_html($order_id),
    '{date}' =>  gmdate('d-m-Y'),
    '{list_of_items}' => $cartItems_html,
    '{payment_method}' => $payment_method,
    '{total_amount}' => $total_amount . "Tk",
    '{status}' => $status,
    '{contact_phone}' => "01537034053",
    '{contact_email}' => "public.nurhasan@gmail.com",


  );



  $mail_prams = [
    'email_to' => $billing_email,
    'email_bcc' => $email_bcc,
    'email_from' => $email_from,
    'email_from_name' => $email_from_name,
    'reply_to' => $reply_to,
    'reply_to_name' => $reply_to_name,
    'subject' => $subject,
    'html' =>  strtr($email_body, $vars),
    'attachments' => [],
  ];




  $ComboStoreMails = new ComboStoreMails();
  $status = $ComboStoreMails->send_email($mail_prams);
}










add_action('combo_store_order_updated', 'combo_store_order_updated');
