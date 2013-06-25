<?php

class BBM_DataWriter_Buttons extends XenForo_DataWriter
{
	protected $_protectedTypes = array('rtl', 'ltr', 'disable', 'transparent', 'redactor');
	
	protected function _getFields() {
		return array(
			'bbm_buttons' => array(
				'config_id' 	=> array(
						'type' => self::TYPE_UINT,
				                'autoIncrement' => true
				),
				'config_ed' 	=> array(
						'type' => self::TYPE_STRING,
						'maxLength' => 200,
						'default' => 'mce'
				),				
				'config_type' 	=> array(
						'type' => self::TYPE_STRING,
						'required' => true,
						'maxLength' => 25,
						'verification' => array('$this', '_verifConfigType')
				),
				'config_name' 	=> array(
						'type' => self::TYPE_STRING,
						'maxLength' => 200
				),
				'config_buttons_order' 	=> array(
						'type' => self::TYPE_STRING, 
						'default' => ''
				),
				'config_buttons_full' => array(
						'type' => self::TYPE_STRING, 
						'default' => ''
				)
			)
		);
	}

	protected function _verifConfigType(&$config_type)
	{
		if (empty($config_type))
		{
			$this->error(new XenForo_Phrase('bbm_config_error_required'), 'config_id');
			return false;
		}

		$config_type = strtolower($config_type);

		if (preg_match('/[^a-zA-Z0-9_]/', $config_type))
		{
			$this->error(new XenForo_Phrase('bbm_please_enter_a_configtype_using_only_alphanumeric'), 'config_id');
			return false;
		}
		
		if(!$this->isUpdate() && in_array($config_type, $this->_protectedTypes))
		{
			$this->error(new XenForo_Phrase('bbm_config_type_protected'), 'config_id');
			return false;
		}

		if (!$this->isUpdate() && $this->_getButtonsModel()->getConfigByType($config_type))
		{
			$this->error(new XenForo_Phrase('bbm_config_type_must_be_unique'), 'config_id');
			return false;
		}

		return true;
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data, 'config_id'))
		{
			return false;
		}
		return array('bbm_buttons' => $this->_getButtonsModel()->getConfigById($id));
	}
	
	protected function _getUpdateCondition($tableName)
	{
		return 'config_id = ' . $this->_db->quote($this->getExisting('config_id'));
	}

	protected function _getButtonsModel()
	{
		return $this->getModelFromCache('BBM_Model_Buttons');
	}	
}