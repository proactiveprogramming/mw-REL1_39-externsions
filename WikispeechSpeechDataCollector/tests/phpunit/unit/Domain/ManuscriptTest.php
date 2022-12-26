<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitorAdapter;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript
 * @since 0.1.0
 */
class ManuscriptTest extends AbstractPersistentTest {
	protected function instanceFactory(): Persistent {
		return new Manuscript();
	}

	protected function visitorTestFactory(): PersistentVisitorAdapter {
		return new class extends PersistentVisitorAdapter {
			public function visitManuscript( Manuscript $manuscript ) {
				return null;
			}
		};
	}

}
