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
 * Class ComplexArrayExtract
 *
 * Defines the parser function {{#complexarrayextract:}}, which allows users to create a new array from a subarray.
 *
 * @extends WSArrays
 */
class ComplexArrayExtract extends ResultPrinter {
	public function getName() {
		return 'complexarrayextract';
	}

	public function getAliases() {
		return [
			'caextract'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string $new_name
	 * @param string $array_name
	 * @return array|bool
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $new_name = '', $array_name = '' ) {
		if ( !$new_name ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'New array' ) );
		}

		if ( !GlobalFunctions::isValidArrayName( $new_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		if ( !$array_name ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Array key' ) );
		}

		return self::arrayExtract( $new_name, $array_name );
	}

	/**
	 * @param $new_name
	 * @param $subarray
	 * @return array|bool
	 *
	 * @throws Exception
	 */
	private static function arrayExtract( $new_name, $array_name ) {
		// If no subarray is provided, show an error.
		if ( !strpos( $array_name, "[" ) ||
			!strpos( $array_name, "]" ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-subarray-not-provided' ) );
		}

		$array = GlobalFunctions::getArrayFromArrayName( $array_name );

		if ( $array ) {
            WSArrays::$arrays[ $new_name ] = new ComplexArray( (array)$array );
		}

		return '';
	}
}
