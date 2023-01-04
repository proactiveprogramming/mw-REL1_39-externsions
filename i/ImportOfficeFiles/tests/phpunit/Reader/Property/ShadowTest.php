<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Shadow;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\HtmlWrapper;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Shadow
 */
class ShadowTest extends TagPropertyProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Shadow();
	}

	/**
	 * @inheritDoc
	 */
	protected function getProperties(): array {
		return [
			'w:shd' => [
				'w:val' => 'clear',
				'w:fill' => '#aaa'
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
		return '<span style=\'background-color: #aaa;\'> process me</span>';
	}
}
