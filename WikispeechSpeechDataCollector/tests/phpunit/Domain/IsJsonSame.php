<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use FormatJson;
use Hamcrest\BaseMatcher;
use Hamcrest\Core\IsSame;
use Hamcrest\Description;
use RuntimeException;

/**
 * This is a PHPUnit matcher for testing the parsed content of two JSON string equals,
 * or if both of them are undefined.
 *
 * The class is meant to be used by implementations of PHPUnit constraints for executing assertions.
 *
 * @since 0.1.0
 */
class IsJsonSame extends BaseMatcher {

	/** @var string|null */
	private $json;

	/**
	 * @since 0.1.0
	 * @param string|null $json
	 */
	public function __construct( ?string $json ) {
		$this->json = $json;
	}

	/**
	 * @since 0.1.0
	 * @param mixed $item
	 * @return bool <code>true</code> if <var>$item</var> matches,
	 *   otherwise <code>false</code>.
	 */
	public function matches( $item ) {
		if ( $this->json === null ) {
			return $item === null;
		} elseif ( !is_string( $item ) ) {
			return false;
		}
		$jsonResult = FormatJson::parse( $this->json, FormatJson::FORCE_ASSOC );
		if ( !$jsonResult->isOK() ) {
			throw new RuntimeException( "Invalid JSON: $this->json" );
		}
		$itemResult = FormatJson::parse( $item, FormatJson::FORCE_ASSOC );
		if ( !$itemResult->isOK() ) {
			throw new RuntimeException( "Invalid JSON: $item" );
		}
		$isSame = new IsSame( $jsonResult->getValue() );
		return $isSame->matches( $itemResult->getValue() );
	}

	/**
	 * @since 0.1.0
	 * @param Description $description
	 */
	public function describeTo( Description $description ) {
		$description->appendText( 'a JSON string set to ' . $this->json );
	}

}
