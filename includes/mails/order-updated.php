<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>
<p>Dear <strong>{customer_name}</strong>,</p>
<p>We wanted to let you know that your order <strong>#{order_number}</strong> is currently <strong>{status}</strong>.</p>
<p>We’ll keep you updated and notify you once it’s updated.</p>
<p>Thank you for your patience!</p>
<p>Best regards,</p>
<p>{site_name}</p>
<p>Email: {contact_email}</p>
<p>Website: <a href="{site_url}">{site_url}</a></p>
<img src="{site_logo_url}" width="160" />

<?php


$templates_data_html['order-updated'] = ob_get_clean();
