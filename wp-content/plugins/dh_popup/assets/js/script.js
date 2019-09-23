( function () {
	if ( typeof window.CustomEvent === "function" ) return false;

	function CustomEvent ( event, params ) {
		params = params || { bubbles: false, cancelable: false, detail: undefined };
		var evt = document.createEvent( 'CustomEvent' );
		evt.initCustomEvent( event,
			params.bubbles, params.cancelable, params.detail );
		return evt;
	}

	CustomEvent.prototype = window.Event.prototype;

	window.CustomEvent = CustomEvent;
} )();
var DHPopup = window.DHPopup || {};
(function ($) {
	DHPopup.timeoutOpen = null;
	DHPopup.timeoutClose = null;
	DHPopup.submitting = false;
	DHPopup.currentUpdateStats = false;
	DHPopup.scrollPopupDisplayed = false;
	DHPopup.timerInactivity=0;
	DHPopup.intervalInactivity=null;
	DHPopup.tracking = function(category, action, label, value){
		if(!parseInt(dhvcPopupSetting.ga_tracking))
			return;
			
		value = value || 1;
		try {
			if (typeof _gaq == 'object') {
				_gaq.push(['_trackEvent', category, action, label, value, false]);
			} else if (typeof _trackEvent == 'function') { 
				_trackEvent(category, action, label, value, false);
			} else if (typeof __gaTracker == 'function') { 
				__gaTracker('send', 'event', category, action, label, value);
			} else if (typeof ga == 'function') {
				ga('send', 'event', category, action, label, value);
			}
		} catch(error) {
		}
	}
	
	DHPopup.updateStats = function(id,campaign_id,type){
		if(DHPopup.currentUpdateStats)
			return;
		DHPopup.currentUpdateStats =true;
		return $.post(dhvcPopupSetting.ajax_url,{
            'action': 'dh_popup_update_stats_ajax',
            'type': type,
            'object_id': id,
            'campaign_id':campaign_id,
            'nonce':dhvcPopupSetting.nonce
        },function(){
        	DHPopup.currentUpdateStats =false;
        });
	}
	
	DHPopup.initEvents = function(){
		var self = this;
		if($('.dh-popup[data-open_type="load"]').length){
			$('.dh-popup[data-open_type="load"]').each(function(){
				var $popup = $(this);
				if('show' !== DHPopup.getCookies($popup.data('id')))
					DHPopup.openPopUp($popup);
			});
		}
		
		if($('.dh-popup[data-open_type="scroll"]').length){
			$(window).on('scroll', function() {
				 if(DHPopup.scrollPopupDisplayed)
					 return;
	             var height = $(document).height() - $(window).height();
	             var scrollTop = $(window).scrollTop();
	             var currentScroll = parseInt((scrollTop / height * 100),10);
            	 $('.dh-popup[data-open_type="scroll"]').each(function(){
					 var $popup = $(this);
            		 if (currentScroll >= parseInt($popup.data('scroll_offset'),10)) {
            			 if('show' !== DHPopup.getCookies($popup.data('id'))){
							DHPopup.openPopUp($popup);
							DHPopup.scrollPopupDisplayed = true;
						}
            		 }
         		});
	        });
		}
		
		if($('.dh-popup[data-open_type="inactivity"]').length){
			function inactivityTimerInterval()
            {
				DHPopup.timerInactivity++;
				console.log(DHPopup.timerInactivity)
				$('.dh-popup[data-open_type="inactivity"]').each(function(){
					 var $popup = $(this);
	        		 if (DHPopup.timerInactivity > parseInt($popup.data('inactivity_seconds'),10)) {
	        			 if('show' !== DHPopup.getCookies($popup.data('id'))){
	        				 console.log('open')
							DHPopup.openPopUp($popup);
						}
	        		 }
	     		});
				if(DHPopup.timerInactivity > parseInt(dhvcPopupSetting.max_inactivity_timer_interval))
					DHPopup.intervalInactivity && clearInterval(DHPopup.intervalInactivity)
            }
			DHPopup.intervalInactivity && clearInterval(DHPopup.intervalInactivity)
            DHPopup.intervalInactivity = setInterval(inactivityTimerInterval, 1000);
			$(document).on('mousemove',function (e) {
            	DHPopup.timerInactivity=0;
            });
            $(document).on('keypress',function (e) {
            	DHPopup.timerInactivity=0;
            });
		}
		
		$('body').find('[href^="#popup_open_"],[data-popup-open]').each(function(){
			var $this = $(this);
			
		    $this.on('click',function(event){
		    	event.stopPropagation();
				event.preventDefault();
				var popup_id = $(this).data('popup-open');

			    if (!popup_id) {
			      popup_id = $(this).attr('href')
			      popup_id = popup_id && popup_id.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
			      popup_id = popup_id.replace('#popup_open_','');
			    }
			    DHPopup.openPopUp('#dh_popup_'+popup_id,true);
		    });
		});
		$('body').find('.dh_popup_close,[href="#popup_close"]').each(function(){
			var $this = $(this);
			
		    $this.on('click',function(event){
		    	event.stopPropagation();
				event.preventDefault();
				if ($.fancybox && $.fancybox.isOpen)
					$.fancybox.close();
		    });
		});
		
		$('body').find('[href^="#not_show_popup_again_"],[data-not_show_popup_again]').each(function(){
			var $this = $(this);
			
		    $this.on('click',function(event){
		    	event.stopPropagation();
				event.preventDefault();
				var popup_id = $(this).data('not_show_popup_again');

			    if (!popup_id) {
				  popup_id = $(this).attr('href')
				  popup_id = popup_id && popup_id.replace(/.*(?=#[^\s]*$)/, '') // strip for ie7
				  popup_id = popup_id.replace('#not_show_popup_again_','');
			    }
			    DHPopup.setCookies(popup_id,parseInt(dhvcPopupSetting.interval_show_popup_again));
			    $.fancybox.close();
		    });
		});
	}
	
	DHPopup.setCookies=function(name,expires){
		expires = expires ? expires : '';
		return Cookies.set( dhvcPopupSetting.cookie_prefix + name,'show', { expires: expires, path: '/' } );
	}
	
	DHPopup.getCookies=function(name){
		if(window.dh_popup_preview && true===window.dh_popup_preview)
			return false;
		return Cookies.get(dhvcPopupSetting.cookie_prefix + name);
	}
	
	
	DHPopup.afterShowPopup = function(popup){
		var $popup = $(popup),
			open_mode = $popup.data('open_mode');
		switch(open_mode){
			case 'once-session':
				DHPopup.setCookies($popup.data('id'),0);
			break;
			case 'once-period':
			case 'once-session':
				var open_interval = $popup.attr('data-open_interval');
				if(open_interval){
					DHPopup.setCookies($popup.data('id'),parseInt(openinterval));
				}
			break;
		}
	}
	
	DHPopup.getViewport = function() {
		var e = window,
	    	a = 'inner';
		if (!('innerWidth' in window)) {
			a = 'client';
			e = document.documentElement || document.body;
		}
		return {
		    width: e[a + 'Width'],
		    height: e[a + 'Height']
		};
	}
	
	DHPopup.updatePopup = function(popup){
		
		var $popup = $(popup),
			viewport = DHPopup.getViewport();
		if(parseInt($popup.data('hide_mobile')) && viewport.width<= 767){
			$.fancybox.close();
		}
		var width = $popup.data("width");
		var height = $popup.data("height");
		var scale = Math.min(viewport.width/width, viewport.height/height);
		scale = scale - 0.03;	
		if (scale > 1) 
			scale = 1;
		if(parseInt($popup.data('disable_responsive'))){
			scale = 1;
		}
		var translate='';
		switch($popup.data('position')){
			case 'center-bottom':
			case 'center-top':
				translate="translate(-50%,0) ";
			break;
			case 'left-center':
			case 'right-center':
				translate="translate(0,-50%) ";
			break;
			case 'center':
				translate="translate(-50%,-50%) ";
			break;
		}
		var transform_origin = $popup.data('position').replace('-',' ');
		$popup.closest('.dh-popup__wrap').css({
			"transform" : translate+"scale("+scale+")",
			"-ms-transform" : translate+"scale("+scale+")",
			"-webkit-transform" : translate+"scale("+scale+")",
			"-ms-transform-origin": transform_origin, /* IE 9 */
		    "transform-origin": transform_origin,
		    "-webkit-transform-origin": transform_origin
		});
	}
	
	DHPopup.openPopUp = function(popup,withoutDelay){
		withoutDelay = withoutDelay || false;
		var viewport = DHPopup.getViewport();
		var $this = this;
		var $popup = $(popup);
		if( parseInt($(popup).data('hide_mobile')) && viewport.width<= 767){
			$.fancybox.close();
			return;
		}
		if ($.fancybox === undefined)
			return;
		if($.fancybox.isOpen)
			return;//$.fancybox.close();
		var overlayBg = {}
		if($popup.data('overlay_type') === 'image')
			overlayBg.background = 'url(' + $popup.data('overlay_image') + ')';
		var $fancyboxOptons = {
			helpers: {
                overlay:$popup.data('overlay_type') === 'disable' ? null : {
                    locked: false,
                    closeClick: parseInt($popup.data('disable_overlay_click')) ? false : true,
                    showEarly: false,
                    speedOut: 5,
                    css:overlayBg
                }
            },
            tpl: {
				closeBtn : '<a title="Close" class="fancybox-item fancybox-close" href="javascript:;"><i class="fancybox-close__line-top"></i><i class="fancybox-close__line-bottom"></i></a>'
			},
            width:'auto',
            openEffect  : 'none', // 'elastic', 'fade' or 'none'
            autoHeight: true,
	        padding: 0,
            titleShow: false,
            arrows: false,
            autoCenter:false,
            closeBtn: $popup.data('hide_close_button') ? false: true,
            wrapCSS: 'dh-popup__wrap dh-popup__wrap--'+ $popup.data('id') + ' dh-popup__wrap--' +$popup.data('position'),
            beforeClose: function(){
            	DHPopup.triggerEvent( $popup, 'beforeClosePopup', $popup);
            },
            afterClose: function(){
            	$this.timeoutClose && clearTimeout($this.timeoutClose);
            	DHPopup.triggerEvent( $popup, 'afterClosePopup', $popup);
            },
            beforeShow: function(){
            	var campaign_id = $popup.data('campaign_id') ? $popup.data('campaign_id') : 0;
            	DHPopup.updateStats($popup.data('id'),campaign_id,'impressions');
            	DHPopup.tracking('popup','open',$popup.data('id'))
				$popup.find( '.dh_popup_field__text' ).each(function(){
					$(this).val('');
				});
            	DHPopup.triggerEvent( $popup, 'beforeOpenPopup', $popup );
            },
            afterShow: function(){
            	DHPopup.afterShowPopup($popup);
            	var closedelay = $popup.data('close_delay');
            	if(closedelay){
            		$this.timeoutClose && clearTimeout($this.timeoutClose);
            		$this.timeoutClose = setTimeout(function(){
            			$.fancybox.close();
        			}, parseInt(closedelay,10) * 1000);
            	}
            	DHPopup.triggerEvent( $popup, 'afterOpenPopup', $popup );
            }
		};
		
		$fancyboxOptons.onUpdate = function(){
			DHPopup.updatePopup($popup );
		};
		
		if(!parseInt($popup.data('disable_responsive'))){
			$fancyboxOptons.fitToView = false;
			$fancyboxOptons.autoCenter = false;
			$fancyboxOptons.autoSize = false;
			$fancyboxOptons.autoResize =  false;
			$fancyboxOptons.margin = 0;
			$fancyboxOptons.wrapCSS = $fancyboxOptons.wrapCSS + ' dh_popup_responsive';
		}
		var open_delay = $popup.data('open_delay');
		if(false===withoutDelay && open_delay){
			$this.timeoutOpen && clearTimeout($this.timeoutOpen);
    		$this.timeoutOpen = setTimeout(function(){
    			$.fancybox.open($popup,$fancyboxOptons);
			}, parseInt(open_delay,10) * 1000);
		}else{
			$.fancybox.open($popup,$fancyboxOptons);
		}
	}
	
	DHPopup.clearResponse = function(form){
		var $form = $( form );
		$( '.dh_popup_not_valid_tip', $form ).remove();
		$form.removeClass( 'invalid sent failed' );
		$( '[aria-invalid]', $form ).attr( 'aria-invalid', 'false' );
		$( '.dh_popup__form-control', $form ).removeClass( 'dh_popup--not-valid' );

	}
	
	DHPopup.triggerEvent = function( target, name, detail ) {
		var $target = $( target );

		/* DOM event */
		var event = new CustomEvent( 'dhpopup' + name, {
			bubbles: true,
			detail: detail
		} );

		$target.get( 0 ).dispatchEvent( event );

		/* jQuery event */
		$target.trigger( 'dhpopup:' + name, detail );
	}
	
	DHPopup.initSubmit = function(form){
		var $form = $( form );
		$form.on('submit',function(event){
			if(event){
				event.stopPropagation();
				event.preventDefault();
			}
			DHPopup.submitForm($form);
			return false;
		});
	}
	
	DHPopup.notValidTip = function( target, message ) {
		var $target = $( target );
		$( '.dh_popup_not_valid_tip', $target.parent() ).remove();
		$( '<span role="alert" class="dh_popup_not_valid_tip"></span>' ).text( message ).appendTo( $target.parent() );
	}

	
	DHPopup.submitForm = function(form){
		var $this = this;
		if($this.submitting)
			return false;
		$this.submitting = true;
		var $form = $( form );
		DHPopup.clearResponse($form);
		var formData = $form.serialize();
		var ajaxSuccess = function( data, status, xhr, $form ) {
			var detail = {
				id: $( data.into ).attr( 'id' ),
				status: data.status
			};
			switch ( data.status ) {
				case 'validation_failed':
					$.each( data.invalidFields, function( i, n ) {
						$( '.dh_popup_field__group--' + n.field, $form ).each( function() {
							DHPopup.notValidTip(this, n.message);
							$( '.dh_popup__form-control', this ).addClass( 'dh_popup--not-valid' );
							$( '[aria-invalid]', this ).attr( 'aria-invalid', 'true' );
						} );
					} );
					$form.addClass( 'invalid' );
					DHPopup.triggerEvent( $form, 'invalidForm', detail );
				break;
				case 'subscribe_success':
					if($( data.into ).data( 'close_type' )==='success'){
						$this.timeoutClose && clearTimeout($this.timeoutClose);
	            		$this.timeoutClose = setTimeout(function(){
	            			$.fancybox.close();
	        			}, 5000);
					}
					DHPopup.setCookies($( data.into ).attr( 'id' ),parseInt(dhvcPopupSetting.interval_show_popup_again));
					var redirecturl= $( data.into ).data( 'redirect_url' );
					if(redirecturl !== ''){
						window.location.href=redirecturl;
					}
				break;
			}
			DHPopup.triggerEvent( $form, 'submitForm', detail );
		};
		$.ajax( {
			type: 'POST',
			url:dhvcPopupSetting.ajax_url,
			data: formData,
			dataType: 'json',
		} ).done( function( data, status, xhr ) {
			DHPopup.tracking('popup','subscribe',$form.closest('.dh-popup').data('id'))
			ajaxSuccess( data, status, xhr, $form );
			$this.submitting = false;
		} ).fail( function( xhr, status, error ) {
			$this.submitting = false;
			DHPopup.triggerEvent( $form, 'submitFail', $form );
		} );
	}
	$( 'div.dh-popup__inner > form' ).each( function() {
		var $form = $( this );
		DHPopup.initSubmit( $form );
	} );
	$(document).ready(function() {
		DHPopup.initEvents();
	});
})(jQuery);