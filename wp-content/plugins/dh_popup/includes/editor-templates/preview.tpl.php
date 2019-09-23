<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! defined( 'VC_IS_TEMPLATE_PREVIEW' ) ) {
	define( 'VC_IS_TEMPLATE_PREVIEW', true );
}
DH_Popup_Frontend::register_assets();

?>
<!DOCTYPE html>
<!--[if IE 7]>
<html class="ie ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html class="ie ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<meta name="viewport" content="width=device-width"/>
	<title><?php echo esc_html($post->post_title) ?></title>
	<?php wp_head(); ?>
	<style type="text/css">
		.dh-popup__wrap,
		.fancybox-inner{
			width:auto !important;
		}
		<?php echo visual_composer()->parseShortcodesCustomCss( $post->post_content ) ?>
		<?php echo $post->custom_css?>
	</style>
</head>
<body <?php body_class()?>>
<div id="dh_popup_preview-primary" class="dh_popup_preview-content">
	<?php echo DH_Popup_Frontend::render_popup($post)?>
</div>
<script type='text/javascript'>
 var dh_popup_preview = true;
</script>
<?php wp_footer();?>
</body>
</html>
