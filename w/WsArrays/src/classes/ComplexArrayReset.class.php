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
 * Class ComplexArrayReset
 *
 * Defines the parser function {{#complexarrayreset:}}, which allows users to reset all or one array.
 *
 * @extends WSArrays
 */
class ComplexArrayReset extends ResultPrinter {
	public function getName() {
		return 'complexarrayreset';
	}

	public function getAliases() {
		return [
			'careset'
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
     * @return string
     */
	public static function getResult( Parser $parser, $array_name = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		self::arrayReset( $array_name );
		return '';
	}

	/**
	 * Reset all or one array.
	 *
	 * @param string $array_name
	 */
	private static function arrayReset( $array_name = '' ) {
		if ( empty( $array_name ) ) {
			WSArrays::$arrays = [];
		} else {
			if ( isset( WSArrays::$arrays[$array_name] ) ) {
				unset( WSArrays::$arrays[$array_name] );
			}
		}
	}
}
