<?php
if (! defined('ABSPATH')) exit;  // if direct access

use EmailValidation\Classes\EmailVerifier;
use EmailValidation\Classes\EmailValidationExport;
use EmailValidation\Classes\EmailValidationCredits;
use EmailValidation\Classes\EmailValidationSpammers;








function maybe_json_decode($value)
{
  if (!is_string($value)) {
    return $value;
  }

  $trimmed = trim($value);

  // Quick check: valid JSON must start with [ or {
  if (($trimmed[0] === '{' || $trimmed[0] === '[')) {
    $decoded = json_decode($trimmed, true);
    if (json_last_error() === JSON_ERROR_NONE) {
      return $decoded;
    }
  }

  return $value; // Return as-is if not valid JSON
}

add_shortcode("quick_verify_email", "quick_verify_email");

function quick_verify_email()
{
  $EmailVerifier = new EmailVerifier();
  //$response = $EmailVerifier->verifyEmail("public.nurhasan@gmail.com");
  $response = $EmailVerifier->checkMxAndDnsRecord("support5@combodevs.com");
  print_r($response);

  ob_start();
  $email = 'support@w3schools.com';

  $domain = substr(strrchr($email, "@"), 1);
  print_r($email);


  //print_r(checkdnsrr($domain, "MX"));


  // $fp = fsockopen($email, 80, $errno, $errstr, 20);
  // if (!$fp) {
  //   echo "$errstr ($errno)<br>";
  // } else {
  //   $out = "GET / HTTP/1.1\r\n";
  //   $out .= "Host: www.w3schools.com\r\n";
  //   $out .= "Connection: Close\r\n\r\n";
  //   fwrite($fp, $out);
  //   while (!feof($fp)) {
  //     echo fgets($fp, 128);
  //   }
  //   fclose($fp);
  // }









  // // Get MX records for the domain
  // $mxRecords = [];
  // if (!getmxrr($domain, $mxRecords)) {

  //   //error_log(serialize($mxRecords));

  //   return false;
  // }


  // // Connect to the first MX server
  // $mxHost = $mxRecords[0];
  // $connection = @fsockopen($mxHost, 25, $errno, $errstr, 10);

  // error_log(serialize($connection));


  // if (!$connection) {
  //   return false;
  // }





?>
  <pre>
<?php
  //print_r(dns_get_record("w3schools.com", DNS_MX));
  //echo var_export($response, true);
?>
</pre>
<?php

  return ob_get_clean();
}


// add_shortcode("export_email_validation_data", "export_email_validation_data");

// function export_email_validation_data()
// {
//   $EmailValidationExport = new EmailValidationExport();
//   $EmailValidationExport->export_email_validation_data(5);
// }


function custom_rest_api_headers()
{
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
  header("Access-Control-Allow-Headers: Authorization, Content-Type");
}
add_action('rest_api_init', 'custom_rest_api_headers', 15);


function allow_cors_headers()
{
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  header("Access-Control-Allow-Headers: Authorization, Content-Type");
}
add_action('rest_api_init', function () {
  add_action('rest_pre_serve_request', 'allow_cors_headers');
});



function handle_preflight()
{
  if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    exit;
  }
}
add_action('init', 'handle_preflight');


// function allow_jwt_cors_ev()
// {
//   header("Access-Control-Allow-Origin: *");
//   header("Access-Control-Allow-Headers: Authorization, Content-Type");
// }
// add_action('init', 'allow_jwt_cors_ev');



function allow_jwt_corsfsd()
{
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: Authorization, Content-Type");
}
add_action('init', 'allow_jwt_corsfsd');



function limit_api_rate_db($response)
{
  global $wpdb;
  $ip = $_SERVER['REMOTE_ADDR'];
  $limit = 60; // Max requests allowed
  $time_window = 60; // Time window in seconds (1 minute)
  $table_name = $wpdb->prefix . 'cstore_api_rate_limits';

  // Remove expired requests
  $wpdb->query($wpdb->prepare(
    "DELETE FROM $table_name WHERE request_time < NOW() - INTERVAL %d SECOND",
    $time_window
  ));

  // Count recent requests from the IP
  $request_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE ip = %s AND request_time >= NOW() - INTERVAL %d SECOND",
    $ip,
    $time_window
  ));

  if ($request_count >= $limit) {
    return new WP_Error('rate_limit_exceeded', __('Too many requests. Please try again later.'), ['status' => 429]);
  }

  // Insert new request log
  $wpdb->insert($table_name, [
    'ip' => $ip,
    'request_time' => current_time('mysql')
  ]);

  return $response;
}

//add_filter('rest_pre_dispatch', 'limit_api_rate_db', 10, 2);




