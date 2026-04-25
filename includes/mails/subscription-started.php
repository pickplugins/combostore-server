<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>

<p>Dear {customer_name},</p>
<p>Your subscription to <strong>{order_id}</strong> has started on <strong>{start_date}</strong>.
  Next billing date: <strong>{next_billing_date}</strong>.</p>
<p>Thank you for subscribing! We’re excited to have you with us.</p>
<p>Best regards,</p>
<p>{site_name}</p>


<?php


$templates_data_html['subscription-started'] = ob_get_clean();
