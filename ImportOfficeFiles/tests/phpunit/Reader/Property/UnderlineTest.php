<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Underline;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Underline
 */
class UnderlineTest extends TagPropertyProcessorTestBase {

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Underline();
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [
			'w:u' => 'single'
		];
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return '<u> process me</u>';
	}
}
