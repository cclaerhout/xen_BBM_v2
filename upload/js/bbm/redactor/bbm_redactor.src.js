!function($, window, document, undefined)
{
	XenForo.BbmCustomEditor = function($textarea) { this.__construct($textarea); };

	XenForo.BbmCustomEditor.prototype =
	{
		__construct: function($textarea)
		{
			if(typeof BBM_Redactor === undefined)
				return false;

			var redactorOptions = $textarea.data('options'),
				myButtons = this.createCustomButtons(),
				myOptions = {
					editorOptions:{
						//plugins: ['test', 'test2'],
					},
					buttons: myButtons
				};

			if(BBM_Redactor.buttonsGrid.length !== 0){
				myOptions.editorOptions.buttons = BBM_Redactor.buttonsGrid;
			}

			if(typeof RedactorPlugins === undefined)
				RedactorPlugins = {};

			$.extend(true, redactorOptions, myOptions);
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

}(jQuery, this, document, 'undefined');