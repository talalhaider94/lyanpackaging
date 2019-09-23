<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class DH_Popup_Frontend {
	public function __construct(){
		add_action( 'template_redirect', array(__CLASS__,'register_assets'),30);
		add_action( 'wp_enqueue_scripts', array(__CLASS__,'enqueue_css'));
		add_action( 'wp_enqueue_scripts', array(__CLASS__,'enqueue_js'));
		add_shortcode('dh_popup_text_field', array(__CLASS__,'text_field_shortcode_render'));
		add_shortcode('dh_popup', array(__CLASS__,'popup_shortcode_render'));
		
		$hook = dh_popup_get_option('hook','get_header');
		if(!is_admin()){
			add_action($hook, array(__CLASS__, 'init'), 15);
			add_action('get_header', array(__CLASS__,'add_popup_css_to_header'),20);
			add_action('wp_footer', array(__CLASS__,'add_popup_to_footer'));
		}
	}
	
	public static function init(){
		global $post,$dh_popup;
		$post_id = 0;
		$popup_list=array();
		$current_page_type='';
		if(is_front_page())
			$current_page_type = 'home';
		elseif (is_page())
			$current_page_type = 'pages';
		elseif (is_single())
			$current_page_type = 'posts';
		elseif (!is_front_page() && !is_page() && !is_single())
			$current_page_type = 'others';
		else 
			$current_page_type = 'all';
		
		$use_targeting = dh_popup_use_targeting();
		if($use_targeting){
			foreach (dh_popup_get_events() as $event=>$label){
				$target = DH_Popup_Targeting::get_target($event);
				if(isset($target['items']) && is_array($target['items'])){
					foreach ($target['items'] as $item){
						if(!isset($item['display']) || empty($item['display']) || (!in_array('all', $item['display_in']) && !in_array($current_page_type, $item['display_in'])))
							continue;
						$scroll_offset = isset($item['scroll_offset']) ? absint($item['scroll_offset']) : 0;
						$inactivity_seconds = isset($item['inactivity_seconds']) ? absint($item['inactivity_seconds']) : 10;
						$popup_list[] = self::render_popup($item['display'],$event,$scroll_offset,$inactivity_seconds);
					}
				}
			}
		}else{
			$query_args = array(
				'post_type'=>'dh_popup',
				'posts_per_page'=> -1,
				'post_status'=>'publish',
				'meta_query'=>array(
					array(
						'key' => '_dh_popup_open_event',
						'value' => array_keys(dh_popup_get_events()),
						'compare'=>'IN'
					)
				)
			);
			$popup_data = new WP_Query($query_args);
			if($popup_data->have_posts()):
				while ($popup_data->have_posts()): $popup_data->the_post(); global $post;
					$display = dh_popup_get_post_meta('display_in');
					if(empty($display) || (!in_array('all', $display) && !in_array($current_page_type, $display) ))
						continue;

					$event = dh_popup_get_post_meta('open_event');
					$scroll_offset = dh_popup_get_post_meta('scroll_offset',$post->ID,10);
					$inactivity_seconds = dh_popup_get_post_meta('inactivity_seconds',$post->ID,0);
					$popup_list[] = self::render_popup($post->ID,$event,$scroll_offset,$inactivity_seconds);
				endwhile;
			endif;
		}
		$dh_popup = $popup_list;
	}
	
	public static function register_assets(){
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_register_style('dh_popup',DH_POPUP_URL.'/assets/css/style.css',array('dh_popup_fancybox'),DH_POPUP_VERSION);
		if(apply_filters('dh_popup_enqueue_fancybox_js', true))
			wp_enqueue_script('dh_popup_fancybox');
		if(apply_filters('dh_popup_enqueue_cookie_js', true))
			wp_enqueue_script('dh_popup_cookie');
		wp_register_script('dh_popup', DH_POPUP_URL.'/assets/js/script'.$suffix.'.js',array('jquery'),DH_POPUP_VERSION,true);
	}
	
	public static function enqueue_css(){
		wp_enqueue_style('dh_popup');
	}
	
	public static function enqueue_js(){
		if(defined('DH_POPUP_IS_FRONTEND_EDITOR'))
			return;
		$ga_tracking =  dh_popup_get_option('ga_tracking');
		$dhvcPopupSetting = array(
			'ajax_url'=>admin_url( 'admin-ajax.php', 'relative' ),
			'cookie_prefix'=>dh_popup_get_cookie_prefix(),
			'ga_tracking'=>!empty($ga_tracking) ? 1 : 0,
			'interval_show_popup_again'=>self::display_only_once_interval(),
			'max_inactivity_timer_interval'=>apply_filters('dh_popup_max_inactivity_timer_interval', 50),
			'nonce'=>wp_create_nonce('dh_popup_ajax')
		);
		wp_localize_script('dh_popup', 'dhvcPopupSetting', $dhvcPopupSetting);
		wp_enqueue_script('dh_popup');
	}
	
	public static function display_only_once_interval(){
		return apply_filters('dh_popup_display_only_once_interval', 30);
	}
	
	public static function add_popup_css_to_header(){
		global $dh_popup_css;
		if(is_404())
			return;
		if(!empty($dh_popup_css))
			wp_add_inline_style('dh_popup',$dh_popup_css);
		unset($dh_popup_css);
	}
	
	public static function add_popup_to_footer(){
		global $dh_popup;
		if(is_404())
			return;
		if(!empty($dh_popup) && is_array($dh_popup))
			echo implode("\n", $dh_popup);
	}
	
	public static function popup_shortcode_render($atts,$content = null){
		$atts = shortcode_atts ( array(
			'id'=>''
		), $atts );
		extract( $atts );
		$popup = get_post(absint($id));
		$display = dh_popup_get_post_meta('display_in',$popup->ID);
		$event = dh_popup_get_post_meta('open_event',$popup->ID);
		$scroll_offset = dh_popup_get_post_meta('scroll_offset',$popup->ID,10);
		$inactivity_seconds = dh_popup_get_post_meta('inactivity_seconds',$popup->ID,0);
		return self::remove_wpautop($content).self::render_popup($popup->ID,$event,$scroll_offset,$inactivity_seconds);
		
	}
	
	public static function text_field_shortcode_render($atts){
		$atts = vc_map_get_attributes( 'dh_popup_text_field', $atts );
		extract( $atts );
		if(empty($name))
			return __("Field name is required",'dh_popup');
		
		$input_class=' dh_popup_field__group--'.$name;
		$icon_html = '';
		if ( 'true' === $add_icon ) {
			vc_icon_element_fonts_enqueue( $i_type );
			if ( isset( ${'i_icon_' . $i_type} ) ) {
				$icon_class = ${'i_icon_' . $i_type};
			} else {
				$icon_class = 'fa fa-adjust';
			}
			$input_class .= ' dh-popup-field--has-icon';
			$icon_html ='<span class="dh-popup-field__icon"><i class="'.$icon_class.'"></i></span>'."\n";
			
		}
		$wrap_css= 'dh_popup_field';
		if ( '' !== $css_animation && 'none' !== $css_animation ) {
			wp_enqueue_script( 'waypoints' );
			wp_enqueue_style( 'animate-css' );
			$wrap_css .= ' wpb_animate_when_almost_visible wpb_' . $css_animation . ' ' . $css_animation;
		}
		$name = esc_attr($name);
		$input_height = !empty($height) ? 'height:'.$height.';' : '';
		$input_width = !empty($width) ? 'width:'.$width.';' : '';
		
		$class_to_input = vc_shortcode_custom_css_class( $input_css, ' ' );
		
		$html = '';
		$html .= '<div class="'.$wrap_css.'">';
		if(!empty($label))
			$html .= '<label class="dh_popup_field__label" for="dh_popup_field_'.$name.'">'.$label.'</label>';
		$html .= '<div class="dh_popup_field__group'.$input_class.esc_attr($el_class).'">';
		$html .= $icon_html;
		$html .= '<input 
				class="dh_popup__form-control dh_popup_field__text'.esc_attr($class_to_input).'"
				type="'.$type.'" 
				id="dh_popup_field_'.$name.'"
				name="'.$name.'"
				aria-invalid="false"
				placeholder="'.$placeholder.'"
				value="'.(!empty($default_value) ? esc_attr($default_value) : '').'"
				style="'.$input_width.$input_height.'"
				'.(!empty($required) ? 'required aria-required="true"':'').'
				'.(!empty($readonly) ? 'readonly':'').'
				/>'. "\n";
		if(!empty($help_text))
			$html .='<span class="dh-popup__field-help">'.$help_text.'</span>' . "\n";
		$html .= '</div>';
		$html .= '</div>';
		return apply_filters('dh_popup_text_field_shortcode_render', $html,$atts);
	}
	
	
	protected static function _popup_hidden_fields($form,$campaign_id=false){
		$hidden_fields = array(
			'action'=>'dh_popup_form_ajax',
			'_dh_popup' => $form->ID,
			'_campaign_id'=>false===$campaign_id ? 0:$campaign_id,
			'_dh_popup_version' => DH_POPUP_VERSION,
			'_dh_popup_nonce'=>wp_create_nonce('dh_popup_'.$form->ID)
		);
		
		$hidden_fields += (array) apply_filters('dh_popup_form_hidden_fields', array() );
		
		$content = '';
		
		foreach ( $hidden_fields as $name => $value ) {
			$content .= sprintf(
				'<input type="hidden" name="%1$s" value="%2$s" />',
				esc_attr( $name ), esc_attr( $value ) ) . "\n";
		}
		
		return '<div style="display: none;">' . "\n" . $content . '</div>' . "\n";
	}
	
	protected static function _edit_link($popup){
		if(!apply_filters('dh_popup_show_edit_link',true))
			return;
			
		$action = '&amp;action=edit';
	
		$popup_type_object = get_post_type_object( $popup->post_type );
		if ( !$popup_type_object )
			return;
	
		if ( !current_user_can( 'edit_post', $popup) )
			return;
			
		$url= admin_url( sprintf( $popup_type_object->_edit_link . $action, $popup->ID ));
		$link = '<div class="edit-link" style="position: absolute; left: 10px; bottom: 20px; background: rgb(0, 0, 0) none repeat scroll 0% 0%; color: rgb(255, 255, 255); padding: 2px 5px; z-index: 1000;"><a style="font-size: 0.8em; color: rgb(255, 255, 255);" class="post-edit-link" href="' . $url . '">' . __('Edit Popup','dh_popup') . '</a></div>';
		return $link;
	}
	
	/**
	 * 
	 * @param WP_Post $popup
	 */
	protected static  function _popup_atts($popup_id,$event='load',$scroll_offset=0,$inactivity_seconds=10,$preview=false){
		if($preview){
			$event = 'load';
			$scroll_offset = 0;
			$inactivity_seconds=0;
		}
		$overlay_image = dh_popup_get_post_meta('overlay_image_background',$popup_id);
		$closetype = dh_popup_get_post_meta('close_type',$popup_id,'default');
		$disable_overlay_click = dh_popup_get_post_meta('disable_overlay_click',$popup_id);
		$display_mode = dh_popup_get_post_meta('display_mode',$popup_id,'every-time');
		$open_when = $event;
		$atts = array(
			'id'=> $popup_id,
			'open_type'=> $open_when,
			'open_mode'=> $display_mode,
			'open_delay'=>absint(dh_popup_get_post_meta('open_delay',$popup_id,0)),
			'close_type'=> $closetype,
			'close_delay'=> $closetype === 'default' ? dh_popup_get_post_meta('close_delay',$popup_id,0) : 0,
			'hide_close_button'=>dh_popup_get_post_meta('hide_close_button',$popup_id),
			'overlay_type'=>dh_popup_get_post_meta('overlay',$popup_id),
			'overlay_image'=>$overlay_image ? wp_get_attachment_url($overlay_image) : '',
			'disable_responsive'=>dh_popup_get_post_meta('disable_responsive',$popup_id),
			'hide_on_mobile'=>dh_popup_get_post_meta('hide_on_mobile',$popup_id),
			'redirect_url'=>esc_attr(esc_url_raw(dh_popup_get_post_meta('redirect_url',$popup_id))),
			'disable_overlay_click'=>!empty($disable_overlay_click) ? 1 : 0,
			'position'=> defined('DH_POPUP_IS_FRONTEND_EDITOR') || $preview ? 'center' : dh_popup_get_post_meta('position',$popup_id,'center')
		);
		if($open_when==='scroll')
			$atts['scroll_offset'] = $scroll_offset;//absint(dh_popup_get_post_meta('scroll_open',$popup_id,10));
		
		if($open_when==='inactivity')
			$atts['inactivity_seconds'] = $inactivity_seconds;//absint(dh_popup_get_post_meta('scroll_open',$popup_id,10));
		
		if('once-period'===$display_mode){
			$atts['open_interval'] = absint(dh_popup_get_post_meta('once_period_day',$popup_id,1));
		}elseif ('once-only'===$display_mode){
			$atts['open_interval'] = self::display_only_once_interval();
		}
		
		$atts = apply_filters('dh_popup_attr', $atts,$popup_id);
		$data_atts = array();
		
		foreach ($atts as $key=>$att){
			$data_atts[] = sprintf('data-%s="%s"',$key,strtolower(esc_attr($att)));
		}
		return implode(' ', $data_atts);
	}
	
	
	public static function remove_wpautop( $content, $autop = false ) {
	
		if ( $autop ) {
			$content = wpautop( preg_replace( '/<\/?p\>/', "\n", $content ) . "\n" );
		}
	
		return do_shortcode( shortcode_unautop( $content ) );
	}
	
	public static function fixPContent( $content = null ) {
		if ( $content ) {
			$s = array(
				'/' . preg_quote( '</div>', '/' ) . '[\s\n\f]*' . preg_quote( '</p>', '/' ) . '/i',
				'/' . preg_quote( '<p>', '/' ) . '[\s\n\f]*' . preg_quote( '<div ', '/' ) . '/i',
				'/' . preg_quote( '<p>', '/' ) . '[\s\n\f]*' . preg_quote( '<section ', '/' ) . '/i',
				'/' . preg_quote( '</section>', '/' ) . '[\s\n\f]*' . preg_quote( '</p>', '/' ) . '/i',
			);
			$r = array(
				'</div>',
				'<div ',
				'<section ',
				'</section>',
			);
			$content = preg_replace( $s, $r, $content );
	
			return $content;
		}
	
		return null;
	}
	
	/**
	 * 
	 * @param WP_Post $popup
	 */
	public static function render_popup($popup,$event='load',$scroll_offset=0,$inactivity_seconds=10){
		global $dh_popup;
		$campaign_id = false;
		if('dh_popup_campaign'===get_post_type($popup)){
			$campaign_id = $popup;
			$campaign_popup = dh_popup_get_post_meta('popup_id',$popup);
			if(!empty($campaign_popup))
				$popup = $campaign_popup[array_rand($campaign_popup,1)];
		}
		$preview = isset($popup->is_preview) && true===$popup->is_preview ? true : false;

		$popup = $preview ? $popup : get_post($popup);
		
		if(!$preview && 'dh_popup'!==get_post_type($popup))
			return;
		
		if( !$preview && apply_filters('dh_popup_render_check_cookie', true) && isset($_COOKIE[dh_popup_get_cookie_prefix().$popup->ID]))
			return;
		
		wp_enqueue_style('js_composer_front');
		wp_enqueue_style('js_composer_custom_css');
		
		$custom_css = '';
		$close_button_color=dh_popup_get_post_meta('close_button_color',$popup->ID,'');
		if(!empty($close_button_color))
			$custom_css.='.dh-popup__wrap--'.$popup->ID.' .fancybox-close__line-bottom,.dh-popup__wrap--'.$popup->ID.' .fancybox-close__line-top{background:'.$close_button_color.'}';
		
		if(!defined('DH_POPUP_IS_FRONTEND_EDITOR') && $wpb_custom_css = get_post_meta( $popup->ID, '_wpb_post_custom_css', true ))
			$custom_css .= $wpb_custom_css;
		
		if(!defined('DH_POPUP_IS_FRONTEND_EDITOR') && $wpb_shortcodes_custom_css = get_post_meta( $popup->ID, '_wpb_shortcodes_custom_css', true ))
			$custom_css .= $wpb_shortcodes_custom_css;

		if($background = dh_popup_get_post_meta('background',$popup->ID)){
			$bg = array();
			$bg['background-color']  = !empty($background['background-color']) ? $background['background-color'] : '';
			$bg['background-repeat']  = !empty($background['background-repeat']) ? $background['background-repeat'] : '';
			$bg['background-size']  = !empty($background['background-size']) ? $background['background-size'] : '';
			$bg['background-attachment'] = !empty($background['background-attachment']) ? $background['background-attachment']:'';
			$bg['background-position'] = !empty($background['background-position']) ? $background['background-position']:'';
			$bg['background-image'] = !empty($background['background-image']) ? 'url("'.wp_get_attachment_url($background['background-image']).'")' : '';
			$custom_css .= '#dh_popup_'.$popup->ID.'{background:'.$bg['background-color'].' '.$bg['background-image'].' '.$bg['background-repeat'].' '.$bg['background-attachment'].' '.$bg['background-position'].'}';
				
		}
		$full_screen = dh_popup_get_post_meta('full_screen',$popup->ID);
		if(!empty($full_screen))
			$custom_css.='.dh-popup__wrap--'.$popup->ID.' .fancybox-skin{box-shadow:none;-webkit-box-shadow:none;-moz-box-shadow:none}';
		if(!empty($custom_css)){
			if($preview || defined('DH_POPUP_IS_FRONTEND_EDITOR'))
				echo '<style type="text/css">'.$custom_css.'</style>'."\n";
			else 
				dh_popup_enqueue_css($custom_css);
		}
		$height = $preview && isset($popup->height) ? $popup->height : dh_popup_get_post_meta('height',$popup->ID);
		$width = $preview && isset($popup->width) ? $popup->width : dh_popup_get_post_meta('width',$popup->ID,'500');
		$popup_class=apply_filters('dh_popup_class', 'dh-popup'.(absint(dh_popup_get_post_meta('disable_responsive',$popup->ID)) ? '':' vc_non_responsive'));
		ob_start();
		?>
		<div id="dh_popup_<?php echo esc_attr($popup->ID)?>" 
			class="<?php echo esc_attr($popup_class)?>" 
			style="<?php echo !empty($width) ? 'width:'.absint($width).'px;' :''?><?php echo !empty($height) ? 'height:'.absint($height).'px;':'' ?>display: none"
			data-width="<?php echo esc_attr($width)?>" data-height="<?php echo esc_attr($height)?>"
			<?php echo self::_popup_atts($popup->ID,$event,$scroll_offset,$inactivity_seconds,$preview)?>
			>
			<div class="dh-popup__inner">
				<form id="dh_popup_form_<?php echo esc_attr($popup->ID)?>" action="" method="post">
				<?php echo defined('DH_POPUP_IS_FRONTEND_EDITOR') ? apply_filters('the_content', $popup->post_content) : self::remove_wpautop(self::fixPContent($popup->post_content))?>
				<?php echo self::_popup_hidden_fields($popup,$campaign_id)?>
				</form>
			</div>
			<?php echo !$preview ? self::_edit_link($popup) : ''?>
		</div>
		<?php
		return apply_filters('dh_popup_render', ob_get_clean());
	}
	
}

new DH_Popup_Frontend();