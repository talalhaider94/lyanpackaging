<?php
/**
 * Reusable Content & Text Blocks Plugin by Loomisoft - ls_cb_main Class
 * Copyright (c) 2017 Loomisoft (www.loomisoft.com)
 */

defined( 'LS_CB_PLUGIN' ) or die();

class ls_cb_main {
	static public $plugin_name = 'Reusable Content & Text Blocks by Loomisoft';
	static public $plugin_version = '1.4.3';
	static public $plugin_wp_url = 'https://wordpress.org/plugins/loomisoft-content-blocks/';

	static public $option_name = 'ls_cb_option';
	static public $option_prefix = 'ls_cb_option_';
	static public $form_element_prefix = 'ls-cb-form-item-';
	static public $nonce_name = 'ls_cb_nonce';
	static public $post_type_slug = 'lscontentblock';
	static public $post_type_label = 'Content Block';
	static public $post_type_labels = 'Content Blocks';
	static public $post_type_menu_icon = 'images/ls-wp-menu-icon.png';
	static public $usage_about_page = 'ls_cb_usage_about_page';
	static public $post_type_shortcode = 'ls_content_block';

	static public $plugin_file;
	static public $plugin_path;
	static public $plugin_url;
	static public $plugin_basename;

	static public $circular_block_tracker = array();
	static public $page_title = '';
	static public $content_block_list = array();
	static public $content_block_slug_list = array();
	static public $content_block_list_by_slug = array();
	static public $option_values = array();
	static public $para_list = array(
		'none'                     => 'No Paragraph Tags / Run Shortcodes',
		'no-shortcodes'            => 'No Paragraph Tags / No Shortcodes',
		'paragraphs'               => 'Add Paragraph Tags / Run Shortcodes',
		'paragraphs-no-shortcodes' => 'Add Paragraph Tags / No Shortcodes',
		'full'                     => 'Full Content Filtering'
	);
	static public $var_values = array();

