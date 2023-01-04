<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt
 * @since 0.1.0
 */
class SkippedManuscriptPromptTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new SkippedManuscriptPrompt();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitSkippedManuscriptPrompt(
				SkippedManuscriptPrompt $skippedManuscriptPrompt
			) {
				return null;
			}
		};
	}

}
