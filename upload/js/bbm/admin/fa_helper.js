if (typeof Bbm === 'undefined') var Bbm = {};
!function($, window, document, undefined)
{
	Bbm.faHelper = function($helper)
	{
		$helper.each(function(){
			var $this = $(this),
				$fa = $(this).find('li'),
				target = $this.data('target'),
				$target = $(target);

			var resetActiveIcon = function(){
					$this.find('li.active').removeClass('active');
			};
				
			$fa.click(function(){
				var $btn = $(this),
					faClass = $btn.children('.fa').data('fa');

				if(!$target.length){
					console.debug('Target "'+target+'" is missing');
					return;
				}

				resetActiveIcon();
				$btn.addClass('active');
				$target.val(faClass);
			});
		});
	};
	
	XenForo.register('.bbm-fa-helper','Bbm.faHelper');	
}
(jQuery, this, document);