<?php

class BBM_DataWriter_Post extends XFCP_BBM_DataWriter_Post
{
	protected $BbCodesModel = null;

	protected function _getBbCodesModel()
	{
		if ($this->BbCodesModel === null)
		{
			$this->BbCodesModel = XenForo_Model::Create('BBM_Model_BbCodes');
		}
		return $this->BbCodesModel;
	}

	protected function _InvalidateCaches()
	{
		$this->_getBbCodesModel()->setBbCodeTagCache('post', $this->get('post_id'), array());    
	}

	protected function _postSaveAfterTransaction()
	{
		parent::_postSaveAfterTransaction();
		if ($this->isUpdate())
		{
			if ($this->isChanged('message') || ($this->isChanged('message_state') && $this->get('message_state') == 'deleted'))
			{
				$this->_InvalidateCaches();
			}
		}
	}

	protected function _messagePostDelete()
	{
		parent::_messagePostDelete();
		$this->_InvalidateCaches();
	}

}