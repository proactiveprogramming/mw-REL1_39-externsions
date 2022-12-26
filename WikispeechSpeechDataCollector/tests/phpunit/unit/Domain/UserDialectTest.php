<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect
 * @since 0.1.0
 */
class UserDialectTest extends AbstractPersistentTest {
	protected function instanceFactory(): Persistent {
		return new UserDialect();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitUserDialect(
				UserDialect $userDialect
			) {
				return null;
			}
		};
	}

}
