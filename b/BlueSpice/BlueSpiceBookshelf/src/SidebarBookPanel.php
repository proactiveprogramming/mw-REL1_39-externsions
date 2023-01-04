<?php

namespace BlueSpice\Bookshelf;

use IContextSource;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\ComponentBase;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\SimpleCard;
use MWStake\MediaWiki\Component\CommonUserInterface\ITabPanel;
use PageHierarchyProvider;

class SidebarBookPanel extends ComponentBase implements ITabPanel {

	/**
	 *
	 * @var Title
	 */
	protected $title = null;

	/**
	 * @param Title $title
	 */
	public function __construct( $title ) {
		$this->title = $title;
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'book-navigation-panel';
	}

	/**
	 * @inheritDoc
	 */
	public function getContainerClasses(): array {
		return [ 'book-nav-panel' ];
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLStyles(): array {
		return [ 'ext.bookshelf.navigation-panel.styles' ];
	}

	/**
	 *
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-text' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-title' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-aria-label' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaDesc(): Message {
		return Message::newFromKey( 'bs-bookshelfui-panel-navigation-aria-desc' );
	}

	/**
	 *
	 * @return IComponent[]
	 */
	public function getSubComponents(): array {
		$bookNav = new BookNavigationPanelHelper();
		$html = $bookNav->setUpBookNavigation( $this->title );

		$items = [];
		$items[] = new SimpleCard( [
			'id' => 'n-book-panel',
			'classes' => [ 'w-100', 'bg-transp' ],
			'items' => [
				new Literal( 'pager',  $html )
			]
		] );

		return $items;
	}

	/**
	 *
	 * @param IContextSource $context
	 * @return bool
	 */
	public function shouldRender( IContextSource $context ): bool {
		$title = $context->getTitle();
		if ( $title->isRedirect() ) {
			$webRequestValues = $context->getRequest()->getValues();
			if ( !isset( $webRequestValues['redirect'] ) || $webRequestValues['redirect'] !== 'no' ) {
				/** @var Title $title */
				$title = MediaWikiServices::getInstance()->getRedirectLookup()
					->getRedirectTarget( $context->getWikiPage() );
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
	 * @param IContextSource $context
	 * @return bool
	 */
	public function isActive( $context ): bool {
		return $this->shouldRender( $context );
	}

}
