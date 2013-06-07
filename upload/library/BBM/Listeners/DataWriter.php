<?php

class BBM_Listeners_DataWriter
{
	public static function DataWriterAdmin($class, array &$extend)
	{
		if ($class == 'XenForo_DataWriter_Forum' && XenForo_Application::get('options')->get('Bbm_Bm_Forum_Config'))
	      	{
      			$extend[] = 'BBM_DataWriter_Forum';
	      	}
	}
}