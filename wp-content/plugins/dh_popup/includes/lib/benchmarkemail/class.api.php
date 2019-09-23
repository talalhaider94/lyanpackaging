<?php

class DH_Benchmarkemail {
	private $api_url = 'https://api.benchmarkemail.com/1.3/';
	private $api_key;
	public function __construct($api_key){
		$this->api_key = $api_key;
	}
	private function make_request() {
		$timeout = apply_filters('dh_popup_benchmarkemail_request_timeout', 20);
		//ini_set( 'default_socket_timeout', $timeout );
		if(!class_exists('IXR_Client'))
			require_once ABSPATH . WPINC . '/class-IXR.php';

		// Connect and Communicate
		$client = new IXR_Client($this->api_url, false, 443, $timeout );
		$start_time = microtime( true );
		$start_time_display = date( 'm/d/Y h:i:s A', current_time( 'timestamp' ) );
		$args = func_get_args();
		call_user_func_array( array( $client, 'query' ), $args );
		$response = $client->getResponse();
		$lapsed = round( microtime( true ) - $start_time, 2 );

		// If Over Limit, Disable for Five Minutes And Produce Warning
		if ( $lapsed >= $timeout ) {
			/*TODO*/
			//error
			return false;
		}
		// Otherwise Respond
		return $response;
	}
	
	public function get_lists(){
		return $this->make_request( 'listGet', $this->api_key, '', 1, 100, 'name', 'asc' );
	}
	
	/**
	 * 
	 * @param int $list_id
	 * @param array $data
	 */
	public function subscribe( $list_id, $data,$opt_in = false ) {
		return $this->make_request( 'listAddContacts', $this->api_key, $list_id, array($data) );
	}
}

?>