<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>
<p>Dear <strong>{customer_name}</strong>,</p>
<p>Your refund for order <strong>{order_number}</strong> has been processed successfully.</p>
<p>Amount refunded: <strong>{total_amount}</strong>
<p>Payment method: <strong>{payment_method}</strong></p>
<p>Please allow a few business days for the amount to reflect in your account.</p>
<p>If you have any questions, feel free to contact us at {contact_phone}.</p>
<p>Best regards,</p>
<p>{site_name}</p>
<p>Email: {contact_email}</p>
<p>Website: <a href="{site_url}">{site_url}</a></p>
<img src="{site_logo_url}" width="160" />
<?php


$templates_data_html['order-refunded'] = ob_get_clean();
