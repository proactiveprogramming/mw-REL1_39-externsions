<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\WikiTextProcessor;

use MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ItalicMarkupReplacement;
use MediaWiki\Extension\ImportOfficeFiles\Workspace;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ItalicMarkupReplacement
 */
class ItalicMarkupReplacementTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\ItalicMarkupReplacement::process()
	 * @return void
	 */
	public function testProcess() {
		$workspaceMock = $this->createMock( Workspace::class );

		$wikitext = $this->getTestText();

		$imageReplacement = new ItalicMarkupReplacement( $workspaceMock );
		$actualWikitext = $imageReplacement->process( $wikitext );

		$expectedWikitext = $this->getExpectedText();

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

	/**
	 * @return string
	 */
	private function getTestText(): string {
		$text = "Lorem ipsum dolor '''sit''' ###PRESERVEITALIC###amet###PRESERVEITALIC###,";
		$text .= " consectetuer '''adipiscing'''''' ''''''elit'''.";
		$text .= " ###PRESERVEITALIC###Aenean###PRESERVEITALIC######PRESERVEITALIC###";
		$text .= " ###PRESERVEITALIC######PRESERVEITALIC###commodo###PRESERVEITALIC### ligula";
		$text .= " '''###PRESERVEITALIC###eget###PRESERVEITALIC###''' dolor.";
		$text .= " '''Aenean ###PRESERVEITALIC###massa###PRESERVEITALIC###'''\n";
		$text .= " Cum '''###PRESERVEITALIC###sociis###PRESERVEITALIC### natoque''' penatibus";
		$text .= " '''et ###PRESERVEITALIC###magnis###PRESERVEITALIC### dis''' parturient montes,";
		$text .= " ###PRESERVEITALIC###nascetur '''ridiculus''' mus###PRESERVEITALIC###.";
		$text .= " Donec quam felis, ultricies '''nec, pellentesque'''''' eu''', pretium quis, sem.";
		$text .= '"Lorem ipsum dolor „\'\'\'sit“\'\'\' amet \'\'\'consectetuer\'\'\' \'\'\'adipiscing';
		$text .= ' \'\'\'„###PRESERVEITALIC###elit###PRESERVEITALIC###';
		$text .= ' ###PRESERVEITALIC###Aenean###PRESERVEITALIC###“\'\'\'';
		return $text;
	}

/**
 * @return string
 */
	private function getExpectedText(): string {
		$text = "Lorem ipsum dolor '''sit''' ''amet'', consectetuer '''adipiscing'''''' ''''''elit'''.";
		$text .= " ''Aenean commodo'' ligula '''''eget''''' dolor. '''Aenean ''massa'''''\n";
		$text .= " Cum '''''sociis'' natoque''' penatibus '''et ''magnis'' dis''' parturient montes,";
		$text .= " ''nascetur '''ridiculus''' mus''.";
		$text .= " Donec quam felis, ultricies '''nec, pellentesque'''''' eu''', pretium quis, sem.";
		$text .= '"Lorem ipsum dolor „\'\'\'sit“\'\'\' amet \'\'\'consectetuer\'\'\'';
		$text .= ' \'\'\'adipiscing \'\'\'„\'\'elit\'\' \'\'Aenean\'\'“\'\'\'';
		return $text;
	}
}
