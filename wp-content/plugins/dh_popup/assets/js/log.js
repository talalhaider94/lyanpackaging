(function ($) {
	$(document).on('click','.dh-popup-entry-list .submitdelete',function(){
		return confirm(dh_popup_log.delete_confirm);
	});
	
	$(document).on('click','a.dh_popup_log_delete',function(){
		return confirm(dh_popup_log.delete_confirm);
	});
	$(document).on('click','#clear_log',function(){
		return confirm(dh_popup_log.delete_confirm);
	});
	$('.dh-popup-action,.dh-popup-action2').click(function(){
		var action = $(this).closest('.bulkactions').find('select');
		if ($(this).closest('form').find('input[name="log_ids[]"]:checked').length > 0) {
			if (action.val() == 'delete') {
				return confirm(dh_popup_log.delete_confirm);
			}
		} else {
			return false;
		}
	});
})(jQuery);