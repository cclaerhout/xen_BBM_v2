<?php

class BBM_ControllerAdmin_Buttons extends XenForo_ControllerAdmin_Abstract
{
	protected $protectedConfigNames = array('ltr', 'rtl', 'redactor');
	
	public function actionIndex()
	{
		$configs = $this->_getButtonsModel()->getOnlyCustomConfigs();
		$configs = $this->_checkIfConfigNameExists($configs);

		list($mceSupport, $redactorSupport) = BBM_Helper_Editors::getCompatibility();

		$viewParams = array(
			'mceSupport' => $mceSupport,
			'redactorSupport' => $redactorSupport,
			'configs' => $configs,
			'permsBbm' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
		);

		return $this->responseView('Bbm_ViewAdmin_Buttons_Homepage', 'bbm_buttons_homepage', $viewParams);
	}

	public function actionAddEditConfig()
	{
		$config = array();
		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
		
		if($config_id)
		{
			$config = $this->_getBbmConfigOrError($config_id);
		}

		return $this->_actionAddEditConfig($config);
	}

	protected function _checkIfConfigNameExists($configs)
	{
		foreach($configs as &$config)
		{
			if(empty($config['config_name']))
			{
				$config['config_name'] = $config['config_type'];
			}
		}
		
		return $configs;
	}

	protected function _getBbmConfigOrError($config_id)
	{
		$config = $this->_getButtonsModel()->getConfigById($config_id);

		if (!$config)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_not_found'), 404));
		}

		if(is_array($config) && isset($config['config_type']))
		{
			$title = 'button_manager_config_' . $config['config_type'];
			$config['phrase'] = new XenForo_Phrase($title);
		}

