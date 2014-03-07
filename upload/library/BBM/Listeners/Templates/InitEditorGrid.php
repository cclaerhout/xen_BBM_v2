<?php

class BBM_Listeners_Templates_InitEditorGrid
{
	public static function Override($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (!class_exists('KingK_BbCodeManager_BbCodeManager'))
		{
		   switch ($hookName) {
		   	case 'editor':
				$options = XenForo_Application::get('options');
				$visitor = XenForo_Visitor::getInstance();
				
		   		if($options->Bbm_Bm_ShowControllerInfo && $visitor['is_admin'])
				{
					$contents .= $template->create('bbm_editor_extra_info', $template->getParams());
				}
		   		break;
			case 'editor_tinymce_init':
				$options = XenForo_Application::get('options');
	
				if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
				{
					break;
				}
	
				if(!empty($options->bbm_debug_tinymcehookdisable))
				{
					break;
				}
				
			       	$visitor = XenForo_Visitor::getInstance();
	
				//Get buttons config
				$myConfigs = XenForo_Model::create('XenForo_Model_DataRegistry')->get('bbm_buttons');
							
				if(empty($myConfigs))
				{
					break; //Blank config (after addon install) => stop the process
				}
	
				//Check which Editor type must be used
				$config_type = self::bakeEditorConfig($template, $options, $visitor, $myConfigs);
	
				//Last check
				if(empty($myConfigs['bbm_buttons'][$config_type]['config_buttons_order']))
				{
					break; //This config type doesn't have any configuration => stop the process
				}
				
				//Get the Grid & Bbm buttons commands (Ready to use=> can't do this in admin options because need to check user permissions here)
				$setup = self::bakeGridCmd($myConfigs['bbm_buttons'][$config_type]['config_buttons_full'], $options, $visitor);
	
				//Let's do some clean up: all previous modifications done by other addons will be erased (providing these addons were executed after this listener function which execution order is 5)
				$contents = preg_replace("#theme_xenforo_buttons[\s\S]+?',#i", '', $contents);
				$contents = preg_replace("#setup(?:\s)?:[\s\S]+?},#i", '', $contents);			
				
				//Insert the grid & setup
				$contents = preg_replace('#xenforo_smilies:#', $setup['cmd'] . $setup['grid'] . '$0', $contents);
	
				if(!empty($options->bbm_debug_tinymcehookcontent) && $visitor['is_admin'])
				{
					Zend_Debug::dump($contents);
				}
				
				break;
			}
		}
	}

