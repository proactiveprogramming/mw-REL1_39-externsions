<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview
 * @since 0.1.0
 */
class RecordingReviewTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new RecordingReview();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitRecordingReview(
				RecordingReview $recordingReview
			) {
				return null;
			}
		};
	}

}
