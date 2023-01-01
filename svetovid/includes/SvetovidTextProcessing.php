<?php

class SvetovidTextProcessing {

	/**
	 * Adds links to wikitext.
	 *
	 * @param string $text Wikitext
	 * @param Title $target The title to link to
	 * @param array $texts An array of possible link texts
	 *
	 * @return int Number of links added to wikitext
	 */
	public static function addLinks( string &$text, Title $target, array $texts ) : int {
		$targetName = $target->getPrefixedText();
		$edits = 0;

		$partial = preg_quote( $targetName, '/' );
		$partial = str_replace( ' ', '[ _]', $partial );
		// \p{L} matches any letter from any language, as opposed to \w (English only)
		$partial = '/^(' . substr( $partial, 0, 1 ) . '(?-i)' . substr( $partial, 1 ) . ')(\p{L}*$)/i';

		foreach ( $texts as $pattern ) {
			if ( $pattern == '' ) {
				continue;
			}

			$text = preg_replace_callback(
				self::prepareRegex( $pattern ),
				function( $matches ) use ( $targetName, $partial, &$edits ) {
					$edits += 1;

					if ( preg_match( $partial, $matches[4], $pMatches ) ) {
						// Something like [[cat]]s or [[cat]]
						return "$matches[1]$matches[3][[$pMatches[1]]]$pMatches[2]$matches[5]";
					} else {
						// [[Cat|kitty]]
						return "$matches[1]$matches[3][[$targetName|$matches[4]]]$matches[5]";
					}
				},
				$text
			);
		}

		return $edits;
	}

	private static function prepareRegex( string $text ) {
		$text = preg_quote( $text, '/' );
		$text = str_replace( ' ', '[ _]', $text );
		return '/((^|\]\])[^\[]*?)(^|[^\p{L}])(' . $text . ')($|[^\p{L}])/i';
	}
}
