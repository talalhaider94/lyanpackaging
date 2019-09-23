<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once vc_path_dir( 'EDITORS_DIR', 'class-vc-backend-editor.php' );

if(!class_exists('DH_Popup_Editor')){
class DH_Popup_Editor extends Vc_Backend_Editor {
	
	protected static $post_type = 'dh_popup';
	protected $templates_editor = false;
	protected static $predefined_templates = false;
	
	public function addHooksSettings(){
		parent::addHooksSettings();
		add_action( 'vc_templates_render_backend_template', array(
			&$this,
			'loadTemplate',
		), 10, 2 );
		add_filter( 'vc_templates_render_frontend_template', array(
			$this,
			'loadTemplate',
		), 10, 2 );
	}
	
	/**
	 * @return DH_Popup_Editor_Templates
	 */
	public function templatesEditor() {
		if ( false === $this->templates_editor ) {
			require_once DH_POPUP_DIR.'/includes/editor-templates.php';
			$this->templates_editor = new DH_Popup_Editor_Templates();
		}
	
		return $this->templates_editor;
	}
	
	public function loadTemplate($template_id, $template_type){
		global $dh_popup_editor_frontend;
		if ( 'dh_popup_templates' === $template_type ) {
			$predefinedTemplate = $this->loadPredefinedTemplate( $template_id, $template_type );
			if(vc_is_page_editable() && vc_enabled_frontend()){
				$dh_popup_editor_frontend->setTemplateContent( $predefinedTemplate['template'] );
				$dh_popup_editor_frontend->enqueueRequired();
				vc_include_template( 'editors/frontend_template.tpl.php', array(
					'editor' => $dh_popup_editor_frontend,
				) );
			}
			return json_encode($predefinedTemplate);
		}
		return $template_id;
	}
	
	public function loadPredefinedTemplate( $template_id, $template_type ) {
		$template = $this->templatesEditor()->load( $template_id );
		$template_meta = $template['meta'];
		if(!isset($template_meta['width']))
			$template['meta']['width'] = '';
		if(!isset($template_meta['height']))
			$template['meta']['height'] = '';
		if(!isset($template_meta['custom_css']))
			$template['meta']['custom_css'] = '';
		return $template;
	
	}
	
	public function printScriptsMessages(){
		parent::printScriptsMessages();
		wp_enqueue_style('dh_popup_editor',DH_POPUP_URL.'/assets/css/admin.css');
	}

	public function render( $post_type ) {
		if ( $this->isValidPostType( $post_type ) ) {
			$this->registerBackendJavascript();
			$this->registerBackendCss();
			// B.C:
			visual_composer()->registerAdminCss();
			visual_composer()->registerAdminJavascript();

			// meta box to render
			add_meta_box( 'wpb_visual_composer', __( 'Popup Builder', 'dh_popup' ), array(
				$this,
				'renderEditor',
			), $post_type, 'normal', 'high' );
		}
	}
	
	public function registerBackendJavascript() {
		parent::registerBackendJavascript();
		wp_register_script( 'dh_popup_editor', DH_POPUP_URL.'/assets/js/popup_builder.js', array( 'vc-backend-min-js' ), DH_POPUP_VERSION, true );
		wp_localize_script( 'dh_popup_editor', 'dh_popup_editor', array(
			'preview' => __( 'Preview', 'dh_popup' ),
			'builder' => __( 'Builder', 'dh_popup' ),
			'add_template_message' => __( 'If you add this template, all your current changes will be removed. Are you sure you want to add template?', 'dh_popup' ),
		) );
	}
	
	public function enqueueJs() {
		parent::enqueueJs();
		wp_enqueue_script( 'dh_popup_editor' );
	}
	
	public function renderEditor( $post = null ) {
		/**
		 * TODO: setter/getter for $post
		 */
		if ( ! is_object( $post ) || 'WP_Post' !== get_class( $post ) || ! isset( $post->ID ) ) {
			return false;
		}
		$this->post = $post;
		$post_custom_css = strip_tags( get_post_meta( $post->ID, '_wpb_post_custom_css', true ) );
		$this->post_custom_css = $post_custom_css;
		dh_popup_include_editor_template( 'editor.tpl.php', array(
			'editor' => $this,
			'post' => $this->post,
		) );
		add_action( 'admin_footer', array(
			$this,
			'renderEditorFooter',
		) );
		do_action( 'vc_backend_editor_render' );
	
		return true;
	}
	
	
	public function renderEditorFooter() {
		dh_popup_include_editor_template( 'editor_footer.tpl.php', array(
			'editor' => $this,
			'post' => $this->post,
		) );
		do_action( 'vc_backend_editor_footer_render' );
	}

	public function editorEnabled() {
		return true;
	}
	
	public function isValidPostType( $type = '' ) {
		$type = ! empty( $type ) ? $type : get_post_type();
		return $this->editorEnabled() && $this->postType() === $type;
	}

	public static function getPopupDemoTemplates($id=''){
		if ( false === self::$predefined_templates ) {
			self::$predefined_templates = apply_filters( 'dh_popup_predefined_templates',include DH_POPUP_DIR.'/includes/editor-template-demos.php' );
		}
		
		if(!empty($id) && isset(self::$predefined_templates[$id]))
			return self::$predefined_templates[$id];
		return self::$predefined_templates;
	}

	public static function postType() {
		return self::$post_type;
	}
}
}
