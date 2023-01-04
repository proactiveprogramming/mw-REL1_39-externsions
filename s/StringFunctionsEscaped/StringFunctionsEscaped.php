<?php
use MediaWiki\Extension\ParserFunctions as PF;

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'StringFunctionsEscaped::This file is a MediaWiki extension, it is not a valid entry point' );
}
if ( !( class_exists( PF::class, false ) ) ) {
	// Commented out because this (unmaintained) extension isn't configured
	// in Wikimedia CI with the proper extension dependencies. (T275173)
	# die( 'StringFunctionsEscaped::You must have extension ParserFunctions' );
}
/*

 Defines a superset of string parser functions that allow character escaping in
 the 'search for' and 'replace with' arguments.

 {{#pos_e:value|key|offset}}

 Returns the first position of key inside the given value, or an empty string.
 If offset is defined, this method will not search the first offset characters.
 See: http://php.net/manual/function.strpos.php

 {{#rpos_e:value|key}}

 Returns the last position of key inside the given value, or -1 if the key is
 not found. When using this to search for the last delimiter, add +1 to the
 result to retrieve position after the last delimiter. This also works when
 the delimiter is not found, because "-1 + 1" is zero, which is the beginning
 of the given value.
 See: http://php.net/manual/function.strrpos.php

 {{#pad_e:value|length|with|direction}}

 Returns the value padded to the certain length with the given with string.
 If the with string is not given, spaces are used for padding. The direction
 may be specified as: 'left', 'center' or 'right'.
 See: http://php.net/manual/function.str-pad.php


 {{#replace_e:value|from|to}}

 Returns the given value with all occurrences of 'from' replaced with 'to'.
 See: http://php.net/manual/function.str-replace.php


 {{#explode_e:value|delimiter|position}}

 Splits the given value into pieces by the given delimiter and returns the
 position-th piece. Empty string is returned if there are not enough pieces.
 Note: Pieces are counted from 0.
 Note: A negative value can be used to count pieces from the end, instead of
 counting from the beginning. The last piece is at position -1.
 See: http://php.net/manual/function.explode.php

 {{#stripnewlines:value}}

 Remove multiple newlines.  Any time there is more than one newline in "value",
 they are changed to a single newline.


 Copyright (c) 2009 Jack D. Pond
 Licensed under GNU version 2
*/

$wgExtensionCredits['parserhook'][] = [
	'path'            => __FILE__,
	'name'            => 'StringFunctionsEscaped',
	'version'         => '1.0.1', // July 7, 2010
	'author'          => [ 'Jack D. Pond' ],
	'license'         => 'GNU Version 2',
	'url'             => 'https://www.mediawiki.org/wiki/Extension:StringFunctionsEscaped',
];

$dir = __DIR__ . '/';

$wgExtensionMessagesFiles['StringFunctionsEscapedMagic'] = $dir . 'StringFunctionsEscaped.i18n.magic.php';

$wgHooks['ParserFirstCallInit'][] = 'ExtStringFunctionsEscaped::onParserFirstCallInit';

class ExtStringFunctionsEscaped {

	public static function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'pos_e',         [ __CLASS__, 'runPos_e' ] );
		$parser->setFunctionHook( 'rpos_e',        [ __CLASS__, 'runRPos_e' ] );
		$parser->setFunctionHook( 'pad_e',         [ __CLASS__, 'runPad_e' ] );
		$parser->setFunctionHook( 'replace_e',     [ __CLASS__, 'runReplace_e' ] );
		$parser->setFunctionHook( 'explode_e',     [ __CLASS__, 'runExplode_e' ] );
		$parser->setFunctionHook( 'stripnewlines', [ __CLASS__, 'runStrip_nl' ] );

		return true;
	}

	/**
	 * {{#pos_e:value|key|offset}}
	 * Note: If the needle is an empty string, single space is used instead.
	 * Note: If the needle is not found, empty string is returned.
	 * Note: The needle is limited to specific length.
	 */
	public static function runPos_e( &$parser, $inStr = '', $inNeedle = '', $inOffset = 0 ) {
		return PF::runPos( $parser, $inStr, stripcslashes( $inNeedle ), $inOffset );
	}

	/**
	 * {{#rpos_e:value|key}}
	 * Note: If the needle is an empty string, single space is used instead.
	 * Note: If the needle is not found, -1 is returned.
	 * Note: The needle is limited to specific length.
	 */
	public static function runRPos_e( &$parser, $inStr = '', $inNeedle = '' ) {
		return PF::runRPos( $parser, $inStr, stripcslashes( $inNeedle ) );
	}

	/**
	 * {{#pad_e:value|length|with|direction}}
	 * Note: Length of the resulting string is limited.
	 */
	public static function runPad_e( &$parser, $inStr = '', $inLen = 0, $inWith = '', $inDirection = '' ) {
		switch ( $inDirection ) {
		default:
		case 'left':
			return CoreParserFunctions::padleft( $parent, $inStr, $inLen, stripcslashes( $inWith ) ?: ' ' );
		case 'right':
			return CoreParserFunctions::padright( $parent, $inStr, $inLen, stripcslashes( $inWith ) ?: ' ' );
		case 'center':
			$amt = ( $inLen - strlen( $inStr ) ) / 2;
			$amt = strlen( $inStr ) + ( $amt < 0 ? 0 : $amt );
			return self::runPad_e(
				$parser,
				self::runPad_e( $parser, $inStr, $amt, $inWith, 'left' ),
				$inLen,
				$inWith
			);
		}
	}

	/**
	 * {{#replace:value|from|to}}
	 * Note: If the needle is an empty string, single space is used instead.
	 * Note: The needle is limited to specific length.
	 * Note: The product is limited to specific length.
	 */
	public static function runReplace_e( &$parser, $inStr = '', $inReplaceFrom = '', $inReplaceTo = '', $inLimit = -1 ) {
		return PF::runReplace( $parser, $inStr, stripcslashes( $inReplaceFrom ), stripcslashes( $inReplaceTo ), $inLimit );
	}

	/**
	 * {{#explode_e:value|delimiter|position}}
	 * Note: Negative position can be used to specify tokens from the end.
	 * Note: If the divider is an empty string, single space is used instead.
	 * Note: The divider is limited to specific length.
	 * Note: Empty string is returned, if there is not enough exploded chunks.
	 */
	public static function runExplode_e( &$parser, $inStr = '', $inDiv = '', $inPos = 0, $inLim = null ) {
		return PF::runExplode( $parser, $inStr, stripcslashes( $inDiv ), $inPos, $inLim );
	}

	/**
	 * {{#stripnewlines:value}}
	 */
	public static function runStrip_nl( &$parser, $inStr = '' ) {
		return preg_replace( stripcslashes( '/\n\n+/' ), stripcslashes( '\n' ), $inStr );
	}

}
