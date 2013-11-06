<?php

class BBM_Helper_BbCodes
{
	public static function emulateWhiteSpace($string)
	{
		return preg_replace_callback(
			'#[\t]+#', 
			array('BBM_Helper_BbCodes', '_emulateWhiteSpaceRegexCallback'), 
			$string
		);
	}
	
	protected static function _emulateWhiteSpaceRegexCallback($matches)
	{
		$breaksX = substr_count($matches[0], "\t");
		$breakPattern = '    ';
		$breakOutput = str_repeat($breakPattern, $breaksX);
					
		return "<span style='white-space:pre'>{$breakOutput}</span>";	
	}
}