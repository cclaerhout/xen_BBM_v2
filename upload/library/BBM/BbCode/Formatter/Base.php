<?php

class BBM_BbCode_Formatter_Base extends XFCP_BBM_BbCode_Formatter_Base
{
	/****
	*	CREATE CUSTOM TAGS
	***/
	protected $_bbmTags = null;
	protected $_xenOriginalTags = array(
		'b', 'i', 'u', 's', 'color', 'font', 'size', 'left', 'center', 
		'right', 'indent', 'url', 'email', 'img', 'quote', 'code', 'php', 
		'html', 'plain', 'media', 'attach');
	protected $_xenContentCheck;
	protected $_bbmSeparator;

	protected $_bbmDisableMethod;
	protected $_bbmImgAllowedUsergroups;

	//@extended
	public function getTags()
	{
		$parentTags = parent::getTags();

		if($this->_bbmTags === null)
		{
			$this->bakeBbmTags();
		}
		
		if($this->_bbmTags !== null)
		{
			$parentTags = $this->_filterXenTags($parentTags);
			return array_merge((array) $parentTags, (array) $this->_bbmTags);
		}
		
		return $parentTags;
	}

	public function bakeBbmTags()
	{
		$bbmTags = XenForo_Model::create('BBM_Model_BbCodes')->getAllBbCodes('strict');
		$visitor = XenForo_Visitor::getInstance();
		 
		if(!is_array($bbmTags))
		{
			return false;
		}

		$allBbmTags = array();

		foreach($bbmTags AS $bbm)
		{
			if((boolean)$bbm['active'])
			{
		  			if($bbm['start_range'])
		  			{
	  					$allBbmTags[$bbm['tag']]['options_number'] = $bbm['options_number'];
	  					$allBbmTags[$bbm['tag']]['start_range'] = $bbm['start_range'];
	  					$allBbmTags[$bbm['tag']]['end_range'] = $bbm['end_range'];
	  					$allBbmTags[$bbm['tag']]['callback'] = array($this, 'replacementMethodRenderer');

	  					if($bbm['plainCallback'])
	  					{
	  						$allBbmTags[$bbm['tag']]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
	  					}
		  			}
		  			elseif($bbm['phpcallback_class'])
		  			{
		  				$this->_preLoadTemplatesFromCallback($bbm['phpcallback_class'], $bbm['phpcallback_method']);
		  				
		  				if( method_exists($bbm['phpcallback_class'], $bbm['phpcallback_method']) )
		  				{
		  					$allBbmTags[$bbm['tag']]['phpcallback_class'] = $bbm['phpcallback_class'];
			  				$allBbmTags[$bbm['tag']]['phpcallback_method'] = $bbm['phpcallback_method'];
			  				$allBbmTags[$bbm['tag']]['callback'] = array($this, 'PhpMethodRenderer');

	  						$this->_prepareClassToLoad($bbm['phpcallback_class']);

			  				if($bbm['plainCallback'])
			  				{
			  					$allBbmTags[$bbm['tag']]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
			  				}	  						
			  			}
			  			else
			  			{
			  				$allBbmTags[$bbm['tag']]['callback'] = array($this, 'renderInvalidTag');
			  			}
		  			}
		  			elseif($bbm['template_active'])
		  			{
						//Preload template automatically
						$this->_preloadBbmTemplates[] = $bbm['template_name'];
						
		  				$allBbmTags[$bbm['tag']]['template_name'] = $bbm['template_name'];
		  				$allBbmTags[$bbm['tag']]['callback'] = array($this, 'TemplateMethodRenderer');

		  				if($bbm['template_callback_class'])
		  				{
			  				if( method_exists($bbm['template_callback_class'], $bbm['template_callback_method']) )
			  				{
				  				$allBbmTags[$bbm['tag']]['template_callback']['class'] = $bbm['template_callback_class'];
				  				$allBbmTags[$bbm['tag']]['template_callback']['method'] = $bbm['template_callback_method'];
	
			  					$this->_prepareClassToLoad($bbm['template_callback_class']);
			  					$this->_preLoadTemplatesFromCallback($bbm['template_callback_class'], $bbm['template_callback_method']);
			  				}
			  				else
				  			{
				  				$allBbmTags[$bbm['tag']]['callback'] = array($this, 'renderInvalidTag');
				  			}			  				
			  			}

		  				if($bbm['plainCallback'])
		  				{
		  					$allBbmTags[$bbm['tag']]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
		  				}
		  			}
		  			
		  			if($bbm['trimLeadingLinesAfter'] > 0 && $bbm['trimLeadingLinesAfter'] < 3)
		  			{
		  				$allBbmTags[$bbm['tag']]['trimLeadingLinesAfter'] = $bbm['trimLeadingLinesAfter'];
		  			}
		  			
		  			if($bbm['regex'])
		  			{
		  				$allBbmTags[$bbm['tag']]['optionRegex'] = $bbm['regex'];
		  			}
		  			
		  			if($bbm['plainChildren'])
		  			{
		  				$allBbmTags[$bbm['tag']]['plainChildren'] = true;
		  			}
		  			
		  			if($bbm['stopSmilies'])
		  			{
		  				$allBbmTags[$bbm['tag']]['stopSmilies'] = true;
		  			}
		  			
		  			if($bbm['stopLineBreakConversion'])
		  			{
		  				$allBbmTags[$bbm['tag']]['stopLineBreakConversion'] = true;
		  			}

		  			if($bbm['parseOptions'])
		  			{
		  				$allBbmTags[$bbm['tag']]['parseOptions'] = true;
		  			}

		  			if($bbm['parser_has_usr'])
		  			{
		  				$allBbmTags[$bbm['tag']]['parser_perms']['parser_has_usr'] = $bbm['parser_has_usr'];		  			
		  			}

		  			if($bbm['parser_usr'])
		  			{
		  				$allBbmTags[$bbm['tag']]['parser_perms']['parser_usr'] = $bbm['parser_usr'];		  			
		  			}

		  			if($bbm['parser_return'])
		  			{
		  				$allBbmTags[$bbm['tag']]['parser_perms']['parser_return'] = $bbm['parser_return'];		  			
		  			}

		  			if($bbm['parser_return_delay'])
		  			{
		  				$allBbmTags[$bbm['tag']]['parser_perms']['parser_return_delay'] = $bbm['parser_return_delay'];
		  			}

	  				$allBbmTags[$bbm['tag']]['view_perms']['can_view_content'] = true;
		  				
		  			if($bbm['view_has_usr'])
		  			{
						$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
						$visitorsOk = unserialize($bbm['view_usr']);
						$canViewBbCode = (array_intersect($visitorUserGroupIds, $visitorsOk)) ? true : false;
						
						$allBbmTags[$bbm['tag']]['view_perms']['can_view_content'] = $canViewBbCode;
		  				$allBbmTags[$bbm['tag']]['view_perms']['view_return'] = $bbm['view_return'];	

		      				if($bbm['view_return'] == 'default_template' && array_search('bbm_viewer_content_protected', $this->_preloadBbmTemplates) === false)
		      				{
		      					$this->_preloadBbmTemplates[] = 'bbm_viewer_content_protected';
		      				}

			  			if($bbm['view_return_delay'])
			  			{
			  				$allBbmTags[$bbm['tag']]['view_perms']['view_return_delay'] = $bbm['view_return_delay'];
			  			}
		  			}
		  			
		  			if($bbm['wrapping_tag'] != 'none')
		  			{
		  				$allBbmTags[$bbm['tag']]['wrappingTag']['tag'] = $bbm['wrapping_tag'];

		  				if(!empty($bbm['wrapping_option']))
		  				{
			  				$allBbmTags[$bbm['tag']]['wrappingTag']['option'] = $bbm['wrapping_option'];		  				
		  				}
		  			}
		  			
		  			if($bbm['emptyContent_check'])
		  			{
		  				$allBbmTags[$bbm['tag']]['emptyContent_check'] = true;
		  			}

	  				$allBbmTags[$bbm['tag']]['options_separator'] = $bbm['options_separator'];
		  		}
		}

		$this->_bbmTags = $allBbmTags;
		
		/****
		*	XenForo Options - only need to call once the options
		***/
		$options = XenForo_Application::get('options');
		$this->_xenContentCheck = $options->Bbm_XenContentCheck;
		$this->_bbmSeparator = $options->Bbm_BbCode_Options_Separator;
		$disabledXenTags = !empty($options->Bbcm_xenTags_disabled) ? $options->Bbcm_xenTags_disabled : array(); 

		$this->_bbmImgAllowedUsergroups = $options->Bbm_xenTags_disabled_usrgrp_img;
		
		if($options->Bbm_wrapper_img != 'none' && !in_array('img', $disabledXenTags) )
		{
			$this->_xenWrappers['img'] = $options->Bbm_wrapper_img;

			if(!empty($options->Bbm_wrapper_img_option))
			{
				$this->_xenWrappersOption['img'] = $options->Bbm_wrapper_img_option;
			}
		}

		if($options->Bbm_wrapper_allcode != 'none')
		{
			$phcTags = array('php', 'html', 'code');
			
			foreach($phcTags as $tag)
			{
				if(!in_array($tag, $disabledXenTags))
				{
					$this->_xenWrappers[$tag] = $options->Bbm_wrapper_allcode;
	
					if(!empty($options->Bbm_wrapper_allcode_option))
					{
						$this->_xenWrappersOption[$tag] = $options->Bbm_wrapper_allcode_option;
					}
				}
			}
		}
		
		if($options->Bbm_wrapper_attach != 'none' && !in_array('attach', $disabledXenTags))
		{
			$this->_xenWrappers['attach'] = $options->Bbm_wrapper_attach;		

			if(!empty($options->Bbm_wrapper_attach_option))
			{
				$this->_xenWrappersOption['attach'] = $options->Bbm_wrapper_attach_option;
			}
		}

		if( 	(!empty($options->Bbm_wrapper_callback) && $options->Bbm_wrapper_callback != 'none')
			 && method_exists($xenWrapperCallback['class'], $xenWrapperCallback['method'])
		)
		{
			$xenWrapperCallback = $options->get('Bbm_wrapper_callback', false);
			
			$this->_xenWrappersCallback['class'] = $xenWrapperCallback['class'];
			$this->_xenWrappersCallback['method'] = $xenWrapperCallback['method'];

			$this->_prepareClassToLoad($xenWrapperCallback['class']);
		}
	}

