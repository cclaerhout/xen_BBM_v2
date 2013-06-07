<?php

class BBM_Protection_BbCode_Formatter_BbCode_Eradicator extends XenForo_BbCode_Formatter_BbCode_Abstract
{
	protected $_generalTagCallback = array('$this', 'deleteAllTagsContent');
	protected $_protectedTags = null;	

	protected $_checkVisitorPerms = true;

	public function setCheckVisitorPerms($value = true)
	{
		$this->_checkVisitorPerms = $value;

		if($value === false)
		{
			$this->_bakeProtectedTags();
		}
		
		return $this;
	}

	public function getTags()
	{
		//The below line is needed to prevent an error
		$parentTags = parent::getTags();

		if($this->_protectedTags === null)
		{
			$this->_bakeProtectedTags();
		}

		if($this->_protectedTags !== null)
		{
			$this->_tags = $this->_protectedTags;

			return $this->_protectedTags;
		}

		return false;
	}


	public function _bakeProtectedTags()
	{
		$bbmCached = XenForo_Application::getSimpleCacheData('bbm_active');

		if(	!is_array($bbmCached) 
			|| !isset($bbmCached['protected']) 
			|| !is_array($bbmCached['protected'])
			|| empty($bbmCached['protected'])
		)
		{
			return false;
		}

		$allProtectedTags = array();
		$protectedTags = $bbmCached['protected'];

		if($this->_checkVisitorPerms == true)
		{
			$visitor = XenForo_Visitor::getInstance();
			$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
		}

		foreach($protectedTags AS $tag => $perms)
		{
			if($this->_checkVisitorPerms == true && array_intersect($visitorUserGroupIds, $perms))
			{
				continue;
			}
			$allProtectedTags[$tag] = array(
				'callback' => $this->_generalTagCallback
			);
		}

		 $this->_protectedTags = $allProtectedTags;
	}


	public function deleteAllTagsContent(array $tag, array $rendererStates)
	{

		return new XenForo_Phrase('bbm_viewer_content_protected');
	}
}