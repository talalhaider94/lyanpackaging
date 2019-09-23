<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Meta_Box {
	
	protected static $field_prefix = '_dh_popup_';
	
	public function __construct() {
		add_action ( 'add_meta_boxes', array (&$this, 'add_meta_boxes' ), 30 );
		add_action ( 'save_post', array (&$this,'save_meta_boxes' ), 1, 2 );
			
		add_action( 'admin_print_scripts-post.php', array( &$this, 'enqueue_scripts' ) );
		add_action( 'admin_print_scripts-post-new.php', array( &$this, 'enqueue_scripts' ) );
	}
	
	public function get_meta_box_tabs(){
		return apply_filters('dh_popup_meta_box_tabs', array(
			'general'=>array(
				'label'=>__("General settings",'dh_popup')
			),
			'mail'=>array(
				'label'=>__("Mail settings",'dh_popup')
			),
		));
	}
	
	private function _get_mailing_list(){
		$mailing_list_type = dh_popup_get_option('mailing_list');
		$mailing_list_options = array();
		if(!empty($mailing_list_type)){
			switch ($mailing_list_type){
				case 'mailchimp';
				$mailing_list_options = dh_popup_get_mailchimp_list(dh_popup_get_option('mailchimp_api',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'constantcontact';
				$mailing_list_options = dh_popup_get_ctct_list(dh_popup_get_option('ctct_username',''), dh_popup_get_option('ctct_password',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'aweber';
				$mailing_list_options = dh_popup_get_aweber_list(dh_popup_get_option('aweber_account',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'convertkit';
				$mailing_list_options = dh_popup_get_convertkit_form(dh_popup_get_option('convertkit_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'getresponse';
				$mailing_list_options = dh_popup_get_getresponse_list(dh_popup_get_option('getresponse_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'campaignmonitor';
				$mailing_list_options = dh_popup_get_campaignmonitor_list(dh_popup_get_option('campaignmonitor_client_id',''), dh_popup_get_option('campaignmonitor_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'activecampaign';
				$mailing_list_options = dh_popup_get_activecampaign_list(dh_popup_get_option('activecampaign_api_url',''), dh_popup_get_option('activecampaign_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'madmimi';
				$mailing_list_options = dh_popup_get_madmimi_list(dh_popup_get_option('madmimi_username',''), dh_popup_get_option('madmimi_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'benchmarkemail';
				$mailing_list_options = dh_popup_get_benchmarkemail_list(dh_popup_get_option('benchmarkemail_api_key',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
				case 'streamsend';
				$mailing_list_options = dh_popup_get_streamsend_lists(dh_popup_get_option('streamsend_login_id',''),dh_popup_get_option('streamsend_api_key',''),dh_popup_get_option('streamsend_audience',''),array(''=>__('Nothing Found&hellip;','dh_popup')));
				break;
			}
		}
		return $mailing_list_options;
	}
	
	public function get_meta_box_fields($tab_id=false){
		$general_display_in = array();
		$general_event = array();
		$mailing_list = $this->_get_mailing_list();
		if(!dh_popup_use_targeting()){
			$general_event = array(
				array(
					'label' =>  __('Open popup for event ?', 'dh_popup'),
					'name' => 'open_event',
					'options'=>dh_popup_get_events(true),
					'type' => 'select',
					'value'=>'',
					'description'=>esc_html__('With event click Element, please set attribute data-popup-open="{popup_ID}" for a element, like a button, or href="#popup_open_{popup_ID}" to target a specific popup to display.Example: <button type="button" data-popup-open="{popup_ID}">Show Popup</button>','dh_popup')
				),
				array(
					'label' =>  __('Open when user scroll % of page', 'dh_popup'),
					'name' => 'scroll_offset',
					'type' => 'text',
					'value'=>10,
					'description'=>__('Enter x % when customer scroll on page','dh_popup')
				),
				array(
					'label' =>  __('Seconds inactivity', 'dh_popup'),
					'name' => 'inactivity_seconds',
					'type' => 'text',
					'value'=>0,
					'description'=>__('Enter x seconds of inactivity','dh_popup')
				)
			);
			$general_display_in = array(
				array(
					'label' =>  __('Display in', 'dh_popup'),
					'name' => 'display_in',
					'value'=>'all',
					'options'=>dh_popup_get_display_in(),
					'type' => 'display_in',
					'description'=>__('Select display in','dh_popup')
				),
			);
		}
		$fields = array(
			'general'=>array_merge(array(
				array(
					'label' =>  __('General Settings', 'dh_popup'),
					'type' => 'heading',
					'value'=>'1',
				),
				array(
					'label' =>  __('Disable Popup on Mobile drivers', 'dh_popup'),
					'name' => 'hide_on_mobile',
					'type' => 'checkbox',
				),
				array(
					'label' =>  __('Disable responsive popup ?', 'dh_popup'),
					'name' => 'disable_responsive',
					'type' => 'checkbox',
				),
				array(
					'label' =>  __('Popup position', 'dh_popup'),
					'name' => 'position',
					'value'=>'center',
					'options'=>array(
						'left-top'=>__('Left Top','dh_popup'),
						'center-top'=>__('Center Top','dh_popup'),
						'right-top'=>__('Right Top','dh_popup'),
						'left-center'=>__('Left Center','dh_popup'),
						'center'=>__('Center Center','dh_popup'),
						'right-center'=>__('Right Center','dh_popup'),
						'left-bottom'=>__('Left Bottom','dh_popup'),
						'center-bottom'=>__('Center Bottom','dh_popup'),
						'right-bottom'=>__('Right Bottom','dh_popup'),
					),
					'type' => 'position',
					'description'=>__('Select popup position.','dh_popup')
				),
				array(
					'label' =>  __('Popup Width', 'dh_popup'),
					'name' => 'width',
					'type' => 'text',
					'value'=>'500',
					'description'=>__('Deafult 500px','dh_popup')
				),
				array(
					'label' =>  __('Popup Height', 'dh_popup'),
					'name' => 'height',
					'type' => 'text',
					'value'=>'',
					'description'=>__('Deafult null is auto height','dh_popup')
				),
				array(
					'label' =>  __('Popup content background', 'dh_popup'),
					'name' => 'background',
					'type' => 'background',
					'description'=>__('You can read more CSS background properties in <a href="https://www.w3schools.com/css/css_background.asp">w3c specification</a>','dh_popup')
				),
				array(
					'type' => 'hr',
				),
				array(
					'label' =>  __('Open Settings', 'dh_popup'),
					'type' => 'heading',
					'value'=>'1',
				)),$general_event,
// 				array(
// 					'label' =>  __('Open popup for event ?', 'dh_popup'),
// 					'name' => 'open_event',
// 					'options'=>dh_popup_get_events(),
// 					'type' => 'select',
// 				),
// 				array(
// 					'label' =>  __('Open when user scroll % of page', 'dh_popup'),
// 					'name' => 'scroll_offset',
// 					'type' => 'text',
// 					'value'=>10,
// 					'description'=>__('Enter x % when customer scroll on page','dh_popup')
// 				),
				array(
					array(
						'label' =>  __('Display mode', 'dh_popup'),
						'name' => 'display_mode',
						'value'=>'every-time',
						'options'=>dh_popup_get_display_mode(),
						'type' => 'display_mode',
						'description'=>__('Select display mode','dh_popup')
					)
				),$general_display_in,
// 				array(
// 					'label' =>  __('Display in', 'dh_popup'),
// 					'name' => 'display_in',
// 					'value'=>'all',
// 					'options'=>dh_popup_get_display_mode(),
// 					'type' => 'display_in',
// 					'description'=>__('Select display in','dh_popup')
// 				),
// 				array(
// 					'label' =>  __('Open Date From', 'dh_popup'),
// 					'name' => 'open_date_from',
// 					'type' => 'datepicker',
// 					'description'=>__('Leave empty to enable popup all the time.','dh_popup')
// 				),
// 				array(
// 					'label' =>  __('Open Date To', 'dh_popup'),
// 					'name' => 'open_date_to',
// 					'type' => 'datepicker',
// 					'description'=>__('Leave empty to enable popup all the time.','dh_popup')
// 				),
				array(
					array(
						'label' =>  __('Open delay X seconds', 'dh_popup'),
						'name' => 'open_delay',
						'type' => 'text',
						'value'=>0,
						'description'=>__('Enter x seconds when customer on page','dh_popup')
					),
					array(
						'label' =>  __('Clear Popup Cookie', 'dhvc_popup'),
						'name' => 'clear_cookie',
						'type' => 'button',
						'description'=>__('Clear popup cookie to test the popup on your live website.','dhvc_popup')
					),
					array(
						'type' => 'hr',
					),
					array(
						'label' =>  __('Close Settings', 'dh_popup'),
						'type' => 'heading',
						'value'=>'1',
					),
					array(
						'label' =>  __('Close Popup type ?', 'dh_popup'),
						'name' => 'close_type',
						'options'=>array(
							'default'=>__('Default','dh_popup'),
							'success'=>__('Form submit success','dh_popup'),
						),
						'value'=>'default',
						'type' => 'select',
						'description'=>__('Select close popup type, default close popup by close button or close popup after subscribe newsletter.','dh_popup')
					),
					array(
						'label' =>  __('Auto Close Popup', 'dh_popup'),
						'name' => 'close_delay',
						'type' => 'text',
						'value'=>0,
						'description'=>__('Close popup in x seconds','dh_popup')
					),
					array(
						'label' =>  __('Hide Close Popup button', 'dh_popup'),
						'name' => 'hide_close_button',
						'type' => 'checkbox',
					),
					array(
						'label' =>  __('Close Popup button color', 'dh_popup'),
						'name' => 'close_button_color',
						'type' => 'color',
					),
					array(
						'type' => 'hr',
					),
					array(
						'label' =>  __('Overlay Settings', 'dh_popup'),
						'type' => 'heading',
						'value'=>'1',
					),
					array(
						'label' =>  __('Type', 'dh_popup'),
						'name' => 'overlay',
						'options'=>array(
							'default'=>__('Default (Opacity)','dh_popup'),
							'disable'=>__('Disable','dh_popup'),
							'image'=>__('Image background','dh_popup')
						),
						'type' => 'select',
					),
					array(
						'label' =>  __('Overlay Image Background', 'dh_popup'),
						'type' => 'image',
						'name'=>'overlay_image_background',
					),
					array(
						'label' =>  __('Full screen popup', 'dh_popup'),
						'name' => 'full_screen',
						'type' => 'checkbox',
					),
					array(
						'label' =>  __('Disable overlay click', 'dh_popup'),
						'name' => 'disable_overlay_click',
						'type' => 'checkbox',
					),
					array(
						'type' => 'hr',
					),
					array(
						'label' =>  __('Redirect Settings', 'dh_popup'),
						'type' => 'heading',
						'value'=>'1',
					),
					array(
						'label' =>  __('Redirect URL', 'dh_popup'),
						'name' => 'redirect_url',
						'type' => 'text',
						'description'=>__('Enter the redirect URL start with http://. After successful form submission user is redirected to this URL. Leave blank to stay on the same page.','dh_popup')
					),
					array(
						'type' => 'hr',
					),
					array(
						'label' =>  __('Mailing Newsletter Settings', 'dh_popup'),
						'type' => 'heading',
						'value'=>'1',
					),
					array(
						'label' =>  __('Use Mailing Newsletter', 'dh_popup'),
						'type' => 'select',
						'name'=>'use_mailing',
						'value'=>'',
						'options'=>array(
							'no'=>__("No",'dh_popup'),
							'yes'=>__("Yes",'dh_popup')
						),
						'description'=>sprintf(__('To use this feature, you need add <a href="%s" target="_blank">Setting Mailing</a>','dh_popup'),admin_url('admin.php?page=dh_popup_setting#dh_popup_accordion_mailing'))
					),
					array(
						'label' =>  __('Mailing List', 'dh_popup'),
						'name' => 'mailing_list',
						'options'=>$mailing_list,
						'type' => 'select',
						'description'=>__('No select to use global settings','dh_popup')
					),
				)
			),
			'mail'=>array(
				array(
					'label' =>  __('Admin Notification Settings', 'dh_popup'),
					'type' => 'heading',
					'value'=>'1',
				),
				array(
					'label' =>  __('Enable Admin Notification', 'dh_popup'),
					'name' => 'notice_admin',
					'type' => 'checkbox',
				),
				array(
					'label' =>  __('Admin email', 'dh_popup'),
					'name' => 'notice_admin_recipient',
					'type' => 'text',
					'value'=>get_option('admin_email')
				),
				array(
					'label' =>  __('Sender', 'dh_popup'),
					'name' => 'notice_admin_sender',
					'type' => 'text',
					'value'=>get_option('blogname').' <'. get_option('admin_email').'>'
				),
				array(
					'label' =>  __('Subject', 'dh_popup'),
					'name' => 'notice_admin_subject',
					'type' => 'text',
					'value'=>'Subscription form submitted',
					'description'=>__('You can use shortcodes in setting: ','dh_popup').implode(' ', array_keys(dh_popup_variables()))
				),
				array(
					'label' =>  __('Additional Headers', 'dh_popup'),
					'name' => 'notice_admin_additional_headers',
					'type' => 'textarea',
					'value'=>'Reply-To: [email]'
				),
				array(
					'label' =>  __('Message Body', 'dh_popup'),
					'name' => 'notice_admin_body',
					'type' => 'textarea',
					'value'=>'
Subscription form submitted.

E-mail: [email]
IP: [remote_ip]
Popup name: [popup_name]
On page: [url]

Thanks you!',
					'description'=>__('You can use shortcodes in setting: ','dh_popup').implode(' ', array_keys(dh_popup_variables())).'. '.__('To use form fields in email message body, please enter field name as variables [field_name] in message body.'),
				),
				array(
					'label' =>  __('Use HTML content type', 'dh_popup'),
					'name' => 'notice_admin_use_html',
					'type' => 'checkbox',
				),
				array(
					'label' =>  __('Confirm Settings', 'dh_popup'),
					'type' => 'heading',
					'value'=>'1',
				),
				array(
					'label' =>  __('Enable confirm email', 'dh_popup'),
					'name' => 'confirm_email',
					'type' => 'checkbox',
				),
				array(
					'label' =>  __('Sender', 'dh_popup'),
					'name' => 'confirm_email_sender',
					'type' => 'text',
					'value'=>get_option('blogname').' <'. get_option('admin_email').'>'
				),
				array(
					'label' =>  __('Subject', 'dh_popup'),
					'name' => 'confirm_email_subject',
					'type' => 'text',
					'value'=>'Thank you for subscription',
					'description'=>__('You can use shortcodes in setting: ','dh_popup').implode(' ', array_keys(dh_popup_variables()))
				),
				array(
					'label' =>  __('Message Body', 'dh_popup'),
					'name' => 'confirm_email_body',
					'type' => 'textarea',
					'value'=>'
Dear [email],

Thank you for subscription.

Thanks,
Wordpress',
					'description'=>__('You can use shortcodes in setting: ','dh_popup').implode(' ', array_keys(dh_popup_variables())).'. '.__('To use form fields in email message body, please enter field name as variables [field_name] in message body.'),
				),
				array(
					'label' =>  __('Use HTML content type', 'dh_popup'),
					'name' => 'confirm_email_use_html',
					'type' => 'checkbox',
				),
			)
		);
		$fields = apply_filters('dh_popup_meta_box_fields',$fields);
		if(false !== $tab_id && $fields[$tab_id])
			return $fields[$tab_id];
		return $fields;
	}
	
	public function add_meta_boxes(){
		
		add_meta_box ('dh_popup_setting',__('Popup Settings','dh_popup'),array($this,'render_with_tabs'), 'dh_popup', 'normal', 'default', $this->get_meta_box_fields() );
		$popup_options = array();
		$popups = get_posts(array(
			'post_type'=>'dh_popup',
			'posts_per_page'=> -1,
			'post_status'=>'publish',
		));
		foreach ($popups as $popup){
			$popup_options[$popup->ID] = $popup->post_title;
		}
		add_meta_box ('dh_popup_campaign_setting',__('Popup','dh_popup'),array($this,'render'), DH_Popup_Campaign::$post_type, 'normal', 'default', array(
			array(
				'type'=>'popup_lists',
				'label'=>__("Select Popup",'dh_popup'),
				'name'=>'popup_id',
				'options'=>$popup_options
			)
		) );
	
	}
	
	public function save_meta_boxes($post_id, $post){
		// $post_id and $post are required
		if (empty ( $post_id ) || empty ( $post )) {
			return;
		}
		// Dont' save meta boxes for revisions or autosaves
		if (defined ( 'DOING_AUTOSAVE' ) || is_int ( wp_is_post_revision ( $post ) ) || is_int ( wp_is_post_autosave ( $post ) )) {
			return;
		}
		// Check the nonce
		if (empty ( $_POST ['dh_popup_meta_box_nonce'] ) || ! wp_verify_nonce ( $_POST ['dh_popup_meta_box_nonce'], 'dh_popup_meta_box_nonce' )) {
			return;
		}
			
		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if (empty ( $_POST ['post_ID'] ) || $_POST ['post_ID'] != $post_id) {
			return;
		}
			
		// Check user has permission to edit
		if (! current_user_can ( 'edit_post', $post_id )) {
			return;
		}
	
		if(isset( $_POST['dh_popup'] )){
			// Process //unique_id
// 			$unique_id = dh_popup_get_post_meta('unique_id',$post_id,null);
			
// 			if('dh_popup'===get_post_type($post) && is_null($unique_id))
// 				update_post_meta($post_id, self::$field_prefix.'unique_id', uniqid(dh_popup_random()));
				
			foreach( (array)$_POST['dh_popup'] as $key=>$value ){
				$value = wp_unslash($value);
				if(is_array($value)){
					$value = array_filter( array_map( 'sanitize_text_field', (array) $value ) );
					update_post_meta( $post_id, self::$field_prefix.$key, $value );
				}else{
					update_post_meta( $post_id, self::$field_prefix.$key, $value );
				}
			}
		}
	}
	
	
	public function enqueue_scripts(){
		$screen         = get_current_screen();
		if(!in_array($screen->post_type, array('dh_popup','dh_popup_campaign')))
			return;		
		wp_enqueue_style('dh_popup_meta_box',DH_POPUP_URL.'/assets/css/meta-box.css',array('dh_popup_jqueryui'));
		wp_register_script('dh_popup_meta_box',DH_POPUP_URL.'/assets/js/meta-box.js',array('jquery','dh_popup_cookie','jquery-ui-datepicker'),null,true);
		wp_localize_script('dh_popup_meta_box', 'dh_popup_meta_box', array(
			'cookie_prefix'=>dh_popup_get_cookie_prefix(),
		));
		wp_enqueue_script('dh_popup_meta_box');
	}
	
	public function render($post,$meta_box){
		$args = $meta_box ['args'];
		if (! is_array ( $args ))
			return false;
		if(!defined('DH_POPUP_META_BOX_NONCE')):
			define('DH_POPUP_META_BOX_NONCE', 1);
			wp_nonce_field ('dh_popup_meta_box_nonce', 'dh_popup_meta_box_nonce',false);
		endif;
		//TODO:
		echo '<div class="dh_popup-metaboxes" data-postid="'.$post->ID.'">';
		if (isset ( $tab ['description'] ) && $tab ['description'] != '') {
			echo '<p>' . $tab ['description'] . '</p>';
		}
		foreach ($args as $field){
			if(!isset($field['type']) )
				continue;
			$this->render_fields($post,$field);
		}
		echo '</div>';
	}
	
	public function render_with_tabs($post, $meta_box){
		$args = $meta_box ['args'];
		if (! is_array ( $args ))
			return false;
		if(!defined('DH_POPUP_META_BOX_NONCE')):
			define('DH_POPUP_META_BOX_NONCE', 1);
			wp_nonce_field ('dh_popup_meta_box_nonce', 'dh_popup_meta_box_nonce',false);
		endif;
		?>
		<div class="dh_popup-metabox-tab">
			<h2 class="nav-tab-wrapper dh_popup-nav-tab-wrapper" style="padding: 0px;">
				<?php $i = 1;?>
				<?php foreach ((array)$this->get_meta_box_tabs() as $key=>$tab):?>
					<a class="nav-tab<?php echo ($i==1) ? ' nav-tab-active':''?>" href="<?php echo '#dh_popup_meta_box_'.esc_attr($key)?>">
						<?php echo esc_html($tab['label'])?>
					</a>
				<?php $i++;?>
				<?php endforeach;?>
			</h2>
			<div class="nav-tab-content dh_popup-tab-content">
			<?php
			//TODO:
			echo '<div class="dh_popup-metaboxes" data-postid="'.$post->ID.'">';
			if (isset ( $tab ['description'] ) && $tab ['description'] != '') {
				echo '<p>' . $tab ['description'] . '</p>';
			}
			$i = 0;
			foreach ( (array) $args as $group=>$fields ) {
				$i++;
				echo '<div id="dh_popup_meta_box_'.$group.'" class="dh_popup-tab-panel" '.($i==1 ? '':' style="display:none"' ).'>';
				foreach ($fields as $field){
					if(!isset($field['type']) )
						continue;
					$this->render_fields($post,$field);
				}
				echo '</div>';
			}
			echo '</div>';
			?>
			</div>
		</div>
	<?php
	}
	
	public function render_fields($post, $field) {
		
		$field['name'] = isset($field['name'] ) ? sanitize_title( $field['name'] ) : '';

		$value = get_post_meta( $post->ID,self::$field_prefix.$field['name'], true );

		$field['value'] = isset( $field['value'] ) ? $field['value'] : '';
		if($value !== '' && $value !== null && $value !== array() && $value !== false)
			$field['value'] = $value;


		$field['id'] 			= isset( $field['id'] ) ? $field['id'] : $field['name'];
		$field['description'] 	= isset($field['description']) ? $field['description'] : '';
		$field['label'] 		= isset( $field['label'] ) ? $field['label'] : '';
		$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : $field['label'];
		$field['fname'] = $field['name'];
		$field['name'] = 'dh_popup['.$field['name'].']';
		if( isset($field['callback']) && !empty($field['callback']) ) {
			call_user_func($field['callback'], $post,$field);
		} else {
			switch ($field['type']){
				case 'heading':
					if(isset($field['heading']))
						echo '<h3>'.$field['heading'].'</h3>';
					else 
						echo '<h3>'.$field['label'].'</h3>';
				break;
				case 'hr':
					echo '<div style="margin-top:20px;margin-bottom:20px;">';
					echo '<hr>';
					echo '</div>';
				break;
				case 'display_mode':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					?>
					<?php foreach (dh_popup_get_display_mode() as $key=>$label):?>
					<input name="<?php echo $field['name']?>" value="<?php echo esc_attr($key)?>" <?php checked($field['value'], $key)?> type="radio">
					<?php echo $label?>
					<?php if('once-period'===$key){?>
					<input name="<?php echo 'dh_popup[once_period_day]' ?>" value="<?php echo dh_popup_get_post_meta('once_period_day',$post->ID,5)?>" type="text">
					<?php _e('days')?>
					<?php }?>
					<br/>
					<?php endforeach;?>
					<?php 
					echo '</div>';
				break;
				case 'background':
					wp_enqueue_style( 'wp-color-picker');
					wp_enqueue_script( 'wp-color-picker');
					if(function_exists( 'wp_enqueue_media' )){
						wp_enqueue_media();
					}else{
						wp_enqueue_style('thickbox');
						wp_enqueue_script('media-upload');
						wp_enqueue_script('thickbox');
					}
					$value_default = array(
						'background-color'      => '',
						'background-repeat'     => '',
						'background-attachment' => '',
						'background-position'   => '',
						'background-image'      => '',
						'background-clip'       => '',
						'background-origin'     => '',
						'background-size'       => '',
						'media' => array(),
					);
					$values = wp_parse_args( $field['value'], $value_default );
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field">';
					echo '<label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					
					echo '<div class="dh_popup-background-field-wrap">';
					//background color
					echo '<div  class="dh_popup-background-color">';
					echo '<span>'.__('Background Color').'</span><br/>';
					echo '<input type="text" name="' .  $field['name'] . '[background-color]" id="' .  $field['id'] . '_background_color" value="' . esc_attr( $values['background-color'] ) . '" /> ';
					echo '<script type="text/javascript">
					jQuery(document).ready(function($){
					    $("#'. ( $field['id'] ).'_background_color").wpColorPicker();
					});
					 </script>
					';
					echo '</div>';
					//background repeat
					echo '<div  class="dh_popup-background-repeat">';
					echo '<span>'.__('Background Repeat').'</span><br/>';
					$bg_repeat_options = array('no-repeat'=>'No Repeat','repeat'=>'Repeat All','repea-x'=>'Repeat Horizontally','repeat-y'=>'Repeat Vertically','inherit'=>'Inherit');
					echo '<select id="' . $field['id'] . '_background_repeat" name="' . $field['name'] . '[background-repeat]">';
					echo '<option value=""></option>';
					foreach ( $bg_repeat_options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $values['background-repeat'] , esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					echo '</select> ';
					echo '</div>';
					//background size
					echo '<div  class="dh_popup-background-size">';
					echo '<span>'.__('Background Size').'</span><br/>';
					$bg_size_options = array('inherit'=>'Inherit','cover'=>'Cover','contain'=>'Contain');
					echo '<select id="' . $field['id'] . '_background_size" name="' . $field['name'] . '[background-size]">';
					echo '<option value=""></option>';
					foreach ( $bg_size_options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $values['background-size'] , esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					echo '</select> ';
					echo '</div>';
					//background attachment
					echo '<div  class="dh_popup-background-attachment">';
					echo '<span>'.__('Background Attachment').'</span><br/>';
					$bg_attachment_options = array('fixed'=>'Fixed','scroll'=>'Scroll','inherit'=>'Inherit');
					echo '<select id="' . $field['id'] . '_background_attachment" name="' . $field['name'] . '[background-attachment]">';
					echo '<option value=""></option>';
					foreach ( $bg_attachment_options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $values['background-attachment'] , esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					echo '</select> ';
					echo '</div>';
					//background position
					echo '<div  class="dh_popup-background-position">';
					$bg_position_options = array(
						'left top' => 'Left Top',
						'left center' => 'Left center',
						'left bottom' => 'Left Bottom',
						'center top' => 'Center Top',
						'center center' => 'Center Center',
						'center bottom' => 'Center Bottom',
						'right top' => 'Right Top',
						'right center' => 'Right center',
						'right bottom' => 'Right Bottom'
					);
					echo '<span>'.__('Background Position').'</span><br/>';
					echo '<select id="' . $field['id'] . '_background_position" name="' . $field['name'] . '[background-position]">';
					echo '<option value=""></option>';
					foreach ( $bg_position_options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $values['background-position'] , esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					echo '</select> ';
					echo '</div>';
					//background image
					echo '<div  class="dh_popup-background-image">';
					echo '<span>'.__('Background Image').'</span><br/>';
					$image_id = $values['background-image'];
					$image = wp_get_attachment_image( $image_id,array(120,120));
					$output = !empty( $image_id ) ? $image : '';
					$btn_text = !empty( $image_id ) ? __( 'Change Image', 'dh_popup' ) : __( 'Select Image', 'dh_popup' );
					echo '<div class="dh_popup-meta-image-thumb">' . $output . '</div>';
					echo '<input type="hidden" name="' . $field['name'] . '[background-image]" id="' . $field['id'] . '" value="' . $values['background-image'] . '" />';
					echo '<input type="button" class="button button-primary" name="' . $field['id'] . '_button_upload" id="' . $field['id'] . '_upload" value="' . $btn_text . '" /> ';
					echo '<input type="button" class="button" name="' . $field['id'] . '_button_clear" id="' . $field['id'] . '_clear" value="' . __( 'Clear Image', 'dh_popup' ) . '" />';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					?>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							<?php if ( empty ( $field['value'] ) ) : ?> $('#<?php echo esc_attr($field['id']) ?>_clear').css('display', 'none'); <?php endif; ?>
							$('#<?php echo esc_attr($field['id']) ?>_upload').on('click', function(event) {
								event.preventDefault();
								var $this = $(this);
		
								// if media frame exists, reopen
								if(dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame) {
									dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.open();
					                return;
					            }
		
								// create new media frame
								// I decided to create new frame every time to control the selected images
								var dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame = wp.media.frames.wp_media_frame = wp.media({
									title: "<?php echo esc_js(__( 'Select or Upload your Image', 'dh_popup' )); ?>",
									button: {
										text: "<?php echo esc_js(__( 'Select', 'dh_popup' )); ?>"
									},
									library: { type: 'image' },
									multiple: false
								});
		
								// when open media frame, add the selected image
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.on('open',function() {
									var selected_id = $this.closest('.dh_popup-meta-box-field').find('#<?php echo esc_attr($field['id']); ?>').val();
									if (!selected_id)
										return;
									var selection = dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.state().get('selection');
									var attachment = wp.media.attachment(selected_id);
									attachment.fetch();
									selection.add( attachment ? [ attachment ] : [] );
								});
		
								// when image selected, run callback
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.on('select', function(){
									var attachment = dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.state().get('selection').first().toJSON();
									$this.closest('.dh_popup-meta-box-field').find('input#<?php echo esc_attr($field['id']); ?>').val(attachment.id);
									var thumbnail = $this.closest('.dh_popup-meta-box-field').find('.dh_popup-meta-image-thumb');
									thumbnail.html('');
									thumbnail.append('<img src="' + attachment.url + '" alt="" />');
		
									$this.attr('value', '<?php echo esc_js(__( 'Change Image', 'dh_popup' )); ?>');
									$('#<?php echo esc_attr($field['id']); ?>_clear').css('display', 'inline-block');
								});
		
								// open media frame
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.open();
							});
		
							$('#<?php echo esc_attr($field['id']); ?>_clear').on('click', function(event) {
								var $this = $(this);
								$this.hide();
								$('#<?php echo esc_attr($field['id']); ?>_upload').attr('value', '<?php echo esc_js(__( 'Select Image', 'dh_popup' )); ?>');
								$this.closest('.dh_popup-meta-box-field').find('input#<?php echo esc_attr($field['id']); ?>').val('');
								$this.closest('.dh_popup-meta-box-field').find('.dh_popup-meta-image-thumb').html('');
							});
						});
					</script>	
					<?php
					echo '</div>';
					echo '</div>';
					echo '</div>';
				break;
				case 'display_in':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					?>
					<?php foreach (dh_popup_get_display_in() as $key=>$label):?>
					<input name="<?php echo $field['name']?>[]" value="<?php echo esc_attr($key)?>" <?php echo (in_array($key,(array) $field['value']) ? 'checked="checked"':'')?> type="checkbox">
					<?php echo $label;?>
					<br/>
					<?php endforeach;?>
					<?php 
					echo '</div>';
				break;
				case 'button':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					echo '<input id="'.$field['id'].'" class="button" name="'.$field['name'].'" value="'.$field['label'].'" style="display: inline-block;" type="button">';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
				break;
				case 'datepicker':
				case 'text':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					$input_attrs = '';
					if($field['type']=='datepicker'){
						$field['placeholder'] = 'YYYY-MM-DD';
						$input_attrs = 'readonly="true" data-dh_popup_meta_box_datepicker="true" maxlength="10" pattern="' . esc_attr( apply_filters( 'dh_popup_meta_box_datepicker_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ) . '"';
					}
					echo '<input type="text" '.$input_attrs.' name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" style="width: 99%;" /> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
				break;
				case 'color':
					wp_enqueue_style( 'wp-color-picker');
					wp_enqueue_script( 'wp-color-picker');
						
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label><input type="text" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" /> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '<script type="text/javascript">
					jQuery(document).ready(function($){
					    $("#'. esc_attr( $field['id'] ).'").wpColorPicker();
					});
				 </script>
				';
					echo '</div>';
					break;
				case 'textarea':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label><textarea name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="5" cols="20" style="width: 99%;">' . esc_textarea( $field['value'] ) . '</textarea> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
					break;
				case 'checkbox':
					$field['cbvalue']       = isset( $field['cbvalue'] ) ? $field['cbvalue'] : '1';
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label><input type="checkbox" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="0"  checked="checked" style="display:none" /><input class="checkbox" type="checkbox" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . ' /> ';
					if ( ! empty( $field['description'] ) ) echo '<span class="description">' . $field['description'] . '</span>';

					echo '</div>';
					break;
				case 'categories':
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					wp_dropdown_categories(array(
						'name'=>esc_attr( $field['name'] ),
						'id'=>esc_attr( $field['id'] ),
						'hierarchical'=>1,
						'selected'=>$field['value']
					));
					echo '</div>';
					break;
				case 'widgetised_sidebars':
					$sidebars = $GLOBALS['wp_registered_sidebars'];
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '">';
					echo '<option value="">' . __('Select a sidebar...','dh_popup') . '</option>';
					foreach ( $sidebars as $sidebar ) {
						$selected = '';
						if ( $sidebar["id"] == $field['value'] ) $selected = ' selected="selected"';
						$sidebar_name = $sidebar["name"];
						echo '<option value="' . $sidebar["id"] . '"' . $selected . '>' . $sidebar_name . '</option>';
					}
					echo '</select> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
					break;
					break;
				case 'select':
					$field['options']       = isset( $field['options'] ) ? $field['options'] : array();
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label><select id="' . esc_attr( $field['id'] ) . '" name="' . esc_attr( $field['name'] ) . '">';
					foreach ( $field['options'] as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '>' . esc_html( $value ) . '</option>';
					}
					echo '</select> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
					break;
				case 'popup_lists':
					$field['options']       = isset( $field['options'] ) ? $field['options'] : array();
					if(empty($field['options']))
						return '';
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><label>' . esc_html( $field['label'] ) . '</label>';
					echo '<input type="hidden" name="'.$field['name'].'[]" value="0" checked  />' ;
					foreach ( $field['options'] as $key => $value ) {
						echo '<span style="display: block;">';
						echo '<input id="dh_popup-checkbox-list-'.$key.'" type="checkbox" name="'.$field['name'].'[]" value="' . esc_attr( $key ) . '" ' . (in_array($key,(array) $field['value']) ? 'checked' : '') . ' />' ;
						echo '<strong>'.esc_html( $value ).'</strong>';
						echo '</span>';
					}
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
					break;
				case 'position':
						$field['options']  = isset( $field['options'] ) ? $field['options'] : array();
						echo '<fieldset class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field"><legend>' . esc_html( $field['label'] ) . '</legend><ul>';
						foreach ( $field['options'] as $key => $value ) {
							$checked = esc_attr( $field['value'] )==esc_attr( $key ) ? true : false;
							echo '<li><label>';
							echo '<input
				        		name="' . esc_attr( $field['name'] ) . '"
				        		value="' . esc_attr( $key ) . '"
				        		type="radio"
								class="radio"
				        		' . ($checked ? 'checked="checked"':'' ). '
				        		/>';
							echo '<span class="dh_popup-position-field-icon '.esc_attr($key).($checked ? ' checked':'').'"></span>';								
				    		echo '</label></li>';
						}
						echo '</ul>';
						if ( ! empty( $field['description'] ) ) {
							echo '<span class="description">' . $field['description'] . '</span>';
						}
						echo '</fieldset>';
				break;
				case 'radio':
					$field['options']       = isset( $field['options'] ) ? $field['options'] : array();
					echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field"><legend>' . esc_html( $field['label'] ) . '</legend><ul>';
					foreach ( $field['options'] as $key => $value ) {
						echo '<li><label><input
				        		name="' . esc_attr( $field['name'] ) . '"
				        		value="' . esc_attr( $key ) . '"
				        		type="radio"
								class="radio"
				        		' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '
				        		/> ' . esc_html( $value ) . '</label>
				    	</li>';
					}
					echo '</ul>';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</fieldset>';
					break;
				case 'gallery':
					if(function_exists( 'wp_enqueue_media' )){
						wp_enqueue_media();
					}else{
						wp_enqueue_style('thickbox');
						wp_enqueue_script('media-upload');
						wp_enqueue_script('thickbox');
					}
						
					if(!defined('_DH_POPUP_META_GALLERY_JS')):
						define('_DH_POPUP_META_GALLERY_JS', 1);
					?>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							$('.dh_popup-meta-gallery-select').on('click',function(e){
								e.stopPropagation();
								e.preventDefault();
								
								var $this = $(this),
									dh_popup_meta_gallery_list = $this.closest('.dh_popup-meta-box-field').find('.dh_popup-meta-gallery-list'),
									dh_popup_meta_gallery_frame,
									dh_popup_meta_gallery_ids = $this.closest('.dh_popup-meta-box-field').find('#dh_popup_meta_gallery_ids'),
									_ids = dh_popup_meta_gallery_ids.val();
	
								if(dh_popup_meta_gallery_frame){
									dh_popup_meta_gallery_frame.open();
									return false;
								}
								
								dh_popup_meta_gallery_frame = wp.media({
									title: '<?php echo esc_js(__('Add Images to Gallery','dh_popup'))?>',
									button: {
										text: '<?php echo esc_js(__('Add to Gallery','dh_popup'))?>',
									},
									library: { type: 'image' },
									multiple: true
								});
	
								dh_popup_meta_gallery_frame.on('select',function(){
									var selection = dh_popup_meta_gallery_frame.state().get('selection');
									selection.map( function( attachment ) {
										attachment = attachment.toJSON();
										if ( attachment.id ) {
											_ids = _ids ? _ids + "," + attachment.id : attachment.id;
											dh_popup_meta_gallery_list.append('\
												<li data-id="' + attachment.id +'">\
													<div class="thumbnail">\
														<div class="centered">\
															<img src="' + attachment.url + '" />\
														</div>\
														<a href="#" title="<?php echo esc_js(__('Delete','dh_popup'))?>">x</a></li>\
													</div>\
												</li>'
											);
										}
										dh_popup_meta_gallery_ids.val( dh_popup_trim(_ids,',') );
										dh_popup_meta_gallery_fn();
									});
								});
	
								dh_popup_meta_gallery_frame.open();
							});
							var dh_popup_meta_gallery_fn = function(){
								if($('.dh_popup-meta-gallery-list').length){
									$('.dh_popup-meta-gallery-list').each(function(){
										var $this = $(this);
										$this.sortable({
											items: 'li',
											cursor: 'move',
											forcePlaceholderSize: true,
											forceHelperSize: false,
											helper: 'clone',
											opacity: 0.65,
											placeholder: 'li-placeholder',
											start:function(event,ui){
												ui.item.css('background-color','#f6f6f6');
											},
											update: function(event, ui) {
												var _ids = '';
												$this.find('li').each(function() {
													var _id = $(this).data( 'id' );
													_ids = _ids + _id + ',';
												});
									
												$this.closest('.dh_popup-meta-box-field').find('#dh_popup_meta_gallery_ids').val( dh_popup_trim(_ids,',') );
											}
										});
	
										$this.find('a').on( 'click',function(e) {
											e.stopPropagation();
											e.preventDefault();
											$(this).closest('li').remove();
											var _ids = '';
											$this.find('li').each(function() {
												var _id = $(this).data( 'id' );
												_ids = _ids + _id + ',';
											});
	
											$this.closest('.dh_popup-meta-box-field').find('#dh_popup_meta_gallery_ids').val( dh_popup_trim(_ids,',') );
	
											return false;
										});
										
									});
								}
							}
							dh_popup_meta_gallery_fn();
						});
					</script>
					<?php
					endif;
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field">';
					echo '<label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					echo '<div class="dh_popup-meta-gallery-wrap"><ul class="dh_popup-meta-gallery-list">';
					if($field['value']){
						$value_arr = explode(',', $field['value']);
						if(!empty($value_arr) && is_array($value_arr)){
							foreach ($value_arr as $attachment_id ){
								if($attachment_id):
							?>
								<li data-id="<?php echo esc_attr( $attachment_id ) ?>">
									<div class="thumbnail">
										<div class="centered">
											<?php echo wp_get_attachment_image( $attachment_id, array(120,120) ); ?>						
										</div>
										<a title="<?php echo __('Delete','dh_popup') ?>" href="#">x</a>
									</div>						
								</li>
							<?php
								endif;
							}
						}
					}
					echo '</ul></div>';
					echo '<input type="hidden" name="' . $field['name'] . '" id="dh_popup_meta_gallery_ids" value="' . $field['value'] . '" />';
					echo '<input type="button" class="button button-primary dh_popup-meta-gallery-select" name="' . $field['id'] . '_button_upload" id="' . $field['id'] . '_upload" value="' . __('Add Gallery Images','dh_popup') . '" /> ';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
				break;
				case 'media':
					if(function_exists( 'wp_enqueue_media' )){
						wp_enqueue_media();
					}else{
						wp_enqueue_style('thickbox');
						wp_enqueue_script('media-upload');
						wp_enqueue_script('thickbox');
					}
					$btn_text = !empty(  $field['value'] ) ? __( 'Change Media', 'dh_popup' ) : __( 'Select Media', 'dh_popup' );
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field">';
					echo '<label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					echo '<input type="text" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" style="width: 99%;margin-bottom:5px" />';
					echo '<input type="button" class="button button-primary" name="' . $field['id'] . '_button_upload" id="' . $field['id'] . '_upload" value="' . $btn_text . '" /> ';
					echo '<input type="button" class="button" name="' . $field['id'] . '_button_clear" id="' . $field['id'] . '_clear" value="' . __( 'Clear', 'dh_popup' ) . '" />';				
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					echo '</div>';
					?>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							<?php if ( empty ( $field['value'] ) ) : ?> $('#<?php echo esc_attr($field['id']); ?>_clear').css('display', 'none'); <?php endif; ?>
							$('#<?php echo esc_attr($field['id']) ?>_upload').on('click', function(event) {
								event.preventDefault();
								var $this = $(this);
		
								// if media frame exists, reopen
								if(dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame) {
									dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame.open();
					                return;
					            }
		
								// create new media frame
								// I decided to create new frame every time to control the selected images
								var dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame = wp.media.frames.wp_media_frame = wp.media({
									title: "<?php echo esc_js(__( 'Select or Upload your Media', 'dh_popup' )); ?>",
									button: {
										text: "<?php echo esc_js(__( 'Select', 'dh_popup' )); ?>"
									},
									library: { type: 'video,audio' },
									multiple: false
								});
		
								// when image selected, run callback
								dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame.on('select', function(){
									var attachment = dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame.state().get('selection').first().toJSON();
									$this.closest('.dh_popup-meta-box-field').find('input#<?php echo esc_attr($field['id']); ?>').val(attachment.url);
									
									$this.attr('value', '<?php echo esc_js(__( 'Change Media', 'dh_popup' )); ?>');
									$('#<?php echo esc_attr($field['id']); ?>_clear').css('display', 'inline-block');
								});
		
								// open media frame
								dh_popup_<?php echo esc_attr($field['id']); ?>_media_frame.open();
							});
		
							$('#<?php echo esc_attr($field['id']) ?>_clear').on('click', function(event) {
								var $this = $(this);
								$this.hide();
								$('#<?php echo esc_attr($field['id']) ?>_upload').attr('value', '<?php echo esc_js(__( 'Select Media', 'dh_popup' )); ?>');
								$this.closest('.dh_popup-meta-box-field').find('#<?php echo esc_attr($field['id']); ?>').val('');
							});
						});
					</script>
					<?php
				break;
				
				case 'image':
					if(function_exists( 'wp_enqueue_media' )){
						wp_enqueue_media();
					}else{
						wp_enqueue_style('thickbox');
						wp_enqueue_script('media-upload');
						wp_enqueue_script('thickbox');
					}
					$image_id = $field['value'];
					$image = wp_get_attachment_image( $image_id,array(120,120));
					$output = !empty( $image_id ) ? $image : '';
					$btn_text = !empty( $image_id ) ? __( 'Change Image', 'dh_popup' ) : __( 'Select Image', 'dh_popup' );
					echo '<div  class="dh_popup-meta-box-field ' . esc_attr( $field['id'] ) . '_field">';
					echo '<label for="' . esc_attr( $field['id'] ) . '">' . esc_html( $field['label'] ) . '</label>';
					echo '<div class="dh_popup-meta-image-thumb">' . $output . '</div>';
					echo '<input type="hidden" name="' . $field['name'] . '" id="' . $field['id'] . '" value="' . $field['value'] . '" />';
					echo '<input type="button" class="button button-primary" name="' . $field['id'] . '_button_upload" id="' . $field['id'] . '_upload" value="' . $btn_text . '" /> ';
					echo '<input type="button" class="button" name="' . $field['id'] . '_button_clear" id="' . $field['id'] . '_clear" value="' . __( 'Clear Image', 'dh_popup' ) . '" />';
					if ( ! empty( $field['description'] ) ) {
						echo '<span class="description">' . $field['description'] . '</span>';
					}
					?>
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							<?php if ( empty ( $field['value'] ) ) : ?> $('#<?php echo esc_attr($field['id']) ?>_clear').css('display', 'none'); <?php endif; ?>
							$('#<?php echo esc_attr($field['id']) ?>_upload').on('click', function(event) {
								event.preventDefault();
								var $this = $(this);
		
								// if media frame exists, reopen
								if(dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame) {
									dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.open();
					                return;
					            }
		
								// create new media frame
								// I decided to create new frame every time to control the selected images
								var dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame = wp.media.frames.wp_media_frame = wp.media({
									title: "<?php echo esc_js(__( 'Select or Upload your Image', 'dh_popup' )); ?>",
									button: {
										text: "<?php echo esc_js(__( 'Select', 'dh_popup' )); ?>"
									},
									library: { type: 'image' },
									multiple: false
								});
		
								// when open media frame, add the selected image
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.on('open',function() {
									var selected_id = $this.closest('.dh_popup-meta-box-field').find('#<?php echo esc_attr($field['id']); ?>').val();
									if (!selected_id)
										return;
									var selection = dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.state().get('selection');
									var attachment = wp.media.attachment(selected_id);
									attachment.fetch();
									selection.add( attachment ? [ attachment ] : [] );
								});
		
								// when image selected, run callback
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.on('select', function(){
									var attachment = dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.state().get('selection').first().toJSON();
									$this.closest('.dh_popup-meta-box-field').find('input#<?php echo esc_attr($field['id']); ?>').val(attachment.id);
									var thumbnail = $this.closest('.dh_popup-meta-box-field').find('.dh_popup-meta-image-thumb');
									thumbnail.html('');
									thumbnail.append('<img src="' + attachment.url + '" alt="" />');
		
									$this.attr('value', '<?php echo esc_js(__( 'Change Image', 'dh_popup' )); ?>');
									$('#<?php echo esc_attr($field['id']); ?>_clear').css('display', 'inline-block');
								});
		
								// open media frame
								dh_popup_<?php echo esc_attr($field['id']); ?>_image_frame.open();
							});
		
							$('#<?php echo esc_attr($field['id']); ?>_clear').on('click', function(event) {
								var $this = $(this);
								$this.hide();
								$('#<?php echo esc_attr($field['id']); ?>_upload').attr('value', '<?php echo esc_js(__( 'Select Image', 'dh_popup' )); ?>');
								$this.closest('.dh_popup-meta-box-field').find('input#<?php echo esc_attr($field['id']); ?>').val('');
								$this.closest('.dh_popup-meta-box-field').find('.dh_popup-meta-image-thumb').html('');
							});
						});
					</script>
								
					<?php
					echo '</div>';
				break;
			}
		}
		
	}
}
new DH_Popup_Meta_Box();