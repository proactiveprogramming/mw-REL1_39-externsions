<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\PostProcessor;

use MediaWiki\Extension\ImportOfficeFiles\SanitizeHeading;
use PHPUnit\Framework\TestCase;

// phpcs:disable Generic.Files.LineLength.TooLong
/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\SanitizeHeadingTestTest
 */
class SanitizeHeadingTest extends TestCase {
	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\SanitizeHeadingTest::build
	 */
	public function testExecute() {
		$sanitizer = new SanitizeHeading();

		$expected = "==My heading==\nHello world!\n===Second heading===\nHello again\n===My heading===\nTest";
		$wikiText = '==<span style="abc:def">' . "My '''''heading'''''</span>==";
		$wikiText .= "\nHello world!\n===Second heading===\nHello again\n===My heading===\nTest";

		$actual = $sanitizer->execute(
			'2',
			$wikiText
		);

		$this->assertEquals( $expected, $actual );

		$expected = "==My heading==\nHello world!\n===Second heading===\nHello again\n===My heading===\nTest";
		$wikiText = '==<span style="abc:def"><span class="bookmark-start" id="_Toc85796086"></span>' . "My heading</span>==";
		$wikiText .= "\nHello world!\n===Second heading===\nHello again\n===My heading===\nTest";

		$actual = $sanitizer->execute(
			'2',
			$wikiText
		);

		$this->assertEquals( $expected, $actual );
	}
}
