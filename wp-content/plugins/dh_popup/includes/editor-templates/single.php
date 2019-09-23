<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! defined( 'DH_POPUP_IS_FRONTEND_EDITOR' ) ) {
	define( 'DH_POPUP_IS_FRONTEND_EDITOR', true );
}
global $post;
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
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<?php wp_head(); ?>
	<style type="text/css">
		.vc_empty-placeholder{
			display:none;
		}
		.dh-popup__inner{
			 position: relative;
		}
		.fancybox-inner,
		.dh-popup {
		    overflow: visible;
		}
		#vc_no-content-helper{
			left: 0;
		    position: absolute;
		    top: 100%;
		    width: 100%;
		}
		.fancybox-overlay{
			display: block;
		}
		.vc_welcome-header,
		.vc_welcome-brand{
			display:none !important;
		}
		.dh-popup{
			display:block !important;
		}
	</style>
</head>
<body <?php body_class()?>>
<div id="dh_popup_editor_frontend-primary" class="dh_popup_preview-content">
	<div class="fancybox-wrap fancybox-desktop fancybox-type-inline dh-popup__wrap dh-popup__wrap--<?php echo $post->ID?> dh-popup__wrap--center dh_popup_responsive fancybox-opened">
		<div class="fancybox-skin">
			<div class="fancybox-outer">
				<div class="fancybox-inner">
					<?php echo DH_Popup_Frontend::render_popup($post)?>
				</div>
			</div>
			<a class="fancybox-item fancybox-close" title="Close" href="javascript:void(0);">
			<i class="fancybox-close__line-top"></i>
			<i class="fancybox-close__line-bottom"></i>
			</a>
		</div>
	</div>
</div>
<script type='text/javascript'>
 var dh_popup_editor_frontend = true;
</script>
<?php wp_footer();?>
<div class="fancybox-overlay fancybox-overlay-fixed" style="width: auto; height: auto; display: block;"></div>
</body>
</html>
