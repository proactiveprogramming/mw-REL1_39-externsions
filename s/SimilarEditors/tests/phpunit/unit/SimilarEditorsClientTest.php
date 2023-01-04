<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Unit;

use MediaWiki\Extension\SimilarEditors\Neighbor;
use MediaWiki\Extension\SimilarEditors\SimilarEditorsClient;
use MediaWiki\Extension\SimilarEditors\TimeOverlap;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiUnitTestCase;
use MWHttpRequest;
use Psr\Log\LoggerInterface;
use Status;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\SimilarEditorsClient
 */
class SimilarEditorsClientTest extends MediaWikiUnitTestCase {

	private function getClient( $data = null ) {
		$status = $this->createMock( Status::class );
		$status->method( 'isOK' )
			->willReturn( true );

		$request = $this->createMock( MWHttpRequest::class );
		$request->method( 'getContent' )
			->willReturn( $data );
		$request->method( 'execute' )
			->willReturn( $status );

		$httpRequestFactory = $this->createMock( HttpRequestFactory::class );
		$httpRequestFactory->method( 'create' )
			->willReturn( $request );

		$logger = $this->createMock( LoggerInterface::class );

		return new SimilarEditorsClient(
			$httpRequestFactory,
			$logger,
			'foo',
			'bar',
			'baz'
		);
	}

	public function testCreateClient() {
		$this->assertNotNull( $this->getClient() );
	}

	public function testGetEditor() {
		$this->assertNull( $this->getClient()->getEditor( 'Editor' ) );
	}

	public function testGetSimilarEditors() {
		$response = '{
			"first_edit_in_data": "2020-03-20 11:23:58 UTC",
			"last_edit_in_data": "2021-08-03 09:30:23 UTC",
			"num_edits_in_data": 7,
			"results": [
				{
					"day-overlap": {
						"cos-sim": 0,
						"level": "No overlap"
					},
					"edit-overlap": 0.4,
					"edit-overlap-inv": 1,
					"hour-overlap": {
						"cos-sim": 0,
						"level": "No overlap"
					},
					"num_edits_in_data": 2,
					"user_text": "SomeUser1"
				},
				{
					"day-overlap": {
						"cos-sim": 0,
						"level": "No overlap"
					},
					"edit-overlap": 0.4,
					"edit-overlap-inv": 1,
					"hour-overlap": {
						"cos-sim": 0,
						"level": "No overlap"
					},
					"num_edits_in_data": 2,
					"user_text": "SomeUser2"
				}
			],
			"user_text": "EditorWithSimilarEdits"
		}';

		$expected = [
			new Neighbor(
				'SomeUser1',
				2,
				0.40000000000000002,
				1,
				new TimeOverlap( 0.0, 'No overlap' ),
				new TimeOverlap( 0.0, 'No overlap' ),
				[]
			),
			new Neighbor(
				'SomeUser2',
				2,
				0.40000000000000002,
				1,
				new TimeOverlap( 0.0, 'No overlap' ),
				new TimeOverlap( 0.0, 'No overlap' ),
				[]
			),
		];
		$this->assertArrayEquals(
			$expected,
			$this->getClient( $response )->getSimilarEditors( 'EditorWithSimilarEdits' )
		);
	}

	public function testGetSimilarEditorsWillReturnNoResults() {
		$response = '{
			"first_edit_in_data": "2020-03-20 11:23:58 UTC",
			"last_edit_in_data": "2021-08-03 09:30:23 UTC",
			"num_edits_in_data": 7,
			"results": [],
			"user_text": "EditorWithNoResults"
		}';

		$expected = [];
		$this->assertArrayEquals(
			$expected,
			$this->getClient( $response )->getSimilarEditors( 'EditorWithNoResults' )
		);
	}

	public function testGetSimilarEditorsWillReturnError() {
		$this->assertSame(
			'',
			$this->getClient()->getSimilarEditors( 'Editor' )
		);
	}
}
