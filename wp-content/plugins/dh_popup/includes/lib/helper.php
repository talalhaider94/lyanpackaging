<?php

function dh_popup_get_mailchimp_list($mailchimp_api,$default=array()){
	$lists = array();
	if(empty($mailchimp_api))
		return $default;
	if(!class_exists('Mailchimp'))
		require_once DH_POPUP_DIR.'/includes/lib/mailchimp/Mailchimp.php';
	try {
		$mailchimp = new Mailchimp(
			$mailchimp_api,
			array(
				'ssl_verifypeer' => false
			)
		);
		$mc_lists = $mailchimp->lists->getList();
		if( ! empty( $mc_lists ) && isset( $mc_lists['total'] ) ){
			if($mc_lists['total'] > 0){
				$lists = array(__('Select a list &hellip;','dh_popup'));
				foreach( $mc_lists['data'] as $list ){
					$lists[ $list['id'] ] = sprintf(__('ID: %1$s - Name: %2$s','dh_popup'),$list['id'],$list['name']);
				}
			}else{
				$lists = array(__("You have not created any lists at MailChimp",'dh_popup'));
			}
		}else{
			$lists = array(__("Unable to load MailChimp lists, check your API Key.", 'dh_popup'));
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_mailchimp_subscribe($mailchimp_api,$args=''){
	$args = wp_parse_args($args,array(
		'list_id'=>'',
		'email'=>array(),
		'merge_vars'=>array(),
		'double_optin'=>false,
		'replace_interests'=>false,
		'send_welcome'=>false
	));
	if(!class_exists('Mailchimp'))
		require_once DH_POPUP_DIR.'/includes/lib/mailchimp/Mailchimp.php';
	$mailchimp = new Mailchimp(
		$mailchimp_api,
		array(
			'ssl_verifypeer' => false
		)
	);
	$status = 'unknown_error';
	$message = '';
	try{
		$subscriber = $mailchimp->lists->subscribe(
			$args['list_id'], 
			array('email'=>$args['email']),
			$args['merge_vars'],'html',
			$args['double_optin'],
			false,
			$args['replace_interests'],
			$args['send_welcome']
		);
		if(! empty( $subscriber['leid'] )){
			$status = 'subscribe_success';
		}
	}catch (Exception $e){
		if($e->getCode() == 214){
			$status = 'subscribe_success';
		}else{
			$status = $e->getCode();
			$message = $e->getMessage();
		}
	}
	return array($status,$message);
}

function dh_popup_get_ctct_list($api_username, $api_password,$default=array()){
	if(empty($api_username) || empty($api_password))
		return $default;
	$er = error_reporting();
	error_reporting(0);
	if(!class_exists('cc'))
		require_once DH_POPUP_DIR.'/includes/lib/constantcontact/class.cc.php';
	try{
		$ctct = new cc($api_username, $api_password);
		$lists = array();
		$ctct_lists = $ctct->get_all_lists('lists');
		//var_dump($ctct_lists);die;
		if ($ctct_lists){
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ((array)$ctct_lists as $list)
				$lists[$list['id']] = $list['Name'];
		}else{
			$lists = array(__("You have not created any lists at Constant Contact",'dh_popup'));
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	error_reporting($er);
	return $lists;
}

function dh_popup_ctct_subscribe($api_username, $api_password,$args=''){
	$args = wp_parse_args($args,array(
		'list_id'=>'',
		'email'=>'',
		'additional_fields'=>'',
		'send_welcome'=>false,
	));
	$status = 'unknown_error';
	$message = '';
	if(!class_exists('cc'))
		require_once DH_POPUP_DIR.'/includes/lib/constantcontact/class.cc.php';
	try {
		$ctct = new cc($api_username, $api_password);
		if ($send_welcome)
			$ctct->set_action_type('contact');
		$contact = $ctct->query_contacts($args['email']);
		if ($contact)
		{
			$ret = $ctct->update_contact($contact['id'], $args['email'], $args['list_id'], $args['additional_fields']);
			if ($ret){
				$status = 'subscribe_success';
			}else{
				$status = $ctct->http_response_code;
				$message = $ctct->http_get_response_code_error($cc->http_response_code);
			}
		}else{
			$new_id = $ctct->create_contact( $args['email'], $args['list_id'], $args['additional_fields']);
			if ($new_id){
				$status = 'subscribe_success';
			}else{
				$status = $ctct->http_response_code;
				$message = $ctct->http_get_response_code_error($cc->http_response_code);
			}
		}
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}


function dh_popup_get_aweber_account($authorization_code){
	if(empty($authorization_code)){
		list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = null;
	}else{
		if(!class_exists('AWeberAPI'))
			require_once DH_POPUP_DIR.'/includes/lib/aweber/aweber_api.php';
		try {
			list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = AWeberAPI::getDataFromAweberID($authorization_code);
		} catch (AWeberAPIException $exc) {
			list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = null;
		} catch (AWeberOAuthDataMissing $exc) {
			list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = null;
		} catch (AWeberException $exc) {
			list($consumerKey, $consumerSecret, $accessToken, $accessSecret) = null;
		}
	}
	$aweber_account = array(
		'consumerKey' => $consumerKey,
		'consumerSecret' => $consumerSecret,
		'accessToken' => $accessToken,
		'accessSecret' => $accessSecret,
	);
	return $aweber_account;
}

function dh_popup_get_aweber_list($aweber_account, $default=array()){
	if(!isset($aweber_account['consumerKey'],$aweber_account['consumerSecret'],$aweber_account['accessToken'],$aweber_account['accessSecret']))
		return $default;
	if(!class_exists('AWeberAPI'))
		require_once DH_POPUP_DIR.'/includes/lib/aweber/aweber_api.php';
	$lists = array();
	try{
		$aweber = new AWeberAPI($aweber_account['consumerKey'], $aweber_account['consumerSecret']);
		$account = $aweber->getAccount($aweber_account['accessToken'] , $aweber_account['accessSecret'] );
		$aweber_lists = $account->lists;
		if($aweber_lists)
		{
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ((array) $aweber_lists->data['entries'] as $list)
			{
				$lists[$list['id']] = $list['name'];
			}
		}else{
			$lists = array(__("You have not created any lists at AWeber",'dh_popup'));
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_aweber_subscribe($aweber_account, $list_id, $email, $name ='', $ip_address, $custom_fields = array()){
	
	if(!class_exists('AWeberAPI'))
		require_once DH_POPUP_DIR.'/includes/lib/aweber/aweber_api.php';
	$status = 'unknown_error';
	$message = '';
	try{
		$aweber = new AWeberAPI($aweber_account['consumerKey'], $aweber_account['consumerSecret']);
		$account = $aweber->getAccount($aweber_account['accessToken'] , $aweber_account['accessSecret'] );
		$list = $account->loadFromUrl('/accounts/' . $account->id . '/lists/' . $list_id);
		$list->subscribers->create(array(
			'email' =>$email,
			'ip_address' => $ip_address,
			'name' => $name,
			'custom_fields'=>$custom_fields
		));
		$status = 'subscribe_success';
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_convertkit_form($api_key,$default=array()){
	if(empty($api_key))
		return $default;
	if(!class_exists('DH_ConvertKit_API'))
		require_once DH_POPUP_DIR.'/includes/lib/convertkit/convertkit-api.php';
	$lists = array();
	try{
		$api = new DH_ConvertKit_API($api_key);
		$forms = $api->get_forms();
		if ( isset( $forms[0]['id'] ) && '-2' === $forms[0]['id'] ) {
			$lists = array(__("Error connecting to API.",'dh_popup'));
		} else {
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ((array) $forms as $form)
			{
				$lists[$form['id']] = $form['name'];
			}
		}
		
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_convertkit_subscribe($api_key, $form_id ,$email, $name){
	if(!class_exists('DH_ConvertKit_API'))
		require_once DH_POPUP_DIR.'/includes/lib/convertkit/convertkit-api.php';
	$status = 'unknown_error';
	$message = '';
	try{
		$api = new DH_ConvertKit_API($api_key);
		$ret = $api->form_subscribe($form_id, array(
			'email' => $email,
			'name' => $name,
		));
		$status = 'subscribe_success';
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}


function dh_popup_get_getresponse_list($api_key,$default = array()){
	if(empty($api_key))
		return $default;
	if(!class_exists('GetResponse'))
		require_once DH_POPUP_DIR.'/includes/lib/getresponse/GetResponseAPI3.class.php';
	$lists = array();
	try{
		$api = new GetResponse($api_key);
		$getresponse_lists = $api->getCampaigns();
		if(isset($getresponse_lists->message)){
			$lists = array($getresponse_lists->message);
			return $lists;
		}
		$lists = array(__('Select a list &hellip;','dh_popup'));
		foreach ($getresponse_lists as $list)
		{
			$lists[$list->campaignId] = $list->name;
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_getresponse_subscribe($api_key, $form_id ,$email, $name){
	if(!class_exists('GetResponse'))
		require_once DH_POPUP_DIR.'/includes/lib/getresponse/GetResponseAPI3.class.php';
	$status = 'unknown_error';
	$message = '';
	try{
		$api = new GetResponse($api_key);
		$ret = $api->addContact(array(
			'email' => $email,
			'name' => $name,
			'campaign'=>array('campaignId' => $form_id)
		));
		if (isset($ret->codeDescription)){
			$status = $ret->code;
			$message = $ret->codeDescription;
		}else{
			$status = 'subscribe_success';
		}
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_campaignmonitor_list($client_id,$api_key,$default=array()){
	if(empty($client_id))
		return $default;	
	if(!class_exists('CS_REST_Clients'))
		require_once DH_POPUP_DIR.'/includes/lib/campaignmonitor/csrest_clients.php';
	$lists = array();
	try{
		$api = new CS_REST_Clients($client_id, $api_key);
		$campaignmonitor_lists = $api->get_lists();
		if($campaignmonitor_lists->was_successful()){
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ((array) $campaignmonitor_lists->response as $list)
			{
				$lists[$list->ListID] = $list->Name;
			}
		}else{
			$lists = array($cs_list->response->Message);
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_campaignmonitor_subscribe($list_id, $api_key, $email, $name, $custom_fields=array()){
	if(!class_exists('CS_REST_Subscribers'))
		require_once DH_POPUP_DIR.'/includes/lib/campaignmonitor/csrest_subscribers.php';
	$CustomFields = array();
	if(!empty($custom_fields)){
		foreach ((array)$custom_fields as $key=>$value)
		{
			$CustomFields[] = array(
				'Key' => $key,
				'Value' => $value
			);
		}
	}
	$status = 'unknown_error';
	$message = '';
	try {
		$api = new CS_REST_Subscribers($list_id, $api_key);
		$ret = $api->add(array(
			'EmailAddress'=>$email,
			'Name'=>$name,
			'CustomFields'=>$CustomFields
		));
		if($ret->was_successful()){
			$status = 'subscribe_success';
		}else{
			$status = 'subscribe_failed';
			$message=$ret->response->Message;
		}
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_activecampaign_list($api_url,$api_key,$default = array()){
	if(empty($api_url) || empty($api_key))
		return $default;
	if(!class_exists('ActiveCampaign'))
		require_once DH_POPUP_DIR.'/includes/lib/activecampaign/ActiveCampaign.class.php';
	$lists = array();
	try{
		$api = new ActiveCampaign($api_url, $api_key);
		$activecampaign_lists = $api->api("list/list?ids=all");
		if(!empty($activecampaign_lists) && $activecampaign_lists->success){
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ((array) $activecampaign_lists as $list)
				if(is_object($list))
						$lists[$list->id] = $list->name;
		}else{
			$lists = array($activecampaign_lists->error);
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}	

function dh_popup_activecampaign_subscribe($api_url,$api_key, $list_id, $email, $name='', $double_optin=false, $custom_fields = array()){
	if(!class_exists('ActiveCampaign'))
		require_once DH_POPUP_DIR.'/includes/lib/activecampaign/ActiveCampaign.class.php';
	$status = 'unknown_error';
	$message = '';
	try {
		$api = new ActiveCampaign($api_url, $api_key);
		$contact = array(
			"email"              => $email,
			"p[{$list_id}]"      => $list_id,
			"status[{$list_id}]" => 1, // "Active" status
		);
		if(!empty($name)){
			$contact['first_name'] = $name;
			$contact['last_name']	=	$name;
		}
		if($double_optin)
			$contact["instantresponders[{$list_id}]"] = 1;
		if(isset($custom_fields['name']))
			unset($custom_fields['name']);
		if(!empty($custom_fields))
			foreach ($custom_fields as $key=>$value)
				$contact['field[%' . $key . '%,0]'] = $value;
			
		$contact_sync = $api->api("contact/sync", $contact);
		if (!(int)$contact_sync->success) {   
			$status = 'subscribe_failed';
			$message=$contact_sync->error;
		}else{
			$status = 'subscribe_success';
		}
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_madmimi_list($username,$api_key,$defaul=array()){
	if(empty($username) || empty($api_key))
		return $defaul;
	if(!class_exists('MadMimi'))
		require_once DH_POPUP_DIR.'/includes/lib/madmimi/MadMimi.class.php';
	$lists =array();
	try{
		$api = new MadMimi($username, $api_key);
		$madmimi_lists = $api->Lists();
		$xml_lists = new SimpleXMLElement($madmimi_lists);
		if($xml_lists->list){
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ($xml_lists->list as $list)
				$lists[(string) $list->attributes()->{'id'}->{0}] = (string) $list->attributes()->{'name'}->{0};
		}else{
			$lists = array(__("You have not created any lists at Mad Mimi",'dh_popup'));
		}		
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_madmimi_subscribe($username,$api_key, $list_id, $email, $name='',$custom_fields=array()){
	if(!class_exists('MadMimi'))
		require_once DH_POPUP_DIR.'/includes/lib/madmimi/MadMimi.class.php';
	$status = 'unknown_error';
	$message = '';
	try {
		$api = new MadMimi($username, $api_key);
		$user = array(
			'email'=>$email,
			'add_list'=>$list_id
		);
		if(!empty($name))
			$user['FirstName'] = $name;
		if(!empty($custom_fields))
			foreach ($custom_fields as $key=>$value)
				$user[$key] = $value;
		$ret = $api->AddUser($user);
		if (is_numeric($ret)) {   
			$status = 'subscribe_success';
		}else{
			$status = 'subscribe_failed';
			$message=$ret;
		}
			
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_benchmarkemail_list($api_key,$deafult = array()){
	if(empty($api_key))
		return $deafult;
	if(!class_exists('DH_Benchmarkemail'))
		require_once DH_POPUP_DIR.'/includes/lib/benchmarkemail/class.api.php';
	$lists=array();
	try{
		$api = new DH_Benchmarkemail($api_key);
		$benchmarkemail_lists = $api->get_lists();
		if(false === $benchmarkemail_lists){
			$lists = array(__("Unable to load BenchmarkEmail lists.", 'dh_popup'));
		}elseif (isset($benchmarkemail_lists['faultCode'])){
			$lists = array($benchmarkemail_lists['faultString']);
		}else{
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ($benchmarkemail_lists as $list){
				$lists[$list['id']] = $list['listname'];
			}
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_benchmarkemail_subscribe($api_key,$list_id,$email,$firstname='',$lastname=''){
	if(!class_exists('DH_Benchmarkemail'))
		require_once DH_POPUP_DIR.'/includes/lib/benchmarkemail/class.api.php';
	$status = 'unknown_error';
	$message = '';
	try {
		$api = new DH_Benchmarkemail($api_key);
		$ret = $api->subscribe($list_id, array(
			'email'=>$email,
			'firstname'=>$firstname,
			'lastname'=>$lastname,
		));
		if (isset( $ret['faultCode'] )) {
			$status = 'subscribe_failed';
			$message=$ret['faultString'];
		}else{
			$status = 'subscribe_success';
		}
			
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}

function dh_popup_get_streamsend_audiences($login_id,$key,$default = array()){
	if(empty($login_id) || empty($key))
		return $default;
	if(!class_exists('DH_Streamsend'))
		require_once DH_POPUP_DIR.'/includes/lib/streamsend/class.api.php';
	try {
		$api = new DH_Streamsend($login_id, $key);
		$audiences = $api->get_audiences();
		$xml_audiences = new SimpleXMLElement($audiences);
		if(isset($xml_audiences->error)){
			$lists = array( (string)$xml_audiences->error);
		}else{
			$lists = array(__('Select a audience&hellip;','dh_popup'));
			foreach ($xml_audiences->audience as $list){
				$lists[(string) $list->id] = (string) $list->name;
			}
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_get_streamsend_lists($login_id,$key,$audience_id,$default = array()){
	if(empty($login_id) || empty($key) || empty($audience_id))
		return $default;
	if(!class_exists('DH_Streamsend'))
		require_once DH_POPUP_DIR.'/includes/lib/streamsend/class.api.php';
	try {
		$api = new DH_Streamsend($login_id, $key);
		$streamsend_lists = $api->get_lists($audience_id);
		$xml_lists = new SimpleXMLElement($streamsend_lists);
		if(isset($xml_lists->error)){
			$lists = array( (string)$xml_lists->error);
		}else{
			$lists = array(__('Select a list &hellip;','dh_popup'));
			foreach ($xml_lists->list as $list){
				$lists[(string) $list->id] = (string) $list->name;
			}
		}
	}catch (Exception $e){
		$lists = array($e->getMessage());
	}
	if(empty($lists))
		$lists = $default;
	return $lists;
}

function dh_popup_streamsend_subscribe($login_id,$key,$audience_id,$list_id,$email,$custom_fields=array()){
	if(!class_exists('DH_Streamsend'))
		require_once DH_POPUP_DIR.'/includes/lib/streamsend/class.api.php';
	$status = 'unknown_error';
	$message = '';
	try {
		$api = new DH_Streamsend($login_id, $key);
		$ret = $api->subscribe($audience_id, $list_id, $email,$custom_fields);
		if (false === $ret) {
			$status = 'subscribe_failed';
			$message=__('Error subscribe','dh_popup');
		}else{
			$status = 'subscribe_success';
		}
			
	}catch (Exception $e){
		$status = $e->getCode();
		$message = $e->getMessage();
	}
	return array($status,$message);
}