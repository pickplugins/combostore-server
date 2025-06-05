<?php

namespace EmailValidation\Classes;

if (!defined('ABSPATH')) exit;  // if direct access
class EmailValidationPostTypes
{
	public function __construct()
	{
		add_action('init', array($this, '_posttype_subscription'));
	}




	public function _posttype_subscription()
	{
		if (post_type_exists("subscription"))
			return;
		$singular  = __('Subscription', 'combo-blocks');
		$plural    = __('Subscriptions', 'combo-blocks');
		register_post_type(
			"subscription",
			apply_filters("combo_blocks_subscription", array(
				'labels' => array(
					'name'                     => $plural,
					'singular_name'         => $singular,
					'menu_name'             => $singular,
					'all_items'             => sprintf(__('All %s', 'combo-blocks'), $plural),
					'add_new'                 => __('Add New', 'combo-blocks'),
					'add_new_item'             => sprintf(__('Add %s', 'combo-blocks'), $singular),
					'edit'                     => __('Edit', 'combo-blocks'),
					'edit_item'             => sprintf(__('Edit %s', 'combo-blocks'), $singular),
					'new_item'                 => sprintf(__('New %s', 'combo-blocks'), $singular),
					'view'                     => sprintf(__('View %s', 'combo-blocks'), $singular),
					'view_item'             => sprintf(__('View %s', 'combo-blocks'), $singular),
					'search_items'             => sprintf(__('Search %s', 'combo-blocks'), $plural),
					'not_found'             => sprintf(__('No %s found', 'combo-blocks'), $plural),
					'not_found_in_trash'     => sprintf(__('No %s found in trash', 'combo-blocks'), $plural),
					'parent'                 => sprintf(__('Parent %s', 'combo-blocks'), $singular)
				),
				'description' => sprintf(__('This is where you can create and manage %s.', 'combo-blocks'), $plural),
				'public'                 => true,
				'show_ui'                 => true,
				'capability_type'         => 'post',
				'map_meta_cap'          => true,
				'publicly_queryable'     => false,
				'exclude_from_search'     => false,
				'hierarchical'             => false,
				'query_var'             => true,
				'supports'                 => array('title', 'editor', 'thumbnail', 'author', 'revisions', 'excerpt', 'custom-fields', 'comments'),
				'show_in_nav_menus'     => false,
				'show_in_menu'     => 'combo-blocks',
				'menu_icon' => 'dashicons-businessman',
				'show_in_rest' => true,
			))
		);
	}
}
