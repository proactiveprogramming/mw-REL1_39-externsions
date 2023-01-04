<?php

namespace GWToolset;

use MediaWikiIntegrationTestCase;

/**
 * @group GWToolset
 * @covers \GWToolset\Utils
 */
class GWToolsetUtilsTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var Utils
	 */
	protected $utils;

	protected function setUp(): void {
		parent::setUp();
		$this->utils = new Utils();
	}

	/**
	 * @covers \GWToolset\Utils::getArraySecondLevelValues
	 */
	public function test_getArraySecondLevelValues_empty() {
		$input = [ [] ];
		$expected = [];
		$this->assertEquals( $expected, $this->utils->getArraySecondLevelValues( $input ) );
	}

	/**
	 * @covers \GWToolset\Utils::getArraySecondLevelValues
	 */
	public function test_getArraySecondLevelValues() {
		$input = [ [ 1 ], [ 2 ], [ 3, 4, 5 ] ];
		$expected = [ 1, 2, 3, 4, 5 ];
		$this->assertEquals( $expected, $this->utils->getArraySecondLevelValues( $input ) );
	}

	/**
	 * @covers \GWToolset\Utils::getBytes
	 */
	public function test_getBytes_passthrough() {
		$this->assertSame( 1, $this->utils->getBytes( '1' ) );
	}

	/**
	 * @covers \GWToolset\Utils::getBytes
	 */
	public function test_getBytes_M() {
		$this->assertEquals( 1048576, $this->utils->getBytes( "1M" ) );
	}

	/**
	 * @covers \GWToolset\Utils::getBytes
	 */
	public function test_getBytes_K() {
		$this->assertEquals( 1024, $this->utils->getBytes( "1K" ) );
	}

	/**
	 * @covers \GWToolset\Utils::getBytes
	 */
	public function test_getBytes_G() {
		$this->assertEquals( 1073741824, $this->utils->getBytes( "1G" ) );
	}

	/**
	 * @covers \GWToolset\Utils::getNamespaceName
	 */
	public function test_getNamespaceName_empty() {
		$this->assertEquals( ':', $this->utils->getNamespaceName() );
	}

	/**
	 * @covers \GWToolset\Utils::getNamespaceName
	 */
	public function test_getNamespaceName_6() {
		$this->assertEquals( 'File:', $this->utils->getNamespaceName( 6 ) );
	}

	/**
	 * @covers \GWToolset\Utils::getNamespaceName
	 */
	public function test_getNamespaceName_not_string() {
		$this->assertNull( $this->utils->getNamespaceName( "Something" ) );
	}

	/**
	 * @covers \GWToolset\Utils::normalizeSpace
	 */
	public function test_normalizeSpace() {
		$this->assertEquals( "a_b_cd", $this->utils->normalizeSpace( "a b cd" ) );
	}

}
