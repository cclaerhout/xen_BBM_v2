<?php

class BBM_ControllerAdmin_Forum extends XFCP_BBM_ControllerAdmin_Forum
{
	public function actionEdit()
	{
		$parent = parent::actionEdit();

		if(isset($parent->params['forum']['bbm_bm_editor']))
		{
			$parent->params['bbm_bm_editors'] = XenForo_Model::create('BBM_Model_Buttons')->getEditorConfigsForForums($parent->params['forum']['bbm_bm_editor']);
//			Zend_Debug::dump($parent);
		}

		return $parent;
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		if ($this->_input->filterSingle('delete', XenForo_Input::STRING))
		{
			return $this->responseReroute('XenForo_ControllerAdmin_Forum', 'deleteConfirm');
		}

		$nodeId = $this->_input->filterSingle('node_id', XenForo_Input::UINT);

		$bbm_bm_editor = $this->_input->filterSingle('bbm_bm_editor', XenForo_Input::STRING);
		$bbm_bm_editor = (empty($bbm_bm_editor )) ? 'disable' : $bbm_bm_editor;

		$writer = $this->_getNodeDataWriter();

		if ($nodeId)
		{
			$writer->setExistingData($nodeId);
		}

		$writer->set('bbm_bm_editor', $bbm_bm_editor);
		$writer->save();

		return parent::actionSave();
	}
}