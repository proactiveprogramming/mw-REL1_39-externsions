<?php

namespace BlueSpice\Bookshelf\ContentHandler;

use BlueSpice\Bookshelf\Action\BookEditAction;
use BlueSpice\Bookshelf\Action\BookEditSourceAction;
use BlueSpice\Bookshelf\BookEditData;
use BlueSpice\Bookshelf\Content\BookContent;
use Content;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\MediaWikiServices;
use MWException;
use ParserOutput;
use TextContentHandler;
use Title;

class BookContentHandler extends TextContentHandler {
	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'book' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_TEXT ] );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return BookContent::class;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'edit' => BookEditAction::class,
			'editbooksource' => BookEditSourceAction::class,
		];
	}

	/**
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$output The output object to fill (reference).
	 */
	protected function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$output
	) {
		// parse just to get links etc into the database, HTML is replaced below.
		$output = MediaWikiServices::getInstance()->getParser()
			->parse(
				$content->getText(),
				$cpoParams->getPage(),
				$cpoParams->getParserOptions(),
				true,
				true,
				$cpoParams->getRevId()
			);

		try {
			$dbKey = $cpoParams->getPage()->getDBkey();
			$title = Title::newFromDBkey( $dbKey );
			$bookEditData = BookEditData::newFromTitleAndRequest(
				$title, new \WebRequest()
			);
			$output->addJsConfigVars( 'bsBookshelfData', $bookEditData->getBookData() );
			$output->addModules( [ 'ext.bluespice.bookshelf.view' ] );
			$output->setText( \Html::element( 'div', [ 'id' => 'bs-bookshelf-view' ] ) );
		}
		catch ( MWException $e ) {
			$output->addWarningMsg( "bs-bookshelf-warning", $e->getText() );
		}
	}
}
