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
 * Class ComplexArraySearch
 *
 * Defines the parser function {{#complexarraysearcharray:}}, which allows users to search for a string in the array, and define an array with all the keys of the result.
 *
 * @extends WSArrays
 */
class ComplexArraySearchArray extends ResultPrinter {
	public function getName() {
		return 'complexarraysearcharray';
	}

	public function getAliases() {
		return [
			'casearcharray',
			'casearcha'
		];
	}

	public function getType() {
		return 'normal';
	}

	private static $found = [];

	/**
	 * @param Parser $parser
	 * @param string $new_array_name
	 * @param string $array_name
	 * @param string $value
	 * @return string|array
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $new_array_name = '', $array_name = '', $value = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $new_array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'New array key' ) );
		}

		if ( !GlobalFunctions::isValidArrayName( $new_array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		if ( empty( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Array key' ) );
		}

		if ( empty( $value ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Value' ) );
		}

		return self::arraySearchArray( $new_array_name, $array_name, $value );
	}

	/**
	 * @param $name
	 * @param $value
	 * @param $new_array
	 * @return string
	 *
	 * @throws Exception
	 */
	private static function arraySearchArray( $new_array, $name, $value ) {
		if ( !GlobalFunctions::arrayExists( $name ) ) {
			return '';
		}

		self::findValues( $value, $name );

		if ( count( self::$found ) > 0 ) {
			WSArrays::$arrays[ $new_array ] = new ComplexArray( self::$found );
		}

		return '';
	}

	/**
	 * @param $value
	 * @param $key
	 *
	 * @throws Exception
	 */
	private static function findValues( $value, $key ) {
		$array = GlobalFunctions::getArrayFromArrayName( $key );

		self::$found = [];
		self::i( $array, $value, $key );
	}

	/**
	 * @param $array
	 * @param $value
	 * @param &$key
	 */
	private static function i( $array, $value, &$key ) {
		foreach ( $array as $current_key => $current_item ) {
			$key .= "[$current_key]";

			if ( $value === $current_item ) {
				array_push( self::$found, $key );
			} else {
				if ( is_array( $current_item ) ) {
					self::i( $current_item, $value, $key );
				}
			}

			$key = substr( $key, 0, strrpos( $key, '[' ) );
		}
	}
}
