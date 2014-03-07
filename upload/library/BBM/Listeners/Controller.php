<?php

class BBM_Listeners_Controller
{
	public static function ControllerAdmin($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerAdmin_Forum' && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
	      	{
      			$extend[] = 'BBM_ControllerAdmin_Forum';
	      	}
	}
}