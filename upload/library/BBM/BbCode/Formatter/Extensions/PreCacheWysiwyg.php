<?php
class BBM_BbCode_Formatter_Extensions_PreCacheWysiwyg extends XFCP_BBM_BbCode_Formatter_Extensions_PreCacheWysiwyg
{
	/****
	*	PRE CACHE FUNCTION
	***/
	protected $_bbmTextView = '';
	
	protected $_bbmPreCacheActive = false;
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

	protected $_bbmPreCacheDone = false;

	//@extended
	public function renderTree(array $tree, array $extraStates = array())
	{
		if(XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
		{
			if(!empty($extraStates['bbmPreCacheInit']) && !$this->_bbmPreCacheDone)
			{
				parent::renderTree($tree, $extraStates);
				unset($extraStates['bbmPreCacheInit']);

				XenForo_CodeEvent::fire('bbm_callback_precache', array(&$this->_bbmPreCache, &$extraStates, 'wysiwyg'));
				XenForo_Application::set('bbm_preCache_wysiwyg', array($this->_bbmPreCache, $extraStates));

				$this->_bbmPreCacheDone = true;

				return '';
			}

			if (XenForo_Application::isRegistered('bbm_preCache_wysiwyg'))
			{
				list($_bbmPreCache, $_extraStates) = XenForo_Application::get('bbm_preCache_wysiwyg');
				$this->_bbmPreCache = $_bbmPreCache;
				$extraStates['bbmPreCacheComplete'] = true;
				$extraStates += $_extraStates;
			}
		}
		
		return parent::renderTree($tree, $extraStates);
	}

	//@extended
	public function renderValidTag(array $tagInfo, array $tag, array $rendererStates)
	{
		//Need to call the parent in both cases - reason: the bbm post params management is done trough this function
		$parent = parent::renderValidTag($tagInfo, $tag, $rendererStates);
		$tagName = $tag['tag'];
		
		if(!empty($rendererStates['bbmPreCacheInit']) && !$this->preParserEnableFor($tagName) )
		{
			return '';
		}
		else
		{
			return $parent;
		}
	}
	
	//@extended
	public function setView(XenForo_View $view = null)
	{
		parent::setView($view);

		if ($view && XenForo_Application::get('options')->get('Bbm_PreCache_Enable'))
		{
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
				//to do later
			}
			
			if(!empty($data))
			{
				if(!is_array($data))
				{
					$data = array($data);
				}

				if(!$multiMode)
				{
					foreach($data as $key => $value)
					{
						if(!in_array($key, $keys) || !is_string($value))
						{
							continue;
						}
					
						$text .= $value;
					}
				}
				else
				{
					foreach($data as $multi)
					{
						foreach($multi as $key => $value)
						{
							if(!in_array($key, $keys) || !is_string($value))
							{
								continue;
							}
						
							$text .= $value;
						}					
					}
				}
			}
			
			$this->_bbmTextView = $text;

			if(!empty($text))
			{
				$parser = $this->getParser();
				$parser->render($text, array('bbmPreCacheInit' => true));
			}
		}
	}	
}
//Zend_Debug::dump($abc);