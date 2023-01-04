<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\Recording
 * @since 0.1.0
 */
class RecordingTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new Recording();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitRecording(
				Recording $recording
			) {
				return null;
			}
		};
	}

}
