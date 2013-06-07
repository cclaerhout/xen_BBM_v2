<?php

class BBM_ViewAdmin_BbCodes_Export extends XenForo_ViewAdmin_Base
{
	public function renderXml()
	{
		$nameSuffix = str_replace(' ', '-', utf8_romanize(utf8_deaccent(ucfirst($this->_params['name']))));
		XenForo_Application::autoload('Zend_Debug');
		$this->setDownloadFileName('BBM_BbCode_' . $nameSuffix . '.xml');
		return $this->_params['xml']->saveXml();
	}
}