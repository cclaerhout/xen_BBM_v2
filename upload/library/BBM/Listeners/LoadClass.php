<?php

class BBM_Listeners_LoadClass
{
	public static function modifyParser($class, array &$extend)
	{
		if ($class == 'XenForo_BbCode_Parser' && XenForo_Application::get('options')->get('Bbm_modify_parser'))
	      	{
			$extend[] = 'BBM_BbCode_Parser';
	      	}
	}
}