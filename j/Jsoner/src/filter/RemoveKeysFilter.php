<?php

namespace jsoner\filter;

use jsoner\Config;

class RemoveKeysFilter implements Filter
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	public static function doFilter( $array, $params ) {

		foreach ( $array as &$item ) {
			FilterHelper::assertIsArrayOrThrow( $item );
			foreach ( $params as $key ) {
				unset( $item[$key] );
			}
		}
		return $array;
	}
}
