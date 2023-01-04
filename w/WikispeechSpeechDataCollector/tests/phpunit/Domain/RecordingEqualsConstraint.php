<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;

/**
 * @since 0.1.0
 */
class RecordingEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param Recording $expected
	 * @param Recording $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsTimestampSame( 'recorded', $expected->getRecorded(), $actual->getRecorded() );
		$this->matchIsSame( 'voiceOf', $expected->getVoiceOf(), $actual->getVoiceOf() );
		$this->matchIsSame( 'spokenDialect',
			$expected->getSpokenDialect(), $actual->getSpokenDialect() );
		$this->matchIsSame( 'manuscriptPrompt',
			$expected->getManuscriptPrompt(), $actual->getManuscriptPrompt() );
		$this->matchIsSame( 'audioFileWikiPageIdentity',
			$expected->getAudioFileWikiPageIdentity(), $actual->getAudioFileWikiPageIdentity() );
	}

}
