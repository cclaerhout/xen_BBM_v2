<?php

class BBM_Options_Model_GetContentTypes extends XenForo_Model
{
	public function getAttachContentTypes($selectedAttachContentTypes)
	{
		$contentTypeModel = $this->_getContentTypeModel();
		$contentTypeFields = $contentTypeModel->getAllContentTypeFields();
		$attachContentTypes = array();
		
		foreach($contentTypeFields as $contentType => $contenTypeFields)
		{
			if(!isset($contenTypeFields['attachment_handler_class']))
			{
				continue;
			}

			$attachContentTypes[] = array(
				'label' => $contentType,
				'value' => $contentType,
				'selected' => in_array($contentType, $selectedAttachContentTypes)
			);			
		}

		return $attachContentTypes;
	}
	
	protected function _getContentTypeModel()
	{
		return $this->getModelFromCache('XenForo_Model_ContentType');
	}
}