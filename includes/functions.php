<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreExport;


/**
 * Convert a string into a file-safe slug (no extension).
 *
 * @param string $name Input string.
 * @return string Slugified string.
 */
function string_to_file_slug($string)
{
  $slug = strtolower($string);
  $slug = preg_replace('/[^a-z0-9]+/i', '-', $slug);
  return trim($slug, '-');
}


function combo_store_upload_file($data)
{
  $upload_dir       = wp_upload_dir();
  //HANDLE UPLOADED FILE
  if (!function_exists('wp_handle_sideload')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
  }
  // Without that I'm getting a debug error!?
  if (!function_exists('wp_get_current_user')) {
    require_once(ABSPATH . 'wp-includes/pluggable.php');
  }
  if (empty($data['tmp_name'])) return;
  // @new
  $file             = array();
  $file['error']    = isset($data['error']) ? $data['error'] : '';
  $file['tmp_name'] = isset($data['tmp_name']) ? $data['tmp_name'] : "";
  $file['name']     = isset($data['name']) ? $data['name'] : '';
  $file['type']     = isset($data['type']) ? $data['type'] : '';
  $file['size']     = isset($data['tmp_name']) ? filesize($data['tmp_name']) : 0;
  // upload file to server
  // @new use $file instead of $image_upload
  $file_return      = wp_handle_sideload($file, array('test_form' => false));
  $filename = $file_return['file'];
  $attachment = array(
    'post_mime_type' => $file_return['type'],
    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
    'post_content' => '',
    'post_status' => 'inherit',
    'guid' => esc_url($upload_dir['url']) . '/' . basename($filename)
  );
  $attach_id = wp_insert_attachment($attachment, $filename, 289);
  require_once(ABSPATH . 'wp-admin/includes/image.php');
  $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
  wp_update_attachment_metadata($attach_id, $attach_data);
  $attach_url = wp_get_attachment_url($attach_id);
  $jsonReturn = array(
    'id'  =>  $attach_id,
    'url'  =>  $attach_url,
  );
  return $jsonReturn;
}

function build_category_tree($terms, $parent = 0)
{
  $branch = [];

  foreach ($terms as $term) {
    if ($term->parent == $parent) {
      $children = build_category_tree($terms, $term->term_id);

      $node = [];
      $term_id = $term->term_id;
      $thumbnail = get_term_meta($term_id, "thumbnail", true);


      $node['term_id'] = $term->term_id;
      $node['name'] = $term->name;
      $node['slug'] = $term->slug;
      $node['count'] = $term->count;
      $node['thumbnail'] = $thumbnail;

      if ($children) {
        $node['children'] = $children;
      }


      $branch[] = $node;
    }
  }

  return $branch;
}



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






// Allow mobile number registration
add_action('user_register', function ($user_id) {
  if (isset($_POST['mobile_number'])) {
    update_user_meta($user_id, 'mobile_number', sanitize_text_field($_POST['mobile_number']));
  }
});



// add_filter('rest_authentication_errors', function ($result) {

//   if (!empty($result)) {
//     return $result;
//   }

//   $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

//   if ($auth) {
//     $token = str_replace('Bearer ', '', $auth);
//     if ($token == 'a3f9c1e8b7d4f6a9c2e5f8d1b3a6c9e7d0f2a4b6c8e1f3a5b7c9d2e4f6a8b0c1') {
//       error_log($token);
//       return true;
//     }
//   }

//   return $result;
// });


// add_filter('rest_authentication_errors', function ($result) {
//   if (strpos($_SERVER['REQUEST_URI'], '/wp-json/combo-store/v2/get_settings') !== false) {
//     $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
//     error_log($_SERVER['REQUEST_URI']);
//     $token = str_replace('Bearer ', '', $auth);

//     if ($token == 'a3f9c1e8b7d4f6a9c2e5f8d1b3a6c9e7d0f2a4b6c8e1f3a5b7c9d2e4f6a8b0c1') {
//       error_log($token);
//       return true;
//     }
//   }
//   return $result;
// });
