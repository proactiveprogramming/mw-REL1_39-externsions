<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt
 * @since 0.1.0
 */
class ManuscriptPromptTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new ManuscriptPrompt();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitManuscriptPrompt( ManuscriptPrompt $manuscriptPrompt ) {
				return null;
			}
		};
	}

}
