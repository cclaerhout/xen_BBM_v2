var Bbm = {};
!function($, window, document, _undefined)
{    
	Bbm.BbmArgToggle = function($e)
	{
		$e.click(function(){
			$(this).siblings('.argBox').toggle();
		});	
	}

	Bbm.XenDefaultSprite = function($form)
	{
		var 	xenSpriteUrl = $form.data('xensprite'),
			url = 'input[name="redactor_image_url"]',
			mode = 'input[name="redactor_sprite_mode"]',
			x = 'input[name="redactor_sprite_params_x"]',
			y = 'input[name="redactor_sprite_params_y"]';
		
		$output = $form.find($form.data('icon-output'));
		$url     = $form.find(url);
		$sprite  = $form.find(mode);
		$x       = $form.find(x);
		$y       = $form.find(y);

		$('#bbm_redactor_btn_config').find('input').bind('change', function(e)
		{
			if ($sprite.is(':checked'))
			{
				$output.attr('src', 'styles/default/xenforo/clear.png').css(
				{
					width: '24px',
					height: '24px',
					background: 'url(' + $url.val() + ') no-repeat ' + $x.val() + 'px ' + $y.val() + 'px'
				});
			}
			else
			{
				$output.attr('src', $url.val()).css(
				{
					width: 'auto',
					height: 'auto',
					background: 'none'
				});
			}
		});
	
		$form.find('#xenSpriteInsert').click(function(){
			$url.val(xenSpriteUrl).trigger('change');
			$('input[name="sprite_mode"]').prop('checked', true);
		});
	}

	XenForo.register('.argToggler','Bbm.BbmArgToggle');
	XenForo.register('form','Bbm.XenDefaultSprite');
}
(jQuery, this, document);