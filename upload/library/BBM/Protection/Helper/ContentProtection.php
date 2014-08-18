<?php

class BBM_Protection_Helper_ContentProtection
{
	/**************************************
		This class is used to protect the content of the Bbm BbCodes with view permissions.
		The general idea is to listen the controller and protect these Bb Codes

		> Two XenForo listerners are extended here: the preView & postView Controller
		   The Controller preView listerner is used to get some key settings and to modify some Json responses
		   The Controller postView listerner is used to modify the other kind of responses (=> it might need to be modified next)

		> Some parts of XenForo can't be protected with only listening the controller:
			# Search
				> Reason: 	XenForo_ViewPublic_Search_Results has this function "XenForo_ViewPublic_Helper_Search::renderSearchResults"
						which modifies the parameters of the Controller Response
				> Solution:	BBM_Protection_Model_Search
				
			# NewsFeed
				> Reason: 	XenForo_ViewPublic_NewsFeed_View has this function "XenForo_ViewPublic_Helper_NewsFeed::getTemplates"
						which modifies the parameters of the Controller Response
				> Solution:	See here: BBM_Protection_Model_NewsFeed
				
			# ThreadWatch
				> Reason: 	The mail is sent inside the datawritter (no controller here)
				> Solution:	See here: BBM_Protection_Model_ThreadWatch 

		> There are two tools to protect the Bb Content Content:
		    These two tools are some stand-alone functions easy to use.
			1) The parsing protection - this is the cleanest way to do this.
				Arguments:
				
				> $string		The string to process
				> $checkVisitorPerms 	Optional. Default is true. This will protect the Bb Code Content only if the user doesn't have the permission to see it.
							In some case the parameter must be set on false. For example with email or alerts => even if the visitor has the permission
							to see the content it doesn't mean the one who will receive the email or the alert has also this permission
			
			2) The mini parser protection - An alternative to the XenForo parser/formatter 
				Reason: with an incomplete closing tag, the XenForo parser will eat all the remaining text... if you apply this to the html output, it's really a problem
				Arguments are the same than the two above

	**************************************/

	/****
	*	Debug tool
	***/
	protected static $_debug;

	/****
	*	XenForo Listeners Hooks
	***/
	protected static $_isControllerAdmin = false;

	protected static $_responseType = NULL;
	protected static $_isJson = false; //Double check

	protected static $_controllerName = NULL;
	protected static $_controllerAction = NULL;
	protected static $_viewName = NULL;
	protected static $_processMiniParser = false;	
	protected static $_processParsing = true;
		
	public static function controllerPreDispatch(XenForo_FrontController $fc, XenForo_RouteMatch &$routeMatch)
	{
		/* Listener - Execution order: #1 */
		self::$_responseType = $routeMatch->getResponseType();
	}

	public static function controllerPreView(XenForo_FrontController $fc, 
		XenForo_ControllerResponse_Abstract &$controllerResponse,
		XenForo_ViewRenderer_Abstract &$viewRenderer,
		array &$containerParams
	)
	{
		/* Listener - Execution order: #2 */
		self::$_isControllerAdmin = (strstr($controllerResponse->controllerName, 'ControllerAdmin')) ? true : false;
      		self::$_controllerName = (isset($controllerResponse->controllerName)) ? $controllerResponse->controllerName : NULL;
      		self::$_controllerAction = (isset($controllerResponse->controllerAction)) ? $controllerResponse->controllerAction : NULL;
      		self::$_viewName = (isset($controllerResponse->viewName)) ? $controllerResponse->viewName : NULL;
      		self::$_isJson = ($viewRenderer instanceof XenForo_ViewRenderer_Json) ? true : false;
	
		if(XenForo_Application::get('options')->get('Bbm_ContentProtection') && self::$_isControllerAdmin === false)
		{
			if(self::$_isJson == true)
			{
				/***
				*  Protect Json Response here. It will not work with the controllerPostView listener (will generate an error)
				**/
				
				if(isset($controllerResponse->params['post']['message']))
				{
					/***
					*	Use for: 	- Edit inline
					*			- Thread fast preview (small popup when mouse over title)
					*			- Thread/post/conversation edit preview
					**/
					$controllerResponse->params['post']['message'] = self::parsingProtection($controllerResponse->params['post']['message']);
				}
	
				if(isset($controllerResponse->params['quote']))
				{
					/***
					*	Use for: 	- Quotes
					**/
					$controllerResponse->params['quote'] = self::parsingProtection($controllerResponse->params['quote'], true, 'quotes');
				}
			}
		}

		if(self::$_debug == 'pre' && self::$_isControllerAdmin === false)
		{
			$visitor = XenForo_Visitor::getInstance();
			if($visitor['is_admin'])
			{
				Zend_Debug::dump($controllerResponse);
			}
		}

		/*Extra function to hide tags in Thread fast preview*/
		if(self::$_isJson == true && self::$_viewName == 'XenForo_ViewPublic_Thread_Preview' && XenForo_Application::get('options')->get('Bbm_HideTagsInFastPreview'))
		{
      			if(isset($controllerResponse->params['post']['message']))
      			{
				if(XenForo_Application::get('options')->get('Bbm_HideTagsInFastPreviewInvisible'))
				{
					$formatter = XenForo_BbCode_Formatter_Base::create('BBM_Protection_BbCode_Formatter_BbCode_Eradicator', false);
					$formatter->setAllTagsAsProtected();
					$formatter->invisibleMode();
				}
				else
				{
					$formatter = XenForo_BbCode_Formatter_Base::create('BBM_Protection_BbCode_Formatter_BbCode_Lupin', false);
				}
				
				$parser = new XenForo_BbCode_Parser($formatter);

				$extraStates = array(
					'bbmContentProtection' => true
				);				
				
				$controllerResponse->params['post']['message'] = $parser->render($controllerResponse->params['post']['message'], $extraStates);
      			}		
		}		
	}

