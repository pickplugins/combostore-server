<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>

<p>Dear {customer_name},</p>
<p>Your next delivery for <strong>{order_id}</strong> is scheduled on <strong>{delivery_date}</strong>.
  We’ll notify you again once it has been shipped.</p>
<p>Thank you for being with us!</p>
<p>Best regards,</p>
<p>{site_name}</p>

<?php


$templates_data_html['subscription-updated'] = ob_get_clean();
