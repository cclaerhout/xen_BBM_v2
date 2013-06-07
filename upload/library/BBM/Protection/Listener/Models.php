<?php

class BBM_Protection_Listener_Models
{
	public static function protectModels($class, array &$extend)
	{
		if ($class == 'XenForo_Model_NewsFeed')
		{
			$extend[] = 'BBM_Protection_Model_NewsFeed';
		}
		
		if ($class == 'XenForo_Model_Search')
	      	{
			$extend[] = 'BBM_Protection_Model_Search';	      	
	      	}

	        if ($class == 'XenForo_Model_ThreadWatch')
      		{
			$extend[] = 'BBM_Protection_Model_ThreadWatch';
	      	} 
	}
}