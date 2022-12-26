<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader;

use DOMDocument;
use MediaWiki\Extension\ImportOfficeFiles\Reader\DocumentPreprocessor;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\DocumentPreprocessor
 */
class DocumentPreprocessorTest extends TestCase {

	/**
	 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\DocumentPreprocessor::execute()
	 */
	public function testSuccess() {
		$inputPath = dirname( __DIR__ ) . '/data/DocumentPreprocessor/input.xml';
		$resultPath = dirname( __DIR__ ) . '/data/DocumentPreprocessor/result.xml';

		$xml = file_get_contents( $inputPath );

		$dom = new DOMDocument();
		$dom->loadXML( $xml );

		$preprocessor = new DocumentPreprocessor();
		$preprocessor->execute( $dom );

		$expectedXml = file_get_contents( $resultPath );
		$actualXml = $dom->saveXML();

		$this->assertEquals( $expectedXml, $actualXml );
	}
}
