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
 * Class ComplexArrayDefine
 *
 * Defines the parser function {{#complexarraydefine:}}, which allows users to define a new array.
 *
 * @extends WSArrays
 */
class ComplexArrayDefine extends ResultPrinter {
	public function getName() {
		return 'complexarraydefine';
	}

	public function getAliases() {
		return [
			'cadefine'
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
	 *
	 * @throws Exception
     * @return array|string
	 */
	public static function getResult( Parser $parser, $frame, $args ) {
		GlobalFunctions::fetchSemanticArrays();

		// Name
		if ( !isset( $args[0] ) || empty( $args[0] ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		$array_name   = GlobalFunctions::getValue( @$args[0], $frame );
		$noparse      = GlobalFunctions::getValue( @$args[3], $frame );
		$array_markup = GlobalFunctions::getValue( @$args[1], $frame, $parser, $noparse );
		$sep          = GlobalFunctions::getValue( @$args[2], $frame );

		if ( !GlobalFunctions::isValidArrayName( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-invalid-name' ) );
		}

		// Define an empty array
		if ( empty( $array_markup ) ) {
			WSArrays::$arrays[ $array_name ] = new ComplexArray();
		} else {
		    self::arrayDefine( $array_name, $array_markup, $sep );
        }

		return '';
	}

	/**
	 * Define array and store it in WSArrays::$arrays as a SafeComplexArray object.
	 *
	 * @param string $array_name
	 * @param string $array_markup
	 * @param string|null $separator
	 * @throws Exception
	 */
	private static function arrayDefine( $array_name, $array_markup, $separator = null ) {
		$array = GlobalFunctions::markupToArray( $array_markup, $separator );

		if ( !$array ) {
			GlobalFunctions::error( wfMessage( 'ca-invalid-markup' ) );
            return;
		}

		WSArrays::$arrays[$array_name] = new ComplexArray( (array)$array );
	}
}
