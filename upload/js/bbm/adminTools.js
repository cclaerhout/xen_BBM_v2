var Bbm = {};
!function($, window, document, _undefined)
{    
	Bbm.BbmArgToggle = function($e)
	{
		$e.click(function(){
			$(this).siblings('.argBox').toggle();
		});	
	}

	 XenForo.register('.argToggler','Bbm.BbmArgToggle')
}
(jQuery, this, document);