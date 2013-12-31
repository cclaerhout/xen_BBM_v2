<?php

class BBM_Installer
{
	public static function install($addon)
	{
		$db = XenForo_Application::get('db');
		
		if(empty($addon))
		{
			//Force uninstall on fresh install
			self::uninstall();

			$db->query("CREATE TABLE IF NOT EXISTS bbm (             
			        		tag_id INT NOT NULL AUTO_INCREMENT,
      						tag varchar(30) NOT NULL,
      						title varchar(255) NOT NULL,
      						description varchar(255) DEFAULT NULL,
      						example TEXT NOT NULL,
						display_help INT(1) NOT NULL DEFAULT '1',
      						active INT(1) NOT NULL DEFAULT '1',      						
      						
      						start_range TEXT NULL DEFAULT NULL,
      						end_range TEXT NULL DEFAULT NULL,
      						options_number INT(11) NOT NULL DEFAULT '0',

						template_active INT(1) NOT NULL DEFAULT '0',
						template_name TEXT DEFAULT NULL,
						template_callback_class TINYTEXT NULL DEFAULT NULL,
						template_callback_method TINYTEXT NULL DEFAULT NULL,

      						phpcallback_class TINYTEXT NULL DEFAULT NULL,
      						phpcallback_method TINYTEXT NULL DEFAULT NULL,
     						
						stopAutoLink varchar(25) NOT NULL DEFAULT 'none',
      						regex varchar(255) DEFAULT NULL,
      						trimLeadingLinesAfter INT(1) NOT NULL DEFAULT '0',
      						plainCallback INT(1) NOT NULL DEFAULT '0',
      						plainChildren INT(1) NOT NULL DEFAULT '0',
      						stopSmilies INT(1) NOT NULL DEFAULT '0',
      						stopLineBreakConversion INT(1) NOT NULL DEFAULT '0',
						wrapping_tag varchar(30) NOT NULL DEFAULT 'none',
						wrapping_option TEXT DEFAULT NULL,
						parseOptions INT(1) NOT NULL DEFAULT '0',
						emptyContent_check INT(1) NOT NULL DEFAULT '1',      						

						parser_has_usr INT(1) NOT NULL DEFAULT '0',
						parser_usr TEXT DEFAULT NULL,
						parser_return  varchar(25) NOT NULL DEFAULT 'blank',
						parser_return_delay INT(11) NOT NULL DEFAULT '0',

						view_has_usr INT(1) NOT NULL DEFAULT '0',
						view_usr TEXT DEFAULT NULL,
						view_return varchar(25) NOT NULL DEFAULT 'blank',
						view_return_delay INT(11) NOT NULL DEFAULT '0',

      						hasButton INT(1) NOT NULL DEFAULT '0',
						button_has_usr INT(1) NOT NULL DEFAULT '0',
						button_usr TEXT DEFAULT NULL,
						killCmd INT(1) NOT NULL DEFAULT '0',
						custCmd varchar(50) DEFAULT NULL,
						imgMethod varchar(20) DEFAULT NULL,
						buttonDesc TINYTEXT DEFAULT NULL,
						tagOptions TINYTEXT DEFAULT NULL,
						tagContent TINYTEXT DEFAULT NULL,

						PRIMARY KEY (tag_id)
					)
		                	ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;"
			);
			
			$db->query("CREATE TABLE IF NOT EXISTS bbm_buttons (             
			        		config_id INT(200) NOT NULL AUTO_INCREMENT,
						config_type TINYTEXT NOT NULL,
						config_name TINYTEXT NOT NULL,
						config_buttons_order TEXT NOT NULL,
						config_buttons_full MEDIUMTEXT NOT NULL,
						PRIMARY KEY (config_id)
					)
		                	ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;"
			);

			$db->query("INSERT INTO bbm_buttons (config_id, config_type, config_name, config_buttons_order, config_buttons_full) VALUES (1, 'ltr', 'ltr', '', ''), (2, 'rtl', 'rtl', '', '');");

