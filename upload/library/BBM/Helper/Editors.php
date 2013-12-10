<?php

class BBM_Helper_Editors
{
	public static function getCompatibility()
	{
		$redactorSupport = (XenForo_Application::get('options')->get('currentVersionId') >= 1020031);
		$mceSupport = !$redactorSupport;
		
		if (method_exists('Sedo_TinyQuattro_Helper_Quattro', 'isEnabled'))
		{
			$activeAddons =  XenForo_Application::get('addOns');
			$mceSupport = (!empty($activeAddons['sedo_tinymce_quattro'])) ? true : false;
		}

		return array($mceSupport, $redactorSupport);
	}
}