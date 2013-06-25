!function($, window, document, _undefined)
{
	XenForo.BbmCustomEditor = function($textarea) { this.__construct($textarea); };

	XenForo.BbmCustomEditor.prototype =
	{
		__construct: function($textarea)
		{
			var redactorOptions = $textarea.data('options'),
			myButtons = this.createCustomButtons(),
			myOptions = {
				editorOptions:{
					//plugins: ['test', 'test2'],
					buttons: BBM_Redactor.buttonsGrid
				},
				buttons: myButtons
			};

			if(typeof RedactorPlugins == 'undefined')
				RedactorPlugins = {};

			$textarea.data('options', $.extend(redactorOptions, myOptions));
		},
		createCustomButtons: function()
		{
			var buttons = BBM_Redactor.customButtonsConfig, custom = {};

			$.each(buttons, function(code,config){

				var tag = config.tag.replace(/^at_/, '');
				
				/*The XenForo would need to be updated*/
				/*
					var oTag ='['+tag, cTag = '[/'+tag+']', content = config.tagContent, options = config.tagOptions, fullCode;
			
					if(options) {
						oTag += '='+options+']';
					}else{
						oTag += ']';
					}
					fullCode = oTag+content+cTag;
				*/

				custom[code] = {
					title: (config.description) ? config.description : config.tag,
					tag: tag
				}
			});
			
			return custom;
		}
	}

	XenForo.register('textarea.BbCodeWysiwygEditor', 'XenForo.BbmCustomEditor');

}(jQuery, this, document);


