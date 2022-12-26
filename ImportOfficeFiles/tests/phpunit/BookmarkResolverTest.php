<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\BookmarkResolver;
use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\BookmarkResolver
 */
class BookmarkResolverTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\BookmarkResolver::resolve
	 */
	public function testResolve() {
		$bookmarkResolver = new BookmarkResolver();
		$inputSegments = $this->getInputSegmentList();
		$actualSegments = $bookmarkResolver->resolve( $inputSegments );

		$expectedSegments = $this->getExpectedSegmentList();

		$this->assertEquals( $expectedSegments->count(), $actualSegments->count() );

		for ( $index = 0; $index < $actualSegments->count(); $index++ ) {
			$actualFilePath = $actualSegments->item( $index )->getFilePath();
			$actualContent = file_get_contents( $actualFilePath );

			$expectedFilePath = $expectedSegments->item( $index )->getFilePath();
			$expectedContent = file_get_contents( $expectedFilePath );

			$this->assertEquals( $expectedContent, $actualContent );
		}
	}

	/**
	 * @return SegmentList
	 */
	private function getInputSegmentList(): SegmentList {
		$segmentList = $this->buildSegmentList( 'input' );

		return $segmentList;
	}

	/**
	 * @return SegmentList
	 */
	private function getExpectedSegmentList(): SegmentList {
		$segmentList = $this->buildSegmentList( 'output' );

		return $segmentList;
	}

	/**
	 * @param string $type
	 * @return SegmentList
	 */
	private function buildSegmentList( $type ): SegmentList {
		$segmentList = new SegmentList();
		$tempDir = wfTempDir();

		$content = file_get_contents( __DIR__ . "/data/BookmarkResolver/part-1.$type.wikitext" );
		file_put_contents( "$tempDir/part-1.$type.wikitext", $content );
		$segmentList->add( new Segment(
			0, 'Test:My Test', "$tempDir/part-1.$type.wikitext"
		) );

		$content = file_get_contents( __DIR__ . "/data/BookmarkResolver/part-2.$type.wikitext" );
		file_put_contents( "$tempDir/part-2.$type.wikitext", $content );
		$segmentList->add( new Segment(
			2, 'Test:My Test/First', "$tempDir/part-2.$type.wikitext"
		) );

		$content = file_get_contents( __DIR__ . "/data/BookmarkResolver/part-3.$type.wikitext" );
		file_put_contents( "$tempDir/part-3.$type.wikitext", $content );
		$segmentList->add( new Segment(
			3, 'Test:My Test/First/Sub', "$tempDir/part-3.$type.wikitext"
		) );

		$content = file_get_contents( __DIR__ . "/data/BookmarkResolver/part-4.$type.wikitext" );
		file_put_contents( "$tempDir/part-4.$type.wikitext", $content );
		$segmentList->add( new Segment(
			2, 'Test:My Test/Second', "$tempDir/part-4.$type.wikitext"
		) );

		return $segmentList;
	}

}
