<?php

class BBM_BbCode_Formatter_Default
{
	public static function templateRawTag(&$content, array &$options, &$templateName, &$fallBack, array $rendererStates, $parentClass)
	{
		if($rendererStates['canUseBbCode'] === true && !XenForo_Application::get('options')->get('Bbm_TagsMap_Disable'))
		{
			$content = htmlspecialchars_decode($content);
		}
		
		$fallBack = $content;
	}
}

?>