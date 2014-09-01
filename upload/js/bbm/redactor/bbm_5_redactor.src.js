if (typeof RedactorPlugins === 'undefined') var RedactorPlugins = {}; //Fix Redactor bug

!function($, window, document, undefined)
{
	RedactorPlugins.bbmButtons = {
		init: function()
		{
			var self = this;

			if(typeof self._init !== undefined){
				self.exec(self); //FF loader (the plugin is loader after the init)
			}

			$(document).on('bbmButtonsPlugin', function(){
				self.exec(self); //Chrome loader - (the plugin is loaded before the init)
			});
		},
		exec: function(self)
		{
      			//Text buttons
      			$.each(self.textButtons, function(name, text){
      				var $bbmButton = self.getBtn(name);
      				$bbmButton.addClass('BbmText').text(text);
      			});
      
      			//Font Awesome buttons
      			$.each(self.faButtons, function(name, faClass){
      				var $bbmButton = self.getBtn(name);
      				$bbmButton.addClass('BbmFa fa '+ faClass);
      			})		
		},
		textButtons: {},
		faButtons: {}
	};

	XenForo.BbmCustomEditor = function($textarea) { this.__construct($textarea); };

	XenForo.BbmCustomEditor.prototype =
	{
		__construct: function($textarea)
		{
			var redactorOptions = $textarea.data('options');

			if(typeof redactorOptions === undefined){
				return false;
			}

			var bbmConfig = redactorOptions.bbmButtonConfig || false,
				self = this;
			
			$.extend(this, {
				$textarea: $textarea,
				redactorOptions: redactorOptions,
				bbmConfig: bbmConfig,
				bbmButtons: redactorOptions.buttons
			});

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

				//Declare bbmButtons plugin
				var editorOptions = redactorOptions.editorOptions;

				if(typeof editorOptions.plugins === undefined || !$.isArray(editorOptions.plugins)){
					editorOptions.plugins = ['bbmButtons'];
				}else{
					editorOptions.plugins.push('bbmButtons');
				}
			}

			setTimeout(function(e){
				self.postHook(e);
			}, 0);
		},
		filterGroup: function(btnGroup)
		{
			return btnGroup;
		},
		postHook: function(e)
		{
			var self = this;
			this.redactor = this.$textarea.data('redactor');

			if(typeof this.redactor === undefined){
				return false;
			}
			
			this.customButtons = this.redactor.opts.buttonsCustom;

			//Override callbacks if needed
			if(typeof self.bbmButtons !== undefined && self.bbmButtons != null)
			{
				$.each(self.bbmButtons, function(name,data){
					if(typeof self.customButtons[name] === undefined){
						return;
					}
	
					var currentButton = self.customButtons[name];
	
					if(typeof data.tag === undefined || typeof data.bbCodeContent === undefined){
						return;
					}
					
					//Bbm buttons
					var  	tag = data.tag,
						content = data.bbCodeContent,
						options = data.bbCodeOptions,
						separator = data.bbCodeOptionsSeparator,
						textButton = data.textButton;
					
					if(data.textButton){
						RedactorPlugins.bbmButtons.textButtons[name] = data.textButton;
					}else if(data.faButton){
						RedactorPlugins.bbmButtons.faButtons[name] = data.faButton;
					}
					
					if(!content && !options){
						return;
					}
	
					//Bbm buttons with defined content or options
					var oTag ='['+tag, cTag = '[/'+tag+']', fullCode;
	
					if(options) {
						oTag += '='+options+']';
					}else{
						oTag += ']';
					}
	
					fullCode = oTag+content+cTag;
	
					currentButton.callback = function(ed){
						if(options && !content){
							var xen_lib = XenForo.BbCodeWysiwygEditor.prototype;
	
							if(typeof xen_lib.wrapSelectionInHtml !== undefined){
								xen_lib.wrapSelectionInHtml(ed, oTag, cTag, true);
								return;							
							}
						}

						ed.execCommand('inserthtml', fullCode);
					};
				});

				RedactorPlugins.bbmButtons._init = true;
				$(document).triggerHandler('bbmButtonsPlugin');	
			}

			//Extend function hook
			if(typeof BBM_Redactor_EXTEND !== undefined){
				$.each(BBM_Redactor_EXTEND, function(k, func){
					if(typeof func === 'function'){
						func(self);
					}
				});
			}
		}
	}

	XenForo.register('textarea.BbCodeWysiwygEditor', 'XenForo.BbmCustomEditor');

}(jQuery, this, document, 'undefined');