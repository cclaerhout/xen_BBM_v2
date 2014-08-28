if (typeof Bbm === 'undefined') var Bbm = {};
!function($, window, document, _undefined)
{    
	$.extend(Bbm, {
		BbmArgToggle: function($e)
		{
			$e.click(function(){
				$(this).siblings('.argBox').toggle();
			});	
		},
		XenDefaultSprite: function($form)
		{
			var xenSpriteUrl = $form.data('xensprite'),
				url = 'input[name="redactor_image_url"]',
				mode = 'input[name="redactor_sprite_mode"]',
				x = 'input[name="redactor_sprite_params_x"]',
				y = 'input[name="redactor_sprite_params_y"]';
			
			var $output = $form.find($form.data('icon-output')),
				$url     = $form.find(url),
				$sprite  = $form.find(mode),
				$x       = $form.find(x),
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
		},
		QuattroIconsHelper: function($helper)
		{
			$helper.hide();
			
			var $form = $helper.parents('form');
			
			if(!$form.length)
				return;
			
			var $btnType = $form.find('[name="quattro_button_type"]'),
				$btnOpt = $form.find('[name="quattro_button_type_opt"]')
				$xenSet = $helper.find('#quattro_icons_xen'),
				$mceSet = $helper.find('#quattro_icons_mce'),
				$faSet =  $helper.find('#quattro_fa_helper'),
				$allset = $helper.find('.quattro_icons_set_wrap');
			
			var resetActiveIcon = function(){
				$helper.find('li.active').removeClass('active');
			};

			var resetAll = function(){
				$helper.hide();	
				$allset.hide();
				$faSet.hide();
				resetActiveIcon();
			};
			
			var displayManager = function($manualSrc, focusText){
				var $this = ($manualSrc instanceof jQuery) ? $manualSrc : $(this), val = $this.val();

				resetAll();

				var selectIcon = function($set){
					val = $btnOpt.val();
					$set.find('[data-unicode="'+val+'"]').parent().addClass('active');
				};

				if(val == 'icons_mce'){
					$helper.show();
					$mceSet.show();
					selectIcon($mceSet);
				}else if(val == 'icons_xen'){
					$helper.show();
					$xenSet.show();				
					selectIcon($xenSet);
				}else if(val == 'text' && focusText !== false){
					$btnOpt.focus();
				}
			};
			
			displayManager($btnType, false);
			$btnType.change(displayManager);
			
			$allset.find('.fs1').click(function(){
				var $this = $(this);
				resetActiveIcon();
				$this.parent().addClass('active');
				$btnOpt.val($this.data('unicode'));
			});
		},
		QuattroFaHelper: function($helper)
		{
			$helper.hide();
			var $form = $helper.parents('form');
			
			if(!$form.length)
				return;			

			var $btnType = $form.find('[name="quattro_button_type"]'),
				$btnOpt = $form.find('[name="quattro_button_type_opt"]'),
				$allset = $helper.find('.quattro_icons_set_wrap');

			var resetActiveIcon = function(){
				$helper.find('li.active').removeClass('active');
			};

			var resetAll = function(){
				$helper.hide();	
				$allset.hide();
				resetActiveIcon();
			};
			
			var displayManager = function($manualSrc, focusText){
				var $this = ($manualSrc instanceof jQuery) ? $manualSrc : $(this), val = $this.val();

				resetAll();

				var selectIcon = function($set){
					val = $btnOpt.val();
					$set.find('[data-fa="'+val+'"]').parent().addClass('active');
				};

				if(val == 'icons_fa'){
					$helper.show();
					selectIcon($helper);
				}
			};

			displayManager($btnType, false);
			$btnType.change(displayManager);				
		},		
		RedactorIconsHelper: function($helper)
		{
			var $imgSrc = $helper.find('.rih_src'),
				$redactorPanel = $helper.parents('#bbm_redactor_btn_config'),
				$title = $redactorPanel.find('.rih_title'),
				$imgUrl = $redactorPanel.find('[name="redactor_image_url"]'),
				$defaultSprite = $redactorPanel.find('#xenSpriteInsert'),
				$spriteMode = $redactorPanel.find('[name="redactor_sprite_mode"]'),
				$x = $redactorPanel.find('[name="redactor_sprite_params_x"]'),
				$y = $redactorPanel.find('[name="redactor_sprite_params_y"]'),
				spriteUrl = $helper.parents('form').data('xensprite');
			
			function onClick(e){
				var $this = $(this),
					top = $this.data('top');
				
				if($imgUrl.val != spriteUrl)
					$defaultSprite.trigger('click');
				
				if (!$spriteMode.is(':checked')){
					$spriteMode.prop('checked', true);
				}
				
				$y.val(top).trigger('change');
			}

			function onLoad(e){
				var $this = $(this),
					img = { w: this.width, h: this.height };
				
				var $ul = $('<ul class="rih_list" />');

				for(var i=0;i<img.h; i = i+32)
				{
					var $li = $('<li />'),
						$img = $('<img alt="" src="styles/default/xenforo/clear.png" />')
							.css('backgroundPosition', '3px  -'+i+'px')
							.data('top', -i)
							.appendTo($li)
							.click(onClick);
					$li.appendTo($ul);
				}
				
				$helper.append($ul);

				$title.click(function(){
					$ul.toggle();
				});				
			}

			$imgSrc.load(onLoad);
		},
		RedactorFaHelper: function($helper)
		{
			$helper.hide();
			var $form = $helper.parents('form');
			
			if(!$form.length)
				return;			

			var $btnType = $form.find('[name="redactor_button_type"]'),
				$btnOpt = $form.find('[name="redactor_button_type_opt"]'),
				$iconWrapper = $form.find('#redactor_icon_wrapper');

			var resetActiveIcon = function(){
				$helper.find('li.active').removeClass('active');
			};

			var resetAll = function(){
				$helper.hide();	
				resetActiveIcon();
			};
			
			var displayManager = function($manualSrc, focusText){
				var $this = ($manualSrc instanceof jQuery) ? $manualSrc : $(this), val = $this.val();

				resetAll();

				var selectIcon = function($set){
					val = $btnOpt.val();
					$set.find('[data-fa="'+val+'"]').parent().addClass('active');
				};

				if(val == 'icons_fa'){
					$helper.show();
					selectIcon($helper);
					$iconWrapper.hide();
				}else if(val =='text'){
					$iconWrapper.hide();
				}else{
					$iconWrapper.show();
				}
			};

			displayManager($btnType, false);
			$btnType.change(displayManager);				
		},
	});

	XenForo.register('.argToggler','Bbm.BbmArgToggle');
	XenForo.register('form','Bbm.XenDefaultSprite');
	XenForo.register('#quattro_icon_helper','Bbm.QuattroIconsHelper');
	XenForo.register('#quattro_fa_helper','Bbm.QuattroFaHelper');
	XenForo.register('#redactorIconsHelper','Bbm.RedactorIconsHelper');
	XenForo.register('#redactor_fa_helper','Bbm.RedactorFaHelper');	
	
}
(jQuery, this, document);