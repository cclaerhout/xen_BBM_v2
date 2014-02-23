<?php
class BBM_BbCode_Formatter_Extensions_PreCacheBase extends XFCP_BBM_BbCode_Formatter_Extensions_PreCacheBase
{
	/****
	*	PRE CACHE FUNCTION
	***/
	protected $_bbmPreCacheActive = false;
	protected $_bbmPreCache = array();
	protected $_bbmPreCacheFirstExec = true;
	protected $_bbmExtraStatesDisablePreCache = array(
		'bbmContentProtection', 'disableBbmPreCache'
	);

	protected function _bbmInitPreCache($extraStates)
	{
		$extraStatesKeys = array_keys($extraStates);
		$processBbmPreCache = array_intersect($extraStatesKeys, $this->_bbmExtraStatesDisablePreCache);
		
		if(!XenForo_Application::get('options')->get('Bbm_PreCache_Enable') || !$this->_bbmPreCacheFirstExec)
		{
			$this->_bbmPreCacheActive = false;
			return;
		}
		
		if(empty($processBbmPreCache))
		{
			$this->_bbmPreCacheActive = true;
		}
		else
		{
			$this->_bbmPreCacheActive = false;		
		}
		
		//Important only execute once (otherwise the db requests will be duplicated)
		$this->_bbmPreCacheFirstExec = false;
	}

	public function bbmPreCacheActive()
	{
		return $this->_bbmPreCacheActive ;
	}

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

	//@extended
	public function renderTree(array $tree, array $extraStates = array())
	{
		$this->_bbmInitPreCache($extraStates);

		if($this->_bbmPreCacheActive)
		{
			$extraStates += array(
				'bbmPreCacheInit' => true
			);
			
			parent::renderTree($tree, $extraStates);
			unset($extraStates['bbmPreCacheInit']);
			XenForo_CodeEvent::fire('bbm_callback_precache', array(&$this->_bbmPreCache, &$extraStates));

			$extraStates['bbmPreCacheComplete'] = true;
		}
		
		return parent::renderTree($tree, $extraStates);
	}
}
//Zend_Debug::dump($abc);