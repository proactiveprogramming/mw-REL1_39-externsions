<?php

namespace jsoner\filter;

class FilterHelper
{
	public static function assertIsArrayOrThrow( $maybeArray, $exception = \Exception::class ) {

		if ( !is_array( $maybeArray ) ) {
			throw new $exception( "Value was not an array!" );
		}
	}
}
