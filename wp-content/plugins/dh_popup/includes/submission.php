<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Submission{
	
	private static $instance;
	
	/**
	 * 
	 * @var DH_Popup_Form
	 */
	private $_popup_form;
	private $_status = 'init';
	private $_posted_data = array();
	private $_uploaded_files = array();
	private $_skip_mail = false;
	private $_response = '';
	private $_invalid_fields = array();
	private $_meta = array();
	
	/**
	 * 
	 * @param DH_Popup_Form $popup_form
	 * @return NULL|DH_Popup_Submission
	 */
	public static function get_instance( DH_Popup_Form $popup_form = null ) {
		if ( empty( self::$instance ) ) {
			if ( null == $popup_form ) {
				return null;
			}
	
			self::$instance = new self;
			self::$instance->_popup_form = $popup_form;
			self::$instance->_skip_mail = $popup_form->is_demo_mode();
			self::$instance->_setup_posted_data();
			self::$instance->_submit();
		} elseif ( null != $popup_form ) {
			return null;
		}
	
		return self::$instance;
	}
	
	public function get_status() {
		return $this->_status;
	}
	
	public function is( $status ) {
		return $this->_status == $status;
	}
	
	public function get_response() {
		return $this->_response;
	}
	
	public function get_meta($key=''){
		if(!empty($key) && isset($this->_meta[$key]))
			return $this->_meta[$key];
		return $this->_meta;
	}
	
	public function get_invalid_field( $name ) {
		if ( isset( $this->_invalid_fields[$name] ) ) {
			return $this->_invalid_fields[$name];
		} else {
			return false;
		}
	}
	
	public function get_invalid_fields() {
		return $this->_invalid_fields;
	}
	
	public function get_posted_data( $name = '' ) {
		if ( ! empty( $name ) ) {
			if ( isset( $this->_posted_data[$name] ) ) {
				return $this->_posted_data[$name];
			} else {
				return null;
			}
		}
	
		return $this->_posted_data;
	}
	
	private function _sanitize_posted_data($value){
		if ( is_array( $value ) ) {
			$value = array_map( array( $this, '_sanitize_posted_data' ), $value );
		} elseif ( is_string( $value ) ) {
			$value = wp_check_invalid_utf8( $value );
			$value = wp_kses_no_null( $value );
		}
	
		return $value;
	}
	
	private function _setup_posted_data(){
		$posted_data = (array) $_POST;
		$posted_data = array_diff_key($posted_data, array( '_dh_popup_nonce' => '' ) );
		$posted_data = $this->_sanitize_posted_data( $posted_data );
		$fields = $this->_popup_form->scan_fields_in_shortcodes();
		foreach ( (array) $fields as $field ) {
			if ( empty( $field['name'] ) ) {
				continue;
			}
			$name = $field['name'];
			$value = '';
				
			if ( isset( $posted_data[$name] ) ) {
				$value = $posted_data[$name];
			}
			$posted_data[$name] = $value;
		}
		$this->_posted_data = apply_filters('dh_popup_posted_data', $posted_data);
		return $this->_posted_data;
	}
	
	private function _get_remote_ip_addr(){
		return dh_popup_get_remote_ip_addr();
	}
	
	private function _get_request_url(){
		$home_url = untrailingslashit( home_url() );
		
		if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] )
			&& 'XMLHttpRequest' == $_SERVER['HTTP_X_REQUESTED_WITH'] ) {
				$referer = isset( $_SERVER['HTTP_REFERER'] )
				? trim( $_SERVER['HTTP_REFERER'] ) : '';
		
				if ( $referer && 0 === strpos( $referer, $home_url ) ) {
					return esc_url_raw( $referer );
				}
		}
	
		$url = preg_replace( '%(?<!:|/)/.*$%', '', $home_url ). dh_popup_get_request_uri();
	
		return $url;
	}
	
	private function _validate(){
		if($this->_invalid_fields)
			return false;	
		$posted_data = $this->_posted_data;
		$fields = $this->_popup_form->scan_fields_in_shortcodes();
		$invalid_fields = array();
		$popup_form = $this->_popup_form;
		$field['type'] = isset($field['type']) ? $field['type'] : 'text';
		foreach ($fields as $field){
			$name = esc_attr($field['name']);
			$value = $posted_data[$name];
			if($field['field_tag']=='dh_popup_text_field' && $field['type']!='email' && isset($field['required']) && '' == $value ){
				$invalid_fields[] = array(
					'field'=>$name,
					'message'=>$popup_form->get_message('invalid_required')
				);
			}
			if($field['type']=='email'){
				if(isset($field['required']) && '' == $value ){
					$invalid_fields[] = array(
						'field'=>$name,
						'message'=>$popup_form->get_message('invalid_required')
					);
				}elseif (''!= $value && !is_email($value)){
					$invalid_fields[] = array(
						'field'=>$name,
						'message'=>$popup_form->get_message('invalid_email')
					);
				}
			}
		}

		$this->_invalid_fields = apply_filters('dh_popup_validate_fields', $invalid_fields, $fields, $posted_data);
		return !empty($this->_invalid_fields) ? false : true;
	}
	
	
	private function _submit(){
		$timestamp = current_time( 'timestamp' );
		$this->_meta = array(
			'site_url'=>home_url('/'),
			'remote_ip' => $this->_get_remote_ip_addr(),
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '',
			'url' => $this->_get_request_url(),
			'popup_name'=>$this->_popup_form->title,
			'popup_id'=>$this->_popup_form->id,
			'datetime_submitted'=>date_i18n(get_option( 'date_format' ).' '.get_option('time_format'),$timestamp)
		);
		$posted_data = $this->_posted_data;
		$popup_form = $this->_popup_form;
		if(!$this->_validate()){
			$this->_status = 'validation_failed';
			$this->_response = $popup_form->get_message( 'validation_error' );
			return $this->_status;
		}
		$scan_fields = $this->_popup_form->scan_fields_in_shortcodes();
		
		$popup_type = $popup_form->get_type();
		$custom_fields = array();
		foreach ($scan_fields as $s_field){
			$custom_field_name = esc_attr($s_field['name']);
			$value = isset($posted_data[$custom_field_name]) ? $posted_data[$custom_field_name] : '';
			if(!empty($value) && strtoupper($custom_field_name) !=='EMAIL'){
				$custom_fields[$custom_field_name] = $value;
			}
		}
		$email = isset($posted_data['email']) ? $posted_data['email'] : ( isset($posted_data['EMAIL']) ? $posted_data['EMAIL'] : '');
		$name = isset($posted_data['name']) ? $posted_data['name'] : ( isset($posted_data['NAME']) ? $posted_data['NAME'] : '');

		switch ($popup_type){
			case 'mailchimp':
				$mailchimp_api = dh_popup_get_option('mailchimp_api',false);
				$list_id = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($list_id))
					$list_id = dh_popup_get_option('mailchimp_list',0);
				if(empty($mailchimp_api) || empty($list_id)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				$merge_vars = $custom_fields;
				$mailchimp_welcome_email= dh_popup_get_option('mailchimp_welcome_email');
				$mailchimp_opt_in = dh_popup_get_option('mailchimp_opt_in');
				$mailchimp_replace_interests = dh_popup_get_option('mailchimp_replace_interests');
				$send_welcome = !empty($mailchimp_welcome_email) ? true : false;
				$double_optin = !empty($mailchimp_opt_in) ? true : false;
				$replace_interests = !empty($mailchimp_replace_interests) ? true : false;
				$mailchimp_group_name = dh_popup_get_option('mailchimp_group_name','');
				$mailchimp_group = dh_popup_get_option('mailchimp_group','');
				if(!empty($mailchimp_group) && !empty($mailchimp_group_name)){
					$merge_vars['GROUPINGS'] = array(
						array('name'=>$mailchimp_group_name, 'groups'=>$mailchimp_group),
					);
				}
				list($mc_subscribe_status,$mc_subscribe_message) = dh_popup_mailchimp_subscribe($mailchimp_api,array(
					'list_id'=>$list_id,
					'email'=>$email,
					'merge_vars'=>$merge_vars,
					'double_optin'=>$double_optin,
					'replace_interests'=>$replace_interests,
					'send_welcome'=>$send_welcome
				));
				$this->_status = $mc_subscribe_status;
				if($mc_subscribe_status === 'subscribe_success' || $mc_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($mc_subscribe_status);
				else
					$this->_response = $mc_subscribe_message;
			break;
			case 'constantcontact':
				$api_username = dh_popup_get_option('ctct_username','');
				$api_password = dh_popup_get_option('ctct_password','');
				//$list_id = dh_popup_get_option('ctct_list',0);
				$list_id = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($list_id))
					$list_id = dh_popup_get_option('ctct_list',0);
				if(empty($api_username) || empty($api_password) || empty($list_id)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				$additional_fields = $custom_fields;
				$send_welcome = !empty($mailchimp_welcome_email) ? true : false;
				list($ctct_subscribe_status,$ctct_subscribe_message) = dh_popup_ctct_subscribe($api_username,$api_password,array(
					'list_id'=>$list_id,
					'email'=>$email,
					'additional_fields'=>$additional_fields,
					'send_welcome'=>$send_welcome
				));
				$this->_status = $ctct_subscribe_status;
				if($ctct_subscribe_status === 'subscribe_success' || $ctct_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($ctct_subscribe_status);
				else
					$this->_response = $ctct_subscribe_message;
			break;
			case 'aweber':
				$aweber_account = dh_popup_get_option('aweber_account','');
				//$aweber_list = dh_popup_get_option('aweber_list',0);
				$aweber_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($aweber_list))
					$aweber_list = dh_popup_get_option('aweber_list',0);
				if(!$aweber_account['consumerKey'] || 
					!$aweber_account['consumerSecret'] || 
					!$aweber_account['accessToken'] || 
					!$aweber_account['accessSecret'] || 
					!$aweber_list
				){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				$additional_fields = $custom_fields;
				
				list($aweber_subscribe_status,$aweber_subscribe_message) = dh_popup_aweber_subscribe(
					$aweber_account,
					$aweber_list,
					$email,
					$name,
					$this->_get_remote_ip_addr(),
					$additional_fields
				);
				$this->_status = $aweber_subscribe_status;
				if($aweber_subscribe_status === 'subscribe_success' || $aweber_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($aweber_subscribe_status);
				else
					$this->_response = $aweber_subscribe_message;
			break;
			case 'convertkit':
				//$convertkit_form = dh_popup_get_option('convertkit_form','');
				$convertkit_form = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($aweber_list))
					$convertkit_form = dh_popup_get_option('convertkit_form',0);
				$api_key = dh_popup_get_option('convertkit_api_key','');
				if(empty($convertkit_form) || empty($api_key) ){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				
				list($convertkit_subscribe_status,$convertkit_subscribe_message) = dh_popup_convertkit_subscribe(
					$api_key, 
					$convertkit_form ,
					$email, 
					$name
				);
				$this->_status = $convertkit_subscribe_status;
				if($convertkit_subscribe_status === 'subscribe_success' || $convertkit_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($convertkit_subscribe_status);
				else
					$this->_response = $convertkit_subscribe_message;
			break;
			case 'getresponse':
				//$getresponse_list = dh_popup_get_option('getresponse_list','');
				$getresponse_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($getresponse_list))
					$getresponse_list = dh_popup_get_option('getresponse_list',0);
				$api_key = dh_popup_get_option('getresponse_api_key','');
				if(empty($getresponse_list) || empty($api_key)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				
				list($getresponse_subscribe_status,$getresponse_subscribe_message) = dh_popup_getresponse_subscribe(
					$api_key,
					$getresponse_list ,
					$email,
					$name
				);
				$this->_status = $getresponse_subscribe_status;
				if($getresponse_subscribe_status === 'subscribe_success' || $getresponse_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($getresponse_subscribe_status);
				else
					$this->_response = $getresponse_subscribe_message;
			break;
			case 'campaignmonitor':
				//$campaignmonitor_list = dh_popup_get_option('campaignmonitor_list','');
				$campaignmonitor_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($campaignmonitor_list))
					$campaignmonitor_list = dh_popup_get_option('campaignmonitor_list',0);
				$api_key = dh_popup_get_option('campaignmonitor_api_key','');
				if(empty($api_key) || empty($campaignmonitor_list) ){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				
				list($campaignmonitor_subscribe_status,$campaignmonitor_subscribe_message) = dh_popup_campaignmonitor_subscribe(
					$campaignmonitor_list,
					$api_key ,
					$email,
					$name,
					$custom_fields
				);
				$this->_status = $campaignmonitor_subscribe_status;
				if($campaignmonitor_subscribe_status === 'subscribe_success' || $campaignmonitor_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($campaignmonitor_subscribe_status);
				else
					$this->_response = $campaignmonitor_subscribe_message;
			break;
			case 'activecampaign':
				//$activecampaign_list = dh_popup_get_option('activecampaign_list','');
				$activecampaign_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($activecampaign_list))
					$activecampaign_list = dh_popup_get_option('activecampaign_list',0);
				$api_key = dh_popup_get_option('activecampaign_api_key','');
				$api_url = dh_popup_get_option('activecampaign_api_url','');
				$activecampaign_opt_in = dh_popup_get_option('activecampaign_opt_in');
				$double_optin = !empty($activecampaign_opt_in) ? true : false;
				if(empty($api_key) || empty($api_url) || empty($activecampaign_list) ){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				
				list($activecampaign_subscribe_status,$activecampaign_subscribe_message) = dh_popup_activecampaign_subscribe(
					$api_url,
					$api_key ,
					$activecampaign_list,
					$email,
					$name,
					$double_optin,
					$custom_fields
				);
				$this->_status = $activecampaign_subscribe_status;
				if($activecampaign_subscribe_status === 'subscribe_success' || $activecampaign_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($activecampaign_subscribe_status);
				else
					$this->_response = $activecampaign_subscribe_message;
			break;
			case 'madmimi':
				$madmimi_username = dh_popup_get_option('madmimi_username','');
				$madmimi_api_key = dh_popup_get_option('madmimi_api_key','');
				//$madmimi_list = dh_popup_get_option('madmimi_list','');
				$madmimi_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($madmimi_list))
					$madmimi_list = dh_popup_get_option('madmimi_list',0);
				if(empty($madmimi_api_key) || empty($madmimi_api_key) || empty($madmimi_list)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				
				list($madmimi_subscribe_status,$madmimi_subscribe_message) = dh_popup_madmimi_subscribe(
					$madmimi_username,
					$madmimi_api_key ,
					$madmimi_list,
					$email,
					$name,
					$custom_fields
				);
				$this->_status = $madmimi_subscribe_status;
				if($madmimi_subscribe_status === 'subscribe_success' || $madmimi_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($madmimi_subscribe_status);
				else
					$this->_response = $madmimi_subscribe_message;
			break;
			case 'benchmarkemail':
				$benchmarkemail_api_key = dh_popup_get_option('benchmarkemail_api_key','');
				//$benchmarkemail_list = dh_popup_get_option('benchmarkemail_list','');
				$benchmarkemail_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($benchmarkemail_list))
					$benchmarkemail_list = dh_popup_get_option('benchmarkemail_list',0);
				if(empty($benchmarkemail_api_key) || empty($benchmarkemail_list)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				$firstname= isset($posted_data['firstname']) ? $posted_data['firstname'] : $name;
				$lastname= isset($posted_data['lastname']) ? $posted_data['lastname'] : $name;
				
				list($benchmarkemail_subscribe_status,$benchmarkemail_subscribe_message) = dh_popup_benchmarkemail_subscribe(
					$benchmarkemail_api_key,
					$benchmarkemail_list ,
					$email,
					$firstname,
					$lastname
				);
				
				$this->_status = $benchmarkemail_subscribe_status;
				if($benchmarkemail_subscribe_status === 'subscribe_success' || $benchmarkemail_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($benchmarkemail_subscribe_status);
				else
					$this->_response = $benchmarkemail_subscribe_message;
			break;
			case 'streamsend':
				$streamsend_login_id = dh_popup_get_option('streamsend_login_id','');
				$streamsend_api_key = dh_popup_get_option('streamsend_api_key','');
				$streamsend_audience = dh_popup_get_option('streamsend_audience','');
				//$streamsend_list = dh_popup_get_option('streamsend_list','');
				$streamsend_list = dh_popup_get_post_meta('mailing_list',$popup_form->id,'');
				if(empty($streamsend_list))
					$streamsend_list = dh_popup_get_option('streamsend_list',0);
				if(empty($streamsend_login_id) || empty($streamsend_api_key) || empty($streamsend_audience) || empty($streamsend_list)){
					$this->_status = 'error_config';
					$this->_response = $popup_form->get_message('error_config');
					return $this->_status;
				}
				list($streamsend_subscribe_status,$streamsend_subscribe_message) = dh_popup_streamsend_subscribe(
					$streamsend_login_id,
					$streamsend_api_key ,
					$streamsend_audience,
					$streamsend_list,
					$email,
					$custom_fields
				);
				$this->_status = $streamsend_subscribe_status;
				if($streamsend_subscribe_status === 'subscribe_success' || $streamsend_subscribe_status === 'unknown_error')
					$this->_response = $popup_form->get_message($streamsend_subscribe_status);
				else
					$this->_response = $streamsend_subscribe_message;
			break;
		}
		/*TODO: Update log*/
		DH_Popup_Log::add_log($popup_form->id,$email,array($this->_status=>$this->_response),$custom_fields);
		if($this->_status==='subscribe_success'){
			/*TODO: Update conversions*/
// 			DH_Popup_Analytics::update_stats($object_id);
			DH_Popup_Analytics::update_stats($posted_data['_campaign_id'],'conversions');
			DH_Popup_Analytics::update_stats($popup_form->id,'conversions');
			$notice_admin = dh_popup_get_post_meta('notice_admin',$popup_form->id);
			if(!empty($notice_admin)){
				$notice_template = $this->_popup_form->get_email_setting('notice_admin');
				$result = DH_Popup_Mail::send($notice_template,'notice_admin',$posted_data);
			}
			$confirm_email = dh_popup_get_post_meta('confirm_email',$popup_form->id);
			if(!empty($confirm_email)){
				$confirm_template = $this->_popup_form->get_email_setting('confirm_email',array('recipient'=>$posted_data['email']));
				$result = DH_Popup_Mail::send($confirm_template,'confirm_email');
			}
		}
		do_action('dhvc_form_submit',$popup_form, $this);
		return $this->_status;
	}
}