		return $config;
	}

	protected function _actionAddEditConfig($config)
	{
		list($mceSupport, $redactorSupport) = BBM_Helper_Editors::getCompatibility();

		$viewParams = array(
			'mceSupport' => $mceSupport,
			'redactorSupport' => $redactorSupport,
      			'config' => $config
      		);
      		
      		return $this->responseView('Bbm_ViewAdmin_Config_Add_Edit', 'bbm_buttons_config_add_edit', $viewParams);
	}

	public function actionSaveConfig()
	{
      		$this->_assertPostOnly();

      		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);

		$dwInput = $this->_input->filter(array(
				'config_type' => XenForo_Input::STRING,
				'config_name' => XenForo_Input::STRING,
				'config_ed' => XenForo_Input::STRING
			)
		);

      		$dw = XenForo_DataWriter::create('BBM_DataWriter_Buttons');

    		if (!empty($config_id) || $this->_getButtonsModel()->getConfigById($config_id))
    		{
      			$dw->setExistingData($config_id);
    		}

		$dw->bulkSet($dwInput);
      		$dw->save();

      		return $this->responseRedirect(
      			XenForo_ControllerResponse_Redirect::SUCCESS,
      			XenForo_Link::buildAdminLink('bbm-buttons')
		);	
	}

	public function actionDeleteConfig()
	{
		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
		$this->checkDeletePermissions($config_id);

		if ($this->isConfirmedPost())
		{
			return $this->_deleteData(
				'BBM_DataWriter_Buttons', 'config_id',
				XenForo_Link::buildAdminLink('bbm-buttons')
			);
		}
		else
		{
			$config = $this->_getBbmConfigOrError($config_id);

			$viewParams = array(
				'config' => $config
			);
			return $this->responseView('Bbm_ViewAdmin_Buttons_Delete', 'bbm_buttons_delete', $viewParams);
		}
	}

	public function checkDeletePermissions($config_id)
	{
		$config_data = $this->_getButtonsModel()->getConfigById($config_id);
		if(	isset($config_data['config_type']) 
			&& 
			in_array($config_data['config_type'], $this->protectedConfigNames)
		)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_type_protected'), 404));
		}
	}

	public function actionEditorConfigLtr()
	{
		$this->_mceReady();
		return $this->_editorConfig('ltr', 'mce');
	}

	public function actionEditorConfigRtl()
	{
		$this->_mceReady();
		return $this->_editorConfig('rtl', 'mce');
	}

	public function actionEditorConfigXen()
	{
		return $this->_editorConfig('redactor', 'xen');
	}

	public function actionEditorCust()
	{
		if (isset($_GET['config_type']))
		{
			//Edit from bbm buttons manager
			$config_type = $_GET['config_type'];
			$datas = $this->_getButtonsModel()->getConfigByType($config_type);
		}
		else
		{
			//Edit from bbm bbcodes manager
			$config_id = $this->_input->filterSingle('config_id', XenForo_Input::UINT);
			$datas = $this->_getButtonsModel()->getConfigById($config_id);
		}
	
		if (!$datas || !isset($datas['config_type']))
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_not_found'), 404));
		}	

		if($datas['config_ed'] == 'mce')
		{
			$this->_mceReady();
		}

		return $this->_editorConfig($datas['config_type'], $datas['config_ed']);
	}


	protected function _mceReady()
	{
		$quattroTable = $this->_getButtonsModel()->checkQuattroTable();
		
		if(!$quattroTable)
		{
			$viewParams = array(
				'error' => 'quattro'
			);
			
			return $this->responseView('Bbm_ViewAdmin_Buttons_Homepage_Error', 'bbm_buttons_homepage_error', $viewParams);
		}
	}

	protected function _editorConfig($configType, $configEd)
	{
		$this->checkEditorConfigCompatibility($configEd);

		//Get config and all buttons
		$xen = $this->_xenButtons($configType, $configEd);

		$buttons = $this->_getBbmBbCodeModel()->getBbCodesWithButton();
		$buttons = $this->_addButtonCodeAndClass($buttons);

		if($configEd == 'mce')
		{
			$buttons = $this->_addQuattroClass($buttons);
		}

		$config =  $this->_getButtonsModel()->getConfigByType($configType);

		/***
		*	Look inside config which buttons are already inside the editor and take them back from the buttons list
		***/
		if(empty($config['config_buttons_full']))
		{
			$viewParams = array(
				'config' => $config,
				'buttonsAvailable' => $buttons,
				'lines' => $xen['blankConfig'],
				'default' => $xen['list'],
				'customCssButtons' => $this->_buttonsWithCustomCss,
				'permissions' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
	 		);
		
			return $this->responseView('Bbm_ViewAdmin_Buttons_Config', 'bbm_buttons_config', $viewParams);
		}
		else
		{
			$buttons = array_merge($xen['buttons'], $buttons);
			$selectedButtons = unserialize($config['config_buttons_full']);

	      		foreach($selectedButtons as $key => $selectedButton)
	      		{
				if(!isset($selectedButton['tag']))
				{
					unset($selectedButtons[$key]);//Fix
					continue;
				}
	
	      			if((!in_array($selectedButton['tag'], array('separator', 'carriage'))) AND !isset($buttons[$selectedButton['tag']]))
	      			{
	      				//If a button has been deleted from database, hide it from the the selected button list (It shoudn't happen due to actionDelete function)
	      				unset($selectedButtons[$key]);
	      			}
	      			else
	      			{
	      				//Hide all buttons which are already used from the available buttons list
	      				unset($buttons[$selectedButton['tag']]);
	      			}
	      		}
			
			//Add button class to the config group too (fix a bug when an orphan button was edited directly from the button manager)
			$selectedButtons = $this->_addButtonCodeAndClass($selectedButtons);

			if($configEd == 'mce')
			{
				$selectedButtons = $this->_addQuattroClass($selectedButtons);
			}
	
			//Create a new array with the line ID as main key 
			$lines = array();
			$line_id = 1;
			
			foreach($selectedButtons as $button)
			{
				if($button['tag'] == 'carriage')
				{
					$line_id++;
				}
				else
				{
					$lines[$line_id][] = $button;
				}
			}
		}

		$viewParams = array(
			'config' => $config,
			'buttonsAvailable' => $buttons,
			'lines' => $lines,
			'default' => $xen['list'],
			'customCssButtons' => $this->_buttonsWithCustomCss,
			'permissions' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
 		);
 		
		return $this->responseView('Bbm_ViewAdmin_Buttons_Config', 'bbm_buttons_config', $viewParams);
	}

	public function checkEditorConfigCompatibility($configEd)
	{
		list($mceSupport, $redactorSupport) = BBM_Helper_Editors::getCompatibility();
		
		if($configEd == 'xen' && !$redactorSupport)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_redactor_unsupported'), 404));		
		}
		
		if($configEd == 'mce' && !$mceSupport)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_mce_unsupported'), 404));
		}
	}

	public function actionPostConfig()
	{
		$this->_assertPostOnly();

		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::STRING);
		$config_type = $this->_input->filterSingle('config_type', XenForo_Input::STRING);
		$config_ed = $this->_input->filterSingle('config_ed', XenForo_Input::STRING);
		$config_buttons_order = $this->_input->filterSingle('config_buttons_order', XenForo_Input::STRING);
		$config_buttons_order = str_replace('button_', '', $config_buttons_order); // 'buttons_' prefix was only use for pretty css		

      		//Get buttons
      		$xen = $this->_xenButtons($config_type, $config_ed);
      		$buttons = $this->_getBbmBbCodeModel()->getBbCodesWithButton();
      		$buttons = $this->_addButtonCodeAndClass($buttons);	
      		$buttons = array_merge($xen['buttons'], $buttons);		
		
		//If user has disable javascript... prevent to register a blank config in database
		if(empty($config_buttons_order))		
		{
			$config_buttons_order = $xen['list']; //Default Xen layout
		}
		
      		//Get selected buttons from user configuration and place them in an array
      		$selected_buttons =  explode(',', $config_buttons_order);

      		//Create the final data array
      		$config_buttons_full = array();

      		foreach ($selected_buttons as $selected_button)
      		{
      			if(!empty($selected_button))
      			{
      				//to prevent last 'else' can't find any index, id must be: id array = id db = id js (id being separator)
      				if($selected_button == 'separator')
      				{
      					$config_buttons_full[] = array('tag' => 'separator', 'button_code' => '|');
      				}
      				elseif($selected_button == '#')
      				{
      					$config_buttons_full[] = array('tag' => 'carriage', 'button_code' => '#');
      				}
      				else
      				{
      					if(isset($buttons[$selected_button])) //Check if the button hasn't been deleted
      					{
      						$config_buttons_full[] = $buttons[$selected_button];
      					}
      				}
      			}
      		}

		//Choose what to display in the ajax response
		$ajaxresponse =  str_replace('separator', '|', $config_buttons_order); // <= Just  for a nicer display

		//Save in Database		
		$config_buttons_full = serialize($config_buttons_full);

		$dw = XenForo_DataWriter::create('BBM_DataWriter_Buttons');
		if ($this->_getButtonsModel()->getConfigById($config_id))
		{
			$dw->setExistingData($config_id);
		}

		$dw->set('config_buttons_order', $config_buttons_order);
		$dw->set('config_buttons_full', $config_buttons_full);
		$dw->save();
		
		//Save into Registry
		$this->_getButtonsModel()->InsertConfigInRegistry();
		
		
		$displayME = 0;//Ajax Debug
		if($displayME == 1)
		{
			// Ajax response ("only run this code if the action has been loaded via XenForo.ajax()")
			if ($this->_noRedirect())
			{

				$viewParams = array(
					'ajaxresponse' => $ajaxresponse,
				);

				return $this->responseView(
					'BBM_ViewAdmin_Buttons_Buttons',
					'bbmbuttons_response',
					$viewParams
				);
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildPublicLink('bbm_buttons_config')
		);
	}

	protected function _xenButtons($configType, $configEd)
	{
		if($configEd == 'mce')
		{
			return $this->_xenMceButtons($configType);
		}
		elseif($configEd == 'xen')
		{
			return $this->_xenRedactorButtons($configType);		
		}
	}

	protected function _xenRedactorButtons($configType)
	{
		if(XenForo_Application::get('options')->get('currentVersionId') < 1030031) 
		{
			/* XenForo 1.2 */
			$redactorButtonsMap = array(
				array('switchmode'),
				array('removeformat'),
				array('bold', 'italic', 'underline', 'deleted'),
				array('fontcolor', 'fontsize', 'fontfamily'),
				array('createlink', 'unlink'),
				array('alignment'),
				array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
				array('smilies', 'image', 'media'),
				array('code', 'quote'),
				array('draft'),
				array('undo', 'redo')
			);
		}
		else
		{
			/* XenForo 1.3 */
			$redactorButtonsMap = array(
				array('removeformat', 'switchmode'),
				array('bold', 'italic', 'underline'), //'deleted'
				array('fontcolor', 'fontsize', 'fontfamily'),
				array('createlink', 'unlink'),
				array('alignment'),
				array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
				array('smilies', 'image', 'media'),
				array('insert'),
				//array('code', 'quote'),
				array('draft'),
				array('undo', 'redo')
			);		
		}
		
		$prefix = '-';//to prevent clashes with bbm bbcodes // to do: check if still needed
		$list = '';
		
		foreach($redactorButtonsMap as $i => $buttonsGroup)
		{
			$separator = ($i == 0) ? '' : ',separator,';

			foreach($buttonsGroup as &$buttonCode)
			{
				$buttonCode = $prefix.$buttonCode;
			}
			
			$buttons = implode(',', $buttonsGroup);
			$list .= $separator . $buttons;
		}
	
		$arrayLines = array('1' => $list);
		
      		/*Lazy: use the same code than for MCE to be sure it will work with the templates loop */
      		$buttons = array(); 
      		$blankConfig = array();
      		
      		foreach($arrayLines as $i => $line)
      		{
      			$xen_buttons =  explode(',', $line);
      			
      			foreach($xen_buttons as $xen_code)
      			{
      				if($xen_code != 'separator')
      				{
      					$blankConfig[$i][] = $buttons[$xen_code] = array(
      						'tag' => $xen_code,
      						'button_code' => $xen_code,
      						'icon_set' => '',
      						'icon_class' =>  '',
      						'icon_set_class' => '',
      						'class' => 'xenButton'
      					);
      				}
      				else
      				{
      					$blankConfig[$i][] = array(
      						'tag' => 'separator',
      						'button_code' => $xen_code,
      						'class' => 'xenButton'
      					);					
      				}
      			}
      		}	
	
		return array(
			'list' => $list,		//will be used as a fallback if user has disable javascript - to do: (or if user wants to reset)
			'buttons' => $buttons, 		//will be merged with other buttons
			'blankConfig' => $blankConfig 	//will be used for blank configs
		);
	
	}

	protected function _xenMceButtons($configType)
	{
		$direction = (in_array($configType, array('ltr', 'rtl'))) ? $configType : 'ltr';
	
		$list = $this->_getButtonsModel()->getQuattroReadyToUse($direction, 'string', ',', 'separator', '#');
		$arrayLines = $this->_getButtonsModel()->getQuattroReadyToUse($direction, 'array', ',', 'separator');
		$fontsMap =  $this->_getButtonsModel()->getQuattroFontsMap();

      		$buttons = array(); 
      		$blankConfig = array();

      		foreach($arrayLines as $i => $line)
      		{
      			$xen_buttons =  explode(',', $line);
      			
      			foreach($xen_buttons as $xen_code)
      			{
      				if($xen_code != 'separator')
      				{
	      				$iconSet = $fontsMap[$xen_code];

      					$blankConfig[$i][] = $buttons[$xen_code] = array(
      						'tag' => $xen_code,
      						'button_code' => $xen_code,
      						'icon_set' => ($iconSet == 'text') ? '' : $iconSet,
      						'icon_class' =>  'mce-ico',
      						'icon_set_class' => $this->_getMceClass($iconSet),
      						'class' => 'xenButton'
      					);
      				}
      				else
      				{
      					$blankConfig[$i][] = array(
      						'tag' => 'separator',
      						'button_code' => $xen_code,
      						'class' => 'xenButton'
      					);					
      				}
      			}
      		}	
	
		return array(
			'list' => $list,		//will be used as a fallback if user has disable javascript - to do: (or if user wants to reset)
			'buttons' => $buttons, 		//will be merged with other buttons
			'blankConfig' => $blankConfig 	//will be used for blank configs
		);
	}
	
	protected function _addButtonCodeAndClass($buttons)
	{	
		foreach($buttons as &$button)
		{
			if(empty($button['button_code']))
			{
				$button['button_code'] = (!empty($button['custCmd'])) ? $button['custCmd'] : 'bbm_'.$button['tag'];
			}
			
			if(isset($button['class']) && $button['class'] == 'xenButton')
			{
				//Needed when check the "config" array
				continue;
			}
			
			if($button['tag'][0] == '@')
			{
				$button['class'] = 'orphanButton';
			}
			else
			{
				$button['class'] = 'activeButton';			
			}

			if(isset($button['active']) && !$button['active'])
			{
				$button['class'] = 'unactiveButton';
			}
		}
		
		return $buttons;
	}

	protected $_buttonsWithCustomCss = array();
	
	protected function _addQuattroClass($buttons)
	{	
		foreach($buttons as &$button)
		{
			$btnType = (isset($button['quattro_button_type'])) ? $button['quattro_button_type'] : '';

			$button['safetag'] = str_replace('@', 'at_', $button['tag']);			

			if(	(isset($button['class']) && $button['class'] == 'xenButton')
				|| empty($btnType) || $btnType == 'manual'
			)
			{
				continue;
			}

			if($btnType != 'text')
			{
				switch ($btnType) {
					case 'icons_mce':
						$iconSet = 'tinymce';
						break;
					case 'icons_xen':
						$iconSet = 'xenforo';
						break;
					default: $iconSet = $btnType;
				}
				
				$button += array(
					'icon_set' => $iconSet,
					'icon_class' =>  'mce-ico',
					'icon_set_class' => $this->_getMceClass($iconSet)
				);
				
				$this->_buttonsWithCustomCss[$button['tag']] = $button;
			}
		}

		return $buttons;
	}

	protected function _getMceClass($iconSet)
	{	
		switch ($iconSet) {
			case 'tinymce':
				$iconSetClass = '';
				break;
			case 'text':
				$iconSetClass = 'mce-text';
				break;
			default: $iconSetClass = "mce-$iconSet-icons";
		}
		
		return $iconSetClass;
	}

	protected function _getBbmBbCodeModel()
	{
		return $this->getModelFromCache('BBM_Model_BbCodes');
	}

	protected function _getButtonsModel()
	{
		return $this->getModelFromCache('BBM_Model_Buttons');
	}	
}
//Zend_Debug::dump($contents);