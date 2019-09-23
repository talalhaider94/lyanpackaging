<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once vc_path_dir( 'EDITORS_DIR', 'navbar/class-vc-navbar.php' );
/**
 * Renders navigation bar for Editors.
 */
class DH_Popup_Editor_Navbar extends Vc_Navbar {
	protected $controls = array(
		'add_element',
		'templates',
		'save_backend',
		'preview_template',
		'custom_css',
		'frontend',
		'fullscreen',
		'windowed',
	);
	
	public function getControlFrontend() {
		if(dh_popup_is_enable_editor_frontend())
			return '<li class="vc_pull-right">'
			. '<a href="' . DH_Popup_Editor_Frontend::getInlineUrl() . '" class="vc_btn vc_btn-primary vc_btn-sm vc_navbar-btn" id="wpb-edit-inline">' . __( 'Frontend Builder (beta)', 'dh_popup' ) . '</a>'
				. '</li>';
		return '';
	}
	
	public function getControlTemplates() {
		return '<li><a href="javascript:;" class="vc_icon-btn vc_templates-button vc_navbar-border-right"  id="dh_popup_templates-editor-button" title="'
			. __( 'Templates', 'dh_popup' ) . '"><i class="vc-composer-icon vc-c-icon-add_template"></i></a></li>';
	}
	
	public function getControlPreviewTemplate() {
		return '<li class="vc_pull-right">'
			. '<a href="#" class="vc_btn vc_btn-grey vc_btn-sm vc_navbar-btn" data-vc-navbar-control="preview">' . __( 'Preview', 'dh_popup' ) . '</a>'
				. '</li>';
	}
	
	public function getControlSaveBackend() {
		return '<li class="vc_pull-right vc_save-backend">'
			. '<a class="vc_btn vc_btn-sm vc_navbar-btn vc_btn-primary vc_control-save" id="wpb-save-post">' . __( 'Update', 'dh_popup' ) . '</a>'
				. '</li>';
	}
}
