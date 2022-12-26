<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\StrikeThrough;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\StrikeThrough
 */
class StrikeThroughTest extends TagPropertyProcessorTestBase {

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new StrikeThrough();
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [
			'w:strike' => 'true'
		];
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return '<s> process me</s>';
	}
}
