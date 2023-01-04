<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use MediaWiki\Extension\ImportOfficeFiles\WikiPageStructureBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiPageStructureBuilder
 */
class WikiPageStructureBuilderTest extends TestCase {
	/**
	 * @var string
	 */
	private $srcDir = '';

	/**
	 * @var string
	 */
	private $destDir = '';

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiPageStructureBuilder::build
	 */
	public function testBuild() {
		$this->srcDir = __DIR__ . '/data/WikiPageStructureBuilder';

		$this->destDir = wfTempDir();

		$builder = new WikiPageStructureBuilder();
		$actual = $builder->build(
			$this->destDir,
			$this->getSegmentList(),
			2
		);

		$expected = $this->getExpectedSegmentList();

		$this->assertEquals( $expected, $actual );

		$actual = $builder->build(
			$this->destDir,
			$this->getSegmentList(),
			0
		);

		$expected = $this->getExpectedSegmentListH1();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return SegmentList
	 */
	private function getSegmentList(): SegmentList {
		$segmentList = new SegmentList();
		$segmentList->add( new Segment(
			0, 'Test:My Test', $this->srcDir . '/part-1.abc'
		) );
		$segmentList->add( new Segment(
			1, 'Test:My Test/First', $this->srcDir . '/part-2.abc'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/First/Sub', $this->srcDir . '/part-3.abc'
		) );
		$segmentList->add( new Segment(
			1, 'Test:My Test/Second', $this->srcDir . '/part-4.abc'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/Second/Sub', $this->srcDir . '/part-5.abc'
		) );
		$segmentList->add( new Segment(
			3, 'Test:My Test/Second/Sub/Sub sub', $this->srcDir . '/part-6.abc'
		) );

		return $segmentList;
	}

	/**
	 * @return SegmentList
	 */
	private function getExpectedSegmentList(): SegmentList {
		$segmentList = new SegmentList();
		$segmentList->add( new Segment(
			0, 'Test:My Test', $this->destDir . '/part-1.structure'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/First/Sub', $this->destDir . '/part-2.structure'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/Second/Sub', $this->destDir . '/part-3.structure'
		) );

		return $segmentList;
	}

	/**
	 * @return SegmentList
	 */
	private function getExpectedSegmentListH1(): SegmentList {
		$segmentList = new SegmentList();
		$segmentList->add( new Segment(
			0, 'Test:My Test', $this->destDir . '/part-1.structure'
		) );

		return $segmentList;
	}
}
