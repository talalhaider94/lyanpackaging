<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
/** @var $edit Vc_Backend_Editor */
// [add element box]
require_once vc_path_dir( 'EDITORS_DIR', 'popups/class-vc-add-element-box.php' );
$add_element_box = new Vc_Add_Element_Box();
$add_element_box->render();
// [/add element box]

// [rendering edit form]
visual_composer()->editForm()->render();
// [/rendering edit form]

// [rendering templates panel editor]
require_once DH_POPUP_DIR.'/includes/editor-templates.php';
$templates_editor = new DH_Popup_Editor_Templates();
$templates_editor->renderUITemplate();

// [/rendering templates panel editor]

// [post settings]
if ( vc_user_access()->part( 'post_settings' )->can()->get() ) {
	require_once vc_path_dir( 'EDITORS_DIR', 'popups/class-vc-post-settings.php' );
	$post_settings = new Vc_Post_Settings( $editor );
	$post_settings->renderUITemplate();
}
// [/post settings]

// [shortcode edit layout]
require_once vc_path_dir( 'EDITORS_DIR', 'popups/class-vc-edit-layout.php' );
$edit_layout = new Vc_Edit_Layout();
$edit_layout->renderUITemplate();
// [/shortcode edit layout]

dh_popup_include_editor_template('editor_shortcodes_templates.tpl.php' );
