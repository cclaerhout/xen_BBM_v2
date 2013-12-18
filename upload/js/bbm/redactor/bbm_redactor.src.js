!function($, window, document, undefined)
{
	XenForo.BbmCustomEditor = function($textarea) { this.__construct($textarea); };

	XenForo.BbmCustomEditor.prototype =
	{
		__construct: function($textarea)
		{
			var redactorOptions = $textarea.data('options'),
			    bbmConfig = redactorOptions.bbmButtonConfig || false,
			    self = this;
			
			this.$textarea = $textarea;
			this.options = redactorOptions;
			    
			if(bbmConfig !== false){
				var buttons = [];
				$.each(bbmConfig, function(k,btnGroup){
					btnGroup = self.filterGroup(btnGroup);
					if(btnGroup){
						buttons.push(btnGroup);
					}					
				});

				var bbmOptions = {
					editorOptions: {
						buttons: buttons
					}
				};
				
				$.extend(true, redactorOptions, bbmOptions);
			}
		},
		filterGroup: function(btnGroup)
		{
			return btnGroup;
		}
	}

	XenForo.register('textarea.BbCodeWysiwygEditor', 'XenForo.BbmCustomEditor');

}(jQuery, this, document, 'undefined');