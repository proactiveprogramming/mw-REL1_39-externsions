<?php

namespace BlueSpice\Expiry;

use Message;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\RestrictedTextLink;

class GlobalActionsTool extends RestrictedTextLink {

	public function __construct() {
		parent::__construct( [] );
	}

	/**
	 *
	 * @return string
	 */
	public function getId(): string {
		return 'ga-special-expiry';
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions(): array {
		$permissions = [
			"expirearticle",
			"expiry-delete"
		];
		return [ 'read' ];
	}

	/**
	 *
	 * @return string
	 */
	public function getHref(): string {
		$tool = \MediaWiki\MediaWikiServices::getInstance()
		->getSpecialPageFactory()
		->getPage( 'Expiry' );
		return $tool->getPageTitle()->getLocalURL();
	}

	/**
	 *
	 * @return Message
	 */
	public function getText(): Message {
		return Message::newFromKey( 'specialexpiry' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getTitle(): Message {
		return Message::newFromKey( 'specialexpiry' );
	}

	/**
	 *
	 * @return Message
	 */
	public function getAriaLabel(): Message {
		return Message::newFromKey( 'expiry' );
	}
}
