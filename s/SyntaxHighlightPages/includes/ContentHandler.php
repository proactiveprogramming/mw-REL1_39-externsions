<?php
namespace MediaWiki\Extension\SyntaxHighlightPages;

class ContentHandler extends \TextContentHandler {

	public function __construct( $modelId = Content::MODEL ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_TEXT ] );
	}

	protected function getContentClass() {
		return Content::class;
	}
}
