<?php
class BBM_BbCode_Formatter_Extensions_PreCacheWysiwyg extends XFCP_BBM_BbCode_Formatter_Extensions_PreCacheWysiwyg
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
	protected $_bbmPostfixParsedKey = '_parsed';

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
		foreach($tags as $tagName => &$tag)
		{
			if (!$this->preParserEnableFor($tagName))
			{
				unset($tags[$tagName]);
			}
		}
		return $tags;
	}

	//@extended
	public function setView(XenForo_View $view = null)
	{
		parent::setView($view);

		if ($view && XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
		{
			// check if there are any tags with preParser enabled
			$_tags = $this->_tags;
			$sanitizedTags = $this->sanitizeTagsForPreParse($_tags);
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
			 *  For posts: check thread & posts
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
			 *  For post edit
			 **/
			if(	isset($params['post']) && is_array($params['post']) && isset($params['thread'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['post'];
				$keys = array('message');
				$multiMode = false;
			}

			/**
			 *  For conversations: check conversation & messages
			 *  It's not perfect, but let's use the same functions than thread & posts
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
			 *  For conversation edit
			 **/
			if(	isset($params['conversationMessage']) && is_array($params['conversationMessage']) && isset($params['conversation'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['conversationMessage'];
				$keys = array('message');
				$multiMode = false;
			}

			/**
			 *  For RM (resource & category)
			 **/
			if(	isset($params['resource']) && is_array($params['resource']) &&  isset($params['category'])
				&& !isset($params['bbm_config'])
			)
			{
				$data = $params['category'];
				$keys = array('message');
				$multiMode = false;
			}

			/**
			 *  For Custom Addons
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
						if (isset($data[$index . $parsedKeySuffix]))
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

			// optimize a bunch of known bbcodes to be near no-ops
			$this->_tags = $sanitizedTags;
			foreach($trees as $BbCodesTree)
			{
				$this->renderTree($BbCodesTree, array('bbmPreCacheInit' => true));
			}
			$this->_tags = $_tags;
			$extraStates = array();
			XenForo_CodeEvent::fire('bbm_callback_precache', array(&$this->_bbmPreCache, &$extraStates, 'wysiwyg'));
			$this->bbm_preCache_base = array($this->_bbmPreCache, $extraStates);
		}
	}
}
//Zend_Debug::dump($abc);