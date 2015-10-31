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
		$this->_getBbCodesModel()->setBbCodeTagCache('post', $this->get('post_id'), $this->getExisting('last_edit_date'), false, array());
        $this->_getBbCodesModel()->setBbCodeTagCache('post', $this->get('post_id'), $this->getExisting('last_edit_date'), true, array());
        if ($this->isChanged('last_edit_date'))
        {
            $this->_getBbCodesModel()->setBbCodeTagCache('post', $this->get('post_id'), $this->get('last_edit_date'), false, array());
            $this->_getBbCodesModel()->setBbCodeTagCache('post', $this->get('post_id'), $this->get('last_edit_date'), true, array());
        }
	}

	protected function _messagePostSave()
	{
		parent::_messagePostSave();
		if ($this->isUpdate())
		{
			if ($this->isChanged('message') || ($this->isChanged('message_state') && $this->get('message_state') == 'deleted'))
			{
				$this->_InvalidateCaches();
			}
		}
	}

	protected function _postSaveAfterTransaction()
	{
		if ($this->isUpdate())
		{
			if ($this->isChanged('message') || ($this->isChanged('message_state') && $this->get('message_state') == 'deleted'))
			{
				$this->_InvalidateCaches();
			}
		}
		parent::_postSaveAfterTransaction();
	}

	protected function _messagePostDelete()
	{
		parent::_messagePostDelete();
		$this->_InvalidateCaches();
	}

}