	protected function _filterXenTags($parentTags)
	{
		$options = XenForo_Application::get('options');
		$disabledXenTags = $options->Bbm_xenTags_disabled;
		$disabledMethod = $options->Bbm_xenTags_disabled_method;

		$this->_bbmDisableMethod = $disabledMethod;	
		
		if(empty($disabledXenTags))
		{
			return $parentTags;
		}

		foreach($disabledXenTags as $tag)
		{
			if($disabledMethod == 'real')
			{
				unset($parentTags[$tag]);
			}
			else
			{
				$parentTags[$tag] = array('replace' => array('', ''));
			}	
		}
		
		return $parentTags;
	}

	/****
	*	CLASS LOADER TOOLS
	*	Reason: no need to load class several times
	***/
	protected $_classToLoad = array();
	
	protected function _prepareClassToLoad($class)
	{
		if(!in_array($class, $this->_classToLoad))
		{
			$this->_classToLoad[] = $class;
		}
	}
	
	protected function _loadClass($class)
	{
		if(in_array($class, $this->_classToLoad))
		{
			XenForo_Application::autoload($class);
			$key = array_search($class, $this->_classToLoad);
			unset($this->_classToLoad[$key]);
		}		
	}

	/****
	*	RENDER TAGS METHODES
	***/

