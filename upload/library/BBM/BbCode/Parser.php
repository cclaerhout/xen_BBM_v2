<?php
class BBM_BbCode_Parser extends XFCP_BBM_BbCode_Parser
{
	//This function will override the XenForo one. It's not really possible to extend it
	protected function _parseTag()
	{
		$tagStartPosition = strpos($this->_text, '[', $this->_position);
		if ($tagStartPosition === false)
		{
			return false;
		}

		$bbCodesOptionsPattern = '#\[(?:/)?[\w\d]+?(?:=(\[([\w\d]+?)(?:=.+?)?\].+?\[/\2\]|[^\[\]])+?)?(?P<closingBracket>\])#iu';
		
		if(	preg_match(
				$bbCodesOptionsPattern, $this->_text, 
				$matches, 
				PREG_OFFSET_CAPTURE,
				$tagStartPosition
			) 
			&& 
			isset($matches['closingBracket'][1]))
		{
			$tagContentEndPosition = $matches['closingBracket'][1];
		}
		else
		{
			$tagContentEndPosition = false;
		}

		if ($tagContentEndPosition === false)
		{
			return false;
		}

		$tagEndPosition = $tagContentEndPosition + 1;

		if ($tagStartPosition != $this->_position)
		{
			$this->_pushText(substr($this->_text, $this->_position, $tagStartPosition - $this->_position));
			$this->_position = $tagStartPosition;
		}

		if ($this->_text[$tagStartPosition + 1] == '/')
		{
			$success = $this->_parseTagClose($tagStartPosition, $tagEndPosition, $tagContentEndPosition);
		}
		else
		{
			$success = $this->_parseTagOpen($tagStartPosition, $tagEndPosition, $tagContentEndPosition);
		}

		if ($success)
		{
			// successful parse, eat the whole tag
			$this->_position = $tagEndPosition;
		}
		else
		{
			// didn't parse the tag properly, eat the first char ([) and try again
			$this->_pushText($this->_text[$tagStartPosition]);
			$this->_position++;
		}

		return true;
	}
}