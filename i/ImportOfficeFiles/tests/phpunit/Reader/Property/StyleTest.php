<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Style;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Style
 */
class StyleTest extends TagPropertyProcessorTestBase {

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Style();
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [
			'Style' => [
				'w:val' => 'Berschrift 1'
			]
		];
	}

	/**
	 * @return Word2007DocumentData
	 */
	protected function getDocumentData(): Word2007DocumentData {
		return new DummyDocumentData( [
			'styles' => [
				[ 'id' => 'Titel', 'name' => 'Title' ],
				[ 'id' => 'Subtitel', 'name' => 'Subtitle' ],
				[ 'id' => 'Berschrift 1', 'name' => 'Heading 1' ],
				[ 'id' => 'Berschrift 2', 'name' => 'Heading 2' ],
				[ 'id' => 'Berschrift 3', 'name' => 'Heading 3' ],
				[ 'id' => 'Berschrift 4', 'name' => 'Heading 4' ],
				[ 'id' => 'Berschrift 5', 'name' => 'Heading 5' ]
			]
		] );
	}

	/**
	 * @return string
	 */
	protected function getWikiText(): string {
		return 'process me';
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return "==process me==";
	}
}
