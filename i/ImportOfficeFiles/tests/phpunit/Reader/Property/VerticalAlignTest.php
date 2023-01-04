<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\VerticalAlign;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\VerticalAlign
 */
class VerticalAlignTest extends TagPropertyProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new VerticalAlign();
	}

	/**
	 * @inheritDoc
	 */
	protected function getProperties(): array {
		return [
			'w:vertAlign' => [
				'w:val' => 'superscript'
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getExpectedWikiText(): string {
		return '<sup> process me</sup>';
	}

}
