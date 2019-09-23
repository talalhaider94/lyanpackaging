/**
 * Reusable Content & Text Blocks Plugin by Loomisoft - Admin JavaScript Routines
 * Copyright (c) 2017 Loomisoft (www.loomisoft.com)
 */

jQuery(function ($) {
	$(document).ready(function () {
		if ($('.ls_cb_read_only_input').length > 0) {
			$('.ls_cb_read_only_input').prop('readonly', true);
		}

		$('.ls_cb_read_only_input').click(function () {
			$(this).select();
		});

		$('.ls-cb-notice-hide-container').css('display', 'block');

		$('.ls-cb-notice-hide-link').on('click', function (event) {

			event.preventDefault();

			button_id = $(this).attr('id');
			if (button_id) {
				button_id = String(button_id);
				button_prefix = String('ls-cb-notice-hide-');
				button_prefix_length = button_prefix.length;
				if (button_id.substr(0, button_prefix_length) == button_prefix) {
					notice_id = button_id.substr(button_prefix_length, button_id.length - button_prefix_length);
					if (notice_id != '') {
						var data = {
							'action': 'ls_cb_hide_notice',
							'ls-cb-notice-hide': notice_id
						};

						$.post(ajaxurl, data, function (response) {
							if (response == 'ok') {
								$('#ls-cb-notice-' + notice_id).slideUp();
							}
						});
					}
				}
			}
		});
	});
});
