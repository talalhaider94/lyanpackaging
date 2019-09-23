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
	vc.TemplatesPanelViewBackend.prototype.templateLoadPreviewAction = "dh_popup_editor_load_template_preview";
	vc.TemplatesPanelViewBackend.prototype.renderTemplate = function(html) {
        _.each(vc.filters.templates, function(callback) {
            html = callback(html)
        }), vc.storage.setContent(html), vc.shortcodes.fetch({
            reset: true
        }), this.hide()
    }
	
	vc.TemplatesPanelViewBackend.prototype.loadTemplate = function(e) {
         e.preventDefault(), e.stopPropagation();
         if( confirm(dh_popup_editor.add_template_message)){
	         var $template = $(e.target).closest("[data-template_id][data-template_type]");
	         $.ajax({
	             type: "POST",
	             url: this.loadUrl,
	             dataType: 'json',
	             data: {
	                 action: this.template_load_action,
	                 template_unique_id: $template.data("template_id"),
	                 template_type: $template.data("template_type"),
	                 vc_inline: true,
	                 _vcnonce: window.vcAdminNonce
	             },
	             context: this
	         }).done(function(response){
	        	 vc.$custom_css.val(response.meta.custom_css);
	        	 $('.dh_popup-meta-box-field #width').val(response.meta.width);
	        	 $('.dh_popup-meta-box-field #height').val(response.meta.height);
	        	 this.renderTemplate(response.template);
	         })
         }
     }
	
}(window.jQuery);
