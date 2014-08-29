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
	 * Get picture source
	 * http://css-tricks.com/snippets/html/base64-encode-of-1x1px-transparent-gif/
	 **/
	public static function getEmptyImageSource($base64 = true, $fallback = 'styles/default/xenforo/clear.png')
	{
		if($base64)
		{
			return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEHAAEALAAAAAABAAEAAAICTAEAOw==';
		}
		
		return $fallback;
	}

	/***
	 * Get special tags ({tag}...{/tag} from content
	 **/
	public static function getSpecialTags($content, array $tags = array('slide'), $getContentBetweenTags = false)
	{
		$tags = implode('|', $tags);
		$count = preg_match_all(
			'#{(?P<tag>'.$tags.')(=(?P<option>\[([\w\d]+)(?:=.+?)?\].+?\[/\4\]|[^{}]+)+?)?}'.
			'(?P<catchup>(?:</[^>]+>\s*)*)?(?P<content>.*?)'.
			'{/\1}(?!(?:\W+)?{/\1})(?P<outside>.*?)(?={\1(?:=[^}]*)?}|$)#is', 
			$content,
			$matches,
			PREG_SET_ORDER
		);
		
		/* Prevent html breaks */
		for(; $count > 0; $count--)
		{
			$k = $count-1;
			$content = &$matches[$k]['content'];
			
			/*
			$catchup = false; //$matches[$k]['catchup'];

			//Catchup lost closing tags at the begin of n+1 special tag
			if($catchup && isset($matches[$k-1]))
			{
				$previousContent = &$matches[$k-1]['content'];
				if(preg_match('#^.*<br />$#s', $previousContent))
				{
					$previousContent = preg_replace('#^(.*)<br />$#s', "$1{$catchup}<br />", $previousContent);
				}
				else
				{
					$previousContent .= $catchup;
				}
			}
			*/
			
			/*Between special tags management*/
			$extraData = $matches[$k]['outside'];
			$extraDataCheck = str_replace('<br />', '', $extraData);

			if(empty($extraDataCheck))
			{
				continue;
			}
			
			if(!$getContentBetweenTags)
			{
				$extraData = $extraDataCheck;
				$contentToErase = preg_split('#[\s]*<?[^>]+?>[\s]*#', $extraData);

				foreach($contentToErase as $text)
				{
					if($text)
					{
						$extraData = str_replace($text, '', $extraData);
					}
				}
			}
				
			$content .= $extraData;
			$content = self::tidyHTML($content);
			//$content = self::tidyHTML($content, true);
		}

		return $matches;
	}

	/***
	 * Emulate white space
	 **/
	public function emulateAllWhiteSpace($string)
	{
		return preg_replace_callback(
			//The below regew will match whitespaces (start from 2 and exclude the last one of a line) + exclude match if a ending html tag is detected
			'#[ ]{2,}+(?<! $)(?![^<]*?>)#', 
			array($this, '_emulateAllWhiteSpaceRegexCallback'), 
			$string
		);
	}
	
	protected function _emulateAllWhiteSpaceRegexCallback($matches)
	{
		$breaksX = substr_count($matches[0], " ");
		$breakPattern = '&nbsp;'; //other possible UTF8 solutions = http://www.cs.tut.fi/~jkorpela/chars/spaces.html
		$breakOutput = str_repeat($breakPattern, $breaksX);
					
		return "{$breakOutput}";
	}

	//Be sure to use the below function in the content of an html tag (without any other html tags - will improve this later)
	public function emulateWhiteSpaceTabs($string)
	{
		return preg_replace_callback(
			'#[\t]+#', 
			array($this, '_emulateWhiteSpaceTabsRegexCallback'), 
			$string
		);
	}
	
	protected function _emulateWhiteSpaceTabsRegexCallback($matches)
	{
		$breaksX = substr_count($matches[0], "\t");
		$breakPattern = '    ';
		$breakOutput = str_repeat($breakPattern, $breaksX);
					
		return "<span style='white-space:pre'>{$breakOutput}</span>";	
	}

	/***
	 * Clean option - to use for example in the options loop
	 **/
	public static function cleanOption($string, $strtolower = false, $trim = true)
	{
		if(XenForo_Application::get('options')->get('Bbm_ZenkakuConv'))
		{
			$string = mb_convert_kana($string, 'a', 'UTF-8');
		}
		
		if($strtolower == true)
		{
			$string = strtolower($string);
		}
		
		if(!$trim)
		{
			return $string;
		}
		
		return trim($string);
	}

	/***
	 * Strip noscript tags (needed because noscript tags can't be nested)
	 **/
	public static function stripNoscript($html)
	{
		return preg_replace('#<noscript>.*?</noscript>#sui', '', $html);
	}


	/***
	 * Strip Bb Codes
	 **/
	public static function stripBbCodes($string, $stripHtmlTags = false, $regexMethod = true)
	{
		if($stripHtmlTags)
		{
			$string = strip_tags($string);
		}

		if($regexMethod)
		{
			//Seems to use less memory
			return XenForo_Helper_String::bbCodeStrip($string, true);
		}

		$formatter = XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_Strip', false);
		$formatter->stripAllBbCode(true, 0);
		$parser = XenForo_BbCode_Parser::create($formatter);

		return $parser->render($string);
	}

	/***
	 * Get a color with and only with the hexa format (needed for Microsoft DX Filters)
	 * Code source: http://forum.codecall.net/php-tutorials/22589-rgb-hex-colors-hex-colors-rgb-php.html
	 **/
	public static function getHexaColor($color, $fallback = 'FFFFFF', $prefix = '#')
	{
		if(in_array($color, array('transparent', 'none')))
		{
			return  $prefix.$fallback;
		}

		$color = XenForo_Helper_Color::unRgba($color);

		if(preg_match('#^rgb\((?P<r>\d{1,3}).+?(?P<g>\d{1,3}).+?(?P<b>\d{1,3})\)$#i', $color, $rgb))
		{
			$color = str_pad(dechex($rgb['r']), 2, "0", STR_PAD_LEFT);
			$color .= str_pad(dechex($rgb['g']), 2, "0", STR_PAD_LEFT);
			$color .= str_pad(dechex($rgb['b']), 2, "0", STR_PAD_LEFT);
			
			//$color = sprintf("%x", ($rgb['r'] << 16) + ($rgb['g'] << 8) + $rgb['b']); // Not accurate if red starts with 0
		}

		if(!empty($color[0]) && $color[0] == '#')
		{
			$color = substr($color, 1);
		}

		return $prefix.$color;
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
       		elseif(strpos($useragent, 'like gecko') !== false && preg_match('#trident/(\d{1,2})\.(\d{1,2})#', $useragent, $match))
       		{
	       		//IE11
	       		if(intval($match[1]) == 7)
	       		{
	       			if($method == 'all' || ($method == 'target' && strpos($range, '11') !== false) )
				{
	       				$output = true;
	       			}
	       		}
       		}

       		return $output;
	}

	/***
	 * Check if an addon is installed
	 **/	
	public static function installedAddon($addonKey)
	{
		$isInstalled = false;
		
		if(XenForo_Application::get('options')->get('currentVersionId') >= 1020031)
		{
			$addons = XenForo_Application::get('addOns');

			if(isset($addons[$addonKey]))
			{
				$isInstalled = true;
			}
		}
		
		return $isInstalled;	
	}

	/***
	 * Tidy some html code using the php dom function or an external class
	 **/
	protected static $_htmlFixer;
	
	public static function tidyHTML($html, $useDOM = false)
	{
		if(!$html)  return $html;

		if(!$useDOM)
		{
			if(!self::$_htmlFixer)
			{
				self::$_htmlFixer = new BBM_Helper_HtmlFixer();
			}
			
			$htmlFixer = self::$_htmlFixer;
			return $htmlFixer->getFixedHtml($html);
		}
		
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$readyContent = self::_beforeLoadHtml($html);
		$doc->loadHTML('<?xml encoding="utf-8"?>' . $readyContent);
		libxml_clear_errors();
		$doc->encoding = 'utf-8';
		$doc->formatOutput = true;
		
		$doc->removeChild($doc->firstChild); //remove html tag
		$doc->removeChild($doc->firstChild); //remove xml fix
		$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild); //make wip tag content as first child		

		$html = $doc->saveHTML($doc->documentElement);
		$html = self::_afterSaveHtml($html);
		return $html;
	}

	protected static function _beforeLoadHtml($html)
	{
		$html = "<wip>{$html}</wip>";
		$html = self::_fixNpTagsRegex($html);
		return $html;
	}

	protected static function _afterSaveHtml($html)
	{
		$html = self::_fixNpTagsRegex($html, true);
		$html = preg_replace('#^\s*<wip>(.*)</wip>\s*$#si', '$1', $html);
		return $html;
	}

	protected static function _fixNpTagsRegex($html, $revertMode = false)
	{
		if(!$revertMode)
		{
			return preg_replace('#<(/?)(\w+):(\w+)( [^>]+)?>#i', '<$1$2-npfix-$3$4>', $html);
		}
		
		return preg_replace('#<(/?)(\w+)-npfix-(\w+)( [^>]+)?>#i', '<$1$2:$3$4>', $html);
	}
}
//Zend_Debug::dump($abc);