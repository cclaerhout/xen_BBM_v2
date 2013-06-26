<?php

class BBM_Listeners_Templates_Preloader
{
	protected static $editor;
	
	public static function preloader($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName) 
		{
		   	case 'help_bb_codes':
				$template->preloadTemplate('help_bbm_bbcodes');
	   			break;
		   	case 'editor':
	   			/***
	   				Templates Preloader
	   			***/
	   			if(XenForo_Application::get('options')->get('Bbm_Bm_ShowControllerInfo'))
				{
					$template->preloadTemplate('bbm_editor_extra_info');
				}

	   			/***
	   				ADD PARAMS TO THE EDITOR TEMPLATE
	   			***/
				$options = XenForo_Application::get('options');
	
				if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
				{
					break;
				}

				$controllerName = $template->getParam('controllerName');
				$controllerAction = $template->getParam('controllerAction');
				$viewName = $template->getParam('viewName');
			
				$extraParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);
				$params = $extraParams+$params; //array + operator: first params overrides the second - is said faster than array_merge
				
	   			break;
		   	case 'forum_edit':
		   		if($template instanceof XenForo_Template_Admin && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
	   			{
					$template->preloadTemplate('bbm_forum_edit_bbm_editor');
			   	}
		   		break;
		   	case 'home':
		   		if($template instanceof XenForo_Template_Admin)
	   			{
	   				$template->preloadTemplate('bbm_admin_icon');
		   		}
		   		break;
		}
	}

	/***
		REDACTOR: Template callback if needed
		=> will be used as a fallback	
	*/

	public static function getJsConfig($content, $params, XenForo_Template_Abstract $template)
	{
		$options = XenForo_Application::get('options');
	
		if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
		{
			return;
		}
	
		$controllerName = $template->getParam('controllerName');
		$controllerAction = $template->getParam('controllerAction');
		$viewName = $template->getParam('viewName');
			
		$params = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);

		$bbmButtonsJsGrid = $params['bbmButtonsJsGrid'];
		$bbmCustomButtons = $params['bbmCustomButtons'];
		
		$output = "<script>var BBM_Redactor = {	buttonsGrid: [$bbmButtonsJsGrid],customButtonsConfig:{";
		
		$i = 1;
		$total = count($bbmCustomButtons);
		
		if(is_array($bbmCustomButtons))
		{
			foreach($bbmCustomButtons as $button)
			{
				$coma = ($i != $total) ? ',' : '';
				$tag = $button['tag'];
				$code =  $button['code'];
				$desc = XenForo_Template_Helper_Core::jsEscape($button['description']);
				$opts = XenForo_Template_Helper_Core::jsEscape($button['tagOptions']);
				$content = XenForo_Template_Helper_Core::jsEscape($button['tagContent']);
				$separator = XenForo_Template_Helper_Core::jsEscape($button['seprarator']);
		
				$output .= "$code:{tag:\"$tag\",code:\"$code\",description:\"$desc\",tagOptions:\"$opts\",tagContent:\"$content\",separator:\"$separator\"}$coma";
			}
		}
		
		$output .= '}};</script>';
		
		return $output;
	}

}
//	Zend_Debug::dump($abc);