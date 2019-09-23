<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Assets {
	public function __construct(){
		if(is_admin()){
			add_action('admin_init', array(&$this,'register_vendor_assets'));
		}else{
			add_action( 'template_redirect', array(&$this,'register_vendor_assets'));
		}
	}
	
	public function register_vendor_assets(){
		wp_register_style( 'dh_popup_jqueryui',DH_POPUP_URL.'/assets/css/jquery-ui-smoothness/jquery-ui.min.css');
		wp_register_script('dh_popup_cookie', DH_POPUP_URL.'/assets/js-cookie/js.cookie.min.js',array('jquery'),'1.4.1',true);
		wp_register_style('dh_popup_fancybox',DH_POPUP_URL.'/assets/fancybox/jquery.fancybox.css',null,'2.1.7');
		wp_register_script('dh_popup_easing', DH_POPUP_URL.'/assets/js/jquery.easing.min.js',array('jquery'),'1.4.1',true);
		wp_register_script('dh_popup_fancybox',DH_POPUP_URL.'/assets/fancybox/jquery.fancybox.pack.js',array('jquery','dh_popup_easing'),'2.1.7',true);
		wp_register_script('dh_popup_imagesloaded',DH_POPUP_URL.'/assets/imagesloaded/imagesloaded.pkgd.min.js',array('jquery','dh_popup_easing'),'2.1.7',true);
	}
}
new DH_Popup_Assets();