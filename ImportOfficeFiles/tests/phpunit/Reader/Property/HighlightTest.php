<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Highlight;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\HtmlWrapper;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Highlight
 */
class HighlightTest extends TagPropertyProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Highlight();
	}

	/**
	 * @inheritDoc
	 */
	protected function getProperties(): array {
		return [
			'w:highlight' => [
				'w:val' => 'yellow'
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
		return '<span style=\'background-color: yellow;\'> process me</span>';
	}
}
