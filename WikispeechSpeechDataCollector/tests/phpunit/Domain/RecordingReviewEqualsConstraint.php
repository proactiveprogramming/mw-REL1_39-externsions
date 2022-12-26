<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;

/**
 * @since 0.1.0
 */
class RecordingReviewEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param RecordingReview $expected
	 * @param RecordingReview $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		$this->matchIsTimestampSame( 'created', $expected->getCreated(), $actual->getCreated() );
		$this->matchIsSame( 'value', $expected->getValue(), $actual->getValue() );
		$this->matchIsSame( 'reviewer', $expected->getReviewer(), $actual->getReviewer() );
		$this->matchIsSame( 'recording', $expected->getRecording(), $actual->getRecording() );
	}

}
