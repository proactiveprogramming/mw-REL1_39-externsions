<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use ExtensionRegistry;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

class TagProcessorTestBase extends TestCase {

	/**
	 * If wikitext processing results should be separated by new line.
	 * It may be useful with, for example, hyperlinks (which don't have new line after them).
	 * But it is not needed, for example, with paragraphs (which have new line after them).
	 *
	 * @var bool
	 */
	protected $newLineNeeded = false;

	public function testProcess() {
		$dom = new DOMDocument();
		// Load input XML
		$fileName = $this->getTestInputFileName();

		// Get rid of new lines and tabulations to not spoil test results
		// This problem is happening only in tests, it is properly handled in conversion process
		$xmlContent = file_get_contents( __DIR__ . "/$fileName" );
		$xmlContent = str_replace( [ "\n", "\t" ], '', $xmlContent );

		$dom->loadXML( $xmlContent );
		$xpath = new DOMXPath( $dom );
		/** @var DOMNodeList */
		$documentElements = $xpath->query( '//w:document/w:body' );

		$extensionRegistry = ExtensionRegistry::getInstance();
		$registry = $extensionRegistry->getAttribute(
			'ImportOfficeFilesWord2007TagProcessorRegistry'
		);
		$processorName = $this->getProcessorName();
		$objectFactory = MediaWikiServices::getInstance()->getObjectFactory();
		$processor = $objectFactory->createObject(
			$registry[$processorName]
		);
		$processor->setDocumentData( $this->getDocumentData() );

		$liveList = $processor->getProcessableElementsFromDocument( $documentElements->item( 0 ) );
		$nonLiveList = [];
		$wikiTextReplacement = '';
		foreach ( $liveList  as $liveNode ) {
			$nonLiveList[] = $liveNode;
		}
		foreach ( $nonLiveList as $nonLiveNode ) {
			$wikiTextReplacement .= $processor->process( $nonLiveNode );
			if ( $this->newLineNeeded ) {
				$wikiTextReplacement .= "\n";
			}
		}

		$this->performChecks( $wikiTextReplacement, $documentElements );
	}

	/**
	 * Performs actual checks if actual result matches expected result.
	 * All asserts and checks should be placed here.
	 * This method can be overridden in child classes to perform some other checks,
	 * like changes in the DOM tree and so on.
	 *
	 * @param string $wikiTextReplacement Concatenated wikitext replacement for all found elements.
	 * 		Can be used to perform any checks of processor work.
	 * @param DOMNodeList $documentElements List of document 'body' nodes.
	 * 		May be useful to check if DOM state changed.
	 * @return void
	 */
	protected function performChecks( string $wikiTextReplacement, DOMNodeList $documentElements ) {
		// Load input XML
		$fileName = $this->getTestResultFileName();
		$expected = file_get_contents( __DIR__ . "/$fileName" );

		$this->assertEquals(
			$expected,
			$wikiTextReplacement
		);
	}

	/**
	 * @return string
	 */
	protected function getProcessorName(): string {
		return 'table';
	}

	/**
	 * @return Word2007DocumentData
	 */
	protected function getDocumentData(): Word2007DocumentData {
		return new DummyDocumentData( [] );
	}

	/**
	 * @return string
	 */
	protected function getTestInputFileName(): string {
		return 'table.input.xml';
	}

	/**
	 * @return string
	 */
	protected function getTestResultFileName(): string {
		return 'table.result.xml';
	}
}
