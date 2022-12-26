<?php

/**
 * RegularTooltips Parser
 *
 * @file
 * @ingroup Extensions
 * @author Sanjay Thiyagarajan
 */
class RegularTooltipsParser {

	/**
	 * Parser function handler for {{#inline-tooltip: inline-text | tooltip-text }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 *
	 * @return string HTML to insert in the page.
	 */
	public static function inlineTooltip( $parser, $value ) {
		$args = array_slice( func_get_args(), 2 );
		$title = $args[0];

		return Html::element( 'abbr', [ 'title' => $title ], $value );
	}

	/**
	 * Parser function handler for {{#info-tooltip: tooltip-text }}
	 *
	 * @param Parser $parser
	 * @param string $value
	 *
	 * @return string HTML to insert in the page.
	 */
	public static function infoTooltip( $parser, $value ) {
		return Html::element( 'span', [
			'class' => 'regular-tooltips',
			'data-regular-tooltips' => $value
		], null );
	}

}
