<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Form {
	private static $form_post = null;
	private static $form_post_type = 'dh_popup';
	private static $current = null;
	private $_scanned_fields = null; // Tags scanned at the last time of scan()
	public $id;
	public $name;
	public $title;
	public $content;
	private function __construct( $post = null ) {
		$post = get_post( $post );
		
		if ( $post && self::$form_post_type == get_post_type( $post ) ) {
			$this->id = $post->ID;
			$this->name = $post->post_name;
			$this->title = $post->post_title;
			$this->content = $post->post_content;
			self::$form_post = $post;
		}
	}
	
	
	public static function get_form_post(){
		return self::$form_post;
	}
	
	public static function get_current() {
		return self::$current;
	}
	
	public function is_demo_mode(){
		return false;
	}
	
	public function get_message($message_id){
		$messages = apply_filters('dh_popup_messages', array(
			'invalid_required'=>__('Please fill in the required field.','dh_popup'),
			'invalid_email'=>__('Email address seems invalid.','dh_popup'),
			'validation_error'=>__('Validation errors occurred. Please confirm the fields.','dh_popup'),
			'error_config'=>__('Config errors occurred. Please contact to admin.','dh_popup'),
			'subscribe_success'=>__('Subscribe success','dh_popup'),
			'unknown_error'=>__('Unknown error','dh_popup')
		));
		if(isset($messages[$message_id]))
			return $messages[$message_id];
		return $messages;
	}
	
	public function send_email_notice_admin(){
		return (bool) dh_popup_get_post_meta('notice_admin',$this->id) ? true : false;
	}
	
	public function send_email_confirm(){
		return (bool) dh_popup_get_post_meta('confirm_email',$this->id) ? true : false;
	}
	
	/**
	 * 
	 * @param int $post
	 * @return boolean|DH_Popup_Form
	 */
	public static function get_instance( $post ) {
		$post = get_post( $post );
	
		if ( ! $post || self::$form_post_type != get_post_type( $post ) ) {
			return false;
		}
	
		return self::$current = new self( $post );
	}
	
	public function scan_fields_in_shortcodes(){
		if(empty($this->_scanned_fields)){
			$shortcode = $this->content;
			preg_replace_callback('/' . $this->_tag_regex() . '/s',array( $this, 'scan_field_callback' ),$shortcode );
		}
		return $this->_scanned_fields;
	}
	
	private function scan_field_callback($m, $replace = false){
		// allow [[foo]] syntax for escaping a tag
		if ( $m[1] == '[' && $m[6] == ']' ) {
			return substr( $m[0], 1, -1 );
		}
		
		$field_tag = $m[2];
		$field_attr = (array) shortcode_parse_atts( $m[3] );
		$field_attr['field_tag'] = $field_tag;
		$this->_scanned_fields[] = $field_attr;
		return $m[0];
	}
	
	public function get_type(){
		$mailing_list_type = dh_popup_get_option('mailing_list');
		$use_mailing = dh_popup_get_post_meta('use_mailing',$this->id,'no');
		if(!empty($mailing_list_type) && 'yes'===$use_mailing)
			return $mailing_list_type;
		return 'null';
	}
	
	private function _tag_regex() {
		$tagnames = array('dh_popup_text_field');
		$tagregexp = join( '|', array_map( 'preg_quote', $tagnames ) );
		return '(\[?)'
			. '\[(' . $tagregexp . ')(?:[\r\n\t ](.*?))?(?:[\r\n\t ](\/))?\]'
				. '(?:([^[]*?)\[\/\2\])?'
					. '(\]?)';
	}
	
	
	private function _validate_fields($fields){
		$posted_data = $this->_posted_data;
		$invalid_fields = array();
		foreach ($fields as $field){
			$name = esc_attr(sanitize_title($field['name']));
			$value = $posted_data[$name];
			if($field['field_tag']=='dh_popup_text_field' && $field['type']!='email' && isset($field['required']) && '' == $value ){
				$invalid_fields[] = array(
					'field'=>$name,
					'message'=>__('Please fill in the required field.','dh_popup') 
				);
			}
			if($field['type']=='email'){
				if(isset($field['required']) && '' == $value ){
					$invalid_fields[] = array(
						'field'=>$name,
						'message'=>__('Please fill in the required field.','dh_popup')
					);
				}elseif (''!= $value && !is_email($value)){
					$invalid_fields[] = array(
						'field'=>$name,
						'message'=>__('Email address seems invalid.','dh_popup')
					);
				}
			}
			$this->_invalid_fields = apply_filters('dh_popup_validate_fields', $invalid_fields,$fields,$posted_data);
		}
		return !empty($this->_invalid_fields) ? $this->_invalid_fields : true;
	}
	
	public function submit(){
		$submission = DH_Popup_Submission::get_instance( $this );
		
		$result = array(
			'into'=>'#dh_popup_'.$this->id,
			'popup_id' => $this->id,
			'status' => $submission->get_status(),
			'message' => $submission->get_response(),
			'demo_mode' => $this->is_demo_mode(),
		);
		
		if ( $submission->is( 'validation_failed' ) ) {
			$result['invalidFields'] = $submission->get_invalid_fields();
		}
		
		do_action( 'dh_popup_form_submit', $this, $result );
		
		$result = apply_filters('dh_popup_form_submit_result', $result, $this);
		wp_send_json($result);
		die;
	}
	
	/**
	 * 
	 * @param string $type
	 * @param string $args
	 * @return Ambigous <multitype:, multitype:string array object , multitype:string array >
	 */
	public function get_email_setting($type='notice_admin',$args=''){
		$email_settings = array(
			'sender'=>'',
			'subject'=>'',
			'additional_headers'=>'',
			'body'=>'',
			'recipient'=>'',
			'use_html'=>'',
		);
		foreach ($email_settings as $setting=>$value){
			$meta_key = '_dh_popup_'.$type.'_'.$setting;
			if(metadata_exists('post', $this->id, $meta_key)){
				$email_settings[$setting] = dh_popup_get_post_meta($type.'_'.$setting,$this->id,'');
			}
		}
		$email_settings = wp_parse_args($args,$email_settings);
		$email_settings = apply_filters('dh_popup_email_settings', $email_settings,$type,$this);
		return $email_settings;
	}
}