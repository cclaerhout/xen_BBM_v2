<?php
class BBM_Listeners_Editor
{
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
		
		$template = $view->createOwnTemplateObject();
		$controllerName = $template->getParam('controllerName');
		$controllerAction = $template->getParam('controllerAction');
		$viewName = $template->getParam('viewName');		
		
		$bbmParams = BBM_Helper_Buttons::getConfig($controllerName, $controllerAction, $viewName);

		if(empty($bbmParams['bbmButtonsJsGridArray']))
		{
			//We must be here with Quattro
			return false;
		}

		$bbmButtonsJsGrid = $bbmParams['bbmButtonsJsGridArray'];
		$bbmCustomButtons = $bbmParams['bbmCustomButtons'];

		$jsonButtons = &$editorOptions['json']['buttons'];
		$extendedButtonsBackup = array();

		/**
		 * Filter buttons grid
		 */
		 if(!empty($bbmButtonsJsGrid))
		 {
		 	foreach($bbmButtonsJsGrid as &$buttonGroup)
		 	{
		 		foreach($buttonGroup as $key => $button)
		 		{
		 			if(!self::filterButton($button, $editorOptions, $editorOptions, $showWysiwyg))
		 			{
		 				unset($buttonGroup[$key]);
		 			}
		 		}
		 	}
		 }

		/**
		 * Other addons Buttons Backup
		 */
		if(!empty($editorOptions['json']['buttons']))
		{
			$extendedButtonsBackup = $jsonButtons;
			$jsonButtons = array();
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

				$jsonButtons[$code] = array(
					'title' => $desc,
					'tag'  => $tag,
					'bbCodeOptions' => $opts,
					'bbCodeOptionsSeparator' => $separator,
					'bbCodeContent' => $content
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
		
		/*Bbm Buttons Grid - will have to inject this with Javascript to be able to fully override the editor grid*/
		$editorOptions['json']['bbmButtonConfig'] = $bbmButtonsJsGrid;
		
		/***
		 * Fallback if any problem occurs to have the most accurate editor configuration
		 **/
		if(empty($bbmButtonsJsGrid))
		{
			return false;
		}

		$xenDefaultButtonConfig = &$editorOptions['json']['buttonConfig'];
		$xenConfigStack = array(
			'basic'	=> 	array('bold', 'italic', 'underline', 'deleted'),
			'extended' => 	array('fontcolor', 'fontsize', 'fontfamily'),
			'link' => 	array('fontcolor', 'fontsize', 'fontfamily'),
			'align' =>	array('alignment'),
			'list' =>	array('unorderedlist', 'orderedlist', 'outdent', 'indent'),
			'indent' =>	array('outdent', 'indent'),
			'block' => 	array('code', 'quote'),
			'media' => 	array('media'),
			'image' => 	array('image'),				
			'smilies' => 	array('smilies')
		);

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
	
	public static function filterButton($button, $editorOptions, array $editorOptions, $showWysiwyg)
	{
		if($button == 'draft' && empty($editorOptions['autoSaveUrl']))
		{
			return false;
		}
		
		return true;
	}
}
//Zend_Debug::dump($bbmEditor);