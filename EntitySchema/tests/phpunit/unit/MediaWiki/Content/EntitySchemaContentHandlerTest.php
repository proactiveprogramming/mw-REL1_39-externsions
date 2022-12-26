<?php

namespace EntitySchema\Tests\Unit\MediaWiki\Content;

use EntitySchema\MediaWiki\Content\EntitySchemaContentHandler;
use MediaWikiUnitTestCase;

/**
 * @covers \EntitySchema\MediaWiki\Content\EntitySchemaContentHandler
 *
 * @license GPL-2.0-or-later
 */
class EntitySchemaContentHandlerTest extends MediaWikiUnitTestCase {

	public function testSupportsDirectApiEditing() {
		$contentHandler = new EntitySchemaContentHandler();

		$this->assertFalse( $contentHandler->supportsDirectApiEditing() );
	}

	public function testIsParserCacheSupported() {
		$contentHandler = new EntitySchemaContentHandler();

		$this->assertTrue( $contentHandler->isParserCacheSupported() );
	}

}
