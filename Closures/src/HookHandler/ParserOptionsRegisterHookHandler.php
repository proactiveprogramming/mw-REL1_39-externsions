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

use Closures\ClosureStore;
use Closures\FetchTemplateHandler;
use MediaWiki\Hook\ParserOptionsRegisterHook;
use Parser;

/**
 * Class ParserOptionsRegisterHookHandler
 *
 * @since 1.0.0
 * @package Closures\HookHandler
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class ParserOptionsRegisterHookHandler implements ParserOptionsRegisterHook {
	/**
	 * @var Parser The Parser object to inject into the FetchTemplateHandler
	 */
	private Parser $parser;

	/**
	 * @var ClosureStore The ClosureStore object to inject into the FetchTemplateHandler
	 */
	private ClosureStore $closure_store;

	/**
	 * @var FetchTemplateHandler The FetchTemplateHandler object to register as the templateCallback
	 *                           in the Parser
	 */
	private FetchTemplateHandler $fetch_template_handler;

	/**
	 * ParserOptionsRegisterHookHandler constructor.
	 *
	 * @param Parser $parser The Parser object to inject into the FetchTemplateHandler
	 * @param ClosureStore $closure_store The ClosureStore object to inject into the FetchTemplateHandler
	 * @param FetchTemplateHandler|null $fetch_template_handler The FetchTemplateHandler object to
	 * register as the templateCallback in the Parser
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		Parser $parser,
		ClosureStore $closure_store,
		FetchTemplateHandler $fetch_template_handler = null
	) {
		$this->parser = $parser;
		$this->closure_store = $closure_store;
		$this->fetch_template_handler = $fetch_template_handler ??
			new FetchTemplateHandler( $this->parser, $this->closure_store );
	}

	/**
	 * Allows registering additional parser options.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOptionsRegister
	 *
	 * @param array &$defaults Options and their defaults
	 * @param array &$inCacheKey Whether each option splits the parser cache
	 * @param array &$lazyLoad Initializers for lazy-loaded options
	 * @return void
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ): void {
		$defaults["templateCallback"] = [ $this->fetch_template_handler, "handleFetchTemplate" ];
	}
}
