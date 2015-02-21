<?php

class BBM_ControllerPublic_Help extends XFCP_BBM_ControllerPublic_Help
{
	public function actionBbCodes()
	{
		$parent = parent::actionBbCodes();
		$bbmBbCodes = $this->getModelFromCache('BBM_Model_BbCodes')->getAllActiveBbCodes('strict');
		$bbmBbCodesInCache = XenForo_Application::getSimpleCacheData('bbm_active');

		if( !empty($bbmBbCodesInCache['nohelp']) )
		{
			$bbmBbCodes = array_diff_key($bbmBbCodes, array_combine($bbmBbCodesInCache['nohelp'], $bbmBbCodesInCache['nohelp']));
		}

		if( !empty($bbmBbCodesInCache['protected_parser']) )
		{
			//$bbmBbCodes = array_diff_key($bbmBbCodes, $bbmBbCodesInCache['protected_parser']);
		}

		$visitor = XenForo_Visitor::getInstance();
		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));

		if( !empty($bbmBbCodesInCache['protected']) )
		{
			foreach($bbmBbCodesInCache['protected'] as $tag => $perms)
			{
				if(array_intersect($visitorUserGroupIds, $perms))
				{
					continue;
				}
				unset($bbmBbCodes[$tag]);
			}
		}

		$parent->subView->params['bbmBbCodes'] = $bbmBbCodes;

		return $parent;
	}
}
//Zend_Debug::dump($code);