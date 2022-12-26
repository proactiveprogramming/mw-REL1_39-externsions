<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater;

use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchListStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Updater\UpdateManager;
use MediaWiki\MediaWikiServices;
use MediaWiki\OAuthClient\Client;
use Psr\Container\ContainerInterface;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Services implements ContainerInterface {
	/** @var ContainerInterface */
	private $container;

	private function __construct( ContainerInterface $container ) {
		$this->container = $container;
	}

	public static function getInstance(): Services {
		return new self( MediaWikiServices::getInstance() );
	}

	public function getAccessTokenStore(): AccessTokenStore {
		return $this->get( 'WSU:AccessTokenStore' );
	}

	/** @inheritDoc */
	public function get( $id ) {
		return $this->container->get( $id );
	}

	/** @inheritDoc */
	public function has( $id ) {
		return $this->container->has( $id );
	}

	public function getBatchListStore(): BatchListStore {
		return $this->get( 'WSU:BatchListStore' );
	}

	public function getBatchStore(): BatchStore {
		return $this->get( 'WSU:BatchStore' );
	}

	public function getOAuthClient(): Client {
		return $this->get( 'WSU:OAuthClient' );
	}

	public function getUpdateManager(): UpdateManager {
		return $this->get( 'WSU:UpdateManager' );
	}
}
