<?php

class BBM_Protection_BbCode_Formatter_BbCode_Lupin extends XenForo_BbCode_Formatter_BbCode_Abstract
{
	protected $_allTags = null;	

	public function getTags()
	{
		$parentTags = parent::getTags();

		if($this->_allTags === null)
		{
			$this->_bakeAllTags($parentTags);
		}

		if($this->_allTags !== null)
		{
			$this->_tags = $this->_allTags;

			return $this->_allTags;
		}

		return false;
	}


	public function _bakeAllTags($parentTags)
	{
		$bbmCache = XenForo_Application::getSimpleCacheData('bbm_active');
		$bbmTags = $bbmCache['list'];
		$allTags = $parentTags;
		
		foreach($bbmTags as $bbmTag)
		{
			$allTags[$bbmTag] = array();
		}

		foreach($allTags AS &$tag)
		{
			foreach($tag as $key => $config)
			{
				unset($tag[$key]);
			}
			
			$tag['replace'] = array('', '');
		}

		 $this->_allTags = $allTags;
	}


	public function deleteAllTagsContent(array $tag, array $rendererStates)
	{

		return new XenForo_Phrase('bbm_viewer_content_protected');
	}
}