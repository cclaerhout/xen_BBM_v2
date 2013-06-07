<?php

/***
	This callback must be configured according to your needs (no support here) - Read & modify the code before activate this callback
	
	To activate this callback, you must:
	1) move this file must be inside the directory (because of its class name): {forum}/library/BBM/BbCode
	2) activate the callback in the XenForo Options for the Bbm: home => options => BbCodes Bbm Manager => XenForo Tags Wrapper Callback
	   Configure like this: BBM_BbCode_XenWrapperCallback::xenWrapperRules
**/

class BBM_BbCode_XenWrapperCallback
{
	public static function xenWrapperRules($tag, $parentClass)
	{
		switch ($tag['tag']) {
			case 'img':
				self::imgRules($parentClass);
			        break;
			case 'php':
			case 'html':
			case 'code':

				self::phcRules($parentClass);
			        break;
		}
	}

	public static function imgRules($parentClass)
	{
		/***
			Get the param node_id from the thread params
			To get all params, just use this function: $parentClass->getThreadParams()
		
		**/
		$nodeid = $parentClass->getThreadParam('node_id');

		if(!$nodeid)
		{
			/*The BbCode has probably not been used inside a thread*/
			return;
		}
		
		/***
			Check if the Bb Code is used inside one or several particular nodes
			you will need to replace XX with the id of these nodes
			
			If you know how to create an option with XenForo (easy), Bbm has a function to help you to get & select your nodes:
			Create an option with XenForo with these options:
			  > Edit Format: PHP Callback
			  > Format Parameters: BBM_BbCodeManager_Options_XenOptions::render_nodes
			  > Data Type: array
			  > Array Sub-Options: *
			  
			After in this file again, call XenForo options...: $xenoptions = XenForo_Application::get('options');
			... and replace array(XX, XX, XX) with $xenoptions->yourOptionId
			
			That's all
		**/

		if(!in_array($nodeid, array(XX, XX, XX)))
		{
			/*The BbCode has not been used in an autorised forum*/
			return;
		}
		
		/***
			The user is broswing the targeted nodes - let's wrap the img Bb Code with the spoiler Bb Code
			
			> The function addWrapper can be used like this:
				$parentClass->addWrapper('theWrappingTag');
					or
				$parentClass->addWrapper('theWrappingTag', 'theWrappingTagOptions');
			
			> If you want to remove a wrapper, use this function instead:
			$parentClass->removeWrapper();
			
		***/
		$parentClass->addWrapper('spoiler', 'Inside the spoiler... a magical picture !');
	}

	public static function phcRules($parentClass)
	{
		//Do what you want
	}	
}
