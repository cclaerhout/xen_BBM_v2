<?php

class BBM_Helper_BbCodes
{
	/***
	 * Regex for URL
	 **/
	public static $regexUrl = '#^(?:(?:https?|ftp|file)://|www\.|ftp\.)[-\p{L}0-9+&@\#/%=~_|$?!:,.]*[-\p{L}0-9+&@\#/%=~_|$]$#ui';

	/***
	 * Regex for COLOR - Doesn't work with color names (on purpose)
	 **/
	public static $colorRegex = '/^(rgb\(\s*\d+%?\s*,\s*\d+%?\s*,\s*\d+%?\s*\)|#[a-f0-9]{6}|#[a-f0-9]{3})$/i';

	/***
	 * Emulate white space
	 **/
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


	/***
	 * Clean option - to use for example in the options loop
	 **/
	public static function cleanOption($string, $strtolower = false)
	{
		if(XenForo_Application::get('options')->get('Bbm_ZenkakuConv'))
		{
			$string = mb_convert_kana($string, 'a', 'UTF-8');
		}
		
		if($strtolower == true)
		{
			$string = strtolower($string);
		}
		
		return $string;
	}

	/***
	 * Strip noscript tags (needed because noscript tags can't be nested)
	 **/
	public static function stripNoscript($html)
	{
		return preg_replace('#<noscript>.*?</noscript>#sui', '', $html);
	}

	/***
	 * Responsive RESS (Responsive Web Design with Server-Side Component) Checker
	 **/
	public static function useResponsiveMode()
	{
		$isResponsive = XenForo_Template_Helper_Core::styleProperty('enableResponsive');
		
		if(!$isResponsive)
		{
			return false;
		}
		
		return self::isMobile();
	}
	
	public static function isMobile($option = false)
	{
		$visitor = XenForo_Visitor::getInstance();
		
		if((!class_exists('Sedo_DetectBrowser_Listener_Visitor') || !isset($visitor->getBrowser['isMobile'])))
		{
			return XenForo_Visitor::isBrowsingWith('mobile');
		}
		else
		{
			//External addon
			if($option == 'onlyTablet')
			{
				return $visitor->getBrowser['isTablet'];
			}

			return $visitor->getBrowser['isMobile'];
		}
	}


	/***
	 * Check IE version (to do: update for IE11)
	 **/
	public static function isBadIE($isBadBelow = 9)
	{
		$goTo = $isBadBelow-1;

		$visitor = XenForo_Visitor::getInstance();
		if(isset($visitor->getBrowser['IEis']))
		{
			//Browser Detection (Mobile/MSIE) Addon
			if($visitor->getBrowser['isIE'] && $visitor->getBrowser['IEis'] < $isBadBelow)
			{
				return true;
			}
		}
		else
		{
			//Manual helper
			if(self::isIE('target', "6-$goTo"))
			{
				return true;
			}
		}
		
		return false;
	}

	//Direct regex check
	public static function isIE($method = false, $range = false)
	{
		if(!isset($_SERVER['HTTP_USER_AGENT']))
		{
			return false;
		}
		
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$output = false;

		if(preg_match('/(?i)msie/', $useragent))
       		{
			if($method == 'all')
			{
	       			$output = true;
	       		}
	       		elseif($method == 'target')
	       		{
	       			$first = $range[0];
	       			$last = substr($range, -1);
	       			$first_fix = $first - 4;
	       			$last_fix = $last - 4;
	       			
	       			if($first > 7 AND $last > 7)
	       			{
		       			if(preg_match('/(?i)Trident\/[' . $first_fix  . '-' . $last_fix  . ']/', $useragent))
       					{
      						$output = true;
	       				}	       			
	       			}
	       			elseif($first < 8 AND $last > 7)
	       			{
		       			if(preg_match('/(?i)Trident\/[4-' . $last_fix  . ']/', $useragent) OR preg_match('/(?i)msie [' . $first . '-7]/', $useragent))
       					{
      						$output = true;
	       				}	       			
	       			}
	       			elseif($last < 8)
	       			{
		       			if(preg_match('/(?i)msie [' . $first . '-' . $last . ']/', $useragent))
       					{
      						$output = true;
	       				}	       			
	       			}
	       		}
	       		else
	       		{
	       			if(preg_match('/(?i)Trident\/4/', $useragent) OR preg_match('/(?i)msie [1-7]/', $useragent))
       				{
       					//IE1 to IE8 width default option
      					$output = true;
	       			}
	       		}
       		}

       		return $output;
	}
}