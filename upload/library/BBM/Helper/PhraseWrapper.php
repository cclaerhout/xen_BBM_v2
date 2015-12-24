<?php

class BBM_Helper_PhraseWrapper
{
	protected $processed = false;
	protected $_text = '';
	protected $jsEscape = false;
	protected $phraseSearch = array();
	protected $phraseReplace = array();

	public function __construct($_text, $jsEscape = false)
	{
		$this->_text = $_text;
		$this->jsEscape = $jsEscape;

		if(preg_match_all('#{phrase:(.+?)}#i', $_text, $captures, PREG_SET_ORDER))
		{
			foreach($captures as $capture)
			{
				$this->phraseSearch[] = $capture[0];
				$this->phraseReplace[] = new XenForo_Phrase($capture[1]);
			}
		}
	}

	public function __toString()
	{
		try
		{
			if($this->processed)
			{
				return $this->_text;
			}
			$this->processed = true;

			if ($this->phraseSearch)
			{
				$this->_text = str_replace($this->phraseSearch, $this->phraseReplace, $this->_text);
			}

			if($this->jsEscape == true)
			{
				$this->_text = XenForo_Template_Helper_Core::jsEscape($this->_text);
			}

			return $this->_text;
		}
		catch (Exception $e)
		{
			XenForo_Error::logException($e, false, "BBM Phrases to string error:");
			return '';
		}
	}

}