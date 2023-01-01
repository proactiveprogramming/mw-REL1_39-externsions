<?php

namespace MediaWiki\Extensions\LunarFunctions;

use Parser;

class Hooks {

	/**
	 * Registers our parser functions with a fresh parser.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserFirstCallInit
	 *
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( Parser $parser ) {

		// These functions accept DOM-style arguments
		$class = LunarFunctions::class;
		$parser->setFunctionHook( 'lunar', "$class::renderLunar", Parser::SFH_OBJECT_ARGS );

	}
}
