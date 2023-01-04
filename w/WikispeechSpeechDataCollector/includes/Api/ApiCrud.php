<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Api;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use ApiBase;
use ApiMain;
use ApiUsageException;
use Exception;
use FormatJson;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Revision\RevisionStore;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionExecutor;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionMWAssociativeArrayMarshaller;
use Psr\Log\LoggerInterface;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\Rdbms\ILoadBalancer;

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 *
 * ?action=wikispeech-sdc-crud&transaction=json content
 *
 * Insecure and unlimited generic API access for
 * creating, updating, reading and deleting persistent objects.
 *
 * This is for development purposes.
 * It allows for communicating with the backend in a way that
 * hopefully is easy to implement as secure ad hoc API features
 * without having to make too many changes in the client code.
 *
 * @see CrudTransactionExecutor
 * @since 0.1.0
 */
class ApiCrud extends ApiBase {

	/** @var CrudContext */
	private $context;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @since 0.1.0
	 * @param ApiMain $mainModule
	 * @param string $moduleName Name of this module
	 * @param ILoadBalancer $dbLoadBalancer
	 * @param RevisionStore $revisionStore
	 * @param string $modulePrefix Prefix to use for parameter names
	 */
	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		ILoadBalancer $dbLoadBalancer,
		RevisionStore $revisionStore,
		$modulePrefix = ''
	) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
		$this->context = new CrudContext(
			$dbLoadBalancer,
			$this->getUser(),
			$revisionStore
		);
		// @todo inject
		$this->logger = LoggerFactory::getInstance( "Wikispeech-SDC-ApiCrud" );
	}

	/**
	 * @since 0.1.0
	 * @throws ApiUsageException
	 */
	public function execute() {
		$inputParameters = $this->extractRequestParams();
		$jsonStatus = FormatJson::parse(
			$inputParameters['transaction'],
			FormatJson::FORCE_ASSOC
		);
		if ( !$jsonStatus->isOK() ) {
			$this->logger->error( __METHOD__ . ': Failed to deserialize JSON. ' . $jsonStatus );
			$this->dieWithError( 'apierror-wikispeech-sdc-crud-deserialize-transaction-json' );
		}

		$transactionMarshaller = new CrudTransactionMWAssociativeArrayMarshaller();

		try {
			$transactionRequest = $transactionMarshaller->deserializeRequest(
				$jsonStatus->getValue()
			);
		} catch ( Exception $e ) {
			$this->logger->error( __METHOD__ . ': Failed to deserialize transaction. ' . $e );
			$this->dieWithError( 'apierror-wikispeech-sdc-crud-deserialize-transaction' );
		}

		$transactionExecutor = new CrudTransactionExecutor( $this->context );
		try {
			$transactionResponse = $transactionExecutor->execute( $transactionRequest );
		} catch ( Exception $e ) {
			$this->logger->error( __METHOD__ . ': Failed to execute transaction. ' . $e );
			$this->dieWithError( 'apierror-wikispeech-sdc-crud-execute-transaction' );
		}

		try {
			$serializedResponse = $transactionMarshaller->serializeResponse( $transactionResponse );
		} catch ( Exception $e ) {
			$this->logger->error( __METHOD__ . ': Failed to serialize response. ' . $e );
			$this->dieWithError( 'apierror-wikispeech-sdc-crud-serialize-response' );
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			$serializedResponse
		);
	}

	/**
	 * Specify what parameters the API accepts.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'transaction' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}

	/**
	 * Give examples of usage.
	 *
	 * @since 0.1.0
	 * @return array
	 */
	public function getExamplesMessages() {
		return [
			// phpcs:ignore Generic.Files.LineLength
			'action=wikispeech-sdc-crud&transaction={"create":{"Recording":[{"identity":null,"voiceOf":"61b25777-a277-406d-96c0-f1206ba64c69","recorded":"2020-11-02T11:15:44.000000Z","spokenDialect":"657c7d19-c871-4c59-82e6-45a7d3d00692","manuscriptPrompt":"4cba603f-ec8a-46ca-bde1-f44ae1a8d415"}]}}'
			=> 'apihelp-wikispeech-sdc-crud-example-1',
			// phpcs:ignore Generic.Files.LineLength
			'action=wikispeech-sdc-crud&transaction={"read":{"Recording":["bf8b38e3-12d4-49c0-be3a-ce52e0bfefcc"]}}'
			=> 'apihelp-wikispeech-sdc-crud-example-2',
			// phpcs:ignore Generic.Files.LineLength
			'action=wikispeech-sdc-crud&transaction={"delete":{"Recording":["bf8b38e3-12d4-49c0-be3a-ce52e0bfefcc"]}}'
			=> 'apihelp-wikispeech-sdc-crud-example-3',
		];
	}

}
