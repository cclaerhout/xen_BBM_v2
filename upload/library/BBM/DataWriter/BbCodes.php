<?php

class BBM_DataWriter_BbCodes extends XenForo_DataWriter
{
	const ALLOW_OVERRIDE = 'allowOverride';
	const IGNORE_CALLBACKS_CHECK = 'ignoreCallbacksCheck';	

	protected function _getDefaultOptions()
	{
		//Fix provided by Despair - thank you!
		return array(
			self::ALLOW_OVERRIDE => false,
			self::IGNORE_CALLBACKS_CHECK => false
		);
	}
	
	protected $_existingDataErrorPhrase = 'bbm_bbcode_not_found';

	protected $_xenBbCodes = array(
		'b', 'i', 'u', 's', 'color', 
		'font', 'size', 'left', 'center', 'right', 'indent', 
		'url', 'email', 'img', 'quote', 'code', 'php', 'html', 
		'list', 'plain', 'media', 'attach'
	);

	protected function _getFields()
	{
		return array(
			'bbm' => array(
				'tag_id' => array('type' => self::TYPE_UINT, 'autoIncrement' => true),
				'tag' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 25, 
					'verification' => array('$this', '_verifyBbCodeTag'),
					'requiredError' => 'bbm_error_invalid_tag'
				),
				'title' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50, 'requiredError' => 'please_enter_valid_title'),
				'description' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 250, 'requiredError' => 'bbm_please_enter_valid_desc'),
				'example' => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 2000, 'requiredError' => 'please_enter_embed_html'),
				'active' => array('type' => self::TYPE_UINT, 'default' => 1),
				'display_help' => array('type' => self::TYPE_UINT, 'default' => 1),

				'bbcode_id' => array('type' => self::TYPE_STRING, 'maxLength' => 250, 'verification' => array('$this', '_verifyBbcui')),
				'bbcode_addon' => array('type' => self::TYPE_STRING, 'maxLength' => 250),

				'start_range' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 7000),
				'end_range' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 7000),
				'options_number' => array('type' => self::TYPE_UINT, 'default' => 0),

				'template_active' => array('type' => self::TYPE_UINT, 'default' => 0),
				'template_name'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),
				'template_callback_class'  => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),
				'template_callback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),

				'phpcallback_class' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),
				'phpcallback_method' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 250),				
				
				'stopAutoLink' => array('type' => self::TYPE_STRING, 'default' => 'none', 'maxLength' => 20),
				'parseOptions' => array('type' => self::TYPE_UINT, 'default' => 0),
				'regex' => array('type' => self::TYPE_STRING, 'default' => ''),
				'trimLeadingLinesAfter' => array('type' => self::TYPE_UINT, 'default' => 0, 'maxLength' => 1),
				'plainCallback' => array('type' => self::TYPE_UINT, 'default' => 0),
				'plainChildren' => array('type' => self::TYPE_UINT, 'default' => 0),
				'stopSmilies' => array('type' => self::TYPE_UINT, 'default' => 0),
				'stopLineBreakConversion' => array('type' => self::TYPE_UINT, 'default' => 0),
				'wrapping_tag' => array('type' => self::TYPE_STRING, 'default' => 'none', 'maxLength' => 30),
				'wrapping_option' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 500),
				'emptyContent_check' => array('type' => self::TYPE_UINT, 'default' => 1),
				'allow_signature' => array('type' => self::TYPE_UINT, 'default' => 0),
				'preParser' => array('type' => self::TYPE_UINT, 'default' => 0),
				'options_separator' => array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 5),				

				'parser_has_usr' => array('type' => self::TYPE_UINT, 'default' => 0),
				'parser_usr' => array('type' => self::TYPE_STRING, 'default' => ''),
				'parser_return' => array('type' => self::TYPE_STRING, 'default' => 'blank'),
				'parser_return_delay' => array('type' => self::TYPE_UINT, 'default' => 0),

				'view_has_usr' => array('type' => self::TYPE_UINT, 'default' => 0),
				'view_usr' => array('type' => self::TYPE_STRING, 'default' => ''),
				'view_return' => array('type' => self::TYPE_STRING, 'default' => 'blank'),
				'view_return_delay' => array('type' => self::TYPE_UINT, 'default' => 0),

				'hasButton' 	=> array('type' => self::TYPE_UINT, 'default' => 0),
				'button_has_usr' => array('type' => self::TYPE_UINT, 'default' => 0),
				'button_usr' 	=> array('type' => self::TYPE_STRING, 'default' => ''),
				'killCmd' 	=> array('type' => self::TYPE_UINT, 'default' => 0),
				'custCmd' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),				
				'quattro_button_type' 		=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),
				'quattro_button_type_opt' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 150),
				'quattro_button_return' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),
				'quattro_button_return_opt' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 150),
				'imgMethod' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 20), // Depreciated
				'buttonDesc' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 255),
				'tagOptions' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 255),
				'tagContent' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 255),
				
				'redactor_has_icon' => array('type' => self::TYPE_UINT, 'default' => 0),
				'redactor_sprite_mode' => array('type' => self::TYPE_UINT, 'default' => 0),
				'redactor_sprite_params_x' => array('type' => self::TYPE_INT, 'default' => 0),
				'redactor_sprite_params_y' => array('type' => self::TYPE_INT, 'default' => 0),
				'redactor_image_url' => array('type' => self::TYPE_STRING, 'default' => ''),
				'redactor_button_type' 		=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 50),
				'redactor_button_type_opt' 	=> array('type' => self::TYPE_STRING, 'default' => '', 'maxLength' => 150)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'tag'))
		{
			return false;
		}

		return array('bbm' => $this->_getBbmBbCodeModel()->getBbCodeById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'tag = ' . $this->_db->quote($this->getExisting('tag'));
	}

	protected function _verifyBbCodeTag(&$tag)
	{
		$tag = strtolower($tag);

		if (preg_match('/[^a-zA-Z0-9_@]/', $tag))
		{
			$this->error(new XenForo_Phrase('bbm_error_tag_must_only_use_alphanumeric'), 'tag');
			return false;
		}

		if (preg_match('/^custom_/i', $tag))
		{
			$this->error(new XenForo_Phrase('bbm_error_tag_custom_prefix_is_forbidden'), 'tag');
			return false;
		}
		
		if(in_array($tag, $this->_xenBbCodes))
		{
			$this->error(new XenForo_Phrase('bbm_error_tag_must_be_unique'), 'tag');
			return false;
		}

		$canOverride = $this->getExtraData(self::ALLOW_OVERRIDE);

		if (($this->isInsert() || $tag != $this->getExisting('tag')) && $canOverride !== true )
		{
			$tagFound = $this->_getBbmBbCodeModel()->getBbCodeByTag($tag);

			if($tagFound)
			{
				$this->error(new XenForo_Phrase('bbm_error_tag_must_be_unique'), 'tag');
				return false;
			}
		}

		return true;
	}

	protected function _verifyBbcui(&$bbcui)
	{
		$bbcui = trim($bbcui);

		if(empty($bbcui))
		{
			return true;
		}

		$dataFound = $this->_getBbmBbCodeModel()->getBbCodeByUniqueIdentifier($bbcui);
		$currentId = $this->getExisting('tag_id'); //null if new bb code
		
		if(empty($dataFound['tag_id']))
		{
			return true;
		}
		elseif((!$currentId && $dataFound) || ($currentId != $dataFound['tag_id']))
		{
			$this->error(new XenForo_Phrase('bbm_error_bbcui_must_be_unique'), 'bbcui');
			return false;
		}
		
		return true;
	}
	
	protected function _verifyCallback($class, $method)
	{
		if (!XenForo_Application::autoload($class) || !method_exists($class, $method))
		{
			$this->error(new XenForo_Phrase('please_enter_valid_callback_method'), 'callback_method');
		}
	}

	protected function _verifyTemplate($templateName)
	{
		$id = $this->_getTemplateModel()->getTemplateIdInStylesByTitle($templateName);

		if(empty($id))
		{
			$this->error(new XenForo_Phrase('bbm_error_please_enter_valid_template_name', array('templateName' => $templateName)), 'template_name');
		}
	}
	
	protected function _preSave()
	{
		$replacementMethod = ($this->get('start_range') || $this->get('end_range')) ? 1 : 0;
		$phpcallbackMethod = ($this->get('phpcallback_method') || $this->get('phpcallback_class')) ? 1 : 0;
		$templateMethod = ($this->get('template_active')) ? 1 : 0;
		$totalMethods = $replacementMethod + $phpcallbackMethod + $templateMethod;

		if($totalMethods == 0)
		{
			$this->error(new XenForo_Phrase('bbm_error_replacement_method_needed'), 'tag');
			return false;
		}

		if($totalMethods > 1)
		{
			$selectedMethods = ($replacementMethod) ? new XenForo_Phrase('bbm_error_replacement_method') . '-' : '';
			$selectedMethods .= ($phpcallbackMethod) ? new XenForo_Phrase('bbm_error_phpcallback_method') .  '-' : '';
			$selectedMethods .= ($templateMethod) ? new XenForo_Phrase('bbm_error_template_method') . '-' : '';
			$selectedMethods = substr($selectedMethods, 0, -1);
			
			$this->error(new XenForo_Phrase('bbm_error_enter_one_replacement_method', array('number' => $totalMethods, 'methods' => $selectedMethods)), 'tag');
			return false;
		}		

		if($this->get('template_active') && !$this->get('template_name'))
		{
			$this->error(new XenForo_Phrase('bbm_error_enter_one_templatename'), 'tag');
			return false;
		}

		if($this->get('template_active') && $this->get('template_name'))
		{
			$this->_verifyTemplate($this->get('template_name'));
		}

		$ignoreCallbacksCheck = $this->getExtraData(self::IGNORE_CALLBACKS_CHECK);
		
		if($ignoreCallbacksCheck !== true)
		{
			if($this->get('template_active') && $this->get('template_callback_class'))
			{
				$this->_verifyCallback($this->get('template_callback_class'), $this->get('template_callback_method'));
			}
			
			if($this->get('phpcallback_class'))
			{
				$this->_verifyCallback($this->get('phpcallback_class'), $this->get('phpcallback_method'));
			}
		}
		
		if(!$phpcallbackMethod && !$templateMethod)
		{
			if(in_array($this->get('parser_return'), array('template', 'callback')))
			{
				$this->error(new XenForo_Phrase('bbm_error_parser_return_invalid'), 'parserReturn');
				return false;
			}

			if(in_array($this->get('view_return'), array('template', 'callback')))
			{
				$this->error(new XenForo_Phrase('bbm_error_view_return_invalid'), 'viewReturn');
				return false;		
			}
		}
		
		if($phpcallbackMethod)
		{
			if($this->get('parser_return') == 'template' || $this->get('view_return') == 'template')
			{
				$this->error(new XenForo_Phrase('bbm_error_parser_return_template_invalid'), 'parserReturn');
				return false;
			}
		}

		if($templateMethod)
		{
			if($this->get('parser_return') == 'callback' || $this->get('view_return') == 'callback')
			{
				$this->error(new XenForo_Phrase('bbm_error_parser_return_callback_invalid'), 'parserReturn');
				return false;
			}
		}
		
		$mceButtonType = $this->get('quattro_button_type');
		$mceButtonOption = $this->get('quattro_button_type_opt');

		if($mceButtonType != 'manual' && !$mceButtonOption && !empty($mceButtonType))
		{
			if($mceButtonType == 'text')
			{
				$this->error(new XenForo_Phrase('bbm_error_btn_option_cant_be_empty_buttonname_needed'), 'mceBtnOpt');
			}
			else
			{
				$this->error(new XenForo_Phrase('bbm_error_btn_option_cant_be_empty_buttonunicode_needed'), 'mceBtnOpt');
			}
			return false;
		}
	}
	
	protected function _getBbmBbCodeModel()
	{
		return $this->getModelFromCache('BBM_Model_BbCodes');
	}

	protected function _getTemplateModel()
	{
		return $this->getModelFromCache('XenForo_Model_Template');
	}	
}