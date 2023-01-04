<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use Wikimedia\ParamValidator\ParamValidator;

class ApiPolishDeclension extends ApiBase {

	/**
	 * @var LoggerInterface
	 */
	private $log;

	public function __construct( ApiMain $main, string $action ) {
		parent::__construct( $main, $action );
		$this->log = LoggerFactory::getInstance( 'Svetovid' );
	}

	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 */
	public function execute() {
		$user = $this->getUser();
		if ( $user->isAnon() ) {
			$this->dieWithError( 'Only registered users can access this endpoint.' );
		}

		if ( !$this->getPermissionManager()->userHasRight( $user, 'svetovid-search' ) ) {
			$this->dieWithError( "You are not allowed to do that." );
		}

		if ( $user->pingLimiter( 'polishdecl' ) ) {
			$this->dieWithError( 'Rate limit for declension exceeded, please try again later.' );
		}

		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'text' );

		$text = $params['text'];
		if ( strlen( $text ) > 256 )
			$this->dieWithError( 'Specified text exceeds the 256-character limit.' );

		$requestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
		$morfeuszURL = MediaWikiServices::getInstance()->getMainConfig()->get( 'SvetovidMorfeuszURL' );
		$url = wfAppendQuery( $morfeuszURL, [ 'query' => $text ] );
		$req = $requestFactory->create( $url );
		$status = $req->execute();

		if ( $status->getValue() !== 200 ) {
			$this->log->error(
				'Could not contact declension service',
				[
					'errors' => $status->getErrors(),
					'value' => $status->getValue(),
				]
			);
			$this->dieWithError(
				'Error communicating with the declension service. Please contact your local system administrator.'
			);
		}

		$response = json_decode( $req->getContent() );

		if ( isset( $response->words ) ) {
			foreach ( $response->words as $word ) {
				foreach ( $word->interpretations as $interp ) {
					$interp->shortText = SvetovidUtilities::transformTagsToText( $interp->tag );
					$interp->longText = SvetovidUtilities::transformTagsToText( $interp->tag, true );
				}
			}
		}

		$this->getResult()->addValue( null, "data", $response );
	}

	/**
	 * Return an array describing all possible parameters to this module
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'text' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				]
		];
	}
}
