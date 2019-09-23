<?php
/**
 * Template Name: Box Builder
 *
 * Description : Page for the box builder.
 **/

get_header();

//include Revolution slider for box builder page
echo do_shortcode('[rev_slider alias="box-builder"]'); 
?>

<section id="offers-banner">
   
   <div class="container">
        
           <div class="container_inner">
              
              <div class="col">
                  <h3>Free delivery<br/><small>For orders over &pound;100</small></h3>
              </div>
              
              <div class="col">
                  <h3>Free tape<br/><small>With every 25 boxes ordered</small></h3>
              </div>
               
           </div>   
       
   </div>
    
</section>

<!--intro section -->
<section class="squeze-padded bb" id="bb-intro">

	<div class="container">

		<div class="container_inner">
			<h3>THE ORDER PROCESS</h3>
			<p>Ordering your custom sized boxes couldn’t be easier. Simply select the thickness, enter the dimensions, chose a quantity, and leave the rest to us.</p>
		</div>
		
	</div>
	
	<div class="action-bottom">
		<i class="fa fa-chevron-down fa-3x"></i>
	</div>
	

</section>

<!-- Pick thickness -->
<section class="squeze-padded bb" id="bb-thickness">

	<div class="container">
		<div class="container_inner">
			
			<h3>
				<small>Step One</small>
				Select a thickness
			</h3>

			<div class="options">
				
				<div class="option-col" data-type="thickness" data-option-val="single">

					<span class="option-img" data-tooltip="Medium Duty Application" data-tooldir="left">
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/cardboard-illustrations-250px-single.png" alt="Lyan Box Builder" />
					</span>

					<p>Single Wall</p>
					
				</div>

				<div class="option-col" data-type="thickness" data-option-val="double">

					<span class="option-img" data-tooltip="Heavy Duty Application" data-tooldir="right">
						<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/cardboard-illustrations-250px-double.png" alt="Lyan Box Builder" />
					</span>

					<p>Double Wall</p>
					
				</div>

			</div>

			<br/>
			<button class="bb-btn btn-dark show-next" data-val="bb-thickness" data-error="Please select a box thickness." data-target="bb-dimensions" type="button">Next Step</button>


		</div>
	</div>

</section>

<!-- enter the dimensions -->
<section class="squeze-padded bb hidden" data-status="hide" id="bb-dimensions">
	
	<div class="container">
		<div class="container_inner">
			
			<h3>
				<small>Step Two</small>
				Enter your box dimensions
			</h3>
			
			<span class="dimensions-holder">
				<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/dimensions-box.png" alt="Lyan Box Builder" />
				<span class="height"></span>
				<span class="width"></span>
				<span class="length"></span>
			</span>

			<div class="options">
				
				<input type="number" id="bb-length" class="dimension-input" placeholder="Length (mm)" />
				<input type="number" id="bb-width" class="dimension-input" placeholder="Width (mm)" />
				<input type="number" id="bb-height" class="dimension-input" placeholder="Height (mm)" />

			</div>
			<br/>
			<button class="bb-btn btn-brand show-next" data-val="bb-sqm" data-error="Please make sure all dimensions are filled in and are greater than 100 and less than 600." data-target="bb-quantity" id="bb-sqm-submit" type="button">Next Step</button><br/>
            <p style="color: #fff"><small>*Please note box diagram is not to scale and for representation only</small></p>

		</div>
	</div>
	

</section>

<!-- Enter the quantity -->
<section class="squeze-padded bb hidden" data-status="hide" id="bb-quantity">
	
	<div class="container">
		<div class="container_inner">
			
			<h3>
				<small>Step Three</small>
				Select your quantity
			</h3>

			<div class="options">
				
				<select name="bb-quantity" class="bb-quantity" id="bb-quantity">
					<?php
					//picker in steps of 25
					for($i = 25; $i <= 500; $i += 25) {
						echo '<option value="'.$i.'">'.$i.'</i>';
					}
                    
                    echo '<option value="contact">500+</option>'; 
                        
					?>
				</select><button class="bb-btn btn-brand show-next calculate" data-val="bb-qty" data-error="Please hit calculate if you have updated the quantity." data-target="bb-total" type="button" id="qty-calc-btn">Calculate</button>

			</div>

			<p>All of our boxes have quantity price breaks built in so you can effectively reduce the unit cost of any box by increasing the order quantity. For large or regular orders please get in touch and we can provide you with a better price direct rather than using our online calculator.</p>

		</div>
	</div>
	

</section>

<!-- Total -->
<section class="squeze-padded bb hidden" data-status="hide" id="bb-total">
	
	<div class="container">
			
		<div class="col" id="completion-col">

			<form id="box-calculation">
				<input type="hidden" id="bb-thickness" name="thickness" value="" />
				<input type="hidden" id="bb-sqm" name="sqm" value="" />
				<input type="hidden" id="bb-qty" name="quantity" value="25" />
				<input type="hidden" id="bb-price" name="price" value="" />
			</form>

			<p>Price (All prices are excluding VAT):</p>
			<h3 id="total-price">&pound;1.40</h3>
			<small id="free-rolls">This order entitles you to <span id="roll-count">2</span> free rolls of tape <span id="delivery-result"></span></small>
			<div class="actions">
				<button class="bb-btn btn-brand" type="button" id="add-to-cart">Add to basket</button><button class="bb-btn btn-brand" id="btn-reset" type="button">Reset <i class="fa fa-refresh"></i></button>
			</div>
			
			<div class="alert" id="delivery-alert"></div>
			
			<span class="loading-cover"><i class="fa fa-spin fa-refresh"></i></span>

		</div>

		<div class="col" id="img-col">
			<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/BoxImage.png" alt="Lyan Box Builder" />
		</div>

	</div>
	

</section>


<!-- outro/custom upsell -->
<section class="squeze-padded bb" id="bb-outro">
	
	<div class="container">
		<div class="container_inner">
			
			<div class="content">
				<h3>Different Styles and custom prints</h3>
				<p>Lyan Packaging can also supply custom boxes that need to be a specific style, specification or size, and from 500+ lots, we can also print our boxes with your logo with up to 2 colours.</p>

				<a class="bb-btn btn-brand" type="button" href="/contact-us/">Get in touch</a>
			</div>

			<img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/img/outro-box.jpg?v=1" alt="Lyan Box Builder - Custom Boxes" />


		</div>
	</div>

</section>

<div id="error"></div>

<?php
get_footer();