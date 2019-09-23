<?php

// enqueue the child theme stylesheet
add_action('wp_enqueue_scripts', 'childThemeEnqueue', 20);
function childThemeEnqueue() {
	
	// enqueue style
	wp_enqueue_style('child-style', get_stylesheet_directory_uri().'/style.css');
	wp_enqueue_style('themeAdditional', get_stylesheet_directory_uri().'/assets/css/themeAdditional.css');
	wp_enqueue_style('box-builder-style', get_stylesheet_directory_uri().'/extras.css', '', time());
	
	// enqueue script
    wp_enqueue_script('Bioep', get_stylesheet_directory_uri() . '/assets/js/bioep.min.js', array( 'jquery' ));
    wp_enqueue_script('box-builder', get_stylesheet_directory_uri().'/box-builder-min.js', array(), time(), true); 	
	wp_enqueue_script('themeAdditionalScript', get_stylesheet_directory_uri() .'/assets/js/themeAdditionalScripts.js', array('jquery'), '20180722', true);

	//enqueue Admin ajax
	wp_localize_script( 'box-builder', 'bbprice', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));

	//enqueue Admin ajax
	wp_localize_script( 'box-builder', 'createvar', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	));
	
}

//include box builder options
include_once('box-options.php');

//new price format
function amend_price_output( $price, $product ) {
 
  if ( $product->is_on_sale() ) :
    $has_sale_text = array(
      '<del>' => '<del>From ',
      '<ins>' => '<br>Sale Price: <ins>'
    );
    $return_string = str_replace(array_keys( $has_sale_text ), array_values( $has_sale_text ), $price);
  else :
    $return_string = 'From ' . $price;
  endif;

  return $return_string;
}
add_filter( 'woocommerce_get_price_html', 'amend_price_output', 100, 2 );

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

//ajax calculation for Box Builder
add_action( 'wp_ajax_nopriv_calc_price', 'calc_price' );
add_action( 'wp_ajax_post_calc_price', 'calc_price' );
add_action( 'wp_ajax_calc_price', 'calc_price' );
function calc_price() {
   
   $qty = $_POST['qty'];
   $weight = $_POST['weight'];
   $sqm = $_POST['sqm'];
   $boxsqm = $_POST['singleboxsqm'];

   $prices = array(
   	'single' => array(
   		'0 - 50' => get_option('single-0-50') == '' ? 1.38 : get_option('single-0-50'),
   		'51 - 100' => get_option('single-51-100') == '' ? 1.25 : get_option('single-51-100'),
   		'101 - 200' => get_option('single-101-200') == '' ? 1.18 : get_option('single-101-200'),
   		'201' => get_option('single-200') == '' ? 1.11 : get_option('single-200')
   		),
   	'double' => array(
   		'0 - 50' => get_option('double-0-50') == '' ? 2.00 : get_option('double-0-50'),
   		'51 - 100' => get_option('double-51-100') == '' ? 1.80 : get_option('double-51-100'),
   		'101 - 200' => get_option('double-101-200') == '' ? 1.70 : get_option('double-101-200'),
   		'201' => get_option('double-200') == '' ? 1.60 : get_option('double-200') 
   		),
   );

   $priceRanges = $prices[$weight];
   $sumPrice = 0;

   foreach($priceRanges as $priceRange => $price) {

   	$minMax = explode(' - ', $priceRange);

   	$min = $minMax[0];
   	$max = $minMax[1];


	   	if($sqm >= $min && $sqm <= $max) {

			$sumPrice = $price;
			break;
	   		error_log($min.' '.$max.' '.$qty.' '.$sumPrice);
			

	   	} else {

	   		if(is_null($max)) {
				$sumPrice = $price;
			} else {
				$sumPrice = null;
			}

	   	}

   }

   $totalPrice = $qty * $sumPrice;
   $freeRolls = $qty / 25;
    
   $boxPrice = $boxsqm * $sumPrice;

   $returnData = array(
   		'price' => "$boxPrice",
   		'total' => "$totalPrice",
   		'free_rolls' => "$freeRolls",
        'box_sqm' => "$boxsqm"
   );

   echo json_encode($returnData, JSON_PRETTY_PRINT);
   die();

}

/**
 * Create a product variation for a defined variable product ID.
 *
 * @since 3.0.0
 * @param int   $product_id | Post ID of the product parent variable product.
 * @param array $variation_data | The data to insert in the product.
 */

