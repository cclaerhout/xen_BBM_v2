<?php

class BBM_Helper_Buttons
{
	public static $textDirection = 'LTR';
	public static $controllerName;
	public static $controllerAction;
	public static $viewName;
	public static $editor;		

	public static function getConfig($controllerName, $controllerAction, $viewName)
	{
		/*Init*/
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();

		if(!empty($options->bbm_debug_tinymcehookdisable))
		{
			return self::_fallBack(1);
		}

		$language = $visitor->getLanguage();

		if(!empty($language['text_direction']))
		{
			self::$textDirection = $language['text_direction'];
		}
		
		self::$controllerName = $controllerName;
		self::$controllerAction = $controllerAction;
		self::$viewName = $viewName;

		//Check if Quattro is enable
		$quattroEnable = BBM_Helper_Bbm::checkIfAddonActive('sedo_tinymce_quattro', true);

		//Which editor is being used? $options->quattro_iconsize is only use to check if the addon is installed or enable
		$editor = (empty($visitor->permissions['sedo_quattro']['display']) || !$quattroEnable) ? 'xen' : 'mce';
		
		if(XenForo_Application::get('options')->get('currentVersionId') < 1020031)
		{
			$editor = 'mce';
		}
		
		self::$editor = $editor;

		//Get buttons config
		$myConfigs = BBM_Helper_Bbm::getBbmButtons();
						
		if(empty($myConfigs))
		{
			return self::_fallBack(2);
		}

		//Only use the configuration for the current editor
		$myConfigs = $myConfigs['bbm_buttons'];

		//Check which Editor type must be used
		list($config_ed, $config_type) = self::_bakeEditorConfig($myConfigs, $editor);

		if(empty($myConfigs[$config_ed][$config_type]['config_buttons_order']))
		{
			return self::_fallBack(3);
		}

		$extraParams = self::_bakeExtraParams($myConfigs[$config_ed][$config_type]['config_buttons_full'], $options, $visitor);
		
		if( ($editor == 'mce' && $editor != $config_ed) || $editor == 'xen')
		{
			$extraParams['loadQuattro'] = false;
		}
		else
		{
			$extraParams['loadQuattro'] = true;		
		}

		return $extraParams;
	}


	protected static function _bakeEditorConfig($myConfigs, $theoricalEditor)
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();

		/****
		*	Check Text Direction
		***/
		$config_type = strtolower(self::$textDirection);
		
		
		//The framework for Redactor doesn't make a difference between RTL/LTR
		if(self::$editor == 'xen')
		{
			$config_type = 'redactor';
		}

		/****
		*	Check controller datas
		***/
		$custConfigs = $options->Bbm_Bm_Cust_Config;

		if(!empty($custConfigs) && is_array($custConfigs))
		{
			$controllerName = self::$controllerName;
			$controllerAction = self::$controllerAction;
			$viewName = self::$viewName;

			$scores = array('0' => $config_type);
			foreach($custConfigs as $custConfig)
			{
				$points = 1;
				$points = ($controllerName == $custConfig['controllername']) ? $points+1 : $points;
				$points = ($controllerAction == $custConfig['controlleraction']) ? $points+1 : $points;
				$points = ($viewName == $custConfig['viewname']) ? $points+1 : $points;	
				
				if($points > 1)
				{
					$configType = $custConfig['configtype'];
					$configEd = (isset($custConfig['editor'])) ? $custConfig['editor'] : null;
					$scores[$points] = array('editor' => $configEd, 'type' => $configType);
				}
			}
			
			$winnerKey = max(array_keys($scores));
			//Sorry but if competitors are ex aequo, the last one wins
			$winner = $scores[$winnerKey];
			
			if(isset($winner['editor']) && isset($winner['type']))
			{
				$winnerEd = $winner['editor'];
				$winnerConfig = $winner['type'];

				if(empty($winnerEd))
				{
					//Might occur if user didn't save again its configuration
					$winnerEd = $theoricalEditor;
				}

				//Anti-doping test
				if(isset($myConfigs[$winnerEd][$winnerConfig]))
				{
					$config_type = $winnerConfig;
					$theoricalEditor = $winnerEd;
					self::$editor = $winnerEd;
				}
			}
		}

