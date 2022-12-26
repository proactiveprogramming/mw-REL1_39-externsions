<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests;

use MediaWiki\Extension\ImportOfficeFiles\CollectionBuilder;
use MediaWiki\Extension\ImportOfficeFiles\Segment;
use MediaWiki\Extension\ImportOfficeFiles\SegmentList;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\CollectionBuilder
 */
class CollectionBuilderTest extends TestCase {
	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

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
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();
		// reset custom namespace settings
		$contLang->resetNamespaces();
		$contLang->getNamespaces();
		parent::tearDown();
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\CollectionBuilder::build
	 */
	public function testBuild() {
		$builder = new CollectionBuilder();
		$actual = $builder->build( $this->getSegmentList() );
		$expected = $this->getExpected();

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * @return SegmentList
	 */
	private function getSegmentList(): SegmentList {
		$segmentList = new SegmentList();
		$segmentList->add( new Segment(
			0, 'Test:My Test', 'dummy/path/part-1.abc'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/First', 'dummy/path/part-2.abc'
		) );
		$segmentList->add( new Segment(
			3, 'Test:My Test/First/Sub', 'dummy/path/part-3.abc'
		) );
		$segmentList->add( new Segment(
			2, 'Test:My Test/Second', 'dummy/path/part-4.abc'
		) );
		$segmentList->add( new Segment(
			3, 'Test:My Test/Second/Sub', 'dummy/path/part-5.abc'
		) );
		$segmentList->add( new Segment(
			4, 'Test:My Test/Second/Sub/Sub sub', 'dummy/path/part-6.abc'
		) );

		return $segmentList;
	}

	/**
	 * @return string
	 */
	private function getExpected(): string {
		$wikiText = "* [[Test:My Test|My Test]]\n";
		$wikiText .= "* [[Test:My Test/First|First]]\n";
		$wikiText .= "** [[Test:My Test/First/Sub|Sub]]\n";
		$wikiText .= "* [[Test:My Test/Second|Second]]\n";
		$wikiText .= "** [[Test:My Test/Second/Sub|Sub]]\n";
		$wikiText .= "*** [[Test:My Test/Second/Sub/Sub sub|Sub sub]]\n";
		return $wikiText;
	}
}
