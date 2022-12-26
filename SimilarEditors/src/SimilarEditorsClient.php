<?php

namespace MediaWiki\Extension\SimilarEditors;

use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;
use Status;

class SimilarEditorsClient implements Client {

	/**
	 * @var HttpRequestFactory
	 */
	private $httpRequestFactory;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var string
	 */
	private $apiUrl;

	/**
	 * @var string
	 */
	private $apiUser;

	/**
	 * @var string
	 */
	private $apiPassword;

	/**
	 * @param HttpRequestFactory $httpRequestFactory
	 * @param LoggerInterface $logger
	 * @param string $apiUrl
	 * @param string $apiUser
	 * @param string $apiPassword
	 */
	public function __construct(
		HttpRequestFactory $httpRequestFactory,
		LoggerInterface $logger,
		string $apiUrl,
		string $apiUser,
		string $apiPassword
	) {
		$this->httpRequestFactory = $httpRequestFactory;
		$this->logger = $logger;
		$this->apiUrl = $apiUrl;
		$this->apiUser = $apiUser;
		$this->apiPassword = $apiPassword;
	}

	/**
	 * @inheritDoc
	 */
	public function getEditor( string $editor ) {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function getSimilarEditors( string $editor ) {
		$request = $this->httpRequestFactory->create(
			$this->apiUrl . '?usertext=' . urlencode( $editor ),
			[
				'method' => 'GET',
				'username' => $this->apiUser,
				'password' => $this->apiPassword
			],
			__METHOD__
		);

		$status = $request->execute();
		$json = json_decode( $request->getContent(), true );

		if ( $status->isOK() ) {
			if ( $json && isset( $json['results'] ) ) {
				return array_map( static function ( $result ) {
					return new Neighbor(
						$result['user_text'],
						$result['num_edits_in_data'],
						$result['edit-overlap'],
						$result['edit-overlap-inv'],
						new TimeOverlap(
							$result['day-overlap']['cos-sim'],
							$result['day-overlap']['level']
						),
						new TimeOverlap(
							$result['hour-overlap']['cos-sim'],
							$result['hour-overlap']['level']
						),
						$result['follow-up'] ?? [] );
				}, $json['results'] );
			}
		}

		// Bad status, or good status but response body contains either an error or bad data
		$this->logErrors( $status, $request->getContent() );
		if ( $json && isset( $json['error-type'] ) ) {
			return $json['error-type'];
		}
		return '';
	}

	/**
	 * @param Status $status
	 * @param string $content
	 * @return void
	 */
	private function logErrors( $status, $content ) {
		$this->logger->warning(
			Status::wrap( $status )->getWikiText( false, false, 'en' ),
			[
				'error' => $status->getErrorsByType( 'error' ),
				'caller' => __METHOD__,
				'content' => $content
			]
		);
	}
}
