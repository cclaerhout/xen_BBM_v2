<?php

class BBM_Listeners_BbCode
{
	public static function listen($class, array &$extend)
	{
		if (!class_exists('KingK_BbCodeManager_BbCodeManager'))
		{
			if ($class == 'XenForo_BbCode_Formatter_BbCode_AutoLink')
		      	{
		      		$extend[] = 'BBM_BbCode_Formatter_BbCode_AutoLink';
		      	}
		
		        if ($class == 'XenForo_BbCode_Formatter_Base')
		        {
		            $extend[] = 'BBM_BbCode_Formatter_Base';
		        }
		        
		      	if ($class == 'XenForo_ControllerPublic_Help')
		      	{
		      		$extend[] = 'BBM_ControllerPublic_Help';
		      	}
		}
	}
}