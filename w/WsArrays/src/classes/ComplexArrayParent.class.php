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
 * Class ComplexArrayParent.class
 *
 * Defines the parser function {{#complexarrayparent:}}, which returns the parent of the given key.
 *
 * @extends WSArrays
 */
class ComplexArrayParent extends ResultPrinter {
	public function getName() {
		return 'complexarrayparent';
	}

	public function getAliases() {
		return [
			'caparent',
			'capapa',
			'camama'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string|null $key
	 * @return array|null
	 */
	public static function getResult( Parser $parser, $key = null ) {
		if ( empty( $key ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Key' ) );
		}

		return self::arrayParent( $key );
	}

	private static function arrayParent( $key ) {
		$regex = '/\[[^\[\]]+\]$/m';

		return preg_replace( $regex, '', $key );
	}
}
