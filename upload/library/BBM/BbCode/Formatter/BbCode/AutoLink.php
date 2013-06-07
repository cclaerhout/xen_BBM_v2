<?php

class BBM_BbCode_Formatter_BbCode_AutoLink extends XFCP_BBM_BbCode_Formatter_BbCode_AutoLink
{
	protected $_fixBbmTags = null;

	public function getTags()
	{
		$parentTags = parent::getTags();
			
		if($this->_fixBbmTags === null)
		{
			$this->_bakeBbmTags();
		}
		
		if($this->_fixBbmTags !== null)
		{
			$tags = array_merge((array) $parentTags, (array) $this->_fixBbmTags);
			
			foreach ($tags AS $tagName => &$tag)
			{
				unset($tag['replace'], $tag['callback'], $tag['trimLeadingLinesAfter']);
				if (!empty($this->_overrideCallbacks[$tagName]))
				{
					$override = $this->_overrideCallbacks[$tagName];
					if (is_array($override) && $override[0] == '$this')
					{
						$override[0] = $this;
					}
	
					$tag['callback'] = $override;
				}
				else if ($this->_generalTagCallback)
				{
					$tag['callback'] = $this->_generalTagCallback;
				}
			}

			$this->_tags = $tags;
			return $tags;
		}

		return $parentTags;
	}


	public function _bakeBbmTags()
	{
		$bbmTags = XenForo_Model::create('BBM_Model_BbCodes')->getAllBbCodes('strict');

		if(!is_array($bbmTags))
		{
			return false;
		}

		$allBbmTags = array();

		foreach($bbmTags AS $bbm)
		{
			if((boolean)$bbm['active'])
			{
				$allBbmTags[$bbm['tag']]['active'] = true;
				
				if($bbm['stopAutoLink'] != 'none')
				{
					$allBbmTags[$bbm['tag']]['stopAutoLink'] = true;
					$allBbmTags[$bbm['tag']]['stopAutoLinkMethod'] = $bbm['stopAutoLink'];	
				}

	      			if($bbm['plainChildren'])
	      			{
	      				$allBbmTags[$bbm['tag']]['plainChildren'] = true;
	      			}
	      			
	      			if($bbm['stopSmilies'])
	      			{
	      				$allBbmTags[$bbm['tag']]['stopSmilies'] = true;
	      			}
	      			
	      			if($bbm['stopLineBreakConversion'])
	      			{
	      				$allBbmTags[$bbm['tag']]['stopLineBreakConversion'] = true;
	      			}
	      		}
		}
		 $this->_fixBbmTags = $allBbmTags;
	}

	public function autoLinkTag(array $tag, array $rendererStates)
	{
		if (is_array($this->_fixBbmTags)
		&& isset($this->_fixBbmTags[$tag['tag']]['stopAutoLink'])
		&& $this->_fixBbmTags[$tag['tag']]['stopAutoLink'] === true
		)
		{
			$rendererStates['stopAutoLink'] = true;

			if(isset($tag['children'][0])
			&& is_string($tag['children'][0])
			&& $this->_fixBbmTags[$tag['tag']]['stopAutoLinkMethod'] == 'strict'
			&& !$this->isOnlyUrl($tag['children'][0]))
			{
				$rendererStates['stopAutoLink'] = false;
			}
		}

		return parent::autoLinkTag($tag, $rendererStates);
	}	

	public function isOnlyUrl($string)
	{

		$regex_url = '#^(?:(?:https?|ftp|file)://|www\.|ftp\.)[-\p{L}0-9+&@\#/%=~_|$?!:,.]*[-\p{L}0-9+&@\#/%=~_|$]$#ui';
		
		if(preg_match($regex_url, $string))
		{
			return true;
		}
		
		return false;
	}
}