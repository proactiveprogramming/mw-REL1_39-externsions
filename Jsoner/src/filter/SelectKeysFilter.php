<?php

namespace jsoner\filter;

use jsoner\Config;

class SelectKeysFilter implements Filter
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {

		$this->config = $config;
	}

	public static function doFilter( $array, $params ) {
		$result = [];
		$select_these_keys = $params;

		if ( count( $array ) > 0 && !array_key_exists( 0, $array ) ) {
			$array = [$array];
		}

		foreach ( $array as $item ) {
			FilterHelper::assertIsArrayOrThrow( $item );

			$selected = array_intersect_key( $item, array_flip( $select_these_keys ) );
			$selected_and_sorted = array_replace( array_flip( $params ), $selected );
			$result[] = $selected_and_sorted;
		}
		return $result;
	}
}
