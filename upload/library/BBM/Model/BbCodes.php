<?php

class BBM_Model_BbCodes extends XenForo_Model
{	
	/**
	* Gets the specified BB code by tag id
	**/
	public function getBbCodeById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm
			WHERE tag_id = ?
		', $id);
	}
	
	/**
	* Gets the specified BB code by tag 
	**/
	public function getBbCodeByTag($tag)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm
			WHERE tag = ?
		', $tag);
	}

	/**
	* Gets the specified BB code by Unique Identifier
	**/
	public function getBbCodeByUniqueIdentifier($bbcui)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm
			WHERE bbcode_id = ?
		', $bbcui);
	}

	/**
	* Gets id from tag 
	**/
	public function getBbCodeIdFromTag($tag)
	{
		$data = $this->_getDb()->fetchRow('
			SELECT tag_id
			FROM bbm
			WHERE tag = ?
		', $tag);
		
		return $data['tag_id'];
	}

	/**
	* Gets Bb Codes by addon
	**/
	public function getBbCodesByAddon($addon)
	{
		return $this->fetchAllKeyed("
				SELECT *
				FROM bbm
				WHERE bbcode_addon = ?
				ORDER BY tag
			", 'tag_id', $addon
		);
	}

	static $allBbcodeCache = null;
	/**
	* Gets all bbm Bb Codes
	*
	* $cmd => options to excude orphan buttons => 'buttons without bbcode function' (starting with '@')
	**/
	public function getAllBbCodes($cmd = null)
	{
		if (self::$allBbcodeCache === null)
		{
			self::$allBbcodeCache = $this->fetchAllKeyed('
					SELECT *
					FROM bbm
					ORDER BY tag
				', 'tag_id');
		}
		$bbcodes = self::$allBbcodeCache;

		if(!is_array($bbcodes))
		{
			return array();
		}
		
		if(!empty($cmd) && $cmd == 'strict')
		{
			foreach ($bbcodes as $key => $bbcode)
			{
				if($bbcode['tag'][0] == '@')
				{
					unset($bbcodes[$key]);
				}
			}	
		}
		
		return $bbcodes;
	}

	/**
	* Gets all bbm Bb Codes by...
	**/
	public function getAllBbCodesBy(array $params, $sortByAddonId)
	{
		$selection = implode(', ', $params);
		
		$bbcodes = $this->fetchAllKeyed("
				SELECT $selection
				FROM bbm
				ORDER BY tag
			", "tag_id");
			
		if(!is_array($bbcodes))
		{
			return array();
		}

		if($sortByAddonId)
		{
			$bbcodes = $this->sortByAddonId($bbcodes);
		}
		
		return $bbcodes;
	}

	/**
	* Sort by AddonId
	**/
	public function sortByAddonId(array $bbCodes)
	{
		$output = array(
			'none' => array()
		);
		
		foreach($bbCodes as $bbCode)
		{
			if(!empty($bbCode['bbcode_addon']))
			{
				$output[$bbCode['bbcode_addon']][] = $bbCode;
			}
			else
			{
				$output['none'][] = $bbCode;
			}
		}
		
		return $output;
	}

	/**
	* Gets all active bbm Bb Codes
	*
	* $cmd => options to excude orphan buttons => 'buttons without bbcode function' (starting with '@')
	**/
	public function getAllActiveBbCodes($cmd = null)
	{
		$active = $this->fetchAllKeyed('
			SELECT *
			FROM bbm
			WHERE active = \'1\'
			ORDER BY tag
		', 'tag');
		
		if(!empty($cmd) && $cmd == 'strict')
		{
			foreach ($active as $key => $bbcode)
			{
				if($bbcode['tag'][0] == '@')
				{
					unset($active[$key]);
				}
			}	
		}
		
		return $active;
	}

	/* *
	*	Cache a simple list of all active tags available inside the simple cache
	*	Not use for buttons but usefull for other addons;
	*	Ref: http://xenforo.com/community/threads/what-is-the-best-way-to-add-datas-into-the-cache.30814/#post-352012
	*/
	public function simplecachedActiveBbCodes()
	{
		$cache['list'] = $this->fetchAllKeyed('
			SELECT tag, bbcode_id
			FROM bbm
			WHERE active = 1
			ORDER BY tag
		', 'tag');

		$cache['nohelp'] = $this->fetchAllKeyed('
			SELECT tag, bbcode_id
			FROM bbm
			WHERE active = 1
			AND display_help = 0
			ORDER BY tag
		', 'tag');

		$cache['protected'] = $this->fetchAllKeyed('
			SELECT tag, view_usr
			FROM bbm
			WHERE active = 1
			AND view_has_usr = 1
			ORDER BY tag
		', 'tag');

		$cache['protected_parser'] = $this->fetchAllKeyed('
			SELECT tag, parser_usr
			FROM bbm
			WHERE active = 1
			AND parser_has_usr = 1
			ORDER BY tag
		', 'tag');

		foreach($cache as $key => $item)
		{
			if(empty($item))
			{
				continue;
			}

			if($key == 'protected' || $key == 'protected_parser')
			{
				$targetKey = ($key == 'protected') ? 'view_usr' : 'parser_usr';
				//Tag in Key - Usergroups in value
				foreach($item as $tag => $protectedTagData)
				{
					if(isset($protectedTagData[$targetKey]))
					{
						$cache[$key][$tag] = unserialize($protectedTagData[$targetKey]);
					}
					else
					{
						unset($cache[$key][$tag]);
					}
				}
			}
			else
			{
				//Tag in value - if bbcode_id is set, it becomes the key, otherwise the key is the tag id (numeric) ; the value remains the tag
				foreach($item as $tag => $tagData)
				{
					if(empty($tagData))
					{
						unset($cache[$key][$tag]); continue;
					}
					
					if(!empty($tagData['bbcode_id']))
					{
						unset($cache[$key][$tag]);
						$cache[$key][$tagData['bbcode_id']] = $tagData['tag'];
					}
					elseif(!empty($tagData['tag']))
					{
						unset($cache[$key][$tag]);
						$cache[$key][] = $tagData['tag'];
					}
				}
			}
		}

		XenForo_Application::setSimpleCacheData('bbm_active', $cache);
		return $cache;
	}

	/**
	* Wipe the simple cache
	**/
	public function wipeActiveBbCodesSimpleCache()
	{
		XenForo_Application::setSimpleCacheData('bbm_active', false);
	}

	/**
	* Gets all active tags (without orphan buttons) using the simple cache => no db request
	**/
	public function getActiveTags($tagToDelete = false, $addNoneOption = false)
	{
		$cache = XenForo_Application::getSimpleCacheData('bbm_active');
		$list = $cache['list'];

		if(!is_array($list))
		{
			return array();
		}

		//Get rid of orphan buttons
		foreach ($list as $key => $bbcode)
		{
			if($bbcode[0] == '@')
			{
				unset($list[$key]);
			}
		}
		
		//Delete the current tag (optional)
		if($tagToDelete != false && isset($tagToDelete['tag']))
		{
			foreach($list as $key => $item)
			{
				if($item == $tagToDelete['tag'])
				{
					unset($list[$key]);
				}
			}
		}

		//Add a "none" option (optional
		if($addNoneOption != false)
		{
			array_unshift($list, new XenForo_Phrase('bbm_none'));
		}
	
		return $list;
	}

	/**
	* 	Bake options for the template 'active' select menu
	*/  
	public function getActiveTagsOption($selected, $addNoneOption = true)
	{
		$activeTags = array();

		foreach ($this->getActiveTags(false, $addNoneOption) AS $tag)
		{
			$activeTags[] = array(
				'label' => $tag,
				'value' => strtolower($tag),
				'selected' => (strtolower($tag) == $selected)
				);
		}

		return $activeTags;
	}


	/**
	* 	Bake options for the template 'active' multi mode
	*/  
	public function getActiveTagsMultiOption($selected, $addNoneOption = true)
	{
		$activeTags = array();

		foreach ($this->getActiveTags(false, $addNoneOption) AS $tag)
		{
			$activeTags[] = array(
				'label' => $tag,
				'value' => strtolower($tag),
				'selected' => ( in_array(strtolower($tag), $selected) )
				);
		}

		return $activeTags;
	}

	/**
	* 	Get bbcodes with a button option
	*/   
	public function getBbCodesWithButton($allFields = false)
	{
		$fields = ($allFields) ? '*' : implode(", ", BBM_Helper_Bbm::getColumnsToKeepInRegistry());
	
		return $this->fetchAllKeyed("
			SELECT $fields
			FROM bbm
			WHERE hasButton = '1'
			ORDER BY tag ASC	
		", 'tag');
	}

	/**
	* 	Get usergroups and return selected ones
	*/ 
	public function getUserGroupOptions($selectedUserGroupIds)
	{
		$selectedUserGroupIds = (is_array($selectedUserGroupIds)) ? $selectedUserGroupIds : array();
		$userGroups = array();
		
		foreach ($this->getDbUserGroups() AS $userGroup)
		{
			$userGroups[] = array(
			'label' => $userGroup['title'],
			'value' => $userGroup['user_group_id'],
			'selected' => in_array($userGroup['user_group_id'], $selectedUserGroupIds)
			);
		}
		
		return $userGroups;
	}

	/**
	* 	Get all usergroups (works with the above function)
	*/ 
	public function getDbUserGroups()
	{
		return $this->_getDb()->fetchAll('
			SELECT user_group_id, title
			FROM xf_user_group
			WHERE user_group_id
			ORDER BY user_group_id
		');
	}

	/**
	* 	Export Bb Codes using their IDS
	*	The IDS parameter must be an array
	*/ 
	public function exportFromIds(array $tagsIDs)
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;
		$rootNode = $document->createElement('bbm_bbcodes');
		$document->appendChild($rootNode);
		
		foreach($tagsIDs as $id)
		{
			$bbcodeNode = $rootNode->appendChild($document->createElement('BbCode'));
			$bbcode = $this->getBbCodeById($id);
			
			$generalNode = $bbcodeNode->appendChild($document->createElement('General'));
				$generalNode->appendChild($document->createElement('tag', $bbcode['tag']));
				$title = $generalNode->appendChild($document->createElement('title', ''));
				   $title->appendChild($document->createCDATASection($bbcode['title']));
				$description = $generalNode->appendChild($document->createElement('description', ''));
				  $description->appendChild($document->createCDATASection($bbcode['description']));
				$example = $generalNode->appendChild($document->createElement('example', ''));
				   $example->appendChild($document->createCDATASection($bbcode['example']));
				$generalNode->appendChild($document->createElement('active', $bbcode['active']));
				$generalNode->appendChild($document->createElement('display_help', $bbcode['display_help']));

				$generalNode->appendChild($document->createElement('bbcode_id', $bbcode['bbcode_id']));
				$generalNode->appendChild($document->createElement('bbcode_addon', $bbcode['bbcode_addon']));

			$MethodsNode = $bbcodeNode->appendChild($document->createElement('Methods'));
				$ReplaceMethodNode = $MethodsNode->appendChild($document->createElement('Replacement'));
					$starRange = $ReplaceMethodNode->appendChild($document->createElement('start_range', ''));
					   $starRange->appendChild($document->createCDATASection($bbcode['start_range']));
					$endRange = $ReplaceMethodNode->appendChild($document->createElement('end_range', ''));
					   $endRange->appendChild($document->createCDATASection($bbcode['end_range']));				
					$ReplaceMethodNode->appendChild($document->createElement('options_number', $bbcode['options_number']));
	
				$TemplateMethodNode = $MethodsNode->appendChild($document->createElement('Template'));
					$TemplateMethodNode->appendChild($document->createElement('active', $bbcode['template_active']));
					$TemplateMethodNode->appendChild($document->createElement('name', $bbcode['template_name']));
					$TemplateMethodNode->appendChild($document->createElement('callback_class', $bbcode['template_callback_class']));
					$TemplateMethodNode->appendChild($document->createElement('callback_method', $bbcode['template_callback_method']));
	
				$PhpMethodNode = $MethodsNode->appendChild($document->createElement('PhpCallback'));
					$PhpMethodNode->appendChild($document->createElement('class', $bbcode['phpcallback_class']));
					$PhpMethodNode->appendChild($document->createElement('method', $bbcode['phpcallback_method']));
			
			$ParserOptionsNode = $bbcodeNode->appendChild($document->createElement('ParserOptions'));
				$ParserOptionsNode->appendChild($document->createElement('stopAutoLink', $bbcode['stopAutoLink']));
				$ParserOptionsNode->appendChild($document->createElement('parseOptions', $bbcode['parseOptions']));
				$ParserOptionsNode->appendChild($document->createElement('regex', $bbcode['regex']));
				$ParserOptionsNode->appendChild($document->createElement('trimLeadingLinesAfter', $bbcode['trimLeadingLinesAfter']));
				$ParserOptionsNode->appendChild($document->createElement('plainCallback', $bbcode['plainCallback']));
				$ParserOptionsNode->appendChild($document->createElement('plainChildren', $bbcode['plainChildren']));
				$ParserOptionsNode->appendChild($document->createElement('stopSmilies', $bbcode['stopSmilies']));
				$ParserOptionsNode->appendChild($document->createElement('stopLineBreakConversion', $bbcode['stopLineBreakConversion']));
				$ParserOptionsNode->appendChild($document->createElement('trimContent', $bbcode['trimContent']));
				$ParserOptionsNode->appendChild($document->createElement('wrapping_tag', $bbcode['wrapping_tag']));
				$ParserOptionsNode->appendChild($document->createElement('wrapping_option', $bbcode['wrapping_option']));	
				$ParserOptionsNode->appendChild($document->createElement('emptyContent_check', $bbcode['emptyContent_check']));
				$ParserOptionsNode->appendChild($document->createElement('allow_signature', $bbcode['allow_signature']));
				$ParserOptionsNode->appendChild($document->createElement('preParser', $bbcode['preParser']));			
				$ParserOptionsNode->appendChild($document->createElement('options_separator', $bbcode['options_separator']));
				
			$ParserPerms = $bbcodeNode->appendChild($document->createElement('ParserPerms'));
				$ParserPerms->appendChild($document->createElement('parser_has_usr', $bbcode['parser_has_usr']));
				$ParserPerms->appendChild($document->createElement('parser_usr', $bbcode['parser_usr']));
				$ParserPerms->appendChild($document->createElement('parser_return', $bbcode['parser_return']));
				$ParserPerms->appendChild($document->createElement('parser_return_delay', $bbcode['parser_return_delay']));
	
			$ViewPerms = $bbcodeNode->appendChild($document->createElement('ViewPerms'));
				$ViewPerms->appendChild($document->createElement('view_has_usr', $bbcode['view_has_usr']));
				$ViewPerms->appendChild($document->createElement('view_usr', $bbcode['view_usr']));
				$ViewPerms->appendChild($document->createElement('view_return', $bbcode['view_return']));
				$ViewPerms->appendChild($document->createElement('view_return_delay', $bbcode['view_return_delay']));
				
			$Button = $bbcodeNode->appendChild($document->createElement('Button'));
				$Button->appendChild($document->createElement('hasButton', $bbcode['hasButton']));
				$Button->appendChild($document->createElement('button_has_usr', $bbcode['button_has_usr']));
				$Button->appendChild($document->createElement('button_usr', $bbcode['button_usr']));		
				$Button->appendChild($document->createElement('killCmd', $bbcode['killCmd']));
				$Button->appendChild($document->createElement('custCmd', $bbcode['custCmd']));
				$Button->appendChild($document->createElement('quattro_button_type', $bbcode['quattro_button_type']));
				$buttonTypeOption = $Button->appendChild($document->createElement('quattro_button_type_opt', ''));
				  $buttonTypeOption->appendChild($document->createCDATASection($bbcode['quattro_button_type_opt']));
				$Button->appendChild($document->createElement('quattro_button_return', $bbcode['quattro_button_return']));
				$quattroButtonReturnOption = $Button->appendChild($document->createElement('quattro_button_return_opt', ''));
				  $quattroButtonReturnOption->appendChild($document->createCDATASection($bbcode['quattro_button_return_opt']));
				$Button->appendChild($document->createElement('imgMethod', $bbcode['imgMethod']));//Depreciated
				$buttonDesc = $Button->appendChild($document->createElement('buttonDesc', ''));
				  $buttonDesc->appendChild($document->createCDATASection($bbcode['buttonDesc']));	
				$tagOptions = $Button->appendChild($document->createElement('tagOptions', ''));
				  $tagOptions->appendChild($document->createCDATASection($bbcode['tagOptions']));
				$tagContent = $Button->appendChild($document->createElement('tagContent', ''));
				  $tagContent->appendChild($document->createCDATASection($bbcode['tagContent']));

				$Button->appendChild($document->createElement('redactor_has_icon', $bbcode['redactor_has_icon']));
				$Button->appendChild($document->createElement('redactor_sprite_mode', $bbcode['redactor_sprite_mode']));
				$Button->appendChild($document->createElement('redactor_sprite_params_x', $bbcode['redactor_sprite_params_x']));
				$Button->appendChild($document->createElement('redactor_sprite_params_y', $bbcode['redactor_sprite_params_y']));
				$RedactorImageUrl = $Button->appendChild($document->createElement('redactor_image_url', ''));
				  $RedactorImageUrl->appendChild($document->createCDATASection($bbcode['redactor_image_url']));
				$Button->appendChild($document->createElement('redactor_button_type', $bbcode['redactor_button_type']));
				$redactorButtonTypeOption = $Button->appendChild($document->createElement('redactor_button_type_opt', ''));
				  $redactorButtonTypeOption->appendChild($document->createCDATASection($bbcode['redactor_button_type_opt']));
		}

		return $document;
	}
	
	/**
	* 	BBCM CONVERTER
	*/ 
	public function detectBbcm()
	{
		$db = $this->_getDb();
		return $db->query("SHOW TABLES LIKE 'kingk_bbcm'")->rowCount() > 0;
	}

	public function getBbcmTagsNames()
	{
		$db = $this->_getDb();
		$tableExists = $db->query("SHOW TABLES LIKE 'kingk_bbcm'")->rowCount() > 0;
	
		if(!$tableExists)
		{
			return array();
		}

      		$bbcmBbCodes = $this->fetchAllKeyed('
	      			SELECT *
      				FROM kingk_bbcm
      				ORDER BY tag
	      		', 'tag');

		if(!is_array($bbcmBbCodes))
		{
			return array();
		}	      		
	      	
	      	foreach($bbcmBbCodes as $k => $bbcmBbCode)
	      	{
	      		if( isset($bbcmBbCode['phpcallback_class']) && $bbcmBbCode['phpcallback_class'] == 'KingK_BbCodeManager_BbCode_Formatter_Default')
	      		{
	      			unset($bbcmBbCodes[$k]);
	      		}

	      		if( isset($bbcmBbCode['template_callback_class']) && $bbcmBbCode['template_callback_class'] == 'KingK_BbCodeManager_BbCode_Formatter_Default')
	      		{
	      			unset($bbcmBbCodes[$k]);
	      		}	      	
	      	}
	      	
	      	return $bbcmBbCodes;
	}

	public function getBbcmTagByTagName($tag)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM kingk_bbcm
			WHERE tag = ?
		', $tag);
	}

	public function convertAndExportBbcmTags(array $tagsNames)
	{
		$document = new DOMDocument('1.0', 'utf-8');
		$document->formatOutput = true;
		$rootNode = $document->createElement('bbm_bbcodes');
		$document->appendChild($rootNode);


		foreach($tagsNames as $tagName)
		{
			$bbcodeNode = $rootNode->appendChild($document->createElement('BbCode'));
			$bbcode = $this->getBbcmTagByTagName($tagName);
			
			$keysToCheck = array(	'display_help', 'template_active', 'template_name', 'template_callback_class', 'template_callback_method',
						'stopAutoLink', 'parseOptions', 'wrapping_tag', 'wrapping_option', 'emptyContent_check', 'allow_signature', 'preParser',
						'parser_has_usr', 'parser_usr', 'parser_return', 'parser_return_delay', 
						'view_has_usr', 'view_usr', 'view_return', 'view_return_delay',
						'hasButton', 'button_has_usr', 'button_usr', 'killCmd', 'custCmd', 'imgMethod', 'buttonDesc', 'tagOptions', 'tagContent',
						'quattro_button_type', 'quattro_button_type_opt', 'quattro_button_return', 'quattro_button_return_opt',
						'redactor_has_icon', 'redactor_sprite_mode', 'redactor_image_url', 'redactor_sprite_params_x', 'redactor_sprite_params_y',
						'redactor_button_type', 'redactor_button_type_opt'
					);

			foreach($keysToCheck as $k)
			{
				if(!isset($bbcode[$k]))
				{
					$bbcode[$k] = '';
				}
			}

			$generalNode = $bbcodeNode->appendChild($document->createElement('General'));
				$generalNode->appendChild($document->createElement('tag', $bbcode['tag']));
				$title = $generalNode->appendChild($document->createElement('title', ''));
				   $title->appendChild($document->createCDATASection($bbcode['title']));
				$description = $generalNode->appendChild($document->createElement('description', ''));
				  $description->appendChild($document->createCDATASection($bbcode['description']));
				$example = $generalNode->appendChild($document->createElement('example', ''));
				   $example->appendChild($document->createCDATASection($bbcode['example']));
				$generalNode->appendChild($document->createElement('active', $bbcode['active']));
				$generalNode->appendChild($document->createElement('display_help', $bbcode['display_help']));
				
			$MethodsNode = $bbcodeNode->appendChild($document->createElement('Methods'));
				$ReplaceMethodNode = $MethodsNode->appendChild($document->createElement('Replacement'));
					$starRange = $ReplaceMethodNode->appendChild($document->createElement('start_range', ''));
					   $starRange->appendChild($document->createCDATASection($bbcode['replacementBegin']));
					$endRange = $ReplaceMethodNode->appendChild($document->createElement('end_range', ''));
					   $endRange->appendChild($document->createCDATASection($bbcode['replacementEnd']));				
					$ReplaceMethodNode->appendChild($document->createElement('options_number', $bbcode['numberOfOptions']));
	
				$TemplateMethodNode = $MethodsNode->appendChild($document->createElement('Template'));
					$TemplateMethodNode->appendChild($document->createElement('active', $bbcode['template_active']));
					$TemplateMethodNode->appendChild($document->createElement('name', $bbcode['template_name']));
					$TemplateMethodNode->appendChild($document->createElement('callback_class', $bbcode['template_callback_class']));
					$TemplateMethodNode->appendChild($document->createElement('callback_method', $bbcode['template_callback_method']));
	
				$PhpMethodNode = $MethodsNode->appendChild($document->createElement('PhpCallback'));
					$PhpMethodNode->appendChild($document->createElement('class', $bbcode['phpcallback_class']));
					$PhpMethodNode->appendChild($document->createElement('method', $bbcode['phpcallback_method']));
			
			$ParserOptionsNode = $bbcodeNode->appendChild($document->createElement('ParserOptions'));
				$ParserOptionsNode->appendChild($document->createElement('stopAutoLink', $bbcode['stopAutoLink']));
				$ParserOptionsNode->appendChild($document->createElement('parseOptions', $bbcode['parseOptions']));
				$ParserOptionsNode->appendChild($document->createElement('regex', $bbcode['regex']));
				$ParserOptionsNode->appendChild($document->createElement('trimLeadingLinesAfter', $bbcode['trimLeadingLinesAfter']));
				$ParserOptionsNode->appendChild($document->createElement('plainCallback', $bbcode['plainCallback']));
				$ParserOptionsNode->appendChild($document->createElement('plainChildren', $bbcode['plainChildren']));
				$ParserOptionsNode->appendChild($document->createElement('stopSmilies', $bbcode['stopSmilies']));
				$ParserOptionsNode->appendChild($document->createElement('stopLineBreakConversion', $bbcode['stopLineBreakConversion']));
				$ParserOptionsNode->appendChild($document->createElement('trimContent', $bbcode['trimContent']));
				$ParserOptionsNode->appendChild($document->createElement('wrapping_tag', $bbcode['wrapping_tag']));
				$ParserOptionsNode->appendChild($document->createElement('wrapping_option', $bbcode['wrapping_option']));		
				$ParserOptionsNode->appendChild($document->createElement('emptyContent_check', $bbcode['emptyContent_check']));
				$ParserOptionsNode->appendChild($document->createElement('allow_signature', $bbcode['allow_signature']));
				$ParserOptionsNode->appendChild($document->createElement('preParser', $bbcode['preParser']));
				
			$ParserPerms = $bbcodeNode->appendChild($document->createElement('ParserPerms'));
				$ParserPerms->appendChild($document->createElement('parser_has_usr', $bbcode['parser_has_usr']));
				$ParserPerms->appendChild($document->createElement('parser_usr', $bbcode['parser_usr']));
				$ParserPerms->appendChild($document->createElement('parser_return', $bbcode['parser_return']));
				$ParserPerms->appendChild($document->createElement('parser_return_delay', $bbcode['parser_return_delay']));
	
			$ViewPerms = $bbcodeNode->appendChild($document->createElement('ViewPerms'));
				$ViewPerms->appendChild($document->createElement('view_has_usr', $bbcode['view_has_usr']));
				$ViewPerms->appendChild($document->createElement('view_usr', $bbcode['view_usr']));
				$ViewPerms->appendChild($document->createElement('view_return', $bbcode['view_return']));
				$ViewPerms->appendChild($document->createElement('view_return_delay', $bbcode['view_return_delay']));
				
			$Button = $bbcodeNode->appendChild($document->createElement('Button'));
				$Button->appendChild($document->createElement('hasButton', $bbcode['hasButton']));
				$Button->appendChild($document->createElement('button_has_usr', $bbcode['button_has_usr']));
				$Button->appendChild($document->createElement('button_usr', $bbcode['button_usr']));		
				$Button->appendChild($document->createElement('killCmd', $bbcode['killCmd']));
				$Button->appendChild($document->createElement('custCmd', $bbcode['custCmd']));

				$Button->appendChild($document->createElement('quattro_button_type', $bbcode['quattro_button_type']));
				$buttonTypeOption = $Button->appendChild($document->createElement('quattro_button_type_opt', ''));
				  $buttonTypeOption->appendChild($document->createCDATASection($bbcode['quattro_button_type_opt']));	
				$Button->appendChild($document->createElement('quattro_button_return', $bbcode['quattro_button_return']));
				$buttonReturnOption = $Button->appendChild($document->createElement('quattro_button_return_opt', ''));
				  $buttonReturnOption->appendChild($document->createCDATASection($bbcode['quattro_button_return_opt']));
				
				$Button->appendChild($document->createElement('imgMethod', $bbcode['imgMethod'])); //Depreciated
				
				$buttonDesc = $Button->appendChild($document->createElement('buttonDesc', ''));
				  $buttonDesc->appendChild($document->createCDATASection($bbcode['buttonDesc']));	
				$tagOptions = $Button->appendChild($document->createElement('tagOptions', ''));
				  $tagOptions->appendChild($document->createCDATASection($bbcode['tagOptions']));
				$tagContent = $Button->appendChild($document->createElement('tagContent', ''));
				  $tagContent->appendChild($document->createCDATASection($bbcode['tagContent']));

				$Button->appendChild($document->createElement('redactor_has_icon', $bbcode['redactor_has_icon']));
				$Button->appendChild($document->createElement('redactor_sprite_mode', $bbcode['redactor_sprite_mode']));
				$Button->appendChild($document->createElement('redactor_sprite_params_x', $bbcode['redactor_sprite_params_x']));
				$Button->appendChild($document->createElement('redactor_sprite_params_y', $bbcode['redactor_sprite_params_y']));
				$RedactorImageUrl = $Button->appendChild($document->createElement('redactor_image_url', ''));
				  $RedactorImageUrl->appendChild($document->createCDATASection($bbcode['redactor_image_url']));
		}

		return $document;
	}
    
	protected $cacheObject = null;
	protected $TagMapCacheOptions = null;

	protected function _getTagMapCacheOptions()
	{
		if ($this->TagMapCacheOptions === null)
		{
			$options = XenForo_Application::get('options');
			$this->TagMapCacheOptions = array(
				'Expiry'       => $options->Bbm_TagsMap_Cache_Expiry,
				'EnableCache'  => $options->Bbm_TagsMap_Cache_Enabled,
				'bbCodeCacheVersion'  => $options->bbCodeCacheVersion
				);
		}   
		return $this->TagMapCacheOptions;
	}

	public function getBbCodeTagCache($content_type, $post_id, $post_date, $bbmRecursiveMode)
	{ 
		if ($this->cacheObject === null)
		{
			$this->cacheObject = XenForo_Application::getCache();
		}
		if ($this->cacheObject !== false)
		{
			$options = $this->_getTagMapCacheOptions();

			$cacheId = 'tagmap_'. $content_type . '_'.
						$post_id . '_'.
						$options['bbCodeCacheVersion'] . '_'.
                        ($bbmRecursiveMode ? '1' : '0') . '_'.
						$post_date
						;
			if ($raw = $this->cacheObject->load($cacheId, true))
			{
				return explode(',', $raw);
			}
		}   
		return array();
	}    

	public function setBbCodeTagCache($content_type, $post_id, $post_date, $bbmRecursiveMode, array $tagMapCache)
	{  
		if ($this->cacheObject === null)
		{
			$this->cacheObject = XenForo_Application::getCache();
		}

		if ($this->cacheObject)
		{
			$options = $this->_getTagMapCacheOptions();

			$cacheId = 'tagmap_'. $content_type . '_'.
						$post_id . '_'.
						$options['bbCodeCacheVersion'] . '_'.
                        ($bbmRecursiveMode ? '1' : '0') . '_'.
						$post_date
						;
			if (!empty($tagMapCache) && $options['EnableCache'])
			{
				$data = implode(',', $tagMapCache);
                $this->cacheObject->save($data, $cacheId, array(), $options['Expiry']);
			}
			else
			{
                $this->cacheObject->remove($cacheId);
			}
		}
	}
}
//Zend_Debug::dump($code);