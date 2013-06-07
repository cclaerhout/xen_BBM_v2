<?php

class BBM_Protection_Model_Search extends XFCP_BBM_Protection_Model_Search
{
	public function getSearchResultsForDisplay(array $results, array $viewingUser = null)
	{
		$parent = parent::getSearchResultsForDisplay($results, $viewingUser = null);

		if (isset($parent['results']) && is_array($parent['results']))
		{
			foreach($parent['results'] AS &$result)
			{
				//Hide in search results - the word will still be found, but it won't be displayed in the preview
				if (isset($result['content']['message']))
				{
					$result['content']['message'] = BBM_Protection_Helper_ContentProtection::parsingProtection($result['content']['message']);
				}
			}
		}

		return $parent;
	}
}				