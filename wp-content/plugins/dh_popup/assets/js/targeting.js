(function($) {
	var $document = $( document );
	$document.on('click','.dh-popup-targeting-item .widget-title,.dh-popup-targeting-item .widget-control-close',function(e){
		e.preventDefault();
		var target = $(this),
			widget = target.closest('div.widget'),
			inside = widget.children('.widget-inside');
		if ( inside.is(':hidden') ) {
			target.attr( 'aria-expanded', 'true' );
			inside.slideDown( 'fast', function() {
				widget.addClass( 'open' );
			});
		} else {
			target.attr( 'aria-expanded', 'false' );
			inside.slideUp( 'fast', function() {
				widget.attr( 'style', '' );
				widget.removeClass( 'open' );
			});
		}
	})
	$document.on('click','.dh-popup-targeting-item .widget-control-remove',function(e){
		e.preventDefault();
		return targetingDelete($(this).closest('.dh-popup-targeting-item'));
	});
	$document.on('click','.dh-popup-targeting-item .widget-control-save',function(e){
		e.preventDefault();
		return targetingSave($(this).closest('.dh-popup-targeting-item'));
	});
	$document.on('click','#dh-popup-targeting-new',function(e){
		e.preventDefault();
		var _item_number = $('#_item_number').val();
		_item_number++;
		var item_template = $(this).closest('.dh-popup-targeting').data('item_template');
		item_template = $(item_template.replace('__i__','item-'+_item_number));
		$('#_item_number').val(_item_number);
		$('.dh-popup-targeting-sortables').prepend(item_template);
		window.setTimeout( function() {
			// Cannot use a callback in the animation above as it fires twice,
			// have to queue this "by hand".
			item_template.find( '.widget-title' ).trigger( 'click' );
		}, 250 );
	});
	var targetingSaveOrder = function(tab){
		var data = {
			action: 'dh_popup_targeting_save_order',
			current_tab: $('#_current_tab').val(),
			nonce: dh_popup_targeting.nonce,
			ids:tab.closest('.dh-popup-targeting-sortables').sortable('toArray').join(',')
		};
		$( '.spinner', $('.dh-popup-targeting-action') ).addClass( 'is-active' );
		$.post( dh_popup_targeting.ajax_url, data, function(response) {
			$( '.spinner', $('.dh-popup-targeting-action') ).removeClass( 'is-active' );
		});
	}
	var targetingSave = function(item){
		var data = item.find('form').serialize(),
			a = {
				action: 'dh_popup_targeting_save_item',
				current_tab: $('#_current_tab').val(),
				item_id:item.attr('id'),
				nonce: dh_popup_targeting.nonce,
				item_number: $('#_item_number').val(),
				ids:item.closest('.dh-popup-targeting-sortables').sortable('toArray').join(',')
			};
		data += '&' + $.param(a);
		$( '.spinner', item ).addClass( 'is-active' );
		$.post( dh_popup_targeting.ajax_url, data, function(response) {
			$( '.spinner', item ).removeClass( 'is-active' );
			$( '.widget-title-text', item ).text("Display");
			$( '.in-widget-title', item ).text(": " + $('select[name="display"] option:selected',item).text());
		});
	}
	
	var targetingDelete = function(item){
		var data = {
			action: 'dh_popup_targeting_delete_item',
			current_tab: $('#_current_tab').val(),
			nonce: dh_popup_targeting.nonce,
			item_id:item.attr('id'),
		};
		$( '.spinner', item ).addClass( 'is-active' );
		$.post( dh_popup_targeting.ajax_url, data, function(response) {
			item.slideUp('fast', function(){
				$(this).remove();
			});
		});
	}
	$('.dh-popup-targeting-sortables').sortable({
		axis: "y",
		placeholder: 'widget-placeholder',
		items: '> .dh-popup-targeting-item',
		handle: '> .widget > .widget-top > .widget-title',
		cursor: 'move',
		distance: 2,
		containment: '#wpwrap',
		tolerance: 'pointer',
		refreshPositions: true,
		start: function( event, ui ) {
			var height, $this = $(this),
				$wrap = $this.parent(),
				inside = ui.item.children('.widget-inside');

			if ( inside.css('display') === 'block' ) {
				ui.item.removeClass('open');
				ui.item.find( '.widget-top button.widget-action' ).attr( 'aria-expanded', 'false' );
				inside.hide();
				$(this).sortable('refreshPositions');
			}
		},
		stop: function( event, ui ) {
			targetingSaveOrder(ui.item);
		}
	});
})(jQuery);