<?php

class BBM_Listeners_Templates_Public
{
	public static function hooks($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (!class_exists('KingK_BbCodeManager_BbCodeManager'))
		{
			switch ($hookName) 
			{
			   	case 'editor':
					$options = XenForo_Application::get('options');
					$visitor = XenForo_Visitor::getInstance();
				
			   		if($options->Bbm_Bm_ShowControllerInfo && $visitor['is_admin'])
					{
						$contents .= $template->create('bbm_editor_extra_info', $template->getParams());
					}
			   		break;
				case 'help_bb_codes':
					$contents .= $template->create('help_bbm_bbcodes', $template->getParams());
					break;
				case 'page_container_head':
					if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
					{
						break;
					}
				
					//Extra css
					$contents .= $template->create('bbm_js', $template->getParams());
					break;
			}
		}
	}
}
//Zend_Debug::dump($abc);