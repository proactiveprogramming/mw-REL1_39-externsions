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
 * Class ComplexArrayUnset.class
 *
 * Unsets a value from an existing array.
 *
 * @extends WSArrays
 */
class ComplexArrayUnset extends ResultPrinter {
	public function getName() {
		return 'complexarrayunset';
	}

	public function getAliases() {
		return [
			'caunset',
			'caremove'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define parameters and initialize parser.
	 *
	 * @param Parser $parser
	 * @param string $array_name
	 * @return array|string
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $array_name = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Array key' ) );
		}

		return self::arrayUnset( $array_name );
	}

	/**
	 * @param $array_name
	 * @return string
	 * @throws Exception
	 */
	private static function arrayUnset( $array_name ) {
		$base_array_name = GlobalFunctions::getBaseArrayFromArrayName( $array_name );

		if ( $base_array_name === $array_name ) {
			// The user is trying to unset the entire array, which is not supported.
			return '';
		}

		if ( !GlobalFunctions::arrayExists( $base_array_name ) ) {
			return '';
		}

		$array = GlobalFunctions::getArrayFromArrayName( $base_array_name );
		$keys  = GlobalFunctions::getKeys( $array_name );

		if ( !$array || !GlobalFunctions::getArrayFromArrayName( $array_name ) ) {
			return '';
		}

		if ( !$keys ) {
			return '';
		}

		self::unsetValueFromKeys( $array, $keys );

		WSArrays::$arrays[$base_array_name] = new ComplexArray( $array );

		return '';
	}

	private static function unsetValueFromKeys( &$array, $keys ) {
		$depth = count( $keys ) - 1;
		$temp =& $array;

		$isAssoc = self::isAssoc( $array );

		for ( $i = 0; $i <= $depth; $i++ ) {
			if ( $i === $depth ) {
				// Last key, delete it.
				unset( $temp[$keys[$i]] );

				if ( count( $temp ) === 0 ) {
				    // Remove dangling array
				    unset( $array[$keys[$i - 1]] );
                }

                if ( !$isAssoc ) {
                    // Reset the array indexing and only do this for numbered arrays.
                    $temp = array_values($temp);
                }

				return;
			}

			$temp =& $temp[$keys[$i]];
		}
	}

    /**
     * Check whether an array is associative or sequentially numbered.
     *
     * @param array $array
     * @return bool
     */
    private static function isAssoc( $array ) {
        if ( $array === [] ) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
