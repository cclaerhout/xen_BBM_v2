<?php

class BBM_ControllerAdmin_Bbm extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		//$this->assertAdminPermission('bbm_BbCodesAndButtons'); // don't activate to display the explanation phrase
	}

	public function actionIndex()
	{
		$viewParams = array(
			'hasBbcm' => $this->_getBbmBbCodeModel()->detectBbcm(),
			'permsBbm' => XenForo_Visitor::getInstance()->hasAdminPermission('bbm_BbCodesAndButtons')
 		);
		return $this->responseView('XenForo_ViewAdmin_Bbm_Index', 'bbm_index', $viewParams);
	}

	protected function _getBbmBbCodeModel()
	{
		return $this->getModelFromCache('BBM_Model_BbCodes');
	}	
}
//Zend_Debug::dump($code);