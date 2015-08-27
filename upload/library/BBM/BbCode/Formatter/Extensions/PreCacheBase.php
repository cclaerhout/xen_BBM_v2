<?php
class BBM_BbCode_Formatter_Extensions_PreCacheBase extends XFCP_BBM_BbCode_Formatter_Extensions_PreCacheBase
{
	/****
	*	PRE CACHE FUNCTION
	***/
	protected $_bbmTextView = '';

	protected $_bbmPreCache = array();

	public function getBbmPreCache()
	{
		return $this->_bbmPreCache;
	}

	public function getBbmPreCacheData($dataKey)
	{
		if(isset($this->_bbmPreCache[$dataKey]))
		{
			return $this->_bbmPreCache[$dataKey];
		}

		return null;
	}

	public function setBbmPreCacheData($dataKey, $dataValue)
	{
		$this->_bbmPreCache[$dataKey] = $dataValue;
	}

	public function pushBbmPreCacheData($dataKey, $dataValue)
	{
		if(!isset($this->_bbmPreCache[$dataKey]))
		{
			$this->_bbmPreCache[$dataKey] = array($dataValue);
		}
		elseif(!is_array($this->_bbmPreCache[$dataKey]))
		{
			$this->_bbmPreCache[$dataKey] = array($this->_bbmPreCache[$dataKey]);
			array_push($this->_bbmPreCache[$dataKey], $dataValue);
		}
		else
		{
			array_push($this->_bbmPreCache[$dataKey], $dataValue);
		}
	}

	protected $bbm_preCache_base = null;

	//@extended
	public function renderTree(array $tree, array $extraStates = array())
	{
		if(!empty($extraStates['bbmPreCacheInit']))
		{
			parent::renderTree($tree, $extraStates);
			return '';
		}
		else if ($this->bbm_preCache_base !== null)
		{
			list($_bbmPreCache, $_extraStates) = $this->bbm_preCache_base;
			$this->_bbmPreCache = $_bbmPreCache;
			$extraStates['bbmPreCacheComplete'] = true;
			$extraStates += $_extraStates;
		}

		return parent::renderTree($tree, $extraStates);
	}

	protected $bbm_preCache_tags = null;

	protected function _getOriginalTagRule($tagName)
	{
		$tagName = strtolower($tagName);

		if (!empty($this->bbm_preCache_tags[$tagName]) && is_array($this->bbm_preCache_tags[$tagName]))
		{
			return $this->bbm_preCache_tags[$tagName];
		}
		else
		{
			return array();
		}
	}

	//@extended
	public function incrementTagMap(array $tagInfo, array $tag, array $rendererStates)
	{
		if (empty($tagInfo))
		{
			$tagInfo = $this->_getOriginalTagRule($tag['tag']);
		}
		return parent::incrementTagMap($tagInfo, $tag, $rendererStates);
	}

	//@extended
	public function renderValidTag(array $tagInfo, array $tag, array $rendererStates)
	{
		//Need to call the parent in both cases - reason: the bbm post params management is done trough this function
		$parent = parent::renderValidTag($tagInfo, $tag, $rendererStates);

		if(empty($rendererStates['bbmPreCacheInit']))
		{
			return $parent;
		}
		return '';
	}

	//@extended
	public function renderString($string, array $rendererStates, &$trimLeadingLines)
	{
		if(empty($rendererStates['bbmPreCacheInit']))
		{
			return parent::renderString($string, $rendererStates, $trimLeadingLines);
		}
		return '';
	}

	//@extended
	public function renderTagUnparsed(array $tag, array $rendererStates)
	{
		if(empty($rendererStates['bbmPreCacheInit']))
		{
			return parent::renderTagUnparsed($tag, $rendererStates);
		}
		$this->renderSubTree($tag['children'], $rendererStates);
		return '';
	}

	protected function sanitizeTagsForPreParse(array $tags)
	{
		$threshold = XenForo_Application::get('options')->get('Bbm_PreCache_Threshold');
		foreach($tags as $tagName => &$tag)
		{
			if (!$this->preParserEnableFor($tagName))
			{
				unset($tags[$tagName]);
			}
			// verify the pre-parse tags are even used.
			else if ($this->_bbCodesMap != null && (empty($this->_bbCodesMap[$tagName]) || count($this->_bbCodesMap[$tagName]) < $threshold))
			{
				unset($tags[$tagName]);
			}
		}
		return $tags;
	}

