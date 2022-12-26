<?php

namespace MediaWiki\Extension\SimilarEditors\Test\Unit;

use MediaWiki\Extension\SimilarEditors\TimeOverlap;
use MediaWikiUnitTestCase;

/**
 * @group SimilarEditors
 * @covers \MediaWiki\Extension\SimilarEditors\TimeOverlap
 */
class TimeOverlapTest extends MediaWikiUnitTestCase {

	public function testDefaultValues() {
		$timeOverlap = new TimeOverlap( 1.134, 'foo' );
		$this->assertNotNull( $timeOverlap );
		$this->assertSame( 1.134, $timeOverlap->getCosineSimilarity() );
		$this->assertSame( 'foo', $timeOverlap->getLevel() );
	}

}
