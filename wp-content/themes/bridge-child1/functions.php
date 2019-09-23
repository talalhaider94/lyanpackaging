<?php

// enqueue the child theme stylesheet
add_action('wp_enqueue_scripts', 'childThemeEnqueue', 20);
function childThemeEnqueue() {
	
	// enqueue style
	wp_enqueue_style('themeAdditional', get_stylesheet_directory_uri().'/assets/css/themeAdditional.css');
	
	// enqueue script
    wp_enqueue_script('Bioep', get_stylesheet_directory_uri() . '/assets/js/bioep.min.js', array( 'jquery' )); 	
	wp_enqueue_script('themeAdditionalScript', get_stylesheet_directory_uri() .'/assets/js/themeAdditionalScripts.js', array('jquery'), '20180722', true);
	
}

// add the bulk discounts table to the product page
add_action( 'woocommerce_single_product_summary', 'productBulkDiscounts', 25);
function productBulkDiscounts(){

    return get_template_part('woocommerce/single-product/product-bulk-discounts');

}

if( function_exists('acf_add_options_page') ) {
 
	$option_page = acf_add_options_page(array(
		'page_title' 	=> 'Popup Settings',
		'menu_title' 	=> 'Popup settings',
		'menu_slug' 	=> 'theme-popup-settings',
		'capability' 	=> 'edit_posts',
		'position' => 10,
		'redirect' 	=> false
	));
 
}