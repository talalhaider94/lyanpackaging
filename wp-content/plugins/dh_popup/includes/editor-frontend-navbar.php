<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once vc_path_dir( 'EDITORS_DIR', 'navbar/class-vc-navbar-frontend.php' );

/**
 *
 */
class DH_Popup_Editor_Frontend_Navbar extends Vc_Navbar_Frontend {
	
	public function getControlTemplates() {
		return '<li><a href="javascript:;" class="vc_icon-btn vc_navbar-border-right"  id="dh_popup_templates-editor-button" title="'
			. __( 'Templates', 'dh_popup' ) . '"><i class="vc-composer-icon vc-c-icon-add_template"></i></a></li>';
	}
}
