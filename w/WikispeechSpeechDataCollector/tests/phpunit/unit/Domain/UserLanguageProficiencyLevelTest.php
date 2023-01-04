<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel
 * @since 0.1.0
 */
class UserLanguageProficiencyLevelTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new UserLanguageProficiencyLevel();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitUserLanguageProficiencyLevel(
				UserLanguageProficiencyLevel $userLanguageProficiencyLevel
			) {
				return null;
			}
		};
	}

}
