! function($) {
	$('[data-vc-navbar-control="preview"]').on('click',function(e){
		 e && e.preventDefault();
	     sendDataPopupPreview();
	})
	$('#dh_popup_templates-editor-button').on('click',function(e){
		 e && e.preventDefault();
		 return openTemplatesWindow();
	});
	
	$('[data-dh-popup-preview-handler]').on('click',function(e){
		 e && e.preventDefault();
		var  $wrapper = $(this).closest("[data-template_id]");
	     sendDataPopupPreview($wrapper.data("template_id"));
	});
	
	
	var sendDataPopupPreview = function(template_id) {
      var $form_html = '<form id="dh_popup-preview-form" target="_blank" action="' + encodeURI(ajaxurl) + '" method="post" style="position: absolute;visibility: hidden;right: 0; bottom:0">  \
<input type="hidden" name="action" value="dh_popup_preview"> \
<input type="hidden" name="post_id"> \
<input type="hidden" name="_vcnonce" value="' + window.vcAdminNonce + '"> \
<input type="submit" name="submit">';
      	if(template_id)
      		$form_html += '<input type="hidden" name="template_unique_id" value="'+template_id+'">';
      	var $form = $($form_html);
      	$form.prependTo("body");
        $form.find('[name="post_id"]').val($("#post_ID").val());
        $form.find('[type="submit"]').trigger("click");
        $form.remove()
    }
	
	var openTemplatesWindow = function(e){
		 vc.templates_panel_view.render().show()
	}
	vc.TemplatesPanelViewFrontend.prototype.loadTemplate = function(e) {
         e.preventDefault(), e.stopPropagation();
         if( confirm(dh_popup_editor_frontend.add_template_message)){
        	vc.TemplatesPanelViewFrontend.__super__.loadTemplate.call(this,e)
         }
    }
	vc.TemplatesPanelViewFrontend.prototype.renderTemplate = function(html) {
		_.each(vc.$page.find(' > [data-model-id]'),function(element){
			var model = vc.shortcodes.get($(element).data("modelId"));
			model.destroy();
		});
		var template, data;
		_.each($(html), function(element) {
		     if ("vc_template-data" === element.id){
		    	 try {
			    	 data = JSON.parse(element.innerHTML)
				 } catch (e) {
				     vcConsoleLog(e)
				 }
		     }
		     if( "vc_template-html" === element.id)
		    	 template = element.innerHTML
		 });
		template && data && vc.builder.buildFromTemplate(template, data) ? this.showMessage(window.i18nLocale.template_added_with_id, "error") : this.showMessage(window.i18nLocale.template_added, "success"), vc.closeActivePanel()
     }
	
	$(window).on("vc_build",function(){
		vc.post_settings_view.on('save',function(){
			var popup_width = parseInt(this.$el.find("#dh_popup-width-field").val(),10);
			var popup_height = this.$el.find("#dh_popup-height-field").val() ?  parseInt(this.$el.find("#dh_popup-height-field").val(),10) : '';
			$(vc.$page).closest('.dh-popup').css({
				'width':parseInt(popup_width)+'px',
				'height':popup_height ? popup_height +'px' : ''
			});
			if(!$('#dh_popup-input-fields-meta').length){
				var input_html = '<div id="dh_popup-input-fields-meta" style="display: none;"><input id="dh_popup-width-field-meta" type="hidden" name="popup_width"><input id="dh_popup-height-field-meta" type="hidden" name="popup_height"></div>';
				$(input_html).prependTo($("#post"));
			}
			$('#dh_popup-width-field-meta').val(popup_width);
			$('#dh_popup-height-field-meta').val(popup_height)
		})
	})
	
	var dh_getViewport = function() {
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
	
	var dh_resizeUpdatePopup = function(popup){
		
		var $popup = $(popup),
			viewport = dh_getViewport();
		
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
	
	$(window).bind("resize",function(){
		dh_resizeUpdatePopup($(vc.$page).closest('.dh-popup'));
	});
	
}(window.jQuery);
