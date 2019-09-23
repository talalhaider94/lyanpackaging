<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Mail {

	private static $current = null;

	private $name = '';
	private $template = array();
	private $use_html = false;

	public static function get_current() {
		return self::$current;
	}

	public static function send( $template, $name = '' ) {
		self::$current = new self( $name, $template );
		return self::$current->compose();
	}

	private function __construct( $name, $template ) {
		$this->name = trim( $name );
		$this->use_html = (bool) $template['use_html'];

		$this->template = wp_parse_args( $template, array(
			'subject' => '',
			'sender' => '',
			'body' => '',
			'recipient' => '',
			'additional_headers' => '',
		) );
	}

	public function name() {
		return $this->name;
	}

	public function get( $component, $replace_tags = false ) {
		$use_html = ( $this->use_html && 'body' == $component );

		$template = $this->template;
		$component = isset( $template[$component] ) ? $template[$component] : '';

		if ( $replace_tags ) {
			$component = $this->replace_variables($component);
			if ( $use_html && ! preg_match( '%<html[>\s].*</html>%is', $component ) ) {
				$component = $this->htmlize( $component );
			}
		}

		return $component;
	}
	
	public function replace_variables($content){
		$variables = dh_popup_variables();
		$submission = DH_Popup_Submission::get_instance();
		$meta = $submission->get_meta();
		$content = strtr($content,dh_popup_variables($meta));
		return strtr($content, $submission->get_posted_data());
	}

	private function htmlize( $body ) {
		$header = apply_filters( 'dh_popup_mail_html_header',
			'<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . esc_html( $this->get( 'subject', true ) ) . '</title>
</head>
<body>
', $this );

		$footer = apply_filters( 'dh_popup_mail_html_footer',
'</body>
</html>', $this );

		$html = $header . wpautop( $body ) . $footer;
		return $html;
	}

	private function compose( $send = true ) {
		$components = array(
			'subject' => $this->get( 'subject', true ),
			'sender' => $this->get( 'sender', true ),
			'body' => $this->get( 'body', true ),
			'recipient' => $this->get( 'recipient', true ),
			'additional_headers' => $this->get( 'additional_headers', true ),
		);

		$components = apply_filters( 'dh_popup_mail_components',$components, dh_popup_get_current_form(), $this );

		if ( ! $send ) {
			return $components;
		}

		$subject = dh_popup_strip_newline( $components['subject'] );
		$sender = dh_popup_strip_newline( $components['sender'] );
		$recipient = dh_popup_strip_newline( $components['recipient'] );
		$body = $components['body'];
		$additional_headers = trim( $components['additional_headers'] );

		$headers = "From: $sender\n";

		if ( $this->use_html ) {
			$headers .= "Content-Type: text/html\n";
		}

		if ( $additional_headers ) {
			$headers .= $additional_headers . "\n";
		}
		return wp_mail( $recipient, $subject, $body, $headers );
	}
}
