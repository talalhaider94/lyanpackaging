<?php
class DH_Streamsend{
	private $api_login_id;
	private $api_key;
	private $api_url = 'https://app.streamsend.com/';
	
	public function __construct($login_id,$key){
		$this->api_login_id = $login_id;
		$this->api_key = $key;
	}
	
	public function get_audiences(){
		return $this->call('audiences.xml');
	}
	
	public function subscribe($audience_id,$list_id,$email,$custom_fields=array()){
		$data = "<person><email-address>$email</email-address>";
		foreach ($custom_fields as $field=>$value){
			if(!empty($value)){
				$tag = str_replace('_', '-', $field);
				$data .= "<$tag>$value</$tag>";
			}
		}
		$data.='</person>';
		$find_person = $this->call("audiences/$audience_id/people.xml?email_address=$email");
		$find_person_xml = new SimpleXMLElement($find_person);
		$person_id = 0;
		if(isset($find_person_xml->person)){
			$person_id = (string)$find_person_xml->person->id;
			$this->call("audiences/$audience_id/people/$person_id.xml",'PUT', $data);
		}else{
			$person_id = $this->call("audiences/$audience_id/people.xml",'POST', $data);
		}
		if($person_id){
			$data = "<membership><list-id>$list_id</list-id><email-address>$email</email-address></membership>";
			return $this->call("audiences/$audience_id/memberships.xml",'POST', $data);
		}
		return false;
	}
	
	public function get_lists($audience_id){
		return $this->call("audiences/$audience_id/lists.xml");
	}
	
	private function call($api_method = '', $http_method = 'GET', $data = array()){
		$url = $this->api_url.'/'.$api_method;
		$headers = array(
			'Content-Type: application/xml',
			'Accept: application/xml'
		);
		try {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_USERPWD, "$this->api_login_id:$this->api_key");
			if (!empty($data)) {
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			if (!empty($http_method)) {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);
			}
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
			curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		} catch (Exception $e) {
			$response = false;
		}
		return $response;
	}
}