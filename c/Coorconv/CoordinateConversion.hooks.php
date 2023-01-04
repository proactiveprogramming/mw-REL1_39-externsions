<?php
/**
* Hooks for CoordinateConversion extension
*
* @file
* @ingroup Extensions
*/

use DataValues\Geo\Values\LatLongValue;
use DataValues\Geo\Parsers\LatLongParser;
use DataValues\Geo\Formatters\LatLongFormatter;
use ValueFormatters\FormatterOptions;

class CoordinateConversionHooks {

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'lat_deg2dms',                                  [    self::class, 'latDegToDMS'   ] );
		$parser->setFunctionHook( 'lat_dms2deg',                                  [    self::class, 'latDMSToDeg'   ] );
		$parser->setFunctionHook( 'long_deg2dms',                                 [    self::class, 'longDegToDMS'  ] );
		$parser->setFunctionHook( 'long_dms2deg',                                 [    self::class, 'longDMSToDeg'  ] );
		$parser->setFunctionHook( 'deg2dms',                                      [    self::class, 'DegToDMS'      ] );
		$parser->setFunctionHook( 'wgs84_2rd',                                    [      RD::class, 'WGS84ToRD'     ] );
		$parser->setFunctionHook( 'wgs84_2lb93',                                  [ Lambert::class, 'WGS84ToLAM93'  ] );
		$parser->setFunctionHook( 'wgs84_2lb08',                                  [ Lambert::class, 'WGS84ToLAM08'  ] );
		$parser->setFunctionHook( 'wgs84_2ch03',                                  [  CH1903::class, 'WGS84ToCH1903' ] );
		$parser->setFunctionHook( 'wgs84_2ch03p',                                 [  CH1903::class, 'WGS84ToCH1903p'] );
		$parser->setFunctionHook( 'lat_long2utm',                                 [  TransM::class, 'WGS84ToUTM'    ] );
		$parser->setFunctionHook( 'wgs84_2utm',                                   [  TransM::class, 'WGS84ToUTM'    ] );
		$parser->setFunctionHook( 'wgs84_2itm',                                   [  OSGRID::class, 'WGS84ToITM'    ] );
		$parser->setFunctionHook( 'wgs84_2tm35fin',                               [  TransM::class, 'WGS84ToTM35FIN'] );
		$parser->setFunctionHook( 'wgs84_2mtm',                                   [  TransM::class, 'WGS84ToMTM'    ] );
		$parser->setFunctionHook( 'wgs84_2osgb',                                  [  OSGRID::class, 'WGS84ToOSGB36' ] );
		$parser->setFunctionHook( 'wgs84_2ig',                                    [  OSGRID::class, 'WGS84ToIG'     ] );
		$parser->setFunctionHook( 'wgs84_2luref',                                 [   LUREF::class, 'WGS84ToLUREF'  ] );
		$parser->setFunctionHook( 'wgs84_2epsg',                                  [  EPSGIO::class, 'WGS84ToEPSG'   ] );
		$parser->setFunctionHook( 'epsg_2wgs84',                                  [  EPSGIO::class, 'EPSGToWGS84'   ] );
		$parser->setFunctionHook( 'epsg',                                         [  EPSGIO::class, 'EPSG'          ] );
	}

	static function DMS( $d, $m, $s, $h ) {
		// prime (minutes, feet) = U+2032, &#8242;, &prime;
		// double prime (seconds, inches) = U+2033, &#8243;, &Prime;
		return sprintf( "%d&deg; %02d&#8242; %04.1f&#8243; %s",                   $d, $m, $s, $h );
	}

	static function latDegToDMS( &$parser, $degrees ) {
		$h = $degrees < 0 ? 'S' : 'N';
		$degrees = round( abs($degrees) * 360000,                                 0 ) % 32400000;
		$d = $degrees / 360000; $degrees %= 360000;
		$m = $degrees / 6000;   $degrees %= 6000;
		$s = $degrees / 100;
		return self::DMS( $d,                                                     $m, $s, $h );
	}

	static function latDMSToDeg( &$parser, $d, $m, $s, $h ) {
		$degrees = ($d * 3600 + $m * 60 + $s) / 3600.0 * ($h == 'N' ? 1 : -1);
		return $degrees;
	}

	static function longDegToDMS( &$parser, $degrees ) {
		$h = $degrees < 0 ? 'W' : 'E';
		$degrees = round( abs($degrees) * 360000,                                 0 ) % 64800000;
		$d = $degrees / 360000; $degrees %= 360000;
		$m = $degrees / 6000;   $degrees %= 6000;
		$s = $degrees / 100;
		return self::DMS( $d,                                                     $m, $s, $h );
	}

	static function longDMSToDeg( &$parser, $d, $m, $s, $h ) {
		$degrees = ($d * 3600 + $m * 60 + $s) / 3600.0 * ($h == 'W' ? -1 : 1);
		return $degrees;
	}

	static function DegToDMS( &$parser, $coord) {
  //             $array = explode(',', $coord);
  //             return self::latDegToDMS( $parser, $array[0]).' , '.self::longDegToDMS( $parser, $array[1]);
   	$llparser = new LatLongParser();
   	$latLongValue = $llparser->parse($coord);
	 	$options = new FormatterOptions();
	 	$options->setOption( LatLongFormatter::OPT_FORMAT,                       LatLongFormatter::TYPE_DMS );
	 	$options->setOption( LatLongFormatter::OPT_DIRECTIONAL,                  true );
	 	$options->setOption( LatLongFormatter::OPT_PRECISION,                    1 / 36000 );
	 	$formatter = new LatLongFormatter($options);
  	return $formatter->format(new LatLongValue($latLongValue->getLatitude(), $latLongValue->getLongitude()));
	}

}