	public function replacementMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag);
		}

		$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);

		if($parserPermissionsReturn !== true)
		{
			return $parserPermissionsReturn;
		}

		$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);
		
		if($viewPermissionsReturn !== true)
		{
			return $viewPermissionsReturn;
		}

		$content = $this->renderSubTree($tag['children'], $rendererStates);
		$fallBack = htmlspecialchars($tag['original'][0]) . $content . htmlspecialchars($tag['original'][1]);

		$startRange = $tagInfo['start_range'];
		$endRange = $tagInfo['end_range'];

		preg_match_all('#{(\d+?)=([^}]*)}#ui', $startRange.$endRange, $captures, PREG_SET_ORDER);
		
		if(!empty($captures))
		{
			$startRange = preg_replace('#{(\d+?)=[^}]*}#ui', '{$1}', $startRange);
			$endRange = preg_replace('#{(\d+?)=[^}]*}#ui', '{$1}', $endRange);
		}

		if ($tag['option'] && $this->parseMultipleOptions($tag['option'], $tagInfo['options_separator']))
		{
			$options = $this->parseMultipleOptions($tag['option'], $tagInfo['options_separator']);

			if(count($options) > $tagInfo['options_number'])
			{
				return $fallBack;
			}
			
			if( isset($tagInfo['parseOptions']) )
			{
				//False parameter because the options are securised in the next loop
				$options = $this->parseAndSecuriseBbCodesInOptions($options, false);
			}

			foreach($options as $key => $option)
			{
				$option = htmlspecialchars($option);

				$id = $key+1;
				$startRange = str_replace('{'.$id.'}', $option, $startRange);
				$endRange = str_replace('{'.$id.'}', $option, $endRange);
			}
		}

		if($captures && is_array($captures))
		{
			foreach($captures as $capture)	
			{
				$curlyTag = '{'.$capture[1].'}';
				$fallbackReplacement = htmlspecialchars($capture[2]); //Securise if a user inserts a curly tag in the tag options
				$startRange = str_replace($curlyTag, $fallbackReplacement, $startRange);				
				$endRange = str_replace($curlyTag, $fallbackReplacement, $endRange);
			}
		}
		
		return $startRange.$content.$endRange;
	}

	public function TemplateMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag);
		}
			
		if(!isset($rendererStates['canUseBbCode']))
		{
			$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);
	
			if($parserPermissionsReturn !== true)
			{
				//Will a short loop with changing the rendererStates
				return $parserPermissionsReturn;
			}

			$rendererStates['isPost'] = ($this->getPostParams() !== NULL) ? true : false;
			$rendererStates['canUseBbCode'] = true;
		}

		if(!isset($rendererStates['canViewBbCode']))
		{
			$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);

			if($viewPermissionsReturn !== true)
			{
				return $viewPermissionsReturn;
			}
			
			$rendererStates['canViewBbCode'] = true;
		}

		$content = $this->renderSubTree($tag['children'], $rendererStates);
		$options = array();
		$templateName = $tagInfo['template_name'];

		if (!empty($tag['option']) && $this->parseMultipleOptions($tag['option'], $tagInfo['options_separator']))
		{
			$options = $this->parseMultipleOptions($tag['option'], $tagInfo['options_separator']);
			array_unshift($options, 'killMe');
			unset($options[0]);
			
			if( isset($tagInfo['parseOptions']) )
			{
				$options = $this->parseAndSecuriseBbCodesInOptions($options);
			}
			else
			{
				$options = $this->secureBbCodesInOptions($options);
			}
		}

		$fallBack = (!$this->_view) ? true : false;

		if( isset($tagInfo['template_callback']) )
		{
			$template_callback_class = $tagInfo['template_callback']['class'];
			$template_callback_method = $tagInfo['template_callback']['method'];
		
			$this->_loadClass($template_callback_class);
			call_user_func_array(array($template_callback_class, $template_callback_method), array(&$content, &$options, &$templateName, &$fallBack, $rendererStates, $this));
		}

		if($fallBack === true)
		{
			//Can be modified by the above template Callback
			$fallBack = '<div class="template_fallback_' . $tag['tag'] . '">' . $this->renderSubTree($tag['children'], $rendererStates) . '</div>';
		}

		$templateArguments = array('content' => $content, 'options' => $options, 'rendererStates' => $rendererStates);

		return $this->renderBbmTemplate($templateName, $templateArguments, $fallBack);
	}
	
	public function PhpMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag);
		}

		if(!isset($rendererStates['canUseBbCode']))
		{
			$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);

			if($parserPermissionsReturn !== true)
			{
				//Will do a short loop with changing the rendererStates
				return $parserPermissionsReturn;
			}

			$rendererStates['isPost'] = ($this->getPostParams() !== NULL) ? true : false;
			$rendererStates['canUseBbCode'] = true;
		}

		if(!isset($rendererStates['canViewBbCode']))
		{
			$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);

			if($viewPermissionsReturn !== true)
			{
				return $viewPermissionsReturn;
			}
			
			$rendererStates['canViewBbCode'] = true;
		}
		
		$phpcallback_class = $tagInfo['phpcallback_class'];
		$phpcallback_method = $tagInfo['phpcallback_method'];

		$this->_loadClass($phpcallback_class);
		return call_user_func_array(array($phpcallback_class, $phpcallback_method), array($tag, $rendererStates, &$this));
	}


	/****
	*	Current tag datas (easy access in this class or in callbacks to tag datas)
	***/
	
	public $currentTag;
	public $currentRendererStates;	
	
	protected function _createCurrentTag($tag, array $tagInfo, array $rendererStates)
	{
		$this->currentTag['tag'] = $tag;
		
		if(isset($tagInfo['callback'][0]))
		{
			$tagInfo['callback'][0] = '';//Not needed for info (recursive)
		}
		
		$this->currentTag['tagInfo'] = $tagInfo;
		$this->currentRendererStates = $rendererStates;
	}

	/****
	*	@extended
	*
	*	The "renderValidTag" is executed before all replacement methods
	***/	

	public function renderValidTag(array $tagInfo, array $tag, array $rendererStates)
	{
      		//Check if xen tags content can be displayed 
		$tagInfo = $this->_disableXenTagByUserGroups($tag['tag'], $tagInfo);

      		//Parent function 
		$parent = parent::renderValidTag($tagInfo, $tag, $rendererStates);
					
    		/***
		*	Empty content check: do NOT use the function "renderSubTree" => it will do some problematic loops
    		***/
      		$content = (isset($tag['children'][0])) ? $tag['children'][0] : '';
      		if(	(empty($content) && isset($tagInfo['emptyContent_check']))
      			||
      			(empty($content) && $this->_xenContentCheck && in_array($tag['tag'], $this->_xenOriginalTags))
      		)
      		{
      			//This will work for all methods
      			return $this->renderInvalidTag($tag, $rendererStates);
      		}

		//Increment tags using the XenForo Standard Replacement Method & all other callback methods than bbm
		if (	!empty($tagInfo['replace']) 
			||
			(	isset($tagInfo['callback'][1]) 
				&&
				!in_array(
					$tagInfo['callback'][1], 
					array('replacementMethodRenderer', 'PhpMethodRenderer', 'TemplateMethodRenderer')
				)
			)
		)
		{
			$this->_createCurrentTag($tag, $tagInfo, $rendererStates);

			if(!isset($rendererStates['stopIncrement']))
			{
				$this->_bakeCurrentPostParams($tag);
			}
		}

		//Xen Standard replacement
		if (!empty($tagInfo['replace']))
		{
			$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);
			if($parserPermissionsReturn !== true)
			{
				return $parserPermissionsReturn;
			}

			$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);
			if($viewPermissionsReturn !== true)
			{
				return $viewPermissionsReturn;
			}
		}

      		//Bbm Tag Wrapping option
      		if($this->_xenWrappersCallback && in_array($tag['tag'], $this->_xenOriginalTags))
      		{
			$this->_loadClass($this->_xenWrappersCallback['class']);
			call_user_func_array(array($this->_xenWrappersCallback['class'], $this->_xenWrappersCallback['method']), array($tag, $this));
      		}

      		if( isset($tagInfo['wrappingTag']['tag']) )
      		{
      			return $this->wrapMe($tag, $rendererStates, $parent);
      		}
      		elseif( isset($this->_xenWrappers[$tag['tag']]) )
      		{
      			return $this->wrapMe($tag, $rendererStates, $parent, true);
      		}
	
		return $parent;
	}

	protected function _disableXenTagByUserGroups($tagName, $tagInfo)
	{
		//To do: extend
		if($tagName != 'img')
		{
			return $tagInfo;
		}
		
		$usergroup = $this->getPostParam('user_group_id');
		$secondaryUsergroups = $this->getPostParam('secondary_group_ids');

		if($usergroup && $secondaryUsergroups && $this->_bbmImgAllowedUsergroups)
		{
			$posterUserGroupIds = array_merge(array((string)$usergroup), (explode(',', $secondaryUsergroups)));
			$postersOk = $this->_bbmImgAllowedUsergroups;
	
			if(array_intersect($posterUserGroupIds, $postersOk))
			{
				if($this->_bbmDisableMethod == 'real')
				{
					//This time the real method is faker than the fake one
					$tagInfo = array('replace' => array('[img]', '[/img]'));
				}
				else
				{
					$tagInfo = array('replace' => array('', ''));				
				}
			}
		}
		
		return $tagInfo;
	}

	public function parseMultipleOptions($tagOption, $customSeparator)
	{
		if(!empty($customSeparator))
		{
			$separator = $customSeparator;
		}
		else
		{
			$separator = (empty($this->_bbmSeparator)) ? ', ' : $this->_bbmSeparator;
		}

		$attributes = explode($separator, $tagOption);
		return $attributes;
	}

	/****
	*	MISC. TAG TOOLS
	***/
	public function hasTag($tagName)
	{
		return (isset($this->_tags[$tagName]) ? true : false);
	}

	protected $_tagNewInfo;
	
	public function addTagExtra($infoKey, $info, $arrayMode = false)
	{
		$tag = $this->currentTag['tag']['tag'];
		if($arrayMode)
		{
			$this->_tagNewInfo[$tag][$infoKey][] = $info;
		}
		else
		{
			$this->_tagNewInfo[$tag][$infoKey] = $info;		
		}
	}	
	
	public function getTagExtra($infoKey = false, $arrayKey = false)
	{
		if(!$infoKey)
		{
			return $this->_tagNewInfo;
		}

		$tag = $this->currentTag['tag']['tag'];
		
		if( !isset($this->_tagNewInfo[$tag])|| !isset($this->_tagNewInfo[$tag][$infoKey]) )
		{
			return false;
		}
		
		if($arrayKey)
		{

			if( isset($this->_tagNewInfo[$tag][$infoKey][$arrayKey]) )
			{
				return $this->_tagNewInfo[$tag][$infoKey][$arrayKey];
			}
			else
			{
				return false;
			}
		}
		
		return $this->_tagNewInfo[$tag][$infoKey];
	}

	public function getAttachmentParams($id, array $validExtensions = null, array $fallbackVisitorPerms = null)
	{
		$rendererStates = $this->currentRendererStates;

		if (isset($rendererStates['attachments'][$id]))
		{
			$attachment = $rendererStates['attachments'][$id];
			$validAttachment = true;
			$canView = empty($rendererStates['viewAttachments']) ? false : true;
			$url = XenForo_Link::buildPublicLink('attachments', $attachment);
			$fallbackPerms = false;

			if($validExtensions != null)
			{
				if(isset($attachment['extension']))
				{
					$validAttachment = (in_array($attachment['extension'], $validExtensions)) ? true : false;
				}
			}
		}
		else
		{
			$attachment = array('attachment_id' => $id);
			$validAttachment = false;
			$canView = false;
			$url = XenForo_Link::buildPublicLink('attachments', $attachment);
			$fallbackPerms = false;
			
			if($fallbackVisitorPerms != null)
			{
				foreach($fallbackVisitorPerms as $visitorPerm)
				{
					if(!isset($visitorPerm['group']) || !isset($visitorPerm['permission']))
					{
						continue;
					}
					
					if(!isset($visitorPerm['permissions']))
					{
						$visitor = XenForo_Visitor::getInstance();
						$visitorPerm['permissions'] = $visitor['permissions'];
					}
					
					$perms = XenForo_Permission::hasPermission($visitorPerm['permissions'], $visitorPerm['group'], $visitorPerm['permission']);

					if($perms == true)
					{
						$canView = true;
						$fallbackPerms = true;
						break;
					}
				}
			}
		}
		
		return array(
			'attachment' => $attachment,
			'validAttachment' => $validAttachment,
			'canView' => $canView,
			'url' => $url,
			'fallbackPerms' => $fallbackPerms
		);
	}

	
	/****
	*	PERMISSIONS TOOLS
	***/
	public function checkBbCodeParsingPerms(array $tag, array $rendererStates)
	{
		if( !isset($this->_tags[$tag['tag']]['parser_perms']) || !isset($this->_tags[$tag['tag']]['parser_perms']['parser_has_usr']) )
		{
			//No need to check parser_has_usr value since the key won't be there if disable (see @bakeBbmTags)
			return true;
		}

		$perms = $this->_tags[$tag['tag']]['parser_perms'];

		$postParams = $this->getPostParams();

		if( $postParams !== NULL)
		{
			$posterUserGroupIds = array_merge(array((string)$postParams['user_group_id']), (explode(',', $postParams['secondary_group_ids'])));
			$postersOk = unserialize($perms['parser_usr']);
		
			if(array_intersect($posterUserGroupIds, $postersOk))
			{
				return true;	
			}
		}

		if( isset($perms['parser_return_delay']) )
		{
			$autorisedLimit = $perms['parser_return_delay'];
			$post_date = $this->getPostParam('post_date');			

			if($post_date !== NULL)
			{
				$interval = XenForo_Application::$time - $post_date;
				$diff_hours = floor($interval / 3600);
				
				if($diff_hours <= $autorisedLimit)
				{
					return true;
				}
			}
		}
		
		$output = '';

		if($perms['parser_return'] == 'content')
		{
			$output = $this->renderSubTree($tag['children'], $rendererStates);
		}
		elseif($perms['parser_return'] == 'content_bb')
		{
			$output = htmlspecialchars($tag['original'][0]) . $this->renderSubTree($tag['children'], $rendererStates) . htmlspecialchars($tag['original'][1]);
		}
		elseif($perms['parser_return'] == 'callback')
		{
			$rendererStates['isPost'] = ($postParams !== NULL) ? true : false;
			$rendererStates['canUseBbCode'] = false; //Default: if is not a post, no way to get this value anyway
			return $this->PhpMethodRenderer($tag, $rendererStates, false);

		}
		elseif($perms['parser_return'] == 'template')
		{
			$rendererStates['isPost'] = ($postParams !== NULL) ? true : false;
			$rendererStates['canUseBbCode'] = false;
			return $this->TemplateMethodRenderer($tag, $rendererStates, false);
		}
		
		return $output;
	}


	public function checkBbCodeViewPerms(array $tag, array $rendererStates)
	{
		if( !isset($this->_tags[$tag['tag']]['view_perms']) )
		{
			return true;
		}
		
		$perms = $this->_tags[$tag['tag']]['view_perms'];

		if($perms['can_view_content'] === true)
		{
			return true;
		}

		if( isset($perms['view_return_delay']) )
		{
			$autorisedLimit = $perms['view_return_delay'];
			$post_date = $this->getPostParam('post_date');			

			if($post_date !== NULL)
			{
				$interval = XenForo_Application::$time - $post_date;
				$diff_hours = floor($interval / 3600);


				if($diff_hours < $autorisedLimit)
				{
					return true;
				}
			}
		}

		$rendererStates['canViewBbCode'] = false;

		$output = '';

		if($perms['view_return'] == 'phrase')
		{
			$output = new XenForo_Phrase('bbm_viewer_content_protected');
		}
		elseif($perms['view_return'] == 'callback')
		{
			return $this->PhpMethodRenderer($tag, $rendererStates, false);
		}
		elseif($perms['view_return'] == 'template')
		{
			return $this->TemplateMethodRenderer($tag, $rendererStates, false);
		}
		elseif($perms['view_return'] == 'default_template')
		{
			$fallBack = new XenForo_Phrase('bbm_viewer_content_protected');
			$templateArguments = array('phrase' => $fallBack, 'rendererStates' => $rendererStates);
			return $this->renderBbmTemplate('bbm_viewer_content_protected', $templateArguments, $fallBack);			
		}
		
		return $output;
	}

	/****
	*	WRAPPER TOOL
	***/
	protected $_xenWrappers;
	protected $_xenWrappersOption;
	protected $_xenWrappersCallback = null;	

	public function wrapMe(array $currentTag, array $rendererStates, $content, $isXenTag = false)
	{
		/****
		*	Check if the wrapping tag is available and get it
		***/
		$wrappingTag = ($isXenTag == false) ? $this->_tags[$currentTag['tag']]['wrappingTag']['tag'] : $this->_xenWrappers[$currentTag['tag']];

		if(!isset($this->_tags[$wrappingTag]))
		{
			return $content;
		}

		$wrappingTagInfo = $this->_tags[$wrappingTag];
		
		/****
		*	Create the wrapper Tag information for the parser
		***/
		$wrapper = array(
			'tag' => $wrappingTag,
			'original' => array(0 => "[$wrappingTag]", 1 => "[/$wrappingTag]"),
			'children' => array(0 => '{#content#}')
		);
		
			if( $isXenTag == false && isset($this->_tags[$currentTag['tag']]['wrappingTag']['option']) )
			{
				$wrapper['option'] = $this->_tags[$currentTag['tag']]['wrappingTag']['option'];

				if($wrapper['option'] == '#clone#')
				{
					if(isset($currentTag['option']))
					{
						$wrapper['option'] = $currentTag['option'];
					}
					else
					{
						unset($wrapper['option']);
					}
				}
			}
			
			if( $isXenTag == true && isset($this->_xenWrappersOption[$currentTag['tag']]) )
			{
				$wrapper['option'] = $this->_xenWrappersOption[$currentTag['tag']];
			}

		/****
		*	Return manager
		***/
		$rendererStates['isWrapper'] = true;
		
      		if( isset($wrappingTagInfo['callback'][1]) )
      		{
			$callBack = $wrappingTagInfo['callback'][1];
			switch ($callBack)
			{
				case 'replacementMethodRenderer':
					//Bbm Replacement Method
				        $output = $this->replacementMethodRenderer($wrapper, $rendererStates, false);
			        	break;
				case 'PhpMethodRenderer':
					//PHP Callback Method
				        $output = $this->PhpMethodRenderer($wrapper, $rendererStates, false);
				        break;
				case 'TemplateMethodRenderer':
				    	//Template Method
				        $output = $this->TemplateMethodRenderer($wrapper, $rendererStates, false);
				        break;
				default:
					//Other callbacks (php/html/etc...)
					$rendererStates['stopIncrement'] = true;
					$output = $this->renderValidTag($wrappingTagInfo, $wrapper, $rendererStates);
			}
      		}
		else
		{
			//XenForo Replacement Method
			$rendererStates['stopIncrement'] = true;					
			$output = $this->renderValidTag($wrappingTagInfo, $wrapper, $rendererStates);
		}
		
		return str_replace('{#content#}', $content, $output);
	}

	public function addWrapper($wrapperTag, $wrapperOptions = false, $separator = false)
	{
		if(!$this->hasTag($wrapperTag))
		{
			return false;
		}

		$tag = $this->currentTag['tag']['tag'];

		/*Set wrapper tag*/
		if(in_array($tag, $this->_xenOriginalTags))
		{
			//from Xen tag
			$this->_xenWrappers[$tag] = $wrapperTag;
		}
		else
		{
			//from Bbm tag
			$this->_tags[$tag]['wrappingTag']['tag'] = $wrapperTag;		
		}
		
		/*Set wrapper options*/
		if($wrapperOptions != false)
		{
			if(is_array($wrapperOptions))
			{
				$wrapperOptions = ($separator == false) ? implode($this->_bbmSeparator, $wrapperOptions) : implode($separator, $wrapperOptions);
			}

			if(in_array($tag, $this->_xenOriginalTags))
			{
				//from Xen tag
				$this->_xenWrappersOption[$tag] = $wrapperOptions;
			}
			else
			{
				//from Bbm tag
				$this->_tags[$tag]['wrappingTag']['option'] = $wrapperOptions;			
			}
		}
	}

	public function removeWrapper()
	{
		$tag = $this->currentTag['tag']['tag'];

		if(in_array($tag, $this->_xenOriginalTags))
		{
			unset($this->_xenWrappers[$tag], $this->_xenWrappersOption[$tag]);
		}
		else
		{		
			unset($this->_tags[$tag]['wrappingTag']);
		}
	}

	/****
	*	PARSER TOOLS
	***/
	protected $_parser;
	
	public function getParser()
	{
		if (!isset($this->_parser))
		{
			$this->_parser = new XenForo_BbCode_Parser($this);
		}
		return $this->_parser;
	}

	public function secureBbCodesInOptions(array $options)
	{
      		foreach ($options as &$option)
      		{
			$option = htmlspecialchars($option);
    		}
      		
      		return $options;
	}

	public function parseAndSecuriseBbCodesInOptions(array $options, $secure = true)
	{
      		foreach ($options as &$option)
      		{
			if($secure === true)
			{
				$option = htmlspecialchars($option); //Protection
			}
			
			$option = $this->ParseMyBBcodesOptions($option);
    		}
      		
      		return $options;
	}
	
	public function ParseMyBBcodesOptions($string)
	{
		$tester = strlen($string) - strlen(strip_tags($string));

		if (empty($tester) AND preg_match('#[\[{](.+?)(?:=.+?)?[}\]].+?[{\[]/\1([}\]])#i', $string, $captures))
		{
			if(isset($captures[2]) && $captures[2] == '}')
			{
				//This is an old special tag {a}...{/a}, convert it back to a normal tag [a]...[/a]
				$string = preg_replace('#[\[{]((.+?)(?:=.+?)?)[}\]](.+?)[{\[](/\2)[}\]]#i', '[$1]$3[$4]', $string);
			}

			$parser = $this->getParser();
			$string = $parser->render($string);

			//Fix for htmlspecialchars
			$string = str_replace(array('&amp;lt;', '&amp;gt;', '&amp;amp;'), array('&lt;', '&gt;', '&amp;'), $string); 
		}

		return $string;
	}

	/****
	*	PRELOAD & RENDER TEMPLATES TOOL
	***/
	protected $_preloadBbmTemplates = array();
	
	//@Extended
	public function preLoadTemplates(XenForo_View $view)
	{
		 //Preload Bbm Templates
		 if($this->_view && is_array($this->_preloadBbmTemplates))
		 {
			foreach($this->_preloadBbmTemplates as $templateName)
			{
				$this->_view->preLoadTemplate($templateName);
			}
		}

		return parent::preLoadTemplates($view);
	}

	protected function _preLoadTemplatesFromCallback($class, $method)
	{
      		//Search if the callback has some templates to preload (from the method "preloadTemplates")
      		if(method_exists($class, 'preloadTemplates'))
      		{
      			//$templateNames = $class::preloadTemplates($method); //Only after php 5.3
      			$templateNames = call_user_func(array($class, 'preloadTemplates'), $method);

      			if(!is_array($templateNames))
      			{
      				$templateNames = array($templateNames);
      			}
      			
      			foreach($templateNames as $templateName)
      			{
      				if(!empty($templateName) && array_search($templateName, $this->_preloadBbmTemplates) === false)
      				{
      					$this->_preloadBbmTemplates[] = $templateName;
      				}
      			}
      		}
	}

	public function renderBbmTemplate($templateName, array $params = array(), $fallBack = false)
	{
		if ($this->_view)
		{
			//Create and render template
			$template = $this->_view->createTemplateObject($templateName, $params);
			return $template->render();
		}

		return $fallBack;
	}

	/****
	*	GET THREAD/POSTS PARAMS TOOLS
	***/
	protected $_threadParams = null;
	protected $_postsDatas = null;
	protected $_bbCodesMap = null;
	protected $_bbCodesIncrementation = array();
	protected $_currentPostParams = null;
	protected $_useDefaultPostParams = false;
	
	/****
	*	GET RESSOURCE/CATEGORY PARAMS
	***/
	protected $_rmParams = null;

	/***
	 *  When you pass the view in the Bb Codes formaters, your have set a main key
	 *  Ie: for XenForo posts, it's "posts", for XenForo thread, it's "thread", for Extra Portal it's "items"
	 **/
	protected $_bbmViewParamsMainKey = '';

	/***
	 *  Most of the time, the targeted key (the one that will contain the messages to parse) is the main key,
	 *  but some addons uses a subkey to stock all elements (ie: Extra portal, items['data']) . Just use this variable to specify
	 *  this array sub key (ie: data). This parameter is optional
	 **/
	protected $_bbmViewParamsTargetedKey = null;

	/***
	 *  The message key (string) where Bb Codes will be parsed. Should me 'message' most of the time
	 **/
	protected $_bbmMessageKey = 'message';

	/***
	 *  The id key for the item (use for debuging)
	 **/
	protected $_bbmIdKey = 'post_id';

	/***
	 *  All extra keys to check (array). This parameter is important. To try to have data per posts, an itterator is used to create a map of the parsing tags.
	 *  If somes tags are not in the map the parser will still parse them, but the map will not be accurate anymore. For example, in XenForo posts
	 *  the signature will be all parsed. So the signature key must be added.
	 **/
	protected $_bbmExtraKeys = array();

	/***
	 *  To avoid to specify all above extra keys that must be checked you can select to use a recursive itterator that will look for all keys with string values
	 *  inside the Targeted key. The admin board can also select to use this mode in the addon options.
	 **/
	protected $_bbmRecursiveMode = false;

	/***
	 *  To remap some values (old key to new key). 
	 **/
	protected $_bbmRemapOptions = false;

	//@extended
	public function setView(XenForo_View $view = null)
	{
		parent::setView($view);

		if ($view)
		{
			$params = $view->getParams();
			$this->_checkIfDebug($params);

			/**
			 *  For posts: check thread & posts
			 **/
			if(	isset($params['posts']) && isset($params['thread']) 
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature');

				$this->_threadParams = $params['thread'];
				$this->_postsDatas = $params['posts'];

				$this->_createBbCodesMap($this->_postsDatas);			
			}

			/**
			 *  For conversations: check conversation & messages
			 *  It's not perfect, but let's use the same functions than thread & posts
			 **/
			if(	isset($params['messages']) && isset($params['conversation']) 
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature');

				$this->_threadParams = $params['conversation'];
				$this->_postsDatas = $params['messages'];
				
				$this->_bbmRemapOptions = array(
					'message_date' => 'post_date',
					'conversation_id'  => 'post_id'
				);
				
				$this->_createBbCodesMap($this->_postsDatas);			
			}

			/**
			 *  For RM (resource & category)
			 **/
			if(	isset($params['resource']) && isset($params['category'])
				&& $this->_disableTagsMap == false  && !isset($params['bbm_config'])
			)
			{
				$rm = $params['resource'];
				$this->_rmParams['category'] = $params['category'];
				$this->_rmParams = $rm;

				$this->_remapKeyValuesToPostParams(
					$rm, array(
						'resource_date' => 'post_date',
						'resource_id'  => 'post_id'
					), true
				);
			}

			/**
			 *  For Custom Addons
			 **/
			if(isset($params['bbm_config']) && $this->_disableTagsMap == false)
			{
				$config = $params['bbm_config'];
				
				if(empty($config['viewParamsMainKey']))
				{
					Zend_Debug::dump('You must set a Main Key !');
					return;
				}
				
				//Main key check				
				$mainKey = $config['viewParamsMainKey'];
				if(!isset($params[$mainKey])) {
					Zend_Debug::dump("The main key '{$mainKey}' doesn't exist.");
					return;				
				} else {
					$this->_bbmViewParamsMainKey = $mainKey;				
				}

				//Targeted key check (optional)
				$targetedKey = false;
				if(	!empty($config['viewParamsTargetedKey'])
					&& $config['viewParamsTargetedKey'] != $config['viewParamsMainKey']
					&& is_string($config['viewParamsTargetedKey'])
				){
					$targetedKey = $config['viewParamsTargetedKey'];
				}

				if(!isset($params[$mainKey][$targetedKey])) {
					Zend_Debug::dump("The targeted key '{$targetedKey}' doesn't exist.");
					return;				
				} else {
					$this->_bbmViewParamsTargetedKey = $targetedKey;				
				}
				
				//MultiPostMode is TRUE by defaut
				$multiPostMode = (isset($config['multiPostsMode']) ? $config['multiPostsMode'] : true);
				$this->_bbmRemapOptions = (isset($config['remapOptions'])) ? $config['remapOptions'] : false;

				if($multiPostMode)
				{
					/***
					 *  This mode is the one where several nodes (posts) with different posters
					 *  are loaded on the same page. It requires to create a map of all tags
					 *  To get the results, use the same functions than to get XenForo Posts/Threads
					 **/
					
					if(!empty($config['messageKey']) && is_string($config['messageKey']))
					{
						$this->_bbmMessageKey = $config['messageKey'];
					}
					
					if(!empty($config['extraKeys']) && is_array($config['extraKeys']))
					{
						$this->_bbmExtraKeys = $config['extraKeys'];
					}			
						
					if(isset($config['recursiveMode']))
					{
						$this->_bbmRecursiveMode = $config['recursiveMode'];
					}
					
					if(isset($config['idKey']))
					{
						$this->_bbmIdKey = $config['idKey'];
					}

					$this->_postsDatas = $params[$mainKey];
					$this->_createBbCodesMap($params[$mainKey]);
				}
				else
				{
					/***
					 *  This mode is the one where the elements on the page are coming from the same
					 *  posters. Ie: the XenForo Ressource Manager
					 *
					 *  Let's use again the viewParamsMainKey & viewParamsTargetedKey to keep
					 *  an unified code
					 **/

					$sourceValues = ($targetedKey) ? $params[$mainKey][$targetedKey] : $params[$mainKey];

					if(is_array($sourceValues) && is_array($this->_remapOptions))
					{				
						$this->_remapKeyValuesToPostParams($sourceValues, $this->_bbmRemapOptions, true);
					}
				}

				if(!empty($config['moreInfoParamsKey']))
				{
					/***
					 * This key is only to emulate the thread info, in other words 
					 * to have more info about the current view. It can be used to
					 * create some Bb Codes. For example if the data from that key 
					 * speficied the name of one page of your addon and you want to 
					 * set permissions on that page, you can use this.
					 * Ok, it's not very clear, so for more information, ask me by pm.
					 **/
					 
					$extraInfoKey = $config['moreInfoParamsKey'];	

					if(is_string($extraInfoKey) && isset($params[$extraInfoKey]))
					{
						$this->_threadParams = $params[$extraInfoKey];
					}
					elseif(is_array($extraInfoKey))
					{
						$wipInfo = array();
						foreach($extraInfoKey as $info)
						{
							if(!isset($params[$extraInfoKey]))
							{
								continue;
							}
							
							$wipInfo[] = $params[$extraInfoKey];
						}
						
						$this->_threadParams = $wipInfo;
					}
				}
			}
		}
	}

	/**
	 * Let's map some key values to Post Params to avoid rewrite some Bb Codes
	 * Important values: post_date, user_id, post_id, user_group_id, secondary_group_ids
	 **/
	protected function _remapKeyValuesToPostParams(array $values, array $remapOptions, $useDefaultPostParams = false)
	{
		if($useDefaultPostParams)
		{
			$this->_useDefaultPostParams = true;
		}
		
		$keyValues = array('post_date', 'user_id', 'post_id', 'user_group_id', 'secondary_group_ids');
		
		foreach($remapOptions as $originalKey => $postParamKey)
		{
			if(!isset($values[$originalKey])){
				continue;
			}
			
			if(isset($this->_currentPostParams[$postParamKey])){
				continue;
			}
			
			$this->_currentPostParams[$postParamKey] = $values[$originalKey];
			
			$key = array_search($postParamKey, $keyValues);
		
			if($key !== false)
			{
				unset($keyValues[$key]);
			}
		}

		foreach($keyValues as $keyValue)
		{
			if(!isset($values[$keyValue]))
			{
				continue;
			}
			
			$this->_currentPostParams[$keyValue] = $values[$keyValue];
		}
	}

	protected function _createBbCodesMap($posts = NULL)
	{
		if( $posts === NULL || !is_array($posts) )
		{
			return;
		}

		$options = XenForo_Application::get('options');
		$messageKey =  $this->_bbmMessageKey;
		$extraKeys =  $this->_bbmExtraKeys;
		
		foreach($posts as $post_id => $post)
		{
			if(!empty($this->_bbmViewParamsTargetedKey))
			{
				$subKey = $this->_bbmViewParamsTargetedKey;
				if( !isset($post[$subKey]) )
				{
					continue;
				}
				
				$data = $post[$subKey];
			}
			else
			{
				$data = $post;
			}

			if( !isset($data[$messageKey]) )
			{
				continue;
			}
			
			if($options->Bbm_TagsMap_GlobalMethod)
			{
				//Global method => will check  all the elements (if they are strings) of the post array
				$flattenPostIt = new RecursiveIteratorIterator( new RecursiveArrayIterator($data) );
				$allPostItemsInOne = '';
				foreach ($flattenPostIt as $postItem)
				{
					if(is_string($postItem))
					{
						$allPostItemsInOne .= '#&#' . $postItem;				
					}
				}
				$target = $allPostItemsInOne;
			}
			else
			{
				//Restrictive method => will only check the message & signature elements of the post array
				$target = $data[$messageKey];
				
				foreach($extraKeys as $extrakey)
				{
					if(!isset($data[$extrakey]) || !is_string($data[$extrakey]))
					{
						continue;
					}	
					
					$target .= $data[$extrakey];
				}
			}

			$BbCodesTree = $this->getParser()->parse($target);
			$BbCodesTreeIt = new RecursiveIteratorIterator( new RecursiveArrayIterator($BbCodesTree) );

			foreach($BbCodesTreeIt as $tagKey => $tagName)
			{
				if($tagKey === 'tag')
				{
					$this->_bbCodesMap[$tagName][] = $post_id;
				}
			}
		}

		if(self::$debug === true)
		{
			echo "Bb Codes Map:<br />";
			Zend_Debug::dump($this->_bbCodesMap);
		}
	}

	protected function _bakeCurrentPostParams($tag)
	{
		if($this->_useDefaultPostParams)
		{
			return $this->_currentPostParams;
		}

		$id = $this->_getCurrentTagId($tag);
		$tagName = $tag['tag'];

		if( !isset($this->_bbCodesMap[$tagName][$id]) )
		{
			if(self::$debug === true)
			{
				echo "According to the Tag Map, the position '$id' of the '$tagName' doesn't exist<br />";
			}

			return 	$this->_currentPostParams = NULL;
		}
		
		$postId = $this->_bbCodesMap[$tagName][$id];

		if ( !isset($this->_postsDatas[$postId]) )
		{
			if(self::$debug === true)
			{
				echo "The post id ($id) doesn't exist<br />";
			}

			return 	$this->_currentPostParams = NULL;
		}

		$this->_currentPostParams = $this->_postsDatas[$postId];
		
		if(!empty($this->_bbmRemapOptions) && is_array($this->_bbmRemapOptions))
		{
			$this->_remapKeyValuesToPostParams($this->_currentPostParams, $this->_bbmRemapOptions);		
		}

		$this->_debugInit($tag['tag']);
	}

	protected function _getCurrentTagId($tag)
	{
		$tagName = $tag['tag'];

		if( !isset($this->_bbCodesIncrementation[$tagName]) )
		{
			$this->_bbCodesIncrementation[$tagName] = 0;	
		}
		else
		{
			$this->_bbCodesIncrementation[$tagName] = $this->_bbCodesIncrementation[$tagName]+1;
		}
		
		return $this->_bbCodesIncrementation[$tagName];
	}
	
	public function getThreadParams()
	{
		return $this->_threadParams;
	}

	public function getThreadParam($param)
	{
		if( isset($this->_threadParams[$param]) )
		{
			return $this->_threadParams[$param];
		}

		if(self::$debug === true)
		{
			echo "This Thread parameter is missing: $param <br />";
		}

		return NULL;
	}

	public function getRmParams()
	{
		return $this->_rmParams;
	}

	public function getRmParam($param, $inCategory = false)
	{
		if(empty($this->_rmParams))
		{
			return NULL;
		}
		
		if(	$inCategory
			&& !empty($this->_rmParams['category'])
			&& isset($this->_rmParams['category'][$param])
		)
		{
			return $this->_rmParams['category'][$param];
		}
		elseif( !$inCategory && isset($this->_rmParams[$param]) )
		{
			return $this->_rmParams['category'][$param];		
		}
		
		return NULL;
	}

	public function getPostParams()
	{
		if(!empty($this->_bbmViewParamsTargetedKey))
		{
			$dataKey = $this->_bbmViewParamsTargetedKey;
			return $this->_currentPostParams[$dataKey];
		}		

		return $this->_currentPostParams;
	}

	public function getPostParam($param, $root = false)
	{
		if(!empty($this->_bbmViewParamsTargetedKey) && $root != false)
		{
			$dataKey = $this->_bbmViewParamsTargetedKey;
			return $this->_currentPostParams[$dataKey];
		}

		if( isset($this->_currentPostParams[$param]) )
		{
			return $this->_currentPostParams[$param];
		}
			
		if(self::$debug === true)
		{
			$callers = debug_backtrace();
			$caller = $callers[1]['function'];
			$line = $callers[1]['line'];
			echo "This Post parameter is missing: $param (calling function: $caller - line:$line)<br />";
		}
	
		return NULL;
	}	

	/****
	*	Debug Module
	*	Yes, it's ugly but it can help a lot when developping - will be removed if no problem occurs
	***/
	protected static $debug = false;
	protected $_disableTagsMap = false;

	protected function _checkIfDebug($params)
	{
		$options = XenForo_Application::get('options');
		
		if($options->Bbm_TagsMap_DebugInfo)
		{
			$visitor = XenForo_Visitor::getInstance();
			self::$debug = ($visitor['is_admin']) ? true : false;
		}

		if($options->Bbm_Params_DebugInfo)
		{
			$visitor = XenForo_Visitor::getInstance();
			if($visitor['is_admin'])
			{
				Zend_Debug::dump($params);			
			}
		}
		
		if($options->Bbm_TagsMap_Disable)
		{
			$this->_disableTagsMap = true;
		}
	}

	protected function _debugInit($tagName)
	{
      		if(self::$debug === true)
      		{
      			$tagId = $this->_bbCodesIncrementation[$tagName];
      			$postId = $this->getPostParam($this->_bbmIdKey);
      			echo "The tag being processed is $tagName (ID:$tagId - Post ID:$postId)<br />";
      		}
	}
}
//Zend_Debug::dump($abc);