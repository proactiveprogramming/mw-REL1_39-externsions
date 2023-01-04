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
 * Abstract class ResultPrinter
 *
 * @extends ResultPrinterFactory
 */
abstract class ResultPrinter extends ResultPrinterFactory {
	/**
	 * This function should return a string containing the name of the class (which is
	 * also the name of the parser function).
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * This function returns the name of any aliases the might want to define for
	 * the parser function.
	 *
	 * @return array
	 */
	abstract public function getAliases();

	/**
	 * Specify whether to implement this extension as an 'sfh' hook or a 'standard' hook.
	 *
	 * @return null|string
	 */
	abstract public function getType();
}
