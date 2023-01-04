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
 * Class ComplexArrayDiff
 *
 * Defines the parser function {{#complexarraydiff:}}, which calculates the difference between two arrays.
 *
 * @extends WSArrays
 */
class ComplexArrayDiff extends ResultPrinter {
	/**
	 * @var string
	 */
	private static $new_array;

    public function getName() {
		return 'complexarraydiff';
	}

	public function getAliases() {
		return [
			'cadiff'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @return array|int
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser ) {
		GlobalFunctions::fetchSemanticArrays();

		return self::arrayDiff( func_get_args() );
	}

    /**
     * Calculate difference between arrays.
     *
     * @param $args
     *
     * @return array|string
     * @throws Exception
     */
	private static function arrayDiff( $args ) {
		self::parseFunctionArguments( $args );

		if ( !GlobalFunctions::isValidArrayName( self::$new_array ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		$arrays = self::pushArrays( $args );

        if ( count( $arrays ) < 2 ) {
            return GlobalFunctions::error( wfMessage( 'ca-too-little-arrays' ) );
        }

        foreach ( $arrays as $array ) {
            if ( !is_array( $array ) ) {
                return '';
            }

            if ( !self::isOneDimensionalArray( $array ) ) {
                return GlobalFunctions::error( wfMessage( 'ca-diff-multidimensional' ) );
            }
        }

		$array_diff = call_user_func_array( 'array_diff_assoc', $arrays );

		if ( is_array( $array_diff ) ) {
            WSArrays::$arrays[ self::$new_array ] = new ComplexArray( $array_diff );
		}

		return '';
	}

	/**
	 * @param $arr
	 * @return array
	 * @throws Exception
	 */
	private static function pushArrays( $arr ) {
		$arrays = [];

		foreach ( $arr as $array ) {
			// Check if the array exists
			if ( !isset( WSArrays::$arrays[ $array ] ) ) {
				continue;
			}

			$array = GlobalFunctions::getArrayFromComplexArray( WSArrays::$arrays[ $array ] );

			array_push( $arrays, $array );
		}

		return $arrays;
	}

	/**
	 * @param &$args
	 */
	private static function parseFunctionArguments( &$args ) {
		self::removeFirstItemFromArray( $args );
		self::getFirstItemFromArray( $args );
		self::removeFirstItemFromArray( $args );
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
	private static function getFirstItemFromArray( &$array ) {
		self::$new_array = reset( $array );
	}

    private static function isOneDimensionalArray(array $array) {
        foreach ( $array as $item ) {
            if ( is_array( $item ) ) {
                return false;
            }
        }

        return true;
    }
}
