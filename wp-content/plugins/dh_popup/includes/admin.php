<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Admin {
	public function __construct(){
		require_once DH_POPUP_DIR.'/includes/settings.php';
		require_once DH_POPUP_DIR.'/includes/meta-box.php';
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_dh_popup_preview',array(&$this,'render_popup_preview'), 5 );
		add_action( 'wp_ajax_dh_popup_editor_load_template_preview', array(&$this,'render_popup_preview') );
		
		DH_Popup_Analytics::admin_init();
		DH_Popup_Log::admin_init();
		DH_Popup_Campaign::admin_init();
		DH_Popup_Targeting::admin_init();
		// Admin Columns
		add_filter( 'manage_edit-dh_popup_columns', array( $this, 'edit_columns' ) );
		add_action( 'manage_dh_popup_posts_custom_column', array( $this, 'custom_columns' ), 2 );
		
		//
		add_action( 'save_post', array( &$this, 'editor_frontend_save' ) );
		
		// Views and filtering
		add_filter( 'views_edit-dh_popup', array( &$this, 'custom_order_views' ) );
		add_filter( 'post_row_actions', array( $this, 'remove_row_actions' ), 10, 1 );
		add_filter( 'post_row_actions', array( $this, 'add_row_actions' ), 10, 2 );
		add_filter('display_post_states', '__return_false');
		//
		add_action( 'add_meta_boxes', array( &$this, 'remove_meta_boxes' ), 1000 );
		
		//Ajax Status
		add_action( 'wp_ajax_dh_popup_change_status', array( __CLASS__, 'ajax_change_status' ) );
	}
	
	public function editor_frontend_save($post_id){
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if($popup_width = vc_post_param( 'popup_width' )){
			update_post_meta($post_id, '_dh_popup_width', $popup_width);
		}
		if($popup_height = vc_post_param( 'popup_height' )){
			update_post_meta($post_id, '_dh_popup_height', $popup_height);
		}
		
	}
	
	public function init(){
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ));
	}
	
	public function create_admin_menu(){
		add_menu_page(__('Popup Builder','dh_popup'), __('Popup Builder','dh_popup'), 'edit_posts', 'dh_popup',null,DH_POPUP_URL.'/assets/images/visual_composer.png','50.5');
	}
	
	public static function ajax_change_status(){
		if (check_admin_referer( 'dh_popup_change_status' ) ) {
			$popup_id = absint( $_GET['popup_id'] );
			$popup = get_post($popup_id);
			if ( 'dh_popup' === get_post_type( $popup ) ) {
				$old_status = get_post_status($popup);
				$new_status = $old_status != 'publish' ? 'publish' : 'private';
				global $wpdb;
				if ($wpdb->update($wpdb->posts, array('post_status' => $new_status), array('ID' => $popup_id))) {
					if (!$silent) 
						wp_transition_post_status($new_status, $old_status, $popup);
				}
			}
		}
		
		wp_safe_redirect( wp_get_referer() ? remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() ) : admin_url( 'edit.php?post_type=dh_popup' ) );
		die();
	}
	
	public function render_popup_preview(){
		global $wp_embed;
		WPBMap::addAllMappedShortcodes();
		visual_composer()->frontCss();
		visual_composer()->frontJsRegister();
		wp_enqueue_script( 'prettyphoto' );
		wp_enqueue_style( 'prettyphoto' );
		wp_enqueue_style( 'js_composer_front' );
		wp_enqueue_script( 'wpb_composer_front_js' );
		wp_enqueue_style( 'js_composer_custom_css' );
		wp_enqueue_style( 'font-awesome' );
		$post_id = (int) vc_request_param( 'post_id' );
		/*TODO*/
		$wp_embed->post_ID = $post_id;
		
		$template_unique_id = vc_request_param('template_unique_id');
		$preview_data = new stdClass();
		$preview_data->ID = -1;
		$preview_data->post_content='';
		$preview_data->custom_css='';
		$preview_data->post_type='dh_popup';
		$preview_data->is_preview = true;
		if(!empty($template_unique_id)){
			$template = DH_Popup_Editor::getPopupDemoTemplates($template_unique_id);
			$template_meta = $template['meta'];
			$preview_data->post_content = trim($template['template']);
			$preview_data->post_title =$template['name'];
			$preview_data->width = isset($template_meta['width']) ? $template_meta['width'] : '';
			$preview_data->height = isset($template_meta['height']) ? $template_meta['height'] : '';
			$preview_data->custom_css = isset($template_meta['custom_css']) ? $template_meta['custom_css'] : '';
		}elseif(!empty($post_id) && $post = get_post($post_id)){
			$preview_data->ID = $post->ID;
			$preview_data->post_content = $post->post_content;
			$preview_data->post_title =$post->post_title;
		}
		dh_popup_include_editor_template('preview.tpl.php',array(
			'post'=>$preview_data,//get_post($post_id)
		));
		die();
	}
	
	public function remove_meta_boxes() {
		remove_meta_box('mymetabox_revslider_0', 'dh_popup', 'normal');
		remove_meta_box( 'vc_teaser', 'dh_popup' , 'side' );
		remove_meta_box( 'commentsdiv', 'dh_popup' , 'normal' );
		remove_meta_box( 'commentstatusdiv', 'dh_popup' , 'normal' );
		remove_meta_box( 'slugdiv', 'dh_popup' , 'normal' );
	}
	
	public function custom_order_views($views){
		unset( $views['publish'] );
	
		if ( isset( $views['trash'] ) ) {
			$trash = $views['trash'];
			unset( $views['draft'] );
			unset( $views['trash'] );
			$views['trash'] = $trash;
		}
	
		return $views;
	}
	
	public function add_row_actions($actions){
		global $post;
		$actions['delete'] = "<a class='submitdelete' id='dhvc_form_submitdelete' title='" . esc_attr( __( 'Delete this item permanently' ) ) . "' href='" . get_delete_post_link( $post->ID, '', true ) . "'>" . __( 'Delete Permanently' ) . "</a>";
		return $actions;
	}
	
	public function remove_row_actions( $actions ) {
		if ( 'dh_popup' === get_post_type() ) {
			unset( $actions['view'] );
			unset( $actions['trash'] );
			unset( $actions['inline hide-if-no-js'] );
		}
	
		return $actions;
	}
	
	public function edit_columns( $existing_columns ) {
		$columns = array();
	
		$columns['cb']             			= isset($existing_columns['cb']) ? $existing_columns['cb'] : '';
		$columns['popup_id']       			= __( 'ID', 'dh_popup' );
		$columns['title']     				= __( 'Title', 'dh_popup' );
		$columns['impressions']     		= __( 'Impressions', 'dh_popup' );
		$columns['conversions']     		= __( 'Conversions', 'dh_popup' );
		$columns['rate']     				= __( 'Rate', 'dh_popup' );
		$columns['status']     				= __( 'Status', 'dh_popup' );
		
		unset($existing_columns['title']);
		unset($existing_columns['cb']);
		unset($existing_columns['date']);
	
		return array_merge($columns,$existing_columns);
	}
	
	public function custom_columns( $column ) {
		global $post;
		switch ( $column ) {
			case 'status':
				$status = $post->post_status;
				$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=dh_popup_change_status&popup_id=' . $post->ID ), 'dh_popup_change_status' );
				echo '<a href="' . esc_url( $url ) . '" title="'. __( 'Click to change status', 'dh_popup' ) . '">';
				if ('publish'!=$status ) {
					echo '<span class="dh_popup_status is-disable" ><i class="dashicons dashicons-hidden"></i> '.__('Disable','dh_popup').'</span>';
				} else {
					echo '<span class="dh_popup_status"><i class="dashicons dashicons-visibility"></i>  '.__('Enable','dh_popup').'</span>';
				}
				echo '</a>';
			break;
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
			case 'popup_id':
				echo get_the_ID();
			break;
		}
	}
	
}

new DH_Popup_Admin();