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
 * Class ComplexArray
 *
 * Great-grandfather class. This is the highest class. It defines the object arrays should be stored in. Arrays that are stored in this object, are always escaped and safe.
 */
class ComplexArray {
	/**
	 * @var array
	 */
	private $array = [];

	/**
	 * @param array $array
	 */
	public function __construct( array $array = [] ) {
		$this->array = $array;
	}

	/**
	 * Return the array with escaped characters.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getArray() {
		return $this->array;
	}
}
