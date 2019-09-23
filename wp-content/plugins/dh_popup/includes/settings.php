<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Settings {
	
	public function __construct(){
		add_action('admin_init', array(&$this,'admin_init'));
		add_action( 'admin_menu', array(&$this,'page_settings'),25);
		add_action('wp_ajax_dh_setting_select_ajax', array($this,'setting_select_ajax'));
	}
	
	
	public function admin_init(){
		register_setting('dh_popup','dh_popup',array(&$this,'register_setting_callback'));
	}
	
	public function register_setting_callback($options){
		$mailing_list_selected = isset($options['mailing_list']) ? $options['mailing_list'] : '';
		foreach ($this->_getMailingListSetting() as $mailing=>$mailing_settings){
			if($mailing!==$mailing_list_selected){
				foreach ($mailing_settings as $mailing_setting_id=>$mailing_setting){
					$options[$mailing_setting_id] = '';
					unset($options[$mailing_setting_id]);
				}
			}
		}
		if($mailing_list=='aweber' && isset($options['aweber_authorization_code'])){
			$aweber_account = dh_popup_get_aweber_account($options['aweber_authorization_code']);
			$options['aweber_account']=$aweber_account;
		}
		return $options;
	}
	
	public function page_settings(){
		$page = add_submenu_page( 'dh_popup', __('Popup Settings','dh_popup'), __('Settings','dh_popup'), 'manage_options', 'dh_popup_setting',array(&$this,'settings_render') );
		add_action( 'load-' . $page, array(&$this,'settings_load') );
	}
	
	private function _getSections(){
		return array(
			'general'=>__("General",'dh_popup'),
			'mailing'=>__("Mailing setting",'dh_popup'),
		);
	}
	
	private function _getFields($section){
		$fields = array(
			'general'=>array(
// 				'clear_cookie'=>array(
// 					'label' =>  __('Clear Popup Cookie', 'dhvc_popup'),
// 					'type' => 'button',
// 					'help'=>__('Clear popup cookie to test the popup on your live website.','dhvc_popup')
// 				),
				'ga_tracking'=>array(
					'type'=>'checkbox',
					'label'=>__('Google Analytics tracking','dh_popup'),
					'help'=>__("Send popup events to Google Analytics. Google Analytics must be installed on your website.",'dh_popup')
				),
				'hook'=>array(
					'type'=>'select',
					'label'=>__('Plugin run in action','dh_popup'),
					'value'=>'get_header',
					'options'=>array(
						'get_header'=>"get_header",
						'wp'=>"wp",
					),
				),
				'disable_log'=>array(
					'type'=>'checkbox',
					'label'=>__('Disable Log','dh_popup'),
					'help'=>__("Disable log subscribed data.",'dh_popup')
				),
				'disable_stats'=>array(
					'type'=>'checkbox',
					'label'=>__('Disable Stats','dh_popup'),
					'help'=>__("Disable stats data.",'dh_popup')
				),
				'disable_advanced_display'=>array(
					'type'=>'checkbox',
					'label'=>__('Disable Advanced Display','dh_popup'),
					'help'=>__("Disable advanced display.",'dh_popup')
				),
// 				'hide_popup_for_role'=>array(
// 					'type'=>'user_roles',
// 					'label'=>__('Disable popups for:','dh_popup'),
// 				),
			),
			'mailing'=>array(
				'mailing_list'=>array(
					'type'=>'select',
					'label'=>__('Mailing List Type','dh_popup'),
					'options'=>dh_popup_dropdown_newsletters(),
				),
				'mailing_list_setting_hr'=>array(
					'type'=>'hr',
				),
				'mailing_list_setting'=>array(
					'type'=>'mailing_list_setting',
				),
			),
		);
		if(isset($fields[$section]))
			return $fields[$section];
		return array();
	}
	
	private function _getMailingListSetting($key=''){
		$settings = array(
			'mailchimp'=>array(
				'mailchimp_api'=>array(
					'type'=>'text',
					'label'=>__('MailChimp API Key','dh_popup'),
					'help'=>__('Enter your API Key. <a href="http://admin.mailchimp.com/account/api-key-popup" target="_blank">Get your API key</a>','dh_popup')
				),
				'mailchimp_list'=>array(
					'type'=>'select',
					'label'=>__('MailChimp List','dh_popup'),
					'options'=>dh_popup_get_mailchimp_list(dh_popup_get_option('mailchimp_api',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add your MailChimp API Key above and save it this list will be populated.','dh_popup')
				),
				'mailchimp_opt_in'=>array(
					'type'=>'checkbox',
					'label'=>__('Enable Double Opt-In','dh_popup'),
					'help'=>__("Learn more about <a href='http://kb.mailchimp.com/article/how-does-confirmed-optin-or-double-optin-work' target='_blank'>Double Opt-in</a>.",'dh_popup')
				),
				'mailchimp_welcome_email'=>array(
					'type'=>'checkbox',
					'label'=>__('Send Welcome Email','dh_popup'),
					'help'=>__("If your Double Opt-in is false and this is true, MailChimp will send your lists Welcome Email if this subscribe succeeds - this will not fire if MailChimp ends up updating an existing subscriber. If Double Opt-in is true, this has no effect. Learn more about <a href='http://blog.mailchimp.com/sending-welcome-emails-with-mailchimp/' target='_blank'>Welcome Emails</a>.",'dh_popup')
				),
				'mailchimp_group_name'=>array(
					'type'=>'text',
					'label'=>__('Group Name','dh_popup'),
					'help'=>__('Optional: Enter the name of the group. Learn more about <a href="http://mailchimp.com/features/groups/" target="_blank">Groups</a>','dh_popup')
				),
				'mailchimp_group'=>array(
					'type'=>'text',
					'label'=>__('Group','dh_popup'),
					'help'=>__('Optional: Comma delimited list of interest groups to add the email to.','dh_popup')
				),
				'mailchimp_replace_interests'=>array(
					'type'=>'checkbox',
					'default'=>'1',
					'label'=>__('Replace Interests','dh_popup'),
					'help'=>__("Whether MailChimp will replace the interest groups with the groups provided or add the provided groups to the member's interest groups.",'dh_popup')
				),
			),
			'constantcontact'=>array(
				'ctct_username'=>array(
					'type'=>'text',
					'label'=>__('Constant Contact username','dh_popup'),
				),
				'ctct_password'=>array(
					'type'=>'password',
					'label'=>__('Constant Contact password','dh_popup'),
				),
				'ctct_list'=>array(
					'type'=>'select',
					'label'=>__('Constant Contact List','dh_popup'),
					'options'=>dh_popup_get_ctct_list(dh_popup_get_option('ctct_username',''), dh_popup_get_option('ctct_password',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add your username and password above and save it this list will be populated.','dh_popup')
				),
				'ctct_welcome_email'=>array(
					'type'=>'checkbox',
					'label'=>__('Send Welcome Email','dh_popup'),
				),
			),
			'aweber'=>array(
				'aweber_authorization_code'=>array(
					'type'=>'textarea',
					'label'=>__('AWeber authorization code','dh_popup'),
					'help'=>__('Enter authorization code. <a href="https://auth.aweber.com/1.0/oauth/authorize_app/6e8b6b59" target="_blank">Get your authorization code</a>','dh_popup')
				),
				'aweber_list'=>array(
					'type'=>'select',
					'label'=>__('AWeber List','dh_popup'),
					'options'=>dh_popup_get_aweber_list(dh_popup_get_option('aweber_account',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add authorization code above and save it this list will be populated.','dh_popup')
				),
			),
			'convertkit'=>array(
				'convertkit_api_key'=>array(
					'type'=>'text',
					'label'=>__('ConvertKit API Key','dh_popup'),
					'help'=>__('Enter api key. <a href="https://app.convertkit.com/account/edit" target="_blank">Get your ConvertKit api key</a>','dh_popup')
				),
				'convertkit_form'=>array(
					'type'=>'select',
					'label'=>__('ConvertKit Form','dh_popup'),
					'options'=>dh_popup_get_convertkit_form(dh_popup_get_option('convertkit_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add api key above and save it this list will be populated.','dh_popup')
				),
			),
			'getresponse'=>array(
				'getresponse_api_key'=>array(
					'type'=>'text',
					'label'=>__('GetResponse API Key','dh_popup'),
					'help'=>__('Enter api key. <a href="www.getresponse.com/learning-center/glossary/api-key.html" target="_blank">Get your api key</a>','dh_popup')
				),
				'getresponse_list'=>array(
					'type'=>'select',
					'label'=>__('GetResponse List','dh_popup'),
					'options'=>dh_popup_get_getresponse_list(dh_popup_get_option('getresponse_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add api key above and save it this list will be populated.','dh_popup')
				),
			),
			'campaignmonitor'=>array(
				'campaignmonitor_api_key'=>array(
					'type'=>'text',
					'label'=>__('CampaignMonitor API Key','dh_popup'),
					'help'=>__('Enter Client ID. <a href="https://www.campaignmonitor.com/api/getting-started/#your-client-id" target="_blank">Get your api key</a>','dh_popup')
				),
				'campaignmonitor_client_id'=>array(
					'type'=>'text',
					'label'=>__('CampaignMonitor Client ID','dh_popup'),
					'help'=>__('Enter Client ID. <a href="https://www.campaignmonitor.com/api/getting-started/#your-client-id" target="_blank">Get your client id</a>','dh_popup')
				),
				'campaignmonitor_list'=>array(
					'type'=>'select',
					'label'=>__('CampaignMonitor List','dh_popup'),
					'options'=>dh_popup_get_campaignmonitor_list(dh_popup_get_option('campaignmonitor_client_id',''), dh_popup_get_option('campaignmonitor_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add Client ID and API Key above and save it this list will be populated.','dh_popup')
				),
			),
			'activecampaign'=>array(
				'activecampaign_api_url'=>array(
					'type'=>'text',
					'label'=>__('ActiveCampaign API URL','dh_popup'),
					'help'=>__('Enter Api URL. <a href="http://www.activecampaign.com/help/using-the-api/" target="_blank">Get your api url</a>','dh_popup')
				),
				'activecampaign_api_key'=>array(
					'type'=>'text',
					'label'=>__('ActiveCampaign Api Key','dh_popup'),
					'help'=>__('Enter Api Key. <a href="http://www.activecampaign.com/help/using-the-api/" target="_blank">Get your api key</a>','dh_popup')
				),
				'activecampaign_list'=>array(
					'type'=>'select',
					'label'=>__('ActiveCampaign List','dh_popup'),
					'options'=>dh_popup_get_activecampaign_list(dh_popup_get_option('activecampaign_api_url',''), dh_popup_get_option('activecampaign_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add API URL and API Key above and save it this list will be populated.','dh_popup')
				),
				'activecampaign_opt_in'=>array(
					'type'=>'checkbox',
					'label'=>__('Enable Double Opt-In','dh_popup'),
					'help'=>__("Learn more about <a href='https://help.activecampaign.com/hc/en-us/articles/115000853310-Double-opt-in-vs-single-opt-in' target='_blank'>Double Opt-in</a>.",'dh_popup')
				),
			),
			'madmimi'=>array(
				'madmimi_username'=>array(
					'type'=>'text',
					'label'=>__('Mad Mimi Username/Email','dh_popup'),
				),
				'madmimi_api_key'=>array(
					'type'=>'text',
					'label'=>__('Mad Mimi Api Key','dh_popup'),
				),
				'madmimi_list'=>array(
					'type'=>'select',
					'label'=>__('Mad Mimi List','dh_popup'),
					'options'=>dh_popup_get_madmimi_list(dh_popup_get_option('madmimi_username',''), dh_popup_get_option('madmimi_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add Username and Api Key above and save it this list will be populated.','dh_popup')
				),
			),
			'benchmarkemail'=>array(
				'benchmarkemail_api_key'=>array(
					'type'=>'text',
					'label'=>__('BenchmarkEmail Api Key','dh_popup'),
				),
				'benchmarkemail_list'=>array(
					'type'=>'select',
					'label'=>__('BenchmarkEmail List','dh_popup'),
					'options'=>dh_popup_get_benchmarkemail_list(dh_popup_get_option('benchmarkemail_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add Api Key above and save it this list will be populated.','dh_popup')
				),
			),
			'streamsend'=>array(
				'streamsend_login_id'=>array(
					'type'=>'text',
					'label'=>__('StreamSend Login ID','dh_popup'),
					'help'=>__('Enter Login ID. <a href="https://app.streamsend.com/account/settings" target="_blank">Get your login id</a>','dh_popup')
				),
				'streamsend_api_key'=>array(
					'type'=>'text',
					'label'=>__('StreamSend Api Key','dh_popup'),
					'help'=>__('Enter Api Key. <a href="https://app.streamsend.com/account/settings" target="_blank">Get your api key</a>','dh_popup')
				),
				'streamsend_audience'=>array(
					'type'=>'select',
					'label'=>__('StreamSend audiences','dh_popup'),
					'options'=>dh_popup_get_streamsend_audiences(dh_popup_get_option('streamsend_login_id',''),dh_popup_get_option('streamsend_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you add Login ID and Api Key above and save it this list will be populated.','dh_popup')
				),
				'streamsend_list'=>array(
					'type'=>'select',
					'label'=>__('StreamSend List','dh_popup'),
					'options'=>dh_popup_get_streamsend_lists(dh_popup_get_option('streamsend_login_id',''),dh_popup_get_option('streamsend_api_key',''),dh_popup_get_option('streamsend_audience',''),array(''=>__('Nothing Found&hellip;','dh_popup'))),
					'help'=>__('After you select StreamSend audiences above and save it this list will be populated.','dh_popup')
				),
			),
		);
		$settings = apply_filters('dh_popup_mailing_setting', $settings);
		if(isset($settings[$key]))
			return $settings[$key];
		return $settings;
	}
	
	public function settings_load(){
		wp_register_script( 'vc_accordion_script', vc_asset_url( 'lib/vc_accordion/vc-accordion.min.js' ), array( 'jquery' ), WPB_VC_VERSION, true );
		wp_register_script( 'dh_popup_setting', DH_POPUP_URL.'/assets/js/setting.js', array( 'vc_accordion_script','dh_popup_cookie' ), DH_POPUP_VERSION, true );
		wp_localize_script('dh_popup_setting', 'dh_popup_setting', array(
			'loading'=>__('Loading data&hellip;','dh_popup'),
			'ajax_url'=>admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'=>wp_create_nonce('dh_setting_select_ajax'),
			'notfound'=>__('Nothing Found&hellip;','dh_popup')
		));
		wp_enqueue_script('dh_popup_setting');
	}
	
	public function setting_select_ajax(){
		$nonce = $_POST['nonce'];
		$response=array('status'=>'failed');
		if (false != wp_verify_nonce($nonce,'dh_setting_select_ajax')) {
			$callback=$_POST['callback'];
			$params=$_POST['params'];
			$selected = dh_popup_get_option($_POST['field'],'');
			if(function_exists($callback))
			{
				$response['status']='success';
				$options = call_user_func_array($callback, $params);
				$response['options']=$options;
				$option_html = '';
				foreach ($options as $value=>$label)
					$option_html .= '<option '.selected($selected, $value, false).' value="'.$value.'">'.$label.'</option>';
				$response['option_html']=$option_html;
			}
		}
		wp_send_json($response);
		die(0);
	}
	
	
	public function settings_render(){
		?>
		<style type="text/css">
		.dh_popup-accordion-panel-body{
			padding: 0 10px;
			display: none;
		}
		.dh_popup-accordion-panel.vc_active .dh_popup-accordion-panel-body{
			display: block;
		}
		</style>
		<div class="wrap vc_settings" id="wpb-js-composer-settings">
			<h2><?php _e( 'Popup Settings', 'dh_popup' ); ?></h2>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php settings_fields('dh_popup'); ?>
				<div class="dh_popup-settings">
					<div class="dh_popup-accordion" data-vc-action="collapseAll">
						<?php $i = 1;?>
						<?php foreach ($this->_getSections() as $key=>$section):?>
						<div class="dh_popup-accordion-panel<?php echo $i==1 ? ' vc_active' : ''?>" id="<?php echo 'dh_popup_accordion_'.$key?>">
							<div class="widget" data-vc-accordion="" data-vc-container=".dh_popup-accordion" data-vc-target="<?php echo '#dh_popup_accordion_'.$key?>">
								<div class="widget-top" style="cursor: default;">
									<div class="widget-title-action">
										<button class="widget-action hide-if-no-js" type="button" aria-expanded="false">
											<span class="screen-reader-text">Setting Panel</span>
											<span class="toggle-indicator" aria-hidden="true"></span>
										</button>
									</div>
									<div class="widget-title">
										<h4>
											<?php echo esc_html( $section ) ?>
											<span class="in-widget-title"></span>
										</h4>
									</div>
								</div>
							</div>
							<div class="dh_popup-accordion-panel-body">
								<table class="form-table">
									<tbody>
										<?php foreach ($this->_getFields($key) as $id=>$params): ?>
										<?php $label = isset($params['label']) ? $params['label'] : ''?>
										<?php $type = isset($params['type']) ? $params['type']:''?>
										<?php if($type=='hr'):?>
										<tr valign="top">
											<th colspan="2" style="padding: 0;">
												<hr/>
											</th>
										</tr>
										<?php elseif($type==='mailing_list_setting'):?>
											<?php foreach ($this->_getMailingListSetting() as $mailing=>$mailing_settings):?>
												<?php foreach ($mailing_settings as $mailing_setting_id=>$mailing_setting):?>
												<tr valign="top" class="dh_popup_mailing_setting_<?php echo esc_attr($mailing)?>">
													<th scope="row" style="width: 200px">
														<label for="<?php echo $mailing_setting_id ?>">
															<?php echo isset($mailing_setting['label']) ? $mailing_setting['label'] : ''?>
														</label>
													</th>
													<?php $this->_render_seting_field($mailing_setting_id, $mailing_setting);?>
												</tr>
												<?php endforeach;?>
											<?php endforeach;?>
										<?php else:?>
										<tr valign="top">
											<th scope="row"  style="width: 200px"><label for="<?php echo $id ?>"><?php echo $label ?></label></th>
											<?php $this->_render_seting_field($id, $params);?>
										</tr>
										<?php endif;?>
										<?php endforeach;?>
									</tbody>
								</table>
							</div>
						</div>
						<?php $i++;?>
						<?php endforeach;?>
					</div>
				</div>
				<?php submit_button( __( 'Save Changes', 'dh_popup' ), 'primary', 'submit_btn', true ); ?>
			</form>
		</div>	
		<?php
	}
	
	private function _render_seting_field($id,$params){
		
		$params = wp_parse_args((array)$params,array(
			'type'=>'',
			'help'=>'',
			'label'=>'',
			'default'=>'',
			'help' =>'',
			'ajax_params'=>'',
			'ajax_fnc'=>'',
			'options'=>array()
		));
	
		extract($params,EXTR_SKIP);
	
		$name = 'dh_popup['.$id.']';
	
		echo '<td scope="row">';
		switch ($type){
			case 'text':
				echo '<input type="text" id="'.$id.'" value="'.dh_popup_get_option($id,$default).'" name="'.$name.'" />';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'button':
				echo '<button id="'.$id.'" class="dh_popup_clear_cookie button" style="display: inline-block;" type="button">'.$label.'</button>';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'textarea':
				echo '<textarea id="'.$id.'" name="'.$name.'" style=" height: 99px;width: 441px;">'.esc_textarea(dh_popup_get_option($id,$default)).'</textarea>';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'password':
				echo '<input type="password" id="'.$id.'" value="'.dh_popup_get_option($id,$default).'" name="'.$name.'" />';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'checkbox':
				echo '<input type="checkbox" id="'.$id.'" '.(dh_popup_get_option($id,$default) == '1' ? ' checked="checked"' : '' ).' value="1" name="'.$name.'">';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'color':
				echo '<input data-default-color="#336CA6" type="text" id="'.$id.'" value="'.dh_popup_get_option($id,$default).'" name="'.$name.'" />';
				echo '<script type="text/javascript">
						jQuery(document).ready(function($){
						    $("#'.$id.'").wpColorPicker();
						});
					 </script>
					 ';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'select':
				echo '<select id="'.$id.'" name="'.$name.'">';
				foreach ($options as $key=>$value){
					$selected = dh_popup_get_option($id,$default) == $key ? ' selected="selected"' : '';
					echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
				}
				echo '</select>';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'select_ajax':
				echo '<select data-name="'.$id.'" data-ajax_fnc="'.$ajax_fnc.'" data-ajax_params="'.$ajax_params.'" class="dh_setting_select_ajax" id="'.$id.'" name="'.$name.'" style="min-width: 200px;">';
				foreach ($options as $key=>$value){
					$selected = dh_popup_get_option($id,$default) == $key ? ' selected="selected"' : '';
					echo '<option value="'.$key.'" '.$selected.'>'.$value.'</option>';
				}
				echo '</select>';
				echo '<br/>';
				echo '<input class="button dh_setting_select_ajax_control" value="'.__('Load data','dh_popup').'" type="button">';
				if(!empty($help)){
					echo '<p>'.$help.'</p>';
				}
			break;
			case 'user_roles':
				$roles = get_editable_roles();
				if(!empty($roles)){
					foreach ((array) $roles as $role_id=>$role){
						echo '<input type="checkbox" name="'.$name.'['.$role_id.']"'.(array_key_exists($role_id, dh_popup_get_option($id,array())) ? ' checked="checked"' : '').'> '.$role['name'].'<br />';
						
					}
				}
			break;
		}
		echo '</td>';
	}
}

new DH_Popup_Settings();