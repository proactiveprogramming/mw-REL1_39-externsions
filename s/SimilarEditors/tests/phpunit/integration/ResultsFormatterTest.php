<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Integration;

use Language;
use MediaWiki\Extension\SimilarEditors\Neighbor;
use MediaWiki\Extension\SimilarEditors\ResultsFormatter;
use MediaWiki\Extension\SimilarEditors\TimeOverlap;
use MediaWiki\User\UserFactory;
use MediaWikiIntegrationTestCase;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\ResultsFormatter
 */
class ResultsFormatterTest extends MediaWikiIntegrationTestCase {

	public function testFormatResults() {
		$user = $this->createMock( User::class );
		$user->method( 'getId' )
			->willReturn( 0 );
		$user->method( 'getName' )
			->willReturn( '1.2.3.4' );

		$userFactory = $this->createMock( UserFactory::class );
		$userFactory->method( 'newFromName' )
			->willReturn( $user );

		$language = $this->createMock( Language::class );

		$resultsFormatter = new ResultsFormatter(
			$userFactory,
			$language
		);
		$results = $resultsFormatter->formatResults(
			'targetUser',
			[
				$this->createMock( Neighbor::class ),
				$this->createMock( Neighbor::class ),
			]
		);

		$this->assertStringContainsString(
			'<table class="mw-datatable sortable">',
			$results,
			'no table element, or incorrect classes'
		);
		$this->assertStringContainsString(
			'<thead><tr><th>',
			$results,
			'thead element should contain row and cells'
		);
		$this->assertStringContainsString(
			'<tbody><tr><td>',
			$results,
			'tbody element should contain row and cells'
		);
		$this->assertStringContainsString(
			'</tr><tr><td>',
			$results,
			'table should contain second row and cells'
		);
	}

	/**
	 * @dataProvider provideFormatRowProperty
	 */
	public function testFormatRowProperty( $expected, $input ) {
		$userFactory = $this->createMock( UserFactory::class );
		$language = $this->createMock( Language::class );
		$user = $this->createMock( User::class );

		$userFactory->method( 'newFromName' )
			->willReturn( $user );
		$user->method( 'getName' )
			->willReturn( 'SomeUser' );

		$wrapper = TestingAccessWrapper::newFromObject( new ResultsFormatter( $userFactory, $language ) );
		$neighbor = new Neighbor(
			'SomeUser',
			100,
			0.2,
			0.4,
			new TimeOverlap( 0.6, 'medium' ),
			new TimeOverlap( 0.8, 'high' ),
			[]
		);

		$this->assertSame( $expected, $wrapper->formatRowProperty( 'targetUser', $neighbor, $input ) );
	}

	public function provideFormatRowProperty() {
		return [
			'edits' => [ '100' , 'edits' ],
			'edit-overlap' => [ '0.2' , 'edit-overlap' ],
			'hour-overlap' => [ 'High' , 'hour-overlap' ],
			'day-overlap' => [ 'Medium' , 'day-overlap' ],
			'inverse-edit-overlap' => [ '0.4' , 'inverse-edit-overlap' ],
			'user' => [
				'<span class="mw-userlink mw-extuserlink mw-anonuserlink"><bdi>en&gt;SomeUser</bdi></span> ' .
				'(<a class="external" rel="nofollow" href="https://interaction-timeline.toolforge.org/' .
				'?wiki=enwiki&amp;user=targetUser&amp;user=SomeUser">timeline</a>)',
				'user'
			]
		];
	}
}
