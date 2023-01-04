<?php

namespace MediaWiki\Extension\Toolhub\Tests;

use GuzzleHttpRequest;
use MediaWiki\Extension\Toolhub\ApiClient;
use MediaWiki\Http\HttpRequestFactory;
use PHPUnit\Framework\TestCase;
use StatusValue;

/**
 * @covers \MediaWiki\Extension\Toolhub\ApiClient
 */
class ApiClientTest extends TestCase {

	/**
	 * @param int $status HTTP response status
	 * @param string|null $content Response body
	 * @return Mock HttpRequestFactory
	 */
	private function createMockHttpRequestFactory(
		$status,
		$content = null
	): HttpRequestFactory {
		$req = $this->createPartialMock(
			GuzzleHttpRequest::class,
			[ 'execute', 'getContent' ]
		);

		if ( $status > 0 && $status < 400 ) {
			$reqStatus = StatusValue::newGood( $status );
		} else {
			$reqStatus = StatusValue::newFatal( $status );
		}
		$req->expects( $this->once() )
			->method( 'execute' )
			->willReturn( $reqStatus );

		if ( $content ) {
			$req->expects( $this->once() )
				->method( 'getContent' )
				->willReturn( $content );
		} else {
			$req->expects( $this->never() )->method( 'getContent' );
		}

		$http = $this->createPartialMock(
			HttpRequestFactory::class,
			[ 'create' ]
		);
		$http->expects( $this->once() )->method( 'create' )->willReturn( $req );

		return $http;
	}

	/**
	 * @param int $status HTTP response status
	 * @param string|null $content Response body
	 * @return ApiClient
	 */
	private function getFixture( $status, $content ): ApiClient {
		return new ApiClient(
			$this->createMockHttpRequestFactory( $status, $content ),
			'https://toolhub.example.org'
		);
	}

	public function testMakeQueryString() {
		$params = [
			'q' => 'toolhub',
			'void' => null,
			'page' => 1,
			'ordering' => '-score',
			'page_size' => 25,
			'null' => null,
		];
		$this->assertEquals(
			'ordering=-score&page=1&page_size=25&q=toolhub',
			ApiClient::makeQueryString( $params )
		);
	}

	public function testGetToolByName() {
		$fixture = $this->getFixture( 200, '{"mock": "response"}' );
		$res = $fixture->getToolByName( 'test' );
		$this->assertNotNull( $res );
		$this->assertEquals( [ "mock" => "response" ], $res );
	}

	public function testGetListById() {
		$fixture = $this->getFixture( 200, '{"mock": "response"}' );
		$res = $fixture->getListById( 1 );
		$this->assertNotNull( $res );
		$this->assertEquals( [ "mock" => "response" ], $res );
	}

	public function testFindTools() {
		$fixture = $this->getFixture( 200, '{"mock": "response"}' );
		$res = $fixture->findTools( 'toolhub' );
		$this->assertNotNull( $res );
		$this->assertEquals( [ "mock" => "response" ], $res );
	}

}
