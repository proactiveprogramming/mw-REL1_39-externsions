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
 * Class ComplexArrayDefinedArrays
 *
 * Defines the parser function {{#complexarraydefinedarrays:}}, which allows users to get a list of defined arrays.
 *
 * @extends WSArrays
 */
class ComplexArrayDefinedArrays extends ResultPrinter {
	public function getName() {
		return 'complexarraydefinedarrays';
	}

	public function getAliases() {
		return [
			'cadefinedarrays',
			'cadefined',
			'cad'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string|null $array_name
	 *
	 * @return array|string
	 */
	public static function getResult( Parser $parser, $array_name = null ) {
		if ( empty( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'New array' ) );
		}

		if ( !GlobalFunctions::isValidArrayName( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		self::arrayDefinedArrays( $array_name );

		return '';
	}

	private static function arrayDefinedArrays( $array_name ) {
		$array = array_keys( WSArrays::$arrays );

		WSArrays::$arrays[ $array_name ] = new ComplexArray( $array );
	}
}
