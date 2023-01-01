<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Updater;

use MediaWiki\Extension\WikibaseStatementUpdater\AccessTokenStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchListStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchStore;
use MediaWiki\OAuthClient\Client;

/**
 * Performs the update and records the success or failure.
 *
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class UpdateManager {
	/** @var BatchStore */
	private $batchStore;
	/** @var BatchListStore */
	private $batchListStore;
	/** @var AccessTokenStore */
	private $accessTokenStore;
	/** @var Client */
	private $client;
	/** @var string */
	private $api;

	public function __construct(
		BatchStore $batchStore,
		BatchListStore $batchListStore,
		AccessTokenStore $accessTokenStore,
		Client $client,
		string $api
	) {
		$this->batchStore = $batchStore;
		$this->batchListStore = $batchListStore;
		$this->accessTokenStore = $accessTokenStore;
		$this->client = $client;
		$this->api = $api;
	}

	/**
	 * @param int $batchItemId
	 * @param int $batchListId
	 * @suppress PhanTypeMismatchArgumentNullable FIXME $record and $accessToken can be null
	 */
	public function process( int $batchItemId, int $batchListId ): void {
		$record = $this->batchStore->getRecord( $batchItemId );
		$list = $this->batchListStore->get( $batchListId );

		if ( $list->getStatus() === 'stopped' ) {
			return;
		}

		$accessToken = $this->accessTokenStore->get( $list->getOwner() );
		$updater = new Updater( $this->client, $this->api, $record->getItem(), $accessToken );

		try {
			$response = $updater->update();
			$output = [
				'status' => 'ok',
				'revisionId' => $response['pageinfo']['lastrevid'],
			];

			$record->setOutput( $output );
			$this->batchStore->updateOutput( $record );
		} catch ( UpdateFailure $e ) {
			$output = [
				'status' => 'error',
				'class' => get_class( $e ),
				'i18n' => $e->getI18nMessage(),
				'message' => $e->getMessage(),
			];

			$record->setOutput( $output );
			$this->batchStore->updateOutput( $record );
		}
	}
}
