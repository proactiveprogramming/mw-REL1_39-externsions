<?php

namespace jsoner\filter;

use jsoner\Config;
use Rwillians\Stingray\Stingray;

class ReduceFilter implements Filter
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	public static function doFilter( $array, $params ) {

		$variable_name = $params[0];
		$selector = $params[1];

		$array_copy = $array;

		foreach ( $array_copy as $index => $item ) {
			FilterHelper::assertIsArrayOrThrow( $item );

			$nested_value = Stingray::get( $item, $selector );
			$item[$variable_name] = $nested_value;
			$array[$index] = $item;
		}

		return $array;
	}
}
