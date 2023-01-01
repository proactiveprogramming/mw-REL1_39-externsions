<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Tag\Handler;
use Html;
use MediaWiki\Linker\LinkRenderer;
use PageHierarchyProvider;
use Parser;
use PPFrame;
use TitleFactory;
use Wikimedia\Rdbms\LoadBalancer;

class BookListHandler extends Handler {
	/**
	 * @var TitleFactory
	 */
	protected $titleFactory = null;

	/**
	 * @var LoadBalancer
	 */
	protected $lb = null;

	/**
	 * @var LinkRenderer
	 */
	protected $linkRenderer = null;

	/**
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param TitleFactory $titleFactory
	 * @param LoadBalancer $lb
	 * @param LinkRenderer $linkRenderer
	 */
	public function __construct( $processedInput, array $processedArgs, Parser $parser,
		PPFrame $frame, TitleFactory $titleFactory, LoadBalancer $lb,
		LinkRenderer $linkRenderer ) {
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
		$this->titleFactory = $titleFactory;
		$this->lb = $lb;
		$this->linkRenderer = $linkRenderer;
	}

	/**
	 *
	 * @return string
	 */
	public function handle() {
		$filters = explode( '|', trim( $this->processedArgs[BookList::PARAM_FILTER] ) );
		$parsedFilters = [];
		foreach ( $filters as $keyValuePair ) {
			$parts = explode( ':', trim( $keyValuePair ), 2 );
			if ( count( $parts ) < 2 ) {
				continue;
			}
			$parsedFilters[trim( $parts[0] )] = trim( $parts[1] );
		}
		// TODO RBV (19.12.11 16:32): error message if invalid filter

		$books = [];
		$res = $this->lb->getConnection( DB_REPLICA )->select(
			'page',
			[ 'page_id' ],
			[ 'page_namespace' => NS_BOOK ],
			__METHOD__,
			[ 'ORDER BY' => 'page_id' ]
		);

		foreach ( $res as $row ) {
			$sourceTitle = $this->titleFactory->newFromID( $row->page_id );
			if ( !$sourceTitle ) {
				continue;
			}

			$oPHProvider = PageHierarchyProvider::getInstanceFor(
				$sourceTitle->getPrefixedText()
			);
			$meta = $oPHProvider->getBookMeta();
			if ( empty( $meta ) ) {
				// No tag found?
				continue;
			}

			$match = false;
			foreach ( $parsedFilters as $key => $value ) {
				if ( empty( $meta[$key] ) ) {
					continue;
				}
				if ( strpos( $meta[$key], $value ) === false ) {
					continue;
				}
				$match = true;
				break;
			}
			if ( !$match ) {
				// Not what we are looking for
				continue;
			}

			$link = $this->linkRenderer->makeLink( $sourceTitle );
			$books[] = [
				'link' => $link,
				'meta' => $meta
			];
		}

		// TODO RBV (20.12.11 10:30): Display meta in tooltip...
		// TODO: allow PDF links to be injected
		$out = Html::openElement( 'ul' );
		foreach ( $books as $book ) {
			$out .= Html::openElement( 'li' );
			$out .= $book['link'];
			$out .= Html::closeElement( 'li' );
		}
		$out .= Html::closeElement( 'ul' );

		return $out;
	}
}
