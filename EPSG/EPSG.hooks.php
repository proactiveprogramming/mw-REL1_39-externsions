<?php
/**
* Hooks for EPSG extension
*
* @file
* @ingroup Extensions
*/

class EPSGHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'wgs84_2epsg',                                  [  EPSGIO::class, 'WGS84ToEPSG'   ] );
		$parser->setFunctionHook( 'epsg_2wgs84',                                  [  EPSGIO::class, 'EPSGToWGS84'   ] );
		$parser->setFunctionHook( 'epsg',                                         [  EPSGIO::class, 'EPSG'          ] );
	}
}
