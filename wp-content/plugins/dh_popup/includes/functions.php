<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function dh_popup_include_editor_template($template, $variables = array(), $once = false){
	is_array($variables) && extract($variables);
	if($once) {
		require_once DH_POPUP_DIR.'/includes/editor-templates/'.$template;
	} else {
		require DH_POPUP_DIR.'/includes/editor-templates/'.$template;
	}
}

function dh_popup_is_enable_editor_frontend(){
	$post_id = isset($_GET['post']) ? intval($_GET['post']) : (isset($_GET['post_id']) ? intval($_GET['post_id']) : 0);
	$post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
	$enable = 'dh_popup' === $post_type|| 'dh_popup'===get_post_type($post_id);
	return apply_filters('dh_popup_is_enable_editor_frontend',$enable);
}

function dh_popup_get_events($show_option_none=false){
	$events =array(
		'load'=>__('On page load','dh_popup'),
		'scroll'=>__('When scroll on page','dh_popup'),
		//'exit'=>__('On exit the page','dh_popup'),
		'inactivity'=>__('When inactivity','dh_popup'),
		'onclick'=>__('When click Element','dh_popup'),
	);
	$events = apply_filters('dh_popup_events', $events);
	if($show_option_none)
		return array(''=>__('Select a event &hellip;','dh_popup')) + $events;
	else
		return $events;
}

function dh_popup_get_display_mode(){
	return apply_filters('dh_popup_display_mode', array(
		'every-time'=>__('Every time','dh_popup'),
		'once-session'=>__('Once per session','dh_popup'),
		'once-period'=>__('Once per','dh_popup'),
		'once-only'=>__('Only once','dh_popup'),
	));
}

function dh_popup_get_display_in(){
	return apply_filters('dh_popup_display_in', array(
		'all'=>__('All page','dh_popup'),
		'home'=>__('Home page','dh_popup'),
		'posts'=>__('Posts','dh_popup'),
		'pages'=>__('Pages','dh_popup'),
		'others'=>__('Categories, Archive and other','dh_popup'),
	));
}

function dh_popup_dropdown_newsletters($show_option_none=true){
	$newsletters = apply_filters('dh_popup_dropdown_newsletters',array(
		'mailchimp'=>__("MailChimp",'dh_popup'),
		'constantcontact'=>__("Constant Contact",'dh_popup'),
		'aweber'=>__("AWeber",'dh_popup'),
		'convertkit'=>__("ConvertKit",'dh_popup'),
		'getresponse'=>__("GetResponse",'dh_popup'),
		'campaignmonitor'=>__("Campaign Monitor",'dh_popup'),
		'activecampaign'=>__('ActiveCampaign','dh_popup'),
		'madmimi'=>__('Mad Mimi','dh_popup'),
		'benchmarkemail'=>__('Benchmarkemail','dh_popup'),
		'streamsend'=>__('StreamSend','dh_popup'),
	));
	if($show_option_none)
		return array(''=>__('Select a type &hellip;','dh_popup')) + $newsletters;
	else 
		return $newsletters;
}

function dh_popup_get_cookie_prefix(){
	return apply_filters('dh_popup_cookie_prefix', 'dh_popup_'.get_current_blog_id().'_');
}

function dh_popup_get_remote_browser(){
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 254 ) : '';
}

function dh_popup_get_remote_ip_addr($checkproxy = true){
	if ($checkproxy && isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != null) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} else if ($checkproxy && isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != null) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		if (!empty($_SERVER['REMOTE_ADDR']))
			$ip = $_SERVER['REMOTE_ADDR'];
		else
			$ip = '';
	}
	return $ip;
}

function dh_popup_random($_length = 5) {
	$symbols = '123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$string = "";
	for ($i=0; $i<$_length; $i++) {
		$string .= $symbols[rand(0, strlen($symbols)-1)];
	}
	return $string;
}

function dh_popup_log_data(){
	$disable_log = dh_popup_get_option('disable_log');
	return apply_filters('dh_popup_log_data', !empty($disable_log) ? false : true);
}

function dh_popup_use_targeting(){
	$disable_advanced_display = dh_popup_get_option('disable_advanced_display');
	return apply_filters('dh_popup_use_targeting', !empty($disable_advanced_display) ? false : true);
}

function dh_popup_stats_data(){
	$disable_stats = dh_popup_get_option('disable_stats');
	return apply_filters('dh_popup_stats_data', !empty($disable_stats) ? false : true);
}

function dh_popup_variables($args=array()){
	$variables = apply_filters('dh_popup_variables',array(
		'[site_url]'=>'',
		'[remote_ip]'=>'',
		'[user_agent]'=>'',
		'[popup_name]'=>'',
		'[popup_id]'=>'',
		'[url]'=>'',
		'[datetime_submitted]'=>'',
	));
	if(empty($args))
		return $variables;
	foreach ($variables as $key=>$value){
		$variable_key = substr($key,1,-1);
		if(isset($args[$variable_key]))
			$variables[$key] = $args[$variable_key];
	}
	return $variables;
}


function dh_popup_get_request_uri() {
	static $request_uri = '';

	if ( empty( $request_uri ) ) {
		$request_uri = add_query_arg( array() );
	}

	return esc_url_raw( $request_uri );
}

function dh_popup_get_current_form() {
	if ( $current = DH_Popup_Form::get_current() ) {
		return $current;
	}
}

function dh_popup_strip_newline( $str ) {
	$str = (string) $str;
	$str = str_replace( array( "\r", "\n" ), '', $str );
	return trim( $str );
}

function dh_popup_update_post_meta($post_id, $meta_key, $meta_value){
	$meta_key = '_dh_popup_'.$meta_key;
	return update_post_meta($post_id, $meta_key, $meta_value);
}

function dh_popup_enqueue_css( $code ) {
	global $dh_popup_css;

	if ( empty( $dh_popup_css ) ) {
		$dh_popup_css = '';
	}

	$dh_popup_css .= dh_popup_css_minify($code);
}

function dh_popup_css_minify( $css ) {
	$css = preg_replace( '/\s+/', ' ', $css );
	$css = preg_replace( '/\/\*[^\!](.*?)\*\//', '', $css );
	$css = preg_replace( '/(,|:|;|\{|}) /', '$1', $css );
	$css = preg_replace( '/ (,|;|\{|})/', '$1', $css );
	$css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );
	$css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );
	return trim( $css );
}

function dh_popup_get_post_meta($meta = '',$post_id='',$default=null){
	$post_id = empty($post_id) ? get_the_ID() : $post_id;
	if(empty($meta))
		return false;
	$value = get_post_meta($post_id,'_dh_popup_'.$meta, true);
	if(is_numeric($value))
		$value = floatval($value);
	if($value !== "" && $value !== null && $value !== false)
		return apply_filters('dh_popup_get_post_meta', $value, $meta, $post_id);
	return apply_filters('dh_popup_get_post_meta', $default, $meta, $post_id);
}

function dh_popup_get_option($id,$default=null){
	global $dh_popup_options;
	
	if ( empty( $dh_popup_options ) ) {
		$dh_popup_options = get_option('dh_popup');
	}
	$value = $default;
	if (isset($dh_popup_options[$id])) {
		$value =  $dh_popup_options[$id];
	}
	return apply_filters('dh_popup_get_option', $value, $id);
}
