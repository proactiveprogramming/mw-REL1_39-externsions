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
 * Class ComplexArrayArrayMap
 */
class ComplexArrayArrayMap extends ResultPrinter {
	private static $array = [];
	private static $variable = '';
	private static $formula  = '';
	private static $new_delimiter = '';

	public function getName() {
		return 'complexarrayarraymap';
	}

	public function getAliases() {
		return [
			'caamap',
			'camapa'
		];
	}

	public function getType() {
		return 'sfh';
	}

	/**
	 * @param Parser $parser
	 * @param $frame
	 * @param $args
	 * @throws Exception
	 *
	 * @return array
	 */
	public static function getResult( Parser $parser, $frame, $args ) {
		GlobalFunctions::fetchSemanticArrays();

		$value = GlobalFunctions::getValue(
			@$args[ 0 ],
			$frame,
			$parser,
			GlobalFunctions::getValue(
				@$args[ 5 ],
				$frame
			)
		);

		$delimiter = GlobalFunctions::getValue(
			@$args[ 1 ],
			$frame
		);

		$variable = GlobalFunctions::getValue(
			@$args[ 2 ],
			$frame
		);

		$formula = GlobalFunctions::getValue(
			@$args[ 3 ],
			$frame,
			$parser,
			'NO_IGNORE,NO_TAGS,NO_TEMPLATES'
		);

		$new_delimiter = GlobalFunctions::getValue(
			@$args[ 4 ],
			$frame
		);

		return [ self::arrayArrayMap( $value, $variable, $formula, $delimiter, $new_delimiter ), 'noparse' => false ];
	}

    /**
     * @param $value
     * @param $variable
     * @param $formula
     * @param $delimiter
     * @param $new_delimiter
     * @return string
     */
	private static function arrayArrayMap( $value, $variable, $formula, $delimiter, $new_delimiter ) {
        if ( empty( $value ) || empty( $variable ) || empty( $formula ) ) {
            return '';
        }

		if ( empty( $delimiter ) ) {
			$delimiter = ',';
		}

		if ( $new_delimiter === null ) {
			$new_delimiter = ', ';
		}

		$delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $delimiter );
		$new_delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $new_delimiter );

		self::$array         = array_map( "trim", explode( $delimiter, $value ) );
		self::$variable      = $variable;
		self::$formula       = $formula;
		self::$new_delimiter = $new_delimiter;

		if ( empty( self::$array ) ) {
		    return '';
        }

		$haystack = self::iterate();

		return $haystack;
	}

	private static function iterate() {
		$haystack = [];

		foreach ( self::$array as $item ) {
			$replaced_formula = str_replace( self::$variable, $item, self::$formula );

			if ( $replaced_formula ) {
                array_push( $haystack, $replaced_formula );
			}
		}

		if ( self::$new_delimiter === "print=pretty" ) {
			return self::prettyPrint( $haystack );
		} else {
			return implode( self::$new_delimiter, $haystack );
		}
	}

	private static function prettyPrint( $haystack ) {
		$num_items = count( $haystack );

		if ( $num_items === 0 ) {
			return "";
		} else if ( $num_items === 1 ) {
			return array_pop( $haystack );
		}

		$last_element = array_pop( $haystack );
		$and = htmlspecialchars( wfMessage( "and" )->plain() );

		return implode(", ", $haystack ) . $and . " " . $last_element;
	}
}
