<?php

class BBM_ControllerAdmin_BbCodes extends XenForo_ControllerAdmin_Abstract
{
	/*****
	*	Global Action permissions
	***/
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('bbm_BbCodesAndButtons');
	}

	/*****
	*	XenForo TinyMCE Buttons commands => needs to be protected
	***/
	private static $protectedXenCmd = 
		array('justifyright','justifycenter','justifyleft','indent','outdent','redo','undo',
		'removeformat','fontselect','fontsizeselect','forecolor','xenforo_smilies','bold','italic',
		'underline','strikethrough','bullist','numlist','link','unlink','image','xenforo_media',
		'xenforo_code','xenforo_custom_bbcode');

	/*****
	*	Index Page
	***/
	public function actionIndex()
	{
		$codes = $this->_getBbmBBCodeModel()->getAllBbCodes();
		$bbcodesWithCallbackErrors = $this->_classAndMethodIntegrityCheck($codes);
		
		//Add class
		foreach ($codes as &$code)
		{
			if($code['tag'][0] == '@')
			{
				$code['class'] = 'orphanButton';
				$code['orphanButton'] = true;
			}
			else
			{
				$code['class'] = 'normalButton';			
				$code['orphanButton'] = false;
			}
			
			$code['disableAddon'] = false;
			
			if (XenForo_Application::isRegistered('addOns'))
			{
				$enableAddons = XenForo_Application::get('addOns');
				if( !empty($code['bbcode_addon']) && !isset($enableAddons[$code['bbcode_addon']]) )
				{
					$code['class'] .= ' disableAddon';
					$code['disableAddon'] = true;
				}
			}
		}

		$viewParams = array(
			'codes' => $codes,
			'callbackErrors' => $bbcodesWithCallbackErrors,
			'permsBbm' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
 		);
		return $this->responseView('Bbm_ViewAdmin_Bbm_BbCodes_List', 'bbm_bb_codes_list', $viewParams);
	}

	/*****
	*	Check Callbacks Class & Method integrity
	***/
	protected function _classAndMethodIntegrityCheck(array $bbcodes)
	{
		$bbcodesWithCallbackErrors = array();
		
		foreach($bbcodes as $bbcode)
		{
			$type = $this->_getParsingType($bbcode);
			
			if( !in_array($type, array('php', 'template')) )
			{
				continue;
			}
			
			if($type == 'template' && !$bbcode['template_callback_class'])
			{
				continue;
			}
			
			if($type == 'php')
			{
				$class = $bbcode['phpcallback_class'];
				$method = $bbcode['phpcallback_method'];			
			}
			else
			{
				$class = $bbcode['template_callback_class'];
				$method = $bbcode['template_callback_method'];
			}
			
			$classCheck = ( class_exists($class) ) ? 0 : 1;
			$methodCheck = ( BBM_Helper_Bbm::callbackChecker($class, $method) ) ? 0 : 1;
			
			if( ($classCheck + $methodCheck) == 0 )
			{
				continue;
			} 
			
			$tag = $bbcode['tag'];
			$bbcodesWithCallbackErrors[$tag] = array(
				'tag_id' => $bbcode['tag_id'],
				'tag' => $tag,
				'title' => $bbcode['title'],
				'type' => $type,
				'status' => $bbcode['active'],
				'class' => $class,
				'classError' => $classCheck,
				'method' => $method,
				'methodError' => $methodCheck
			);
		}
		
		return $bbcodesWithCallbackErrors;
	}

	/*****
	*	Get parsing type: direct/php/template
	***/
	protected function _getParsingType(array $bbcode)
	{
		if($bbcode['start_range'] || $bbcode['end_range'])
		{
			return 'direct';
		}
		
		if($bbcode['phpcallback_method'])
		{
			return 'php';
		}
		
		if($bbcode['template_active'])
		{
			return 'template';
		}
		
		return false;
	}

	/*****
	*	Add a button
	***/
	public function actionAdd()
	{
		return $this->_getBbmBbCodeAddEditResponse(array());
	}


	/*****
	*	Edit a Bb Code
	***/
	public function actionEdit()
	{
		if(isset($_GET['tag']))
		{	
			//From the selected buttons line of the BM 
			$tagId = $this->_getBbmBBCodeModel()->getBbCodeIdFromTag($_GET['tag']);
		}
		else
		{
			$tagId= $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		}
		
		$code = $this->_getBbmBbCodeOrError($tagId);

		return $this->_getBbmBbCodeAddEditResponse($code);
	}


	/*****
	*	Manage Bb Code Add/Edit Return 
	*	The view template will not be the same if the request is coming from the Button Manager
	***/
	protected function _getBbmBbCodeAddEditResponse(array $code)
	{
		$code = $this->_getBbmBbCodePermissions($code);
		$addOnModel = $this->_getAddOnModel();

		list($mceSupport, $redactorSupport) = BBM_Helper_Editors::getCompatibility();

		$viewParams = array(
			'addOnOptions' => $addOnModel->getAddOnOptionsListIfAvailable(),
			'addOnSelected' => (isset($code['bbcode_addon']) ? $code['bbcode_addon'] : $addOnModel->getDefaultAddOnId()),
			'mceSupport' => $mceSupport,
			'redactorSupport' => $redactorSupport,
			'code' => $code,
			'quattroSets' => BBM_Helper_QuattroUnicode::getunicodeSets(),
			'activeTags' => $this->_getBbmBBCodeModel()->getActiveTags($code),
			'faFonts' => array_flip(BBM_Helper_FontAwesome::getFonts())
		);

		//Check if the edit is made from the button manager
		if (isset($_GET['bm']))
		{
			$viewParams['code']['bm_src'] = $_GET['bm'];
			return $this->responseView('Bbm_ViewAdmin_Bbm_BbCodes_Edit', 'bbm_bb_codes_edit_overlay', $viewParams);			
		}

		return $this->responseView('Bbm_ViewAdmin_Bbm_BbCodes_Edit', 'bbm_bb_codes_edit', $viewParams);
	}

	/*****
	*	Add permissions for some options (will be used in templates)
	***/
	protected function _getBbmBbCodePermissions(array $code)
	{
		if(empty($code))
		{
			$code = array(
				'button_usr' => array(), 
				'parser_usr' => array(),
				'view_usr' => array()				
			);
		}

		//Button users
		if(!empty($code['button_usr']))
		{
			$code['button_usr'] = unserialize($code['button_usr']);
		}

		$code['button_usr_list'] = $this->_getBbmBBCodeModel()->getUserGroupOptions($code['button_usr']);

		//Parser users
		if(!empty($code['parser_usr']))
		{
			$code['parser_usr'] = unserialize($code['parser_usr']);
		}

		$code['parser_usr_list'] = $this->_getBbmBBCodeModel()->getUserGroupOptions($code['parser_usr']);

		//View users
		if(!empty($code['view_usr']))
		{
			$code['view_usr'] = unserialize($code['view_usr']);
		}

		$code['view_usr_list'] = $this->_getBbmBBCodeModel()->getUserGroupOptions($code['view_usr']);

		return $code;
	}

	/*****
	*	Enable a Bb Code
	***/
	public function actionEnable()
	{
		$bbcodeId = $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		$this->_enableDisable($bbcodeId);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);
	}

	/*****
	*	Disable a Bb Code
	***/	
	public function actionDisable()
	{
		$bbcodeId = $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		$this->_enableDisable($bbcodeId);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);
	}

	/*****
	*	Disable Invalid Bb Codes
	***/	
	public function actionDisableInvalidBbcodes()
	{
		$bbcodeIds = $this->_input->filterSingle('invalid_callbacks', array(XenForo_Input::INT, 'array' => true));

		foreach($bbcodeIds as $bbcodeId)
		{
			$code = $this->_getBbmBbCodeOrError($bbcodeId);
			if($code['active'])
			{
				$this->_enableDisable($bbcodeId);
			}
		}
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);
	}

	/*****
	*	Main function to enable/disable a Bb Code
	***/
	protected function _enableDisable($tagId)
	{
		$code = $this->_getBbmBbCodeOrError($tagId);
		
		$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');

		if($code['active'])
		{
			$dw->setExtraData(BBM_DataWriter_BbCodes::IGNORE_CALLBACKS_CHECK, true);
		}
		
		$dw->setExistingData($code['tag_id']);

		if($code['active'])
		{
			$dw->set('active', 0);
		}
		else
		{
			$dw->set('active', 1);
		}
		
		$dw->save();

		//Update buttons config
		$this->_getBbmButtonsModel()->toggleButtonStatusInAllConfigs($code);
	
		//Update simple cache
		$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes();
	
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);
	}

	/*****
	*	Save a Bb Code
	***/	
	public function actionSave()
	{
		$this->_assertPostOnly();

		$tagId = $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		$tag = $this->_input->filterSingle('tag', XenForo_Input::STRING);

		//If the kill function is activated, check if the button name is not a XenForo button command name
		//Why? A bbcode button will automatically have a prefix (bbm), but if the kill function (bypass), then no prefix anymore
		$killCmd = $this->_input->filterSingle('killCmd', XenForo_Input::UINT);
		
		if(!empty($killCmd) && in_array($tag, self::$protectedXenCmd))
		{
			throw new XenForo_Exception(new XenForo_Phrase('bbm_admin_error_cannot_use_protected_xen_cmd'), true);
		}

		$dwInput = $this->_input->filter(array(
				'tag' => XenForo_Input::STRING,
				'title' => XenForo_Input::STRING,
				'description' => XenForo_Input::STRING,
				'example' => XenForo_Input::STRING,
				'active' => XenForo_Input::UINT,
				'display_help' => XenForo_Input::UINT,

				'start_range' => XenForo_Input::STRING,
				'end_range' => XenForo_Input::STRING,
				'options_number' => XenForo_Input::UINT,

				'phpcallback_class' => XenForo_Input::STRING,
				'phpcallback_method' => XenForo_Input::STRING,

				'stopAutoLink' => XenForo_Input::STRING,
				'parseOptions' => XenForo_Input::UINT,
				'regex' => XenForo_Input::STRING,
				'trimLeadingLinesAfter' => XenForo_Input::UINT,
				'plainCallback' => XenForo_Input::UINT,
				'plainChildren' => XenForo_Input::UINT,
				'stopSmilies' => XenForo_Input::UINT,
				'stopLineBreakConversion' => XenForo_Input::UINT,
				'wrapping_tag' => XenForo_Input::STRING,
				'wrapping_option' => XenForo_Input::STRING,
				'emptyContent_check' => XenForo_Input::UINT,
				'allow_signature' => XenForo_Input::UINT,
				'preParser' => XenForo_Input::UINT,
				'options_separator' => XenForo_Input::STRING,

				'parser_has_usr' => XenForo_Input::UINT,
				'parser_return' => XenForo_Input::STRING,
				'parser_return_delay' => XenForo_Input::UINT,

				'view_has_usr' => XenForo_Input::UINT,
				'view_return' => XenForo_Input::STRING,
				'view_return_delay' => XenForo_Input::UINT,
								
				'template_active' => XenForo_Input::UINT,
				'template_name' => XenForo_Input::STRING,
				'template_callback_class' => XenForo_Input::STRING,
				'template_callback_method' => XenForo_Input::STRING,

				'hasButton' => XenForo_Input::UINT,
				'button_has_usr' => XenForo_Input::UINT,
				'killCmd' => XenForo_Input::UINT,
				'custCmd' => XenForo_Input::STRING,
				'quattro_button_type' => XenForo_Input::STRING,
				'quattro_button_type_opt' => XenForo_Input::STRING,
				'quattro_button_return' => XenForo_Input::STRING,
				'quattro_button_return_opt' => XenForo_Input::STRING,
				'imgMethod' => XenForo_Input::STRING, //Depreciated
				'buttonDesc' => XenForo_Input::STRING,
				'tagOptions' => XenForo_Input::STRING,
				'tagContent' => XenForo_Input::STRING,

				'redactor_has_icon' => XenForo_Input::UINT,
				'redactor_sprite_mode' => XenForo_Input::UINT,
				'redactor_sprite_params_x' => XenForo_Input::INT,
				'redactor_sprite_params_y' => XenForo_Input::INT,
				'redactor_image_url' => XenForo_Input::STRING,
				'redactor_button_type' => XenForo_Input::STRING,
				'redactor_button_type_opt' => XenForo_Input::STRING
		));

		if(XenForo_Application::debugMode())
		{
			$dwInput += $this->_input->filter(array(
				'bbcode_id' => XenForo_Input::STRING,
				'bbcode_addon' => XenForo_Input::STRING
			));
		}

		//Button autofill (will avoid DW error) | To do: check this in the DW directly
		if( (!empty($tag)  && $tag[0] == '@') )
		{
			$dwInput['example'] = '#';
			$dwInput['start_range'] = '#';
			$dwInput['end_range'] = '#';			
		}
		
		//Array_keys is the only trick I've found to get the usergroups id selected... Associated template code => name="button_usr[{$list.value}]"
		$dwInput['button_usr'] = serialize(array_keys($this->_input->filterSingle('button_usr', array(XenForo_Input::STRING, 'array' => true))));
		$dwInput['parser_usr'] = serialize(array_keys($this->_input->filterSingle('parser_usr', array(XenForo_Input::STRING, 'array' => true))));
		$dwInput['view_usr'] = serialize(array_keys($this->_input->filterSingle('view_usr', array(XenForo_Input::STRING, 'array' => true))));

		$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');
		$existedData = $this->_getBbmBbCodeModel()->getBbCodeById($tagId);
		
		if ($existedData)
		{
			if( !XenForo_Application::debugMode() && isset($existedData['bbcode_id'], $existedData['bbcode_addon']) )
			{
				//Should not be needed but I think to have seen once these keys overriden by blank values
				$dwInput += array(
					'bbcode_id' => $existedData['bbcode_id'],
					'bbcode_addon' => $existedData['bbcode_addon']
				);
			}
			
			$dw->setExistingData($tagId);
			$this->_getBbmButtonsModel()->addUpdateButtonInAllConfigs($dwInput);
		}

		$dw->bulkSet($dwInput);
		$dw->save();

		//Update simple cache
		$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes();
		

		/***
			Return Manager
		***/
		$src = $this->_input->filterSingle('bmsrc', XenForo_Input::STRING);
		
		if(!empty($src) && in_array($src, array('ltr', 'rtl')))
		{
			//This is an edit from the Button Manager (ltr/rtl type)
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('bbm-buttons/editorconfig' . $src)
			);			
		}
		elseif(!empty($src))
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('bbm-buttons/editorcust', '', array('config_type' => $src))
			);		
		}

		if ($this->_input->filterSingle('reload', XenForo_Input::STRING))
		{
			/*Reload*/
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
				XenForo_Link::buildAdminLink('bbm-bbcodes/edit', $tagId)
			);
		}
	
		/*Save & exit*/
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);

		/* For debug
			return $this->responseMessage($dwInput['button_usr']);
		*/
	}
	
	/*****
	*	Delete a Bb Code
	***/	
	public function actionDelete()
	{
		$tagId = $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		$code = $this->_getBbmBbCodeOrError($tagId);

		if ($this->isConfirmedPost())
		{
			$this->_getBbmButtonsModel()->deleteButtonInAllConfigs($code);

			$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');
			$dw->setExistingData($tagId);
			$dw->delete();
			
			$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes(); //Must be executed after the dw

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('bbm-bbcodes')
			);
		}
		else
		{
			$viewParams = array(
				'code' => $code
			);
			return $this->responseView('Bbm_ViewAdmin_Bbm_BbCode_Delete', 'bbm_bb_codes_delete', $viewParams);
		}
	}

	/*****
	*	Import Uploader
	***/	
	public function actionImportUploader()
	{
		return $this->responseView('Bbm_ViewAdmin_BbCodes_Import', 'bbm_bb_codes_import');
	}

	/*****
	*	Import one or several Bb Code
	***/	
		
	public function actionImport()
	{
		$this->_assertPostOnly();

		$fileTransfer = new Zend_File_Transfer_Adapter_Http();
		if ($fileTransfer->isUploaded('upload_file'))
		{
			$fileInfo = $fileTransfer->getFileInfo('upload_file');
			$fileName = $fileInfo['upload_file']['tmp_name'];
		}
		else
		{
			$fileName = $this->_input->filterSingle('server_file', XenForo_Input::STRING);
		}
		
		if (!file_exists($fileName) || !is_readable($fileName))
		{
			throw new XenForo_Exception(new XenForo_Phrase('please_enter_valid_file_name_requested_file_not_read'), true);
		}
		
		$file = BBM_Helper_Bbm::scanXmlFile($fileName);
		
		if($file->getName() != 'bbm_bbcodes')
		{
			throw new XenForo_Exception(new XenForo_Phrase('bbm_xml_invalid'), true);
		}

		$BbCodes = count($file->BbCode);
		$overrideOption = $this->_input->filterSingle('bbm_override', XenForo_Input::STRING);
		
		if($BbCodes == 1)
		{
	      		$code = $this->_getImportValues($file->BbCode);

      			if(!isset($code['tag']))
      			{
      				throw new XenForo_Exception(new XenForo_Phrase('bbm_xml_invalid'), true);	
      			}
	
			if(is_array($this->_getBbmBbCodeModel()->getBbCodeByTag($code['tag'])))
			{
				$viewParams = array(
					'code' => $code,
					'xml' => $file->asXML()
				);
	
				return $this->responseView('Bbm_ViewAdmin_Bbm_BbCode_Import_Override', 'bbm_bb_codes_import_override', $viewParams);
			}
	
			$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');
			$dw->bulkSet($code);
			$dw->save();
	
			//Update simple cache
			$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes();
		
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('bbm-bbcodes')
			);
		}
		else
		{
			$new = array();
			$updated = array();
			$notupdated = array();

			foreach($file->BbCode as $BbCode)
			{
				$code = $this->_getImportValues($BbCode);
				
				if(!isset($code['tag']))
				{
					throw new XenForo_Exception(new XenForo_Phrase('bbm_xml_invalid'), true);	
				}

				if(is_array($this->_getBbmBbCodeModel()->getBbCodeByTag($code['tag'])) && !$overrideOption)
				{
					$notupdated[] = $code['tag'];
					continue;
				}
				
				$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');
				
				if(is_array($this->_getBbmBbCodeModel()->getBbCodeByTag($code['tag'])) && $overrideOption)
				{
					$updated[] = $code['tag'];
					$tag = $code['tag'];
					$tagId = $this->_getBbmBbCodeModel()->getBbCodeIdFromTag($tag);					


					if ($this->_getBbmBbCodeModel()->getBbCodeById($tagId))
					{
						$dw->setExistingData($tagId);
						$this->_getBbmButtonsModel()->addUpdateButtonInAllConfigs($code);
					}
		
					$dw->bulkSet($code);
					$dw->save();					
				}
				else
				{
					$new[] = $code['tag'];
					$dw->bulkSet($code);
					$dw->save();			
				}
			}

			//Update simple cache
			$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes();
			
			
			$viewParams = array(
					'new' => $new,
					'updated' => $updated,
					'notupdated' => $notupdated
			);

			return $this->responseView('Bbm_ViewAdmin_Bulk_Import_Results', 'bbm_bb_codes_import_results', $viewParams);			
		}
	}

	/*****
	*	Import Override one Bb Code
	***/		
	public function actionImportOverride()
	{
      		if (!$this->isConfirmedPost())
      		{
			throw new XenForo_Exception(new XenForo_Phrase('bbm_forbidden_action'), true);	
      		}

		$xml = $this->_input->filterSingle('xml', XenForo_Input::STRING);
		$xmlObj = BBM_Helper_Bbm::scanXmlString($xml);

      		$code = $this->_getImportValues($xmlObj->BbCode);
      		$tag = $code['tag'];
      		$tagId = $this->_getBbmBbCodeModel()->getBbCodeIdFromTag($tag);

      		$dw = XenForo_DataWriter::create('BBM_DataWriter_BbCodes');
	
		if ($this->_getBbmBbCodeModel()->getBbCodeById($tagId))
		{
			$dw->setExistingData($tagId);
			$this->_getBbmButtonsModel()->addUpdateButtonInAllConfigs($code);
		}
		
		//$dw->setExtraData(BBM_DataWriter_BbCodes::ALLOW_OVERRIDE, true);
		$dw->bulkSet($code);
		$dw->save();

		$this->_getBbmBbCodeModel()->simplecachedActiveBbCodes();
	
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('bbm-bbcodes')
		);
	}

	/*****
	*	Function to extract values from the xml
	***/	
	protected function _getImportValues($BbCode)
	{
		$builder = array(
				'tag' => (string) $BbCode->General->tag,
				'title' => (string) $BbCode->General->title,
				'description' => (string) $BbCode->General->description,
				'example' => (string) $BbCode->General->example,
				'active' => (int) $BbCode->General->active,
				'display_help' => (int) $BbCode->General->display_help,

				'bbcode_id' => (string) $BbCode->General->bbcode_id,
				'bbcode_addon' => (string) $BbCode->General->bbcode_addon,

				'start_range' => (string) $BbCode->Methods->Replacement->start_range,
				'end_range' => (string) $BbCode->Methods->Replacement->end_range,
				'options_number' => (int) $BbCode->Methods->Replacement->options_number,
				
				'template_active' => (int) $BbCode->Methods->Template->active,
				'template_name' => (string) $BbCode->Methods->Template->name,
				'template_callback_class' => (string) $BbCode->Methods->Template->callback_class,
				'template_callback_method' => (string) $BbCode->Methods->Template->callback_method,

				'phpcallback_class' => (string) $BbCode->Methods->PhpCallback->class,
				'phpcallback_method' => (string) $BbCode->Methods->PhpCallback->method,

				'stopAutoLink' => (string) $BbCode->ParserOptions->stopAutoLink,
				'regex' => (string) $BbCode->ParserOptions->regex,
				'trimLeadingLinesAfter' => (int) $BbCode->ParserOptions->trimLeadingLinesAfter,
				'plainCallback' => (int) $BbCode->ParserOptions->plainCallback,
				'plainChildren' => (int) $BbCode->ParserOptions->plainChildren,
				'parseOptions' => (int) $BbCode->ParserOptions->parseOptions,
				'stopSmilies' => (int) $BbCode->ParserOptions->stopSmilies,
				'stopLineBreakConversion' => (int) $BbCode->ParserOptions->stopLineBreakConversion,
				'wrapping_tag' =>  (string) $BbCode->ParserOptions->wrapping_tag,
				'wrapping_option' => (string) $BbCode->ParserOptions->wrapping_option,
				'emptyContent_check' => (int) $BbCode->ParserOptions->emptyContent_check,
				'allow_signature' => (int) $BbCode->ParserOptions->allow_signature,
				'preParser' => (int) $BbCode->ParserOptions->preParser,
				'options_separator' => (string) $BbCode->ParserOptions->options_separator,

				'parser_has_usr' => (int) $BbCode->ParserPerms->parser_has_usr,
				'parser_usr' => (string) $BbCode->ParserPerms->parser_usr,
				'parser_return' => (string) $BbCode->ParserPerms->parser_return,
				'parser_return_delay' => (int) $BbCode->ParserPerms->parser_return_delay,
				
				'view_has_usr' => (int) $BbCode->ViewPerms->view_has_usr,
				'view_usr' => (string) $BbCode->ViewPerms->view_usr,
				'view_return' => (string) $BbCode->ViewPerms->view_return,
				'view_return_delay' => (int) $BbCode->ViewPerms->view_return_delay,
				
				'hasButton' => (int) $BbCode->Button->hasButton,
				'button_has_usr' => (int) $BbCode->Button->button_has_usr,
				'button_usr' => (string) $BbCode->Button->button_usr,
				'killCmd' => (int) $BbCode->Button->killCmd,
				'custCmd' => (string) $BbCode->Button->custCmd,
				'quattro_button_type' => (string) $BbCode->Button->quattro_button_type,
				'quattro_button_type_opt' => (string) $BbCode->Button->quattro_button_type_opt,
				'quattro_button_return' => (string) $BbCode->Button->quattro_button_return,
				'quattro_button_return_opt' => (string) $BbCode->Button->quattro_button_return_opt,
				'imgMethod' => (string) $BbCode->Button->imgMethod, //depreciated
				'buttonDesc' => (string) $BbCode->Button->buttonDesc,
				'tagOptions' => (string) $BbCode->Button->tagOptions,
				'tagContent' => (string) $BbCode->Button->tagContent,

				'redactor_has_icon' => (int) $BbCode->Button->redactor_has_icon,
				'redactor_sprite_mode' => (int) $BbCode->Button->redactor_sprite_mode,
				'redactor_sprite_params_x' => (int) $BbCode->Button->redactor_sprite_params_x,
				'redactor_sprite_params_y' => (int) $BbCode->Button->redactor_sprite_params_y,
				'redactor_image_url' => (string) $BbCode->Button->redactor_image_url,
				'redactor_button_type' => (string) $BbCode->Button->redactor_button_type,
				'redactor_button_type_opt' => (string) $BbCode->Button->redactor_button_type_opt			
			);
			
		return $builder;
	}

	/*****
	*	Export one Bb Code
	***/	
	public function actionExport()
	{
		$tagId = $this->_input->filterSingle('tag_id', XenForo_Input::STRING);
		$tag = $this->_getBbmBbCodeOrError($tagId);

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'name' => $tag['tag'],
			'xml' => $this->_getBbmBbCodeModel()->exportFromIds(array($tag['tag_id']))
		);

		return $this->responseView('BBM_ViewAdmin_BbCodes_Export', '', $viewParams);
	}

	/*****
	*	Export several Bb Codes - Page
	***/	
	public function actionBulkExportPage()
	{
		$params = array('tag', 'tag_id', 'bbcode_addon');
		$codes = $this->_getBbmBBCodeModel()->getAllBbCodesBy($params, true);
		$addOnModel = $this->_getAddOnModel();
		$addOnOptions = $addOnModel->getAddOnOptionsListIfAvailable();
		$hasBbCodes = false;
		
		if( count($codes) > 1 || !empty($codes['none']) )
		{
			$hasBbCodes = true;
		}

		$BbCodesByAddon = array();
		foreach($codes as $addon => $BbCodes)
		{
			if($addon == 'none')
			{
				$BbCodesByAddon[$addon] = $BbCodes;
			}
			elseif( isset($addOnOptions[$addon]) && !isset($BbCodesByAddon[$addOnOptions[$addon]]) )
			{
				$newKey = $addOnOptions[$addon];
				
				$BbCodesByAddon[$newKey] = $BbCodes;
			}
			else
			{
				$BbCodesByAddon[$addon] = $BbCodes;
			}
		}
		
		$viewParams = array(
			'BbCodesByAddon' => $BbCodesByAddon,
			'hasBbCodes' => $hasBbCodes
 		);

		return $this->responseView('BBM_ViewAdmin_Bbm_BbCodes_ExportList', 'bbm_bb_codes_export_list', $viewParams);
	}

	/*****
	*	Export several Bb Codes - Function
	***/	
	public function actionBulkExport()
	{
		$this->_assertPostOnly();
		$tagsIDs = $this->_input->filterSingle('bbcode_list',  array(XenForo_Input::UINT, 'array' => true));

		if (empty($tagsIDs))
		{
			throw new XenForo_Exception(new XenForo_Phrase('bbm_must_select_atleast_one_bbcode'), true);
		}

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'name' => new XenForo_Phrase('bbm_multi_export_file_name'),		
			'xml' => $this->_getBbmBbCodeModel()->exportFromIds($tagsIDs)
		);

		return $this->responseView('BBM_ViewAdmin_BbCodes_Export', '', $viewParams);
	}

	/*****
	*	Bbcm Bulk Converter - Page
	***/	
	public function actionBulkBbcmConverterPage()
	{
		$tags = $this->_getBbmBBCodeModel()->getBbcmTagsNames();
		
		$viewParams = array(
			'codes' => $tags
 		);
		return $this->responseView('BBM_ViewAdmin_Bbm_Bbcm_Converter_List', 'bbm_bb_codes_bbcm_converter_list', $viewParams);
	}

	/*****
	*	Bbcm Bulk Converter - Page
	***/	
	public function actionBulkBbcmConverter()
	{
		$this->_assertPostOnly();
		$tagsNames = $this->_input->filterSingle('bbcode_list',  array(XenForo_Input::STRING, 'array' => true));

		if (empty($tagsNames))
		{
			throw new XenForo_Exception(new XenForo_Phrase('bbm_must_select_atleast_one_bbcode'), true);
		}

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'name' => new XenForo_Phrase('bbm_bbcm_converted'),		
			'xml' => $this->_getBbmBbCodeModel()->convertAndExportBbcmTags($tagsNames)
		);

		return $this->responseView('BBM_ViewAdmin_BbCodes_Export', '', $viewParams);
	}

	/*****
	*	Function to check that the Bb Code exists
	***/	
	protected function _getBbmBbCodeOrError($tagId)
	{
		$info = $this->_getBbmBbCodeModel()->getBbCodeById($tagId);
		if (!$info)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('bbm_bbcode_not_found'), 404));
		}

		return $info;
	}

	/*****
	*	Get Models
	***/	
	protected function _getBbmBbCodeModel()
	{
		return $this->getModelFromCache('BBM_Model_BbCodes');
	}

	protected function _getBbmButtonsModel()
	{
		return $this->getModelFromCache('BBM_Model_Buttons');
	}

	protected function _getAddOnModel()
	{
		return $this->getModelFromCache('XenForo_Model_AddOn');
	}			
}
//Zend_Debug::dump($code);