<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

require_once vc_path_dir( 'EDITORS_DIR', 'popups/class-vc-templates-panel-editor.php' );

class DH_Popup_Editor_Templates extends Vc_Templates_Panel_Editor {
	
	public function __construct() {
		add_filter( 'vc_templates_render_category', array(
			&$this,
			'renderTemplateBlock',
		), 10, 2 );
		add_filter( 'vc_templates_render_template', array(
			&$this,
			'renderTemplateWindowPopup',
		), 10, 2 );
	}

	public function renderTemplateBlock( $category ) {
		if ( 'dh_popup_templates' === $category['category'] ) {
			$category['output'] = '<div class="vc_col-md-12">';
			if ( isset( $category['category_name'] ) ) {
				$category['output'] .= '<h3>' . esc_html( $category['category_name'] ) . '</h3>';
			}
			if ( isset( $category['category_description'] ) ) {
				$category['output'] .= '<p class="vc_description">' . esc_html( $category['category_description'] ) . '</p>';
			}
			$category['output'] .= '</div>';

			$category['output'] .= '
			<div class="vc_column vc_col-sm-12">
				<div class="vc_ui-template-list vc_templates-list-my_templates vc_ui-list-bar" data-vc-action="collapseAll">';
			if ( ! empty( $category['templates'] ) ) {
				foreach ( $category['templates'] as $template ) {
					$category['output'] .= $this->renderTemplateListItem( $template );
				}
			}
			$category['output'] .= '
				</div>
			</div>';
		}

		return $category;
	}

	/** Output rendered template in modal dialog
	 * @since 4.4
	 *
	 * @param $template_name
	 * @param $template_data
	 *
	 * @return string
	 */
	public function renderTemplateWindowPopup( $template_name, $template_data ) {
		if ( 'dh_popup_templates' === $template_data['type']  ) {
			return $this->renderTemplateWindowPopupTemplate( $template_name, $template_data );
		}

		return $template_name;
	}

	/**
	 * @since 4.4
	 *
	 * @param $templateName
	 * @param $templateData
	 *
	 * @return string
	 */
	protected function renderTemplateWindowPopupTemplate( $templateName, $templateData ) {

		ob_start();

		$templateId = esc_attr( $templateData['unique_id'] );
		$templateName = esc_html( $templateName );
		$templateNameLower = strtolower( $templateName );
		$templateType = esc_attr( isset( $templateData['type'] ) ? $templateData['type'] : 'custom' );
		$customClass = esc_attr( isset( $templateData['custom_class'] ) ? $templateData['custom_class'] : '' );
		$previewTemplateTitle = esc_attr( 'Preview template', 'dh_popup' );
		$addTemplateTitle = esc_attr( 'Use template', 'dh_popup' );

		echo <<<HTML
			<button type="button" class="vc_ui-list-bar-item-trigger" title="$addTemplateTitle"
				data-template-handler=""
				data-vc-ui-element="template-title">$templateName</button>
			<div class="vc_ui-list-bar-item-actions">
				<button type="button" class="vc_general vc_ui-control-button" title="$addTemplateTitle"
					 	data-template-handler="" data-vc-ui-element="template-title">
					<i class="vc-composer-icon vc-c-icon-add" style="vertical-align: middle;"></i> <span>$addTemplateTitle</span>
				</button>
				<button type="button" class="vc_general vc_ui-control-button" title="$previewTemplateTitle"
					data-dh-popup-preview-handler data-vc-container=".vc_ui-list-bar" data-vc-target="[data-template_id=$templateId]">
					<i class="dashicons dashicons-welcome-view-site" style="vertical-align: middle;"></i> <span>$previewTemplateTitle</span>
				</button>
			</div>
HTML;

		return ob_get_clean();
	}

	public function load( $template_id = false ) {
		if ( ! $template_id ) {
			$template_id = vc_post_param( 'template_unique_id' );
		}
		if ( ! isset( $template_id ) || '' === $template_id ) {
			echo 'Error: TPL-02';
			die();
		}
		if ( false !== ( $predefined_template = $this->getPopupTemplates( $template_id ) ) ) {
			return $predefined_template;
		}
	}

	public function getAllTemplates() {
		$data = array();
		$popup_templates = $this->getPopupTemplates();
		// this has only 'name' and 'template' key  and index 'key' is template id.
		if ( ! empty( $popup_templates ) ) {
			$arr_category = array(
				'category' => 'dh_popup_templates',
				'category_name' => __( 'Popup Templates', 'dh_popup' ),
				'category_weight' => 10,
			);
			$category_templates = array();
			foreach ( $popup_templates as $template_id => $template_data ) {
				$category_templates[] = array(
					'unique_id' => $template_id,
					'name' => $template_data['name'],
					'type' => 'dh_popup_templates',
					// for rendering in backend/frontend with ajax
				);
			}
			$arr_category['templates'] = $category_templates;
			$data[] = $arr_category;
		}

		// To get any other 3rd "Custom template" - do this by hook filter 'vc_get_all_templates'
		return apply_filters( 'dh_popup_get_all_templates', $data );
	}
	

	public function getPopupTemplates($id='') {
		$list = DH_Popup_Editor::getPopupDemoTemplates();
		if(!empty($id) && isset($list[$id]))
			return $list[$id];
		return $list;
	}
}
