<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace Closures;

use BadTitleError;
use OutOfBoundsException;
use Title;

/**
 * Class ClosureStore
 *
 * @since 1.0.0
 * @package Closures
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureStore {
	/**
	 * @var array List of key-value pairs, where the key is the name of the closure
	 *            and the value the body of the closure
	 */
	public array $closures = [];

	/**
	 * Registers a new closure with the given name and the given body, or overrides
	 * an existing closure when the closure_name is already defined.
	 *
	 * @param string $closure_name The name of the closure to register
	 * @param string $closure_body The body of the closure to register
	 *
	 * @throws BadTitleError When the given closure name is not valid (a closure name is
	 *                       invalid the Title object constructed for it cannot represent
	 *                       a page in the wiki's database)
	 *
	 * @since 1.1.0
	 * @stable to call Since 1.1.0
	 */
	public function add( string $closure_name, string $closure_body ): void {
		// Since template names are case-insensitive, make everything lower case
		$closure_name = strtolower( $closure_name );

		// Check whether the given closure name is valid
		$title = Title::makeTitleSafe( NS_TEMPLATE, $closure_name );
		if ( !( $title instanceof Title ) || !$title->canExist() ) {
			throw new BadTitleError( "The closure name '$closure_name' is not valid" );
		}

		// Register the closure to the closures array
		$this->closures[$closure_name] = $closure_body;
	}

	/**
	 * Returns the body of the given closure name, or throws an exception when
	 * the given closure does not exist.
	 *
	 * @param string $closure_name The name of the closure to get the body of
	 * @return string The body of the given closure name
	 *
	 * @throws OutOfBoundsException When the given closure name does not exist
	 *
	 * @since 1.0.0
	 * @stable to call Since 1.0.0
	 */
	public function get( string $closure_name ): string {
		// Since template names are case-insensitive, make everything lower case
		$closure_name = strtolower( $closure_name );

		if ( !$this->exists( $closure_name ) ) {
			throw new OutOfBoundsException( "The closure '$closure_name' does not exist" );
		}

		return $this->closures[$closure_name];
	}

	/**
	 * Checks whether the given closure name exists.
	 *
	 * @param string $closure_name The name of the closure to check for
	 *                             whether it exists
	 * @return bool True if and only if the given closure name exists
	 *
	 * @since 1.0.0
	 * @stable to call Since 1.0.0
	 */
	public function exists( string $closure_name ): bool {
		return isset( $this->closures[ strtolower( $closure_name ) ] );
	}
}
