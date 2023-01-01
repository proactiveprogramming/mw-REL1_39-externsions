<?php

use MediaWiki\Revision\RevisionRecord;

class SvetovidUtilities {

	/**
	 * Transforms declension tags into something more human-readable.
	 *
	 * @param string $tags
	 * @param bool $long
	 * @return string
	 */
	public static function transformTagsToText( string $tags, bool $long = false ) {
		$text = '';
		foreach ( explode( ':', $tags ) as $tag ) {
			$inner = '';
			foreach ( explode( '.', $tag ) as $subtag ) {
				if ( $long ) {
					$message = wfMessage( "svetovid-grammar-l-$subtag" );
					$mText = $message->escaped();
					if ( !$message->exists() || $mText == '-' ) {
						$message = wfMessage( "svetovid-grammar-s-$subtag" );
					}
				} else {
					$message = wfMessage( "svetovid-grammar-s-$subtag" );
				}

				if ( !$message->exists() ) continue;
				$mText = $message->escaped();
				if ( $mText == '-' ) continue;

				if ( $inner ) {
					$inner .= '/';
				}
				$inner .= $mText;
			}

			if ( $text && $inner ) {
				$text .= ', ';
			}
			$text .= $inner;
		}

		return $text;
	}

	public static function getTextFromRevision( RevisionRecord $revision ) : string {
		try {
			$content = $revision->getContent( 'main' );

			if ( is_a( $content, 'TextContent' ) ) {
				/** @var TextContent $content */
				return $content->getText();
			}

			return '';
		}
		catch ( Exception $ex ) {
			return '';
		}
	}
}
