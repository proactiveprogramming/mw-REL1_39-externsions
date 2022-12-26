<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * @since 0.1.0
 */
class ManuscriptEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param Manuscript $expected
	 * @param Manuscript $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsSame( 'name', $expected->getName(), $actual->getName() );
		$this->matchIsTimestampSame( 'created', $expected->getCreated(), $actual->getCreated() );
		$this->matchIsTimestampSame( 'disabled', $expected->getDisabled(), $actual->getDisabled() );
		$this->matchIsSame( 'language', $expected->getLanguage(), $actual->getLanguage() );
		$this->matchIsSame( 'domain', $expected->getDomain(), $actual->getDomain() );
	}

}
