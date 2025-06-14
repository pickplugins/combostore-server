<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access
class EmailValidationPostTypes
{
	public function __construct()
	{
		add_action('init', array($this, 'post_type_coupon'), 0);
		add_action('init', array($this, 'post_type_product'), 0);
		add_action('init', array($this, 'register_product_cat'), 0);
		add_action('init', array($this, 'register_product_tag'), 0);
	}




	public function post_type_coupon()
	{
		if (post_type_exists("product"))
			return;

		$singular  = __('coupon', 'combo-store');
		$plural    = __('coupons', 'combo-store');


		register_post_type(
			"coupon",
			apply_filters("cstore_post_type_coupon", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					'all_items'             => sprintf(__('All %s', 'combo-store'), $plural),
					'add_new' 				=> sprintf(__('Add %s', 'combo-store'), $singular),
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store'), $singular),
					'edit' 					=> __('Edit', 'combo-store'),
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store'), $singular),
					'new_item' 				=> sprintf(__('New %s', 'combo-store'), $singular),
					'view' 					=> sprintf(__('View %s', 'combo-store'), $singular),
					'view_item' 			=> sprintf(__('View %s', 'combo-store'), $singular),
					'search_items' 			=> sprintf(__('Search %s', 'combo-store'), $plural),
					'not_found' 			=> sprintf(__('No %s found', 'combo-store'), $plural),
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store'), $plural),
					'parent' 				=> sprintf(__('Parent %s', 'combo-store'), $singular)
				),
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store'), $plural),
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap'          => true,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> false,
				'hierarchical' 			=> false,
				'rewrite' 				=> true,
				'query_var' 			=> true,
				'supports' 				=> array('title', 'editor', 'thumbnail', 'custom-fields', 'author', 'excerpt'),
				'show_in_nav_menus' 	=> false,
				'menu_icon' => 'dashicons-megaphone',
			))
		);
	}

	public function post_type_product()
	{
		if (post_type_exists("product"))
			return;

		$singular  = __('Product', 'combo-store');
		$plural    = __('Products', 'combo-store');


		register_post_type(
			"product",
			apply_filters("cstore_post_type_product", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					'all_items'             => sprintf(__('All %s', 'combo-store'), $plural),
					'add_new' 				=> sprintf(__('Add %s', 'combo-store'), $singular),
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store'), $singular),
					'edit' 					=> __('Edit', 'combo-store'),
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store'), $singular),
					'new_item' 				=> sprintf(__('New %s', 'combo-store'), $singular),
					'view' 					=> sprintf(__('View %s', 'combo-store'), $singular),
					'view_item' 			=> sprintf(__('View %s', 'combo-store'), $singular),
					'search_items' 			=> sprintf(__('Search %s', 'combo-store'), $plural),
					'not_found' 			=> sprintf(__('No %s found', 'combo-store'), $plural),
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store'), $plural),
					'parent' 				=> sprintf(__('Parent %s', 'combo-store'), $singular)
				),
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store'), $plural),
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap'          => true,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> false,
				'hierarchical' 			=> false,
				'rewrite' 				=> true,
				'query_var' 			=> true,
				'supports' 				=> array('title', 'editor', 'thumbnail', 'custom-fields', 'author', 'excerpt'),
				'show_in_nav_menus' 	=> false,
				'menu_icon' => 'dashicons-megaphone',
			))
		);
	}








	public function register_product_cat()
	{

		$singular  = __('Product Category', 'combo-store');
		$plural    = __('Product Categories', 'combo-store');

		register_taxonomy(
			"product_cat",
			apply_filters('cstore_product_cat_object_type', array('product')),
			apply_filters('cstore_product_cat_args', array(
				'hierarchical' 			=> true,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
					'search_items'      => sprintf(__('Search %s', 'combo-store'), $plural),
					'all_items'         => sprintf(__('All %s', 'combo-store'), $plural),
					'parent_item'       => sprintf(__('Parent %s', 'combo-store'), $singular),
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store'), $singular),
					'edit_item'         => sprintf(__('Edit %s', 'combo-store'), $singular),
					'update_item'       => sprintf(__('Update %s', 'combo-store'), $singular),
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store'), $singular),
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'product_cat', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}




	public function register_product_tag()
	{

		$singular  = __('Product Tag', 'combo-store');
		$plural    = __('Product Tags', 'combo-store');

		register_taxonomy(
			"product_tag",
			apply_filters('cstore_product_tag_object_type', array('product')),
			apply_filters('cstore_product_tag_args', array(
				'hierarchical' 			=> false,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
					'search_items'      => sprintf(__('Search %s', 'combo-store'), $plural),
					'all_items'         => sprintf(__('All %s', 'combo-store'), $plural),
					'parent_item'       => sprintf(__('Parent %s', 'combo-store'), $singular),
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store'), $singular),
					'edit_item'         => sprintf(__('Edit %s', 'combo-store'), $singular),
					'update_item'       => sprintf(__('Update %s', 'combo-store'), $singular),
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store'), $singular),
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'product_tag', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}
}
