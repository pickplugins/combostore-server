<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>
<p>Dear <strong>{customer_name}</strong>,</p>
<p>Thank you for your order!</p>
<p>We’ve received your order <strong>#{order_number}</strong> on <strong>{date}</strong>.</p>
<p><strong>Order Summary:</strong></p>
<ul>
  {list_of_items}
  <li>Total: <strong>{total_amount}</strong></li>
</ul>
<p>We’ll send you another update once your order has been shipped.</p>
<p>If you have any questions, please contact us at {contact_phone}.</p>
<p>Best regards,</p>
<p>{site_name}</p>
<p>Email: {contact_email}</p>
<p>Website: <a href="{site_url}">{site_url}</a></p>
<img src="{site_logo_url}" width="160" />
<?php


$templates_data_html['order-created'] = ob_get_clean();
