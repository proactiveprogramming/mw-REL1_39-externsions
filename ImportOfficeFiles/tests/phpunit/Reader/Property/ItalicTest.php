<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Italic;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Italic
 */
class ItalicTest extends TagPropertyProcessorTestBase {

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Italic();
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [
			'w:i' => 'true'
		];
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return " ###PRESERVEITALIC###process me###PRESERVEITALIC###";
	}
}
