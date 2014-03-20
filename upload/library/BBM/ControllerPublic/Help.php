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
			foreach($bbmBbCodesInCache['nohelp'] as $tag)
			{
				unset($bbmBbCodes[$tag]);
			}
		}

		$parent->subView->params['bbmBbCodes'] = $bbmBbCodes;

		return $parent;
	}
}