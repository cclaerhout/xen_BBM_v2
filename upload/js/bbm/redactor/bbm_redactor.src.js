!function($, window, document, undefined)
{
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
			this.customButtons = this.redactor.opts.buttonsCustom;

			if(typeof this.redactor === undefined){
				return false;
			}

			//Override callbacks if needed
			if(typeof self.bbmButtons !== undefined)
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
						separator = data.bbCodeOptionsSeparator;
					
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