<?php

class BBM_BbCode_Formatter_Wysiwyg extends XFCP_BBM_BbCode_Formatter_Wysiwyg
{
	/****
	*	CREATE CUSTOM TAGS
	***/
	protected $_bbmTags = null;

	protected $_xenOriginalTags = array(
		'b', 'i', 'u', 's', 'color', 'font', 'size', 'left', 'center', 
		'right', 'indent', 'url', 'email', 'img', 'quote', 'code', 'php', 
		'html', 'plain', 'media', 'attach'
	);
	
	//@extended
	public function getTags()
	{
		$parentTags = parent::getTags();

		if($this->_bbmTags === null)
		{
			$this->bakeBbmTags();
		}
		
		if($this->_bbmTags !== null)
		{
			return array_merge((array) $parentTags, (array) $this->_bbmTags);
		}
		
		return $parentTags;
	}

	public function bakeBbmTags()
	{
		$bbmTags = BBM_Helper_Bbm::getBbmBbCodes();
		$activeAddons = (XenForo_Application::isRegistered('addOns')) ? XenForo_Application::get('addOns') : array();
		$visitor = XenForo_Visitor::getInstance();

		if(!is_array($bbmTags))
		{
			return false;
		}

		$allBbmTags = array();

		foreach($bbmTags AS $bbm)
		{
			if(!$bbm['active'])
			{
				continue;
			}

			if( !empty($activeAddons) && !empty($bbm['bbcode_addon']))
			{
				if( !isset($activeAddons[$bbm['bbcode_addon']]) )
				{
					//Skip Bb Codes linked to an addon when this addon is disabled
					continue;
				}
			}

			$tagName = $bbm['tag'];

      			if(!empty($bbm['preParser']))
      			{
      				$this->addPreParserBbCode($tagName);
      			}
		}

		$this->_bbmTags = $allBbmTags;
		
		/****
		*	XenForo Options - only need to call once the options
		***/
		$options = XenForo_Application::get('options');
		
		if(!empty($options->Bbm_PreCache_XenTags))
		{
			foreach($options->Bbm_PreCache_XenTags as $tagName)
			{
				$this->addPreParserBbCode($tagName);
			}
		}
	}

	protected function _bbmCallbackChecker($class, $method = null)
	{
		if($method != null)
		{
			return (class_exists($class) && method_exists($class, $method));
		}
		
		return class_exists($class);
	}

	/****
	*	PREPARSER BB-CODES	
	*	Bb Codes that can use the pre-parser function. Purpose: 
	*	limit the renderer execution to only those that need it
	***/
	protected $_bbmPreParserBbCodes = array();
	
	public function addPreParserBbCode($tagName)
	{
		$this->_bbmPreParserBbCodes[$tagName] = true;
	}

	public function getPreParserBbCodes()
	{
		return $this->_bbmPreParserBbCodes;
	}
	
	public function preParserEnableFor($tagName)
	{
		return isset($this->_bbmPreParserBbCodes[$tagName]);
	}

	/****
	*	CLASS LOADER TOOLS
	*	Reason: no need to load class several times
	***/
	protected $_classToLoad = array();
	
	protected function _prepareClassToLoad($class)
	{
		if(!in_array($class, $this->_classToLoad))
		{
			$this->_classToLoad[] = $class;
		}
	}
	
	protected function _loadClass($class)
	{
		if(in_array($class, $this->_classToLoad))
		{
			XenForo_Application::autoload($class);
			$key = array_search($class, $this->_classToLoad);
			unset($this->_classToLoad[$key]);
		}		
	}

	/****
	*	PARSER TOOLS
	***/
	protected $_bbmParser;

	public function getParser()
	{
		if (!isset($this->_bbmParser))
		{
			$this->_bbmParser = new XenForo_BbCode_Parser($this);
		}
		return $this->_bbmParser;
	}	
}
//Zend_Debug::dump($abc);