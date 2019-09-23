(function ($) {
	$('.dh_popup_analytics_date').each(function(){
		$(this).datepicker({
			dateFormat: 'yy-mm-dd',
			numberOfMonths: 1,
			showButtonPanel: true
		});
	});
	$(document).on('click','#clear_analytics',function(){
		return confirm(dh_popup_analytics.delete_confirm);
	});
})(jQuery);