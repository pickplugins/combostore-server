<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>

<p>Dear {customer_name},</p>
<p>Thank you for registering with <strong>{site_name}</strong>.
  Your account has been created successfully, and you can now log in using your registered email.</p>
<p>We’re excited to have you with us!</p>
<p>Best regards,</p>
<p>{site_name}</p>










<?php


$templates_data_html['user-registered'] = ob_get_clean();