	public static function bakeEditorConfig($template, $options, $visitor, $myConfigs)
	{
		/****
		*	Check Text Direction
		***/
		$config_type = ($template->getParam('pageIsRtl') === true) ? 'rtl' : 'ltr';

		/****
		*	Check controller datas
		***/
		$custConfigs = $options->Bbm_Bm_Cust_Config;
	
		if(!empty($custConfigs) || is_array($custConfigs))
		{
			$controllerName = $template->getParam('controllerName');
			$controllerAction = $template->getParam('controllerAction');
			$viewName = $template->getParam('viewName');

			$scores = array('0' => $config_type);
			foreach($custConfigs as $custConfig)
			{
				$points = 1;
				$points = ($controllerName == $custConfig['controllername']) ? $points+1 : $points;
				$points = ($controllerAction == $custConfig['controlleraction']) ? $points+1 : $points;
				$points = ($viewName == $custConfig['viewname']) ? $points+1 : $points;	
				
				if($points > 1)
				{
					$scores[$points] = $custConfig['configtype'];
				}
			}
			
			$winnerKey = max(array_keys($scores));
			//Sorry but if competitors are ex aequo, the last one wins
			$winner = $scores[$winnerKey];
			
			//Anti-doping test
			$config_type = (isset($myConfigs['bbm_buttons'][$winner])) ? $winner : $config_type;
		}

		/****
		*	Check forum config (option)
		***/
		if($options->Bbm_Bm_Forum_Config)
		{
			$editorOptions = false;

			if (XenForo_Application::isRegistered('bbm_bm_editor'))
			{
				$editorOptions = XenForo_Application::get('bbm_bm_editor');
			}

			if($editorOptions !== false && $editorOptions != 'disable')
			{
				$config_type = $editorOptions;
			}
		}

		/****
		*	Check if mobile
		***/
		if((!class_exists('Sedo_DetectBrowser_Listener_Visitor') || !isset($visitor->getBrowser['isMobile'])))
		{
			//No external addon has been installed or activated
			if(XenForo_Visitor::isBrowsingWith('mobile') && $options->Bbm_Bm_Mobile != 'disable')
			{
				//is mobile and editor has a style option
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Mobile])) ? $options->Bbm_Bm_Mobile : $config_type;
			}
			
			return $config_type;
		}
		else
		{
			//External addon is installed

			if(!$visitor->getBrowser['isMobile'])
			{
				//is not mobile
				return $config_type;
			}
			
			if($visitor->getBrowser['isTablet'] && $options->Bbm_Bm_Tablets != 'transparent')
			{
				//is a tablet & transparent mode has been activated
				$config_type = $options->Bbm_Bm_Tablets;
			}
			
			if($visitor->getBrowser['isMobile'] && $options->Bbm_Bm_Mobile != 'disable')
			{
				//is a mobile device and mobile configuration has been activated
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Mobile])) ? $options->Bbm_Bm_Mobile : $config_type;
			}
			
			if($visitor->getBrowser['isTablet'] && $options->Bbm_Bm_Tablets != 'disable')
			{
				//is a tablet & tablet configuration has been activated
				$config_type = (isset($myConfigs['bbm_buttons'][$options->Bbm_Bm_Tablets])) ? $options->Bbm_Bm_Tablets : $config_type;				
			}

			return $config_type;		
		}
	}

	public static function bakeGridCmd($buttons, $options, $visitor)
	{
		$allButtons = unserialize($buttons);

		//Server info for buttons icons
		$server_root = preg_replace('#/\w+?\.\w{3,4}$#', '', $_SERVER["SCRIPT_FILENAME"]);
		$icons_folder = $server_root . '/styles/bbm/editor/';

		//Visitor info
		$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));

		//Only to make debug display cleaner		
		$br = "\r\n\t\t\t\t";

		//Init Grid
		$output['grid'] = "theme_xenforo_buttons1 : '";
		$line = 1;
		
		//Init Cmd
		$output['cmd'] = '';

		//Let's start the big loop
		foreach($allButtons as $button)
		{
			//Check if button active (only for Bbm buttons => they will be the only ones to have the key [active]
			if(isset($button['active']) && empty($button['active']))
			{
				continue; //Next loop !
			}
			
			//Check Permissions
			if($button['button_has_usr'])
			{
				$usrOK = unserialize($button['button_usr']);

				if(!array_intersect($visitorUserGroupIds, $usrOK))
				{
					continue; //Next loop !
				}
			}

			/*****
			*	GRID CREATION
			***/

				if($button['tag'] == 'carriage')
				{
					$line++;
					$output['grid'] = substr($output['grid'], 0, -1); //must delete the last ',' from the button, then start a new line
					$output['grid'] .= "',".$br."theme_xenforo_buttons$line : '";
				}
				else
				{
					if($button['tag'] == 'separator')
					{
						$tempTag = '|';
					}
					elseif( $button['class'] != 'xenButton' && empty($button['killCmd']) ) 
					{
						//Bbm buttons (classic)
						$tempTag = 'bbm_' . $button['tag'];
					}
					elseif( $button['class'] != 'xenButton' && !empty($button['killCmd']) && !empty($button['custCmd'])  )
					{
						//Bbm buttons with TinyMCE plugin
						$tempTag = $button['custCmd'];
					}
					else
					{
						//Xen buttons
						$tempTag =  $button['tag'];
					}

					$output['grid'] .= $tempTag . ',';
				}


			/*****
			*	COMMAND CREATION
			***/

				//Target bbm buttons which have command options activated
				if(!$button['hasButton'] || $button['killCmd']) //will include carriage, separator & xen buttons.
				{
					continue;	
				}
	
				//Icon Management
				if($button['imgMethod'] == 'Direct')
				{
	      				if (file_exists($icons_folder . $button['tag'] . '.png'))
	      				{
	      					$icon_url = $options->boardUrl . '/styles/bbm/editor/' . $button['tag'] . '.png';
	      				}
	      				elseif (file_exists($icons_folder . $button['tag'] . '.gif'))
	      				{
	      					$icon_url = $options->boardUrl . '/styles/bbm/editor/' . $button['tag'] . '.gif';
	      				}
	      				elseif (file_exists($icons_folder . 'default.png'))
	      				{
	      					$icon_url = $options->boardUrl . '/styles/bbm/editor/default.png';
	      				}
	      				else
	      				{
	      					$icon_url = $options->boardUrl . '/styles/bbm/editor/' . $button['tag'] . '.png';
	      				}			
				}
	
	      			//Button Title Management
	      			$phrase = '';
	      			if(!empty($button['buttonDesc']))
	      			{
	      				$phrase = self::DetectPhrases($button['buttonDesc']);
	      
	      			}
	
	      			//Opening Tag Management
	      			if(empty($button['tagOptions']))
	      			{
	      				$opening = $button['tag'];
	      			}
	      			else
	      			{
	      				$opening_option = self::DetectPhrases($button['tagOptions']);
	      				$opening = $button['tag'] . '=' . $opening_option;
	      			}
	
	      			//Content Management
	      			if(empty($button['tagContent']))
	      			{
	      				$content = "ed.selection.getContent()";
	      			}
	      			else
	      			{
	      				$content_replace = self::DetectPhrases($button['tagContent']);
	      				$content = "'$content_replace'";
	      			}			
	      	
	      			//Button and Command Management
	      			$ext = $button['tag'];
	      			
				if($button['imgMethod'] == 'Direct')
				{
		      			$output['cmd'] .= "
			      			ed.addCommand('Bbm_$ext', function() {
			      				ed.focus();
			      				ed.selection.setContent('[$opening]' + $content + '[/$ext]');
			              		});
			      	        	ed.addButton('bbm_$ext', {
			              	        	        title : '$phrase',
			      	        	        	cmd : 'Bbm_$ext',
			                      	        	image : '$icon_url'
			      	                });
		      		        ";
		      		}
		      		else
		      		{
		  	      		$output['cmd'] .= "
			      			ed.addCommand('Bbm_$ext', function() {
			      				ed.focus();
			      				ed.selection.setContent('[$opening]' + $content + '[/$ext]');
			              		});
			      	        	ed.addButton('bbm_$ext', {
			              	        	        title : '$phrase',
			      	        	        	cmd : 'Bbm_$ext'
			      	                });
		      		        ";
		      		}
		}

		//Finish Grid creation
		$output['grid'] = substr($output['grid'], 0, -1); 
		$output['grid'] .= "',$br $br";
		
		//Correct Grid if line is empty (for example if a line has only private buttons then the users who can't access it will have an error)
		$output['grid'] = preg_replace("#theme_xenforo_buttons\d{1,2}\s*?:\s*?'(?:(?:\|(?:,)?)+?')?,#i", '', $output['grid']); //empty seperators will also be deleted

		//Finish Command creation		
		if(!empty($output['cmd']))
		{
			$output['cmd'] = "setup : function(ed) { $br" . $output['cmd'] . "$br},$br $br";
		}

		return $output;
	}

	public static function DetectPhrases($string)
	{
		if(preg_match_all('#{phrase:(.+?)}#i', $string, $captures, PREG_SET_ORDER))
		{
			foreach($captures as $capture)
			{
				$phrase = new XenForo_Phrase($capture[1]);
				$string = str_replace($capture[0], $phrase, $string);
			}
		}

		return addslashes($string);
	}

	/****

	*	Auto add template 'help_bbm_bbcodes' to template 'help_bb_codes'
	*
	***/

	public static function help_bbm_bbcodes($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (!class_exists('KingK_BbCodeManager_BbCodeManager'))
		{
			switch ($hookName) {
				case 'help_bb_codes':
					$contents .= $template->create('help_bbm_bbcodes', $template->getParams());
					break;
		 	}
		}
	}

	/****

	*	Add Extra Js/Css
	*
	***/

	public static function extraJsCss($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		if (!class_exists('KingK_BbCodeManager_BbCodeManager'))
		{
			switch ($hookName) {
				case 'page_container_head':
					if ($template instanceof XenForo_Template_Admin && !$options->Bbm_Bm_SetInAdmin)
					{
						break;
					}
				
					$contents .= $template->create('bbm_js', $template->getParams());
					break;
			}
		}		
	}
}
//	Zend_Debug::dump($abc);