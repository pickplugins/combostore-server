<?php
if (! defined('ABSPATH')) exit;  // if direct access

ob_start();
?>
<p>Dear {customer_name},</p>

<p>Thank you for your review! It has been submitted successfully and will be visible shortly.</p>
<p>We appreciate your feedback!</p>
<p>Best regards,</p>
<p>{site_name}</p>

<?php


$templates_data_html['review-submitted'] = ob_get_clean();
