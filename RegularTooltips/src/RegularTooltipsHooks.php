<?php

/**
 * RegularTooltips Extension
 *
 * @file
 * @ingroup Extensions
 * @author Sanjay Thiyagarajan
 *
 */

class RegularTooltipsHooks {
	/**
	 * Register parser hooks
	 * @see https://www.mediawiki.org/wiki/Manual:Parser_functions
	 * @param Parser $parser
	 */
	public static function registerParserFunctions( $parser ) {
		$output = RequestContext::getMain()->getOutput();
		$output->addModules( 'ext.RegularTooltips' );

		// Register parser functions
		$parser->setFunctionHook( 'inline-tooltip', [ 'RegularTooltipsParser', 'inlineTooltip' ] );
		$parser->setFunctionHook( 'info-tooltip', [ 'RegularTooltipsParser', 'infoTooltip' ] );
	}

}
