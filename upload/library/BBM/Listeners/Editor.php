<?php
class BBM_Listeners_Editor
{
	public static function addRedactorButtons(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
	{
		if (!$showWysiwyg)
		{
			return false;
		}
		
		//To do
		return false;
		
		$template = $view->createOwnTemplateObject();
		$controllerName = $template->getParam('controllerName');
		$controllerAction = $template->getParam('controllerAction');
		$viewName = $template->getParam('viewName');		
		
		$bbmParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);
		$bbmButtonsJsGrid = $bbmParams['bbmButtonsJsGrid'];
		$bbmCustomButtons = $bbmParams['bbmCustomButtons'];
	}
}