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
 * Class ComplexArrayAddValue
 *
 * Defines the parser function {{#complexarrayaddvalue:}}, which allows users to add values to (sub)arrays.
 *
 * @extends WSArrays
 */
class ComplexArrayAddValue extends ResultPrinter {
	public function getName() {
		return 'complexarrayaddvalue';
	}

	public function getAliases() {
		return [
			'caaddvalue',
			'caadd',
			'caaddv',
			'caset'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define parameters and initialize parser.
	 *
	 * @param Parser $parser
	 * @param string $name
	 * @param string $value
	 * @return array|null
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $name = '', $value = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		if ( empty( $value ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Value' ) );
		}

		if ( !strpos( $name, "[" ) ||
			 !strpos( $name, "]" ) ) {
			$ca_subarray_not_provided = wfMessage( 'ca-subarray-not-provided' );

			return GlobalFunctions::error( $ca_subarray_not_provided );
		}

		return self::arrayAddValue( $name, $value );
	}

	/**
	 * This function first calculates the name of the base array, then fetches that array and adds a value to the array.
	 * The array is then saved again under the same name with the value added.
	 *
	 * @param $array_name
	 * @param $value
	 * @return array|string
	 *
	 * @throws Exception
	 */
	private static function arrayAddValue( $array_name, $value ) {
		$base_array_name = GlobalFunctions::getBaseArrayFromArrayName( $array_name );

		if ( !GlobalFunctions::arrayExists( $base_array_name ) ) {
			return '';
		}

		$keys = GlobalFunctions::getKeys( $array_name );

		if ( !$keys ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		$array = GlobalFunctions::getArrayFromComplexArray( WSArrays::$arrays[ $base_array_name ] );

		self::set( $keys, $array, $value );

		WSArrays::$arrays[ $base_array_name ] = new ComplexArray( $array );

		return '';
	}

	/**
	 * @param $path
	 * @param array &$array
	 * @param null $value
	 */
	private static function set( $path, &$array = [], $value = null ) {
		$value = GlobalFunctions::markupToArray( $value );

		$temp =& $array;

		foreach ( $path as $key ) {
			if ( !isset( $temp[$key] ) ) {
				$temp[$key] = [];
			}

			$temp =& $temp[$key];
		}

		$temp = $value;
	}
}