function mergeCSVByEmail($file1, $file2, $outputFile)
{
  $emails = []; // To store unique email records

  // Read first CSV file
  if (($handle1 = fopen($file1, "r")) !== false) {
    $header1 = fgetcsv($handle1); // Read the header row
    while (($row = fgetcsv($handle1)) !== false) {
      $email = strtolower(trim($row[0])); // Normalize email
      $emails[$email] = array_combine($header1, $row); // Store data with email as key
    }
    fclose($handle1);
  }

  // Read second CSV file
  if (($handle2 = fopen($file2, "r")) !== false) {
    $header2 = fgetcsv($handle2);
    while (($row = fgetcsv($handle2)) !== false) {
      $email = strtolower(trim($row[0])); // Normalize email
      $data = array_combine($header2, $row);

      // If email exists, merge data
      if (isset($emails[$email])) {
        $emails[$email] = array_merge($emails[$email], $data);
      } else {
        $emails[$email] = $data;
      }
    }
    fclose($handle2);
  }

  // Combine headers from both files
  $allHeaders = array_unique(array_merge(array_keys(reset($emails))));

  // Write to output CSV
  $output = fopen($outputFile, "w");
  fputcsv($output, $allHeaders); // Write header row
  foreach ($emails as $row) {
    fputcsv($output, array_merge(array_fill_keys($allHeaders, ""), $row));
  }
  fclose($output);

  echo "Merged CSV saved as $outputFile";
}

// Example usage
//mergeCSVByEmail("file1.csv", "file2.csv", "merged_output.csv");


add_action('email_validation_order_created', 'email_validation_order_created');

function email_validation_order_created($data)
{

  $total = isset($data['total']) ? $data['total'] : '';
  $userid = isset($data['userid']) ? $data['userid'] : '';






  $source     = 'order'; // codes. redeem, 


  if ($total === '$10.00') {
    $amount     = 10000;
  }
  if ($total === '$18.00') {
    $amount     = 20000;
  }
  if ($total === '$45.00') {
    $amount     = 50000;
  }
  if ($total === '$90.00') {
    $amount     = 100000;
  }



  $credit_type = 'instant';
  $notes = '';

  $EmailValidationCredits = new EmailValidationCredits();
  $response = $EmailValidationCredits->add_credit($userid, $amount, $credit_type, $source, $notes);
}



// function update_woocommerce_prices()
// {
//   if (!class_exists('WooCommerce')) {
//     return 'WooCommerce is not installed.';
//   }

//   $args = array(
//     'post_type'      => 'product',
//     'posts_per_page' => -1,
//     'fields'         => 'ids',
//   );

//   $product_ids = get_posts($args);

//   if (empty($product_ids)) {
//     return 'No products found.';
//   }

//   foreach ($product_ids as $product_id) {
//     $current_price = get_post_meta($product_id, '_price', true);
//     $current_regular_price = get_post_meta($product_id, '_regular_price', true);
//     $current_sale_price = get_post_meta($product_id, '_sale_price', true);

//     if (!empty($current_price)) {
//       $new_price = round($current_price / 85, 2);
//       update_post_meta($product_id, '_price', $new_price);
//     }

//     if (!empty($current_regular_price)) {
//       $new_regular_price = round($current_regular_price / 85, 2);
//       update_post_meta($product_id, '_regular_price', $new_regular_price);
//     }

//     if (!empty($current_sale_price)) {
//       $new_sale_price = round($current_sale_price / 85, 2);
//       update_post_meta($product_id, '_sale_price', $new_sale_price);
//     }
//   }

//   return 'Product prices updated successfully.';
// }

// add_shortcode('update_prices', 'update_woocommerce_prices');







// function update_spamers()
// {
// 	if (!class_exists('WooCommerce')) {
// 		return 'WooCommerce is not installed.';
// 	}

// 	$EmailValidationSpammers = new EmailValidationSpammers();

// 	$args = array(
// 		'post_type'      => 'spammer',
// 		'post_status'      => 'publish',
// 		'posts_per_page' => -1,
// 		'fields'         => 'ids',
// 	);

// 	$product_ids = get_posts($args);

// 	if (empty($product_ids)) {
// 		return 'No products found.';
// 	}

// 	foreach ($product_ids as $product_id) {
// 		$email = get_post_meta($product_id, 'email', true);
// 		$report_count = get_post_meta($product_id, 'report_count', true);
// 		$ref_domain = get_post_meta($product_id, 'ref_domain', true);
// 		$date_created = get_post_meta($product_id, 'date_created', true);
// 		$spammer_level = get_post_meta($product_id, 'spammer_level', true);

// 		//error_log($product_id);
// 		//error_log($email);
// 		wp_trash_post($product_id, false);
// 		$EmailValidationSpammers->ceate_spammer($email, $ref_domain, $report_count, $spammer_level, $date_created);
// 	}

// 	return 'Product prices updated successfully.';
// }

// add_shortcode('update_spamers', 'update_spamers');


function update_spamers_doamin()
{

  $EmailValidationSpammers = new EmailValidationSpammers();
  $EmailValidationSpammers->loop_update_domain_array_to_single();
}

add_shortcode('update_spamers_doamin', 'update_spamers_doamin');
