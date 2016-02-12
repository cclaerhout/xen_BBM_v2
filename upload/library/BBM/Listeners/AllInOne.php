<?php
class BBM_Listeners_AllInOne
{
	/***
	 * BB CODES LISTENER
	 **/
	public static $class_check = null;

	public static function BbCodes($class, array &$extend)
	{
		if (self::$class_check === null)
		{
			self::$class_check = class_exists('KingK_BbCodeManager_BbCodeManager');
		}

		if (self::$class_check === false)
		{
			switch($class)
			{
				case 'XenForo_BbCode_Formatter_BbCode_AutoLink':
					$extend[] = 'BBM_BbCode_Formatter_BbCode_AutoLink';
				break;

				case 'XenForo_BbCode_Formatter_BbCode_Filter':
					$extend[] = 'BBM_BbCode_Formatter_BbCode_Filter';
				break;

				case 'XenForo_BbCode_Formatter_Base':
					$extend[] = 'BBM_BbCode_Formatter_Base';
					if(XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
					{
						//must come after to be loaded before (only for the same extended functions) - same execution level
						$extend[] = 'BBM_BbCode_Formatter_Extensions_PreCacheBase';
					}
				break;

				case 'XenForo_BbCode_Formatter_Wysiwyg':
					$extend[] = 'BBM_BbCode_Formatter_Wysiwyg';
					if(XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
					{
						$extend[] = 'BBM_BbCode_Formatter_Extensions_PreCacheWysiwyg';
					}
				break;

				case 'XenForo_ControllerPublic_Help':
					$extend[] = 'BBM_ControllerPublic_Help';
				break;

				case 'XenForo_DataWriter_DiscussionMessage_Post':
					$extend[] = 'BBM_DataWriter_Post';
				break;
			}
		}
	}

	/***
	 * FILES HEALTH CHECK
	 **/
	public static function files_health_check(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
		$hashes += BBM_FileSums::getHashes();
	}

	/***
	 * EXTENDS FORUM DATAWRITER
	 **/
	public static function DataWriterAdmin($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_Forum' && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
		{
	  		$extend[] = 'BBM_DataWriter_Forum';
		}
	}

	/***
	 * EXTENDS ATTACHMENT MODEL
	 **/
	public static function extendAttachmentModel($class, array &$extend)
	{
		if($class == 'XenForo_Model_Attachment' && XenForo_Application::get('options')->get('Bbm_Bypass_Visitor_Perms_For_Img'))
		{
			$extend[] = 'BBM_Model_Attachment';
		}
	}

	/***
	 * SET APPLICATIONS + GET CCV
	 **/
	protected static $_controllerName = NULL;
	protected static $_controllerAction = NULL;
	protected static $_viewName = NULL;

	public static function controllerPreView(
		XenForo_FrontController $fc,
		XenForo_ControllerResponse_Abstract &$controllerResponse,
		XenForo_ViewRenderer_Abstract &$viewRenderer, array &$containerParams
	)
	{
		//SET BBM BM EDITOR BY FORUM LISTENER
		$bbmEditor = (isset($controllerResponse->params['forum']['bbm_bm_editor'])) ? $controllerResponse->params['forum']['bbm_bm_editor'] : false;
		XenForo_Application::set('bbm_bm_editor', $bbmEditor);

      		//GET CCV
      		self::$_controllerName = (isset($controllerResponse->controllerName)) ? $controllerResponse->controllerName : NULL;
      		self::$_controllerAction = (isset($controllerResponse->controllerAction)) ? $controllerResponse->controllerAction : NULL;
      		self::$_viewName = (isset($controllerResponse->viewName)) ? $controllerResponse->viewName : NULL;
	}


	/***
	 * OVERRIDE A PART OF THE BB CODE PARSER
	 **/
	public static function modifyParser($class, array &$extend)
	{
		if ($class == 'XenForo_BbCode_Parser' && XenForo_Application::get('options')->get('Bbm_modify_parser'))
		{
			$extend[] = 'BBM_BbCode_Parser';
		}
	}

	/***
	 * INIT TEMPLATE HELPERS
	 **/
	public static function initTemplateHelpers(XenForo_Dependencies_Abstract $dependencies, array $data)
	{
		XenForo_Template_Helper_Core::$helperCallbacks += array(
			'bbm_strip_noscript' => array('BBM_Helper_BbCodes', 'stripNoscript'),
			'bbm_color_hexa' => array('BBM_Helper_BbCodes', 'getHexaColor'),
			'bbm_unbreakable_quote' => array('BBM_Helper_BbCodes', 'unbreakableQuote'),
			'sedo_is_https' => array('BBM_Helper_BbCodes', 'isHttps')
		);
	}

	/***
	 * SETUP EDITOR LISTENER
	 **/
	public static function addRedactorButtons(XenForo_View $view, $formCtrlName, &$message, array &$editorOptions, &$showWysiwyg)
	{
		if (!$showWysiwyg)
		{
			return false;
		}

		$xenOptions = XenForo_Application::get('options');

		if($xenOptions->bbm_debug_tinymcehookdisable)
		{
			return false;
		}

		$bbmParams = BBM_Helper_Buttons::getConfig(self::$_controllerName, self::$_controllerAction, self::$_viewName);

		if(empty($bbmParams['bbmButtonsJsGridArray']))
		{
			//We must be here with Quattro
			return false;
		}

		$bbmButtonsJsGrid = $bbmParams['bbmButtonsJsGridArray'];
		$bbmCustomButtons = $bbmParams['bbmCustomButtons'];

		if(!isset($editorOptions['json']['buttons']))
		{
			//Make this as an array to avoid any errors below
			$editorOptions['json']['buttons'] = array();
		}

		$jsonButtons = &$editorOptions['json']['buttons'];
		$extendedButtonsBackup = array();

		/**
		 * Filter buttons grid
		 */
		 $allGridButtons = array();

		 if(!empty($bbmButtonsJsGrid))
		 {
		 	foreach($bbmButtonsJsGrid as &$buttonGroup)
		 	{
		 		foreach($buttonGroup as $key => $button)
		 		{
		 			if(!self::filterButton($button, $editorOptions, $showWysiwyg))
		 			{
		 				unset($buttonGroup[$key]);
		 				continue;
		 			}

		 			array_push($allGridButtons, $button);
		 		}
		 	}
		 }

		/**
		 * XenForo Custom BbCodes Manager Buttons
		 */
		if(!empty($editorOptions['json']['bbCodes']))
		{
			$customBbCodesButtons = array();

			foreach($editorOptions['json']['bbCodes'] as $k => $v)
			{
	 			$customTag = "custom_$k";

	 			if(!in_array($customTag, $allGridButtons) && self::filterButton($customTag, $editorOptions, $showWysiwyg))
	 			{
	 				$customBbCodesButtons[] = $customTag;
	 			}
			}

			if(!empty($customBbCodesButtons))
			{
				$bbmButtonsJsGrid[] = $customBbCodesButtons;
			}
		}

		/**
		 * Other addons Buttons Backup
		 */
		if(!empty($jsonButtons))
		{
			$extendedButtonsBackup = $jsonButtons;
		}

		/**
		 * Get BBM Custom Buttons
		 */
		if(is_array($bbmCustomButtons))
		{
			foreach($bbmCustomButtons as $button)
			{
				$tag = preg_replace('#^at_#', '', $button['tag']);
				$code =  $button['code'];
				$desc = XenForo_Template_Helper_Core::jsEscape($button['description']);
				$opts = XenForo_Template_Helper_Core::jsEscape($button['tagOptions']);
				$content = XenForo_Template_Helper_Core::jsEscape($button['tagContent']);
				$separator = XenForo_Template_Helper_Core::jsEscape($button['separator']);
				$textButton = XenForo_Template_Helper_Core::jsEscape($button['textButton']);
				$faButton = XenForo_Template_Helper_Core::jsEscape($button['faButton']);

				$jsonButtons[$code] = array(
					'title' => $desc,
					'tag'  => $tag,
					'bbCodeOptions' => $opts,
					'bbCodeOptionsSeparator' => $separator,
					'bbCodeContent' => $content,
					'textButton' => $textButton,
					'faButton' => $faButton
				);
			}
		}

		/**
		 * Let's put back the buttons from other addons at the end of the editor with the backup
		 * Also check if some of these buttons have a bbm configuration to delete them from the backup
		 */
		if(	!empty($extendedButtonsBackup)
			&& is_array($extendedButtonsBackup)
		)
		{
			foreach($jsonButtons as $buttonCode => $jsonButton)
			{
				if(isset($extendedButtonsBackup[$buttonCode]))
				{
					$jsonButtons[$buttonCode] = array_merge($jsonButtons[$buttonCode], $extendedButtonsBackup[$buttonCode]);
					unset($extendedButtonsBackup[$buttonCode]);
				}
			}

			if(!empty($extendedButtonsBackup))
			{
				//Extend custom buttons
				$jsonButtons += $extendedButtonsBackup;

				//Extend buttons grid
				$extendedgrid = array();
				foreach($extendedButtonsBackup as $buttonCode => $extendedButton)
				{
					$extendedgrid[] = $buttonCode;
				}

				array_push($bbmButtonsJsGrid, $extendedgrid);
			}
		}

		if(empty($jsonButtons))
		{
			//Let's put back as if it would have been if it was empty
			$jsonButtons = null;
		}

		/*Bbm Buttons Grid - will have to inject this with Javascript to be able to fully override the editor grid*/
		$editorOptions['json']['bbmButtonConfig'] = $bbmButtonsJsGrid;

		/***
		 * Fallback if any problem occurs to have the most accurate editor configuration
		 **/

		if(empty($bbmButtonsJsGrid))
		{
			return false;
		}

		if(empty($editorOptions['json']['buttonConfig']))
		{
			$editorOptions['json']['buttonConfig'] = array();
		}

		$xenDefaultButtonConfig = &$editorOptions['json']['buttonConfig'];

		if(XenForo_Application::get('options')->get('currentVersionId') < 1030031)
		{
			//XenForo 1.2
			$xenConfigStack = array(
				'basic'	=> 	array('bold', 'italic', 'underline', 'deleted'),
				'extended' => 	array('fontcolor', 'fontsize', 'fontfamily'),
				'link' => 	array('createlink', 'unlink'),
				'align' =>	array('alignment'),
				'list' =>	array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
				'indent' =>	array('outdent', 'indent'),
				'block' => 	array('code', 'quote'),
				'media' => 	array('media'),
				'image' => 	array('image'),
				'smilies' => 	array('smilies')
			);
		}
		else
		{
			//XenForo 1.3 beta1-beta2
			$xenConfigStack = array(
				'basic'	=> 	array('bold', 'italic', 'underline'),
				'extended' => 	array('fontcolor', 'fontsize', 'fontfamily'),
				'link' => 	array('createlink', 'unlink'),
				'align' =>	array('alignment'),
				'list' =>	array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
				'indent' =>	array('outdent', 'indent'),
				'smilies' => 	array('smilies'),
				'image' => 	array('image'),
				'media' => 	array('media'),
				'block' => 	array('insert')
			);
		}

		foreach($xenConfigStack as $groupName => $stackBtnGroup)
		{
			foreach($bbmButtonsJsGrid as $editorBtnGroup)
			{
				if(!is_array($editorBtnGroup))
				{
					continue;
				}

				if(array_intersect($stackBtnGroup, $editorBtnGroup))
				{
					/***
					 * At least one button has been found, the group then must be displayed, let's set the value to false to prevent to disable it
					 **/
					$xenConfigStack[$groupName] = false;
					break;
				}
			}
		}

		if(!empty($xenConfigStack))
		{
			foreach($xenConfigStack as $groupName => $stackBtnGroup)
			{
				if($stackBtnGroup === false)
				{
					$xenDefaultButtonConfig[$groupName] = true;
				}
				else
				{
					$xenDefaultButtonConfig[$groupName] = false;
				}
			}
		}
	}

	public static function filterButton($button, array $editorOptions, $showWysiwyg)
	{
		if($button == 'draft' && empty($editorOptions['autoSaveUrl']))
		{
			return false;
		}

		return true;
	}
}
//Zend_Debug::dump($abc);