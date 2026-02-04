<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>

<p>Dear {customer_name},</p>
<p>Your subscription to <strong>{order_id}</strong> has been canceled effective <strong>{cancellation_Date}</strong>.
  You will continue to have access until <strong>[End Date, if applicable]</strong>.</p>
<p>Thank you for being with us, and we hope to see you again in the future.</p>
<p>Best regards,</p>
<p>{site_name}</p>

<?php


$templates_data_html['subscription-canceled'] = ob_get_clean();
