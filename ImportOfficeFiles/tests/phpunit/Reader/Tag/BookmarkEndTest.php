<?php

namespace MediaWiki\Extension\ImportOfficeFiles\Tests\Reader\Tag;

/**
 * @covers \MediaWiki\Extension\ImportOfficeFiles\Reader\Tag\BookmarkEnd
 */
class BookmarkEndTest extends TagProcessorTestBase {

	/**
	 * @inheritDoc
	 */
	protected function getProcessorName(): string {
		return 'bookmarkEnd';
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
		return 'bookmark.end.result.xml';
	}
}