	static public function start( $plugin_file ) {

		self::$plugin_file     = $plugin_file;
		self::$plugin_path     = plugin_dir_path( self::$plugin_file );
		self::$plugin_url      = plugin_dir_url( self::$plugin_file );
		self::$plugin_basename = plugin_basename( self::$plugin_file );

		if ( $option_values = get_option( self::$option_name ) ) {
			if ( is_array( $option_values ) ) {
				foreach ( $option_values as $option_key => $option_value ) {
					self::$option_values[ $option_key ] = $option_value;
				}
			}
		}
		if ( ! isset( self::$option_values[ 'plugin-version' ] ) ) {
			self::$option_values[ 'plugin-version' ] = '';
		}
		if ( self::compare_versions( self::$option_values[ 'plugin-version' ], self::$plugin_version ) > 2 ) {
			self::update_option( 'plugin-version', self::$plugin_version );
			self::update_option( 'notice-hide-general-welcome', null );
			self::update_option( 'notice-first-time-general-welcome', null );
			self::update_option( 'notice-impression-count-general-welcome', null );
			self::update_option( 'notice-hide-make-donations', null );
			self::update_option( 'notice-first-time-make-donations', null );
			self::update_option( 'notice-impression-count-make-donations', null );
		}
		update_option( self::$option_name, self::$option_values );

		register_activation_hook( self::$plugin_file, __CLASS__ . '::plugin_activate' );
		register_deactivation_hook( self::$plugin_file, __CLASS__ . '::plugin_deactivate' );
		add_action( 'init', __CLASS__ . '::register_post' );
		add_action( 'widgets_init', __CLASS__ . '::register_widget' );
		add_action( 'wp_head', __CLASS__ . '::do_wp_head' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::enqueue_scripts' );
		add_action( 'admin_menu', __CLASS__ . '::admin_menu' );
		add_filter( 'plugin_action_links_' . self::$plugin_basename, __CLASS__ . '::add_action_links' );
		add_action( 'admin_notices', __CLASS__ . '::display_general_welcome' );
		add_filter( 'pre_get_posts', __CLASS__ . '::reorder_list' );
		add_filter( 'manage_' . self::$post_type_slug . '_posts_columns', __CLASS__ . '::set_column_titles' );
		add_action( 'manage_' . self::$post_type_slug . '_posts_custom_column', __CLASS__ . '::set_columns', 10, 2 );
		add_action( 'contextual_help', __CLASS__ . '::contextual_help', 10, 3 );
		add_filter( 'enter_title_here', __CLASS__ . '::change_title_text' );
		add_filter( 'mce_external_plugins', __CLASS__ . '::mce_plugin' );
		add_filter( 'mce_buttons', __CLASS__ . '::mce_button' );
		add_action( 'wp_ajax_ls_cb_mce_popup_form', __CLASS__ . '::mce_popup_form' );
		add_action( 'admin_footer', __CLASS__ . '::mce_popup_form' );
		add_shortcode( self::$post_type_shortcode, __CLASS__ . '::content_block_shortcode' );
		add_filter( 'the_content', __CLASS__ . '::do_the_content' );
		add_action( 'wp_ajax_ls_cb_hide_notice', __CLASS__ . '::hide_notice' );

		return true;
	}

	static public function plugin_activate() {

		self::update_option( 'notice-hide-general-welcome', null );
		self::update_option( 'notice-first-time-general-welcome', null );
		self::update_option( 'notice-impression-count-general-welcome', null );
		self::update_option( 'notice-hide-make-donations', null );
		self::update_option( 'notice-first-time-make-donations', null );
		self::update_option( 'notice-impression-count-make-donations', null );

		flush_rewrite_rules();

	}

	static public function plugin_deactivate() {

		flush_rewrite_rules();

	}

	static public function register_post() {

		$user               = wp_get_current_user();
		$allowed_roles      = array(
			'editor',
			'administrator',
			'author'
		);
		$publicly_queryable = array_intersect( $allowed_roles, $user->roles );

		register_post_type( self::$post_type_slug, array(
			'label'                => __( self::$post_type_labels, 'loomisoft-content-blocks-text-domain' ),
			'labels'               => array(
				'name'               => __( self::$post_type_labels, 'loomisoft-content-blocks-text-domain' ),
				'singular_name'      => __( self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'menu_name'          => __( self::$post_type_labels, 'loomisoft-content-blocks-text-domain' ),
				'name_admin_bar'     => __( self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'all_items'          => __( 'All ' . self::$post_type_labels, 'loomisoft-content-blocks-text-domain' ),
				'add_new'            => __( 'Add New ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'add_new_item'       => __( 'Add New ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'edit_item'          => __( 'Edit ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'new_item'           => __( 'New ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'view_item'          => __( 'View ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' ),
				'search_items'       => __( 'Search ' . self::$post_type_labels, 'loomisoft-content-blocks-text-domain' ),
				'not_found'          => __( 'No ' . strtolower( self::$post_type_labels ) . ' found', 'loomisoft-content-blocks-text-domain' ),
				'not_found_in_trash' => __( 'No ' . strtolower( self::$post_type_labels ) . ' found in Trash', 'loomisoft-content-blocks-text-domain' ),
				'parent_item_colon'  => __( 'Parent ' . self::$post_type_label, 'loomisoft-content-blocks-text-domain' )
			),
			'public'               => $publicly_queryable,
			'exclude_from_search'  => true,
			'publicly_queryable'   => $publicly_queryable,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'show_in_menu'         => true,
			'show_in_admin_bar'    => true,
			'menu_icon'            => self::$plugin_url . self::$post_type_menu_icon,
			'hierarchical'         => false,
			'supports'             => array(
				'title',
				'editor'
			),
			'register_meta_box_cb' => __CLASS__ . '::meta_box',
			'has_archive'          => false
		) );

		flush_rewrite_rules();

		$args = array(
			'post_type'   => self::$post_type_slug,
			'post_status' => 'any',
			'nopaging'    => true
		);

		$content_blocks = get_posts( $args );

		foreach ( $content_blocks as $content_block ) {
			if ( trim( $content_block->post_title ) == '' ) {
				$new_content_block_values = array(
					'ID'         => $content_block->ID,
					'post_title' => __( 'Content Block', 'loomisoft-content-blocks-text-domain' ) . ' ' . $content_block->ID
				);

				wp_update_post( $new_content_block_values );
			}
		}

		$args = array(
			'post_type'   => self::$post_type_slug,
			'post_status' => 'publish',
			'nopaging'    => true
		);

		$content_blocks = get_posts( $args );

		self::$content_block_list = array();

		foreach ( $content_blocks as $content_block ) {
			self::$content_block_list[ $content_block->ID ]                = $content_block->post_title;
			self::$content_block_slug_list[ $content_block->ID ]           = $content_block->post_name;
			self::$content_block_list_by_slug[ $content_block->post_name ] = $content_block->ID;
		}
	}

	static public function register_widget() {

		register_widget( 'ls_cb_widget' );

	}

	static public function do_wp_head() {

		self::$page_title = get_the_title();

	}

	static public function enqueue_scripts() {

		wp_enqueue_script( 'jquery-ui-dialog', array(
			'jquery',
			'jquery-ui-core'
		) );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_script( 'ls_cb_admin_script', self::$plugin_url . 'js/admin.js', array(), self::$plugin_version );
		wp_enqueue_style( 'ls_cb_admin_style', self::$plugin_url . 'css/admin.css', array(), self::$plugin_version );
		$custom_css = '#adminmenu #menu-posts-' . self::$post_type_slug . ' div.wp-menu-image, #adminmenu #menu-posts-' . self::$post_type_slug . ' div.wp-menu-image img { opacity: 1 !important; }';
		wp_add_inline_style( 'ls_cb_admin_style', $custom_css );

	}

	static public function admin_menu() {

		add_submenu_page( 'edit.php?post_type=' . self::$post_type_slug, __( 'Loomisoft Content Blocks - Usage & About', 'loomisoft-content-blocks-text-domain' ), __( 'Usage & About', 'loomisoft-content-blocks-text-domain' ), 'edit_posts', self::$usage_about_page, __CLASS__ . '::usage_about_page' );

	}

	static public function add_action_links( $links ) {

		$mylinks = array(
			'<a href="' . admin_url( 'edit.php?post_type=' . self::$post_type_slug . '&page=' . self::$usage_about_page ) . '">' . __( 'Usage & About', 'loomisoft-content-blocks-text-domain' ) . '</a>',
		);

		return array_merge( $links, $mylinks );
	}

	static public function reorder_list( $query ) {

		if ( $query->is_admin ) {
			if ( $query->get( 'post_type' ) == self::$post_type_slug ) {
				$query->set( 'orderby', 'post_title' );
				$query->set( 'order', 'ASC' );
			}
		}

		return $query;
	}

	static public function set_column_titles() {

		return array(
			'cb'                                => '<input type="checkbox"/>',
			'title'                             => __( 'Title', 'loomisoft-content-blocks-text-domain' ),
			self::$post_type_slug . 'shortcode' => __( 'Basic Shortcode', 'loomisoft-content-blocks-text-domain' ),
			'date'                              => __( 'Date', 'loomisoft-content-blocks-text-domain' )
		);

	}

	static public function set_columns( $column, $post_id ) {

		switch ( $column ) {
			case self::$post_type_slug . 'shortcode' :

				echo '<input type="text" class="ls_cb_read_only_input" value="' . esc_attr( '[' . self::$post_type_shortcode . ' id="' . $post_id . '"]' ) . '" style="width: 270px !important;"/><br />(' . __( 'Edit post for full list', 'loomisoft-content-blocks-text-domain' ) . ')';
				break;

			default :
				break;
		}

	}

	static public function contextual_help( $contextual_help, $screen_id, $screen ) {
		$current_screen = get_current_screen();

		if ( ( $current_screen->post_type == self::$post_type_slug ) && ( $current_screen->id != self::$post_type_slug . '_page_' . self::$usage_about_page ) ) {
			$screen->remove_help_tabs();

			$content_prefix  = '<div class="ls-cb-admin-info-page-column-content"><div id="ls-cb-admin-branding-right"><a href="http://www.loomisoft.com/" target="_blank"><img src="' . esc_attr( self::$plugin_url ) . 'images/ls-wp-admin-branding.png" /></a></div>';
			$content_postfix = '</div>';

			$help_screens = array(
				'overview' => array(
					'title'   => __( 'Overview', 'loomisoft-content-blocks-text-domain' ),
					'heading' => __( self::$plugin_name, 'loomisoft-content-blocks-text-domain' ) . ' ' . __( 'Version', 'loomisoft-content-blocks-text-domain' ) . ' ' . __( self::$plugin_version, 'loomisoft-content-blocks-text-domain' ),
					'text'    => array(
						__( 'Loomisoft’s Reusable Content & Text Blocks plugin allows you to define modular and repeated blocks of text and other content and place them within pages, posts, sidebars, widgetised areas or anywhere on your site via shortcodes, via the provided widget or via PHP.', 'loomisoft-content-blocks-text-domain' ),
						__( 'The idea behind this plugin is two-fold. The first is to modularise repeated content so you can use the same content in multiple pages, posts and other places, which will allow you to change the relevant content in just one place rather than in dozens of pages and posts. The second is to provide an easy way to add complex custom content within sidebars and widgets. Being compatible with WPBakery’s Page Builder (formerly known as Visual Composer), Avada’s Fusion Builder, Beaver Builder and SiteOrigin Page Builder means that embedded blocks can have a richer range of elements, layout and styling.', 'loomisoft-content-blocks-text-domain' ),
						__( 'For full usage information, click below.', 'loomisoft-content-blocks-text-domain' )
					),
					'more'    => array(
						'url'  => admin_url( 'edit.php?post_type=' . self::$post_type_slug . '&page=' . self::$usage_about_page ),
						'text' => __( 'Usage & About', 'loomisoft-content-blocks-text-domain' )
					)
				)
			);

			foreach ( $help_screens as $help_screen_id => $help_screen ) {
				$content = $content_prefix . '<h2>' . esc_html( $help_screen[ 'heading' ] ) . '</h2><p>' . str_replace( '##br##', '<br />', esc_html( implode( '##br####br##', $help_screen[ 'text' ] ) ) );
				if ( ( isset( $help_screen[ 'more' ] ) ) && ( is_array( $help_screen[ 'more' ] ) ) && ( isset( $help_screen[ 'more' ][ 'url' ] ) ) && ( isset( $help_screen[ 'more' ][ 'text' ] ) ) ) {
					$content .= '<br /><br /><a href="' . esc_attr( $help_screen[ 'more' ][ 'url' ] ) . '"';
					if ( ( isset( $help_screen[ 'more' ][ 'target' ] ) ) ) {
						$content .= ' target="' . esc_attr( $help_screen[ 'more' ][ 'target' ] ) . '"';
					}
					$content .= '>' . esc_html( $help_screen[ 'more' ][ 'text' ] ) . '</a>';
				}
				$content .= '</p>' . $content_postfix;

				$screen->add_help_tab( array(
					'id'      => self::$plugin_basename . '-tab-' . $help_screen_id,
					'title'   => $help_screen[ 'title' ],
					'content' => $content
				) );
			}
		}

		return $contextual_help;
	}

	static public function change_title_text( $title ) {
		global $post;

		switch ( $post->post_type ) {
			case '' . self::$post_type_slug:
				$title = __( 'Enter content block name here', 'loomisoft-content-blocks-text-domain' );
				break;
		}

		return $title;
	}

	static public function check_editor() {
		if ( ( current_user_can( 'edit_posts' ) ) || ( current_user_can( 'edit_pages' ) ) ) {
			if ( get_user_option( 'rich_editing' ) == 'true' ) {
				return true;
			}
		}

		return false;
	}

	static public function mce_plugin( $plugin_array ) {
		if ( self::check_editor() ) {
			$plugin_array[ 'ls_cb_button' ] = self::$plugin_url . 'js/editor.js';
		}

		return $plugin_array;
	}

	static public function mce_button( $buttons ) {
		if ( self::check_editor() ) {
			array_push( $buttons, 'ls_cb_button' );
		}

		return $buttons;
	}

	static public function mce_popup_form() {
		if ( self::check_editor() ) {
			echo '<form id="' . esc_attr( self::$option_prefix . 'shortcode_form' ) . '" name="' . esc_attr( self::$option_prefix . 'shortcode_form' ) . '" action="#" method="post">';
			echo '<div class="ls-cb-mce-form-container ls-cb-clearfix">';
			echo '<div id="ls-cb-mce-popup" class="hidden" style="max-width:800px">';
			echo '<div class="ls-cb-mce-field-container ls-cb-clearfix">';
			echo '<div class="ls-cb-mce-label"><label for="' . esc_attr( self::$option_prefix . 'cbid' ) . '">' . esc_html( __( 'Content Block:', 'loomisoft-content-blocks-text-domain' ) ) . '</label></div>';
			echo '<div class="ls-cb-mce-field ls-cb-clearfix"><select id="' . esc_attr( self::$option_prefix . 'cbid' ) . '" name="' . esc_attr( self::$option_prefix . 'cbid' ) . '" class="ls-cb-select-input" message="' . esc_attr( __( 'Content block must be selected', 'loomisoft-content-blocks-text-domain' ) ) . '">';
			echo '<option value="" selected="selected">' . esc_html( __( 'Select Content Block', 'loomisoft-content-blocks-text-domain' ) ) . '</option>';
			foreach ( self::$content_block_list as $content_block_id => $content_block_title ) {
				echo '<option value="' . esc_attr( $content_block_id ) . '" slug="' . esc_attr( self::$content_block_slug_list[ $content_block_id ] ) . '">' . esc_html( $content_block_title ) . '</option>';
			}
			echo '</select></div>';
			echo '</div>';
			echo '<div class="ls-cb-mce-field-container ls-cb-clearfix">';
			echo '<div class="ls-cb-mce-label"><label for="' . esc_attr( self::$option_prefix . 'para' ) . '">' . esc_html( __( 'Content Filtering / Paragraph Tags:', 'loomisoft-content-blocks-text-domain' ) ) . '</label></div>';
			echo '<div class="ls-cb-mce-field ls-cb-clearfix"><select id="' . esc_attr( self::$option_prefix . 'para' ) . '" name="' . esc_attr( self::$option_prefix . 'para' ) . '" class="ls-cb-select-input">';
			foreach ( self::$para_list as $para_key => $para_value ) {
				if ( $para_key == 'none' ) {
					$para_key = '';
				}
				echo '<option value="' . esc_attr( $para_key ) . '"' . ( ( $para_key == '' ) ? ' selected="selected"' : '' ) . '>' . esc_html( __( $para_value, 'loomisoft-content-blocks-text-domain' ) ) . '</option>';
			}
			echo '</select></div>';
			echo '</div>';
			echo '<div class="ls-cb-mce-field-container ls-cb-clearfix">';
			echo '<div class="ls-cb-mce-field ls-cb-clearfix">';
			echo '<div class="ls-cb-mce-field-radio"><input type="radio" id="' . esc_attr( self::$option_prefix . 'use-1' ) . '" name="' . esc_attr( self::$option_prefix . 'use' ) . '" value="id" class="ls-cb-radio-input ' . esc_attr( self::$option_prefix . 'use' ) . '" checked="checked" /></div>';
			echo '<div class="ls-cb-mce-field-radio-label"><label for="' . esc_attr( self::$option_prefix . 'use-0' ) . '">' . esc_html( __( 'By ID', 'loomisoft-content-blocks-text-domain' ) ) . '</label></div>';
			echo '<div class="ls-cb-mce-field-radio"><input type="radio" id="' . esc_attr( self::$option_prefix . 'use-2' ) . '" name="' . esc_attr( self::$option_prefix . 'use' ) . '" value="slug" class="ls-cb-radio-input ' . esc_attr( self::$option_prefix . 'use' ) . '" /></div>';
			echo '<div class="ls-cb-mce-field-radio-label"><label for="' . esc_attr( self::$option_prefix . 'use-1' ) . '">' . esc_html( __( 'By Slug', 'loomisoft-content-blocks-text-domain' ) ) . '</label></div>';
			echo '</div>';
			echo '</div>';
			echo '<div class="ls-cb-mce-field-container ls-cb-clearfix">';
			echo '<div class="ls-cb-mce-field ls-cb-clearfix"><a href="#" id="' . esc_attr( self::$option_prefix . 'insert' ) . '" class="ls-cb-mce-button-blue">' . esc_html( __( 'Insert', 'loomisoft-content-blocks-text-domain' ) ) . '</a></div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</form>';
		}
	}

	static public function meta_box( $post ) {
		add_meta_box( $post->id, __( 'Content Block Usage Codes', 'loomisoft-content-blocks-text-domain' ), __CLASS__ . '::meta_box_screen', self::$post_type_slug, 'normal', 'core' );
	}

	static public function is_edit_page( $new_edit = null ) {
		global $pagenow;
		if ( ! is_admin() ) {
			return false;
		}
		if ( $new_edit == "edit" ) {
			return in_array( $pagenow, array( 'post.php', ) );
		} elseif ( $new_edit == "new" ) {
			return in_array( $pagenow, array( 'post-new.php' ) );
		} else {
			return in_array( $pagenow, array(
				'post.php',
				'post-new.php'
			) );
		}
	}

	static public function meta_box_screen( $post ) {

		$code = array();

		if ( self::is_edit_page( 'edit' ) ) {
			$post_id    = $post->ID;
			$post_slug  = $post->post_name;
			$code[ 0 ]  = '[' . self::$post_type_shortcode . ' id="' . $post_id . '"]';
			$code[ 1 ]  = '[' . self::$post_type_shortcode . ' id="' . $post_id . '" para="no-shortcodes"]';
			$code[ 2 ]  = '[' . self::$post_type_shortcode . ' id="' . $post_id . '" para="paragraphs"]';
			$code[ 3 ]  = '[' . self::$post_type_shortcode . ' id="' . $post_id . '" para="paragraphs-no-shortcodes"]';
			$code[ 4 ]  = '[' . self::$post_type_shortcode . ' id="' . $post_id . '" para="full"]';
			$code[ 5 ]  = '[' . self::$post_type_shortcode . ' slug="' . $post_slug . '"]';
			$code[ 6 ]  = '[' . self::$post_type_shortcode . ' slug="' . $post_slug . '" para="no-shortcodes"]';
			$code[ 7 ]  = '[' . self::$post_type_shortcode . ' slug="' . $post_slug . '" para="paragraphs"]';
			$code[ 8 ]  = '[' . self::$post_type_shortcode . ' slug="' . $post_slug . '" para="paragraphs-no-shortcodes"]';
			$code[ 9 ]  = '[' . self::$post_type_shortcode . ' slug="' . $post_slug . '" para="full"]';
			$code[ 10 ] = '<?php echo ls_content_block_by_id( ' . $post_id . ' ); ?>';
			$code[ 11 ] = '<?php echo ls_content_block_by_id( ' . $post_id . ', \'no-shortcodes\' ); ?>';
			$code[ 12 ] = '<?php echo ls_content_block_by_id( ' . $post_id . ', \'paragraphs\' ); ?>';
			$code[ 13 ] = '<?php echo ls_content_block_by_id( ' . $post_id . ', \'paragraphs-no-shortcodes\' ); ?>';
			$code[ 14 ] = '<?php echo ls_content_block_by_id( ' . $post_id . ', \'full\' ); ?>';
			$code[ 15 ] = '<?php echo ls_content_block_by_slug( \'' . $post_slug . '\' ); ?>';
			$code[ 16 ] = '<?php echo ls_content_block_by_slug( \'' . $post_slug . '\', \'no-shortcodes\' ); ?>';
			$code[ 17 ] = '<?php echo ls_content_block_by_slug( \'' . $post_slug . '\', \'paragraphs\' ); ?>';
			$code[ 18 ] = '<?php echo ls_content_block_by_slug( \'' . $post_slug . '\', \'paragraphs-no-shortcodes\' ); ?>';
			$code[ 19 ] = '<?php echo ls_content_block_by_slug( \'' . $post_slug . '\', \'full\' ); ?>';
		} else {
			for ( $i = 0; $i <= 15; $i ++ ) {
				$code[ $i ] = __( 'New post', 'loomisoft-content-blocks-text-domain' );
			}
		}

		$access            = array(
			__( 'Shortcode', 'loomisoft-content-blocks-text-domain' ),
			__( 'PHP', 'loomisoft-content-blocks-text-domain' )
		);
		$methods           = array(
			__( 'ID', 'loomisoft-content-blocks-text-domain' ),
			__( 'Slug', 'loomisoft-content-blocks-text-domain' )
		);
		$para_descriptions = array(
			__( 'No Paragraph Tags / Run Shortcodes', 'loomisoft-content-blocks-text-domain' ),
			__( 'No Paragraph Tags / No Shortcodes', 'loomisoft-content-blocks-text-domain' ),
			__( 'Add Paragraph Tags / Run Shortcodes', 'loomisoft-content-blocks-text-domain' ),
			__( 'Add Paragraph Tags / No Shortcodes', 'loomisoft-content-blocks-text-domain' ),
			__( 'Full Content Filtering', 'loomisoft-content-blocks-text-domain' )
		);

		for ( $i = 0; $i <= 1; $i ++ ) {
			for ( $j = 0; $j <= 1; $j ++ ) {
				echo '<h1>' . esc_html( __( 'Via', 'loomisoft-content-blocks-text-domain' ) ) . ' ' . esc_html( $access[ $i ] ) . ' / ' . esc_html( __( 'By', 'loomisoft-content-blocks-text-domain' ) ) . ' ' . esc_html( $methods[ $j ] ) . '</h1>';
				echo '<div class="ls_cb_meta">';
				for ( $k = 0; $k <= 4; $k ++ ) {
					$index = ( $i * 10 ) + ( $j * 5 ) + $k;
					echo '<div class="ls_cb_meta_label"><label for="' . esc_attr( self::$option_prefix . 'access_' . $index ) . '">' . $para_descriptions[ $k ] . '</label></div><div class="ls_cb_meta_field"><input type="text" class="ls_cb_read_only_input" id="' . esc_attr( self::$option_prefix . 'access_' . $index ) . '" name="' . esc_attr( self::$option_prefix . 'access_' . $index ) . '" value="' . esc_attr( $code[ $index ] ) . '" /></div>';
				}
				echo '</div>';
			}
		}
	}

	static public function usage_about_page() {
		echo '<div class="wrap"><h1>' . esc_html( __( 'Usage & About', 'loomisoft-content-blocks-text-domain' ) ) . ' - ' . esc_html( __( self::$plugin_name, 'loomisoft-content-blocks-text-domain' ) ) . ' ' . esc_html( __( 'Version', 'loomisoft-content-blocks-text-domain' ) ) . ' ' . esc_html( __( self::$plugin_version, 'loomisoft-content-blocks-text-domain' ) ) . '</h1>';
		echo '<div class="ls-cb-admin-info-page-row ls-cb-clearfix">';
		echo '<div class="ls-cb-admin-info-page-column-half">';
		echo '<div class="ls-cb-admin-info-page-column-inner">';
		echo '<div class="postbox">';
		echo '<div class="inside">';
		echo '<div class="ls-bw-admin-info-page-column-content">';
		echo '<p>Loomisoft&rsquo;s Reusable Content &amp; Text Blocks plugin allows you to define modular and repeated blocks of text and other content and place them within pages, posts, sidebars, widgetised areas or anywhere on your site via shortcodes, via the provided widget or via PHP.</p>';
		echo '<p>The idea behind this plugin is two-fold. The first is to modularise repeated content so you can use the same content in multiple pages, posts and other places, which will allow you to change the relevant content in just one place rather than in dozens of pages and posts. The second is to provide an easy way to add complex custom content within sidebars and widgets. Being compatible with WPBakery&rsquo;s Page Builder (formerly known as Visual Composer), Avada&rsquo;s Fusion Builder, Beaver Builder and SiteOrigin Page Builder means that embedded blocks can have a richer range of elements, layout and styling.</p>';
		echo '<p><strong>Note:</strong> The documentation on this page is also available with screenshots for guidance on the <a href="http://www.loomisoft.com/docs/reusable-content-text-blocks-wordpress-plugin/" target="_blank">Loomisoft website</a>.</p>';
		echo '<h2>Managing Content/Text Blocks</h2>';
		echo '<h3>Adding/Editing/Deleting Content Blocks</h3>';
		echo '<p>Content or text blocks can be added and managed via the custom &ldquo;Content Blocks&rdquo; post type in the same way that the normal WordPress posts and pages are ... you can add new content blocks, or edit or bin existing blocks in exactly the same way.</p>';
		echo '<h3>Content Block Contents</h3>';
		echo '<p>Each content block has three main elements:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>Title</strong>: This is more for your reference and more for labelling purposes</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>Content</strong>: The actual content of each block can be managed in a similar way to normal pages and posts. You can use the normal WordPress TinyMCE editor or one of the page builders listed below.</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>Visibility</strong>: For a content block to be displayed, it must be set to &ldquo;Published&rdquo;. This gives great control over whether a block is displayed. Content blocks may even be set for publication at a future date and time.</li>';
		echo '</ul>';
		echo '<p>Once you are happy with the block, just save it.</p>';
		echo '<p>If you are amending an existing content block, the relevant changes will take effect immediately wherever the block is used. However, note that if you are using any cache plugins, you might need to purge caches before changes are reflected on the site.</p>';
		echo '<h3>Page Builders</h3>';
		echo '<p>Not only can you use the generic TinyMCE editor for putting content in a block, you can also use page builders such as WPBakery&rsquo;s Page Builder (formerly known as Visual Composer), Avada&rsquo;s Fusion Builder, Beaver Builder, SiteOrigin Page Builder. Other page builders may, in principle, also be compatible.</p>';
		echo '<p>In order to do so, for the specific page builder, enable the &ldquo;lscontentblock&rdquo; or &ldquo;Content Blocks&rdquo; post type within its settings. Also, when your content block contains content from a page builder, you must call (embed) the block with &ldquo;full content filtering&rdquo; or the PARA parameter set to &ldquo;full&rdquo;. <a href="#ls-cb-section-para">See section below</a> for full information regarding content processing and the PARA parameter.</p>';
		echo '<p>As at the time of the publication of this documentation, the plugin has been tested with:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>WPBakery&rsquo;s Page Builder version 5.4.2</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Avada&rsquo;s Fusion Builder version 1.2.2 (with Avada version 5.2.2)</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Beaver Builder version 1.10.9.2</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>SiteOrigin Page Builder version 2.5.14</li>';
		echo '</ul>';
		echo '<h2>Placing Content Blocks Using Shortcodes</h2>';
		echo '<p>The simplest way to embed content blocks within posts and pages is by using shortcodes. In fact, shortcodes can be used outside of the main content area so long as the theme you use performs shortcode processing in those areas.</p>';
		echo '<p>The basic shortcode syntax is as follows, making reference to your content block either using its ID or slug, using the following format:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block id="<span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span>"]</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block slug="<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>"]</pre>';
		echo '<p>You can also use the &ldquo;para&rdquo; parameter, which gives you greater control over how the content is filtered. For example:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block id="<span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span>" para="<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>"]</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block slug="<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>" para="<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>"]</pre>';
		echo '<p><a href="#ls-cb-section-para">See section below</a> for full information regarding content processing and the PARA parameter.</p>';
		echo '<p>The basic shortcode for each content block can be found on the main Content Blocks page. A more extensive list of the shortcodes can be found on the edit page for the specific block.</p>';
		echo '<h3>Shortcode Generator</h3>';
		echo '<p>The plugin provides a shortcode generator dialog box available from the WordPress TinyMCE editor window. To use it, place the cursor where you want the code to be inserted within the editor and click the Loomisoft logo.</p>';
		echo '<p>The generator dialogue box allows you to select:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>The required content block from those available</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>The desired content processing (<a href="#ls-cb-section-para">See section below</a> for full information regarding content processing and the PARA parameter)</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Whether the ID or the slug should be used</li>';
		echo '</ul>';
		echo '<p>Once you click &ldquo;Insert&rdquo;, the correct shortcode is inserted into the editor.</p>';
		echo '<h2>Placing Content Blocks Using Widgets</h2>';
		echo '<p>For within sidebars and widgetised areas, the plugin provides a widget that lets you place content blocks. Available from your WordPress site&rsquo;s widget management page under menu item Appearance &gt; Widgets, the widget can be dragged and dropped in the normal way.</p>';
		echo '<p>Once inserted into the correct area, the widget allows you to select:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>The required content block from those available</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>The desired content processing (<a href="#ls-cb-section-para">See section below</a> for full information regarding content processing and the PARA parameter)</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Whether the ID or the slug should be used</li>';
		echo '</ul>';
		echo '<p>Once you have made the relevant selections, click &ldquo;Save&rdquo;.</p>';
		echo '<h2>Placing Content Blocks Using PHP</h2>';
		echo '<p>If you are a web developer (or comfortable using PHP) and there are inaccessible areas of the theme where you want to enable content that you or your clients can amend as and when they need, you can use the provided PHP functions to embed content blocks.</p>';
		echo '<p>The basic function syntax is as follows, making reference to your content block either using the its ID or slug:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_id( <span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span> ); ?&gt;</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_slug( \'<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>\' ); ?&gt;</pre>';
		echo '<p>You can also use the &ldquo;para&rdquo; parameter, which gives you greater control over how the content is filtered. For example:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_id( <span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span>, \'<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>\' ); ?&gt;</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_slug( \'<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>\', \'<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>\' ); ?&gt;</pre>';
		echo '<p><a href="#ls-cb-section-para">See section below</a> for full information regarding content processing and the PARA parameter.</p>';
		echo '<p id="ls-cb-section-para">An extensive list of the PHP code snippets for each content block can be found on the edit page for the specific block.</p>';
		echo '<h2>Content Processing &amp; PARA Parameter</h2>';
		echo '<p>WordPress does a few things before the content from a page or post gets displayed on your site. For instance, it processes HTML paragraph (p) tags, it runs shortcodes and even sends the content to the theme and any plugins so they can do their thing to the content and include their bits.</p>';
		echo '<p>The plugin gives you versatile control over these processes when a block is called from within a page or post ... you can:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Include or suppress paragraph tags inside the content block when, for instance, you want the text in the content block to show up as part of an existing line in your page/post</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Run or suppress shortcodes inside the content block</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Or do full content processing, which is essential for the correct operation of the page builders</li>';
		echo '</ul>';
		echo '<p>Within shortcodes or PHP calls, you can do this through the PARA parameter, possible values for which are:</p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>None specified (default)</strong> – Do not insert paragraph tags, but run shortcodes within the content block. Please note that when shortcodes within a content block are run, they themselves may have paragraph tags within them</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>&ldquo;no-shortcodes&rdquo;</strong> – Do not insert paragraph tags and do not run shortcodes. Please note that mark-ups set in the WordPress WYSIWYG editor (e.g. bold, underline, colours, headings, etc.) will still be shown</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>&ldquo;paragraphs&rdquo;</strong> – Insert paragraph tags and run shortcodes</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>&ldquo;paragraphs-no-shortcodes&rdquo;</strong> – Insert paragraph tags, but do not run shortcodes</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span><strong>&ldquo;full&rdquo;</strong> – Perform full WordPress content filtering</li>';
		echo '</ul>';
		echo '<p><strong>Notes:</strong></p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>While full content filtering is necessary to allow compatibility with visual page builders, other plugins (e.g. author information blocks, social sharing buttons, etc.) that are designed to insert content into posts and pages during this content filtering process will also kick into effect. The result is that these insertions end up being repeated more than once on a page or post with content blocks in them. If full content filtering is absolutely essential, but you do not wish the third party plugin to insert additional content, you may be able to suppress this from within that plugin&rsquo;s settings. For example, StarBox, which inserts author information into posts and pages, has the option to &ldquo;Hide Author Box from custom posts types&rdquo;</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>For backward compatibility the plugin interprets the old para=&rdquo;yes&rdquo; option (or true in PHP) as para=&rdquo;full&rdquo;</li>';
		echo '</ul>';
		echo '<h2>Custom Variables</h2>';
		echo '<p>The plugin allows you to define custom variables, which can then be displayed within content blocks.</p>';
		echo '<h3>Setting Variables</h3>';
		echo '<p>To set a variable, you need to include it in the calling shortcode (e.g. within your page or post) or in the calling PHP code.</p>';
		echo '<p>The basic syntaxes in shortcodes are:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block id="<span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span>" para="<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>" <span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 NAME&gt;</span>="<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 VALUE&gt;</span>" <span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 NAME&gt;</span>="<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 VALUE&gt;</span>"]</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block slug="<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>" para="<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>" <span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 NAME&gt;</span>="<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 VALUE&gt;</span>" <span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 NAME&gt;</span>="<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 VALUE&gt;</span>"]</pre>';
		echo '<p>With variables passed as an associative array, the basic syntaxes in PHP are:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_id( <span style="background-color: #ffff00; font-style: italic;">&lt;ID&gt;</span>, \'<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>\', array( \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 NAME&gt;</span>\' =&gt; \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 VALUE&gt;</span>\', \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 NAME&gt;</span>\' =&gt; \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 VALUE&gt;</span>\' ) ); ?&gt;</pre>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">&lt;? echo ls_content_block_by_slug( \'<span style="background-color: #ffff00; font-style: italic;">&lt;SLUG&gt;</span>\', \'<span style="background-color: #ffff00; font-style: italic;">&lt;PARA&gt;</span>\', array( \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 NAME&gt;</span>\' =&gt; \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 1 VALUE&gt;</span>\', \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 NAME&gt;</span>\' =&gt; \'<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE 2 VALUE&gt;</span>\' ) ); ?&gt;</pre>';
		echo '<p><strong>Notes:</strong></p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Variable names must be lowercase alphanumeric (a-z, 0-9) and must begin with &ldquo;var&rdquo; (e.g. &ldquo;varpercentage&rdquo;, &ldquo;varcountry20&rdquo;, etc.).</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>In these examples, we have shown 2 variables, but you may use one or more.</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>Variable values may not contain an inverted comma (&ldquo;) as WordPress will confuse this with the variable value delimiter.</li>';
		echo '</ul>';
		echo '<h3>Displaying Variables</h3>';
		echo '<p>Once defined, a content block (or one down a chain) can display the value of a variable within its content by using the shortcode:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block getvar="<span style="background-color: #ffff00; font-style: italic;">&lt;VARIABLE NAME&gt;</span>"]</pre>';
		echo '<p><strong>Notes:</strong></p>';
		echo '<ul>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>After being set, variable values persist until a page or post is fully rendered unless they are overwritten. As such, once set, they may be called at any point after – whether in a content block or the post itself.</li>';
		echo '<li style="padding-left: 30px; position: relative;"><span style="display: block; position: absolute; left: 10px;">•</span>To avoid variable values causing potential issues within the final HTML of the page, they are HTML escaped before being displayed.</li>';
		echo '</ul>';
		echo '<h2>Other Useful Shortcodes</h2>';
		echo '<p>The following useful shortcodes, which can be used within content blocks as well as other places such as pages/posts, are provided by the plugin:</p>';
		echo '<p><strong>Date &amp; Time:</strong></p>';
		echo '<p>The current date/time in standard PHP date format strings (See <a href="http://php.net/manual/en/function.date.php" target="_blank">http://php.net/manual/en/function.date.php</a>):</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block datetime="<span style="background-color: #ffff00; font-style: italic;">&lt;DATE/TIME FORMAT&gt;</span>"]</pre>';
		echo '<p>For example, the current date in dd/mm/yyyy format would be:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block datetime="d/m/Y"]</pre>';
		echo '<p><strong>Site Title:</strong></p>';
		echo '<p>The title of your WordPress site as set in WordPress administration area – Settings &gt; General:</p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block info="site-title"]</pre>';
		echo '<p><strong>Current Page/Post Title:</strong></p>';
		echo '<pre style="margin-left: 40px; white-space: pre-wrap;">[ls_content_block info="page-title"]</pre>';
		echo '<p>Note that this is the raw title of the page/post and not the same as the title within the HTML title tag, which can be changed by SEO plugins.</p>';
		echo '<h2>Content Block Chains and Circular References</h2>';
		echo '<p>As content blocks are themselves custom post types, they can contain shortcodes for embedding other content blocks. As such, the plugin allows you to have a chain of content blocks. For instance, you might have:</p>';
		echo '<p style="padding-left: 30px;"><strong>Post or Page</strong> contains <strong>Content Block A</strong>, which contains <strong>Content Block B</strong>, which contains <strong>Content Block C</strong>, etc.</p>';
		echo '<p>The plugin is designed to protect against circular references that would cause an infinite loop. For example, you might have:</p>';
		echo '<p style="padding-left: 30px;"><strong>Post or Page</strong> contains <strong>Content Block A</strong>, which contains <strong>Content Block B</strong>, which contains <strong>Content Block A</strong></p>';
		echo '<p>Or more complex scenarios:</p>';
		echo '<p style="padding-left: 30px;"><strong>Post or Page</strong> contains <strong>Content Block A</strong>, which contains <strong>Content Block B</strong>, which contains <strong>Content Block C</strong>, which contains <strong>Content Block A</strong></p>';
		echo '<p>In each case, the plugin is designed to drop the second loop.</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '<div class="ls-cb-admin-info-page-column-half">';
		echo '<div class="ls-cb-admin-info-page-column-inner">';
		echo '<div class="postbox">';
		echo '<div class="inside">';
		echo '<div class="ls-bw-admin-info-page-column-content">';
		echo '<div id="ls-bw-admin-branding"><a href="http://www.loomisoft.com/" target="_blank"><img src="' . esc_attr( self::$plugin_url ) . 'images/ls-wp-admin-branding.png"></a></div>';
		echo '<h2>Help &amp; More</h2>';
		echo '<p>We have designed our plugin to be intuitive and easy for someone with a fair amount of WordPress experience. We have also tried to make this documentation as extensive as it needs to be without confusing things.</p>';
		echo '<p>However, if you have any questions or need help, then help is at hand. Please feel free to contact us – either through the <a href="http://www.loomisoft.com/contact/" target="_blank">contact form</a> on our website or through the <a href="https://wordpress.org/support/plugin/loomisoft-content-blocks" target="_blank">WordPress.org support system</a>.</p>';
		echo '<h3>Bugs &amp; Clashes</h3>';
		echo '<p>We use our own plugins in-house so we generally know if there are any issues straight away, but it is our users who really put them through their paces and expose them to a wide variety of situations. So if you find bugs, or clashes with other plugins or your theme, then we <em><strong><span style="text-decoration: underline;">definitely</span></strong></em> want to hear from you so we can fix the issue.</p>';
		echo '<h3>Your Suggestions</h3>';
		echo '<p>If you have any suggestions for improvements, do let us know. We are always interested in making our plugins better through enhancements that fit in with the core purpose of the particular plugin.</p>';
		echo '<h3>Rate this Plugin</h3>';
		echo '<p>If you like this plugin ... or better still, if you <em><strong><span style="text-decoration: underline;">love</span></strong></em> it ... please take a moment to <a href="https://wordpress.org/support/plugin/loomisoft-content-blocks/reviews/" target="_blank">rate it on WordPress.org</a> and let the world know.</p>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	static public function do_the_content( $content ) {

		if ( get_post_type() == self::$post_type_slug ) {
			if ( class_exists( 'Vc_Base' ) ) {
				$vc = new Vc_Base;
				$vc->addFrontCss();
			}
		}

		if ( get_post_type() == self::$post_type_slug ) {
			if ( class_exists( 'SiteOrigin_Panels' ) ) {
				$renderer = SiteOrigin_Panels::renderer();
				$renderer->add_inline_css( get_the_ID(), $renderer->generate_css( get_the_ID() ) );
			}
		}

		return $content;
	}

	static public function get_content( $id, $para = false ) {
		global $wp_query;

		if ( in_array( $id, self::$circular_block_tracker ) ) {
			return '';
		}

		self::$circular_block_tracker[] = $id;

		$post_content = '';

		switch ( $para ) {

			case 'full':

				$args = array(
					'p'           => $id,
					'post_type'   => self::$post_type_slug,
					'post_status' => 'publish',
					'nopaging'    => true
				);

				if ( class_exists( 'FLBuilder' ) ) {
					ob_start();
					FLBuilder::render_query( $args );
					$post_content = ob_get_clean();
				} else {
					$the_query = new WP_Query( $args );

					$original_query = false;

					if ( class_exists( 'Vc_Base' ) && ( ! is_singular() ) ) {
						/*
						 * Not "ideal" to overwrite global WP variables but required as Visual Composer uses is_singular function
						 * to see whether to write styles which in turn depends on being in the main WP loop.
						 *
						 * This code only kicks in when the following conditions are all met:
						 *
						 * 1. When full content filtering is required
						 * 2. When Visual Composer is present
						 * 3. When is_singular is false, which happens when:
						 *    (a) We're not in the main loop (e.g. when the content block is called in the header/footer area).
						 *    (b) When we're not in a single post, attachment, page, custom post type post
						 */
						$original_query = $wp_query;
						$wp_query       = $the_query;
					}

					if ( $the_query->have_posts() ) {
						while ( $the_query->have_posts() ) {
							$the_query->the_post();

							$post_content = apply_filters( 'the_content', get_the_content() );

							break;
						}
					}

					if ( is_object( $original_query ) ) {
						if ( get_class( $original_query ) == 'WP_Query' ) {
							/*
							 * Reinstate original $wp_query ... see above
							 */
							$wp_query = $original_query;
						}
					}

					wp_reset_postdata();
				}

				break;

			default:

				$args       = array(
					'p'           => $id,
					'post_type'   => self::$post_type_slug,
					'post_status' => 'publish',
					'nopaging'    => true
				);
				$block_post = get_posts( $args );

				if ( count( $block_post ) > 0 ) {
					switch ( $para ) {

						case 'paragraphs':
							$post_content = do_shortcode( wpautop( $block_post[ 0 ]->post_content ) );
							break;

						case 'paragraphs-no-shortcodes':
							$post_content = wpautop( $block_post[ 0 ]->post_content );
							break;

						case 'no-shortcodes':
							$post_content = $block_post[ 0 ]->post_content;
							break;

						default:
							$post_content = do_shortcode( $block_post[ 0 ]->post_content );
							break;

					}
				}

				break;
		}

		foreach ( array_keys( self::$circular_block_tracker, $id ) as $key ) {
			unset( self::$circular_block_tracker[ $key ] );
		}

		return $post_content;
	}

	static public function get_block_by_id( $id = false, $para = false, $vars = array() ) {
		$html = '';

		$id   = self::get_clean_id( $id );
		$para = self::get_clean_para( $para );

		if ( is_array( $vars ) ) {
			foreach ( $vars as $key => $value ) {
				$key = trim( strtolower( $key ) );
				if ( ( substr( $key, 0, 3 ) == 'var' ) && ( strlen( $key ) > 3 ) ) {
					if ( preg_match( '/^[a-z0-9]+$/', $key ) === 1 ) {
						self::$var_values[ $key ] = str_replace( "\\\"", "\"", $value );
					}
				}
			}
		}

		if ( $id !== false ) {
			$html = self::get_content( $id, $para );
		}

		return $html;
	}

	static public function get_block_by_slug( $slug = false, $para = false, $vars = array() ) {
		$html = '';

		$slug = self::get_clean_slug( $slug );
		$para = self::get_clean_para( $para );

		if ( is_array( $vars ) ) {
			foreach ( $vars as $key => $value ) {
				$key = trim( strtolower( $key ) );
				if ( ( substr( $key, 0, 3 ) == 'var' ) && ( strlen( $key ) > 3 ) ) {
					if ( preg_match( '/^[a-z0-9]+$/', $key ) === 1 ) {
						self::$var_values[ $key ] = str_replace( "\\\"", "\"", $value );
					}
				}
			}
		}

		if ( $slug !== false ) {
			$html = self::get_content( self::$content_block_list_by_slug[ $slug ], $para );
		}

		return $html;
	}

	static public function get_clean_id( $id = false ) {
		if ( preg_match( '/^((0{1})|([1-9]{1})|([1-9]{1}[0-9]*))$/', trim( strval( $id ) ) ) === 1 ) {
			if ( isset( self::$content_block_list[ $id ] ) ) {
				return intval( $id );
			}
		}

		return false;
	}

	static public function get_clean_slug( $slug = '' ) {
		$slug = trim( strval( $slug ) );
		if ( $slug != '' ) {
			if ( isset( self::$content_block_list_by_slug[ $slug ] ) ) {
				return $slug;
			}
		}

		return false;
	}

	static public function get_clean_para( $para = '' ) {
		if ( $para === false ) {
			return '';
		} elseif ( $para === true ) {
			return 'full';
		} else {
			$para = strtolower( trim( strval( $para ) ) );
			if ( in_array( $para, array(
				'',
				'no-shortcodes',
				'yes',
				'full',
				'paragraphs',
				'paragraphs-no-shortcodes'
			) ) ) {
				if ( $para == 'yes' ) {
					$para = 'full';
				}

				return $para;
			}
		}

		return '';
	}

	static public function content_block_shortcode( $atts ) {

		$html = '';

		foreach ( $atts as $key => $value ) {
			$key = trim( strtolower( $key ) );
			if ( ( substr( $key, 0, 3 ) == 'var' ) && ( strlen( $key ) > 3 ) ) {
				if ( preg_match( '/^[a-z0-9]+$/', $key ) === 1 ) {
					self::$var_values[ $key ] = str_replace( "\\\"", "\"", $value );
				}
			}
		}

		$para = '';

		if ( isset( $atts[ 'para' ] ) ) {
			$para = $atts[ 'para' ];
		}

		if ( isset( $atts[ 'id' ] ) ) {
			$html = self::get_block_by_id( $atts[ 'id' ], $para );
		} elseif ( isset( $atts[ 'slug' ] ) ) {
			$html = self::get_block_by_slug( $atts[ 'slug' ], $para );
		} elseif ( isset( $atts[ 'getvar' ] ) ) {
			$var = trim( strtolower( $atts[ 'getvar' ] ) );
			if ( ( substr( $var, 0, 3 ) == 'var' ) && ( strlen( $var ) > 3 ) ) {
				if ( preg_match( '/^[a-z0-9-_]+$/', $var ) === 1 ) {
					if ( isset( self::$var_values[ $var ] ) ) {
						$html = esc_html( self::$var_values[ $var ] );
					}
				}
			}
		} elseif ( isset( $atts[ 'datetime' ] ) ) {
			if ( $datetime = date( $atts[ 'datetime' ] ) ) {
				$html = esc_html( $datetime );
			}
		} elseif ( isset( $atts[ 'info' ] ) ) {
			$info = strtolower( trim( strval( $atts[ 'info' ] ) ) );
			switch ( $info ) {
				case 'site-title':
					$html = esc_html( get_bloginfo( 'name' ) );
					break;
				case 'page-title':
					$html = esc_html( self::$page_title );
					break;
			}
		}

		return $html;
	}

	static public function get_version_digits( $version ) {
		$version_digits = array(
			0,
			0,
			0,
			0
		);
		if ( preg_match( '/^(([0-9]{1})|([1-9]{1}[0-9]*))(\.(([0-9]{1})|([1-9]{1}[0-9]*))(\.(([0-9]{1})|([1-9]{1}[0-9]*))(\.(([0-9]{1})|([1-9]{1}[0-9]*))){0,1}){0,1}){0,1}$/', $version, $matches ) === 1 ) {
			if ( ! is_array( $matches ) ) {
				$matches = array();
			}
			for ( $i = 0; $i < 4; $i ++ ) {
				if ( isset( $matches[ ( 4 * $i ) + 1 ] ) ) {
					$version_digits[ $i ] = intval( $matches[ ( 4 * $i ) + 1 ] );
				}
			}
		}

		return $version_digits;
	}

	static public function compare_versions( $old_version = '', $new_version = '' ) {
		$level              = 0;
		$old_version_digits = self::get_version_digits( $old_version );
		$new_version_digits = self::get_version_digits( $new_version );
		for ( $i = 0; $i < 4; $i ++ ) {
			if ( $new_version_digits[ $i ] > $old_version_digits[ $i ] ) {
				$level = 4 - $i;
				break;
			} elseif ( $new_version_digits[ $i ] < $old_version_digits[ $i ] ) {
				$level = - 1;
				break;
			}
		}

		return $level;
	}

	static public function get_option( $option_name ) {
		if ( isset( self::$option_values[ $option_name ] ) ) {
			return self::$option_values[ $option_name ];
		}

		return false;
	}

	static public function update_option( $option_key, $option_value ) {
		if ( is_null( $option_value ) ) {
			unset( self::$option_values[ $option_key ] );
		} else {
			self::$option_values[ $option_key ] = $option_value;
		}
		update_option( self::$option_name, self::$option_values );
	}

	static public function hide_notice() {
		if ( isset( $_POST[ 'ls-cb-notice-hide' ] ) ) {
			self::update_option( 'notice-hide-' . trim( $_POST[ 'ls-cb-notice-hide' ] ), true );
			echo 'ok';
		}

		wp_die();
	}

	static public function display_general_welcome() {
		$current_screen = get_current_screen();

		$first_time       = self::get_option( 'notice-first-time-general-welcome' );
		$impression_count = self::get_option( 'notice-impression-count-general-welcome' );

		if ( ( $first_time === false ) || ( preg_match( '/^((0{1})|([1-9]{1})|([1-9]{1}[0-9]*))$/', trim( strval( $first_time ) ) ) !== 1 ) || ( $impression_count === false ) || ( preg_match( '/^((0{1})|([1-9]{1})|([1-9]{1}[0-9]*))$/', trim( strval( $impression_count ) ) ) !== 1 ) ) {
			$first_time = time();
			self::update_option( 'notice-first-time-general-welcome', $first_time );
			$impression_count = 0;
			self::update_option( 'notice-impression-count-general-welcome', $impression_count );
		}

		if ( ( time() - $first_time < 604800 ) && ( $impression_count < 200 ) ) { // Stop displaying after 7 days or 200 times
			if ( ! self::get_option( 'notice-hide-general-welcome' ) ) {
				$heading = esc_html( __( 'Welcome to version ' . self::$plugin_version . ' of ' . self::$plugin_name, 'loomisoft-content-blocks-text-domain' ) );
				if ( $current_screen->id == self::$post_type_slug . '_page_' . self::$usage_about_page ) {
					$message = esc_html( __( 'If you are new to the plugin, full usage instructions can be found below ... ', 'loomisoft-content-blocks-text-domain' ) ) . '<em>' . esc_html( __( 'Enjoy!', 'loomisoft-content-blocks-text-domain' ) ) . '</em>';
				} else {
					$message = esc_html( __( 'If you are new to the plugin, full usage instructions can be found on the ', 'loomisoft-content-blocks-text-domain' ) ) . ' <a href="' . admin_url( 'edit.php?post_type=' . self::$post_type_slug . '&page=' . self::$usage_about_page ) . '">' . esc_html( __( 'Usage & About', 'loomisoft-content-blocks-text-domain' ) ) . '</a> ' . esc_html( __( 'page ... ', 'loomisoft-content-blocks-text-domain' ) ) . '<em>' . esc_html( __( 'Enjoy!', 'loomisoft-content-blocks-text-domain' ) ) . '</em>';
				}
				self::display_notice( 'general-welcome', $heading, $message, true, true );
				self::update_option( 'notice-impression-count-general-welcome', $impression_count + 1 );
			}
		}

		/*
		$first_time       = self::get_option( 'notice-first-time-make-donations' );
		$impression_count = self::get_option( 'notice-impression-count-make-donations' );

		if ( ( $first_time === false ) || ( preg_match( '/^((0{1})|([1-9]{1})|([1-9]{1}[0-9]*))$/', trim( strval( $first_time ) ) ) !== 1 ) || ( $impression_count === false ) || ( preg_match( '/^((0{1})|([1-9]{1})|([1-9]{1}[0-9]*))$/', trim( strval( $impression_count ) ) ) !== 1 ) ) {
			$first_time = time();
			self::update_option( 'notice-first-time-make-donations', $first_time );
			$impression_count = 0;
			self::update_option( 'notice-impression-count-make-donations', $impression_count );
		}

		if ( ( time() - $first_time < 604800 ) && ( $impression_count < 200 ) ) { // Stop displaying after 7 days or 200 times
			if ( ( $current_screen->post_type == self::$post_type_slug ) && ( ! self::get_option( 'notice-hide-make-donations' ) ) ) {
				$heading = esc_html( __( 'Make a donation', 'loomisoft-content-blocks-text-domain' ) );
				$message = esc_html( __( 'We develop, bugfix and support this plugin because we want to make it better and better and we always welcome any suggestions you offer. If it fits in with the core purpose of the plugin, we\'ll gladly consider it.', 'loomisoft-content-blocks-text-domain' ) ) . '<br /><br />' . esc_html( __( 'This plugin is provided free of charge, but we do welcome financial contributions ... ', 'loomisoft-content-blocks-text-domain' ) ) . '<a href="https://www.paypal.me/loomisoft" target="_blank">' . esc_html( __( 'Click here to make a donation', 'loomisoft-content-blocks-text-domain' ) ) . '</a>';
				self::display_notice( 'make-donations', $heading, $message, true, false );
				self::update_option( 'notice-impression-count-make-donations', $impression_count + 1 );
			}
		}
		*/
	}

	static public function display_notice( $notice_id, $heading, $message, $hideable, $show_branding = true ) {
		if ( ! self::get_option( 'notice-hide-' . $notice_id ) ) {
			echo '<div id="ls-cb-notice-' . esc_attr( $notice_id ) . '" class="ls-cb-notice notice is-dismissible"><div class="ls-cb-admin-info-page-column-content ls-cb-clearfix">';
			if ( $show_branding ) {
				echo '<div id="ls-cb-admin-branding-right"><a href="http://www.loomisoft.com/" target="_blank"><img src="' . esc_attr( self::$plugin_url ) . 'images/ls-wp-admin-branding.png" /></a></div>';
			}
			echo '<h2>' . $heading . '</h2><p>' . $message . '</p>';
			if ( $hideable ) {
				echo '<p class="ls-cb-notice-hide-container"><a id="ls-cb-notice-hide-' . esc_attr( $notice_id ) . '" class="ls-cb-notice-hide-link" href="#">' . esc_html( __( 'Hide this notice', 'loomisoft-content-blocks-text-domain' ) ) . '</a></p>';
			}
			echo '</div></div>';
		}
	}
}
