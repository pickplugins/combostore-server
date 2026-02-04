<?php
if (! defined('ABSPATH')) exit;  // if direct access

use ComboStore\Classes\ComboStoreExport;



/**
 * Replace old meta key with new meta key across all posts.
 *
 * @param string $old_key The old meta key to replace.
 * @param string $new_key The new meta key to use.
 */
function replace_meta_key($old_key, $new_key)
{
  global $wpdb;

  // Get all posts with the old meta key
  $results = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT post_id, meta_value 
             FROM $wpdb->postmeta 
             WHERE meta_key = %s",
      $old_key
    )
  );

  if (!empty($results)) {
    foreach ($results as $row) {

      $price = str_replace(',', '', $row->meta_value);
      $price = (int)$price;

      // Add new meta key with old value
      add_post_meta($row->post_id, $new_key, $price);

      // Delete the old meta key
      delete_post_meta($row->post_id, $old_key, $row->meta_value);
    }
  }
}


//replace_meta_key('price', 'regularPrice');





// combo_store_update_instock();


add_shortcode("combo_store_update_instock", "combo_store_update_instock");
function combo_store_update_instock()
{
  $meta_query = array();
  $tax_query = array();
  $job_category = "";
  $response = [];


  // $tax_query[] = array(
  //     'taxonomy' => 'job_category',
  //     'field'    => 'slug',
  //     'terms'    => $job_category,
  //     //'operator'    => '',
  // );

  // $meta_query[] = array(
  //   'key' => 'credits',
  //   'value' => "0",
  //   'compare' => '>',
  // );

  // $meta_query[] = array(
  //   'key' => 'credits',
  //   'value' => "",
  //   'compare' => 'NOT EXISTS',
  // );

  // $meta_query[] = array(
  //   'key' => 'main_prompt',
  //   'value' => "",
  //   'compare' => 'EXISTS',
  // );

  $query_args = array(
    'post_type' => 'product',
    'post_status' => 'any',
    // 's' => "",
    'orderby' => 'date',
    'order' => 'ASC',
    // 'meta_query' => $meta_query,
    // 'tax_query' => $tax_query,
    'posts_per_page' => -1,
    // 'paged' => 1,
  );

  $wp_query = new WP_Query($query_args);

  $count = 1;



  if ($wp_query->have_posts()) :

    while ($wp_query->have_posts()) : $wp_query->the_post();

      $post_id = get_the_ID();

      update_post_meta($post_id, 'stockStatus', 'instock');


      $count++;
    endwhile;


    wp_reset_query();
    wp_reset_postdata();
  else :


  endif;

  //echo $count;


}



