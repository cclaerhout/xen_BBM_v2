<?php
class BBM_Options_XenOptions
{ 
      	public static function render_bm_mobile(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Model_Buttons')->getEditorConfigsForMobile($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_radio', $view, $fieldPrefix, $preparedOption, $canEdit);
      	}

      	public static function render_bm_tablets(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Model_Buttons')->getEditorConfigsForMobile($preparedOption['option_value'], true);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_radio', $view, $fieldPrefix, $preparedOption, $canEdit);
      	}

      	public static function render_active_tags(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Model_BbCodes')->getActiveTagsOption($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_select', $view, $fieldPrefix, $preparedOption, $canEdit);
      	}

	public static function render_nodes(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Options_Model_GetNodes')->getNodesOptions($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_bbm_multiselect', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	public static function render_nodes_all(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Options_Model_GetNodes')->getNodesOptions($preparedOption['option_value'], true);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_bbm_multiselect', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

	public static function render_usergroups(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
	{
		$preparedOption['formatParams'] = XenForo_Model::create('BBM_Options_Model_GetUsergroups')->getUserGroupsOptions($preparedOption['option_value']);
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_checkbox', $view, $fieldPrefix, $preparedOption, $canEdit);
	}

      	public static function render_xen_tags(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
	        $tags = array('b', 'i', 'u', 's', 'color', 'font', 'size', 'left', 'center', 'right', 'indent', 'url', 'email', 'img', 'quote', 'code', 'php', 'html', 'plain', 'media', 'attach');

	        foreach($tags as $tag)
	        {
			$xenTags[] = array(
				'label' => $tag,
				'value' => $tag,
				'selected' => in_array($tag, $preparedOption['option_value'])
			);
	        }

		$preparedOption['formatParams'] = $xenTags;
		return XenForo_ViewAdmin_Helper_Option::renderOptionTemplateInternal('option_list_option_bbm_multiselect', $view, $fieldPrefix, $preparedOption, $canEdit);
      	}

      	public static function render_bm_cust(XenForo_View $view, $fieldPrefix, array $preparedOption, $canEdit)
      	{
      		$choices = $preparedOption['option_value'];
      		$editLink = $view->createTemplateObject('option_list_option_editlink', array(
      			'preparedOption' => $preparedOption,
      			'canEditOptionDefinition' => $canEdit
      		));

		$configs = XenForo_Model::create('BBM_Model_Buttons')->getOnlyCustomConfigs();

		//Check if there is any bbm configs
		if(!is_array($configs) || empty($configs))
		{
			unset($configs);
		      	$configs['disable'] = array(
		      			'value' => 'disable',
		      			'phrase' => new XenForo_Phrase('bbm_no_editor_available')
		      	);
		}
		else
		{
			foreach($configs as &$config)
			{
				if(isset($config['config_type']))
				{
					$config['phrase'] = $config['config_name'];
				}
			}
		}

      		return $view->createTemplateObject('option_bbm_bm_cust', array(
      			'fieldPrefix' => $fieldPrefix,
      			'listedFieldName' => $fieldPrefix . '_listed[]',
      			'preparedOption' => $preparedOption,
      			'formatParams' => $preparedOption['formatParams'],
      			'editLink' => $editLink,
      			'choices' => $choices,
      			'configs' => $configs,
      			'nextCounter' => count($choices) + 1
      		));
      	}
      	
      	public static function verify_bm_cust(array &$configs, XenForo_DataWriter $dw, $fieldName)
      	{
		$srcConfigs = XenForo_Model::create('BBM_Model_Buttons')->getOnlyCustomConfigs();
		$mapEditorTypeByConfig = array();

		foreach($srcConfigs as $editorType =>  $srcConfig)
		{
			if(empty($srcConfig['config_type']))
			{
				//Should not occur
				continue;
			}
			
			$mapEditorTypeByConfig[$srcConfig['config_type']] = $srcConfig['config_ed'];
		}
		
		foreach ($configs as $key => &$config)
		{
			if(empty($config['controllername']) && empty($config['controlleraction']) && empty($config['viewname']))
			{
				unset($configs[$key]);
				continue;
			}
			
			$config['controllername'] = trim($config['controllername']);
			$config['controlleraction'] = trim($config['controlleraction']);
			$config['viewname'] = trim($config['viewname']);

			$type = $config['configtype'];
			$config['editor'] = $mapEditorTypeByConfig[$type];
		}
		
		ksort($configs);
		array_unshift($configs, 'index0');
		unset($configs[0]);

		return true;
      	}				
}
//Zend_Debug::dump($configs);