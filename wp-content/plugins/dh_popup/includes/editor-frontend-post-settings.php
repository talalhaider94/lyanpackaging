<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once vc_path_dir( 'EDITORS_DIR', 'popups/class-vc-post-settings.php' );

class DH_Popup_Editor_Frontend_Post_Settings extends Vc_Post_Settings{
	public function renderUITemplate() {
		dh_popup_include_editor_template( 'editor_frontend-post-settings.tpl.php', array(
			'box' => $this
		) );
	}
}