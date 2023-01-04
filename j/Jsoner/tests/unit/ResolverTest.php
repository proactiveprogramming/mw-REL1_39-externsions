<?php

namespace jsoner;

use jsoner\exceptions\CurlException;
use jsoner\exceptions\HttpUriFormatException;

class ResolverTest extends \PHPUnit_Framework_TestCase
{
	private $emptyConfig;

	protected function setUp() {

		$this->emptyConfig = new Config();
	}

	public function testThatInvalidFullUrlsThrowException() {

		$this->expectException( CurlException::class );

		$resolver = new Resolver( $this->emptyConfig, "http://example.invalid/" );
		$resolver->resolve();
	}

	public function testThatInvalidUrlsThrowException() {
		$this->expectException( CurlException::class );
		$config = new Config( ["BaseUrl" => "https://example.invalid/"] );

		$resolver = new Resolver( $config, "this/path/is/invalid/" );
		$resolver->resolve();
	}

	public function validTestData() {

		return [
				[null, 'http://example.com/absCleanParams?a=c',
						'http://example.com/absCleanParams?a=c'],

				[null, 'http://example.com/absDirtyParams?a= c',
						'http://example.com/absDirtyParams?a=%20c'],

				[null, 'http://example.com/absDirtyParams/?key="super Value"',
						'http://example.com/absDirtyParams/?key=%22super%20Value%22'],

				['http://example.com', '/relClean/',
						'http://example.com/relClean/'],

				['http://example.com', '/relCleanParams?id=4',
						'http://example.com/relCleanParams?id=4'],

				['http://example.com', '/relDirtyParams?id=  4',
						'http://example.com/relDirtyParams?id=%20%204'],

				['http://example.com', '/relDirtyParams2?id=shoop!da\'woop\\',
						'http://example.com/relDirtyParams2?id=shoop%21da%27woop%5C'],

				['https://example.com', '/relClean/',
						'https://example.com/relClean/'],

				['https://example.com', '/relCleanParams?id=4',
						'https://example.com/relCleanParams?id=4'],

				['https://example.com', '/relDirtyParams?id=  4',
						'https://example.com/relDirtyParams?id=%20%204'],

				['https://example.com', '/relDirtyParams2?id=shoop!da\'woop\\',
						'https://example.com/relDirtyParams2?id=shoop%21da%27woop%5C'],

		];
	}

	/**
	 * @dataProvider validTestData
	 */
	public function testValidBuildUrl( $baseUrl, $queryOrFullUrl, $expected ) {
		$config = new Config( ['BaseUrl' => $baseUrl] );

		$resolver = new Resolver( $config, $queryOrFullUrl );
		$this->assertEquals( $expected, $resolver->buildUrl() );
	}

	public function invalidTestData() {

		return [
				[null, '/relativePathWithoutParams/'],
				[null, '/relativePathWithParams?a=bc'],
				[null, '/relativePathWithParams?a= b c '],
				[null, '/relativePathWithParams?a=!!!bc"!'],
				[null, null],
				[null, 'ftp://example.com/onlyhttpatthemoment'],
		];
	}

	/**
	 * @dataProvider invalidTestData
	 */
	public function testInvalidBuildUrl( $baseUrl, $queryOrFullUrl ) {
		$this->expectException( HttpUriFormatException::class );

		$config = new Config( ['BaseUrl' => $baseUrl] );

		$resolver = new Resolver( $config, $queryOrFullUrl );
		$resolver->resolve();
	}
}
