<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;

/**
 * @since 0.1.0
 */
class UserLanguageProficiencyLevelEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param UserLanguageProficiencyLevel $expected
	 * @param UserLanguageProficiencyLevel $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsSame( 'user', $expected->getUser(), $actual->getUser() );
		$this->matchIsSame( 'language', $expected->getLanguage(), $actual->getLanguage() );
		$this->matchIsSame( 'proficiencyLevel',
			$expected->getProficiencyLevel(), $actual->getProficiencyLevel() );
	}

}
