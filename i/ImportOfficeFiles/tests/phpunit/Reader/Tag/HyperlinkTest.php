<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\Hyperlink
 */
class HyperlinkTest extends TagProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected $newLineNeeded = true;

	/**
	 * @return string
	 */
	protected function getProcessorName(): string {
		return 'hyperlink';
	}

	/**
	 * @return Word2007DocumentData
	 */
	protected function getDocumentData(): Word2007DocumentData {
		return new DummyDocumentData( [
			'rels' => [
				[
					'Id' => 'rId6',
					'Target' => 'http://some_site.test'
				]
			]
		] );
	}

	/**
	 * @return string
	 */
	protected function getTestInputFileName(): string {
		return 'hyperlink.input.xml';
	}

	/**
	 * @return string
	 */
	protected function getTestResultFileName(): string {
		return 'hyperlink.result.xml';
	}
}
