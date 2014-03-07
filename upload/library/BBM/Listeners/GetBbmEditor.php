<?php

class BBM_Listeners_GetBbmEditor
{
	public static function getBbmEditor(XenForo_FrontController $fc, XenForo_ControllerResponse_Abstract &$controllerResponse, XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams)
	{
		$bbmEditor = (isset($controllerResponse->params['forum']['bbm_bm_editor'])) ? $controllerResponse->params['forum']['bbm_bm_editor'] : false;
		XenForo_Application::set('bbm_bm_editor', $bbmEditor);
	}
}
//Zend_Debug::dump($bbmEditor);