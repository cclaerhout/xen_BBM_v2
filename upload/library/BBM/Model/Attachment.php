<?php

class BBM_Model_Attachment extends XFCP_BBM_Model_Attachment
{
	public function canViewAttachment(array $attachment, $tempHash = '', array $viewingUser = null)
	{
		$parentReturn = parent::canViewAttachment($attachment, $tempHash, $viewingUser);

		if(!isset($attachment['filename']) || $parentReturn != false)
		{
			return $parentReturn;
		}

		$xenOptions = XenForo_Application::get('options');
		$validImgExtensions = array_map('trim', explode(',', $xenOptions->Bbm_Bypass_Visitor_Perms_Img_Ext));
		$validContentTypes = $xenOptions->Bbm_Bypass_Visitor_Perms_Img_Ct;

		if(empty($validImgExtensions) 
			|| empty($validContentTypes) 
			|| !isset($attachment['filename'])
			|| !isset($attachment['content_type'])
		)
		{
			return $parentReturn;
		}

		
		if(!in_array($attachment['content_type'], $validContentTypes))
		{
			return $parentReturn;
		}

		$extension = XenForo_Helper_File::getFileExtension($attachment['filename']);
		if(!in_array($extension, $validImgExtensions))
		{
			return $parentReturn;
		}

		return true;
	}
}
//Zend_Debug::dump($viewingUser);