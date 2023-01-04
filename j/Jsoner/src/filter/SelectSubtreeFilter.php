<?php

namespace jsoner\filter;

use jsoner\Config;

/**
 *
 * Does not modify the array if there is no such subtree.
 *
 */
class SelectSubtreeFilter implements Filter
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {

		$this->config = $config;
	}

	public static function doFilter( $array, $subtree ) {

		// If array contains a sequential list of objects, do nothing
		if ( array_keys( $array ) === range( 0, count( $array ) - 1 ) ) {
			return $array;
		}

		if ( array_key_exists( $subtree, $array ) ) {
			$array = $array[$subtree];
		}
		return $array;
	}
}



