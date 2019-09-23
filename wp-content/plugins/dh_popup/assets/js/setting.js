(function ($) {
	$('.dh_setting_select_ajax').each(function(){
		var $this = $(this);
		$this.parent().find('.dh_setting_select_ajax_control').on('click',function(){
			$this.find("option").text(dh_popup_setting.loading);
			var data={
					'action':'dh_setting_select_ajax',
					'callback':$this.data('ajax_fnc'),
					'nonce':dh_popup_setting.nonce,
					'field':$this.data('name'),
					'params':{}
				},
				ajax_params=$this.data('ajax_params').split(',');
			$.each(ajax_params,function(i,param){
				data.params[param] = $('#'+param).val();
			});
			$.post(dh_popup_setting.ajax_url,data,function(response){
				if(response.status=='success'){
					$this.html(response.option_html);
				}else{
					$this.find("option").text(dh_popup_setting.notfound);
				}
			},'json');
		});
	});
	$(".dh_popup-settings [data-vc-accordion]").on("show.vc.accordion", function() {
		var _wp_http_referer = $('input[name="_wp_http_referer"]').val();
		$(this).closest('form').find('input[name="_wp_http_referer"]').val(_wp_http_referer+'#'+$(this).closest('.dh_popup-accordion-panel').get(0).id);
    })
	var value = $('.dh_popup-accordion-panel-body').find('select#mailing_list').val();
	$('.dh_popup-accordion-panel-body').find('[class^="dh_popup_mailing_setting_"]').hide();
	$('tr.dh_popup_mailing_setting_' + value).show()
	$('.dh_popup-accordion-panel-body').find('select#mailing_list').on('change',function(){
		var value = $(this).val();
		$('.dh_popup-accordion-panel-body').find('[class^="dh_popup_mailing_setting_"]').hide();
		$('tr.dh_popup_mailing_setting_' + value).show();
	});
})(jQuery);