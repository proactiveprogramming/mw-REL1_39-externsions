<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use MediaWiki\Extension\ImportOfficeFiles\TitleMapBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\TitleMapBulderTest
 */
class TitleMapBulderTest extends TestCase {
	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\TitleMapBulderTest::build
	 */
	public function testBuild() {
		$titleMapBuilder = new TitleMapBuilder();
		$segments = $this->getSegments();
		$actualMap = $titleMapBuilder->build( $segments, 'TestCase', 'Test' );
		$expectedMap = $this->getExpectedMap();

		$this->assertEquals( $expectedMap, $actualMap );
	}

	/**
	 * @return SegmentList
	 */
	private function getSegments(): SegmentList {
		$segments = [
			new Segment( 0, 'Root title', 'dummy/file/path/segment-1.prep' ),
			new Segment( 2, 'Dummy title 1 level 2', 'dummy/file/path/segment-2.prep' ),
			new Segment( 3, 'Dummy title 2 level 3', 'dummy/file/path/segment-3.prep' ),
			new Segment( 4, 'Dummy title 3 level 4', 'dummy/file/path/segment-4.prep' ),
			new Segment( 2, 'Dummy title 4 level 2', 'dummy/file/path/segment-5.prep' ),
			new Segment( 3, 'Dummy title 5 level 3', 'dummy/file/path/segment-6.prep' ),
			new Segment( 3, 'Dummy title 6 level 3', 'dummy/file/path/segment-7.prep' ),
			new Segment( 2, 'Dummy title 7 level 2', 'dummy/file/path/segment-8.prep' ),
			new Segment( 2, 'Dummy title 8 level 2', 'dummy/file/path/segment-9.prep' )
		];

		$segmentList = new SegmentList();
		foreach ( $segments as $segment ) {
			$segmentList->add( $segment );
		}
		return $segmentList;
	}

	/**
	 * @return array
	 */
	private function getExpectedMap(): array {
		return [
			"Test:TestCase",
			"Test:TestCase/Dummy title 1 level 2",
			"Test:TestCase/Dummy title 1 level 2/Dummy title 2 level 3",
			"Test:TestCase/Dummy title 1 level 2/Dummy title 2 level 3/Dummy title 3 level 4",
			"Test:TestCase/Dummy title 4 level 2",
			"Test:TestCase/Dummy title 4 level 2/Dummy title 5 level 3",
			"Test:TestCase/Dummy title 4 level 2/Dummy title 6 level 3",
			"Test:TestCase/Dummy title 7 level 2",
			"Test:TestCase/Dummy title 8 level 2"
		];
	}
}
