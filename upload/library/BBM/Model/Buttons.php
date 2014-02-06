<?php

class BBM_Model_Buttons extends XenForo_Model
{
	/**
	* Get TinyQuattro buttons
	*/
	public function checkQuattroTable(){
		$db = $this->_getDb();
		return ($db->query("SHOW TABLES LIKE 'bbm_tinyquattro'")->rowCount() > 0) ? true : false;
	}
	
	public function getQuattroDatas()
	{
		return $this->fetchAllKeyed('
			SELECT *
			FROM  bbm_tinyquattro
			ORDER BY button_name
		', 'button_id');
	}

	public function getQuattroByTextDirection($direction)
	{
		return $this->fetchAllKeyed("
			SELECT *
			FROM  bbm_tinyquattro
			ORDER BY button_{$direction}_pos
		", 'button_id');
	}

	//Output format options: 'array', 'string' or 'js' 
	public function getQuattroReadyToUse($direction = 'ltr', $outputFormat = 'array', $buttonSeparator = ' ', $groupSeparator = '|', $lineSeparator = '#')
	{
		$buttons = $this->getQuattroByTextDirection($direction);
		
		if(empty($buttons))
		{
			return $data;
		}
		
		$lines = array();
		
		foreach($buttons as $data)
		{
			$name = $data['button_name'];
			$line = $data['button_line'];
			$newGroup = ($data['button_separator']) ? $buttonSeparator.$groupSeparator : '';

			if(!isset($lines[$line]))
			{
				$lines[$line] = $name.$newGroup;
			}
			else
			{
				$lines[$line] .= $buttonSeparator.$name.$newGroup;
			}
		}
		
		if($outputFormat == 'array')
		{
			return $lines;
		}
		elseif($outputFormat == 'string')
		{
			$readyToUse = '';
			$numberOfLines = count($lines);
				
			foreach($lines as $number => $buttonsList)
			{	
				$endString = ($number == $numberOfLines) ? '' : $buttonSeparator.$lineSeparator.$buttonSeparator;
				$readyToUse .= $buttonsList.$endString;
			}
		
			return $readyToUse;			
		}
		elseif($outputFormat == 'js')
		{
			$readyToUse = '';
			$numberOfLines = count($lines);
				
			foreach($lines as $number => $buttonsList)
			{	
				$endString = ($number == $numberOfLines) ? ',' : ",\r\n";
				$readyToUse .= "toolbar$number: \"$buttonsList\"$endString";
			}
		
			return $readyToUse;
		}
	}

	public function getQuattroFontsMap()
	{
		$data =  $this->fetchAllKeyed("
			SELECT button_name, button_font
			FROM  bbm_tinyquattro
		", 'button_id');
		
		if(empty($data))
		{
			$data;
		}
		
		foreach($data as $d)
		{
			$name = $d['button_name'];
			$font = $d['button_font'];
			$map[$name] = $font;
		}
		
		return $map;
	}

	/**
	* Get configs by Id
	*/
	public function getConfigById($id)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm_buttons
			WHERE config_id = ?
		', $id);
	}

	/**
	* Get configs by type
	*/	
	public function getConfigByType($type)
	{
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM bbm_buttons
			WHERE config_type = ?
		', $type);
	}
 
	/**
	* Get all Configs
	*/
	public function getAllConfigs($sortBy = 'config_id')
	{
		return $this->fetchAllKeyed('
			SELECT * 
			FROM bbm_buttons
			ORDER BY config_type
		', $sortBy);
	}
	
	/**
	* Get only custom configs (exclude RTL/LTR)
	*/
	public function getOnlyCustomConfigs()
	{
		$configs = $this->getAllConfigs('config_type');
		//unset($configs['ltr'], $configs['rtl'], $configs['redactor']); // Let Redactor
		unset($configs['ltr'], $configs['rtl']);
		return $configs;
	}

	/**
	* Buttons DW Management
	*/
	public function addUpdateButtonInAllConfigs($buttonData)
	{
		$this->_manageButtonInAllConfigs($buttonData, 'update');
	}

	public function deleteButtonInAllConfigs($buttonData)
	{
		$this->_manageButtonInAllConfigs($buttonData, 'delete');		
	}

	public function toggleButtonStatusInAllConfigs($buttonData)
	{
		$this->_manageButtonInAllConfigs($buttonData, 'toggleStatus');
	}

	protected function _manageButtonInAllConfigs($buttonData, $mode)
	{
		$datasToKeep = array_flip(BBM_Helper_Bbm::getColumnsToKeepInRegistry());

		$buttonData = array_intersect_key($buttonData, $datasToKeep);
		$button_tag = $buttonData['tag'];

		$configs = $this->getAllConfigs();

		foreach ($configs as $config_id => $config)
		{
			//Only continue if the config was set & wasn't empty (for ie: user delete a default button before to have set a config)
			if(empty($config['config_buttons_full']) || empty($config['config_buttons_order']))
			{
				continue;
			}
			
			$config_type = $config['config_type'];
			$config_ed = $config['config_ed'];
			
      			//Prepare two main elements
			$order = explode(',', $config['config_buttons_order']);
      			$full = unserialize($config['config_buttons_full']);
      			
      			/***
      				Find the key inside the full configuration of the targeted button 
      				Info: the key is the position of the button and not its tag!
      			**/
      			foreach ($full as $buttonIndex => $selectedbutton)
      			{
				if(!isset($selectedbutton['tag']))
				{
					continue;
				}
				
      				if ($selectedbutton['tag'] == $button_tag) 
      				{
      					$buttonPositionInFull = $buttonIndex;
      					break;
      				}
      			}

      			if(!isset($buttonPositionInFull))
      			{
      				continue;
      			}
      			
      			//Modify the button according to the chosen mode
			if($mode == 'update')
			{
				if($buttonData['active'])
				{
					$buttonData['class'] = 'activeButton';
				}
				else
				{
					$buttonData['class'] = 'unactiveButton';			
				}

      				//Update button values in the config

      				$full[$buttonPositionInFull] = $buttonData;			
			}
			elseif($mode == 'delete')
			{
				$buttonPositionInOrder = array_search($button_tag, $order);
				
				unset($full[$buttonPositionInFull]);
				unset($order[$buttonPositionInOrder]);
			}
			elseif($mode == 'toggleStatus')
			{
      				if($buttonData['active'])
      				{
					$full[$buttonPositionInFull]['active'] = 0;
      					$full[$buttonPositionInFull]['class'] = 'unactiveButton';
      				}
      				else
      				{
					$full[$buttonPositionInFull]['active'] = 1;
      					$full[$buttonPositionInFull]['class'] = 'activeButton';      				
      				}
			}

			//Let's prepare the two main elements to be saved in the database
      			$full = serialize($full);
			$order = implode(',', $order);

			//Save			
			$dw = XenForo_DataWriter::create('BBM_DataWriter_Buttons');

			if ($this->getConfigById($config_id))
			{
				$dw->setExistingData($config_id);
			}

			$dw->set('config_type', $config_type);
			$dw->set('config_buttons_order', $order);
			$dw->set('config_buttons_full', $full);
      			$dw->save();
      
			//Update the Registry
			$this->InsertConfigInRegistry();
     		}
	}

	/**
	* Registry Functions (thanks to Jake Bunce ; http://xenforo.com/community/threads/ideal-way-for-cron-to-loop-through-all-users-over-several-runs.33600/#post-382901)
	*/
	public function InsertConfigInRegistry()
	{   
		//Registre structure:  bbm_buttons=>configEditor=>configType=>config
		$allConfigs = $this->getAllConfigs('config_type');
		$allConfigs = $this->_setEditorAsPrefixForConfigs($allConfigs);

		$config['bbm_buttons'] = $allConfigs;
		XenForo_Model::create('XenForo_Model_DataRegistry')->set('bbm_buttons', $config);
	}

	protected function _setEditorAsPrefixForConfigs($configs)
	{
		foreach($configs as $configType => $config)
		{
			$configEd = (empty($config['config_ed'])) ? 'mce' : $config['config_ed'];
			$configs[$configEd][$configType] = $config;
			unset($configs[$configType]);
		}
		
		return $configs;
	}

	public function CleanConfigInRegistry()
	{	  
		XenForo_Model::create('XenForo_Model_DataRegistry')->delete('bbm_buttons');
	}

	public function getEditorConfigsForForums($selected = 'disable')
	{
		return $this->getEditorConfigsForMobile($selected, false);
	}
	
	public function getEditorConfigsForMobile($selected, $tablets = false)
	{
		$configs = $this->getOnlyCustomConfigs();

		//Check if there is any bbm configs
		if(empty($configs))
		{
		      	$configs['disable'] = array(
		      			'value' => 'disable',
		      			'label' => new XenForo_Phrase('bbm_no_editor_available'),
		      			'selected' => true
		      	);
			return $configs;		
		}

		//Check if the system can detect tablets (on demand - see argument)		
		$visitor = XenForo_Visitor::getInstance();
		if( $tablets === true && (!class_exists('Sedo_DetectBrowser_Listener_Visitor') || !isset($visitor->getBrowser['isMobile'])))
            	{
			unset($configs);
		      	$configs['disable'] = array(
		      			'value' => 'disable',
		      			'label' => new XenForo_Phrase('bbm_mobilestyleselector_addon_not_installed'),
		      			'selected' => true
		      	);
			return $configs;
		}

	      	//Add disable option
	      	$configs['disable'] = array(
	      			'value' => 'disable',
	      			'label' => new XenForo_Phrase('bbm_disable'),
	      			'selected' => ($selected == 'disable')
	      	);

	      	//Add rtl_ltr option for tablets
	      	if($tablets === true)
	      	{
		      	$configs['transparent'] = array(
		      			'value' => 'rtl_ltr',
		      			'label' => new XenForo_Phrase('bbm_transparent'),
		      			'selected' => ($selected == 'transparent')
		      	);
		}
		
      		foreach ($configs AS $key => &$config)
      		{
			if(in_array($key, array('disable', 'transparent')))
			{
	      			continue;
			}

			$name = (isset($config['config_name'])) ? $config['config_name'] : $config['config_type'];

      			$config = array(
      				'value' => $config['config_type'],
      				'label' => $name,
      				'selected' => ($selected == $config['config_type'])
      			);
      		}

		return $configs;
	}	
}

//Zend_Debug::dump($configs);