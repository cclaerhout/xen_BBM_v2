<?php

class BBM_Protection_Model_NewsFeed extends XFCP_BBM_Protection_Model_NewsFeed
{
	public function fillOutNewsFeedItems(array $newsFeed, array $viewingUser)
	{
		$parent = parent::fillOutNewsFeedItems($newsFeed, $viewingUser);

		if(!is_array($parent))
		{
			return $parent;
		}

		foreach ($parent AS &$item )
		{
			if( isset($item['content']['message']) )
			{
				$item['content']['message'] = BBM_Protection_Helper_ContentProtection::parsingProtection($item['content']['message']);
			}
		}

		return $parent;
	} 
}