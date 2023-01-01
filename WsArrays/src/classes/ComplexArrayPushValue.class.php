<?php

/**
 * WSArrays - Associative and multidimensional arrays for MediaWiki.
 * Copyright (C) 2019 Marijn van Wezel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * Class ComplexArrayPushValue
 *
 * Defines the parser function {{#complexarraypushvalue:}}, which allows users to push a value or subarray to the end of a (sub)array.
 *
 * @extends WSArrays
 */
class ComplexArrayPushValue extends ResultPrinter {
	public function getName() {
		return 'complexarraypushvalue';
	}

	public function getAliases() {
		return [
			'complexarraypush',
			'capush'
		];
	}

	public function getType() {
		return 'sfh';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string $frame
	 * @param string $args
	 * @return array|bool|null
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $frame, $args ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( !isset( $args[0] ) || empty( $args[0] ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		if ( !isset( $args[1] ) || empty( $args[1] ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Value' ) );
		}

		$noparse = GlobalFunctions::getValue( @$args[2], $frame );
		$array_name = GlobalFunctions::getValue( @$args[0], $frame );
		$value = GlobalFunctions::getValue( @$args[1],  $frame, $parser, $noparse );

		return self::arrayPushValue( $array_name, $value );
	}

	/**
	 * @param $array_name
	 * @param $markup_value
	 * @return array|bool|string
	 *
	 * @throws Exception
	 */
	private static function arrayPushValue( $array_name, $markup_value ) {
		$base_array = GlobalFunctions::getBaseArrayFromArrayName( $array_name );

		// If the array doesn't exist yet, create it
		if ( !GlobalFunctions::arrayExists( $base_array ) ) {
			if ( !GlobalFunctions::isValidArrayName( $base_array ) ) {
				return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
			}

			WSArrays::$arrays[ $base_array ] = new ComplexArray();
		}

		$matches = [];
		preg_match_all( "/(?<=\[).+?(?=\])/", $array_name, $matches );

		$array = GlobalFunctions::getArrayFromComplexArray( WSArrays::$arrays[$base_array] );
		$value = GlobalFunctions::markupToArray( $markup_value );

		if ( count( $value ) === 1 ) {
			$value = $value[0];
		}

		if ( !strpos( $array_name, "[" ) ) {
			self::replace( $value, $array, $base_array );
		} else {
			$result = self::add( $matches[0], $array, $value );

			if ( $result !== true ) {
				return $result;
			}

			WSArrays::$arrays[$base_array] = new ComplexArray( $array );
		}

		return '';
	}

	private static function replace( $value, $array, $base_array ) {
		array_push( $array, $value );

		WSArrays::$arrays[ $base_array ] = new ComplexArray( $array );
	}

	/**
	 * Push value to location defined in $path.
	 *
	 * @param $path
	 * @param array &$array
	 * @param null $value
	 * @return array|bool
	 */
	private static function add( $path, &$array = [], $value = null ) {
		$temp =& $array;
		$depth = count( $path );
		$current_depth = 0;

		foreach ( $path as $key ) {
			$current_depth++;

			if ( !array_key_exists( $key, (array)$temp ) ) {
				$temp[$key] = [];
			}

			if ( $current_depth !== $depth ) {
				if ( !is_array( $temp[$key] ) ) {
					$temp[$key] = [ $temp[$key] ];
				}

				$temp =& $temp[$key];
			} else {
				if ( !is_array( $temp[$key] ) ) {
					$temp[$key] = [ $temp[$key] ];
				}

				array_push( $temp[$key], $value );

				break;
			}
		}

		return true;
	}
}
