!function($, window, document, _undefined)
{    
	XenForo.BbmSpoiler = function($element)
	{
		//JS detected - init (inline-block tested on IE7-8)
		$element.find('.bbm_spoiler_noscript').addClass('bbm_spoiler').removeClass('bbm_spoiler_noscript');
		$element.find('.button').css('display','inline-block');

		//Toggle function
		$element.find('.button').toggle(
			function () {
				$(this).parent().parent('.bbmSpoilerBlock').children('.quotecontent').children('.bbm_spoiler').show();
				$(this).children('.bbm_spoiler_show').hide();
				$(this).children('.bbm_spoiler_hide').show();
				
			},
			function () {
				$(this).parent().parent('.bbmSpoilerBlock').children('.quotecontent').children('.bbm_spoiler').hide();
				$(this).children('.bbm_spoiler_show').show();
				$(this).children('.bbm_spoiler_hide').hide();
			}
		);		
	}

	 XenForo.register('.bbmSpoilerBlock','XenForo.BbmSpoiler')
}
(jQuery, this, document);