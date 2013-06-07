<?php

class BBM_ControllerPublic_Help extends XFCP_BBM_ControllerPublic_Help
{
	public function actionBbCodes()
	{
		$wrap = parent::actionBbCodes();
		$bbmBbCodes = $this->getModelFromCache('BBM_Model_BbCodes')->getAllActiveBbCodes('strict');
		
		if( empty($bbmBbCodes) )
		{
			return $wrap;
		}

		$bbmBbCodesInCache = XenForo_Application::getSimpleCacheData('bbm_active');;

		if( isset($bbmBbCodesInCache['nohelp']) && !empty($bbmBbCodesInCache['nohelp']) )
		{
			foreach($bbmBbCodesInCache['nohelp'] as $tag)
			{
				unset($bbmBbCodes[$tag]);
			}
		}

		$wrap->subView->params['bbmBbCodes'] = $bbmBbCodes;

		return $wrap;
	}
}