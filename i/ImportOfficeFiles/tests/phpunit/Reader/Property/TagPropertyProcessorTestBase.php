<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Property;

use MediaWiki\Extension\ImportOfficeFiles\ITagPropertyProcessor;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Component\Word2007DocumentData;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Property\Underline;
use MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\HtmlWrapper;
use MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\DummyDocumentData;
use PHPUnit\Framework\TestCase;

class TagPropertyProcessorTestBase extends TestCase {

	public function testProcess() {
		$wikiText = $this->getWikiText();
		$properties = $this->getProperties();
		$documentData = $this->getDocumentData();

		/** @var ITagPropertyProcessor */
		$processor = $this->getProcessor();
		$processor->setWikiText( $wikiText );
		$processor->setProperties( $properties );
		$processor->setDocumentData( $documentData );

		$wikiText = $processor->process();

		$htmlWrapper = $this->getHtmlWrapper();
		if ( !( $htmlWrapper === null ) ) {
			$styles = $processor->getWrapperStyles();
			if ( $styles ) {
				$htmlWrapper->setStyles( $styles );
			}

			$wikiText = $htmlWrapper->wrap( $wikiText );
		}

		$this->assertEquals(
			$this->getExpectedWikiText(),
			$wikiText
		);
	}

	/**
	 * @return ITagPropertyProcessor
	 */
	protected function getProcessor(): ITagPropertyProcessor {
		return new Underline();
	}

	/**
	 * @return string
	 */
	protected function getWikiText(): string {
		return ' process me';
	}

	/**
	 * @return array
	 */
	protected function getProperties(): array {
		return [];
	}

	/**
	 * @return Word2007DocumentData
	 */
	protected function getDocumentData(): Word2007DocumentData {
		return new DummyDocumentData( [] );
	}

	/**
	 * @return HtmlWrapper|null
	 */
	protected function getHtmlWrapper(): ?HtmlWrapper {
		return null;
	}

	/**
	 * @return string
	 */
	protected function getExpectedWikiText(): string {
		return ' process me';
	}
}
