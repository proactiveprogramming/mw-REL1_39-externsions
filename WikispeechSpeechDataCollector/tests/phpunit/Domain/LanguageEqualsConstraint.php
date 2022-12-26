<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * @since 0.1.0
 */
class LanguageEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param Language $expected
	 * @param Language $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsSame( 'nativeName', $expected->getNativeName(), $actual->getNativeName() );
		$this->matchIsSame( 'iso639a1', $expected->getIso639a1(), $actual->getIso639a1() );
		$this->matchIsSame( 'iso639a2b', $expected->getIso639a2b(), $actual->getIso639a2b() );
		$this->matchIsSame( 'iso639a2t', $expected->getIso639a2t(), $actual->getIso639a2t() );
		$this->matchIsSame( 'iso639a3', $expected->getIso639a3(), $actual->getIso639a3() );
	}

}
