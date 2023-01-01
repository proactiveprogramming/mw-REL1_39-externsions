<?php

namespace jsoner;

class SuperTest extends \PHPUnit_Framework_TestCase
{
	private $config;
	private $requestCache = [];

	protected function setUp() {
		$this->config = new FakeMWConfig( [
			"BaseUrl" => null,
			"User" => null,
			"Pass" => null,
		] );
	}

	public function testJsonerConstruct() {
		$options = [];
		$jsoner = new Jsoner( $this->config, $options );

		$this->assertNotNull( $jsoner );
	}

	public function testBasic() {
		$options = [
			'url' => TestUtil::makeIntegrationTestUrl( __FUNCTION__ ),
			't-JsonDump' => null
		];
		$output = ( new Jsoner( $this->config, $options ) )->run($this->requestCache);

		$this->assertContains( 'test', $output );
	}

	public function testOrderOfSelect1() {
		$options = [
			'url' => TestUtil::makeIntegrationTestUrl( __FUNCTION__ ),
			'f-SelectKeys' => 'email,name',
			't-JsonDump' => null
		];
		$output = ( new Jsoner( $this->config, $options ) )->run($this->requestCache);
		$this->assertRegExp( '/email.*name/s', $output );
	}

	public function testOrderOfSelect2() {
		$options = [
			'url' => TestUtil::makeIntegrationTestUrl( __FUNCTION__ ),
			'f-SelectKeys' => 'name,email',
			't-JsonDump' => null
		];
		$output = ( new Jsoner( $this->config, $options ) )->run($this->requestCache);
		$this->assertRegExp( '/name.*email/s', $output );
	}

	public function testPokeApi() {
		$options = [
			'url' => TestUtil::makeIntegrationTestUrl( __FUNCTION__ ),
			'f-SelectSubtree' => 'stats',
			'f-SelectRecord' => 'base_stat:65',
			't-JsonDump' => null
		];
		$output = ( new Jsoner( $this->config, $options ) )->run($this->requestCache);
//		$this->assertTrue(is_array($output));
//		$this->assertEquals(2, count($output));
		$this->assertRegExp( '/special-defense/s', $output );
		$this->assertRegExp( '/special-attack/s', $output );
	}
}

























