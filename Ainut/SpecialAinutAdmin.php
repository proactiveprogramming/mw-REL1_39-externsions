<?php
/**
 * Application form.
 *
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

namespace Ainut;

use Html;
use Linker;
use MediaWiki\MediaWikiServices;
use PermissionsError;
use SpecialPage;
use SplObjectStorage;
use User;

class SpecialAinutAdmin extends SpecialPage {
	/** @var ApplicationManager */
	protected $appManager;
	/** @var ReviewManager */
	protected $revManager;

	public function __construct() {
		parent::__construct( 'AinutAdmin' );
	}

	public function isListed() {
		return false;
	}

	public function execute( $par ) {
		$this->requireLogin();

		$this->checkExecutePermissions( $this->getUser() );
		$this->setHeaders();

		$out = $this->getOutput();

		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$this->appManager = new ApplicationManager( $lb );
		$this->revManager = new ReviewManager( $lb );

		if ( $par === 'export' ) {
			$format = $out->getRequest()->getText( 'format', 'Word2007' );
			$appId = $out->getRequest()->getInt( 'app', -1 );
			$appReviews = null;

			if ( $appId === -1 ) {
				$appReviews = $this->getAllReviewsByApplication();
				$filename = $this->msg( 'ainut-export-summary' )->plain();
			} else {
				$app = $this->appManager->findById( $appId );
				if ( $app ) {
					$appReviews = new SplObjectStorage();
					$appReviews[$app] = $this->revManager->findByApplication( $app->getId() );
					$filename = $app->getFields()['title'];
				}
			}

			if ( $appReviews ) {
				$de = new DocumentExporter();
				$doc = $de->createDocument( $appReviews, $this->getContext() );
				$de->printDocument( $doc, $filename, $format );
				$out->disable();
				return;
			}
		}

		$appReviews = $this->getAllReviewsByApplication();
		$listing = $this->getApplicationListing( $appReviews );
		$out->addHtml( $listing );
	}

	protected function checkExecutePermissions( User $user ) {
		if ( !$user->isAllowed( 'ainut-admin' ) ) {
			throw new PermissionsError( 'ainut-admin' );
		}
	}

	private function getAllReviewsByApplication() {
		$s = new SplObjectStorage();
		$apps = $this->appManager->getFinalApplications();
		foreach ( $apps as $app ) {
			$s[$app] = $this->revManager->findByApplication( $app->getId() );
		}

		return $s;
	}

	private function getApplicationListing( SplObjectStorage $appReviews ) {
		$output = [];

		$lang = $this->getLanguage();

		$rows = [];
		$rows[] = implode(
			[
				Html::element( 'th', [], $this->msg( 'ainut-revlist-name' )->text() ),
				Html::element( 'th', [], $this->msg( 'ainut-revlist-submitter' )->text() ),
				Html::element( 'th', [], $this->msg( 'ainut-revlist-reviewcount' )->text() ),
				Html::element( 'th', [], $this->msg( 'ainut-revlist-export' )->text() ),
			]
		);

		foreach ( $appReviews as $app ) {
			$reviews = $appReviews[$app];

			$exportLinks = [];
			$exportLinks[] = Linker::link(
				$this->getPageTitle( 'export' ),
				htmlspecialchars( 'DOC' ),
				[ 'target' => '_blank' ],
				[ 'app' => $app->getId() ]
			);
			$exportLinks[] = Linker::link(
				$this->getPageTitle( 'export' ),
				htmlspecialchars( 'PDF' ),
				[ 'target' => '_blank' ],
				[ 'app' => $app->getId(), 'format' => 'PDF' ]
			);

			$rows[] = implode(
				[
					Html::element( 'td', [], $app->getFields()['title'] ),
					Html::element( 'td', [], User::newFromId( $app->getUser() )->getName() ),
					Html::element( 'td', [], $lang->formatNum( count( $reviews ) ) ),
					Html::rawElement( 'td', [], implode( ' | ', $exportLinks ) ),
				]
			);
		}

		$rows = array_map(
			function ( $x ) {
				return Html::rawElement( 'tr', [], $x );
			},
			$rows
		);
		$contents = implode( $rows );

		$output[] = Html::rawElement( 'table', [ 'class' => 'wikitable sortable' ], $contents );

		global $wgUseMediaWikiUIEverywhere;
		$x = $wgUseMediaWikiUIEverywhere;
		$wgUseMediaWikiUIEverywhere = true;

		$output[] = Html::linkButton(
			$this->msg( 'ainut-export-summary-as-doc' ),
			[ 'href' => $this->getPageTitle( 'export' )->getLocalUrl() ],
			[ 'mw-ui-button', 'mw-ui-big' ]
		);

		$output[] = Html::linkButton(
			$this->msg( 'ainut-export-summary-as-pdf' ),
			[ 'href' => $this->getPageTitle( 'export' )->getLocalUrl( [ 'format' => 'PDF' ] ) ],
			[ 'mw-ui-button', 'mw-ui-big' ]
		);

		$wgUseMediaWikiUIEverywhere = $x;

		/*	$fields = [];
			$fields[] = \Html::hidden( 'action', $this->getPageTitle( 'export' )->getLocalUrl() );
			$fields[] = \Html::submitButton( 'action', $this->getPageTitle( 'export' )->getLocalUrl() );
			$form = \Html::element( 'form', [], implode( $fields );*/

		return implode( $output );
	}
}
