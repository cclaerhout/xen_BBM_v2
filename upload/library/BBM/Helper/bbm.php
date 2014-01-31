<?php

class BBM_Helper_Bbm
{
	public static function callbackChecker($class, $method)
	{
		if(!empty($method))
		{
			return (class_exists($class) && method_exists($class, $method));
		}
		
		return class_exists($class);
	}
	
	public static function getColumnsToKeepInRegistry()
	{
		return array('tag_id', 'bbcode_id', 'tag', 'active', 'hasButton',
			'button_has_usr', 'button_usr', 'killCmd', 'custCmd',
			'buttonDesc', 'tagOptions', 'tagContent', 'options_separator',
			'quattro_button_type', 'quattro_button_type_opt', 'quattro_button_return', 'quattro_button_return_opt',
			'redactor_has_icon', 'redactor_sprite_mode', 'redactor_image_url', 'redactor_sprite_params_x', 'redactor_sprite_params_y'
		);
	}
}