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
		'html', 'plain', 'media', 'attach'
	);

	protected $_xenContentCheck;
	protected $_bbmSeparator;
	protected $_bbmDisableMethod;
	protected $_bbmXenTagsParsingAllowedUsergroups = array();
	protected $_bbmXenTagsParsingAllowedNodes = array();

	protected $_formatterUniqid;

	//@extended
	public function __construct()
	{
		$this->updateFormatterUniqid();
		return parent::__construct();
	}

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
		$bbmTags = BBM_Helper_Bbm::getBbmBbCodes();
		$activeAddons = (XenForo_Application::isRegistered('addOns')) ? XenForo_Application::get('addOns') : array();
		$visitor = XenForo_Visitor::getInstance();

		if(!is_array($bbmTags))
		{
			return false;
		}

		$allBbmTags = array();

		foreach($bbmTags AS $bbm)
		{
			if(!$bbm['active'])
			{
				continue;
			}

			if( !empty($activeAddons) && !empty($bbm['bbcode_addon']))
			{
				if( !isset($activeAddons[$bbm['bbcode_addon']]) )
				{
					//Skip Bb Codes linked to an addon when this addon is disabled
					continue;
				}
			}

			$tagName = $bbm['tag'];

			if($bbm['start_range'])
 			{
 				$allBbmTags[$tagName]['options_number'] = $bbm['options_number'];
 				$allBbmTags[$tagName]['start_range'] = $bbm['start_range'];
 				$allBbmTags[$tagName]['end_range'] = $bbm['end_range'];
 				$allBbmTags[$tagName]['callback'] = array($this, 'replacementMethodRenderer');

 				if($bbm['plainCallback'])
 				{
 					$allBbmTags[$tagName]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
 				}
 			}
 			elseif($bbm['phpcallback_class'])
 			{
 				$this->_preLoadTemplatesFromCallback($bbm['phpcallback_class'], $bbm['phpcallback_method']);

 				if( $this->_bbmCallbackChecker($bbm['phpcallback_class'], $bbm['phpcallback_method']) )
 				{
 					$allBbmTags[$tagName]['phpcallback_class'] = $bbm['phpcallback_class'];
					$allBbmTags[$tagName]['phpcallback_method'] = $bbm['phpcallback_method'];
	 				$allBbmTags[$tagName]['callback'] = array($this, 'PhpMethodRenderer');

	 				if($bbm['plainCallback'])
	 				{
	 					$allBbmTags[$tagName]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
	 				}
				}
				else
				{
					$allBbmTags[$tagName]['callback'] = array($this, 'renderInvalidTag');
				}
			}
			elseif($bbm['template_active'])
			{
				//Preload template automatically
				$this->_preloadBbmTemplates[] = $bbm['template_name'];

				$allBbmTags[$tagName]['template_name'] = $bbm['template_name'];
				$allBbmTags[$tagName]['callback'] = array($this, 'TemplateMethodRenderer');

				if($bbm['template_callback_class'])
				{
					if( $this->_bbmCallbackChecker($bbm['template_callback_class'], $bbm['template_callback_method']) )
					{
						$allBbmTags[$tagName]['template_callback']['class'] = $bbm['template_callback_class'];
						$allBbmTags[$tagName]['template_callback']['method'] = $bbm['template_callback_method'];

						$this->_preLoadTemplatesFromCallback($bbm['template_callback_class'], $bbm['template_callback_method']);
					}
					else
					{
						$allBbmTags[$tagName]['callback'] = array($this, 'renderInvalidTag');
					}
				}

				if($bbm['plainCallback'])
				{
					$allBbmTags[$tagName]['parseCallback'] = array($this, 'parseValidatePlainIfNoOption');
				}
			}

			if($bbm['trimLeadingLinesAfter'] > 0 && $bbm['trimLeadingLinesAfter'] < 3)
			{
				$allBbmTags[$tagName]['trimLeadingLinesAfter'] = $bbm['trimLeadingLinesAfter'];
			}

			if($bbm['regex'])
			{
				$allBbmTags[$tagName]['optionRegex'] = $bbm['regex'];
			}

			if($bbm['plainChildren'])
			{
				$allBbmTags[$tagName]['plainChildren'] = true;
			}

			if($bbm['stopSmilies'])
			{
				$allBbmTags[$tagName]['stopSmilies'] = true;
			}

			if($bbm['stopLineBreakConversion'])
			{
				$allBbmTags[$tagName]['stopLineBreakConversion'] = true;
			}

			if(!empty($bbm['trimContent']))
			{
				$allBbmTags[$tagName]['trimContent'] = true;
			}

			if($bbm['parseOptions'])
			{
				$allBbmTags[$tagName]['parseOptions'] = true;
			}

			if($bbm['parser_has_usr'])
			{
				$allBbmTags[$tagName]['parser_perms']['parser_has_usr'] = $bbm['parser_has_usr'];
			}

			if($bbm['parser_usr'])
			{
				$allBbmTags[$tagName]['parser_perms']['parser_usr'] = $bbm['parser_usr'];
			}

			if($bbm['parser_return'])
			{
				$allBbmTags[$tagName]['parser_perms']['parser_return'] = $bbm['parser_return'];
			}

			if($bbm['parser_return_delay'])
			{
				$allBbmTags[$tagName]['parser_perms']['parser_return_delay'] = $bbm['parser_return_delay'];
			}

			$allBbmTags[$tagName]['view_perms']['can_view_content'] = true;

			if($bbm['view_has_usr'])
			{
				$visitorUserGroupIds = array_merge(array((string)$visitor['user_group_id']), (explode(',', $visitor['secondary_group_ids'])));
				$visitorsOk = unserialize($bbm['view_usr']);
				$canViewBbCode = (array_intersect($visitorUserGroupIds, $visitorsOk)) ? true : false;

				$allBbmTags[$tagName]['view_perms']['can_view_content'] = $canViewBbCode;
				$allBbmTags[$tagName]['view_perms']['view_return'] = $bbm['view_return'];

					/*
	 				if($bbm['view_return'] == 'default_template' && array_search('bbm_viewer_content_protected', $this->_preloadBbmTemplates) === false)
					{
	 					$this->_preloadBbmTemplates[] = 'bbm_viewer_content_protected';
					}
					*/

				if($bbm['view_return_delay'])
				{
					$allBbmTags[$tagName]['view_perms']['view_return_delay'] = $bbm['view_return_delay'];
				}
			}

			if($bbm['wrapping_tag'] != 'none')
			{
				$allBbmTags[$tagName]['wrappingTag']['tag'] = $bbm['wrapping_tag'];

				if(!empty($bbm['wrapping_option']))
				{
					$allBbmTags[$tagName]['wrappingTag']['option'] = $bbm['wrapping_option'];
				}
			}

			if($bbm['emptyContent_check'])
			{
				$allBbmTags[$tagName]['emptyContent_check'] = true;
			}

			if(!empty($bbm['preParser']))
			{
				$this->addPreParserBbCode($tagName);
			}

			$allBbmTags[$tagName]['allowSignature'] = $bbm['allow_signature'];

			$allBbmTags[$tagName]['options_separator'] = $bbm['options_separator'];

			$allBbmTags[$tagName]['bbcode_id'] = $bbm['bbcode_id'];
		}

		$this->_bbmTags = $allBbmTags;

		/****
		*	XenForo Options - only need to call once the options
		***/
		$options = XenForo_Application::get('options');

		$this->_xenContentCheck = $options->Bbm_XenContentCheck;
		$this->_bbmSeparator = $options->Bbm_BbCode_Options_Separator;
		$disabledXenTags = !empty($options->Bbcm_xenTags_disabled) ? $options->Bbcm_xenTags_disabled : array();

		$this->_bbmXenTagsParsingAllowedUsergroups = array(
			'attach' => $options->Bbm_xenTags_disabled_usrgrp_attach,
			'email' => $options->Bbm_xenTags_disabled_usrgrp_email,
			'img' => $options->Bbm_xenTags_disabled_usrgrp_img,
			'url' => $options->Bbm_xenTags_disabled_usrgrp_url,
			'media' => $options->Bbm_xenTags_disabled_usrgrp_media,
			'color' => $options->Bbm_xenTags_disabled_usrgrp_color
		);

		$this->_bbmXenTagsParsingAllowedNodes = array(
			'attach' => $options->Bbm_xenTags_disabled_nodes_attach,
			'email' => $options->Bbm_xenTags_disabled_nodes_email,
			'img' => $options->Bbm_xenTags_disabled_nodes_img,
			'url' => $options->Bbm_xenTags_disabled_nodes_url,
			'media' => $options->Bbm_xenTags_disabled_nodes_media,
			'color' => $options->Bbm_xenTags_disabled_nodes_color
		);

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

		if($options->Bbm_wrapper_media != 'none' && !in_array('media', $disabledXenTags))
		{
			$this->_xenWrappers['media'] = $options->Bbm_wrapper_media;

			if(!empty($options->Bbm_wrapper_media_option))
			{
				$this->_xenWrappersOption['media'] = $options->Bbm_wrapper_media_option;
			}
		}

		if($options->Bbm_wrapper_url != 'none' && !in_array('url', $disabledXenTags))
		{
			$this->_xenWrappers['url'] = $options->Bbm_wrapper_url;

			if(!empty($options->Bbm_wrapper_url_option))
			{
				$this->_xenWrappersOption['url'] = $options->Bbm_wrapper_url_option;
			}
		}


		if( 	(!empty($options->Bbm_wrapper_callback) && $options->Bbm_wrapper_callback != 'none')
			 && $this->_bbmCallbackChecker($xenWrapperCallback['class'], $xenWrapperCallback['method'])
		)
		{
			$xenWrapperCallback = $options->get('Bbm_wrapper_callback', false);

			$this->_xenWrappersCallback['class'] = $xenWrapperCallback['class'];
			$this->_xenWrappersCallback['method'] = $xenWrapperCallback['method'];
		}

		if(!empty($options->Bbm_PreCache_XenTags))
		{
			foreach($options->Bbm_PreCache_XenTags as $tagName)
			{
				$this->addPreParserBbCode($tagName);
			}
		}
	}

	protected function _bbmCallbackChecker($class, $method = null)
	{
		if($method != null)
		{
			return (class_exists($class) && method_exists($class, $method));
		}

		return class_exists($class);
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
	*	PREPARSER BB-CODES
	*	Bb Codes that can use the pre-parser function. Purpose:
	*	limit the renderer execution to only those that need it
	***/
	protected $_bbmPreParserBbCodes = array();

	public function addPreParserBbCode($tagName)
	{
		$this->_bbmPreParserBbCodes[$tagName] = true;
	}

	public function getPreParserBbCodes()
	{
		return $this->_bbmPreParserBbCodes;
	}

	public function preParserEnableFor($tagName)
	{
		return isset($this->_bbmPreParserBbCodes[$tagName]);
	}

	/****
	*	RENDER TAGS METHODES
	***/
	public function replacementMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);
		$this->_createCurrentCallbackTag($tag, $tagInfo, $rendererStates);
		$this->bbmMethodInputFilter($tag, $rendererStates, $tagInfo);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag, $rendererStates);
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

		if (  (!empty($tag['option']) || ( isset($tag['option']) && is_numeric($tag['option']) ))
			&& $this->parseMultipleOptions($tag['option'], $tagInfo['options_separator'])
		)
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

		return $this->bbmMethodOutputFilter(
			$startRange.$content.$endRange, 'bbm_direct'
		);
	}

	public function TemplateMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);
		$this->_createCurrentCallbackTag($tag, $tagInfo, $rendererStates);
		$this->bbmMethodInputFilter($tag, $rendererStates, $tagInfo);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag, $rendererStates);
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
		elseif($rendererStates['canUseBbCode'] && $this->checkBbCodeParsingPerms($tag, $rendererStates, true) !== true)
		{
			//For nested BB Codes
			$rendererStates['canUseBbCode'] = false;
			$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);

			return $parserPermissionsReturn;
		}
		elseif(!$rendererStates['canUseBbCode'])
		{
			return $this->checkBbCodeParsingPerms($tag, $rendererStates);
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
		elseif($rendererStates['canViewBbCode'] && $this->checkBbCodeViewPerms($tag, $rendererStates, true) !== true)
		{
			//For nested BB Codes
			$rendererStates['canViewBbCode'] = false;
			$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);

			return $viewPermissionsReturn;
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

			if(method_exists($template_callback_class, '__construct'))
			{
				new $template_callback_class($this);
			}

			call_user_func_array(array($template_callback_class, $template_callback_method), array(&$content, &$options, &$templateName, &$fallBack, $rendererStates, $this));
		}

		if( !empty($tagInfo['bbcode_id']) )
		{
			$uniqueID = $tagInfo['bbcode_id'];
			XenForo_CodeEvent::fire('bbm_callback_template_pre', array(&$content, &$options, &$templateName, &$fallBack, $rendererStates, $this, $uniqueID), $uniqueID);
		}

		if($fallBack === true)
		{
			//Can be modified by the above template Callback
			$fallBack = '<div class="template_fallback_' . $tag['tag'] . '">' . $this->renderSubTree($tag['children'], $rendererStates) . '</div>';
		}

		$templateArguments = array('content' => $content, 'options' => $options, 'rendererStates' => $rendererStates);

		return $this->bbmMethodOutputFilter(
			$this->renderBbmTemplate($templateName, $templateArguments, $fallBack), 'bbm_template'
		);
	}

	public function PhpMethodRenderer(array $tag, array $rendererStates, $increment = true)
	{
		$tagInfo = $this->_tags[$tag['tag']];
		$this->_createCurrentTag($tag, $tagInfo, $rendererStates);
		$this->_createCurrentCallbackTag($tag, $tagInfo, $rendererStates);
		$this->bbmMethodInputFilter($tag, $rendererStates, $tagInfo);

		if($increment == true)
		{
			$this->_bakeCurrentPostParams($tag, $rendererStates);
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
		elseif($rendererStates['canUseBbCode'] && $this->checkBbCodeParsingPerms($tag, $rendererStates, true) !== true)
		{
			//For nested BB Codes
			$rendererStates['canUseBbCode'] = false;
			$parserPermissionsReturn = $this->checkBbCodeParsingPerms($tag, $rendererStates);

			return $parserPermissionsReturn;
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
		elseif($rendererStates['canViewBbCode'] && $this->checkBbCodeViewPerms($tag, $rendererStates, true) !== true)
		{
			//For nested BB Codes
			$rendererStates['canViewBbCode'] = false;
			$viewPermissionsReturn = $this->checkBbCodeViewPerms($tag, $rendererStates);

			return $viewPermissionsReturn;
		}

		$phpcallback_class = $tagInfo['phpcallback_class'];
		$phpcallback_method = $tagInfo['phpcallback_method'];

		return $this->bbmMethodOutputFilter(
			call_user_func_array(array($phpcallback_class, $phpcallback_method), array($tag, $rendererStates, &$this)),
			'bbm_php'
		);
	}

	public function bbmMethodInputFilter(array &$tag, array &$rendererStates, array $tagInfo)
	{
		if(!empty($tagInfo['trimContent']))
		{
			$keys = array_keys($tag['children']);
			if ($keys)
			{
				$first = reset($keys);
				$last = end($keys);

				if (is_string($tag['children'][$first]))
				{
					$tag['children'][$first] = ltrim($tag['children'][$first]);
				}
				if (is_string($tag['children'][$last]))
				{
					$tag['children'][$last] = rtrim($tag['children'][$last]);
				}
			}
		}
	}

	public function bbmMethodOutputFilter($string, $method = null)
	{
		return $string;
	}

	/****
	*	Current tag datas (easy access in this class or in callbacks to tag datas)
	***/

	public $currentTag = array();
	public $currentRendererStates = array();

	protected function _createCurrentTag($tag, array $tagInfo, array $rendererStates)
	{
		$this->currentTag['tag'] = $tag;
		$this->currentTag['tagInfo'] = $tagInfo;
		$this->currentRendererStates = $rendererStates;
	}


	/****
	*	Current callback tag datas
	***/

	public $currentCallbackTag = array();
	public $currentCallbackRendererStates = array();

	protected function _createCurrentCallbackTag($tag, array $tagInfo, array $rendererStates)
	{
		$this->currentCallbackTag['tag'] = $tag;
		$this->currentCallbackTag['tagInfo'] = $tagInfo;
		$this->currentCallbackRendererStates = $rendererStates;
	}

	/****
	*	Get advanced tag data
	***/
	public function bbmGetParentTag($rendererStates = null)
	{
		if($rendererStates == null)
		{
			$rendererStates = $this->bbmGetCurrentRendererStates();
		}

		if( isset($rendererStates['tagDataStack'], $rendererStates['tagDataStack'][0], $rendererStates['tagDataStack'][0]['tag']) )
		{
			return $rendererStates['tagDataStack'][0]['tag'];
		}

		return null;
	}

	public function bbmGetCurrentTag()
	{
		if( isset($this->currentTag['tag'], $this->currentTag['tag']['tag']) )
		{
			return $this->currentTag['tag']['tag'];
		}

		return null;
	}

	public function bbmGetCurrentTagData()
	{
		return $this->currentTag;
	}

	public function bbmGetCurrentRendererStates()
	{
		return $this->currentRendererStates;
	}

	public function bbmGetCurrentCallbackTag()
	{
		if( isset($this->currentCallbackTag['tag'], $this->currentCallbackTag['tag']['tag']) )
		{
			return $this->currentCallbackTag['tag']['tag'];
		}

		return null;
	}

	public function bbmGetCurrentCallbackTagData()
	{
		return $this->currentCallbackTag;
	}

	public function bbmGetCurrentCallbackRendererStates()
	{
		return $this->currentCallbackRendererStates;
	}

	/****
	*	@extended
	*
	*	The "renderValidTag" is executed before all replacement methods
	***/

	static $renderHash = array('replacementMethodRenderer' => true, 'PhpMethodRenderer' => true, 'TemplateMethodRenderer' => true);

	public function renderValidTag(array $tagInfo, array $tag, array $rendererStates)
	{
		$this->incrementTagMap($tagInfo, $tag, $rendererStates);
		//Check if xen tags content can be displayed
		$tagInfo = $this->_xenTagControl($tag, $tagInfo);

		if(!empty($tagInfo['_bbmNoViewPerms']))
		{
			$fallBack = new XenForo_Phrase('bbm_viewer_content_protected');
			$templateArguments = array('tagName' => $tag['tag'], 'phrase' => $fallBack, 'rendererStates' => $rendererStates);
			return $this->renderBbmTemplate('bbm_viewer_content_protected', $templateArguments, $fallBack);
		}

		//Parent function
		$parent = parent::renderValidTag($tagInfo, $tag, $rendererStates);

		/***
		*	Empty content check: do NOT use the function "renderSubTree" => it will do some problematic loops
		*	0 check content solutions: is_numeric() or $content !== '0'
		***/
		$content = (isset($tag['children'][0])) ? $tag['children'][0] : '';
		if(	(empty($content) && !is_numeric($content) && isset($tagInfo['emptyContent_check']))
			||
			(empty($content) && !is_numeric($content) && $this->_xenContentCheck && in_array($tag['tag'], $this->_xenOriginalTags))
		)
		{
			//This will work for all methods
			return $this->renderInvalidTag($tag, $rendererStates);
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


	protected $bbm_advanceTagMapOnInvalid = false;

	public function renderInvalidTag(array $tag, array $rendererStates)
	{
		// ensure that invalid tags (for whatever reason) which are a part of the tag map are still consumed.
		if ($this->bbm_advanceTagMapOnInvalid)
		{
			$this->incrementTagMap(array(), $tag, $rendererStates);
		}
		return parent::renderInvalidTag($tag, $rendererStates);
	}

	public function incrementTagMap(array $tagInfo, array $tag, array $rendererStates)
	{
		$this->bbm_advanceTagMapOnInvalid = false;
		//Increment tags using the XenForo Standard Replacement Method & all other callback methods than bbm
		if (	!empty($tagInfo['replace'])
			||
			(	isset($tagInfo['callback'][1])
				&&
				!isset(self::$renderHash[$tagInfo['callback'][1]])
			)
		)
		{
			$this->_createCurrentTag($tag, $tagInfo, $rendererStates);
			$this->_bakeCurrentPostParams($tag, $rendererStates);
		}
	}

	static $xenTagArray = array('attach' => true, 'email' => true, 'img' => true, 'media' => true, 'url' => true, 'color' => true);

	protected function _xenTagControl($tag, $tagInfo)
	{
		$tagName = strtolower($tag['tag']);

		if(!isset(self::$xenTagArray[$tagName]))
		{
			return $tagInfo;
		}

		$tagInfo = $this->_xenTagControlParsingPerms($tagName, $tagInfo);
		$tagInfo = $this->_xenTagControlViewingPerms($tagName, $tagInfo);

		return $tagInfo;
	}

	protected function _xenTagControlParsingPerms($tagName, $tagInfo)
	{
		$usergroup = $this->getPostParam('user_group_id');

		if( $usergroup === NULL || empty($this->_bbmXenTagsParsingAllowedUsergroups[$tagName]) )
		{
			return $tagInfo;
		}

		$targetedUsergroups = $this->_bbmXenTagsParsingAllowedUsergroups[$tagName];
		$secondaryUsergroups = $this->getPostParam('secondary_group_ids');
		$posterUserGroupIds = array_merge(array((string)$usergroup), (explode(',', $secondaryUsergroups)));

		if(array_intersect($posterUserGroupIds, $targetedUsergroups))
		{
			$proceed = true;
			$node_id = $this->getThreadParam('node_id');

			if($node_id)
			{
				$options = XenForo_Application::get('options');
				$allowedNodes = $this->_bbmXenTagsParsingAllowedNodes[$tagName];

				if(!empty($allowedNodes) && !in_array('all', $allowedNodes))
				{
					if(!in_array($node_id, $allowedNodes))
					{
						$proceed = false;
					}
				}
			}

			if($proceed == true)
			{
				if($this->_bbmDisableMethod == 'real')
				{
					//This time the real method is faker than the fake one
					$tagInfo = array('replace' => array("[$tagName]", "[/$tagName]"));
				}
				else
				{
					$tagInfo = array('replace' => array('', ''));
				}
			}
		}

		return $tagInfo;
	}

	/*
	 * Use of tag-type caching means this function scales per bb code tag type rather than on the number of uses of the bb code
	 */
	protected $_viewPermCache = array();

	protected function _xenTagControlViewingPerms($tagName, $tagInfo)
	{
		if($this->_bbmByPassPerms === true)
		{
			return $tagInfo;
		}

		if (isset($this->_viewPermCache[$tagName]))
		{
			return $this->_viewPermCache[$tagName];
		}

		$visitor = XenForo_Visitor::getInstance();

		/*Node Ids - disable Bb Code*/
		$permKey = "bbm_disable_{$tagName}";
		$node_id = $this->getThreadParam('node_id');

		if($node_id)
		{
			$nodePermissions = $visitor->getNodePermissions($node_id);
			$disableViewPerms = XenForo_Permission::hasContentPermission($nodePermissions, $permKey);
		}
		else
		{
			$disableViewPerms = $visitor->hasPermission('forum', $permKey);
		}

		if($disableViewPerms)
		{
			if($this->_bbmDisableMethod == 'real')
			{
				$tagInfo = array('replace' => array("[$tagName]", "[/$tagName]"));
			}
			else
			{
				$tagInfo = array('replace' => array('', ''));
			}
		}

		/*View protection - hide content*/
		$permKey = "bbm_hide_{$tagName}";

		$tagInfo['_bbmNoViewPerms'] = $visitor->hasPermission('bbm_bbcodes_grp', $permKey);

		$this->_viewPermCache[$tagName] = $tagInfo;

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
		$tag = ($this->bbmGetCurrentCallbackTag()) ? $this->bbmGetCurrentCallbackTag() : $this->bbmGetCurrentTag();

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

		$tag = ($this->bbmGetCurrentCallbackTag()) ? $this->bbmGetCurrentCallbackTag() : $this->bbmGetCurrentTag();

		if( !isset($this->_tagNewInfo[$tag]) || !isset($this->_tagNewInfo[$tag][$infoKey]) )
		{
			return null;
		}

		if($arrayKey)
		{

			if( isset($this->_tagNewInfo[$tag][$infoKey][$arrayKey]) )
			{
				return $this->_tagNewInfo[$tag][$infoKey][$arrayKey];
			}
			else
			{
				return null;
			}
		}

		return $this->_tagNewInfo[$tag][$infoKey];
	}


	protected $bbmBypassImgPerms;
	protected $bbmBypassImgPermsExt = array();
	protected $bbmBypassImgPermsContentTypes = array();

	public function getAttachmentParams($id, array $validExtensions = null, array $fallbackVisitorPerms = null)
	{
		$currentTag = $this->bbmGetCurrentTag();
		$rendererStates = $this->bbmGetCurrentRendererStates();

		if($this->bbmBypassImgPerms == null)
		{
			$xenOptions = XenForo_Application::get('options');
			$this->bbmBypassImgPerms = $xenOptions->Bbm_Bypass_Visitor_Perms_For_Img;
			$this->bbmBypassImgPermsExt = array_map('trim', explode(',', $xenOptions->Bbm_Bypass_Visitor_Perms_Img_Ext));
			$this->bbmBypassImgPermsContentTypes = $xenOptions->Bbm_Bypass_Visitor_Perms_Img_Ct;
		}

		$bypassImgPermissions = $this->bbmBypassImgPerms;
		$bypassImgExt = $this->bbmBypassImgPermsExt;
		$bypassImgCt = $this->bbmBypassImgPermsContentTypes;

		if (isset($rendererStates['attachments'], $rendererStates['attachments'][$id]))
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

			if(isset($attachment['extension']) && $bypassImgPermissions && !empty($bypassImgExt) && !empty($bypassImgCt))
			{
				if(in_array($attachment['extension'], $bypassImgExt) && in_array($attachment['content_type'], $bypassImgCt))
				{
					$canView = true;
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

		$output = array(
			'attachment' => $attachment,
			'validAttachment' => $validAttachment,
			'canView' => $canView,
			'url' => $url,
			'fallbackPerms' => $fallbackPerms
		);

		return $output;
	}

	public function getTextDirection($cssReturn = null)
	{

		$dir = strtolower($this->_textDirection);

		if($cssReturn == 'align' || $cssReturn == 'float')
		{
			return ($dir == 'ltr') ? 'left' : 'right';
		}
		elseif($cssReturn == 'padding')
		{
			return ($dir == 'ltr') ? 'padding-left' : 'padding-right';
		}
		elseif($cssReturn == 'margin')
		{
			return ($dir == 'ltr') ? 'margin-left' : 'margin-right';
		}

		return $dir;
	}

	protected $_bbmWrapMeBypassContent = false;

	public function getBbmWrapMeBypassContentState()
	{
		return $this->_bbmWrapMeBypassContent;
	}

	protected $_bbmProtectedBridgeStack = array();

	public function bbmProtectedBridge($function, $args = array())
	{
		$function = "_$function";

		if(!isset($this->_bbmProtectedBridgeStack[$function]))
		{
			$this->_bbmProtectedBridgeStack[$function] = method_exists(__CLASS__, $function);
		}

		if(!$this->_bbmProtectedBridgeStack[$function])
		{
			return -1;
		}

		return call_user_func_array(array($this, $function), $args);
	}

	public function getFormatterUniqid()
	{
		return $this->_formatterUniqid;
	}

	public function updateFormatterUniqid()
	{
		$this->_formatterUniqid = uniqid();
		return;

		/**
		*	If you don't want to use at all the php uniqid function, the below
		*	code can be used (ref: http://php.net/manual/fr/function.uniqid.php)
		*	Purpose: http://www.ning.com/code/2012/02/uniqid-is-slow/
		*	But since the uniq id is now only called once, it shouldn't be a problem
		*	anymore
		***/

		$m=microtime(true);
		$this->_formatterUniqid = sprintf("%8x%05x\n",floor($m),($m-floor($m))*1000000);
	}

	protected $_bbmUniqidStack = array();

	public function uniqid($key = 'misc_')
	{
		if(isset($this->_bbmUniqidStack[$key]))
		{
			$this->_bbmUniqidStack[$key] = $this->_bbmUniqidStack[$key]+1;
		}
		else
		{
			$this->_bbmUniqidStack[$key] = 1;
		}

		$formatterUniqid = $this->_formatterUniqid;
		$tagUniqId = $this->_bbmUniqidStack[$key];

		return "{$key}{$formatterUniqid}_{$tagUniqId}";
	}

	/****
	*	PERMISSIONS TOOLS
	***/
	public function checkBbCodeParsingPerms(array $tag, array $rendererStates, $preventLoop = false)
	{
		if($this->_bbmByPassPerms === true)
		{
			return true;
		}

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

		if($preventLoop == true)
		{
			return false;
		}

		$rendererStates['canUseBbCode'] = false; //Default: if is not a post, no way to get this value anyway
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
			$output = $this->PhpMethodRenderer($tag, $rendererStates, false);

		}
		elseif($perms['parser_return'] == 'template')
		{
			$rendererStates['isPost'] = ($postParams !== NULL) ? true : false;
			$output = $this->TemplateMethodRenderer($tag, $rendererStates, false);
		}

		if(strlen($output) == 0)
		{
			$output = "&nbsp;";
		}

		return $output;
	}

	public function checkBbCodeViewPerms(array $tag, array $rendererStates, $preventLoop = false)
	{
		if($this->_bbmByPassPerms === true)
		{
			return true;
		}

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

		if($preventLoop == true)
		{
			return false;
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
			$templateArguments = array('tagName' => $tag['tag'], 'phrase' => $fallBack, 'rendererStates' => $rendererStates);
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
	protected $_dontWrapMeParentTags = array('url');
	protected $_wrapMeContent = '';

	public function wrapMe(array $currentTagData, array $rendererStates, $content, $isXenTag = false)
	{
		/****
		*	Don't use the wrapMe function if the previous tag was the Url tag
		***/
		$parentTag = $this->bbmGetParentTag();

		if( in_array($parentTag, $this->_dontWrapMeParentTags) && !in_array($currentTagData['tag'], $this->_dontWrapMeParentTags) )
		{
			return $content;
		}

		/****
		*	Check if the wrapping tag is available and get it
		***/
		$wrappingTag = ($isXenTag == false) ? $this->_tags[$currentTagData['tag']]['wrappingTag']['tag'] : $this->_xenWrappers[$currentTagData['tag']];

		if(!isset($this->_tags[$wrappingTag]) || $wrappingTag == $currentTagData)
		{
			return $content;
		}

		$wrappingTagInfo = $this->_tags[$wrappingTag];
		$this->_wrapMeContent = $content;

		$uniqContent = uniqid('wrapme_content_');

		/****
		*	Create the wrapper Tag information for the parser
		***/
		$wrapper = array(
			'tag' => $wrappingTag,
			'original' => array(0 => "[$wrappingTag]", 1 => "[/$wrappingTag]"),
			'children' => array(0 => $uniqContent)
		);

			if( $isXenTag == false && isset($this->_tags[$currentTagData['tag']]['wrappingTag']['option']) )
			{
				$wrapper['option'] = $this->_tags[$currentTagData['tag']]['wrappingTag']['option'];

				if($wrapper['option'] == '#clone#')
				{
					if(isset($currentTagData['option']))
					{
						$wrapper['option'] = $currentTagData['option'];
					}
					else
					{
						unset($wrapper['option']);
					}
				}
			}

			if( $isXenTag == true && isset($this->_xenWrappersOption[$currentTagData['tag']]) )
			{
				$wrapper['option'] = $this->_xenWrappersOption[$currentTagData['tag']];
			}

		/****
		*	Return manager
		***/

		$rendererStates['isWrapper'] = true;
		$rendererStates['stopWhiteSpaceEmulation'] = true;

		if($parentTag == $wrappingTag)
		{
			$rendererStates['wrapMeIndenticalParent'] = true;
		}

		$this->_bbmWrapMeBypassContent = true;

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

		$this->_bbmWrapMeBypassContent = false;
		//$output = str_replace($uniqContent, $content, $output);

		return $this->bbmMethodOutputFilter($output, 'bbm_wrapme');
	}


	/**
	 * Let's give an ID to each branch of the tree that has a children that will be be proceed by the renderSubTree function
	 **/

	//@Extended
	public function renderTree(array $tree, array $extraStates = array())
	{
		$this->_bbmGiveIdToTreeChildren($tree);
		return parent::renderTree($tree, $extraStates);
	}

	protected $_bbmTreeChildrenId = 1;
	protected function _bbmGiveIdToTreeChildren(&$tree)
	{
		foreach($tree as $k => &$v)
		{
			if(!empty($v['children']) && is_array($v['children']))
			{
				$v['children']['bbm_subtree_id'] = array('bbmSubtreeId' => $this->_bbmTreeChildrenId, 'children' => array()); //Fake a children to avoid any problems
				$this->_bbmTreeChildrenId++;
				$this->_bbmGiveIdToTreeChildren($v['children']);
			}
		}
	}

	//This variable will get all IDs of the tree branches that would have been processed by the renderSubtree function
	protected $_bbmSubtreeIdsProcessed = array();

	//Extended
	public function renderSubTree(array $tree, array $rendererStates)
	{
		if($this->_bbmWrapMeBypassContent)
		{
			//With this implementation, the listeners/callbacks can be normally executed with the proper content
			return $this->_wrapMeContent;
		}

		/***
		 * Let's check if a subtree branch has already been processed - if it is, stop the incrementation
		 *
		 * 	This problem occurs when someone extends a renderer by first calling the parent (will certainly use in the background the renderSubtree function),
		 *	then uses the renderSubTree for some reasons, which makes this function used twice and will mess up with the tags map.
		 *	Ie:
		 *	public function renderBbCode(array $tag, array $rendererStates)
		 *	{
		 *		$parentOuput = parent::renderBbCode($tag, $rendererStates);
		 *
		 *		if(strtolower($tag['tag']) == 'test')
		 *		{
		 *			return $this->renderSubTree($tag['children'], $rendererStates);
		 *		}
		 *
		 *		return $parentOuput;
		 *	}
		 **/
		if(isset($tree['bbm_subtree_id']))
		{
			$id = $tree['bbm_subtree_id']['bbmSubtreeId'];

			if(isset($this->_bbmSubtreeIdsProcessed[$id]))
			{
				//The purpose of all of this !
				$rendererStates['stopIncrement'] = true;
			}
			else
			{
				$this->_bbmSubtreeIdsProcessed[$id] = true;
			}
			unset($tree['bbm_subtree_id']);
		}

		return parent::renderSubTree($tree, $rendererStates);
	}

	//@Extended
	public function renderTag(array $element, array $rendererStates, &$trimLeadingLines)
	{
		if(isset($element['bbmSubtreeId']))
		{
			/**
			 * This can occur with some Bb Codes such as "List" which is using the renderTag instead of renderSubTree
			 * The faked children is to avoid the subtree id to be converted into a real string and be displayed
			 * The below code is not useful but since it's useless to go further, let's end with it
			 **/
			return '';
		}

		$this->bbm_advanceTagMapOnInvalid = true;
		return parent::renderTag($element, $rendererStates, $trimLeadingLines);
	}

	public function addWrapper($wrapperTag, $wrapperOptions = false, $separator = false)
	{
		if(!$this->hasTag($wrapperTag))
		{
			return false;
		}

		$tag = $this->bbmGetCurrentTag();

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
		$tag = $this->bbmGetCurrentTag();

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
	protected $_bbmParser;
	protected $_transparentBbmParser;

	public function getTransparentParser()
	{
		if (!isset($this->_transparentBbmParser))
		{
			$that = clone $this;
			$that->_resetIncrementation();
			$that->_useDefaultPostParams = true;
			$this->_transparentBbmParser = XenForo_BbCode_Parser::create($that);
		}

		return $this->_transparentBbmParser;
	}

	public function getParser()
	{
		if (!isset($this->_bbmParser))
		{
			$this->_bbmParser = XenForo_BbCode_Parser::create($this);
		}
		return $this->_bbmParser;
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

			$parser = $this->getTransparentParser();
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
		$bbmListenerTemplateCache = array();
		XenForo_CodeEvent::fire('bbm_callback_template_cache', array(&$bbmListenerTemplateCache));

		if(!empty($bbmListenerTemplateCache) && is_array($bbmListenerTemplateCache) && is_array($this->_preloadBbmTemplates))
		{
			$this->_preloadBbmTemplates = array_unique(array_merge($this->_preloadBbmTemplates, $bbmListenerTemplateCache));
		}

		if($this->_view && is_array($this->_preloadBbmTemplates))
		{
			foreach($this->_preloadBbmTemplates as $templateName)
			{
				$this->_view->preLoadTemplate($templateName);
			}
		}

		$this->_view->preLoadTemplate('bbm_viewer_content_protected');

		return parent::preLoadTemplates($view);
	}

	protected function _preLoadTemplatesFromCallback($class, $method)
	{
		//Search if the callback has some templates to preload (from the method "preloadTemplates")
		if( $this->_bbmCallbackChecker($class, 'preloadTemplates') )
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
	 * When you pass the view in the Bb Codes formaters, your have set a main key
	 * Ie: for XenForo posts, it's "posts", for XenForo thread, it's "thread", for Extra Portal it's "items"
	 **/
	protected $_bbmViewParamsMainKey = '';

	/***
	 * Most of the time, the targeted key (the one that will contain the messages to parse) is the main key,
	 * but some addons uses a subkey to stock all elements (ie: Extra portal, items['data']) . Just use this variable to specify
	 * this array sub key (ie: data). This parameter is optional
	 **/
	protected $_bbmViewParamsTargetedKey = null;

	/***
	 * The message key (string) where Bb Codes will be parsed. Should be 'message' most of the time
	 **/
	protected $_bbmMessageKey = 'message';

	/***
	 * The key postpended to the message/signature key (string) which may contain pre-parsed bbcodes - Optional feature of Xenforo 1.2 upwards. Should be '%_parsed' most of the time
	 **/
	protected $_bbmPostfixParsedKey = '_parsed';


	/***
	 * The id key for the item (use for debuging)
	 **/
	protected $_bbmIdKey = 'post_id';

	/***
	 * All extra keys to check (array). This parameter is important. To try to have data per posts, an itterator is used to create a map of the parsing tags.
	 * If somes tags are not in the map the parser will still parse them, but the map will not be accurate anymore. For example, in XenForo posts
	 * the signature will be all parsed. So the signature key must be added.
	 **/
	protected $_bbmExtraKeys = array();

	/***
	 * To avoid to specify all above extra keys that must be checked you can select to use a recursive itterator that will look for all keys with string values
	 * inside the Targeted key. The admin board can also select to use this mode in the addon options.
	 **/
	protected $_bbmRecursiveMode = null;

	/***
	 * To remap some values (old key to new key).
	 **/
	protected $_bbmRemapOptions = false;

	/***
	 * If the key 'bbmBypassPermissions' has been enabled in the view, then Bbm Bb Codes permissions will be ignored
	 **/
	protected $_bbmByPassPerms = false;

	//@extended
	public function setView(XenForo_View $view = null)
	{
		parent::setView($view);
		$this->_bbmSetRequestPath();

		if ($view)
		{
			$params = $view->getParams();
			$xenOptions = XenForo_Application::get('options');
			$this->_checkIfDebug($params);
			$viewName = $this->bbmGetViewName();

			if(!empty($params['bbmBypassPermissions']))
			{
				$this->_bbmByPassPerms = true;
			}
			else
			{
				$disableProtectionViewNames = array_map('trim', explode("\n", $xenOptions->Bbm_DisablePermsViewName));

				if(in_array($viewName, $disableProtectionViewNames))
				{
					$this->_bbmByPassPerms = true;
				}
			}

			if($this->_bbmByPassPerms)
			{
				$bbCodesToDelete = $xenOptions->Bbm_DisablePermsBbCodes;

				if(is_array($bbCodesToDelete))
				{
					foreach($bbCodesToDelete as $bbCodeTag)
					{
						if(isset($this->_tags[$bbCodeTag]))
						{
							unset($this->_tags[$bbCodeTag]);
						}
					}
				}
			}

			if($xenOptions->Bbm_DisplayViewName)
			{
				if(XenForo_Visitor::getInstance()->get('is_admin'))
				{
					Zend_Debug::dump($viewName);
				}
			}

			/**
			 * For posts: check thread & posts
			 **/
			if(	isset($params['posts']) && is_array($params['posts']) && isset($params['thread'])
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature');

				$this->_threadParams = $params['thread'];
				$this->_postsDatas = $params['posts'];
				$visiblePosts = array();
				foreach($this->_postsDatas as $post_id => $post)
				{
					if ($post['message_state'] == 'visible')
					{
						$visiblePosts[$post_id] = $post;
					}
				}
				$this->_createBbCodesMap($visiblePosts, 'post', 'last_edit_date');
			}
			/**
			 * Preview new post/edit preview in thread or new thread
			 **/
			else if( (!isset($params['posts']) && isset($params['thread']) || isset($params['forum'])) && isset($params['message'])
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$visitor = XenForo_Visitor::getInstance()->ToArray();

				$this->_bbmMessageKey = 'message';

				if (isset($params['thread']))
				{
					$this->_threadParams = $params['thread'];
				}
				else
				{
					$this->_threadParams = array( 'node_id' => $params['forum']['node_id']);
				}

				$isNewMessage = (!isset($params['post']));

				if($isNewMessage)
				{
					$id = 0;
					$user_id = $visitor['user_id'];
				}
				else
				{
					$id = $params['post']['post_id'];
					$user_id = $params['post']['user_id'];
				}

				if ($user_id == $visitor['user_id'])
				{
					$user = $visitor;
				}
				else
				{
					$user = XenForo_Model::Create('XenForo_Model_User')->getUserById($user_id);
				}

				if (!empty($user))
				{
					$user_group_id = $user['user_group_id'];
					$secondary_group_ids = $user['secondary_group_ids'];
				}
				else
				{
					/*Safety fallback*/
					$user_group_id = 1; //Unregistered / Unconfirmed
					$secondary_group_ids = array();
				}

				$this->_postsDatas = array(
					$id => array(
						'post_date' => XenForo_Application::$time,
						'user_id' => $user_id,
						'post_id' => $id,
						'user_group_id' => $user_group_id,
						'secondary_group_ids' => $secondary_group_ids,
						'message' => $params['message']
					)
				);

				$this->_createBbCodesMap($this->_postsDatas);
			}
			/**
             * Posts in reports
             **/
            else if( isset($params['report']['content_type']) && $params['report']['content_type'] == 'post'
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
            {
				$visitor = XenForo_Visitor::getInstance()->ToArray();

				$this->_bbmMessageKey = 'message';
				$this->_threadParams = array( 'node_id' => $params['report']['extraContent']['node_id']);

                $id = $params['report']['content_id'];
                $user_id = $params['report']['content_user_id'];

				if ($user_id == $visitor['user_id'])
				{
					$user = $visitor;
				}
				else
				{
					$user = XenForo_Model::Create('XenForo_Model_User')->getUserById($user_id);
				}

				if (!empty($user))
				{
					$user_group_id = $user['user_group_id'];
					$secondary_group_ids = $user['secondary_group_ids'];
				}
				else
				{
					/*Safety fallback*/
					$user_group_id = 1; //Unregistered / Unconfirmed
					$secondary_group_ids = array();
				}

				$this->_postsDatas = array(
					$id => array(
						'post_date' => XenForo_Application::$time,
						'user_id' => $user_id,
						'post_id' => $id,
						'user_group_id' => $user_group_id,
						'secondary_group_ids' => $secondary_group_ids,
						'message' => $params['report']['extraContent']['message']
					)
				);

				$this->_createBbCodesMap($this->_postsDatas);
            }
			/**
			 * For conversations: check conversation & messages
			 * Let's use viewNames here, it's unlikely the content of conversations are reused in other views
			 **/
			if(	$viewName == 'XenForo_ViewPublic_Conversation_View' && isset($params['messages'], $params['conversation'])
				&& is_array($params['messages']) && $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature'); //needed

				$this->_threadParams = $params['conversation'];
				$this->_postsDatas = $params['messages'];

				$this->_bbmRemapOptions = array(
					'message_date' => 'post_date',
					'conversation_id' => 'post_id'
				);

				$this->_createBbCodesMap($this->_postsDatas);
			}
			/**
			 * Preview an existed message in conversation
			 **/
			else if( isset($params['conversationMessage']) && isset($params['message'])
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				//Viewname: XenForo_ViewPublic_Conversation_EditMessagePreview
				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature'); //Should not be needed

				$this->_threadParams = $params['conversation'];

				$params['conversationMessage']['message'] = $params['message'];
				$this->_postsDatas = array(
					$params['conversationMessage']['message_id'] => $params['conversationMessage']
				);

				$this->_createBbCodesMap($this->_postsDatas);
			}
			/**
			 * Preview a new conversation or a new message in a conversation
			 **/
			else if( $viewName == 'XenForo_ViewPublic_Conversation_Preview' && isset($params['message'])
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$visitor = XenForo_Visitor::getInstance()->ToArray();

				$this->_bbmMessageKey = 'message';
				$this->_bbmExtraKeys = array('signature'); //Should not be needed

				$this->_threadParams = array();
				$this->_postsDatas = array(
					0 => array(
						'post_date' => XenForo_Application::$time,
						'user_id' => $visitor['user_id'],
						'post_id' => 0,
						'user_group_id' => $visitor['user_group_id'],
						'secondary_group_ids' => $visitor['secondary_group_ids'],
						'message' => $params['message']
					)
				);

				$this->_createBbCodesMap($this->_postsDatas);
			}

			/**
			 * For RM (resource & category)
			 **/
			if(	isset($params['resource']) && is_array($params['resource']) && isset($params['category'])
				&& $this->_disableTagsMap == false && !isset($params['bbm_config'])
			)
			{
				$rm = $params['resource'];
				$this->_rmParams['category'] = $params['category'];
				$this->_rmParams = $rm;

				$this->_remapKeyValuesToPostParams(
					$rm, array(
						'resource_date' => 'post_date',
						'resource_id' => 'post_id'
					), true
				);
			}

			/**
			 * For Custom Addons
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

				if($targetedKey && !isset($params[$mainKey][$targetedKey])) {
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
					 * This mode is the one where several nodes (posts) with different posters
					 * are loaded on the same page. It requires to create a map of all tags
					 * To get the results, use the same functions than to get XenForo Posts/Threads
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
					 * This mode is the one where the elements on the page are coming from the same
					 * posters. Ie: the XenForo Ressource Manager
					 *
					 * Let's use again the viewParamsMainKey & viewParamsTargetedKey to keep
					 * an unified code
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
	 * Let's map some key values to Post Params to avoid to rewrite some Bb Codes
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

	protected $_parseCache = array();
	protected $_cacheBbcodeTree = false;

	protected function _createBbCodesMap($posts = NULL, $content_type = NULL, $edit_date = NULL)
	{
		if( $posts === NULL || !is_array($posts) )
		{
			return;
		}

		$bbcodesModel = $this->_getBbCodesModel();
		$options = XenForo_Application::get('options');
		$bbmRecursiveMode = ($this->_bbmRecursiveMode != null) ? $this->_bbmRecursiveMode : $options->Bbm_TagsMap_GlobalMethod;
		$messageKey = $this->_bbmMessageKey;
		$extraKeys = $this->_bbmExtraKeys;
		$parsedKeySuffix = $this->_bbmPostfixParsedKey;
		$parsedMessageKey = $messageKey . $parsedKeySuffix;
		$cache_threshold = $options->Bbm_TagsMap_Cache_Threshold; // in milliseconds
		$cache_enabled = $options->Bbm_TagsMap_Cache_Enabled && ($content_type != '');

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

			$tag_cache = array();
			$has_loaded_tags = false;
			if ($cache_enabled && isset($post[$edit_date]))
			{
				$tag_cache = $bbcodesModel->getBbCodeTagCache($content_type, $post_id, $post[$edit_date], $bbmRecursiveMode);
				if (!empty($tag_cache))
				{
					foreach($tag_cache as $tag)
					{
						$this->_bbCodesMap[$tag][] = $post_id;
					}
					$has_loaded_tags = true;
				}
			}
			$time = microtime(true);

			if($bbmRecursiveMode)
			{
				if (!$has_loaded_tags)
				{
					//Global method => will check all the elements (if they are strings) of the post array
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
					$this->_tagBBCodeFromTree($cache_enabled, $tag_cache, $post_id, $this->getParser()->parse($target));
				}
			}
			else
			{
				//Restrictive method => will only check the message & signature elements of the post array
				$BbCodesTree = null;
				if (!$has_loaded_tags)
				{
					if (isset($data[$parsedMessageKey]))
					{
						$BbCodesTree = @unserialize($data[$parsedMessageKey]);
					}

					if (!$BbCodesTree)
					{
						$target = $data[$messageKey];
						$BbCodesTree = $this->getParser()->parse($target);
					}
					$this->_tagBBCodeFromTree(!$has_loaded_tags && $cache_enabled, $tag_cache, $post_id, $BbCodesTree );
				}

				if ($this->_cacheBbcodeTree && $BbCodesTree === null)
				{
					$BbCodesTree = @unserialize($data[$parsedMessageKey]);
				}

				if ($BbCodesTree !== null)
				{
					$this->_parseCache[$post_id . $messageKey] = $BbCodesTree;
				}

				// extra data should be relatively small, don't do tag map caching. This also ensures cache invalidation stays sane
				foreach($extraKeys as $extrakey)
				{
					if(!isset($data[$extrakey]) || !is_string($data[$extrakey]))
					{
						continue;
					}

					$BbCodesTree = null;
					$extraparsedkey = $extrakey . $parsedKeySuffix;

					//Check parsed data
					if (isset($data[$extraparsedkey]))
					{
						$BbCodesTree = @unserialize($data[$extraparsedkey]);
					}

					if ($BbCodesTree == null)
					{
						$target = $data[$extrakey];
						$BbCodesTree = $this->getParser()->parse($target);
					}

					if ($this->_cacheBbcodeTree && $BbCodesTree !== null)
					{
						$this->_parseCache[$post_id . $extrakey] = $BbCodesTree;
					}

					$tmp = array();
					$this->_tagBBCodeFromTree(false, $tmp, $post_id, $BbCodesTree);
				}
			}

			if (!$has_loaded_tags && $cache_enabled && isset($post[$edit_date]) && (microtime(true) - $time)*1000 > $cache_threshold)
			{
				$bbcodesModel->setBbCodeTagCache($content_type, $post_id, $post[$edit_date], $bbmRecursiveMode, $tag_cache);
			}
		}

		if(self::$debug === true)
		{
			echo "Bb Codes Map:<br />";
			Zend_Debug::dump($this->_bbCodesMap);
		}
	}

	protected function _tagBBCodeFromTree($canCacheTagMap, array &$tagMapCache, $post_id, $BbCodesTree)
	{
		if(!is_array($BbCodesTree))
		{
			return;
		}

		foreach($BbCodesTree as $entry)
		{
			if (is_array($entry) && isset($entry['tag']))
			{
				$tag = $entry['tag'];
				$this->_bbCodesMap[$tag][] = $post_id;

				if ($canCacheTagMap)
				{
					$tagMapCache[] = $tag;
				}

				if (!empty($entry['children']))
				{
					$this->_tagBBCodeFromTree($canCacheTagMap, $tagMapCache, $post_id, $entry['children']);
				}
			}
		}
	}

	protected function _bakeCurrentPostParams($tag, $rendererStates)
	{
		if(!empty($rendererStates['stopIncrement']))
		{
			return;
		}

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

	protected function _resetIncrementation()
	{
		$this->_bbCodesIncrementation = array();
	}

	public function getBbmBbCodesMap()
	{
		return $this->_bbCodesMap;
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
			$line = isset($callers[1]['line']) ? $callers[1]['line'] : 'Unknown';
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

	/****
	*	Set/Get Request Path
	*	Often use to create proper Bb Codes with anchors - Better to do this only once
	***/

	protected $bbmRequestUri = null;
	protected $bbmFullBasePath = null;
	protected $bbmFullUri = null;

	protected function _bbmsetRequestPath()
	{
		$requestPath = XenForo_Application::get('requestPaths');

		if(isset($requestPath['requestUri']))
		{
			$this->bbmRequestUri = $requestPath['requestUri'];
		}

		if(isset($requestPath['fullBasePath']))
		{
			$this->bbmFullBasePath = $requestPath['fullBasePath'];
		}

		if(isset($requestPath['fullUri']))
		{
			$this->bbmFullUri = $requestPath['fullUri'];
		}
	}

	public function getRequestPath($mode = 'requestUri')
	{
		switch ($mode) {
			case 'requestUri':
				return $this->bbmRequestUri;
			break;
			case 'fullBasePath':
				return $this->bbmFullBasePath;
			break;
			case 'fullUri':
				return $this->bbmFullUri;
			break;
			case 'all': return array(
				'requestUri' => $this->bbmRequestUri,
				'fullBasePath' => $this->bbmFullBasePath,
				'fullUri' => $this->bbmFullUri
			);
		}
	}


	/****
	 *	Template params functions
	 ***/
	protected $bbmToolTemplate;

	public function bbmGetToolTemplate()
	{
		if(!$this->bbmToolTemplate)
		{
			$view = $this->getView();
			$params = $view->getParams();
			$params['bbm_templateSource'] = $view->getTemplateName();
			$this->bbmToolTemplate = $view->createTemplateObject('__bbm_tool_template', $params);
			//$this->bbmToolTemplate = $this->getView()->createOwnTemplateObject();
		}
		return $this->bbmToolTemplate;
	}

	public function bbmGetTemplateParam($param)
	{
		$bbmToolTemplate = $this->bbmGetToolTemplate();
		return $bbmToolTemplate->getParam($param);
	}

	public function bbmGetControllerName()
	{
		return $this->bbmGetTemplateParam('controllerName');
	}

	public function bbmGetControllerAction()
	{
		return $this->bbmGetTemplateParam('controllerAction');
	}

	public function bbmGetViewName()
	{
		return $this->bbmGetTemplateParam('viewName');
	}

	protected static $BbCodesModel = null;
	protected function _getBbCodesModel()
	{
		if (self::$BbCodesModel == null)
		{
			self::$BbCodesModel = XenForo_Model::Create('BBM_Model_BbCodes');
		}
		return self::$BbCodesModel;
	}

	//@Extended
	public function filterFinalOutput($output)
	{
		$output = parent::filterFinalOutput($output);

		if(XenForo_Application::get('options')->get('bbm_unbreakable_double_quotes'))
		{
			$output = BBM_Helper_BbCodes::unbreakableQuote($output);
		}

		return $output;
	}
}
//Zend_Debug::dump($abc);