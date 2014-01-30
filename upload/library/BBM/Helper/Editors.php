<?php

class BBM_Helper_Editors
{
	public static function getCompatibility()
	{
		$redactorSupport = (XenForo_Application::get('options')->get('currentVersionId') >= 1020031);
		$mceSupport = !$redactorSupport;
		
		if (class_exists('Sedo_TinyQuattro_Helper_Quattro') && method_exists('Sedo_TinyQuattro_Helper_Quattro', 'isEnabled') && $redactorSupport)
		{
			//$redactorSupport added to conditional to match only XenForo > 1.2
			$activeAddons =  XenForo_Application::get('addOns');
			$mceSupport = (!empty($activeAddons['sedo_tinymce_quattro'])) ? true : false;
		}

		return array($mceSupport, $redactorSupport);
	}
}