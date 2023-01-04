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
 * Class ComplexArrayMerge
 *
 * Defines the parser function {{#complexarraymerge:}}, which allows users to merge multiple arrays.
 *
 * @extends WSArrays
 */
class ComplexArrayMerge extends ResultPrinter {
	public function getName() {
		return 'complexarraymerge';
	}

	public function getAliases() {
		return [
			'camerge'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * @var string
	 */
	private static $new_array = '';

	/**
	 * @var string
	 */
	private static $last_element = '';

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @return array|null
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser ) {
		GlobalFunctions::fetchSemanticArrays();

		return self::arrayMerge( func_get_args() );
	}

	/**
	 * @param $args
	 * @return array|string
	 * @throws Exception
	 */
	private static function arrayMerge( $args ) {
		self::parseFunctionArguments( $args );

		if ( !GlobalFunctions::isValidArrayName( self::$new_array ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		if ( count( $args ) < 2 ) {
			return GlobalFunctions::error( wfMessage( 'ca-too-little-arrays' ) );
		}

		$arrays = self::iterate( $args );

		if ( self::$last_element === "recursive" ) {
			$array = call_user_func_array( 'array_merge_recursive', $arrays );

			if ( !is_array( $array ) ) {
                WSArrays::$arrays[ self::$new_array ] = new ComplexArray( $array );
			}
		} else {
			$array = call_user_func_array( 'array_merge', $arrays );

			if ( is_array( $array ) ) {
                WSArrays::$arrays[ self::$new_array ] = new ComplexArray( $array );
			}
		}

		return '';
	}

	/**
	 * @param &$args
	 */
	private static function parseFunctionArguments( &$args ) {
		self::removeFirstItemFromArray( $args );
		self::getFirstItemFromArray( $args );
		self::removeFirstItemFromArray( $args );
		self::removeLastItemFromArray( $args );

		// If the last element is not "recursive", add it back
		if ( self::$last_element !== "recursive" ) {
			self::addItemToEndOfArray( $args, self::$last_element );
		}
	}

	/**
	 * @param $arr
	 * @return array
	 * @throws Exception
	 */
	private static function iterate( $arr ) {
		$arrays = [];
		foreach ( $arr as $array ) {
			// Check if the array exists
			if ( !isset( WSArrays::$arrays[ $array ] ) ) {
				continue;
			}

			$array = GlobalFunctions::getArrayFromComplexArray( WSArrays::$arrays[ $array ] );
			array_push( $arrays, (array)$array );
		}

		return $arrays;
	}

	/**
	 * @param &$array
	 */
	private static function removeFirstItemFromArray( &$array ) {
		array_shift( $array );
	}

	/**
	 * @param &$array
	 */
	private static function removeLastItemFromArray( &$array ) {
		self::$last_element = array_pop( $array );
	}

	/**
	 * @param &$array
	 */
	private static function getFirstItemFromArray( &$array ) {
		self::$new_array = reset( $array );
	}

	/**
	 * @param &$array
	 * @param $item
	 */
	private static function addItemToEndOfArray( &$array, $item ) {
		array_push( $array, $item );
	}
}
