<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Color;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\HtmlWrapper;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Color
 */
class ColorTest extends TagPropertyProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Color();
	}

	/**
	 * @inheritDoc
	 */
	protected function getProperties(): array {
		return [
			'w:color' => [
				'w:val' => 'aaa'
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getHtmlWrapper(): ?HtmlWrapper {
		return new HtmlWrapper( 'span' );
	}

	/**
	 * @inheritDoc
	 */
	protected function getExpectedWikiText(): string {
		return '<span style=\'color: #aaa;\'> process me</span>';
	}
}
