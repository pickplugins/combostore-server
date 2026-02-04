<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>
<p>Dear {customer_name},</p>

<p>Thank you! Your comment has been submitted successfully.
  Our team will review it and publish it shortly.</p>
<p>Best regards,</p>
<p>{site_name}</p>
<?php


$templates_data_html['comment-submitted'] = ob_get_clean();