	public static function controllerPostView($fc, &$output)
	{
		/* Listener - Execution order: #3 */
		if(XenForo_Application::get('options')->get('Bbm_ContentProtection') && self::$_isControllerAdmin === false && is_string($output))
		{
	      		if(self::$_responseType == 'html' && self::$_isJson != true && self::$_processParsing == true)
	      		{
		      		//Don't use the parser method, if it finds an opening tag it will "eat" all the page. Use instead the regex method
		      		//$output = self::parsingProtection($output);
	      		
	      			$output = self::miniParserProtection($output, true);
	      		}
	      		elseif(self::$_processMiniParser == true)
	      		{
	      			$output = self::miniParserProtection($output, true); 
	      		}
		}

		if(self::$_debug == 'post' && self::$_isControllerAdmin === false)
		{
			$visitor = XenForo_Visitor::getInstance();
			if($visitor['is_admin'])
			{
				Zend_Debug::dump($output);
			}
		}
	}

	/****
	*	Bbm Bb Codes Content Protection tools
	***/
	public static function parsingProtection($string, $checkVisitorPerms = true, $src = null)
	{
		if(XenForo_Application::get('options')->get('Bbm_ContentProtection'))
		{
			$formatter = XenForo_BbCode_Formatter_Base::create('BBM_Protection_BbCode_Formatter_BbCode_Eradicator', false);
			$formatter->setCheckVisitorPerms($checkVisitorPerms);

			$parser = XenForo_BbCode_Parser::create($formatter);

			$extraStates = array(
				'bbmContentProtection' => true
			);
			
			$string = $parser->render($string, $extraStates);

			if($src == 'quotes')
			{
				$string.= "\r\n";
			}			
		}
		
		return $string;
	}
	
	public static function miniParserProtection($string, $checkVisitorPerms = true)
	{
		$visitor = XenForo_Visitor::getInstance();
		$options = XenForo_Application::get('options');

		if(!$options->Bbm_ContentProtection)
		{
			return $string;
		}

		$bbmCached = XenForo_Application::getSimpleCacheData('bbm_active');

		if(	!is_array($bbmCached) 
			|| !isset($bbmCached['protected']) 
			|| !is_array($bbmCached['protected'])
			|| empty($bbmCached['protected'])
		)
		{
			return $string;
		}

		if($checkVisitorPerms === true)
		{
			$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
		}
		
		$protectedTags = $bbmCached['protected'];
		$replace = new XenForo_Phrase('bbm_viewer_content_protected');
		$openTags = array();

		$xenProtectedTags = array('attach', 'email', 'img', 'media', 'url');
		$xenProtectedTagsNoPermsDisplay = false;//make another phrase inviting the user to log to see the content?
		
		foreach($xenProtectedTags as $tagName)
		{
			$permKey = "bbm_hide_{$tagName}";
			$permsVal = $visitorUserGroupIds;

			if($checkVisitorPerms === true)
			{
				if($visitor->hasPermission('bbm_bbcodes_grp', $permKey))
				{
					$permsVal = array();
				}
			}
			else
			{
				//ie: for alerts, mails
				if($xenProtectedTagsNoPermsDisplay)
				{
					$permsVal = array();
				}
			}
				
			$protectedTags[$tagName] = $permsVal;
		}

		foreach($protectedTags AS $tag => $perms)
		{
			if($checkVisitorPerms === true && array_intersect($visitorUserGroupIds, $perms))
			{
				continue;
			}
			
			$openTags[] = $tag;
		}
		
		$tagRules = array();
			
		foreach($openTags as $tag)
		{
			$tagRules[$tag] = array(
				'stringReplace'  => new XenForo_Phrase('bbm_viewer_content_protected')
			);
		}

		$parserOptions = array(
			'parserOpeningCharacter' => '[',
			'parserClosingCharacter' => ']',
			'htmlspecialcharsForContent' => false,
			'htmlspecialcharsForOptions' => false,
			'nl2br' => false,
			'checkClosingTag' => true,
			'preventHtmlBreak' => true
		);


		if(!preg_match('#<body[^>]*?><pre>#', $string))
		{
			$miniParser = new BBM_Protection_Helper_MiniParser($string, $tagRules, array(), $parserOptions);
			$string = $miniParser->render();
		}

		return $string;		
	}
}
//Zend_Debug::dump($string);