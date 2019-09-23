(function ($) {
	
	$('[data-dh_popup_meta_box_datepicker]').each(function(){
		$(this).datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true
		});
	});
	
	$('.dh_popup-metabox-tab .nav-tab').on('click',function(e){
		e.stopPropagation();
		e.preventDefault();
		var _this = $(this),
			selector = _this.attr('data-target');
		if (!selector) {
			selector = _this.attr('href');
			selector = selector && selector.replace(/.*(?=#[^\s]*$)/, ''); // strip for ie7
		}
		_this.closest('.dh_popup-nav-tab-wrapper').find('.nav-tab-active').removeClass('nav-tab-active');
		_this.addClass('nav-tab-active');
		$('.dh_popup-tab-content').find('.dh_popup-tab-panel').hide();
		$('.dh_popup-tab-content').find(selector).show();
		
	});
	
	$('.dh_popup-meta-box-field #clear_cookie').on('click',function(){
		Cookies.remove( dh_popup_meta_box.cookie_prefix + $('.dh_popup-metaboxes').data('postid'), { path: '/' } );
		alert("Cookie is clear");
	});
	var toggleSelectOpenEvent = function(field){
		var fieldValue = $(field).val();
		$('.dh_popup-meta-box-field.scroll_offset_field').hide();
		$('.dh_popup-meta-box-field.inactivity_seconds_field').hide();
		if(fieldValue==='scroll'){
			$('.dh_popup-meta-box-field.scroll_offset_field').show();
		}else if(fieldValue==='inactivity'){
			$('.dh_popup-meta-box-field.inactivity_seconds_field').show();
		}
		
	}
	var toggleSelect = function(field,value,showFields){
		var fieldValue = $(field).val();
		if((!$.isArray(value) && fieldValue == value) || ($.isArray(value) && $.inArray(fieldValue,value) >= 0 )){
			$.each(showFields,function(i,f){
				$('.dh_popup-meta-box-field.'+f+'_field').show();
			});
		}else{
			$.each(showFields,function(i,f){
				$('.dh_popup-meta-box-field.'+f+'_field').hide();
			});
		}
		
	}
	toggleSelectOpenEvent('.dh_popup-meta-box-field #open_event');
	$('.dh_popup-meta-box-field #open_event').on('change',function(){
		toggleSelectOpenEvent($(this),'scroll',['scroll_offset']);
	});
	toggleSelect('.dh_popup-meta-box-field #use_mailing','yes',['mailing_list']);
	$('.dh_popup-meta-box-field #use_mailing').on('change',function(){
		toggleSelect($(this),'yes',['mailing_list']);
	});
	toggleSelect('.dh_popup-meta-box-field #overlay','image',['overlay_image_background']);
	$('.dh_popup-meta-box-field #overlay').on('change',function(){
		toggleSelect($(this),'image',['overlay_image_background']);
	});
	toggleSelect('.dh_popup-meta-box-field #close_type','default',['close_delay']);
	$('.dh_popup-meta-box-field #close_type').on('change',function(){
		toggleSelect($(this),'default',['close_delay']);
	});
})(jQuery);