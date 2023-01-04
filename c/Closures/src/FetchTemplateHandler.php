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

use MediaWiki\Debug\DeprecatablePropertyArray;
use Parser;
use Title;

/**
 * Class FetchTemplateHandler
 *
 * @since 1.0.0
 * @package Closures
 * @author Marijn van Wezel
 * @license GPL-2.0-or-later
 */
class FetchTemplateHandler {
	/**
	 * @var Parser The parser to use as a fallback for when a normal template
	 *             call is executed.
	 */
	private Parser $parser;

	/**
	 * @var ClosureStore The ClosureStore to fetch closures from
	 */
	private ClosureStore $closure_store;

	/**
	 * FetchTemplateHandler constructor.
	 *
	 * @param Parser $parser
	 * @param ClosureStore $closure_store The ClosureStore to fetch closures from
	 *
	 * @since 1.0.0
	 */
	public function __construct( Parser $parser, ClosureStore $closure_store ) {
		$this->parser = $parser;
		$this->closure_store = $closure_store;
	}

	/**
	 * Handles a template fetch call from the parser.
	 *
	 * @see Parser::statelessFetchTemplate()
	 *
	 * @param Title $title The title to fetch the content of; this Title corresponds to the title between the brackets
	 * @param Parser|false $parser
	 * @return array|DeprecatablePropertyArray
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function handleFetchTemplate( Title $title, $parser = false ) {
		$template_name = $title->getNamespace() === NS_TEMPLATE ? $title->getText() : $title->getFullText();

		if ( !$this->closure_store->exists( $template_name ) ) {
			// The closure does not exist, fallback to default template behaviour
			return $this->parser->statelessFetchTemplate( $title, $parser );
		}

		return [ "text" => $this->closure_store->get( $template_name ) ];
	}
}
