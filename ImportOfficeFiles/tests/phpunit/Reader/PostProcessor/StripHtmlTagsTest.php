<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\PostProcessor;

use MediaWiki\Extension\ImportOfficeFiles\Reader\PostProcessor\StripHtmlTags;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\PostProcessor\StripHtmlTags
 */
class StripHtmlTagsTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\PostProcessor\StripHtmlTags::process()
	 */
	public function testExternalLink() {
		$wikitext = '[https://www.google.com.ua/ ';
		$wikitext .= '<span style=\'color: #1155cc;background-color: #b7b7b7;\'>Some link</span>]';

		$postProcessor = new StripHtmlTags();

		$actualWikitext = $postProcessor->process( $wikitext );
		$expectedWikitext = '[https://www.google.com.ua/ Some link]';

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\PostProcessor\StripHtmlTags::process()
	 */
	public function testInternalLink() {
		$wikitext = '[[#_onnpgudyhc4g|<span style=\'color: #1155cc;\'>Some internal link</span>]]';

		$postProcessor = new StripHtmlTags();

		$actualWikitext = $postProcessor->process( $wikitext );
		$expectedWikitext = '[[#_onnpgudyhc4g|Some internal link]]';

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\PostProcessor\StripHtmlTags::process()
	 */
	public function testDisplayTitle() {
		$wikitext = '{{DISPLAYTITLE:<span style=\'color: #ffff00;\'>\'\'\'\'\'Title with styles\'\'\'\'\'</span>}}';

		$postProcessor = new StripHtmlTags();

		$actualWikitext = $postProcessor->process( $wikitext );
		$expectedWikitext = '{{DISPLAYTITLE:Title with styles}}';

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

}
