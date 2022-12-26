<?php

namespace MWStake\MediaWiki\Component\Wikitext\Tests\Parser;

use MediaWiki\Extension\MenuEditor\Node\Keyword;
use MediaWiki\Extension\MenuEditor\Node\RawText;
use MediaWiki\Extension\MenuEditor\Node\TwoFoldLinkSpec;
use MediaWiki\Extension\MenuEditor\Node\WikiLink;
use MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MWStake\MediaWiki\Lib\Nodes\INodeProcessor;
use PHPUnit\Framework\TestCase;

class MenuParserTest extends TestCase {
	/**
	 * @covers \MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser::parse
	 * @covers \MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser::addNode
	 * @covers \MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser::removeNode
	 * @covers \MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser::replaceNode
	 * @covers \MediaWiki\Extension\MenuEditor\Parser\WikitextMenuParser::getMutatedData
	 */
	public function testParsing() {
		$text = file_get_contents( __DIR__ . '/../data/menu.txt' );

		$processors = $this->getProcessors();
		$revision = $this->getRevision( $text );
		$parser = new WikitextMenuParser( $revision, $processors );
		$nodes = $parser->parse();

		$this->assertInstanceOf( TwoFoldLinkSpec::class, $nodes[0] );
		$this->assertInstanceOf( RawText::class, $nodes[1] );
		$this->assertInstanceOf( WikiLink::class, $nodes[2] );
		$this->assertInstanceOf( WikiLink::class, $nodes[3] );
		$this->assertInstanceOf( Keyword::class, $nodes[4] );

		$nodes[2]->setLabel( 'DummyPage' );
		$parser->replaceNode( $nodes[2] );
		$nodes[3]->setLabel( 'Foo' );
		$parser->replaceNode( $nodes[3] );
		$nodes[4]->setKeyword( 'LANGUAGES' );
		$nodes[4]->setLevel( 1 );
		$parser->replaceNode( $nodes[4] );
		$parser->removeNode( $nodes[1] );

		$newNode = new RawText( 2, 'foo-bar', '' );
		$parser->addNode( $newNode, 'append', false );

		$this->assertSame(
			file_get_contents( __DIR__ . '/../data/mutated_menu.txt' ),
			// Cannot save file without a newline at the end, so adding it here manually
			$parser->getMutatedData() . "\n"
		);
	}

	/**
	 * @return INodeProcessor[]
	 */
	private function getProcessors() {
		$processorFactory = MediaWikiServices::getInstance()->getService(
			'MWStakeWikitextNodeProcessorRegistryFactory'
		);
		return $processorFactory->getAll();
	}

	/**
	 * @param string $text
	 * @return MutableRevisionRecord
	 * @throws \MWException
	 */
	private function getRevision( $text ) {
		$content = new \WikitextContent( $text );
		$title = \Title::newMainPage();
		$revisionRecord = new MutableRevisionRecord( $title );
		$revisionRecord->setSlot(
			SlotRecord::newUnsaved(
				SlotRecord::MAIN,
				$content
			)
		);

		return $revisionRecord;
	}
}
