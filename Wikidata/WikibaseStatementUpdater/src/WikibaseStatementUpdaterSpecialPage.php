<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater;

use Html;
use HTMLForm;
use JobQueueGroup;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchList;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchListStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Batch\BatchStore;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\LineTooShort;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\OnlyQItemsSupported;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\ParsingFailure;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\QualifiersUnsupported;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\UnsupportedCommand;
use MediaWiki\Extension\WikibaseStatementUpdater\Parser\V1Parser;
use MediaWiki\Extension\WikibaseStatementUpdater\Updater\UpdateJob;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\OAuthClient\Client;
use MediaWiki\OAuthClient\Exception;
use MediaWiki\OAuthClient\Token;
use OOUI\ButtonGroupWidget;
use OOUI\ButtonInputWidget;
use OOUI\ButtonWidget;
use OOUI\FormLayout;
use OOUI\HtmlSnippet;
use Psr\Log\LoggerInterface;
use SpecialPage;
use ThrottledError;
use Title;
use Wikimedia\Rdbms\ILoadBalancer;
use const DB_PRIMARY;
use const DB_REPLICA;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class WikibaseStatementUpdaterSpecialPage extends SpecialPage {
	/** @var AccessTokenStore */
	private $accessTokenStore;
	/** @var Client */
	private $client;
	/** @var BatchListStore */
	private $batchListStore;
	/** @var BatchStore */
	private $batchStore;
	/** @var LoggerInterface */
	private $logger;
	/** @var JobQueueGroup */
	private $jobQueueGroup;
	/** @var ILoadBalancer */
	private $dbLoadBalancer;

	public function __construct(
		AccessTokenStore $accessTokenStore,
		Client $client,
		BatchListStore $batchListStore,
		BatchStore $batchStore,
		LoggerInterface $logger,
		JobQueueGroup $jobQueueGroup,
		ILoadBalancer $dbLoadBalancer
	) {
		parent::__construct( 'WikibaseStatementUpdater' );
		$this->accessTokenStore = $accessTokenStore;
		$this->client = $client;
		$this->batchListStore = $batchListStore;
		$this->batchStore = $batchStore;
		$this->logger = $logger;
		$this->jobQueueGroup = $jobQueueGroup;
		$this->dbLoadBalancer = $dbLoadBalancer;
	}

	public static function factory(): self {
		$services = Services::getInstance();
		$mwServices = MediaWikiServices::getInstance();
		return new self(
			$services->getAccessTokenStore(),
			$services->getOAuthClient(),
			$services->getBatchListStore(),
			$services->getBatchStore(),
			LoggerFactory::getInstance( 'WikibaseStatementUpdater' ),
			$mwServices->getJobQueueGroup(),
			$mwServices->getDBLoadBalancer()
		);
	}

	public function getDescription(): string {
		return $this->msg( 'wsu-special-wikibasestatementupdater' )->text();
	}

	protected function getGroupName(): string {
		return 'wikibase';
	}

	public function execute( $subPage ) {
		parent::execute( $subPage );
		$this->requireLogin();

		$request = $this->getRequest();

		$oauthVerifier = $request->getVal( 'oauth_verifier' );
		if ( $oauthVerifier !== null ) {
			$this->finishAuth( $oauthVerifier );
			return;
		}

		if ( $subPage === 'auth' ) {
			$this->beginAuth();
			return;
		}

		$accessToken = $this->accessTokenStore->get( $this->getUser() );
		if ( $accessToken ) {
			try {
				$this->client->identify( $accessToken );
			} catch ( Exception $e ) {
				$accessToken = null;
				$this->logger->warning( $e->getMessage() );
			}
		}

		if ( !$accessToken ) {
			$this->getOutput()->addWikiMsg(
				'wsu-give-permission',
				'Special:WikibaseStatementUpdater/auth'
			);
			return;
		}

		if ( $subPage === 'new' ) {
			$this->showBatchCreationForm();
			return;
		}

		$batchId = $request->getVal( 'batch' );
		if ( $batchId === null ) {
			$this->showBatchList();
			return;
		}

		$ok =
			$request->wasPosted() &&
			$this->getUser()->matchEditToken( $request->getVal( 'token' ) );
		$redirectAfterAction = $this->getFullTitle()->getLocalURL( [ 'batch' => $batchId ] );

		if ( $request->getCheck( 'start' ) ) {
			if ( !$ok ) {
				throw new ThrottledError();
			}

			$this->scheduleBatch( (int)$batchId );
			$this->getOutput()->redirect( $redirectAfterAction );
		} elseif ( $request->getCheck( 'stop' ) ) {
			if ( !$ok ) {
				throw new ThrottledError();
			}

			$this->stopBatch( (int)$batchId );
			$this->getOutput()->redirect( $redirectAfterAction );
		} else {
			$this->showBatch( (int)$batchId );
		}
	}

	private function finishAuth( string $oauthVerifier ): void {
		$request = $this->getRequest();
		$response = $request->response();

		$requestToken = new Token(
			$request->getCookie( 'wsuRequestTokenKey' ),
			$request->getCookie( 'wsuRequestTokenSecret' )
		);

		$accessToken = $this->client->complete( $requestToken, $oauthVerifier );
		$response->setCookie( 'wsuRequestTokenKey', '' );
		$response->setCookie( 'wsuRequestTokenSecret', '' );

		try {
			$this->client->identify( $accessToken );
		} catch ( Exception $e ) {
			$this->getOutput()->wrapWikiTextAsInterface(
				'errorbox',
				$e->getMessage()
			);
			$this->logger->warning( $e->getMessage() );
			return;
		}

		$this->accessTokenStore->set( $this->getUser(), $accessToken );
		$this->getOutput()->redirect( $this->getPageTitle()->getLocalURL() );
	}

	private function beginAuth(): void {
		/** @var Token $requestToken */
		[ $authUrl, $requestToken ] = $this->client->initiate();
		$response = $this->getRequest()->response();
		$response->setCookie( 'wsuRequestTokenKey', $requestToken->key, null );
		$response->setCookie( 'wsuRequestTokenSecret', $requestToken->secret, null );
		$this->getOutput()->redirect( $authUrl );
	}

	private function showBatchCreationForm() {
		$form = HTMLForm::factory(
			'ooui',
			$this->getFormFields(),
			$this->getContext(),
		);
		$form->setSubmitCallback( [ $this, 'createBatch' ] );

		$form->show();
	}

	private function getFormFields(): array {
		$fields = [];

		$fields['title'] = [
			'type' => 'text',
			'label-message' => 'wsu-wsu-batch-title',
			'maxlength' => 255,
			'required' => true,
		];

		$fields['input'] = [
			'type' => 'textarea',
			'label-message' => 'wsu-wsu-batch-contents',
			'validation-callback' => function ( $a ) {
				if ( $a === null ) {
					return true;
				}
				$parser = new V1Parser();
				try {
					$parser->parse( $a );
					return true;
				} catch ( ParsingFailure $e ) {
					return $this->formatError( $e );
				}
			},
		];

		return $fields;
	}

	private function formatError( ParsingFailure $e ): string {
		$tokens = null;
		if ( $e instanceof LineTooShort ) {
			$detailsMessage = $this->msg( 'wsu-wsu-error-linetooshort', $e->getLine() );
		} elseif ( $e instanceof OnlyQItemsSupported ) {
			$detailsMessage = $this->msg( 'wsu-wsu-error-onlyq', $e->getLine() );
			$tokens = [ 0, 0 ];
		} elseif ( $e instanceof QualifiersUnsupported ) {
			$detailsMessage = $this->msg( 'wsu-wsu-error-noqualifiers', $e->getLine() );
			$tokens = [ 3, 10 ];
		} elseif ( $e instanceof UnsupportedCommand ) {
			$detailsMessage = $this->msg( 'wsu-wsu-error-unsupportedcommand', $e->getLine() );
			$tokens = [ 1, 1 ];
		} else {
			$detailsMessage = $this->msg( 'wsu-wsu-error-unknown', $e->getLine() );
		}

		$errorMessage = $this->msg( 'wsu-wsu-error', $detailsMessage );

		$lineText = htmlspecialchars( $e->getLineText() );
		if ( $tokens ) {
			$tokenValues = explode( "\t", $lineText, 10 );

			for ( $i = $tokens[0]; $i <= $tokens[1]; $i++ ) {
				$tokenValues[$i] = '<i>' . $tokenValues[$i] . '</i>';
			}
			$lineText = implode( "\t", $tokenValues );
		}

		return $errorMessage->parseAsBlock() . '<pre>' . $lineText . '</pre>';
	}

	/**
	 * @param int $batchId
	 * @suppress PhanTypeMismatchArgumentNullable FIXME $list can be null
	 */
	private function scheduleBatch( int $batchId ) {
		$list = $this->batchListStore->get( $batchId );
		$this->batchListStore->updateStatus( $list, 'started' );

		$items = $this->batchStore->getRecords( $list );
		$jobs = [];
		foreach ( $items as $item ) {
			$output = $item->getOutput();
			$status = $output['status'] ?? null;
			if ( $status === 'ok' ) {
				continue;
			}

			$item->setOutput( [] );
			$this->batchStore->updateOutput( $item );

			$jobs[] = UpdateJob::newJob( $item->getId(), $list->getId() );
		}

		$this->jobQueueGroup->push( $jobs );
	}

	private function stopBatch( int $batchId ) {
		$list = $this->batchListStore->get( $batchId );
		if ( $list ) {
			$this->batchListStore->updateStatus( $list, 'stopped' );
		}
	}

	private function showBatch( int $id ): void {
		$db = $this->dbLoadBalancer->getConnectionRef( DB_REPLICA );
		$batchListStore = new BatchListStore( $db );
		$list = $batchListStore->get( $id );

		$output = $this->getOutput();
		$output->enableOOUI();
		$output->addModules( 'ext.wsu' );

		$form = new FormLayout(
			[
				'method' => 'POST',
				'action' => $this->getPageTitle()->getLinkURL( [ 'batch' => $id ] ),
			]
		);

		$form->appendContent(
			new HtmlSnippet( Html::hidden( 'token', $this->getUser()->getEditToken() ) )
		);

		$buttons = [];
		$buttons[] = new ButtonWidget(
			[
				'icon' => 'previous',
				'href' => $this->getPageTitle()->getLinkURL(),
				'label' => $this->msg( 'wsu-goback' )->plain(),
			]
		);

		if ( $list ) {
			$buttons[] = new ButtonInputWidget(
				[
					'icon' => 'reload',
					'type' => 'submit',
					'name' => 'start',
					'label' => $this->msg( 'wsu-start' )->plain(),
					'flags' => [ 'primary', 'progressive' ],
				]
			);
			$buttons[] = new ButtonInputWidget(
				[
					'icon' => 'hand',
					'type' => 'submit',
					'name' => 'stop',
					'label' => $this->msg( 'wsu-stop' )->plain(),
					'flags' => [ 'primary', 'destructive' ],
				]
			);
		}

		$form->appendContent( new ButtonGroupWidget( [ 'items' => $buttons ] ) );
		$output->addHTML( $form );

		if ( !$list ) {
			$output->wrapWikiTextAsInterface(
				'errorbox',
				$this->msg( 'wsu-unknown-batch' )->plain()
			);
			return;
		}

		$output->addHTML(
			Html::element( 'h2', [ 'class' => 'mw-ext-wsu-heading' ], $list->getName() )
		);

		$batchStore = new BatchStore( $db );
		$items = $batchStore->getRecords( $list );

		$output->addHTML(
			<<<HTML
			<table class="wikitable sortable">
			<thead>
				<th>{$this->msg( 'wsu-batchtable-subject' )->escaped()}</th>
				<th>{$this->msg( 'wsu-batchtable-command' )->escaped()}</th>
				<th>{$this->msg( 'wsu-batchtable-id' )->escaped()}</th>
				<th>{$this->msg( 'wsu-batchtable-value' )->escaped()}</th>
				<th>{$this->msg( 'wsu-batchtable-status' )->escaped()}</th>
			</thead>
			HTML
		);

		foreach ( $items as $item ) {
			$subject = Html::element(
				'a',
				[
					'href' => Title::makeTitle(
						120,
						$item->getItem()->getSubject(),
						$item->getItem()->getCommandId()
					)->getLinkURL(),
				],
				$item->getItem()->getSubject()
			);
			$commandType = $this->msg( 'wsu-command-P' )->escaped();
			$commandId = htmlspecialchars( $item->getItem()->getCommandId() );
			$value = htmlspecialchars( $item->getItem()->getValue() );

			$itemOutput = $item->getOutput();
			$itemStatus = $itemOutput['status'] ?? '';
			if ( $itemStatus === 'ok' ) {
				$revisionId = $itemOutput['revisionId'] ?? '';
				$diffUrl = wfAppendQuery(
					wfScript(),
					[
						'oldid' => $revisionId,
						'diff' => 'prev',
					]
				);
				$status = Html::element( 'a', [ 'href' => $diffUrl ], $itemStatus );
			} else {
				if ( isset( $itemOutput['i18n'] ) ) {
					$itemMessage = $this->msg( ...$itemOutput['i18n'] )->text();
				} else {
					$itemMessage = $itemOutput['message'] ?? '';
				}
				$status = Html::element( 'span', [ 'title' => $itemMessage ], $itemStatus );
			}

			$output->addHTML(
				<<<HTML
				<tr>
					<td>$subject</td>
					<td>$commandType</td>
					<td>$commandId</td>
					<td>$value</td>
					<td>$status</td>
				</tr>
				HTML
			);
		}

		$output->addHTML(
			Html::closeElement( 'table' )
		);
	}

	private function showBatchList() {
		$db = $this->dbLoadBalancer->getConnectionRef( DB_REPLICA );
		$batchListStore = new BatchListStore( $db );
		$batches = $batchListStore->getForUser( $this->getUser() );
		$output = $this->getOutput();
		$output->enableOOUI();

		$form = new FormLayout( [ 'action' => $this->getPageTitle( 'new' )->getLinkURL() ] );
		$form->appendContent(
			new ButtonInputWidget(
				[
					'icon' => 'add',
					'type' => 'submit',
					'label' => $this->msg( 'wsu-create-batch' )->plain(),
					'flags' => [ 'primary', 'progressive' ],
				]
			)
		);
		$output->addHtml( $form );

		if ( $batches ) {
			$output->addWikiMsg( 'wsu-your-batches' );

			$output->addHTML( '<ul>' );

			foreach ( $batches as $batch ) {
				$output->addHTML(
					Html::rawElement(
						'li',
						[],
						Html::element(
							'a',
							[
								'href' => $this->getPageTitle()->getLocalURL(
									[
										'batch' => $batch->getId(),
									]
								),
							],
							"#{$batch->getId()} {$batch->getName()}"
						)
					)
				);
			}

			$output->addHTML( '</ul>' );
		}
	}

	public function createBatch( array $data ) {
		$db = $this->dbLoadBalancer->getConnectionRef( DB_PRIMARY );

		$parser = new V1Parser();
		$items = $parser->parse( $data['input'] );

		$batchListStore = new BatchListStore( $db );
		$batchList = new BatchList( $data['title'], $this->getUser() );
		$batchListRecord = $batchListStore->add( $batchList );

		$batchStore = new BatchStore( $db );
		$batchStore->addItems( $batchListRecord, $items );

		$this->getOutput()->redirect(
			$this->getPageTitle()->getLocalURL(
				[
					'batch' => $batchListRecord->getId(),
				]
			)
		);
	}
}
