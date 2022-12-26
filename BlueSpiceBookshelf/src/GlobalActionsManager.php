<?php

namespace BlueSpice\Bookshelf;

use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;
use SpecialPage;

class GlobalActionsManager extends RestrictedTextLink {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'ga-bs-bookshelf';
	}

	/**
	 *
	 * @return string[]
	 */
	public function getPermissions(): array {
		$permissions = [
			'bookshelf-viewspecialpage'
		];
		return $permissions;
	}

	/**
	 * @return string
	 */
	public function getHref(): string {
		$specialPage = SpecialPage::getTitleFor( 'BookshelfBookManager' );
		return $specialPage->getLocalURL();
	}

	/**
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'bs-bookshelf-ga-manager-label' );
	}

	/**
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'bs-bookshelfui-extension-description' );
	}

	/**
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'bs-bookshelf-ga-manager-label' );
	}
}
