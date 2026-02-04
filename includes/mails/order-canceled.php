<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>

<p>Dear <strong>{customer_name}</strong>,</p>
<p>Your order <strong>#{order_number}</strong> has been canceled as requested.
  If payment was made, the refund will be processed within <strong>3</strong> days.</p>
<p>For any questions, please contact us at <strong>{contact_phone}</strong>.</p>
<p>Best regards,</p>
<p>{site_name}</p>
<p>Email: {contact_email}</p>
<p>Website: <a href="{site_url}">{site_url}</a></p>
<img src="{site_logo_url}" width="160" />

<?php


$templates_data_html['order-canceled'] = ob_get_clean();
