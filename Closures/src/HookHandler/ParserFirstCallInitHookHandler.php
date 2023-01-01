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

namespace Closures\HookHandler;

use Closures\ClosureParserFunction;
use Closures\ClosureStore;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MWException;
use Parser;

/**
 * Class ParserFirstCallInitHookHandler
 *
 * @since 1.0.0
 * @package Closures\HookHandler
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ParserFirstCallInitHookHandler implements ParserFirstCallInitHook {
	/**
	 * @var ClosureStore The ClosureStore object to inject into the Parser function hook
	 */
	private ClosureStore $closure_store;

	/**
	 * ParserFirstCallInitHookHandler constructor.
	 *
	 * @param ClosureStore $closure_store The ClosureStore object to inject into the Parser function hook
	 *
	 * @since 1.0.0
	 */
	public function __construct( ClosureStore $closure_store ) {
		$this->closure_store = $closure_store;
	}

	/**
	 * Called when the parser initializes for the first time. Initializes the
	 * #closure parser function to the parser.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser Parser object being initialized
	 * @return void
	 *
	 * @since 1.0.0
	 * @throws MWException
	 * @internal
	 */
	final public function onParserFirstCallInit( $parser ): void {
		$parser->setFunctionHook( "closure", [
			new ClosureParserFunction( $this->closure_store ),
			"handleFunctionHook"
		], SFH_OBJECT_ARGS );
	}
}