			self::addColumnIfNotExist($db, 'xf_forum', 'bbm_bm_editor', "varchar(25) NOT NULL DEFAULT 'disable'");
		}

		if(empty($addon) || $addon['version_id'] < 4)
		{
			self::addColumnIfNotExist($db, 'bbm', 'options_separator', "varchar(6) DEFAULT NULL");
		}

		if(empty($addon) || $addon['version_id'] < 4)
		{
			self::addColumnIfNotExist($db, 'bbm', 'options_separator', "varchar(6) DEFAULT NULL");
		}

		if(empty($addon) || $addon['version_id'] < 10)
		{
			self::addColumnIfNotExist($db, 'bbm_buttons', 'config_name', "TINYTEXT DEFAULT NULL");

			self::addColumnIfNotExist($db, 'bbm', 'quattro_button_type', "varchar(55) DEFAULT NULL");
			self::addColumnIfNotExist($db, 'bbm', 'quattro_button_type_opt', "varchar(155) DEFAULT NULL");
			self::addColumnIfNotExist($db, 'bbm', 'quattro_button_return', "varchar(55) DEFAULT NULL");
			self::addColumnIfNotExist($db, 'bbm', 'quattro_button_return_opt', "varchar(155) DEFAULT NULL");
		}

		if(empty($addon) || $addon['version_id'] < 14)
		{
			self::addColumnIfNotExist($db, 'bbm_buttons', 'config_ed', "varchar(25) NOT NULL DEFAULT 'mce'");
			$db->query("INSERT INTO bbm_buttons (config_ed, config_type, config_name, config_buttons_order, config_buttons_full) VALUES ('xen', 'redactor', 'redactor', '', '');");	
			
			if(!empty($addon))
			{
				//Update Registry - first key have changed
				$configs = XenForo_Model::create('BBM_Model_Buttons')->InsertConfigInRegistry();
			}		
		}

		if(empty($addon) || $addon['version_id'] < 23)
		{
			self::addColumnIfNotExist($db, 'bbm', 'redactor_has_icon', "INT(1) NOT NULL DEFAULT '0'");
			self::addColumnIfNotExist($db, 'bbm', 'redactor_sprite_mode', "INT(1) NOT NULL DEFAULT '0'");
			self::addColumnIfNotExist($db, 'bbm', 'redactor_sprite_params_x', "INT(11) NOT NULL DEFAULT '0'");
			self::addColumnIfNotExist($db, 'bbm', 'redactor_sprite_params_y', "INT(11) NOT NULL DEFAULT '0'");
			self::addColumnIfNotExist($db, 'bbm', 'redactor_image_url', "TINYTEXT NULL DEFAULT NULL");		
		}

		if(empty($addon) || $addon['version_id'] < 27)
		{
			$db->query("UPDATE bbm_buttons SET config_name = 'Quattro RTL' WHERE bbm_buttons.config_type = 'rtl'");
			$db->query("UPDATE bbm_buttons SET config_name = 'Quattro LTR' WHERE bbm_buttons.config_type = 'ltr'");			
		}

		if(empty($addon) || $addon['version_id'] < 40)
		{
			self::addColumnIfNotExist($db, 'bbm', 'bbcode_id', "TINYTEXT NULL DEFAULT NULL");
			self::addColumnIfNotExist($db, 'bbm', 'bbcode_addon', "TINYTEXT NULL DEFAULT NULL");
		}

		//Generate simple cache (users don't need anymore to edit a bbcode and save it (without operating any change) to activate the Simple Cache
		XenForo_Model::create('BBM_Model_BbCodes')->simplecachedActiveBbCodes(); 
	}
	
	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("DROP TABLE IF EXISTS bbm");
		$db->query("DROP TABLE IF EXISTS bbm_buttons");

		if ($db->fetchRow('SHOW COLUMNS FROM xf_forum WHERE Field = ?', 'bbm_bm_editor'))
		{
			$db->query("ALTER TABLE xf_forum DROP bbm_bm_editor");
		}

		XenForo_Model::create('XenForo_Model_DataRegistry')->delete('bbm_buttons');
		XenForo_Application::setSimpleCacheData('bbm_active', false);
	}
	
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
		if ($db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}
	 
		return $db->query("ALTER TABLE $table ADD $field $attr");
	}
	
	public static function changeColumnValueIfExist($db, $table, $field, $attr)
	{
		if (!$db->fetchRow("SHOW COLUMNS FROM $table WHERE Field = ?", $field))
		{
			return;
		}

		return $db->query("ALTER TABLE $table CHANGE $field $field $attr");
	}
}