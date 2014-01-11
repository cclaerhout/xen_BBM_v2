<?php

class BBM_Helper_QuattroUnicode
{
	public static function unicodeIconsSet()
	{
		$xen = array(
			'e000',
			'e001',
			'e002',
			'e003',
			'e004',
			'e005',
			'e006',
			'e007',
			'e008',
			'e009',
			'e00a',
			'e00b',
			'e00c',
			'e00d',
			'e00e',
			'e00f',
			'e010',
			'e011',
			'e012',
			'e013',
			'e014',
			'e015',
			'e016',
			'e017',
			'e018',
			'e019',
			'e01a',
			'e01b',
			'e01c',
			'e01d',
			'e01e',
			'e01f',
			'e020',
			'e021',
			'e023',
			'e024',
			'e025',
			'e026',
			'e027',
			'e028',
			'e029',
			'e02a',
			//'e02b', //disable - already there - will have to delete it from the fonts
			'e02d',
			'e02e',
			'f1e2',
			'f1e1',
			'f1e3',
			'e022',
			'e031',
			'e032',
			'e033',
			'f34a',
			'f0b3',
			'f33c',
			'f348',
			'f347',
			'f3c5',
			'f1fb',
			'f206',
			'f21e',
			'f21f',
			'e030',
			'e204',
			'e2ff',
			'e1f9',
			'e0f2',
			'e02c',
			'e02f',
			'e035',
			'e447',
			'e036',
			'e0be',
			//'e0bf', //disable - already there - will have to delete it from the fonts
			'e0c0',
			'e11e',
			'e232',
			'e448',
			'e034'
		);
		
		$mce = array(
			//'f000',// Don't add this icon
			'e034',
			'e032',
			'e031',
			'e030',
			'e02f',
			'e02e',
			'e02d',
			'e02c',
			'e02b',
			'e02a',
			'e029',
			'e028',
			'e027',
			'e026',
			'e025',
			'e024',
			'e023',
			'e022',
			'e021',
			'e020',
			'e01f',
			'e01e',
			'e01d',
			'e01c',
			'e01b',
			'e01a',
			'e019',
			'e018',
			'e017',
			'e016',
			'e015',
			'e014',
			'e013',
			'e012',
			'e011',
			'e010',
			'e00f',
			'e00e',
			'e00d',
			'e00c',
			'e00b',
			'e00a',
			'e009',
			'e008',
			'e007',
			'e006',
			'e005',
			'e004',
			'e003',
			'e002',
			'e001',
			'e000',
			'e033',
			'e035'
		);
	
		return array($xen, $mce);
	}

	public static function getunicodeSets()
	{
		list($xenSet, $mceSet) = self::unicodeIconsSet();
		
		return array(
			'xen' => self::_setParsedUnicode($xenSet),
			'mce' => self::_setParsedUnicode($mceSet)
		);
	}
	
	protected static function _setParsedUnicode(array $src)
	{
		foreach($src as &$icon)
		{
			$icon = array(
				'unicode' => $icon,
				'parsed'  => json_decode('"\u'.$icon.'"')
			);
		}
		
		return $src;		
	}	
}
//Zend_Debug::dump($code);