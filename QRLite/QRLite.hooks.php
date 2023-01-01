<?php

/**
 * Hooks for QRLite extension
 *
 * @file
 * @ingroup Extensions
 */
class QRLiteHooks {

	/**
	 * Register parser hooks
	 *
	 * See also http://www.mediawiki.org/wiki/Manual:Parser_functions
	 */
	public static function onParserFirstCallInit( &$parser ) {
		// Register parser functions
		$parser->setFunctionHook( 'qrlite', 'QRLiteHooks::qrliteFunctionHook' );

		return true;
	}

	/**
	 * Wrapper for the #qrlite parser function
	 *
	 * @param $parser
	 * @param $main
	 *
	 * @return array
	 */
	public static function qrliteFunctionHook( $parser, $main ) {
		$args = array_slice( func_get_args(), 2 );
		$params = self::extractOptions( $args );

		// If the prefix is not set as key-value, but the first parameter is set
		// Use it as prefix (short form)
		if ( $main ) {
			$params['prefix'] = $main;
		}

		$id = QRLiteFunctions::generateQRCode( $params );

		return [ $id, 'noparse' => true, 'isHTML' => true ];
	}

	/**
	 * Converts an array of values in form [0] => "name=value" into a real
	 * associative array in form [name] => value
	 *
	 * @param array $options
	 * @return array $results
	 */
	private static function extractOptions( array $options ) {
		$results = [];

		foreach ( $options as $option ) {
			$pair = explode( '=', $option, 2 );
			if ( count( $pair ) == 2 ) {
				$name = trim( $pair[0] );
				$value = trim( $pair[1] );
				$results[$name] = $value;
			}
		}

		return $results;
	}
}
