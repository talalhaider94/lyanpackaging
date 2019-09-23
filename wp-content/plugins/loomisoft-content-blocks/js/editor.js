/**
 * Reusable Content & Text Blocks Plugin by Loomisoft - Editor JavaScript Routines
 * Copyright (c) 2017 Loomisoft (www.loomisoft.com)
 */

jQuery(function ($) {
	tinymce.create('tinymce.plugins.ls_cb_dialog', {
		init: function (editor, url) {
			editor.addButton('ls_cb_button', {
				icon: 'icons ls-cb-mce-icon',
				tooltip: 'Insert Loomisoft Content Block',
				cmd: 'ls_cb_plugin_command'
			});

			editor.addCommand('ls_cb_plugin_command', function () {
				$('#ls-cb-mce-popup').dialog('open');
			});
		}

	});

	tinymce.PluginManager.add('ls_cb_button', tinymce.plugins.ls_cb_dialog);

	$('#ls-cb-mce-popup').dialog({
		title: 'Insert Loomisoft Content Block',
		dialogClass: 'wp-dialog ls-cb-dialog',
		autoOpen: false,
		draggable: false,
		width: 'auto',
		modal: true,
		resizable: false,
		closeOnEscape: true,
		position: {
			my: "center",
			at: "center",
			of: window
		},
		open: function () {
			$('#ls_cb_option_cbid').val('');
			$('#ls_cb_option_para').val('');
			$('#ls_cb_option_use-0').prop('checked', true);
			$('.ls-cb-dialog .ui-widget-overlay').on('click', function () {
				$('#ls-cb-mce-popup').dialog('close');
			});
		},
		create: function () {
			$('.ui-dialog-titlebar-close').addClass('ui-button');
		},
	});

	$('#ls_cb_option_insert').on('click', function (e) {
		e.preventDefault();

		var cb_id = $('#ls_cb_option_cbid').val();
		var cb_slug = $('#ls_cb_option_cbid option:selected').attr('slug');
		var para = $('#ls_cb_option_para').val();
		var use = $('.ls_cb_option_use:checked').val();
		var shortcode = '';

		if (cb_id == '') {
			if (($('#ls_cb_option_cbid').attr('message'))) {
				alert($('#ls_cb_option_cbid').attr('message'));
			}
		} else {

			shortcode = '[ls_content_block';
			if (use == 'slug') {
				shortcode += ' slug="' + cb_slug + '"';
			} else {
				shortcode += ' id="' + cb_id + '"';
			}
			if (para != '') {
				shortcode += ' para="' + para + '"';
			}
			shortcode += ']';

			tinymce.execCommand('mceInsertContent', false, shortcode);
			$('#ls-cb-mce-popup').dialog('close');
		}
	});
});