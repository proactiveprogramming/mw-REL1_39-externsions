<?php

namespace BlueSpice\Bookshelf;

use InvalidArgumentException;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use PageHierarchyProvider;

class ChapterPagerPanel extends Literal {

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	/**
	 * @inheritDoc
	 */
	public function __construct( $title ) {
		$this->title = $title;
		parent::__construct( 'bs-book-chapterpager', '' );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'bs-book-chapterpager';
	}

	/**
	 *
	 * @return string
	 */
	public function getHtml(): string {
		$chapterPager = new ChapterPager();
		$chapterPager->makePagerData( $this->title );
		return $chapterPager->getDefaultPagerHtml( $this->title );
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( $context ): bool {
		$title = $context->getTitle();
		if ( $title->isRedirect() ) {
			$webRequestValues = $context->getRequest()->getValues();
			if ( !isset( $webRequestValues['redirect'] ) || $webRequestValues['redirect'] !== 'no' ) {
				$title = $context->getWikiPage()->getRedirectTarget();
			}
		}
		try {
			$provider = PageHierarchyProvider::getInstanceForArticle(
				$title->getPrefixedText()
			);
		} catch ( InvalidArgumentException $ex ) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [ 'ext.bookshelf.pager.content.styles' ];
	}
}
