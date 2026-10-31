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
		add_action('init', array($this, 'register_product_badge'), 0);
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

		// error_log(wp_json_encode($productAttributes));


		if (empty($productAttributes)) return;
		foreach ($productAttributes as $productAttribute) {
			$slug = isset($productAttribute['slug']) ? $productAttribute['slug'] : '';
			if (empty($slug)) continue;
			if (taxonomy_exists($slug)) continue;
			$plural = isset($productAttribute['label']) ? $productAttribute['label'] : '';
			$singular_name = isset($productAttribute['singular_name']) ? $productAttribute['singular_name'] : $plural;
			$menu_name = isset($productAttribute['menu_name']) ? $productAttribute['menu_name'] : $plural;
			$add_new = isset($productAttribute['add_new']) ? $productAttribute['add_new'] : "Add New";
			$all_items = isset($productAttribute['all_items']) ? $productAttribute['all_items'] : sprintf("All %s", $plural);
			$add_new_item = isset($productAttribute['add_new_item']) ? $productAttribute['add_new_item'] : sprintf("Add New %s", $singular_name);
			$edit = isset($productAttribute['edit']) ? $productAttribute['edit'] : "Edit";
			$edit_item = isset($productAttribute['edit_item']) ? $productAttribute['edit_item'] : sprintf("Edit %s", $singular_name);
			$new_item = isset($productAttribute['new_item']) ? $productAttribute['new_item'] : sprintf("New %s", $singular_name);
			$view = isset($productAttribute['view']) ? $productAttribute['view'] : sprintf("View %s", $singular_name);
			$view_item = isset($productAttribute['view_item']) ? $productAttribute['view_item'] : sprintf("View %s", $singular_name);
			$search_items = isset($productAttribute['search_items']) ? $productAttribute['search_items'] : sprintf("Search %s", $plural);
			$not_found = isset($productAttribute['not_found']) ? $productAttribute['not_found'] : sprintf("No %s found", $plural);
			$not_found_in_trash = isset($productAttribute['not_found_in_trash']) ? $productAttribute['not_found_in_trash'] : sprintf("No %s found in trash", $plural);
			$parent = isset($productAttribute['parent']) ? $productAttribute['parent'] : sprintf("Parent %s", $singular_name);
			$description = isset($productAttribute['description']) ? $productAttribute['description'] : sprintf("This is where you can create and manage %s.", $plural);
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

		$singular  = __('Coupon', 'combostore-server');
		$plural    = __('Coupons', 'combostore-server');


		register_post_type(
			"coupon",
			apply_filters("cstore_post_type_coupon", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combostore-server'),
					/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combostore-server'), $singular)
				),
				/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combostore-server'), $plural),
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

		$singular  = __('wishlist', 'combostore-server');
		$plural    = __('wishlists', 'combostore-server');


		register_post_type(
			"wishlist",
			apply_filters("cstore_post_type_wishlist", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combostore-server'),
					/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combostore-server'), $singular)
				),
				/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combostore-server'), $plural),
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

		$singular  = __('Product', 'combostore-server');
		$plural    = __('Products', 'combostore-server');


		register_post_type(
			"product",
			apply_filters("cstore_post_type_product", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combostore-server'),
					/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combostore-server'), $singular)
				),
				/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combostore-server'), $plural),
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








	public function register_product_cat()
	{

		$singular  = __('Product Category', 'combostore-server');
		$plural    = __('Product Categories', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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

		$singular  = __('Product Tag', 'combostore-server');
		$plural    = __('Product Tags', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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
	public function register_product_badge()
	{

		$singular  = __('Product Badge', 'combostore-server');
		$plural    = __('Product Badges', 'combostore-server');

		register_taxonomy(
			"product_badge",
			apply_filters('cstore_product_badge_object_type', array('product')),
			apply_filters('cstore_product_badge_args', array(
				'hierarchical' 			=> false,
				'show_admin_column' 	=> true,
				'update_count_callback' => '_update_post_term_count',
				'label' 				=> $plural,
				'labels' => array(
					'name'              => $plural,
					'singular_name'     => $singular,
					'menu_name'         => ucwords($plural),
					/* translators: Post Type name */
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
				),
				'show_ui' 				=> true,
				'public' 	     		=> true,
				'rewrite' => array(
					'slug' => 'product_badge', // This controls the base slug that will display before each term
					'with_front' => false, // Don't display the category base before "/locations/"
					'hierarchical' => false // This will allow URL's like "/locations/boston/cambridge/"
				),
			))
		);
	}


	public function register_product_brand()
	{

		$singular  = __('Product brand', 'combostore-server');
		$plural    = __('Product brands', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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

		$singular  = __('Product visibility', 'combostore-server');
		$plural    = __('Product visibility', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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

		$singular  = __('Ticket', 'combostore-server');
		$plural    = __('Tickets', 'combostore-server');


		register_post_type(
			"ticket",
			apply_filters("cstore_post_type_ticket", array(
				'labels' => array(
					'name' 					=> $plural,
					'singular_name' 		=> $singular,
					'menu_name'             => $singular,
					/* translators: Post Type name */
					'all_items'             => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'add_new' 				=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item' 			=> sprintf(__('Add %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit' 					=> __('Edit', 'combostore-server'),
					/* translators: Post Type name */
					'edit_item' 			=> sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item' 				=> sprintf(__('New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view' 					=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'view_item' 			=> sprintf(__('View %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'search_items' 			=> sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found' 			=> sprintf(__('No %s found', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'not_found_in_trash' 	=> sprintf(__('No %s found in trash', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent' 				=> sprintf(__('Parent %s', 'combostore-server'), $singular)
				),
				/* translators: Post Type name */
				'description' => sprintf(__('This is where you can create and manage %s.', 'combostore-server'), $plural),
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

		$singular  = __('Ticket Category', 'combostore-server');
		$plural    = __('Ticket Categories', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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

		$singular  = __('Ticket Tag', 'combostore-server');
		$plural    = __('Ticket Tags', 'combostore-server');

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
					'search_items'      => sprintf(__('Search %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'all_items'         => sprintf(__('All %s', 'combostore-server'), $plural),
					/* translators: Post Type name */
					'parent_item'       => sprintf(__('Parent %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'parent_item_colon' => sprintf(__('Parent %s:', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'edit_item'         => sprintf(__('Edit %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'update_item'       => sprintf(__('Update %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'add_new_item'      => sprintf(__('Add New %s', 'combostore-server'), $singular),
					/* translators: Post Type name */
					'new_item_name'     => sprintf(__('New %s Name', 'combostore-server'),  $singular)
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
