<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;

/**
 * @since 0.1.0
 */
class SkippedManuscriptPromptEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param SkippedManuscriptPrompt $expected
	 * @param SkippedManuscriptPrompt $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsTimestampSame( 'skipped', $expected->getSkipped(), $actual->getSkipped() );
		$this->matchIsSame( 'user', $expected->getUser(), $actual->getUser() );
		$this->matchIsSame( 'manuscriptPrompt',
			$expected->getManuscriptPrompt(), $actual->getManuscriptPrompt() );
	}

}
