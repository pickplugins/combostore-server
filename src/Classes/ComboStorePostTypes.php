<?php

namespace ComboStore\Classes;

if (!defined('ABSPATH')) exit;  // if direct access
class ComboStorePostTypes
{
	public function __construct()
	{
		add_action('init', array($this, 'taxonomy_register'), 0);

		add_action('init', array($this, 'post_type_coupon'), 0);
		add_action('init', array($this, 'post_type_wishlist'), 0);
		add_action('init', array($this, 'post_type_ticket'), 0);
		add_action('init', array($this, 'register_ticket_cat'), 0);
		add_action('init', array($this, 'register_ticket_tag'), 0);
		add_action('init', array($this, 'post_type_product'), 0);
		add_action('init', array($this, 'register_product_cat'), 0);
		add_action('init', array($this, 'register_product_tag'), 0);
		add_action('init', array($this, 'register_product_brand'), 0);
		add_action('init', array($this, 'register_product_visibility'), 0);

		add_action('init', array($this, 'post_type_ticket'), 0);
		add_action('init', array($this, 'register_ticket_cat'), 0);
		add_action('init', array($this, 'register_ticket_tag'), 0);
	}


	public function taxonomy_register()
	{
		$combo_store_settings = get_option('combo_store_settings');
		$productAttributes = isset($combo_store_settings['productAttributes']) ? $combo_store_settings['productAttributes'] : [];


		if (empty($productAttributes)) return;
		foreach ($productAttributes as $productAttribute) {
			$slug = isset($productAttribute['slug']) ? $productAttribute['slug'] : '';
			if (empty($slug)) continue;
			if (taxonomy_exists($slug)) continue;
			$plural = isset($productAttribute['labels']['name']) ? $productAttribute['labels']['name'] : '';
			$singular_name = isset($productAttribute['labels']['singular_name']) ? $productAttribute['labels']['singular_name'] : $plural;
			$menu_name = isset($productAttribute['labels']['menu_name']) ? $productAttribute['labels']['menu_name'] : $plural;
			$add_new = isset($productAttribute['labels']['add_new']) ? $productAttribute['labels']['add_new'] : "Add New";
			$all_items = isset($productAttribute['labels']['all_items']) ? $productAttribute['labels']['all_items'] : "All %s";
			$add_new_item = isset($productAttribute['labels']['add_new_item']) ? $productAttribute['labels']['add_new_item'] : "Add %s";
			$edit = isset($productAttribute['labels']['edit']) ? $productAttribute['labels']['edit'] : "Edit";
			$edit_item = isset($productAttribute['labels']['edit_item']) ? $productAttribute['labels']['edit_item'] : "Edit %s";
			$new_item = isset($productAttribute['labels']['new_item']) ? $productAttribute['labels']['new_item'] : "New %s";
			$view = isset($productAttribute['labels']['view']) ? $productAttribute['labels']['view'] : "View %s";
			$view_item = isset($productAttribute['labels']['view_item']) ? $productAttribute['labels']['view_item'] : "View %s";
			$search_items = isset($productAttribute['labels']['search_items']) ? $productAttribute['labels']['search_items'] : "Search %s";
			$not_found = isset($productAttribute['labels']['not_found']) ? $productAttribute['labels']['not_found'] : "No %s found";
			$not_found_in_trash = isset($productAttribute['labels']['not_found_in_trash']) ? $productAttribute['labels']['not_found_in_trash'] : "No %s found in trash";
			$parent = isset($productAttribute['labels']['parent']) ? $productAttribute['labels']['parent'] : "Parent %s";
			$description = isset($productAttribute['description']) ? $productAttribute['description'] : "This is where you can create and manage %s.";
			$object_types = ["product"];

			$public = isset($productAttribute['public']) ? $productAttribute['public'] : true;
			$show_ui = isset($productAttribute['show_ui']) ? $productAttribute['show_ui'] : true;
			$show_in_rest = isset($productAttribute['show_in_rest']) ? $productAttribute['show_in_rest'] : false;
			$capability_type = isset($productAttribute['capability_type']) ? $productAttribute['capability_type'] : "post";
			// $publish_posts = isset($productAttribute['capabilities']['publish_posts']) ? $productAttribute['labels']['publish_posts'] : "publish_" . $slug . "s";
			// $edit_posts = isset($productAttribute['capabilities']['edit_posts']) ? $productAttribute['labels']['edit_posts'] : "edit_" . $slug . "s";
			// $edit_others_posts = isset($productAttribute['capabilities']['edit_others_posts']) ? $productAttribute['labels']['edit_others_posts'] : "edit_others_" . $slug . "s";
			// $read_private_posts = isset($productAttribute['capabilities']['read_private_posts']) ? $productAttribute['labels']['read_private_posts'] : "read_private_" . $plural;
			// $edit_post = isset($productAttribute['capabilities']['edit_post']) ? $productAttribute['labels']['edit_post'] : "edit_" . $slug;
			// $delete_post = isset($productAttribute['capabilities']['delete_post']) ? $productAttribute['labels']['delete_post'] : "delete_" . $slug;
			// $read_post = isset($productAttribute['capabilities']['read_post']) ? $productAttribute['labels']['read_post'] : "read_" . $slug;
			$map_meta_cap = isset($productAttribute['map_meta_cap']) ? $productAttribute['map_meta_cap'] : true;
			$publicly_queryable = isset($productAttribute['publicly_queryable']) ? $productAttribute['publicly_queryable'] : true;
			$show_admin_column = isset($productAttribute['show_admin_column']) ? $productAttribute['show_admin_column'] : true;
			$rewrite = isset($productAttribute['rewrite']) ? $productAttribute['rewrite'] : true;
			$exclude_from_search = isset($productAttribute['exclude_from_search']) ? $productAttribute['exclude_from_search'] : false;
			$hierarchical = isset($productAttribute['hierarchical']) ? $productAttribute['hierarchical'] : false;
			$query_var = isset($productAttribute['query_var']) ? $productAttribute['query_var'] : true;
			$show_in_nav_menus = isset($productAttribute['show_in_nav_menus']) ? $productAttribute['show_in_nav_menus'] : true;
			$menu_icon = isset($productAttribute['menu_icon']) ? $productAttribute['menu_icon'] : 'dashicons-grid-view';
			$show_in_menu = isset($productAttribute['show_in_menu']) ? $productAttribute['show_in_menu'] : $slug;
			$supports = isset($productAttribute['supports']) ? $productAttribute['supports'] : array("title");
			// $map_meta_cap =  true;
			// $publicly_queryable =  true;
			// $exclude_from_search =  false;
			// $hierarchical =  false;
			// $query_var = true;
			// $show_in_nav_menus = true;
			// $rewrite = true;
			// $menu_icon =  'dashicons-grid-view';
			// $supports =  array('title', 'author', 'comments', 'custom-fields');
			$taxonomy_args = [];
			$taxonomy_args['labels']['name'] = $plural;
			$taxonomy_args['labels']['singular_name'] = $singular_name;
			$taxonomy_args['labels']['menu_name'] = $menu_name;
			$taxonomy_args['labels']['all_items'] = $all_items;
			$taxonomy_args['labels']['add_new'] = $add_new;
			$taxonomy_args['labels']['add_new_item'] = $add_new_item;
			$taxonomy_args['labels']['edit'] = $edit;
			$taxonomy_args['labels']['edit_item'] = $edit_item;
			$taxonomy_args['labels']['new_item'] = $new_item;
			$taxonomy_args['labels']['view'] = $view;
			$taxonomy_args['labels']['view_item'] = $view_item;
			$taxonomy_args['labels']['search_items'] = $search_items;
			$taxonomy_args['labels']['not_found'] = $not_found;
			$taxonomy_args['labels']['not_found_in_trash'] = $not_found_in_trash;
			$taxonomy_args['labels']['parent'] = $parent;
			//$taxonomy_args['capabilities']['publish_posts'] = $publish_posts;
			//($public);
			$taxonomy_args['description'] = $description;
			$taxonomy_args['public'] = (bool) $public;
			$taxonomy_args['show_ui'] = (bool) $show_ui;
			$taxonomy_args['show_in_rest'] = (bool) $show_in_rest;
			$taxonomy_args['map_meta_cap'] = (bool) $map_meta_cap;
			$taxonomy_args['publicly_queryable'] = (bool) $publicly_queryable;
			$taxonomy_args['show_admin_column'] = (bool) $show_admin_column;
			$taxonomy_args['exclude_from_search'] = (bool) $exclude_from_search;
			$taxonomy_args['hierarchical'] = (bool) $hierarchical;
			$taxonomy_args['query_var'] = (bool)$query_var;
			$taxonomy_args['supports'] = $supports;
			$taxonomy_args['show_in_nav_menus'] = (bool)$show_in_nav_menus;
			$taxonomy_args['menu_icon'] = $menu_icon;
			$taxonomy_args['rewrite'] = $rewrite;
			if (!empty($show_in_menu)) {
				//$taxonomy_args['show_in_menu'] = $show_in_menu;
			}
			register_taxonomy(
				$slug,
				$object_types,
				apply_filters("combo_blocks_taxonomy_{$slug}", $taxonomy_args)
			);
		}
	}

