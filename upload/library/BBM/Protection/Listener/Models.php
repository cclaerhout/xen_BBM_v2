<?php

class BBM_Protection_Listener_Models
{
	public static function protectModels($class, array &$extend)
	{
        	switch($class)
		{
        		case 'XenForo_Model_NewsFeed':
        			$extend[] = 'BBM_Protection_Model_NewsFeed';
                	break;
            		case 'XenForo_Model_Search':
                		$extend[] = 'BBM_Protection_Model_Search';
                	break;
        		case 'XenForo_Model_ThreadWatch':
                		$extend[] = 'BBM_Protection_Model_ThreadWatch';
                	break;
        	}
	}
}
