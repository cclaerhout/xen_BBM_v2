<?php

class BBM_ViewAdmin_Buttons_Buttons extends XenForo_ViewAdmin_Base
{
	public function renderJson()
	{
		$output = $this->_renderer->getDefaultOutputArray(get_class($this), $this->_params, $this->_templateName);
		
		$output['ajaxresponse'] = $this->_params['ajaxresponse'];
		
		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}