	public function post_type_coupon()
	{
		if (post_type_exists("coupon"))
			return;

		$singular  = __('Coupon', 'combo-store-server');
		$plural    = __('Coupons', 'combo-store-server');


		register_post_type(
			"coupon",
			apply_filters("cstore_post_type_coupon", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combo-store-server'),
/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combo-store-server'), $singular)
				),
/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store-server'), $plural),
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

	public function post_type_wishlist()
	{
		if (post_type_exists("wishlist"))
			return;

		$singular  = __('wishlist', 'combo-store-server');
		$plural    = __('wishlists', 'combo-store-server');


		register_post_type(
			"wishlist",
			apply_filters("cstore_post_type_wishlist", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combo-store-server'),
/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combo-store-server'), $singular)
				),
/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store-server'), $plural),
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

		$singular  = __('Product', 'combo-store-server');
		$plural    = __('Products', 'combo-store-server');


		register_post_type(
			"product",
			apply_filters("cstore_post_type_product", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combo-store-server'),
/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combo-store-server'), $singular)
				),
/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store-server'), $plural),
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

		$singular  = __('Product Category', 'combo-store-server');
		$plural    = __('Product Categories', 'combo-store-server');

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
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
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

		$singular  = __('Product Tag', 'combo-store-server');
		$plural    = __('Product Tags', 'combo-store-server');

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
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
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
	public function register_product_brand()
	{

		$singular  = __('Product brand', 'combo-store-server');
		$plural    = __('Product brands', 'combo-store-server');

		register_taxonomy(
			"product_brand",
			apply_filters('cstore_product_brand_object_type', array('product')),
			apply_filters('cstore_product_brand_args', array(
				'hierarchical' 			=> false,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'product_brand', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}
	public function register_product_visibility()
	{

		$singular  = __('Product visibility', 'combo-store-server');
		$plural    = __('Product visibility', 'combo-store-server');

		register_taxonomy(
			"product_visibility",
			apply_filters('cstore_product_visibility_object_type', array('product')),
			apply_filters('cstore_product_visibility_args', array(
				'hierarchical' 			=> true,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'product_visibility', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}





	public function post_type_ticket()
	{
		if (post_type_exists("ticket"))
			return;

		$singular  = __('Ticket', 'combo-store-server');
		$plural    = __('Tickets', 'combo-store-server');


		register_post_type(
			"ticket",
			apply_filters("cstore_post_type_ticket", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combo-store-server'),
/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combo-store-server'), $singular)
				),
/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-store-server'), $plural),
				'public' 				=> true,
				'show_ui' 				=> true,
				'capability_type' 		=> 'post',
				'map_meta_cap'          => true,
				'publicly_queryable' 	=> true,
				'exclude_from_search' 	=> false,
				'hierarchical' 			=> false,
				'rewrite' 				=> true,
				'query_var' 			=> true,
				'supports' 				=> array('title', 'editor', 'thumbnail', 'custom-fields', 'author', 'excerpt', 'comments'),
				'show_in_nav_menus' 	=> false,
				'menu_icon' => 'dashicons-megaphone',
			))
		);
	}








	public function register_ticket_cat()
	{

		$singular  = __('Ticket Category', 'combo-store-server');
		$plural    = __('Ticket Categories', 'combo-store-server');

		register_taxonomy(
			"ticket_cat",
			apply_filters('cstore_ticket_cat_object_type', array('ticket')),
			apply_filters('cstore_ticket_cat_args', array(
				'hierarchical' 			=> true,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),	
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'prompt_cat', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}




	public function register_ticket_tag()
	{

		$singular  = __('Ticket Tag', 'combo-store-server');
		$plural    = __('Ticket Tags', 'combo-store-server');

		register_taxonomy(
			"ticket_tag",
			apply_filters('cstore_ticket_tag_object_type', array('ticket')),
			apply_filters('cstore_ticket_tag_args', array(
				'hierarchical' 			=> false,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combo-store-server'), $plural),
/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combo-store-server'), $singular),
/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combo-store-server'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'prompt_tag', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}
}
