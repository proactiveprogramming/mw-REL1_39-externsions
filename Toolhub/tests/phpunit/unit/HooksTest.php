<?php

namespace MediaWiki\Extension\Toolhub\Tests;

use MediaWiki\Extension\Toolhub\CoreLibrary;
use MediaWiki\Extension\Toolhub\Hooks;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Toolhub\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	public function testOnScribuntoExternalLibraries() {
		$hooks = new Hooks();
		$extraLibraries = [];

		$hooks->onScribuntoExternalLibraries( 'not-lua', $extraLibraries );
		$this->assertArrayEquals( [], $extraLibraries );

		$hooks->onScribuntoExternalLibraries( 'lua', $extraLibraries );
		$this->assertArrayHasKey( 'mw.ext.toolhub', $extraLibraries );
		$lib = $extraLibraries[ 'mw.ext.toolhub' ];
		$this->assertArrayHasKey( 'class', $lib );
		$this->assertArrayHasKey( 'deferLoad', $lib );
		$this->assertEquals( CoreLibrary::class, $lib[ 'class' ] );
		$this->assertTrue( $lib[ 'deferLoad' ] );
	}

}
