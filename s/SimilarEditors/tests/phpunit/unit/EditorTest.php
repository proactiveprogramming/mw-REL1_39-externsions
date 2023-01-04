<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Unit;

use MediaWiki\Extension\SimilarEditors\Editor;
use MediaWikiUnitTestCase;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\Editor
 */
class EditorTest extends MediaWikiUnitTestCase {
	public function testDefaultValues() {
		$editor = new Editor();

		$this->assertNull( $editor->getUserText() );
		$this->assertNull( $editor->getNumEditsInData() );
		$this->assertNull( $editor->getFirstEditInData() );
		$this->assertNull( $editor->getLastEditInData() );
	}
}
