!function($, window, document, undefined)
{
	var xenCreate = XenForo.create,
		BbCodeWysiwygEditorToLoad = false, BbmCustomEditorLoaded = false;

	XenForo.create = function(className, element, extra)
	{
		if(className == 'XenForo.BbCodeWysiwygEditor') {
			className = 'XenForo.bbmBridge';
		}
		
		xenCreate(className, element, extra);

		if(className == 'XenForo.BbmCustomEditor') {
			BbmCustomEditorLoaded = true;
			if(BbCodeWysiwygEditorToLoad){
				$(document).trigger('bbmLoadEd');
			}
		}		
	}

	XenForo.bbmBridge = function($textarea){
		var self = this;

		var loadEditor = function(){
			new XenForo.BbCodeWysiwygEditor($textarea);
		};
		
		if(!BbmCustomEditorLoaded){
			$(document).on('bbmLoadEd', function(e){
				loadEditor();
			});
			BbCodeWysiwygEditorToLoad = true;
		}else{
			loadEditor();
		}
	}
}
(jQuery, this, document);