<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Bold;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Bold
 */
class BoldTest extends TagPropertyProcessorTestBase {

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Bold();
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [
			'w:b' => 'true'
		];
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return " ###PRESERVEBOLD###process me###PRESERVEBOLD###";
	}
}
