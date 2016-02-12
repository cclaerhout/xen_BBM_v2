<?php

class BBM_BbCode_Formatter_BbCode_Filter extends XFCP_BBM_BbCode_Formatter_BbCode_Filter
{
	public function getTags()
	{
		$parentTags = parent::getTags();

		$baseFormatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_Base');
		$baseFormatterTags = $baseFormatter->getTags();

		if($baseFormatterTags !== null)
		{
			foreach($baseFormatterTags as $tagName => $tag)
			{
				if(!isset($parentTags[$tagName]))
				{
					$parentTags[$tagName] = $this->_filterTagDefinition($tagName, $tag);
				}
			}
		}

		return $parentTags;
	}
}