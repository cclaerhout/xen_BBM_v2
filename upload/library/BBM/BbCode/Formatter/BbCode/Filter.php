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
				unset($tag['replace'], $tag['callback'], $tag['trimLeadingLinesAfter']);
				$tag['callback'] = $this->_generalTagCallback;
				$parentTags[$tagName] = $tag;
			}
		}

		return $parentTags;
	}
}