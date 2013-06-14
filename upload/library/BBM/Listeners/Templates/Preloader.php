<?php

class BBM_Listeners_Templates_Preloader
{
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName) 
		{
		   	case 'help_bb_codes':
				$template->preloadTemplate('help_bbm_bbcodes');
	   			break;
		   	case 'editor':
	   			/***
	   				Templates Preloader
	   			***/
	   			if(XenForo_Application::get('options')->get('Bbm_Bm_ShowControllerInfo'))
				{
					$template->preloadTemplate('bbm_editor_extra_info');
				}

	   			/***
	   				ADD PARAMS TO THE EDITOR TEMPLATE
	   			***/
				$options = XenForo_Application::get('options');
	
				if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
				{
					break;
				}
	
				if(!empty($options->bbm_debug_tinymcehookdisable))
				{
					break;
				}


			       	$visitor = XenForo_Visitor::getInstance();


				//Don't continue if Quattro not available (might need to change this for other editor integrations
				if(!isset($visitor->permissions['sedo_quattro']['display']) || empty($visitor->permissions['sedo_quattro']['display']))
				{
					break;
				}
	
				//Get buttons config
				$myConfigs = XenForo_Model::create('XenForo_Model_DataRegistry')->get('bbm_buttons');
							
				if(empty($myConfigs))
				{
					break;
				}
	
				//Check which Editor type must be used
				$config_type = self::_bakeEditorConfig($template, $options, $visitor, $myConfigs);
	
				if(empty($myConfigs['bbm_buttons'][$config_type]['config_buttons_order']))
				{
					break;
				}

				$extraParams = self::_bakeExtraParams($myConfigs['bbm_buttons'][$config_type]['config_buttons_full'], $options, $visitor);
				$params = $extraParams+$params; //array + operator: first params overrides the second - is said faster than array_merge
	   			break;
		   	case 'forum_edit':
		   		if($template instanceof XenForo_Template_Admin && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
	   			{
					$template->preloadTemplate('bbm_forum_edit_bbm_editor');
			   	}
		   		break;
		   	case 'home':
		   		if($template instanceof XenForo_Template_Admin)
	   			{
	   				$template->preloadTemplate('bbm_admin_icon');
		   		}
		   		break;
		}
	}

	protected static function _bakeEditorConfig($template, $options, $visitor, $myConfigs)
	{
		/****
		*	Check Text Direction
		***/
		$config_type = ($template->getParam('pageIsRtl') === true) ? 'rtl' : 'ltr';

		/****
		*	Check controller datas
		***/
		$custConfigs = $options->Bbm_Bm_Cust_Config;
	
		if(!empty($custConfigs) || is_array($custConfigs))
		{
			$controllerName = $template->getParam('controllerName');
			$controllerAction = $template->getParam('controllerAction');
			$viewName = $template->getParam('viewName');

			$scores = array('0' => $config_type);
			foreach($custConfigs as $custConfig)
			{
				$points = 1;
				$points = ($controllerName == $custConfig['controllername']) ? $points+1 : $points;
				$points = ($controllerAction == $custConfig['controlleraction']) ? $points+1 : $points;
				$points = ($viewName == $custConfig['viewname']) ? $points+1 : $points;	
				
				if($points > 1)
				{
					$scores[$points] = $custConfig['configtype'];
				}
			}
			
			$winnerKey = max(array_keys($scores));
			//Sorry but if competitors are ex aequo, the last one wins
			$winner = $scores[$winnerKey];
			
			//Anti-doping test
			$config_type = (isset($myConfigs['bbm_buttons'][$winner])) ? $winner : $config_type;
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
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Mobile])) ? $options->Bbm_Bm_Mobile : $config_type;
			}
			
			return $config_type;
		}
		else
		{
			//External addon is installed

			if(!$visitor->getBrowser['isMobile'])
			{
				//is not mobile
				return $config_type;
			}
			
			if($visitor->getBrowser['isTablet'] && $options->Bbm_Bm_Tablets != 'transparent')
			{
				//is a tablet & transparent mode has been activated
				$config_type = $options->Bbm_Bm_Tablets;
			}
			
			if($visitor->getBrowser['isMobile'] && $options->Bbm_Bm_Mobile != 'disable')
			{
				//is a mobile device and mobile configuration has been activated
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Mobile])) ? $options->Bbm_Bm_Mobile : $config_type;
			}
			
			if($visitor->getBrowser['isTablet'] && $options->Bbm_Bm_Tablets != 'disable')
			{
				//is a tablet & tablet configuration has been activated
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Tablets])) ? $options->Bbm_Bm_Tablets : $config_type;				
			}

			return $config_type;		
		}
	}
	
	/***
	*	Will output three new params: 1) quattroGrid, 2) customButtonsCss, 3) customButtonsJs
	**/
	
	protected static function _bakeExtraParams($buttons, $options, $visitor)
	{
		$buttons = unserialize($buttons);
		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));	
		
		$quattroGrid = array();
		$customButtonsCss = array();
		$customButtonsJs = array();
		
		$lastButtonKey = count($buttons);
		$lineID = 1;
		
		foreach($buttons as $key => $button)
		{
			$key = $key+1;

			/*Check if button has a tag - should not be needed*/
			if(!isset($button['tag']))
			{
				continue;
			}

			/*Don't display disable buttons*/
			if(isset($button['active']) && !$button['active'])
			{
				continue;
			}

			/*Detect new lines & proceed to some changes to the grid*/
			if($button['tag'] == 'carriage')
			{
				$quattroGrid[$lineID] = implode(' ', $quattroGrid[$lineID]);
				$lineID++;
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

			/*Check if button has a code - should not be needed*/
			if(empty($button['button_code']))
			{
				$button['button_code'] = (!empty($button['custCmd'])) ? $button['custCmd'] : 'bbm_'.$button['tag'];
			}

			$tag = self::_ifOrphanCleanMe($button['tag']);
			$code = self::_ifOrphanCleanMe($button['button_code']);


			/*Bake the extra CSS for custom Buttons*/
			if(!empty($button['quattro_button_type']) && !in_array($button['quattro_button_type'], array('manual', 'text')))
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
					'return' => $button['quattro_button_return'],
					'returnOption' => self::_detectPhrases($button['quattro_button_return_opt']),
					'description' => self::_detectPhrases($button['buttonDesc']),
					'tagOptions' => self::_detectPhrases($button['tagOptions']),
					'tagContent' => self::_detectPhrases($button['tagContent'])
				);
			}

			/*Bake the grid*/
			$quattroGrid[$lineID][] = $code;

			if($key == $lastButtonKey)
			{
				$quattroGrid[$lineID] = implode(' ', $quattroGrid[$lineID]);
			}
		}

		return array(
			'quattroGrid' => $quattroGrid,
			'customButtonsCss' => $customButtonsCss,
			'customButtonsJs' => $customButtonsJs
		);
	}
	
	/***
		This function is used to replace the aerobase of the orphan buttons by at_
		Reason: the @ charachter can't be used as an object key in js
	**/
	protected static function _ifOrphanCleanMe($string)
	{
		return str_replace('@', 'at_', $string);
	}

	protected static function _detectPhrases($string, $addSlashes = false)
	{
		if(preg_match_all('#{phrase:(.+?)}#i', $string, $captures, PREG_SET_ORDER))
		{
			foreach($captures as $capture)
			{
				$phrase = new XenForo_Phrase($capture[1]);
				$string = str_replace($capture[0], $phrase, $string);
			}
		}
		
		if($addSlashes == true)
		{
			return addslashes($string);
		}
		
		return $string;		
	}
}
//	Zend_Debug::dump($abc);