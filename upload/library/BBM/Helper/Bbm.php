<?php

class BBM_Helper_Bbm
{
	public static function callbackChecker($class, $method)
	{
		if(!empty($method))
		{
			return (class_exists($class) && method_exists($class, $method));
		}
		
		return class_exists($class);
	}

	public static function scanXmlFile($xmlFile)
	{
		if(self::callbackChecker('XenForo_Helper_DevelopmentXml', 'scanFile'))
		{
			//Protected method
			$file = XenForo_Helper_DevelopmentXml::scanFile($xmlFile);
		}
		else
		{
			//Classic PHP method
			$file = new SimpleXMLElement($xmlFile, null, true);
		}
		
		return $file;
	}

	public static function scanXmlString($xmlString)
	{
		if(self::callbackChecker('Zend_Xml_Security', 'scan'))
		{
			//Protected method
			$xmlObj = Zend_Xml_Security::scan($xmlString);

			if (!$xmlObj)
			{
				throw new XenForo_Exception("Invalid XML in $xmlObj");
			}
		}
		else
		{
			//Classic PHP method
			$xmlObj = simplexml_load_string($xmlString);
		}
		
		return $xmlObj;
	}
	
	public static function getColumnsToKeepInRegistry()
	{
		return array('tag_id', 'bbcode_id', 'tag', 'active', 'hasButton',
			'button_has_usr', 'button_usr', 'killCmd', 'custCmd',
			'buttonDesc', 'tagOptions', 'tagContent', 'options_separator',
			'quattro_button_type', 'quattro_button_type_opt', 'quattro_button_return', 'quattro_button_return_opt',
			'redactor_has_icon', 'redactor_sprite_mode', 'redactor_image_url', 'redactor_sprite_params_x', 'redactor_sprite_params_y',
			'redactor_button_type', 'redactor_button_type_opt'
		);
	}

	public static function getXenCustomBbCodes($onlyTagNames = false)
	{
		if (XenForo_Application::isRegistered('bbCode'))
		{
			$xenBbCodes = XenForo_Application::get('bbCode');
		}
		else
		{
			$xenBbCodes = XenForo_Model::create('XenForo_Model_BbCode')->getBbCodeCache();
			XenForo_Application::set('bbCode', $xenBbCodes);
		}

		if(!empty($xenBbCodes['bbCodes']))
		{
			if($onlyTagNames)
			{
				return array_keys($xenBbCodes['bbCodes']);
			}
			
			return $xenBbCodes['bbCodes'];
		}
		
		return array();
	}

	public static function getXenCustomBbCode($tagName)
	{
		$xenCustomBbCodes = self::getXenCustomBbCodes();

		if(!isset($xenCustomBbCodes[$tagName]))
		{
			return false;
		}		
	
		return $xenCustomBbCodes[$tagName];
	}	
	
	public static function getBbmBbCodes()
	{
		if (XenForo_Application::isRegistered('bbm_bbcodes'))
		{
			$bbmTags = XenForo_Application::get('bbm_bbcodes');
		}
		else
		{
			$bbmTags = XenForo_Model::create('BBM_Model_BbCodes')->getAllBbCodes('strict');
			XenForo_Application::set('bbm_bbcodes', $bbmTags);
		}
		
		return 	$bbmTags;
	}

	public static function getBbmButtons()
	{
		if (XenForo_Application::isRegistered('bbm_buttons'))
		{
			$bbmButtons = XenForo_Application::get('bbm_buttons');
		}
		else
		{
			$bbmButtons = XenForo_Model::create('XenForo_Model_DataRegistry')->get('bbm_buttons');
			XenForo_Application::set('bbm_buttons', $bbmButtons);
		}
		
		return 	$bbmButtons;
	}
	
	public static function checkIfAddonActive($addonId, $realReturn = false)
	{
		if(!XenForo_Application::isRegistered('addOns'))
		{
			return ($realReturn) ? false : true; //XenForo 1.1
		}

		$activeAddons = XenForo_Application::get('addOns');
		
		return isset($activeAddons[$addonId]);
	}
}