<?php

class BBM_Helper_Editors
{
	public static function getCompatibility()
	{
		$redactorSupport = (XenForo_Application::get('options')->get('currentVersionId') >= 1020031);
		$mceSupport = true;
		
		if (method_exists('Sedo_TinyQuattro_Helper_Quattro', 'isEnabled') && $redactorSupport)
		{
			$mceSupport = Sedo_TinyQuattro_Helper_Quattro::isEnabled();
		}
		
		return array($mceSupport, $redactorSupport);
	}
}