	//@extended
	public function setView(XenForo_View $view = null)
	{
		$this->_cacheBbcodeTree = true;
		parent::setView($view);

		if ($view && XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
		{
			// check if there are any tags with preParser enabled
			$this->bbm_preCache_tags = $this->_tags;
			$sanitizedTags = $this->sanitizeTagsForPreParse($this->bbm_preCache_tags);

			if (empty($sanitizedTags))
			{
				return;
			}

			/**
			 * Purpose: get back the original text and parse it will a special rendererState
			 * It will manage inside the renderTree function (global init), then in the
			 * renderValidTag function (tag init)
			 **/

			$params = $view->getParams();

			$text = '';
			$data = false;
			$keys = array();
			$multiMode = false;

			/**
			 * For posts: check thread & posts
			 **/
			if(	isset($params['posts']) && is_array($params['posts']) && isset($params['thread'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['posts'];
				$keys = array('message', 'signature');
				$multiMode = true;
			}

			/**
			 * For conversations: check conversation & messages
			 * It's not perfect, but let's use the same functions than thread & posts
			 **/
			if(	isset($params['messages']) && is_array($params['messages']) && isset($params['conversation'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['messages'];
				$keys = array('message', 'signature');
				$multiMode = true;
			}

			/**
			 * For RM (resource & category)
			 **/
			if(	isset($params['resource']) && is_array($params['resource']) && isset($params['category'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['category'];
				$keys = array('message');
				$multiMode = false;
			}

			/**
			 * For Custom Addons
			 **/
			if(isset($params['bbm_config']))
			{
				$config = $params['bbm_config'];

				if(empty($config['viewParamsMainKey']))
				{
					Zend_Debug::dump('You must set a Main Key !');
					return;
				}

				$mainKey = $config['viewParamsMainKey'];

				if(!isset($params[$mainKey]))
				{
					Zend_Debug::dump('The main key must be valid !');
					return;
				}

				$data = $params[$mainKey];
				$multiMode = (isset($config['multiPostsMode'])) ? $config['multiPostsMode'] : true;

				$targetedKey = false;
				if(	!empty($config['viewParamsTargetedKey'])
					&& $config['viewParamsTargetedKey'] != $config['viewParamsMainKey']
					&& is_string($config['viewParamsTargetedKey'])
				){
					$targetedKey = $config['viewParamsTargetedKey'];
				}

				if($multiMode)
				{
					$keys = array();

					if(!empty($config['messageKey']) && is_string($config['messageKey']))
					{
						$messageKey = $config['messageKey'];
					}

					if(!empty($config['extraKeys']) && is_array($config['extraKeys']))
					{
						$keys = $config['extraKeys'];
					}

					array_unshift($keys, $messageKey);
				}
				else
				{
					if($targetedKey)
					{
						$keys = array($targetedKey);
					}
					else
					{
						$data = $params;
						$keys = $mainKey;
					}
				}
			}

			$trees = array();
			$parser = $this->getParser();
			if(!empty($data))
			{
				if(!is_array($data))
				{
					$data = array($data);
				}

				$parsedKeySuffix = $this->_bbmPostfixParsedKey;

				foreach($data as $key => $data)
				{
					foreach($keys as $index)
					{
						if(!isset($data[$index]) || !is_string($data[$index]))
						{
							continue;
						}

						$BbCodesTree = null;
						if (isset($this->_parseCache[$key.$index]))
						{
							$BbCodesTree = $this->_parseCache[$key.$index];
						}

						if (!$BbCodesTree && isset($data[$index . $parsedKeySuffix]))
						{
							$BbCodesTree = @unserialize($data[$index . $parsedKeySuffix]);
						}

						if (!$BbCodesTree)
						{
							$BbCodesTree = $parser->parse($data[$index]);
						}
						$trees[] = $BbCodesTree;
					}
				}
			}

			// Optimize a bunch of known bbcodes to be near no-ops
			$this->_tags = $sanitizedTags;
			$_smilieTranslate = $this->_smilieTranslate;
			$this->_smilieTranslate = array();
			foreach($trees as $BbCodesTree)
			{
				$this->renderTree($BbCodesTree, array('bbmPreCacheInit' => true));
			}
			$this->_tags = $this->bbm_preCache_tags;
			$this->_smilieTranslate = $_smilieTranslate;
			$extraStates = array();
			XenForo_CodeEvent::fire('bbm_callback_precache', array(&$this->_bbmPreCache, &$extraStates, 'base'));
			$this->bbm_preCache_base = array($this->_bbmPreCache, $extraStates);

			/*Bb Codes MAP*/
			$this->_resetIncrementation();
		}
	}
}
//Zend_Debug::dump($abc);