<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Updater;

use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchItem;
use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Exception;
use MediaWiki\OAuthClient\Token;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Updater {
	/** @var Client */
	private $client;
	/** @var string */
	private $api;
	/** @var BatchItem */
	private $item;
	/** @var Token */
	private $accessToken;

	public function __construct(
		Client $client,
		string $api,
		BatchItem $item,
		Token $accessToken
	) {
		$this->client = $client;
		$this->api = $api;
		$this->item = $item;
		$this->accessToken = $accessToken;
	}

	/** @throws UpdateFailure */
	public function update(): array {
		try {
			$response = $this->getClaims( $this->item );
			$claims = $response['claims'][$this->item->getCommandId()];

			$count = count( $claims );
			if ( $count === 0 ) {
				return $this->addClaim( $this->item );
			} elseif ( $count === 1 ) {
				$id = $claims[0]['id'];
				return $this->updateClaim( $id, $this->item->getValue() );
			} else {
				throw new AmbiguousUpdate( 'Property has multiple statements' );
			}
		} catch ( Exception $e ) {
			throw new UpdateFailure( 'Connection failure', 0, $e );
		}
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function getClaims( BatchItem $item ): array {
		$params = [
			'action' => 'wbgetclaims',
			'entity' => $item->getSubject(),
			'property' => $item->getCommandId(),
		];

		return $this->makeGetRequest( $params );
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function makeGetRequest( array $params ): array {
		return $this->makeRequest( $params, false, null );
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function makeRequest( $params, $isPost, $postParams ): array {
		$params += [
			'format' => 'json',
			'errorformat' => 'wikitext',
			'formatversion' => 2,
		];

		$apiUrl = wfAppendQuery( $this->api, $params );
		$returnValue = json_decode(
			$this->client->makeOAuthCall( $this->accessToken, $apiUrl, $isPost, $postParams ),
			true
		);

		if ( !$returnValue ) {
			throw new ApiFailure( 'Invalid response' );
		}

		if ( isset( $returnValue['errors'] ) ) {
			$errors = json_encode( $returnValue['errors'] );
			throw new ApiFailure( "Response has errors: $errors" );
		}

		return $returnValue;
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function addClaim( BatchItem $item ): array {
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $item->getSubject(),
			'snaktype' => 'value',
			'property' => $item->getCommandId(),
			'value' => $item->getValue(),
		];

		$postParams = [
			'token' => $this->getToken(),
		];

		return $this->makePostRequest( $params, $postParams );
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function getToken(): string {
		$params = [
			'action' => 'query',
			'meta' => 'tokens',
		];

		$response = $this->makeGetRequest( $params );
		return $response['query']['tokens']['csrftoken'];
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function makePostRequest( array $params, array $postParams ): array {
		return $this->makeRequest( $params, true, $postParams );
	}

	/**
	 * @throws Exception
	 * @throws ApiFailure
	 */
	private function updateClaim( string $id, string $value ): array {
		$params = [
			'action' => 'wbsetclaimvalue',
			'claim' => $id,
			'value' => $value,
			'snaktype' => 'value',
		];

		$postParams = [
			'token' => $this->getToken(),
		];

		return $this->makePostRequest( $params, $postParams );
	}
}
