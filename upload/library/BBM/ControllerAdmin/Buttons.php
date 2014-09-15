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
			in_array($config_data['config_type'], $this->_protectedConfigNames)
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

	protected function _editorConfig($config_type, $config_ed)
	{
		$this->checkEditorConfigCompatibility($config_ed);

		/*Get config*/
		$config =  $this->_getButtonsModel()->getConfigByType($config_type);
		$config_buttons_full = $this->_getConfigFull($config);

      		/*Get buttons*/
      		list( 	$availableButtons, $blankConfigAvailableButtons, 
			$xenButtonsList, $blankXenButtonsList, 
			$xenButtons, $bbmButtons ) = $this->getAllButtons($config_type, $config_ed);

		/***
		*	Look inside config which buttons are already inside the editor and take them back from the buttons list
		***/
		if(!$config_buttons_full)
		{
			$viewParams = array(
				'config' => $config,
				'buttonsAvailable' => $blankConfigAvailableButtons,
				'lines' => $blankXenButtonsList,
				'default' => $xenButtonsList,
				'customCssButtons' => $this->_buttonsWithCustomCss,
				'permissions' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
	 		);
		
			return $this->responseView('Bbm_ViewAdmin_Buttons_Config', 'bbm_buttons_config', $viewParams);
		}
		else
		{
			$selectedButtons = $config_buttons_full;

	      		foreach($selectedButtons as $key => $selectedButton)
	      		{
				if(!isset($selectedButton['tag']))
				{
					//All buttons must have a tag, if it doesn't there had been a pb somewhere
					unset($selectedButtons[$key]);
					continue;
				}
				
				$refKey = $selectedButton['tag'];
	
	      			if((!in_array($refKey, array('separator', 'carriage'))) AND !isset($availableButtons[$refKey]))
	      			{
	      				//If a button has been deleted from database, hide it from the the selected button list (It shoudn't happen due to actionDelete function)
	      				unset($selectedButtons[$key]);
	      			}
	      			else
	      			{
	      				//Hide all buttons which are already used from the available buttons list
	      				unset($availableButtons[$refKey]);
	      			}
	      		}
			
			//Add button class to the config group too (fix a bug when an orphan button was edited directly from the button manager)
			$selectedButtons = $this->_addButtonCodeAndClass($selectedButtons, $config_ed);
	
			//Create a new array with the line ID as main key
			$lines = $this->_insertButtonsInLines($selectedButtons);

		}

		$viewParams = array(
			'config' => $config,
			'buttonsAvailable' => $availableButtons,
			'lines' => $lines,
			'default' => $xenButtonsList,
			'customCssButtons' => $this->_buttonsWithCustomCss,
			'permissions' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
 		);
 		
		return $this->responseView('Bbm_ViewAdmin_Buttons_Config', 'bbm_buttons_config', $viewParams);
	}

	protected function _getConfigFull($config)
	{
		if(empty($config['config_buttons_full']) || !isset($config['config_buttons_full']))
		{
			return false;
		}
		
		$check = $config = unserialize($config['config_buttons_full']);

		foreach($check as $k => $e)
		{
			if(!isset($e['tag']))
			{
				//There's a problem here, let's prevent any error
				unset($check[$k], $config[$k]);
				continue;
			}
			
			if( in_array($e['tag'], array('carriage', 'separator')) )
			{
				unset($check[$k]);
			}
		}

		if(empty($check))
		{
			return false;
		}
		
		return $config;
	}

	protected function _insertButtonsInLines(array $buttons)
	{
		$lines = array();
		$line_id = 1;
		$hasButtons = false;

		foreach($buttons as $button)
		{
			if($button['tag'] == 'carriage')
			{
				if($hasButtons)
				{
					$line_id++;
					$hasButtons = false;
				}
			}
			else
			{
				$lines[$line_id][] = $button;
				$hasButtons = true;
			}
		}

		return $lines;	
	}

	public function checkEditorConfigCompatibility($config_ed)
	{
		list($mceSupport, $redactorSupport) = BBM_Helper_Editors::getCompatibility();
		
		if($config_ed == 'xen' && !$redactorSupport)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_redactor_unsupported'), 404));		
		}
		
		if($config_ed == 'mce' && !$mceSupport)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_config_mce_unsupported'), 404));
		}
	}

	public function getAllButtons($config_type, $config_ed)
	{
      		$xenButtons = $this->_getXenButtons($config_type, $config_ed);
      		$bbmButtons =  $this->_getBbmBbCodeModel()->getBbCodesWithButton();

		if(!empty($xenButtons['extraButtons']))
		{
			/***
			 * Add solo+custom buttons for XenForo Redactor
			 * The extraButtons key doesn't have XenForo default buttons that are supposed to be already in the selected area
			 */
			$blankConfigAvailableButtons = $this->_addButtonCodeAndClass(
				array_merge($xenButtons['extraButtons'], $bbmButtons),
				$config_ed
			);
		}
		else
		{
			$blankConfigAvailableButtons = $this->_addButtonCodeAndClass($bbmButtons, $config_ed);
		}
		
		$availableButtons = $this->_addButtonCodeAndClass(
			array_merge($xenButtons['buttons'], $bbmButtons),
			$config_ed
		);

		$xenButtonsList = $xenButtons['list'];
		
		$blankXenButtonsList = $xenButtons['blankConfig'];

		foreach($blankXenButtonsList as &$buttonsInLine)
		{
			if(empty($buttonsInLine))
			{
				continue;
			}

			$buttonsInLine = $this->_addButtonCodeAndClass($buttonsInLine, $config_ed);		
		}

		return array(
			$availableButtons, $blankConfigAvailableButtons, 
			$xenButtonsList, $blankXenButtonsList, 
			$xenButtons, $bbmButtons
		);
	}

	public function actionPostConfig()
	{
		$this->_assertPostOnly();

		$config_id = $this->_input->filterSingle('config_id', XenForo_Input::STRING);
		$config_type = $this->_input->filterSingle('config_type', XenForo_Input::STRING);
		$config_ed = $this->_input->filterSingle('config_ed', XenForo_Input::STRING);
		$config_buttons_order = $this->_input->filterSingle('config_buttons_order', XenForo_Input::STRING);

      		/*Get buttons*/
      		list( 	$availableButtons, $blankConfigAvailableButtons, 
			$xenButtonsList, $blankXenButtonsList, 
			$xenButtons, $bbmButtons ) = $this->getAllButtons($config_type, $config_ed);

		if(empty($config_buttons_order))		
		{
			 //If user has disabled JavaScrit, set a default layout to prevent to register a blank config in the database
			$config_buttons_order = $xenButtons['list'];
		}
		
      		/*Get buttons*/
      		$selected_buttons =  explode(',', $config_buttons_order);

      		$config_buttons_full = array();
      		$config_buttons_order = array(); //Reset to clean the variable during the loop
      		$hasOneValidButton = false;

      		foreach ($selected_buttons as $selected_button)
      		{
			//To prevent last 'else' can't find any index, id must be: id array = id db = id js (id being separator)
			$error = false;
			
			if(in_array($selected_button, array('|', 'separator')))
			{
				$config_buttons_full[] = array('tag' => 'separator', 'button_code' => '|');
      			}
      			elseif($selected_button == '#')
      			{
      				$config_buttons_full[] = array('tag' => 'carriage', 'button_code' => '#');
      			}
      			else
      			{
      				if(isset($availableButtons[$selected_button], $availableButtons[$selected_button]['tag'])) //Check if the button hasn't been deleted
      				{
      					$config_buttons_full[] = $availableButtons[$selected_button];
      					$hasOneValidButton = true;
      				}
      				else
      				{
      					$error = true;
      				}
			}
			
			if(!$error)
			{
				$config_buttons_order[] = $selected_button;
			}
      		}

		//Get back the buttons order variable once it has been surely cleaned
		if(!empty($config_buttons_order) && $hasOneValidButton)
		{
			$config_buttons_order = implode(',', $config_buttons_order);
			$config_buttons_full = serialize($config_buttons_full);
		}
		else
		{
			//Raz
			$config_buttons_order = '';
			$config_buttons_full = '';			
		}

		//Save in Database
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
					'ajaxresponse' => str_replace('separator', '|', $config_buttons_order),
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

	protected function _getXenButtons($config_type, $config_ed)
	{
		if($config_ed == 'mce')
		{
			return $this->_xenMceButtons($config_type);
		}
		elseif($config_ed == 'xen')
		{
			return $this->_xenRedactorButtons($config_type);		
		}
	}
	
	protected $bbmAvailableButtons = array();

	protected function _xenRedactorButtons($config_type)
	{
		$xenCurrentVersionId = XenForo_Application::get('options')->get('currentVersionId');
		$defaultMenuButtons = array();
		$extraButtons = array();
		$xenCustomBbCodes = BBM_Helper_Bbm::getXenCustomBbCodes(true);

		if($xenCurrentVersionId < 1030031) 
		{
			/* XenForo 1.2 - max: 1020570*/
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
			
			array_push($defaultMenuButtons, 'alignment', 'draft');
		}
		else
		{
			/* XenForo 1.3 */
			$redactorButtonsMap = array(
				array('removeformat', 'switchmode'),
				array('bold', 'italic', 'underline'),
				array('fontcolor', 'fontsize', 'fontfamily'),
				array('createlink', 'unlink'),
				array('alignment'), //menu
				array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
				array('smilies', 'image', 'media'),
				array('insert'), //menu
				array('draft'), //menu
				array('undo', 'redo')
			);

			array_push($defaultMenuButtons, 'alignment', 'draft', 'insert');

			if($xenCurrentVersionId >= 1030033) //1.3 beta 3
			{
				$extraButtons = array(
					'draftsave', 'draftdelete',
					'insertquote', 'insertspoiler', 'insertcode', 'deleted'
				);
			}
		}
		
		$prefix = '-';//to prevent clashes with bbm bbcodes // to do: check if still needed
		$list = '';
		
		foreach($redactorButtonsMap as $i => $buttonsGroup)
		{
			foreach($buttonsGroup as &$buttonCode)
			{
				$buttonCode = $prefix.$buttonCode;
			}
			
			$buttonsGroup = implode(',', $buttonsGroup);

			$separator = ($i == 0) ? '' : ',separator,';
			$list .= $separator.$buttonsGroup;
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
				$extraClass = '';
				$tag = ($xen_code[0] == $prefix) ? substr($xen_code, 1) : $xen_code;

				
				if(in_array($tag, $defaultMenuButtons))
				{
					$extraClass = 'menu';	
				}

      				if($xen_code != 'separator')
      				{
      					$blankConfig[$i][] = $buttons[$xen_code] = array(
      						'tag' => $xen_code,
      						'button_code' => $xen_code,
      						'icon_set' => '',
      						'icon_class' =>  '',
      						'icon_set_class' => '',
      						'class' => 'xenButton',
      						'extraClass' => $extraClass
      					);
      				}
      				else
      				{
      					$blankConfig[$i][] = array(
      						'tag' => 'separator',
      						'button_code' => $xen_code,
      						'class' => 'xenButton',
      						'extraClass' => $extraClass
      					);					
      				}
      			}
      		}

		/*Extra buttons*/
		if(!empty($extraButtons))
		{
      			foreach($extraButtons as $key => $xen_code)
      			{
				$xen_code = $prefix.$xen_code;
     				$extraButtons[$key] = $buttons[$xen_code] = array(
      					'tag' => $xen_code,
      					'button_code' => $xen_code,
      					'icon_set' => '',
      					'icon_class' =>  '',
      					'icon_set_class' => '',
      					'class' => 'xenButton',
      					'extraClass' => 'solo'
      				);
      			}
		}

		/*Custom BbCodes buttons*/
		if(!empty($xenCustomBbCodes))
		{
			foreach($xenCustomBbCodes as $xen_code)
			{
	     			$xenCustKey = "custom_{$xen_code}";
	     			$extraButtons[] = $buttons[$xenCustKey] = array(
	      				'tag' => $xenCustKey,
	      				'button_code' => $xenCustKey,
	      				'icon_set' => '',
	      				'icon_class' =>  '',
	      				'icon_set_class' => '',
	      				'class' => 'xenButton',
	      				'extraClass' => 'xenCustom'
	      			);			
			}
		}

		$this->bbmAvailableButtons = $buttons;

		return array(
			'list' => $list,			//will be used as a fallback if user has disable javascript - to do: (or if user wants to reset)
			'buttons' => $buttons, 			//will be merged with other buttons
			'blankConfig' => $blankConfig, 		//will be used for blank configs
			'extraButtons' => $extraButtons
		);
	}

	protected function _xenMceButtons($config_type)
	{
		$direction = (in_array($config_type, array('ltr', 'rtl'))) ? $config_type : 'ltr';
	
		$list = $this->_getButtonsModel()->getQuattroReadyToUse($direction, 'string', ',', 'separator', '#');
		$arrayLines = $this->_getButtonsModel()->getQuattroReadyToUse($direction, 'array', ',', 'separator');
		$fontsMap =  $this->_getButtonsModel()->getQuattroFontsMap();

		$xenCustomBbCodes = BBM_Helper_Bbm::getXenCustomBbCodes(true);

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
      						'class' => 'xenButton',
      						'extraClass' => ''
      					);
      				}
      				else
      				{
      					$blankConfig[$i][] = array(
      						'tag' => 'separator',
      						'button_code' => $xen_code,
      						'class' => 'xenButton',
      						'extraClass' => ''
      					);					
      				}
      			}
      		}	

		/*Custom BbCodes buttons*/
		$extraButtons = array();
		if(!empty($xenCustomBbCodes))
		{
			foreach($xenCustomBbCodes as $xen_code)
			{
	     			$xenCustKey = "custom_{$xen_code}";
	     			$extraButtons[] = $buttons[$xenCustKey] = array(
	      				'tag' => $xenCustKey,
	      				'button_code' => $xenCustKey,
	      				'icon_set' => '',
	      				'icon_class' =>  '',
	      				'icon_set_class' => '',
	      				'class' => 'xenButton',
	      				'extraClass' => 'xenCustom'
	      			);			
			}
		}
	
		$this->bbmAvailableButtons = $buttons;
		
		return array(
			'list' => $list,		//will be used as a fallback if user has disable javascript - to do: (or if user wants to reset)
			'buttons' => $buttons, 		//will be merged with other buttons
			'blankConfig' => $blankConfig, 	//will be used for blank configs
			'extraButtons' => $extraButtons			
		);
	}
	
	protected $allDataButtons = array();
	
	protected function _checkAddonStateForButton($button)
	{
		if( !isset($button['tag']) )
		{
			return null;
		}

		$tag = $button['tag'];

		if(empty($this->allDataButtons))
		{
			$this->allDataButtons = $this->_getBbmBbCodeModel()->getBbCodesWithButton(true);
		}
		
		if( !isset($this->allDataButtons[$tag]) || empty($this->allDataButtons[$tag]['bbcode_addon']) )
		{
			return null;
		}

		$addonId = $this->allDataButtons[$tag]['bbcode_addon'];
		
		return BBM_Helper_Bbm::checkIfAddonActive($addonId);
	}

	protected $_buttonsWithCustomCss = array();

	protected function _addButtonCodeAndClass($buttons, $config_ed)
	{	
		foreach($buttons as $k => &$button)
		{
			if(empty($button['button_code']))
			{
				$button['button_code'] = (!empty($button['custCmd'])) ? $button['custCmd'] : 'bbm_'.$button['tag'];
			}
			
			if(isset($button['class']) && $button['class'] == 'xenButton')
			{
				$tag = $button['tag'];
		
				if( isset($this->bbmAvailableButtons[$tag]) && !empty($this->bbmAvailableButtons[$tag]['extraClass']) )
				{
					$button['extraClass'] = $this->bbmAvailableButtons[$tag]['extraClass'];
				}
			}
			else
			{
				if($button['tag'][0] == '@')
				{
					$button['class'] = 'orphanButton';
				}
				else
				{
					$button['class'] = 'activeButton';			
				}

				$addonDisable = ($this->_checkAddonStateForButton($button) === false);
			
				if((isset($button['active']) && !$button['active']) || $addonDisable)
				{
					$button['class'] = 'unactiveButton';

					if($addonDisable)
					{
						$button['class'] .= ' addonDisabled';
					}
				}
			}

			/*MCE CONFIG*/
			if($config_ed == 'mce')
			{
				$btnType = (isset($button['quattro_button_type'])) ? $button['quattro_button_type'] : '';
	
				$button['safetag'] = str_replace('@', 'at_', $button['tag']);			
	
				if(	( $btnType && !in_array($btnType, array('text', 'manual', 'icons_fa')) )
					&& 
					( isset($button['class']) && $button['class'] != 'xenButton' )
				)
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
				
				if($btnType == 'icons_fa' && !empty($button['quattro_button_type_opt']))
				{
					$button['icon_set'] = 'icons_fa';
					
					if(empty($button['icon_class']))
					{
						$button['icon_class'] = "bbm_fa fa $button[quattro_button_type_opt]";
					}
					else
					{
						$button['icon_class'] = "bbm_fa fa $button[quattro_button_type_opt] $button[icon_class]";
					}
				}
			}

			/*MCE CONFIG*/
			if($config_ed == 'xen')
			{
				$btnType = (isset($button['redactor_button_type'])) ? $button['redactor_button_type'] : '';

				if($btnType == 'icons_fa' && !empty($button['redactor_button_type_opt']))
				{
					$button['icon_set'] = 'icons_fa';
					
					if(empty($button['icon_class']))
					{
						$button['icon_class'] = "bbm_fa fa $button[redactor_button_type_opt]";
					}
					else
					{
						$button['icon_class'] = "bbm_fa fa $button[redactor_button_type_opt] $button[icon_class]";
					}
				}			
			}
			
			/*Add a cleanName key for the template*/
			$button = $this->_addCleanNameToButton($button);
		}
		
		return $buttons;
	}

	protected function _addCleanNameToButton($button)
	{
		if(!isset($button['tag']))
		{
			return $button;
		}

			$tagName = $button['tag'];

			if($tagName[0] == '-')
			{
				$cleanName = substr($tagName, 1);
			}
			elseif($tagName == 'separator')
			{
				$cleanName = '|';
			}
			elseif(strpos($tagName, 'custom_') === 0)
			{
				$cleanName = substr($tagName, 7);
			}
			else
			{
				$cleanName = $tagName;
			}

			$button['cleanName'] = $cleanName;
		
		return $button;
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