<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use MediaWiki\Extension\ImportOfficeFiles\SegmentsTitleBuilder;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;

/**
 * @group Database
 */
class SegmentsTitleBulderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @return void
	 */
	public function addDBData() {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$namespaces = [
			-2 => 'Media',
			-1 => 'Special',
			0 => '',
			1 => 'Talk',
			2 => 'User',
			3 => 'User_talk',
			4 => 'Project',
			5 => 'Project_talk',
			6 => 'File',
			7 => 'File_talk',
			8 => 'MediaWiki',
			9 => 'MediaWiki_talk',
			10 => 'Template',
			11 => 'Template_talk',
			12 => 'Help',
			13 => 'Help_talk',
			14 => 'Category',
			15 => 'Category_talk',
			99990 => 'Test',
			99991 => 'Test_talk'
		];

		$contLang->setNamespaces( $namespaces );

		$this->insertPage( 'Case/Dummy title 1 level 2', '', 99990 );
		$this->insertPage( 'Case/Dummy title 4 level 2', '', 99990 );
		$this->insertPage( 'Case/Dummy title 4 level 2 (1)', '', 99990 );
		$this->insertPage( 'Test2 Case/Dummy title 4 level 2', '', 0 );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentsTitleBuilder::buildTitleMap
	 */
	public function testBuildTitleMap() {
		$segments = $this->getSegments();

		$builder = new SegmentsTitleBuilder( $segments, 'Case', 'Test' );

		$actualMap = $builder->buildTitleMap();
		$expectedMap = $this->getExpectedMap();

		$this->assertEquals( $expectedMap, $actualMap );

		$builder = new SegmentsTitleBuilder( $segments, 'Case', 'Test', true );

		$actualMap = $builder->buildTitleMap();
		$expectedMap = $this->getExpectedMapWithCollision();

		$this->assertEquals( $expectedMap, $actualMap );

		$builder = new SegmentsTitleBuilder( $segments, 'Case', 'Test2', true );

		$actualMap = $builder->buildTitleMap();
		$expectedMap = $this->getExpectedMapWithNotExistingNamespace();

		$this->assertEquals( $expectedMap, $actualMap );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentsTitleBuilder::buildSegmentList
	 */
	public function testBuildSegmentList() {
		$segments = $this->getSegments();
		$builder = new SegmentsTitleBuilder( $segments, 'Case', 'Test' );
		$actualMap = $builder->buildSegmentList();
		$expectedList = $this->getExpectedSegments();

		$this->assertEquals( $expectedList, $actualMap );

		$builder = new SegmentsTitleBuilder( $segments, 'Case', 'Test', true );
		$actualMap = $builder->buildSegmentList();
		$expectedList = $this->getExpectedSegmentsWithCollision();

		$this->assertEquals( $expectedList, $actualMap );
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
			new Segment( 2, 'Dummy title 8 level 2', 'dummy/file/path/segment-9.prep' ),
		];

		$segmentList = new SegmentList();
		foreach ( $segments as $segment ) {
			$segmentList->add( $segment );
		}
		return $segmentList;
	}

	/**
	 * @return SegmentList
	 */
	private function getExpectedSegments(): SegmentList {
		$segments = [
			new Segment( 0, 'Test:Case',
				'dummy/file/path/segment-1.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 1 level 2',
				'dummy/file/path/segment-2.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 1 level 2/Dummy title 2 level 3',
				'dummy/file/path/segment-3.prep' ),
			new Segment( 4, 'Test:Case/Dummy title 1 level 2/Dummy title 2 level 3/Dummy title 3 level 4',
				'dummy/file/path/segment-4.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 4 level 2',
				'dummy/file/path/segment-5.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 4 level 2/Dummy title 5 level 3',
				'dummy/file/path/segment-6.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 4 level 2/Dummy title 6 level 3',
				'dummy/file/path/segment-7.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 7 level 2',
				'dummy/file/path/segment-8.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 8 level 2',
				'dummy/file/path/segment-9.prep' ),
		];

		$segmentList = new SegmentList();
		foreach ( $segments as $segment ) {
			$segmentList->add( $segment );
		}
		return $segmentList;
	}

	/**
	 * @return SegmentList
	 */
	private function getExpectedSegmentsWithCollision(): SegmentList {
		$segments = [
			new Segment( 0, 'Test:Case',
				'dummy/file/path/segment-1.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 1 level 2 (1)',
				'dummy/file/path/segment-2.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 1 level 2 (1)/Dummy title 2 level 3',
				'dummy/file/path/segment-3.prep' ),
			new Segment( 4, 'Test:Case/Dummy title 1 level 2 (1)/Dummy title 2 level 3/Dummy title 3 level 4',
				'dummy/file/path/segment-4.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 4 level 2 (2)',
				'dummy/file/path/segment-5.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 4 level 2 (2)/Dummy title 5 level 3',
				'dummy/file/path/segment-6.prep' ),
			new Segment( 3, 'Test:Case/Dummy title 4 level 2 (2)/Dummy title 6 level 3',
				'dummy/file/path/segment-7.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 7 level 2',
				'dummy/file/path/segment-8.prep' ),
			new Segment( 2, 'Test:Case/Dummy title 8 level 2',
				'dummy/file/path/segment-9.prep' ),
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
			"Test:Case",
			"Test:Case/Dummy title 1 level 2",
			"Test:Case/Dummy title 1 level 2/Dummy title 2 level 3",
			"Test:Case/Dummy title 1 level 2/Dummy title 2 level 3/Dummy title 3 level 4",
			"Test:Case/Dummy title 4 level 2",
			"Test:Case/Dummy title 4 level 2/Dummy title 5 level 3",
			"Test:Case/Dummy title 4 level 2/Dummy title 6 level 3",
			"Test:Case/Dummy title 7 level 2",
			"Test:Case/Dummy title 8 level 2"
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedMapWithCollision(): array {
		return [
			"Test:Case",
			"Test:Case/Dummy title 1 level 2 (1)",
			"Test:Case/Dummy title 1 level 2 (1)/Dummy title 2 level 3",
			"Test:Case/Dummy title 1 level 2 (1)/Dummy title 2 level 3/Dummy title 3 level 4",
			"Test:Case/Dummy title 4 level 2 (2)",
			"Test:Case/Dummy title 4 level 2 (2)/Dummy title 5 level 3",
			"Test:Case/Dummy title 4 level 2 (2)/Dummy title 6 level 3",
			"Test:Case/Dummy title 7 level 2",
			"Test:Case/Dummy title 8 level 2"
		];
	}

	/**
	 * @return array
	 */
	private function getExpectedMapWithNotExistingNamespace(): array {
		return [
			"Test2 Case",
			"Test2 Case/Dummy title 1 level 2",
			"Test2 Case/Dummy title 1 level 2/Dummy title 2 level 3",
			"Test2 Case/Dummy title 1 level 2/Dummy title 2 level 3/Dummy title 3 level 4",
			"Test2 Case/Dummy title 4 level 2 (1)",
			"Test2 Case/Dummy title 4 level 2 (1)/Dummy title 5 level 3",
			"Test2 Case/Dummy title 4 level 2 (1)/Dummy title 6 level 3",
			"Test2 Case/Dummy title 7 level 2",
			"Test2 Case/Dummy title 8 level 2"
		];
	}
}
