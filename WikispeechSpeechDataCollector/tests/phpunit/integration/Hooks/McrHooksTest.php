<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Hooks\McrHooks
 * @since 0.1.0
 */
class McrHooksTest extends MediaWikiIntegrationTestCase {

	public function testSlotRoleRegistry_getMcrHooks_exists() {
		$definedRoles = MediaWikiServices::getInstance()->getSlotRoleRegistry()->getDefinedRoles();
		$this->assertContains( RecordingAnnotationsCrud::SLOT_ROLE, $definedRoles );
	}

}
