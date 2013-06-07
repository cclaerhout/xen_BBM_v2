<?php

class BBM_Route_PrefixAdmin_BBCodes implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithStringParam($routePath, $request, 'tag_id');

		return $router->getRouteMatch('BBM_ControllerAdmin_BbCodes', $action);
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithStringParam($outputPrefix, $action, $extension, $data, 'tag_id');
	}
}