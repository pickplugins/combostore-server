<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreExport;





function create_wp_admin_user()
{
  $username = 'public3';
  $password = '123456';
  $email    = 'public3@gmail.com';

  // Check if user already exists
  if (username_exists($username) || email_exists($email)) {
    return;
  }

  // Create user
  $user_id = wp_create_user($username, $password, $email);

  if (!is_wp_error($user_id)) {
    // Set role to administrator
    $user = new WP_User($user_id);
    $user->set_role('customer');
  }
}
//add_action('init', 'create_wp_admin_user');













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





function upload_media_file($data)
{
  $upload_dir       = wp_upload_dir();

  error_log("Uploading file: " . print_r($data, true));
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

  error_log(wp_json_encode($attach_data));

  $jsonReturn = array(
    'id'  =>  $attach_id,
    'url'  =>  $attach_url,
    'title'  =>  $data['name'],
  );
  return $jsonReturn;
}



/**
 * The restricted upload folder, wp-content/uploads/combostore, created on first
 * use. Files there are for restricted items (paid downloads, licensed
 * software), so the folder refuses direct web access: Apache/LiteSpeed read
 * the .htaccess, IIS the web.config, and index.php stops directory listings.
 * Nginx ignores all three and needs a `location` rule denying the folder.
 *
 * @return array|WP_Error ['path' => absolute dir, 'url' => its URL]
 */
function combo_store_secure_upload_dir()
{
  $uploads = wp_upload_dir(null, false);
  if (!empty($uploads['error'])) {
    return new WP_Error('upload_dir', $uploads['error']);
  }

  $path = trailingslashit($uploads['basedir']) . 'combostore';
  $url  = trailingslashit($uploads['baseurl']) . 'combostore';

  if (!is_dir($path) && !wp_mkdir_p($path)) {
    return new WP_Error('upload_dir', 'Could not create the secure upload folder.');
  }

  $guards = array(
    '.htaccess'  => "# ComboStore secure uploads: no direct access.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\nOptions -Indexes\n",
    'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration>\n  <system.webServer>\n    <authorization>\n      <deny users=\"*\" />\n    </authorization>\n  </system.webServer>\n</configuration>\n",
    'index.php'  => "<?php\n// Silence is golden.\n",
  );

  foreach ($guards as $name => $contents) {
    if (!file_exists("$path/$name")) {
      file_put_contents("$path/$name", $contents);
    }
  }

  return array('path' => $path, 'url' => $url);
}


/**
 * Upload a file into the secure folder (see combo_store_secure_upload_dir).
 *
 * Same input and response as upload_media_file, plus `secure` and `path`
 * (relative to the uploads folder). The file name gets a random prefix, so a
 * restricted file cannot be found by guessing its name on a server where the
 * deny rules do not apply. It is recorded as an attachment marked
 * `_combostore_secure`, without thumbnails (they would sit in the same folder
 * and be just as unreachable).
 *
 * @return array|WP_Error
 */
function upload_secure_media_file($data)
{
  if (empty($data['tmp_name'])) {
    return new WP_Error('no_file', 'No file was uploaded.');
  }

  $dir = combo_store_secure_upload_dir();
  if (is_wp_error($dir)) return $dir;

  if (!function_exists('wp_handle_sideload')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
  }

  $file = array(
    'error'    => isset($data['error']) ? $data['error'] : 0,
    'tmp_name' => $data['tmp_name'],
    'name'     => isset($data['name']) ? $data['name'] : '',
    'type'     => isset($data['type']) ? $data['type'] : '',
    'size'     => filesize($data['tmp_name']),
  );

  /* Point this one upload at the secure folder; ordinary uploads are untouched. */
  $to_secure_dir = function ($uploads) use ($dir) {
    $uploads['path']   = $dir['path'];
    $uploads['url']    = $dir['url'];
    $uploads['subdir'] = '/combostore';
    return $uploads;
  };

  add_filter('upload_dir', $to_secure_dir);
  $file_return = wp_handle_sideload($file, array(
    'test_form' => false,
    'unique_filename_callback' => function ($dir_path, $name, $ext) {
      $base = sanitize_file_name(pathinfo($name, PATHINFO_FILENAME));
      $candidate = wp_generate_password(12, false) . '-' . $base . $ext;
      while (file_exists(trailingslashit($dir_path) . $candidate)) {
        $candidate = wp_generate_password(12, false) . '-' . $base . $ext;
      }
      return $candidate;
    },
  ));
  remove_filter('upload_dir', $to_secure_dir);

  if (!empty($file_return['error'])) {
    return new WP_Error('upload_failed', $file_return['error']);
  }

  $filename = $file_return['file'];

  $attach_id = wp_insert_attachment(array(
    'post_mime_type' => $file_return['type'],
    'post_title'     => preg_replace('/\.[^.]+$/', '', isset($data['name']) ? $data['name'] : basename($filename)),
    'post_content'   => '',
    'post_status'    => 'inherit',
    'guid'           => $file_return['url'],
  ), $filename, 0);

  if (is_wp_error($attach_id) || !$attach_id) {
    wp_delete_file($filename);
    return new WP_Error('upload_failed', 'The file was saved but could not be added to the media library.');
  }

  update_post_meta($attach_id, '_combostore_secure', 1);

  return array(
    'id'     => $attach_id,
    'url'    => $file_return['url'],
    'title'  => isset($data['name']) ? $data['name'] : basename($filename),
    'secure' => true,
    'path'   => 'combostore/' . basename($filename),
  );
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
