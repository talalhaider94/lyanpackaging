(function($) {

	//on hover show tooltip
	$('.bb').on('mouseenter', '.option-img', function() {
		var tooltipText = $(this).attr('data-tooltip');
		var tooltipDirection = $(this).attr('data-tooldir');

		$(this).append('<span class="option-tooltip ' + tooltipDirection + '">'+ tooltipText +'</span>');

	});

	//on hover show tooltip
	$('.bb').on('mouseleave', '.option-img', function() {
		$('.option-tooltip', this).remove();
	});

	//show next section on click
	$('.bb').on('click', '.show-next', function() {

		showNext($(this).attr('data-target'), $(this).attr('data-val'), $(this).attr('data-error'));

		if($(this).hasClass('calculate')) {
			calculatePrice();
		}

	});

	//on select thickness insert value to input
	$('.bb').on('click', '.option-col', function() {
		
		var itemValue = $(this).attr('data-option-val');
		
		if($('#bb-thickness').val(itemValue)){

			if($('.option-col').hasClass('active')) {
				$('.option-col').removeClass('active');
			}

			$(this).addClass('active');
		}

		if($('#bb-sqm').val() != '' && $('#bb-qty').val() != '' && $('#bb-thickness').val() != '' ) {
			calculatePrice();
		}

	});

	//on qty change, adjust form input
	$('.bb').on('change', '#bb-quantity', function() {

		$('#bb-qty').val($(this).val());

		calculatePrice();

	});

	//show next function
	function showNext(target, value, error) {

		if($('#'+value).val() != '') {

			$("html, body").animate({ scrollTop: ($('#'+target).offset().top - $('.header_top_bottom_holder').height()) }, 600);

			if($('#'+target).hasClass('hidden')) {
				$('#'+target).removeClass('hidden');
			}

		} else {

			$('#error').html(error);
			$('#error').addClass('active');
		}

		setTimeout(function() {
			$('#error').html('');
			$('#error').removeClass('active');
		}, 5000);

	}

	//on measurement input
	$('.bb').on('keyup', '.dimension-input', function(key) {

		if($(this).is('#bb-length')) {

			if($(this).val() != '') {
				$('.length').html($(this).val()+'mm');
			} else {
				$('.length').html('');
			}

		} else if($(this).is('#bb-height')) {

			if($(this).val() != '') {
				$('.height').html($(this).val()+'mm');
			} else {
				$('.height').html('');
			}

		} else if($(this).is('#bb-width')) {

			if($(this).val() != '') {
				$('.width').html($(this).val()+'mm');
			} else {
				$('.width').html('');
			}

		}

		if($('#bb-length').val() != '' && $('#bb-height').val() != '' && $('#bb-width').val() != '') {

			var error = false;

			//check that all input values are between 100 and 600
			$('.dimension-input').each(function() {
				if(Number($(this).val()) < 100 || Number($(this).val()) > 600) {
					error = true;
				}
			});

			if(error) {

				$('#error').html('Dimensions should be greater than 100 and less than 600.');
				$('#error').addClass('active');
				$('#bb-sqm').val('');

			} else {

				var sqm = calculateSqm($('#bb-length').val(), $('#bb-height').val(), $('#bb-width').val());
				$('#error').html('');
				$('#error').removeClass('active');

			}

			$('#bb-sqm').val(sqm);

			if($('#bb-sqm').val() != '' && $('#bb-qty').val() != '' && $('#bb-thickness').val() != '' ) {
				calculatePrice();
			}

		} else {

			$('#bb-sqm').val('');

		}
	
	});

	//reset button
	$('.bb').on('click', '#btn-reset', function() {

		$("html, body").animate({ scrollTop: ($('#bb-thickness').offset().top - $('.header_top_bottom_holder').height()) }, 600);

		$('.bb[data-status="hide"]').each(function() {

				$(this).addClass('hidden');
			
		});

	});

	//on add to cart button create new variation and add new product var to cart
	$('#completion-col').on('click','#add-to-cart', function(e) {

		e.preventDefault();
		
		console.log('Create Variation');

		// preventDefault();
		var totalSqm = $('#bb-sqm').val() * $('#bb-qty').val();
	
		$.ajax({
			url : createvar.ajax_url,
			beforeSend : (function() {
				$('#add-to-cart').prop('disabled', true);
				$('#add-to-cart').html('<i class="fa fa-spin fa-refresh"></i>');
			}),
			type : 'post',
			data : {
				action : 'create_var',
				qty : $('#bb-qty').val(),
				weight : $('#bb-thickness').val(),
				height : $('#bb-height').val(),
				width : $('#bb-width').val(),
				length : $('#bb-length').val(),
				price : $('#bb-price').val(),
				sqm : totalSqm
			},
			success : function(response) {
				var responseData = JSON.parse(response);
				console.log(responseData);
				$('#add-to-cart').html('Added to cart!');
				setTimeout(function() {
					window.location.replace(window.location.protocol + "//" + window.location.hostname + "/cart");
				}, 500);
			}
		});

		console.log(totalSqm);

	});

	//calculate Sqm
	function calculateSqm(length, height, width) {

		var sqm  = (((length / 1000) + (width / 1000)) * ((width / 1000) + (height / 1000))) * 2;
		sqm = sqm.toFixed(2);
		
		if(sqm < 0.3) {
			sqm = 0.3;
		}

		console.log(sqm);

		return sqm;
			
	}

	//calculate price function using ajax
	function calculatePrice() {
		console.log('Calculate the box');

		// preventDefault();
		var totalSqm = $('#bb-sqm').val() * $('#bb-qty').val();
	
		$.ajax({
			url : bbprice.ajax_url,
			beforeSend : (function() {
				$('#completion-col').append('<span class="loading-cover"><i class="fa fa-spin fa-refresh"></i></span>');
			}),
			type : 'post',
			data : {
				action : 'calc_price',
				qty : $('#bb-qty').val(),
				weight : $('#bb-thickness').val(),
				sqm : totalSqm
			},
			success : function(response) {
				var responseData = JSON.parse(response);
				console.log(responseData);
				$('#completion-col .loading-cover').remove();
				$('#roll-count').html(responseData.free_rolls);
				$('#total-price').html("&pound;"+Number(responseData.price).toFixed(2));
				var pricePerBox = Number(responseData.price).toFixed(2);
				$('#total-price').html("&pound;"+pricePerBox);
				$('#bb-price').val(pricePerBox);
			}
		});

		console.log(totalSqm);
	}

})(jQuery);