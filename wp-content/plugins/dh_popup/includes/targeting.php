<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if(!class_exists('DH_Popup_Targeting')){
class DH_Popup_Targeting{
	private static $current_tab='';
	private static $_display_options=null;
	
	public static function admin_init(){
		if(!dh_popup_use_targeting())
			return;
		add_action( 'admin_menu', array(__CLASS__,'page_menu'),13);
	} 
	
	public static function get_options(){
		return (array) get_option('dh_popup_targeting',array());
	}
	
	public static function init(){
		if(!dh_popup_use_targeting())
			return;
		add_action('wp_ajax_dh_popup_targeting_save_item', array(__CLASS__,'save_item'));
		add_action('wp_ajax_dh_popup_targeting_save_order', array(__CLASS__,'save_order'));
		add_action('wp_ajax_dh_popup_targeting_delete_item', array(__CLASS__,'delete_item'));
	}
	
	
	public static function save_order(){
		check_ajax_referer( 'dh_popup_targeting_nonce', 'nonce' );
		
		if ( !current_user_can('edit_theme_options') || !$_POST['ids'])
			wp_die( -1 );
		$options = self::get_options();
		$targeting = isset($_POST['current_tab']) ? $_POST['current_tab'] : '';
		$targeting_options = isset($options[$targeting]) ? $options[$targeting] : array();
		$targeting_options['ids'] = explode(',', $_POST['ids']);
		$options[$targeting] = $targeting_options;
		update_option('dh_popup_targeting', $options);
		die('ok');
	}
	
	public static function save_item(){
		check_ajax_referer( 'dh_popup_targeting_nonce', 'nonce' );
		
		if ( !current_user_can('edit_theme_options') || !$_POST['item_id'] || !$_POST['current_tab'])
			wp_die( -1 );
		$options = self::get_options();
		$targeting = isset($_POST['current_tab']) ? $_POST['current_tab'] : '';
		$targeting_options = isset($options[$targeting]) ? $options[$targeting] : array();
		$item_id = $_POST['item_id'];
		$targeting_options['item_number'] = $_POST['item_number'];
		$targeting_options['ids'] = explode(',', $_POST['ids']);
		unset( $_POST['current_tab'], $_POST['action'],$_POST['nonce'], $_POST['ids'], $_POST['item_id'], $_POST['item_number']);
		
		$targeting_options['items'][$item_id] = $_POST;
		
		$options[$targeting] = $targeting_options;
		update_option('dh_popup_targeting', $options);
		die('ok');
	}
	
	public static function delete_item(){
		check_ajax_referer( 'dh_popup_targeting_nonce', 'nonce' );
		
		if ( !current_user_can('edit_theme_options') || !$_POST['item_id'])
			wp_die( -1 );
		$options = self::get_options();
		$targeting = isset($_POST['current_tab']) ? $_POST['current_tab'] : '';
		$targeting_options = isset($options[$targeting]) ? $options[$targeting] : array();
		$targeting_items = isset($targeting_options['items']) ? $targeting_options['items'] : array();
		$item_id = $_POST['item_id'];
		if(isset($targeting_items[$item_id])){
			unset($targeting_items[$item_id]);
			$targeting_options['items'] = $targeting_items;
		}
		$options[$targeting] = $targeting_options;
		update_option('dh_popup_targeting', $options);
		die('ok');
	}
	
	private static function _get_tabs($name=false){
		$tabs= dh_popup_get_events();
		if(false!==$name && isset($tabs[$name]))
			return $tabs[$name];
		return $tabs;
	}
	
	public static function get_target($target){
		$options = self::get_options();
		if(isset($options[$target]))
			return $options[$target];
		return false;
	}
	
	public static function page_menu(){
		$page = add_submenu_page( 'dh_popup', __('Advanced Display','dh_popup'), __('Advanced Display','dh_popup'), 'manage_options', 'dh_popup_targeting',array(__CLASS__,'render') );
		add_action( 'load-' . $page, array(__CLASS__,'page_load') );
	}
	
	public static function page_load(){
		wp_enqueue_style('dh_popup_targeting',DH_POPUP_URL.'/assets/css/targeting.css');
		wp_register_script( 'dh_popup_targeting', DH_POPUP_URL.'/assets/js/targeting.js', array( 'jquery-ui-sortable'), DH_POPUP_VERSION, true );
		wp_localize_script('dh_popup_targeting', 'dh_popup_targeting', array(
			'loading'=>__('Loading data&hellip;','dh_popup'),
			'ajax_url'=>admin_url( 'admin-ajax.php', 'relative' ),
			'nonce'=>wp_create_nonce('dh_popup_targeting_nonce'),
			'notfound'=>__('Nothing Found&hellip;','dh_popup')
		));
		wp_enqueue_script('dh_popup_targeting');
	}
	
	private static function _get_display_options($id=false){
		if(is_null(self::$_display_options)){
			$popup_options = array();
			$popups = get_posts(array(
				'post_type'=>'dh_popup',
				'posts_per_page'=> -1,
				'post_status'=>'publish',
			));
			foreach ($popups as $popup){
				$popup_options[$popup->ID] = __('Popup: ','dh_popup').$popup->post_title;
			}
			$campaigns = get_posts(array(
				'post_type'=>'dh_popup_campaign',
				'posts_per_page'=> -1,
				'post_status'=>'publish',
			));
			foreach ($campaigns as $campaign){
				$popup_options[$campaign->ID] = __('Campaign: ','dh_popup').$campaign->post_title;
			}
			self::$_display_options = $popup_options;
		}
		if(false!==$id && isset(self::$_display_options[$id]))
			return self::$_display_options[$id];
		return self::$_display_options;
	}
	
	private static function _render_item($item){
		$popup_options = self::_get_display_options();
		$is_scroll_event = self::$current_tab==='scroll' ? true : false;
		$is_inactivity_event = self::$current_tab==='inactivity'?true : false;
		ob_start();
		?>
<div id="<?php echo isset($item->id) ? $item->id : '__i__' ?>" class="dh-popup-targeting-item">
	<div class="widget">
		<div class="widget-top">
			<div class="widget-title-action">
				<button class="widget-action hide-if-no-js" type="button" aria-expanded="false">
					<span class="screen-reader-text">Setting Panel</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
			<div class="widget-title">
				<h4><span class="widget-title-text"><?php echo isset($item->title) ? $item->title : __("Add New",'dh_popup')?></span><span class="in-widget-title"><?php echo isset($item->in_widget_title) ? $item->in_widget_title : '' ?></span></h4>
			</div>
		</div>
		<div class="widget-inside">
			<form method="post">
				<table class="form-table">
					<tbody>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Display:','dh_popup')?></th>
							<td>
								<select name="display">
									<option value="">
										<?php _e('None','dh_popup')?>
									</option>
									<?php foreach ($popup_options as $id=>$name){?>
									<option <?php selected($item->display, $id)?> value="<?php echo esc_attr($id)?>">
										<?php echo esc_html($name)?>
									</option>
									<?php }?>
								</select>
							</td>
						</tr>
						<?php /*?>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Display mode:','dh_popup')?></th>
							<td>
								<?php foreach (dh_popup_get_display_mode() as $key=>$label):?>
								<label>
									<input name="display_mode" value="<?php echo esc_attr($key)?>" <?php checked($item->display_mode, $key)?> type="radio">
									<?php echo $label?>
									<?php if('once-period'===$key){?>
									<input name="once_period_day" value="<?php echo isset($item->once_period_day) ? $item->once_period_day : 5?>" type="text">
									<?php _e('days')?>
									<?php }?>
								</label>
								<br/>
								<?php endforeach;?>
							</td>
						</tr>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Open delay:','dh_popup')?></th>
							<td>
								<input name="open_delay" value="<?php echo $item->open_delay ?>" type="text"><?php _e('seconds','dh_popup')?>
							</td>
						</tr>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Close delay:','dh_popup')?></th>
							<td>
								<input name="close_delay" value="<?php echo $item->close_delay ?>" type="text"><?php _e('seconds','dh_popup')?>
							</td>
						</tr>
						*/?>
						<?php if($is_scroll_event):?>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Scroll offset:','dh_popup')?></th>
							<td>
								<label>
									<input name="scroll_offset" value="<?php echo isset($item->scroll_offset) ? $item->scroll_offset : 50;  ?>" type="text">
									<br />
									<?php _e('Enter x % when customer scroll on page','dh_popup')?>
								</label>
							</td>
						</tr>
						<?php endif;?>
						<?php if($is_inactivity_event):?>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Seconds inactivity:','dh_popup')?></th>
							<td>
								<label>
									<input name="inactivity_seconds" value="<?php echo isset($item->inactivity_seconds) ? $item->inactivity_seconds : 10;  ?>" type="text">
									<br />
									<?php _e('Enter x seconds of inactivity','dh_popup')?>
								</label>
							</td>
						</tr>
						<?php endif;?>
						<tr class="" valign="top">
							<th scope="row"><?php _e('Display in:','dh_popup')?></th>
							<td>
								<?php foreach (dh_popup_get_display_in() as $key=>$label):?>
								<label>
									<input name="display_in[]" value="<?php echo esc_attr($key)?>" <?php echo (in_array($key, $item->display_in) ? 'checked="checked"':'')?> type="checkbox">
									<?php echo $label;?>
								</label>
								<br/>
								<?php endforeach;?>
							</td>
						</tr>
					</tbody>
				</table>
				<div class="widget-control-actions">
					<div class="alignleft">
						<button class="button-link button-link-delete widget-control-remove" type="button"><?php _e('Delete','dh_popup')?></button>
						|
						<button class="button-link widget-control-close" type="button"><?php _e('Close','dh_popup')?></button>
					</div>
					<div class="alignright">
						<input class="button button-primary widget-control-save right" name="savewidget" value="<?php esc_attr_e('Save','dh_popup')?>" type="submit">
						<span class="spinner"></span>
					</div>
					<br class="clear">
				</div>
			</form>
		</div>
	</div>
</div>		
		<?php 
		return trim(ob_get_clean());
	}
	
	public static function render(){
		// Get current tab/section
		$current_tab     = empty( $_GET['tab'] ) ? 'load' : sanitize_title( $_GET['tab'] );
		self::$current_tab = $current_tab;
		$template_data = new stdClass();
		$template_data->display='';
		$template_data->display_mode='every-time';
		$template_data->once_period_day = 5;
		$template_data->open_delay=0;
		$template_data->close_delay=0;
		$template_data->display_in=array('all');
		$template_data->title = __('Add new','dh_popup');
		$options = self::get_options();
		$targeting_options = isset($options[$current_tab]) ? $options[$current_tab] : array();
		$targeting_items = isset($targeting_options['items']) ? $targeting_options['items'] : array();
		$targeting_ids = isset($targeting_options['ids']) ? $targeting_options['ids'] : array();
		$item_number = isset($targeting_options['item_number']) ? $targeting_options['item_number'] : 0;
		?>
<div class="wrap dh-popup-targeting" data-item_template="<?php echo esc_attr(self::_render_item($template_data))?>">
	<nav class="nav-tab-wrapper">
		<?php
			foreach ( self::_get_tabs() as $name => $label ) {
				echo '<a href="' . admin_url( 'admin.php?page=dh_popup_targeting&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
			}
		?>
	</nav>
	<div class="dh-popup-targeting-list">
		<div class="dh-popup-targeting-notice">
			<?php echo sprintf(__('You can add multiple Popup or Campaign display: %s','dh_popup'),'<strong>'.self::_get_tabs($current_tab).'</strong>')?>.
			<?php if(self::$current_tab==='onclick'):?>
			<br/>
			<br/>
			<code>
			<?php echo esc_html__('Set attribute data-popup-open="{popup_ID}" for a element, like a button, or href="#popup_open_{popup_ID}" to target a specific popup to display.Example: <button type="button" data-popup-open="{popup_ID}">Show Popup</button>','dh_popup')?>
			</code>
			<?php endif;?>
		</div>
		<div class="dh-popup-targeting-action">
			<input name="dh-popup-targeting-new" id="dh-popup-targeting-new" class="button-primary" type="button" value="<?php esc_attr_e( 'Add New', 'dh_popup' ); ?>" />				
			<span class="spinner"></span>
		</div>
		<br class="clear">
		<div class="dh-popup-targeting-sortables">
			<?php 
			foreach ($targeting_ids as $targeting_id):
				if(isset($targeting_items[$targeting_id])){
					$targeting_option = (object)$targeting_items[$targeting_id];
					$display = self::_get_display_options($targeting_option->display);
					if(is_array($display)||!in_array(get_post_type((int)$targeting_option->display),array('dh_popup_campaign','dh_popup')))
						continue;
					$targeting_option->id = $targeting_id;
					$targeting_option->title=__("Display",'dh_popup');
					
					if(!isset($targeting_option->display) || empty($targeting_option->display)){
						$targeting_option->in_widget_title = ": ".__("None",'dh_popup');
					}else{
						$targeting_option->in_widget_title = ": ".$display;
					}
					echo self::_render_item($targeting_option);
				}
			endforeach;
			?>
		</div>
	</div>
	<input name="_current_tab" id="_current_tab" type="hidden" value="<?php echo $current_tab?>">
	<input name="_item_number" id="_item_number" type="hidden" value="<?php echo $item_number?>">
</div>
		<?php
	}
}
}