<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentListTest
 */
class SegmentListTest extends TestCase {
	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentListTest::add
	 */
	public function testCount() {
		$segmentList = new SegmentList();

		$this->assertSame( 0, $segmentList->count() );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentListTest::add
	 */
	public function testAdd() {
		$segment = new Segment( 0, 'Root title', 'dummy/file/path/segment-1.prep' );

		$segmentList = new SegmentList();
		$segmentList->add( $segment );

		$this->assertSame( 1, $segmentList->count() );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentListTest::item
	 */
	public function testItem() {
		$segment = new Segment( 0, 'Root title', 'dummy/file/path/segment-1.prep' );

		$segmentList = new SegmentList();
		$segmentList->add( $segment );

		$newLabel = $segmentList->item( 0 )->getLabel();

		$this->assertSame( 1, $segmentList->count() );
		$this->assertEquals( 'Root title', $newLabel );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SegmentListTest::replace
	 */
	public function testReplace() {
		$segment = new Segment( 0, 'Root title', 'dummy/file/path/segment-1.prep' );
		$newSegment = new Segment( 0, 'New root title', 'dummy/file/path/segment-1.prep' );

		$segmentList = new SegmentList();
		$segmentList->add( $segment );
		$status = $segmentList->replace( 0, $newSegment );

		$newLabel = $segmentList->item( 0 )->getLabel();

		$this->assertTrue( $status );
		$this->assertSame( 1, $segmentList->count() );
		$this->assertEquals( 'New root title', $newLabel );
	}
}
