<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\BookmarkStart
 */
class BookmarkStartTest extends TagProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessorName(): string {
		return 'bookmarkStart';
	}

	/**
	 * @inheritDoc
	 */
	protected function getTestInputFileName(): string {
		return 'bookmark.input.xml';
	}

	/**
	 * @inheritDoc
	 */
	protected function getTestResultFileName(): string {
		return 'bookmark.start.result.xml';
	}
}
