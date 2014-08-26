if(typeof Sedo == 'undefined') Sedo = {};

!function($, window, document, undefined)
{
	Sedo.BbmAdminButtons = function($buttonsArea) { this.__construct($buttonsArea); };
	Sedo.BbmAdminButtons.prototype = 
	{
		__construct: function($buttonsArea)
		{
			this.$buttonsArea = $buttonsArea;
			this.config_type = $buttonsArea.data('configType');
			this.$buttonsList = $buttonsArea.find('#ButtonsList');
			this.$target = $buttonsArea.find('#target');
			this.$newLine = $buttonsArea.find('#AddNewLine');

			var self = this,
				$selection = self.getSelectedEl();
				
			self.makeListSortable();

			self.saveUpdates = function(event, ui){
				var update, 
					$selection_ul = self.getSelectedUl();
			
				$selection_ul.each(function(index) {
					var btns = self._getBtnCodes($(this), 'data-tag'); //id:$(this).sortable('toArray')
			
					if(update){
						update = update + ',#,' + btns;
					}
					else{
						update = btns;
					}
				});	
		
				self.$target.val(update);
			};
		
			//Prevent blank config on first install
			self.saveUpdates(); 
			
			//Add new line function
			self.$newLine.click(function(){
		 		var $selection_ul = self.getSelectedUl(),
		 			i = $selection_ul.size(),
		 			config_type = self.config_type;

				$buttonsArea.find('#list_'+config_type+'_'+i).after(
					'<div class="deleteme"><span>X</span></div><ul id="list_'+config_type+'_'+(i+1)+'" class="selection connectedSortable connectedTools ui-sortable"></ul>'
				);
	
				self.makeListSortable();
			});

			//Delete line function (and put back button in buttons list)
			$selection.on('click', '.deleteme', function(){
				var $this = $(this);
				
				$this.next('ul').children().appendTo(self.$buttonsList);
				$this.next('ul').remove();
				$this.remove();
				self.updateID();
				self.saveUpdates(event, ui); 
			});
			
			//Create a new separator
		 	$buttonsArea.find('#CreateSeparator').click(function() {
				self.$buttonsList.append('<li data-tag="separator" class="separator"><span>|</span></li>');
 		 	});

			//Reset overlay
			self.resetOverlay($buttonsArea);
			
			//Init toolip (jQuery UI)
			self.tooltipConfig = {
				position: { 
					my: 'left-12 bottom',
					at: 'right top+2' 
				},
				tooltipClass: 'bbm-buttons'
			};
			
			self.$buttonsList.add($selection).tooltip(self.tooltipConfig);
		},
		_getBtnCodes: function($ul, attr)
		{
			var btns = [],
				$li = $ul.children().each(function(){
					btns.push($(this).attr(attr));
				});
			
			return btns.join();
		},
		makeListSortable: function()
		{
			var self = this;
			
			self.$buttonsArea.find('.connectedSortable').sortable({
				update: function(){
					self.saveUpdates();
				},
				start: function(){
					var $selection = self.getSelectedEl();
					self.$buttonsList.add($selection).tooltip('destroy');
				
				},
				stop: function(){
					var $selection = self.getSelectedEl(),
						$tooltip = self.$buttonsList.add($selection);
					$tooltip.tooltip(self.tooltipConfig);
				},
				helper: 'original',
				connectWith: '.connectedSortable'
			}).disableSelection();	
		},
		updateID: function()
		{
			var self = this, i = 1,
				$selection_ul = self.getSelectedUl();
			
			$selection_ul.each(function(index) {
				var $this = $(this),
					config_type = self.config_type;
					
				$this.attr("id","list_"+config_type+"_"+i);
				i++;
			});
		},
		resetOverlay: function($buttonsArea)
		{
			var self = this,
				$razButtons = $buttonsArea.find('#razButtons'),
				$razConfirm = $buttonsArea.find('#razConfirm'),
				$defaultConfig = $buttonsArea.find('#defaultConfig');
			
			var razOvl = $razButtons.overlay({
			 	mask: {
			 		color: '#ff1919',
			 		maskId: 'bbmMask',
					loadSpeed: 200,
					opacity: 0.60
				},
				onLoad: function(e) {
					var $myOverlay = this.getOverlay().insertAfter($('#bbmMask'));
				},
	 			closeOnClick: false,
	 			top: 'center'
			});

		 	$razConfirm.click(function() {
				var line = 0,
					defaultConfig = $('#defaultConfig').val().split(','),
					$defaultButtons = $('<div />'),
					$lines = self.getSelectedUl(),
					$selection = self.getSelectedEl(),
					$containerGlobal = $buttonsArea.find('#container_global');
				
				$.each(defaultConfig, function(i, v){
					if(v != 'separator' && v != '#'){
						var $btn = $containerGlobal.find('[data-tag="'+v+'"]').detach();
						$defaultButtons.append($btn);
					} else if(v == 'separator') {
						$defaultButtons.append('<div class="_separator" />');
					} else if(v == '#') {
						$defaultButtons.append('<div class="_carriage" />');
					}			
				});
				
				$lines.children().not('.separator').appendTo(self.$buttonsList);
				$selection.children().empty().not(':first').remove();
	
				$defaultButtons.children().each(function(i){
					var $this = $(this);
					
					if($this.hasClass('_carriage')) {
						self.$newLine.trigger('click');
						$lines = self.getSelectedUl();//dom has been updated
						line++;
					} else if($this.hasClass('_separator')) {
						$lines.eq(line).append('<li data-tag="separator" class="separator"><span>|</span></li>');
					} else {
						$lines.eq(line).append($this);
					}
				});
	
				self.updateID();
				self.saveUpdates();		
	 	 	});			
		},
		getSelectedEl: function()
		{
			return this.$buttonsArea.find('#container_select');
		},
		getSelectedUl: function()
		{
			return this.$buttonsArea.find('#container_select').children('ul');
		}		
	};

	Sedo.BbmAdminButtonsAjax = function($form)
	{
		$form.bind('AutoValidationComplete', function(e)
		{
			if (e.ajaxData.templateHtml)
			{
				e.preventDefault();

				new XenForo.ExtLoader(e.ajaxData, function()
				{
					$(e.ajaxData.templateHtml).xfInsert('replaceAll', '#AjaxResponse');
				});
			}
		});
	};

	XenForo.register('#bbm_buttons_config', 'Sedo.BbmAdminButtons');
	XenForo.register('form.bbmButtonsConfig', 'Sedo.BbmAdminButtonsAjax');	
}
(jQuery, this, document);