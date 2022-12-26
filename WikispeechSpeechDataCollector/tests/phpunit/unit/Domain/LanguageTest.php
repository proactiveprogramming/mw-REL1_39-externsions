<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\Language
 * @since 0.1.0
 */
class LanguageTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new Language();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitLanguage( Language $language ) {
				return null;
			}
		};
	}
}
