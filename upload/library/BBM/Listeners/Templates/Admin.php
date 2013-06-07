<?php

class BBM_Listeners_Templates_Admin
{
	public static function OptionsForum($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName) {
		   	case 'admin_icons_home':
				$contents .= $template->create('bbm_admin_icon', $template->getParams());
	   		break;		
	   		case 'forum_edit_basic_information':
   				if (!$template instanceof XenForo_Template_Admin || !XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
   				{
					break;	   					
	   			}

				$params = $template->getParams();
				$params += array(
					'bbm_bm_editors' => XenForo_Model::create('BBM_Model_Buttons')->getEditorConfigsForForums()
				);
				
	   			$contents .= $template->create('bbm_forum_edit_bbm_editor', $params);
	   		break;
	   		case 'admin_sidebar_home':
	   			if (class_exists('KingK_BbCodeManager_BbCodeManager'))
				{
		   			$params = array();
	   				$contents = $template->create('bbm_notice_bbcm', $params) . $contents;
	   			}
	   		break;
		}
	}
}
//	Zend_Debug::dump($abc);