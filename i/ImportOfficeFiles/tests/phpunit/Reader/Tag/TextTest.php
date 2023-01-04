<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\Text
 */
class TextTest extends TagProcessorTestBase {

	/**
	 * @return string
	 */
	protected function getProcessorName(): string {
		return 'text';
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
		return 'text.input.xml';
	}

	/**
	 * @return string
	 */
	protected function getTestResultFileName(): string {
		return 'text.result.xml';
	}
}
