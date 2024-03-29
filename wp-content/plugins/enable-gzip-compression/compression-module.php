<?php
/**
 * Module for compression.
 */
require_once(__DIR__.'/enable-gzip-compression.php');
 
if (!defined('ABSPATH')) return;

require_once(ABSPATH . 'wp-admin/includes/file.php');

class EnableGzipCompressionModule {

	const HTACCESS_FILENAME = '.htaccess';

	public function toggle_gzip_compression() {
		$compression_enabled_initially = get_option('gzip_compression') === EnableGzipCompression::OPTION_ON;
		$result = FALSE;
		
		if ($compression_enabled_initially) {
			$result = $this->remove_gzip_compression_from_htaccess();
		}
		else {			
			$result = $this->add_gzip_compression_to_htaccess();
		}
				
		if ($result === FALSE) {
			update_option('gzip_htaccess_save', EnableGzipCompression::STATUS_ERROR);
			$this->redirect_to_settings_page();
		}
		else {
			$working = $this->is_gzip_compression_working_test();
			
			if (!$compression_enabled_initially && !$working) {
				$this->remove_gzip_compression_from_htaccess();
				update_option('gzip_test_result', EnableGzipCompression::STATUS_ERROR);
				$this->redirect_to_settings_page();
			} else {
				update_option('gzip_test_result', EnableGzipCompression::STATUS_OK);
			}
			
			if ($compression_enabled_initially) {
				update_option('gzip_compression', EnableGzipCompression::OPTION_OFF);				
			}
			else {
				update_option('gzip_compression', EnableGzipCompression::OPTION_ON);
			}
			update_option('gzip_htaccess_save', EnableGzipCompression::STATUS_OK);
		}
		
		$this->redirect_to_settings_page();
	}

	public function add_gzip_compression_to_htaccess() {
		$file = get_home_path() . self::HTACCESS_FILENAME;
		
		$lines = array();
		$lines[] = '<IfModule mod_deflate.c>';
		$lines[] = 'AddOutputFilterByType DEFLATE text/text text/html text/plain text/xml text/css application/x-javascript application/javascript';
		$lines[] = '</IfModule>';
		
		return insert_with_markers($file, EnableGzipCompression::PLUGIN_NAME, $lines);
	}
	
	public function remove_gzip_compression_from_htaccess() {
		$file = get_home_path() . self::HTACCESS_FILENAME;
		return insert_with_markers($file, EnableGzipCompression::PLUGIN_NAME, array());
	}
	
	private function is_gzip_compression_working_test() {
		$arguments = array(
			'headers' => array(
				'Content-Encoding' => 'gzip'
			)
		);
		
		$response = wp_remote_get(get_site_url(), $arguments);
		return strpos($response['headers']['content-encoding'], 'gzip') !== FALSE;
	}
	
	private function redirect_to_settings_page() {
		header('Location: ' . get_admin_url() . EnableGzipCompression::ADMIN_SETTINGS_URL);
		exit();
	}
	
}