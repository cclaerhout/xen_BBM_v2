<?php
class BBM_Options_Verification
{ 
      	public static function xenWrapperCallback(array &$configs, XenForo_DataWriter $dw, $fieldName)
      	{
		if( isset($configs['Bbm_wrapper_callback']) && $configs['Bbm_wrapper_callback'] == 'callback' && !BBM_Helper_Bbm::callbackChecker($configs['class'], $configs['method']) )
		{
			$dw->error(new XenForo_Phrase('bbm_xenwrapper_callback_not_valid'));
		}
		
		return true;
      	}
}