function create_product_variation( $product_id, $variation_data ){

    // Get the Variable product object (parent)
    $product = wc_get_product($product_id);


    // check if SKU already exists and return variation id if it does
    // if not then create new variation
    $variationArgs = array(
    	'post_type'  => 'product_variation',
    	'meta_query' => array(
        	array(
            	'key'   => '_sku',
            	'value' => $variation_data['sku'],
        	)
    	)
	);

	$checkForVariation = new WP_Query($variationArgs);

	if($checkForVariation -> have_posts()): 

		while($checkForVariation -> have_posts()) : $checkForVariation -> the_post();

		$variation_id = get_the_ID();

		endwhile;

	else :

	    $variation_post = array(
	        'post_title'  => $product->get_title(),
	        'post_name'   => 'product-'.$product_id.'-variation',
	        'post_status' => 'publish',
	        'post_parent' => $product_id,
	        'post_type'   => 'product_variation',
	        'guid'        => $product->get_permalink()
	    );

	    // Creating the product variation
	    $variation_id = wp_insert_post( $variation_post );

	    // Get an instance of the WC_Product_Variation object
	    $variation = new WC_Product_Variation( $variation_id );

	    // Save thickness attribute to variation
	    update_post_meta( $variation_id, 'attribute_thickness', $variation_data['thickness']);
	    
	    ## Set/save all other data

	    // SKU
	    if( ! empty( $variation_data['sku'] ) )
	        $variation->set_sku( $variation_data['sku'] );

	    // Prices
	    if( empty( $variation_data['sale_price'] ) ){
	        $variation->set_price( $variation_data['regular_price'] );
	    } else {
	        $variation->set_price( $variation_data['sale_price'] );
	        $variation->set_sale_price( $variation_data['sale_price'] );
	    }
	    $variation->set_regular_price( $variation_data['regular_price'] );

	    //dimensions
	    $variation->set_length($variation_data['length']);
	    $variation->set_width($variation_data['width']);
	    $variation->set_height($variation_data['height']);


	    // Stock
	    if( ! empty($variation_data['stock_qty']) ){
	        $variation->set_stock_quantity( $variation_data['stock_qty'] );
	        $variation->set_manage_stock(true);
	        $variation->set_stock_status('');
	    } else {
	        $variation->set_manage_stock(false);
	    }

	    $variation->set_weight(''); // weight (reseting)

	    $variation->save(); // Save the data

	endif;

    return $variation_id;
    die();
}

//ajax calculation for Box Builder
add_action( 'wp_ajax_nopriv_create_var', 'create_var' );
add_action( 'wp_ajax_post_create_var', 'create_var' );
add_action( 'wp_ajax_create_var', 'create_var' );
function create_var() {

	global $woocommerce;
   	
   	// values from JS file
   	// qty : $('#bb-qty').val(),
	// weight : $('#bb-thickness').val(),
	// height : $('#bb-height').val(),
	// width : $('#bb-width').val(),
	// length : $('#bb-length').val(),
	// price : $('#bb-price').val(),
	// sqm : totalSqm

	$qty = $_POST['qty'];
	$thickness = $_POST['weight'];
	$height = $_POST['height'];
	$width = $_POST['width'];
	$length = $_POST['length'];
    $pricePerBox = $_POST['price'];
    $totalSqm = $_POST['sqm'];

	$parent_id = 1405; // Box Builder product ID

	// The variation data
	$variation_data =  array(
	    'thickness' => $thickness,
	    'height' => $height,//get the height
	    'width' => $width,//get the width
	    'length' => $length,//get the length
	    'sku'           => $thickness.$height.'x'.$width.'x'.$length.'-'.$pricePerBox,
	    'regular_price' => $pricePerBox,//get calculated price
	    'sale_price'    => '',
	);


	// The function to be run
	$variation = create_product_variation( $parent_id, $variation_data );
	
	//add to cart
	$woocommerce->cart->add_to_cart($parent_id, $qty, $variation);
	
	http_response_code(200); 
	echo $variation;
	die();

}

/**
     * Output WooCommerce content.
     *
     * This function is only used in the optional 'woocommerce.php' template
     * which people can add to their themes to add basic woocommerce support
     * without hooks or modifying core templates.
     *
     * @access public
     * @return void
     */
    function woocommerce_content() {
	
	    if ( is_singular( 'product' ) ) {
		
		    while ( have_posts() ) : the_post();
			
			    wc_get_template_part( 'content', 'single-product' );
		
		    endwhile;
		
	    } else {
            
            // Moved this to woocomooerce direct to appear in the right place
	    	//do_action( 'woocommerce_archive_description' );
		
		    if ( have_posts() ) {
			
			    /**
			     * Hook: woocommerce_before_shop_loop.
			     *
			     * @hooked wc_print_notices - 10
			     * @hooked woocommerce_result_count - 20
			     * @hooked woocommerce_catalog_ordering - 30
			     */
			    do_action( 'woocommerce_before_shop_loop' );
			
			    woocommerce_product_loop_start();
			
			    if ( wc_get_loop_prop( 'total' ) ) {
				    while ( have_posts() ) {
					    the_post();
					
					    /**
					     * Hook: woocommerce_shop_loop.
					     *
					     * @hooked WC_Structured_Data::generate_product_data() - 10
					     */
					    do_action( 'woocommerce_shop_loop' );
					
					    wc_get_template_part( 'content', 'product' );
				    }
			    }
			
			    woocommerce_product_loop_end();
			
			    /**
			     * Hook: woocommerce_after_shop_loop.
			     *
			     * @hooked woocommerce_pagination - 10
			     */
			    do_action( 'woocommerce_after_shop_loop' );
		    } else {
			    /**
			     * Hook: woocommerce_no_products_found.
			     *
			     * @hooked wc_no_products_found - 10
			     */
			    do_action( 'woocommerce_no_products_found' );
		    }
	    }
    }