		/****
		*	Check forum config (option)
		***/
		if($options->Bbm_Bm_Forum_Config)
		{
			$editorOptions = false;

			if (XenForo_Application::isRegistered('bbm_bm_editor'))
			{
				$editorOptions = XenForo_Application::get('bbm_bm_editor');
			}

			if($editorOptions !== false && $editorOptions != 'disable')
			{
				$config_type = $editorOptions;
				$theoricalEditor = self::_mceOrXenDetectByConfigType($myConfigs, $config_type);
			}
		}

		/****
		*	Check if mobile
		***/
		if((!class_exists('Sedo_DetectBrowser_Listener_Visitor') || !isset($visitor->getBrowser['isMobile'])))
		{
			//No external addon has been installed or activated
			if(XenForo_Visitor::isBrowsingWith('mobile') && $options->Bbm_Bm_Mobile != 'disable')
			{
				//is mobile and editor has a style option
				$checkEdAndConfig = self::_mceOrXenDetectByConfigType($myConfigs, $options->Bbm_Bm_Mobile);
				
				if($checkEdAndConfig)
				{
					$config_type = $options->Bbm_Bm_Mobile;
					$theoricalEditor = $checkEdAndConfig;
				}
			}
			
			return array($theoricalEditor, $config_type);
		}
		else
		{
			//External addon is installed

			if(!$visitor->getBrowser['isMobile'])
			{
				//is not mobile
				return array($theoricalEditor, $config_type);
			}
			
			if($visitor->getBrowser['isTablet'] && !in_array($options->Bbm_Bm_Tablets, array('transparent', 'disable')))
			{
				//is a tablet & transparent mode has been activated
				$config_type = $options->Bbm_Bm_Tablets;
			}
			
			if($visitor->getBrowser['isMobile'] && $options->Bbm_Bm_Mobile != 'disable')
			{
				//is a mobile device and mobile configuration has been activated
				$checkEdAndConfig = self::_mceOrXenDetectByConfigType($myConfigs, $options->Bbm_Bm_Mobile);
				
				if($checkEdAndConfig)
				{
					$config_type = $options->Bbm_Bm_Mobile;
					$theoricalEditor = $checkEdAndConfig;
				}
			}
			
			if($visitor->getBrowser['isTablet'] && $options->Bbm_Bm_Tablets != 'disable')
			{
				//is a tablet & tablet configuration has been activated
				$checkEdAndConfig = self::_mceOrXenDetectByConfigType($myConfigs, $options->Bbm_Bm_Tablets);
				
				if($checkEdAndConfig)
				{
					$config_type = $options->Bbm_Bm_Tablets;
					$theoricalEditor = $checkEdAndConfig;
				}
			}

			return array($theoricalEditor, $config_type);
		}
	}

	protected static function _mceOrXenDetectByConfigType($configs, $configType)
	{
		//May be not the best way to proceed but the easiest
		if(isset($configs['mce'][$configType]))
		{
			self::$editor = 'mce';
			return 'mce';
		}
		elseif(isset($configs['xen'][$configType]))
		{
			self::$editor = 'xen';				
			return 'xen';
		}
		
		return false;
	}
	
	/***
	*	Will output three new params: 1) quattroGrid, 2) customButtonsCss, 3) customButtonsJs
	**/
	
	protected static function _bakeExtraParams($buttons)
	{
		$buttons = unserialize($buttons);

		if(self::$editor == 'mce')
		{
			return self::_bakeQuattroParams($buttons);
		}
		
		return self::_bakeRedactorParams($buttons);
	}	

	protected static function _bakeQuattroParams($buttons)
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();
		
		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));	

		$xenParsingBbCodesPerms = array(
			'email' => $options->Bbm_xenTags_disabled_usrgrp_email,
			'img' => $options->Bbm_xenTags_disabled_usrgrp_img,
			'url' => $options->Bbm_xenTags_disabled_usrgrp_url,
			'media' => $options->Bbm_xenTags_disabled_usrgrp_media
		);
		
		$xenButtonsNameToBbCodes = array(
			'xen_link' => array('email', 'url'),
			'xen_unlink' => array('email', 'url'),
			'xen_image' => array('img'),
			'xen_media' => array('media')
		);
		
		$quattroGrid = array();
		$customButtonsCss = array();
		$customButtonsJs = array();
		
		$lastButtonKey = count($buttons);
		$lineID = 1;

		foreach($buttons as $key => $button)
		{
			$key = $key+1;

			/*Check if button has a tag - should not be needed*/
			if(empty($button['tag']))
			{
				continue;
			}

			$tag = $button['tag'];

			/*Don't display disable buttons*/
			if(isset($button['active']) && !$button['active'])
			{
				continue;
			}

			/*Xen buttons perms based on Xen Bb Codes parsing permissions*/
			if(isset($xenButtonsNameToBbCodes[$tag]))
			{
				$disableBbCodesPerms = false;
				foreach($xenButtonsNameToBbCodes[$tag] as $bbcodeTag)
				{
					if(array_intersect($visitorUserGroupIds, $xenParsingBbCodesPerms[$bbcodeTag]))
					{
						$disableBbCodesPerms = true;
						break;
					}
				}
				
				if($disableBbCodesPerms)
				{
					continue;
				}
			}

			/*Detect new lines & proceed to some changes to the grid*/
			if($tag == 'carriage')
			{
				$lineID++;
				$quattroGrid[$lineID] = array();				
				continue;
			}

			/*Button permissions*/
			if(!empty($button['button_has_usr']))
			{
				$usrOK = unserialize($button['button_usr']);

				if(!array_intersect($visitorUserGroupIds, $usrOK))
				{
					continue;
				}
			}
			
			/*Button Code Formatter*/
			if(!empty($button['button_code']))
			{
				$code = $button['button_code'];
			}
			else
			{
				$code = "bbm_$tag";		

			}
			
			if(!empty($button['bbcode_id']))
			{
				$code = "bbm_".$button['bbcode_id'];
			}

			if(!empty($button['custCmd']))
			{
				$code = $button['custCmd'];
			}

			$tag = self::_cleanOrphan($tag);
			$code = self::_cleanOrphan($code);

			/*Bake the extra CSS for custom Buttons*/
			if(!empty($button['quattro_button_type']) && !in_array($button['quattro_button_type'], array('manual', 'text', 'icons_fa')))
			{
				$btnType = $button['quattro_button_type'];
				
				switch ($btnType) {
					case 'icons_mce':
						$iconSet = 'tinymce';
						break;
					case 'icons_xen':
						$iconSet = 'xenforo';
						break;
					default: $iconSet = $btnType;
				}

				$customButtonsCss[] = array(
					'buttonCode' => $code,
					'iconCode' => $button['quattro_button_type_opt'],
					'iconSet' => $iconSet
				);
			}

			if(!empty($button['quattro_button_type']))
			{
				$btnType = $button['quattro_button_type'];
				
				switch ($btnType) {
					case 'icons_mce':
						$iconSet = 'tinymce';
						break;
					case 'icons_xen':
						$iconSet = 'xenforo';
						break;
					default: $iconSet = $btnType;
				}

				$customButtonsJs[] = array(
					'tag'	=> $tag,
					'code' => $code,
					'iconSet' => $iconSet,
					'type' => $btnType,
					'typeOption' => self::_detectPhrases($button['quattro_button_type_opt']),
					'return' => (!empty($button['killCmd'])) ? 'kill' : $button['quattro_button_return'],
					'returnOption' => self::_detectPhrases($button['quattro_button_return_opt']),
					'description' => self::_detectPhrases($button['buttonDesc']),
					'tagOptions' => self::_detectPhrases($button['tagOptions']),
					'tagContent' => self::_detectPhrases($button['tagContent']),
					'separator' => (empty($button['options_separator'])) ? $options->Bbm_BbCode_Options_Separator : $button['options_separator']
				);
			}
			
			/*XenForo Custom BbCode Management*/
			if(strpos($tag, 'custom_') === 0)
			{
				$tag = substr($tag, 7);

				$customButtonsJs[] = array(
					'tag' => $tag,
					'code' => $code,
					'iconSet' => false,
					'type' => 'xenCustom',
					'typeOption' => '',
					'return' => 'direct',
					'returnOption' => '',
					'description' => '',
					'tagOptions' => '',
					'tagContent' => '',
					'separator' => $options->Bbm_BbCode_Options_Separator			
				);
			}

			/*Bake the grid*/
			$quattroGrid[$lineID][] = $code;
		}

		foreach($quattroGrid as &$buttons)
		{
			$buttons = implode(" ", $buttons);
		}

		return array(
			'quattroGrid' => $quattroGrid,
			'customQuattroButtonsCss' => $customButtonsCss,
			'customQuattroButtonsJs' => $customButtonsJs
		);
	}

	protected static function _bakeRedactorParams($buttons)
	{
		$options = XenForo_Application::get('options');
		$visitor = XenForo_Visitor::getInstance();

		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));	

		$xenParsingBbCodesPerms = array(
			'email' => $options->Bbm_xenTags_disabled_usrgrp_email,
			'img' => $options->Bbm_xenTags_disabled_usrgrp_img,
			'url' => $options->Bbm_xenTags_disabled_usrgrp_url,
			'media' => $options->Bbm_xenTags_disabled_usrgrp_media
		);
		
		$xenButtonsNameToBbCodes = array(
			'createlink' => array('email', 'url'),
			'unlink' => array('email', 'url'),
			'image' => array('img'),
			'media' => array('media')
		);
		
		$buttonsGrid = array();
		$customButtons = array();
		$customButtonsCss = array();
		
		$btn_group_id = 1;

		foreach($buttons as $button)
		{
			/*Check if button has a tag - should not be needed*/
			if(empty($button['tag']))
			{
				continue;
			}

			$tag = $button['tag'];

			/*Don't display disable buttons*/
			if(isset($button['active']) && !$button['active'])
			{
				continue;
			}

			/*Button permissions*/
			if(!empty($button['button_has_usr']))
			{
				$usrOK = unserialize($button['button_usr']);

				if(!array_intersect($visitorUserGroupIds, $usrOK))
				{
					continue;
				}
			}

			/*Button Code Formatter*/
			if(!empty($button['button_code']))
			{
				$code = $button['button_code'];
			}
			else
			{
				$code = "bbm_$tag";		

			}
			
			if(!empty($button['bbcode_id']))
			{
				$code = "bbm_".$button['bbcode_id'];
			}

			if(!empty($button['custCmd']))
			{
				$code = $button['custCmd'];
			}

			if(!empty($button['class']) && $button['class'] == 'xenButton')
			{
				$tag = $code = str_replace('-', '', $tag);
			}

			$tag = self::_cleanOrphan($tag);
			$code = self::_cleanOrphan($code);

			/*Xen buttons perms based on Xen Bb Codes parsing permissions*/
			if(isset($xenButtonsNameToBbCodes[$tag]))
			{
				$disableBbCodesPerms = false;
				foreach($xenButtonsNameToBbCodes[$tag] as $bbcodeTag)
				{
					if(array_intersect($visitorUserGroupIds, $xenParsingBbCodesPerms[$bbcodeTag]))
					{
						$disableBbCodesPerms = true;
						break;
					}
				}
				
				if($disableBbCodesPerms)
				{
					continue;
				}
			}

			if($tag == 'separator')
			{
				$btn_group_id++;
			}
			else
			{
				$buttonsGrid[$btn_group_id][] = $code;
			}

			if(isset($button['tagContent']))
			{
				$textButton = false;
				if(isset($button['redactor_button_type']) && $button['redactor_button_type'] == 'text')
				{
					$textButton = self::_detectPhrases($button['redactor_button_type_opt']);
				}

				$faButton = false;
				if(isset($button['redactor_button_type']) && $button['redactor_button_type'] == 'icons_fa')
				{
					$faButton = $button['redactor_button_type_opt'];
				}
				
				$customButtons[] = array(
					'tag'	=> $tag,
					'code' => $code,
					'description' => self::_detectPhrases($button['buttonDesc']),
					'tagOptions' => self::_detectPhrases($button['tagOptions']),
					'tagContent' => self::_detectPhrases($button['tagContent']),
					'separator' => (empty($button['options_separator'])) ? $options->Bbm_BbCode_Options_Separator : $button['options_separator'],
					'textButton' => $textButton,
					'faButton' => $faButton
				);
			}

			if(	!empty($button['redactor_has_icon']) && !empty($button['redactor_image_url']) 
				&& isset($button['redactor_button_type']) && !in_array($button['redactor_button_type'], array('text', 'icons_fa'))
			)
			{			
				$customButtonsCss[] = array(
					'tag'	=> $tag,
					'code' => $code,
					'url' => $button['redactor_image_url'],
					'isSprite' => $button['redactor_sprite_mode'],
					'pos' => array(
						'x' =>$button['redactor_sprite_params_x'],
						'y' => $button['redactor_sprite_params_y']
					)
				);
			}
		}

		return array(
			'bbmButtonsJsGrid' => self::flattenRedactorButtonsGrid($buttonsGrid),
			'bbmButtonsJsGridArray' => $buttonsGrid,
			'bbmCustomButtons' => $customButtons,
			'bbmCustomCss' => $customButtonsCss
		);
	}

	public static function flattenRedactorButtonsGrid($buttonsGrid)
	{
		if(!empty($buttonsGrid))
		{
			foreach($buttonsGrid as &$buttons)
			{
				$buttons = '["'.implode('", "', $buttons).'"]';
			}
			
			$buttonsGrid = implode(',', $buttonsGrid);
		}
		else
		{
			$buttonsGrid = false;
		}
		
		return $buttonsGrid;
	}
	
	/***
		This function is used to replace the aerobase of the orphan buttons by at_
		Reason: the @ charachter can't be used as an object key in js
	**/
	protected static function _cleanOrphan($string)
	{
		return str_replace('@', 'at_', $string);
	}

	protected static function _detectPhrases($string, $jsEscape = false)
	{
		if(preg_match_all('#{phrase:(.+?)}#i', $string, $captures, PREG_SET_ORDER))
		{
			foreach($captures as $capture)
			{
				$phrase = new XenForo_Phrase($capture[1]);
				$string = str_replace($capture[0], $phrase, $string);
			}
		}
		
		if($jsEscape == true)
		{
			return XenForo_Template_Helper_Core::jsEscape($string);
		}
		
		return $string;		
	}

	protected static function _fallBack($debug)
	{
		//var_dump($debug);
		if(self::$editor == 'mce')
		{
			return self::_mceFallback();
		}
		elseif(self::$editor == 'xen')
		{
			return self::_xenFallback();
		}
		else
		{
			return self::_safeFallback();
		}
	}
	
	protected static function _mceFallback()
	{
		return array(
			'quattroGrid' => array(),
			'customQuattroButtonsCss' => array(),
			'customQuattroButtonsJs' => array(),
			'loadQuattro' => true			
		);	
	}

	protected static function _xenFallback()
	{
		return array(
			'bbmButtonsJsGrid' => '',
			'bbmButtonsJsGridArray' => array(),
			'bbmCustomButtons' => array(),
			'bbmCustomCss' => array(),
			'loadQuattro' => false
		);	
	}

	protected static function _safeFallback()
	{
		return array(
			'quattroGrid' => array(),
			'customQuattroButtonsCss' => array(),
			'customQuattroButtonsJs' => array(),
			'bbmButtonsJsGrid' => '',
			'bbmButtonsJsGridArray' => array(),
			'bbmCustomButtons' => array(),
			'bbmCustomCss' => array(),
			'loadQuattro' => null			
		);	
	}		
}
//Zend_Debug::dump($bbmEditor);