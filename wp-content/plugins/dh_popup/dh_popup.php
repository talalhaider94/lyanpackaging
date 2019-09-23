<?php
/*
* Plugin Name: DHPopup - Popup Builder for Visual Composer
* Plugin URI: http://sitesao.com/
* Description: Easily builder Modal Popup in Visual Composer
* Version: 1.0.5
* Author: Sitesao team
* Author URI: http://sitesao.com/
* License: License GNU General Public License version 2 or later;
* Copyright 2017  Sitesao team
*/
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!defined('DH_POPUP_VERSION'))
	define('DH_POPUP_VERSION','1.0.5');

if(!defined('DH_POPUP_URL'))
	define('DH_POPUP_URL',untrailingslashit( plugins_url( '/', __FILE__ ) ));

if(!defined('DH_POPUP_DIR'))
	define('DH_POPUP_DIR',untrailingslashit( plugin_dir_path( __FILE__ ) ));

class DH_Popup {
	public function __construct(){
		add_action( 'plugins_loaded', array($this,'plugins_loaded'), 9 );
		register_activation_hook(__FILE__,array(&$this, 'activate'));
		register_deactivation_hook(__FILE__,array(&$this, 'deactivate'));
	}
	
	public function plugins_loaded(){
		if(!defined('WPB_VC_VERSION')){
			add_action('admin_notices', array(&$this,'notice'));
			return;
		}
		$this->_includes();
		add_action('init',array(&$this,'init'));
		

		add_action( 'vc_after_init', array(&$this,'editor_init') );
		
		$this->initShortcode();
		
		add_filter('single_template',array(&$this,'single_template'),10,3);
	}
	
	public function init(){
		load_plugin_textdomain( 'dh_popup', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		dh_popup_get_request_uri();
		add_action('wp_ajax_dh_popup_form_ajax', array(&$this,'submit_form'));
		add_action('wp_ajax_nopriv_dh_popup_form_ajax', array(&$this,'submit_form'));
		DH_Popup_Analytics::init();
		DH_Popup_Targeting::init();
	}
	
	public function single_template($template, $type, $templates){
		$object = get_queried_object();
		if ( ! empty( $object->post_type ) && 'dh_popup'===$object->post_type ) {
			return DH_POPUP_DIR.'/includes/editor-templates/single.php';
		}
		return $template;
	}
	
	public function activate(){
		if(!class_exists('DH_Popup_Analytics'));
			require_once DH_POPUP_DIR.'/includes/analytics.php';
		if(!class_exists('DH_Popup_Log'))
			require_once DH_POPUP_DIR.'/includes/log.php';
		DH_Popup_Analytics::create_tables();
		DH_Popup_Log::create_tables();
	}
	
	public function deactivate(){
		
	}
	
	public function is_vc_plugin_activate(){
		if(!function_exists('is_plugin_active'))
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		return is_plugin_active('js_composer/js_composer.php');
	}
	
	public function notice(){
		echo '<div class="updated">
			    <p>' . sprintf('<strong>%s</strong> requires <strong><a href="http://codecanyon.net/item/visual-composer-page-builder-for-wordpress/242431?ref=Sitesao" target="_blank">Visual Composer</a></strong> plugin  to be installed and activated on your site.', 'Popup Builder for Visual Composer') . '</p>
			  </div>';
	}
	
	private function _includes(){
		require_once DH_POPUP_DIR.'/includes/functions.php';
		require_once DH_POPUP_DIR.'/includes/post-types.php';
		require_once DH_POPUP_DIR.'/includes/targeting.php';
		require_once DH_POPUP_DIR.'/includes/lib/helper.php';
		require_once DH_POPUP_DIR.'/includes/assets.php';
		require_once DH_POPUP_DIR.'/includes/form.php';
		require_once DH_POPUP_DIR.'/includes/mail.php';
		require_once DH_POPUP_DIR.'/includes/submission.php';
		require_once DH_POPUP_DIR.'/includes/front-end.php';
		require_once DH_POPUP_DIR.'/includes/log.php';
		require_once DH_POPUP_DIR.'/includes/campaign.php';
		require_once DH_POPUP_DIR.'/includes/analytics.php';
		
		if(is_admin()){
			require_once DH_POPUP_DIR.'/includes/admin.php';
		}
	}
	
	public function editor_init(){
		global $dh_popup_editor,$dh_popup_editor_frontend;
		require_once DH_POPUP_DIR.'/includes/editor.php';
		$dh_popup_editor = new DH_Popup_Editor();
		$dh_popup_editor->addHooksSettings();
		if(dh_popup_is_enable_editor_frontend()){
			require_once DH_POPUP_DIR.'/includes/editor-frontend.php';
			$dh_popup_editor_frontend = new DH_Popup_Editor_Frontend();
			$dh_popup_editor_frontend->init();
		}
	}
	
	
	
	public function initShortcode(){
		require_once DH_POPUP_DIR.'/includes/vc-map.php';
		$vcmap = new DH_Popup_VCMap();
		add_action( 'vc_after_set_mode', array($vcmap,'loadShortcodes'));
	}
	
	public function submit_form() {
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'XMLHttpRequest' !== $_SERVER['HTTP_X_REQUESTED_WITH'] && $_SERVER['REQUEST_METHOD'] !== 'POST' )
			die(0);
		$nonce = $_POST['_dh_popup_nonce'];
		if (false != wp_verify_nonce($nonce,'dh_popup_'.$_POST['_dh_popup']) && isset( $_POST['_dh_popup'] ) ) {
			$_popup_form  = DH_Popup_Form::get_instance((int)$_POST['_dh_popup']);
			if ( $_popup_form ) {
				$response = $_popup_form->submit();
				wp_send_json($response);
			}
		}
		die(0);
	}
	
}

new DH_Popup();