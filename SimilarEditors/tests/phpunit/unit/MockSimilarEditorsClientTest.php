<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Unit;

use MediaWiki\Extension\SimilarEditors\MockSimilarEditorsClient;
use MediaWiki\Extension\SimilarEditors\Neighbor;
use MediaWiki\Extension\SimilarEditors\TimeOverlap;
use MediaWikiUnitTestCase;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\MockSimilarEditorsClient
 */
class MockSimilarEditorsClientTest extends MediaWikiUnitTestCase {

	/**
	 * @return MockSimilarEditorsClient
	 */
	private function getClient(): MockSimilarEditorsClient {
		return new MockSimilarEditorsClient();
	}

	public function testCreate() {
		$this->assertNotNull( $this->getClient() );
	}

	public function testGetEditor() {
		$this->assertNull( $this->getClient()->getEditor( 'Editor' ) );
	}

	public function testGetSimilarEditors() {
		$client = $this->getClient();
		$neighbors = [
			new Neighbor(
				'SomeUser1',
				100,
				0.2,
				0.4,
				new TimeOverlap( 0.6, 'medium' ),
				new TimeOverlap( 0.8, 'high' ),
				[]
			),
			new Neighbor(
				'SomeUser2',
				500,
				0.7,
				0.5,
				new TimeOverlap( 0.3, 'low' ),
				new TimeOverlap( 0.1, 'low' ),
				[]
			),
		];
		$this->assertArrayEquals( $neighbors, $client->getSimilarEditors( 'Editor' ) );
	}
}
