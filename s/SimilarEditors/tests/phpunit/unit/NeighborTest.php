<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Unit;

use MediaWiki\Extension\SimilarEditors\Neighbor;
use MediaWiki\Extension\SimilarEditors\TimeOverlap;
use MediaWikiUnitTestCase;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\Neighbor
 */
class NeighborTest extends MediaWikiUnitTestCase {
	public function testDefaultValues() {
		$neighbor = new Neighbor(
			'',
			0,
			0,
			0,
			new TimeOverlap( 0, '' ),
			new TimeOverlap( 0, '' ),
			[]
		);

		$this->assertNotNull( $neighbor->getUserText() );
		$this->assertNotNull( $neighbor->getNumEditsInData() );
		$this->assertNotNull( $neighbor->getEditOverlap() );
		$this->assertNotNull( $neighbor->getEditOverlapInv() );
		$this->assertNotNull( $neighbor->getDayOverlap() );
		$this->assertNotNull( $neighbor->getHourOverlap() );
		$this->assertNotNull( $neighbor->getFollowUp() );
	}
}
