<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('DH_Popup_Post_Types')):
class DH_Popup_Post_Types {
	
	public static function init(){
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
	}
	
	public static function register_post_types(){
		if ( ! is_blog_installed() || post_type_exists( 'dh_popup' ) ) {
			return;
		}
		
		do_action( 'dh_popup_register_post_type' );
		
		register_post_type( 'dh_popup', array(
			'labels' => array(
				'add_new_item' => __( 'Add New Popup', 'dh_popup' ),
				'name' => __( 'Popups', 'dh_popup' ),
				'singular_name' => __( 'Popup', 'dh_popup' ),
				'edit_item' => __( 'Edit Popup', 'dh_popup' ),
				'view_item' => __( 'View Popup', 'dh_popup' ),
				'search_items' => __( 'Search Popup', 'dh_popup' ),
				'not_found' => __( 'No Popup found', 'dh_popup' ),
				'not_found_in_trash' => __( 'No Popup found in Trash', 'dh_popup' ),
			),
			'public' => isset($_GET['vc_editable']) && 'true'===$_GET['vc_editable'] ? true : false,
			'has_archive' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => isset($_GET['vc_editable']) && 'true'===$_GET['vc_editable'] ? true : false,
			'show_ui' => true,
			'show_in_menu'=> 'dh_popup',
			'query_var' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array(
				'title',
				'editor',
			),
		) );
		
		register_post_type( 'dh_popup_campaign', array(
			'labels' => array(
				'add_new_item' => __( 'Add Campaign', 'dh_popup' ),
				'name' => __( 'Campaign', 'dh_popup' ),
				'singular_name' => __( 'Campaign', 'dh_popup' ),
				'edit_item' => __( 'Edit Campaign', 'dh_popup' ),
				'view_item' => __( 'View Campaign', 'dh_popup' ),
				'search_items' => __( 'Search', 'dh_popup' ),
				'not_found' => __( 'No Campaign found', 'dh_popup' ),
				'not_found_in_trash' => __( 'No Campaign found in Trash', 'dh_popup' ),
			),
			'public' => false,
			'has_archive' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu'=> 'dh_popup',
			'query_var' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array(
				'title',
			),
		) );
	}
}
DH_Popup_Post_Types::init();
endif;