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
use Parser;
use PPFrame;

/**
 * Class ClosureParserFunction
 *
 * Provides the {{#closure}} parser function.
 *
 * @since 1.0.0
 * @package Closures
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ClosureParserFunction {
	/**
	 * @var ClosureStore
	 */
	private ClosureStore $closure_store;

	/**
	 * ClosureParserFunction constructor.
	 *
	 * @param ClosureStore $closure_store The ClosureStore object to add the closures to
	 *
	 * @since 1.0.0
	 */
	public function __construct( ClosureStore $closure_store ) {
		$this->closure_store = $closure_store;
	}

	/**
	 * This function is the direct callback for the {{#closure}} parser function
	 * and gets directly by the parser.
	 *
	 * @param Parser &$parser The calling parser
	 * @param PPFrame $frame The current parser frame
	 * @param array $args The arguments given to the parser function as PPNode objects
	 * @return string
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function handleFunctionHook( Parser &$parser, PPFrame $frame, array $args ): string {
		if ( !isset( $args[0] ) || empty( $args[0] ) ) {
			return wfMessage( "closures-missing-closure-name" )->parse();
		}

		$expand_original = fn ( $arg ) => $frame->expand( $arg, PPFrame::RECOVER_ORIG );

		$closure_name = trim( $frame->expand( array_shift( $args ) ) );
		$closure_body = implode( "|", array_map( $expand_original, $args ) );

		try {
			$this->closure_store->add( $closure_name, $closure_body );
		} catch ( BadTitleError $exception ) {
			return wfMessage( "closures-bad-closure-name" )->parse();
		}

		return "";
	}
}
