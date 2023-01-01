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
 * Defines the parser function {{#complexarraysearch:}}, which allows users to get search in an array.
 *
 * @extends WSArrays
 */
class ComplexArraySearch extends ResultPrinter {
	public function getName() {
		return 'complexarraysearch';
	}

	public function getAliases() {
		return [
		  'casearch'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * @var string
	 */
	private static $array_name = '';

	/**
	 * @param Parser $parser
	 * @param string $array_name
	 * @param string $value
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $array_name = '', $value = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( $array_name === '' ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		if ( $value === '' ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Value' ) );
		}

		return self::arraySearch( $array_name, $value );
	}

	/**
	 * @param string $array_name
	 * @param $value
	 * @return array|int|string
	 *
	 * @throws Exception
	 */
	private static function arraySearch( $array_name, $value ) {
		if ( !isset( WSArrays::$arrays[ $array_name ] ) ) {
			return '';
		}

		self::$array_name = null;

		$array = GlobalFunctions::getArrayFromArrayName( $array_name );
		self::findValue( $array, $value, $array_name );

		return self::$array_name;
	}

	/**
	 * @param array $array
	 * @param string $value
	 * @param string &$array_name
	 */
	private static function findValue( $array, $value, &$array_name ) {
		foreach ( $array as $current_key => $current_item ) {
			$array_name .= "[$current_key]";

			if ( $value === $current_item ) {
				self::$array_name = $array_name;

				return;
			} else {
				if ( is_array( $current_item ) ) {
					self::findValue( $current_item, $value, $array_name );
				}

				$array_name = substr( $array_name, 0, strrpos( $array_name, '[' ) );
			}
		}
	}
}
