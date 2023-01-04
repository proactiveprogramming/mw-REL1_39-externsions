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
 * Class ComplexArraySize
 *
 * Defines the parser function {{#complexarraysize:}}, which allows users to get the size of a (sub)array.
 *
 * @extends WSArrays
 */
class ComplexArraySize extends ResultPrinter {
	public function getName() {
		return 'complexarraysize';
	}

	public function getAliases() {
		return [
		  'casize'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string $array_name
	 * @param string $options
	 * @return array|int
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $array_name = '', $options = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Array key' ) );
		}

		return self::arraySize( $array_name, $options );
	}

	/**
	 * Calculate size of array.
	 *
	 * @param $name
	 * @param string $options
	 * @return array|string
	 *
	 * @throws Exception
	 */
	private static function arraySize( $name, $options = '' ) {
		if ( !GlobalFunctions::arrayExists( GlobalFunctions::getBaseArrayFromArrayName( $name ) ) ) {
			return '';
		}

		$array = GlobalFunctions::getArrayFromArrayName( $name );

		if ( $options === "top" ) {
			return count( $array );
		}

		return count( $array, COUNT_RECURSIVE );
	}
}
