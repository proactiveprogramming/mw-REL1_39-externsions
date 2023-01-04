<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\WikiTextProcessor;

use MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\BoldMarkupReplacement;
use MediaWiki\Extension\ImportOfficeFiles\Workspace;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\BoldMarkupReplacement
 */
class BoldMarkupReplacementTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\WikiTextProcessor\BoldMarkupReplacement::process()
	 * @return void
	 */
	public function testProcess() {
		$workspaceMock = $this->createMock( Workspace::class );

		$wikitext = $this->getTestText();

		$imageReplacement = new BoldMarkupReplacement( $workspaceMock );
		$actualWikitext = $imageReplacement->process( $wikitext );

		$expectedWikitext = $this->getExpectedText();

		$this->assertEquals( $expectedWikitext, $actualWikitext );
	}

	/**
	 * @return string
	 */
	private function getTestText(): string {
		$text = "Lorem ipsum dolor ###PRESERVEBOLD###sit###PRESERVEBOLD### ''amet'',";
		$text .= " consectetuer ###PRESERVEBOLD###adipiscing###PRESERVEBOLD######PRESERVEBOLD###";
		$text .= " ###PRESERVEBOLD######PRESERVEBOLD###elit###PRESERVEBOLD###.";
		$text .= " ''Aenean'''' ''''commodo'' ligula ###PRESERVEBOLD###''eget''###PRESERVEBOLD###";
		$text .= " dolor. ###PRESERVEBOLD###Aenean ''massa''###PRESERVEBOLD###\n";
		$text .= " Cum ###PRESERVEBOLD###''sociis'' natoque###PRESERVEBOLD### penatibus";
		$text .= " ###PRESERVEBOLD###et ''magnis'' dis###PRESERVEBOLD### parturient montes,";
		$text .= " ''nascetur ###PRESERVEBOLD###ridiculus###PRESERVEBOLD### mus''.";
		$text .= " Donec quam felis, ultricies ###PRESERVEBOLD###nec,";
		$text .= " pellentesque###PRESERVEBOLD######PRESERVEBOLD### eu###PRESERVEBOLD###, pretium quis, sem.";
		$text .= '"Lorem ipsum dolor „###PRESERVEBOLD###sit“###PRESERVEBOLD### amet';
		$text .= ' ###PRESERVEBOLD###consectetuer###PRESERVEBOLD### ###PRESERVEBOLD###adipiscing';
		$text .= ' ###PRESERVEBOLD###„\'\'elit\'\' \'\'Aenean\'\'“###PRESERVEBOLD###';
		return $text;
	}

/**
 * @return string
 */
	private function getExpectedText(): string {
		$text = "Lorem ipsum dolor '''sit''' ''amet'', consectetuer '''adipiscing elit'''.";
		$text .= " ''Aenean'''' ''''commodo'' ligula '''''eget''''' dolor. '''Aenean ''massa'''''\n";
		$text .= " Cum '''''sociis'' natoque''' penatibus '''et ''magnis'' dis''' parturient montes,";
		$text .= " ''nascetur '''ridiculus''' mus''.";
		$text .= " Donec quam felis, ultricies '''nec, pellentesque eu''', pretium quis, sem.";
		$text .= '"Lorem ipsum dolor „\'\'\'sit“\'\'\' amet \'\'\'consectetuer\'\'\'';
		$text .= ' \'\'\'adipiscing \'\'\'„\'\'elit\'\' \'\'Aenean\'\'“\'\'\'';
		return $text;
	}
}
