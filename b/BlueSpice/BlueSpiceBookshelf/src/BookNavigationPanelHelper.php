<?php

namespace BlueSpice\Bookshelf;

use BSTreeNode;
use BSTreeRenderer;
use Html;
use HtmlArmor;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PageHierarchyProvider;

class BookNavigationPanelHelper {

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	 /**
	  *
	  * @var array
	  */
	protected $tree = null;

	/**
	 *
	 * @param Title $title
	 * @return string
	 */
	public function setUpBookNavigation( $title ) {
		$this->title = $title;
		$chapterHtml = $this->getPager();
		$bookTitleHtml = $this->getBookNavigation();

		$html = $chapterHtml . $bookTitleHtml;
		return $html;
	}

	/**
	 *
	 * @return string
	 */
	protected function getPager() {
		$chapterPager = new ChapterPager();
		$chapterPager->makePagerData( $this->title );
		return $chapterPager->getDefaultPagerHtml( $this->title );
	}

	/**
	 *
	 * @return string
	 */
	protected function getBookNavigation() {
		try {
			$provider = PageHierarchyProvider::getInstanceForArticle(
				$this->title->getPrefixedText()
			);
		} catch ( InvalidArgumentException $ex ) {
			return false;
		}

		$contentHtml = $this->getNodes( $provider );
		$bookEditLinkHtml = $this->getBookEditLink();

		return $contentHtml	. $bookEditLinkHtml;
	}

	/**
	 *
	 * @return string
	 */
	protected function getBookEditLink() {
		$bookEditorTitle = \Title::makeTitleSafe(
			$this->tree->bookshelf->page_namespace,
			$this->tree->bookshelf->page_title
		);

		$bookEditorLink = \Html::openElement(
			'a',
			[
				'id' => 'edit-book',
				'class' => 'bs-link-edit-bookshelfui-book',
				'href' => $bookEditorTitle->getFullURL( [ 'action' => 'edit' ] ),
				'title' => wfMessage( 'bs-bookshelfui-book-title-link-edit' )->plain()
			]
		);
		$bookEditorLink .=
			wfMessage( 'bs-bookshelfui-book-title-link-edit-text' )->plain();

		$bookEditorLink .= \Html::closeElement( 'a' );
		return $bookEditorLink;
	}

	/**
	 *
	 * @param PageHierarchyProvider $provider
	 * @return string
	 */
	protected function getNodes( $provider ) {
		$this->tree = $provider->getExtendedTOCJSON();
		$rootNode = new BSTreeNode( $this->tree->text, null, new \HashConfig( [
			BSTreeNode::CONFIG_EXPANDED => true,
			BSTreeNode::CONFIG_IS_LEAF => false,
			BSTreeNode::CONFIG_TEXT => $this->tree->articleDisplayTitle
		] ) );
		$this->rootNodeId = $rootNode->getId();
		$this->addChildsToNode( $rootNode, $this->tree->children );

		$renderer = new BSTreeRenderer( $rootNode, new \HashConfig( [
			BSTreeRenderer::CONFIG_ID => $this->getTreeId()
		] ) );
		$paths = $this->getPathsToExpand( $provider );
		foreach ( $paths as $path ) {
			$renderer->expandPath( $path );
		}

		return $renderer->render();
	}

	/**
	 *
	 * @return string
	 */
	protected function getTreeId() {
		return 'book-tree';
	}

	/**
	 *
	 * @param BSTreeNode $node
	 * @param array $childs
	 */
	protected function addChildsToNode( $node, $childs ) {
		foreach ( $childs as $child ) {
			$nodeText = $this->makeNodeText( $child );
			$childNode = new BSTreeNode( $child->id, $node, new \HashConfig( [
				BSTreeNode::CONFIG_EXPANDED => false,
				BSTreeNode::CONFIG_IS_LEAF => false,
				BSTreeNode::CONFIG_TEXT => $nodeText[0],
				BSTreeNode::CONFIG_ACTIVE => $nodeText[1]
			] ) );

			$node->appendChild( $childNode );
			if ( isset( $child->children ) && !empty( $child->children ) ) {
				$this->addChildsToNode( $childNode,  $child->children );
			}
		}
	}

	/**
	 *
	 * @param BSTreeNode $node
	 * @return string
	 */
	protected function makeNodeText( $node ) {
		if ( $node->articleType === 'plain-text' ) {
			return $this->makePlainTextNodeText( $node );
		}
		if ( $node->articleType === 'wikilink-with-alias' ) {
			return $this->makeWikiPageNodeText( $node );
		}
		if ( $node->articleType === 'wikilink' ) {
			return $this->makeWikiPageNodeText( $node );
		}
	}

	/**
	 *
	 * @param BSTreeNode $node
	 * @return array
	 */
	protected function makePlainTextNodeText( $node ) {
		return [
			\Html::element(
				'span',
				[
					'level' => $node->articleNumber,
					'name' => $node->articleNumber,
					'title' => $node->text
				],
				$node->text
			),
			false
		];
	}

	/**
	 *
	 * @param BSTreeNode $node
	 * @return array
	 */
	protected function makeWikiPageNodeText( $node ) {
		$currentTitle = $this->title;
		$target = \Title::newFromText( $node->articleTitle );

		$num = Html::element(
			'span',
			[ 'class' => 'bs-articleNumber' ],
			$node->articleNumber
		);
		$title = Html::element(
			'span',
			[ 'class' => 'bs-articleText' ],
			str_replace( $node->articleNumber . '. ', '', $node->text )
		);

		$attribs = [
			'name' => $node->articleNumber,
			'title' => $node->text
		];

		if ( $currentTitle->equals( $target ) ) {
			$active = true;
			$attribs['class'] = 'active';
		} else {
			$active = false;
		}

		return [ MediaWikiServices::getInstance()->getLinkRenderer()->makeLink(
			$target,
			new HtmlArmor( $num . $title ),
			$attribs
		),
		$active ];
	}

	/**
	 * @param PageHierarchyProvider $provider
	 * @return array
	 */
	protected function getPathsToExpand( $provider ) {
		$number = $provider->getNumberFor(
			$this->title->getPrefixedText()
		);

		$path = $this->makeExpandPath( $number );

		return [ $path ];
	}

	/**
	 *
	 * @param string $number
	 * @return string
	 */
	protected function makeExpandPath( $number ) {
		$numberParts = explode( '.', $number );
		$path = [ \Sanitizer::escapeIdForAttribute( $number ) ];
		$count = count( $numberParts );
		for ( $i = 0; $i <= $count; $i++ ) {
			array_pop( $numberParts );
			$id = implode( '.', $numberParts );
			if ( empty( $id ) ) {
				continue;
			}
			$path[] = \Sanitizer::escapeIdForAttribute( $id );
		}
		$path[] = \Sanitizer::escapeIdForAttribute( $this->rootNodeId );
		$path[] = '';

		return implode( '/', array_reverse( $path ) );
	}
}
