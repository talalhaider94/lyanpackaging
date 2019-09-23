<?php
/**
 * Reusable Content & Text Blocks Plugin by Loomisoft - ls_cb_widget Class
 * Copyright (c) 2017 Loomisoft (www.loomisoft.com)
 */

defined( 'LS_CB_PLUGIN' ) or die();

class ls_cb_widget extends WP_Widget {
	public function __construct() {
		parent::__construct( 'ls_content_block', __( 'Loomisoft Content Block', 'loomisoft-content-blocks-text-domain' ), array( 'description' => __( 'Place a content/text block in the selected area.', 'loomisoft-content-blocks-text-domain' ) ) );
	}

	function get_widget_info( $instance, &$title, &$cbid, &$para, &$cssid, &$cssclass ) {
		$title = '';

		if ( isset( $instance[ 'title' ] ) ) {
			$title = strip_tags( strval( $instance[ 'title' ] ) );
		}

		$cbid = '';

		if ( isset( $instance[ 'cbid' ] ) ) {

			$cbid = ls_cb_main::get_clean_id( $instance[ 'cbid' ] );

			if ( $cbid === false ) {
				$cbid = '';
			} elseif ( ! isset( ls_cb_main::$content_block_list[ $cbid ] ) ) {
				$cbid = '';
			}
		}

		$para = '';

		if ( isset( $instance[ 'para' ] ) ) {
			$para = ls_cb_main::get_clean_para( $instance[ 'para' ] );
		} elseif ( isset( $instance[ 'wpautop' ] ) ) {
			$para = ls_cb_main::get_clean_para( $instance[ 'wpautop' ] );
		}

		$cssid = '';

		if ( isset( $instance[ 'cssid' ] ) ) {
			$cssid = strip_tags( strval( $instance[ 'cssid' ] ) );
		}

		$cssclass = '';

		if ( isset( $instance[ 'cssclass' ] ) ) {
			$cssclass = strip_tags( strval( $instance[ 'cssclass' ] ) );
		}

		return ( $cbid != '' );
	}

	public function widget( $args, $instance ) {

		$title = '';
		$cbid  = '';
		$para  = '';
		$cssid = '';
		$cssclass = '';

		if ( $this->get_widget_info( $instance, $title, $cbid, $para, $cssid, $cssclass ) ) {

			echo $args[ 'before_widget' ];

			if ( ! empty( $title ) ) {
				echo $args[ 'before_title' ] . apply_filters( 'widget_title', $title ) . $args[ 'after_title' ];
			}

			echo '<div';
			if ($cssid != '') {
				echo ' id="' . esc_html( $cssid ) . '"';
			}
			if ($cssclass != '') {
				echo ' class="' . esc_html( $cssclass ) . '"';
			}
			echo '>';
			echo ls_cb_main::get_block_by_id( $cbid, $para );
			echo '</div>';

			echo $args[ 'after_widget' ];
		}
	}

	public function form( $instance ) {
		$title = '';
		$cbid  = '';
		$para  = '';
		$cssid = '';
		$cssclass = '';

		$this->get_widget_info( $instance, $title, $cbid, $para, $cssid, $cssclass );

		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'title' ) . '">' . esc_html( __( 'Title:', 'loomisoft-content-blocks-text-domain' ) ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'title' ) . '" name="' . $this->get_field_name( 'title' ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'cbid' ) . '">' . esc_html( __( 'Content Block:', 'loomisoft-content-blocks-text-domain' ) ) . '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id( 'cbid' ) . '" name="' . $this->get_field_name( 'cbid' ) . '">';
		echo '<option value=""' . ( ( $cbid == '' ) ? ' selected="selected"' : '' ) . '></option>';
		foreach ( ls_cb_main::$content_block_list as $content_block_id => $content_block_title ) {
			echo '<option value="' . esc_attr( $content_block_id ) . '"' . ( ( $cbid == $content_block_id ) ? ' selected="selected"' : '' ) . '>' . esc_html( $content_block_title ) . '</option>';
		}
		echo '</select>';
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'para' ) . '">' . esc_html( __( 'Content Filtering / Paragraph Tags:', 'loomisoft-content-blocks-text-domain' ) ) . '</label>';
		echo '<select class="widefat" id="' . $this->get_field_id( 'para' ) . '" name="' . $this->get_field_name( 'para' ) . '">';
		foreach ( ls_cb_main::$para_list as $para_key => $para_value ) {
			if ( $para_key == 'none' ) {
				$para_key = '';
			}
			echo '<option value="' . esc_attr( $para_key ) . '"' . ( ( $para_key == $para ) ? ' selected="selected"' : '' ) . '>' . esc_html( __( $para_value, 'loomisoft-content-blocks-text-domain' ) ) . '</option>';
		}
		echo '</select>';
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'cssid' ) . '">' . esc_html( __( 'Custom CSS ID (excluding the beginning "#"):', 'loomisoft-content-blocks-text-domain' ) ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'cssid' ) . '" name="' . $this->get_field_name( 'cssid' ) . '" type="text" value="' . esc_attr( $cssid ) . '" />';
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id( 'cssclass' ) . '">' . esc_html( __( 'Custom CSS Class (excluding the beginning "."):', 'loomisoft-content-blocks-text-domain' ) ) . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id( 'cssclass' ) . '" name="' . $this->get_field_name( 'cssclass' ) . '" type="text" value="' . esc_attr( $cssclass ) . '" />';
		echo '</p>';
	}

	public function update( $new_instance, $old_instance ) {
		$title = '';
		$cbid  = '';
		$para  = '';
		$cssid = '';
		$cssclass = '';

		$this->get_widget_info( $new_instance, $title, $cbid, $para, $cssid, $cssclass );

		$instance = array(
			'title' => $title,
			'cbid'  => $cbid,
			'para'  => $para,
			'cssid' => $cssid,
			'cssclass' => $cssclass
		);

		return $instance;
	}
}
