<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_VCMap {
	public function addShortcodeSettings(){
		require_once vc_path_dir( 'CONFIG_DIR', 'content/vc-icon-element.php' );
		$icons_params = vc_map_integrate_shortcode( vc_icon_element_params(), 'i_', '', array(
			'include_only_regex' => '/^(type|icon_\w*)/',
			// we need only type, icon_fontawesome, icon_blabla..., NOT color and etc
		), array(
			'element' => 'add_icon',
			'value' => 'true',
		) );
		if ( is_array( $icons_params ) && ! empty( $icons_params ) ) {
			foreach ( $icons_params as $key => $param ) {
				if ( is_array( $param ) && ! empty( $param ) ) {
					if ( isset( $param['admin_label'] ) ) {
						// remove admin label
						unset( $icons_params[ $key ]['admin_label'] );
					}
				}
			}
		}
	
		$params = array_merge( array (
			array (
				"type" => "textfield",
				"heading" => __ ( "Label", 'dh_popup' ),
				"param_name" => "label",
				'admin_label' => true
			),
			array (
				"type" => "textfield",
				"heading" => __ ( "Name", 'dh_popup' ),
				"param_name" => "name",
				'admin_label' => true,
				"description" => __ ( 'Field name is required.  Please enter single word, no spaces, no start with number. Underscores(_) allowed', 'dh_popup' )
			),
			array (
				"type" => "dropdown",
				"heading" => __ ( "Field type", 'dh_popup' ),
				"param_name" => "type",
				"std"=>"text",
				"value"=>array(
					"Text"=>"text",
					"Email"=>"email",
				),
			),
			array (
				"type" => "textfield",
				"heading" => __ ( "Default value", 'dh_popup' ),
				"param_name" => "default_value"
			),
			array (
				"type" => "textfield",
				"heading" => __ ( "Placeholder text", 'dh_popup' ),
				"param_name" => "placeholder"
			),
			array(
				'type' => 'checkbox',
				'heading' => __( 'Add icon?', 'dh_popup' ),
				'param_name' => 'add_icon',
			),
		),$icons_params,array(
				array (
					"type" => "textarea",
					"heading" => __ ( "Help text", 'dh_popup' ),
					"param_name" => "help_text",
					'description' => __ ( 'This is the help text for this form control.', 'dh_popup' )
				),
				array (
					"type" => "checkbox",
					"heading" => __ ( "Required ? ", 'dh_popup' ),
					"param_name" => "required",
					"value" => array (
						__ ( 'Yes, please', 'dh_popup' ) => '1'
					)
				),
				array (
					"type" => "checkbox",
					"heading" => __ ( "Read only ? ", 'dh_popup' ),
					"param_name" => "readonly",
					"value" => array (
						__ ( 'Yes, please', 'dh_popup' ) => '1'
					)
				),
				vc_map_add_css_animation(),
				array (
					'type' => 'textfield',
					'heading' => __ ( 'Extra class name', 'dh_popup' ),
					'param_name' => 'el_class',
					'description' => __ ( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'dh_popup' )
				),
				array (
					"type" => "textfield",
					"heading" => __ ( "Width", 'dh_popup' ),
					"param_name" => "width",
					"description"=>__("Enter height of field, ex: 30px, 100%...",'dh_popup'),
					'group' => __( 'Design Options', 'dh_popup' ),
				),
				array (
					"type" => "textfield",
					"heading" => __ ( "Height", 'dh_popup' ),
					"param_name" => "height",
					"description"=>__("Enter height of field, ex: 30px",'dh_popup'),
					'group' => __( 'Design Options', 'dh_popup' ),
				),	
				array(
					'type' => 'css_editor',
					'heading' => __( 'CSS box', 'dh_popup' ),
					'param_name' => 'input_css',
					'group' => __( 'Design Options', 'dh_popup' ),
				),
			)
		);
		return array (
			"name" => __ ( "Popup Text Field", 'dh_popup' ),
			"base" => "dh_popup_text_field",
			"category" => __ ( "Popup Field", 'dh_popup' ),
			"icon" => "icon-dh-popup-text-field",
			"description"=>__('Text field for Popup builder','dh_popup'),
			"params" =>$params,
		);
	}
	
	public function mapShortcodes(){
		vc_lean_map( 'dh_popup_text_field', array(
			$this,
			'addShortcodeSettings',
		) );
	}
	
	public function loadShortcodes(){
		add_action( 'vc_after_mapping', array(
			$this,
			'mapShortcodes',
		) );
	}
}