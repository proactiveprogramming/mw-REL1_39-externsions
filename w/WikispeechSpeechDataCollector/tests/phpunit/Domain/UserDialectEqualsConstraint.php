<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;

/**
 * @since 0.1.0
 */
class UserDialectEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param UserDialect $expected
	 * @param UserDialect $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsSame( 'user', $expected->getUser(), $actual->getUser() );
		$this->matchIsSame( 'language', $expected->getLanguage(), $actual->getLanguage() );
		$this->matchIsSame( 'spokenProficiencyLevel',
			$expected->getSpokenProficiencyLevel(), $actual->getSpokenProficiencyLevel() );
		$this->matchIsJsonSame( 'location', $expected->getLocation(), $actual->getLocation() );
	}

}
