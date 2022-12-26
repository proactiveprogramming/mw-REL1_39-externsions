<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\Textrun
 */
class TextrunTest extends TagProcessorTestBase {

	/**
	 * @return string
	 */
	protected function getProcessorName(): string {
		return 'textrun';
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
		return 'textrun.input.xml';
	}

	/**
	 * @return string
	 */
	protected function getTestResultFileName(): string {
		return 'textrun.result.xml';
	}
}
