<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\User
 * @since 0.1.0
 */
class UserTest extends AbstractPersistentTest {

	protected function instanceFactory(): Persistent {
		return new User();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitUser(
				User $user
			) {
				return null;
			}
		};
	}

}
