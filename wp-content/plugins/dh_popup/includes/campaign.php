<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if(!class_exists('DH_Popup_Campaign')){

class DH_Popup_Campaign {
	public static $post_type = 'dh_popup_campaign';
	
	public static function init(){
	}
	
	public static function admin_init(){
		add_filter( 'manage_edit-dh_popup_campaign_columns', array( __CLASS__, 'edit_columns' ) );
		add_action( 'manage_dh_popup_campaign_posts_custom_column', array( __CLASS__, 'custom_columns' ), 2 );
		
		// Views and filtering
		add_filter( 'views_edit-dh_popup_campaign', array( __CLASS__, 'custom_order_views' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'remove_row_actions' ), 10, 1 );
		add_filter( 'post_row_actions', array( __CLASS__, 'add_row_actions' ), 10, 2 );
		add_filter('display_post_states', '__return_false');
		//
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_meta_boxes' ), 1000 );
		
	}
	
	public static function remove_meta_boxes() {
		remove_meta_box( 'vc_teaser',self::$post_type , 'side' );
		remove_meta_box( 'commentsdiv', self::$post_type , 'normal' );
		remove_meta_box( 'commentstatusdiv', self::$post_type , 'normal' );
		remove_meta_box( 'slugdiv', self::$post_type , 'normal' );
		remove_meta_box('mymetabox_revslider_0', self::$post_type, 'normal');
	}
	
	public static function custom_order_views($views){
		unset( $views['publish'] );
	
		if ( isset( $views['trash'] ) ) {
			$trash = $views['trash'];
			unset( $views['draft'] );
			unset( $views['trash'] );
			$views['trash'] = $trash;
		}
	
		return $views;
	}
	
	public static function add_row_actions($actions){
		global $post;
		$actions['delete'] = "<a class='submitdelete' id='dhvc_form_submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
		return $actions;
	}
	
	public static function remove_row_actions( $actions ) {
		if ( self::$post_type === get_post_type() ) {
			unset( $actions['view'] );
			unset( $actions['trash'] );
			unset( $actions['inline hide-if-no-js'] );
		}
	
		return $actions;
	}
	
	public static function edit_columns( $existing_columns ) {
		$columns = array();
	
		$columns['cb']             			= $existing_columns['cb'];
		$columns['adtesting_id']       		= __( 'ID', 'dh_popup' );
		$columns['title']     				= __( 'Title', 'dh_popup' );
		$columns['impressions']     		= __( 'Impressions', 'dh_popup' );
		$columns['conversions']     		= __( 'Conversions', 'dh_popup' );
		$columns['rate']     				= __( 'Rate', 'dh_popup' );
	
		unset($existing_columns['title']);
		unset($existing_columns['cb']);
		unset($existing_columns['date']);
	
		return array_merge($columns,$existing_columns);
	}
	
	public static function custom_columns( $column ) {
		global $post;
		switch ( $column ) {
			case 'impressions':
				echo absint(dh_popup_get_post_meta('impressions',$post->ID));
			break;
			case 'conversions':
				echo absint(dh_popup_get_post_meta('conversions',$post->ID));
			break;
			case 'rate':
				$impressions =  absint(dh_popup_get_post_meta('impressions',$post->ID));
				$conversions = absint(dh_popup_get_post_meta('conversions',$post->ID));
				if($impressions > 0)
					echo round(($conversions / $impressions) * 100, 2) . '%';
				else
					echo '--';
			break;
			case 'adtesting_id':
				echo get_the_ID();
			break;
		}
	}
}
}