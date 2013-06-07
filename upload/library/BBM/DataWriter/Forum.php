<?php

class BBM_DataWriter_Forum extends XFCP_BBM_DataWriter_Forum
{
	protected function _getFields()
	{
		$parent = parent::_getFields();
		$parent['xf_forum']['bbm_bm_editor'] = array('type' => self::TYPE_STRING, 'maxLength' => 25, 'default' => 'disable');

		return $parent;
	}

	protected function _preSave()
	{
	        $_input = new XenForo_Input($_REQUEST);

		$bbm_bm_editor = $_input->filterSingle('bbm_bm_editor', XenForo_Input::STRING);

		$bbm_bm_editor = (empty($bbm_bm_editor )) ? 'disable' : $bbm_bm_editor;

		$this->set('bbm_bm_editor', $bbm_bm_editor);

		return parent::_preSave();
	}	
}
//Zend_Debug::dump($bbm_bm_editor);