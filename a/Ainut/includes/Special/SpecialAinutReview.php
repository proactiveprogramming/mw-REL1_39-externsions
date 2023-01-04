<?php
/**
 * Application form.
 *
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

namespace Ainut;

use ErrorPageError;
use FormSpecialPage;
use Html;
use HTMLForm;
use Linker;
use MediaWiki\MediaWikiServices;
use PermissionsError;
use RawMessage;
use Status;
use User;

class SpecialAinutReview extends FormSpecialPage {
	/** @var Application */
	protected $app;
	/** @var ApplicationManager */
	protected $appManager;
	/** @var Review */
	protected $rev;
	/** @var ReviewManager */
	protected $revManager;

	public function __construct() {
		parent::__construct( 'AinutReview' );
	}

	public function isListed() {
		return false;
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	public function execute( $par ) {
		$this->requireLogin();
		$this->checkExecutePermissions( $this->getUser() );

		$out = $this->getOutput();
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$this->appManager = new ApplicationManager( $lb );
		$this->revManager = new ReviewManager( $lb );

		$apps = $this->appManager->getFinalApplications();

		if ( !$par ) {
			$this->setHeaders();
			$listing = $this->getApplicationListing( $apps );
			$out->addHtml( $listing );
			return;
		}

		$this->checkReadOnly();

		foreach ( $apps as $app ) {
			if ( $app->getId() === (int)$par ) {
				$this->app = $app;

				$userId = $this->getUser()->getId();
				$this->rev = $this->revManager->findByUserAndApplication( $userId, $app->getId() );

				if ( !$this->rev ) {
					$this->rev = new Review( $userId, $this->app->getId() );
				}
				parent::execute( $par );
				return;
			}
		}

		$this->setHeaders();
		$out->wrapWikiMsg( '<div class="errorbox">$1</div>', 'ainoa-rev-err-id1' );
		$out->addReturnTo( $this->getPageTitle() );
	}

	protected function checkExecutePermissions( User $user ) {
		if ( !$this->getConfig()->get( 'AinutReviewsOpen' ) ) {
			throw new ErrorPageError( 'ainutreview', 'ainut-rev-closed' );
		}

		if ( !$user->isAllowed( 'ainut-review' ) ) {
			throw new PermissionsError( 'ainut-review' );
		}
	}

	private function getApplicationListing( array $apps ): string {
		$output = [];

		$rows = [];
		$rows[] = implode(
			[
				Html::element( 'th', [], $this->msg( 'ainut-revlist-name' )->text() ),
				Html::element( 'th', [], $this->msg( 'ainut-revlist-submitter' )->text() ),
				Html::element( 'th', [], $this->msg( 'ainut-revlist-reviewed' )->text() ),
			]
		);

		foreach ( $apps as $app ) {
			$rev = $this->revManager->findByUserAndApplication(
				$this->getUser()->getId(),
				$app->getId()
			);

			$reviewed = $rev ? '✓ ' : '';

			$link = Linker::link(
				$this->getPageTitle( $app->getId() ),
				$reviewed . $this->msg( 'ainut-revlist-act' )->escaped()
			);

			$rows[] = implode(
				[
					Html::element( 'td', [], $app->getFields()['title'] ),
					Html::element( 'td', [], User::newFromId( $app->getUser() )->getName() ),
					Html::rawElement( 'td', [], $link ),
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

		return implode( $output );
	}

	protected function getFormFields() {
		$appForm = new ApplicationForm();
		$fields = $appForm->getFormFields(
			$this->app->getFields(),
			[ $this->getContext(), 'msg' ]
		);

		foreach ( $fields as &$field ) {
			$field['disabled'] = true;
			$field['required'] = false;
			unset( $field['validation-callback'], $field['help-message'] );

			if ( isset( $field['type'] ) && $field['type'] === 'textarea' ) {
				$escapedText = htmlspecialchars( $field['default'] );
				$formattedText = str_replace( "\n", "<br>", $escapedText );
				$field['default'] = $formattedText;
				$field['type'] = 'info';
				$field['raw'] = 'true';
			}
		}

		$fields['title']['type'] = 'info';
		$fields['location']['type'] = 'info';

		$defaults = $this->rev->getFields();

		$newFields = [
			'review' => [
				'type' => 'textarea',
				'label-message' => 'ainut-rev-review',
				'help-message' => "ainut-rev-review-notice",
				'rows' => 10,
				'maxlength' => 1000,
				'default' => isset( $defaults['review'] ) ? $defaults['review'] : '',
				'required' => true,
				'cssclass' => 'mw-ainut-len-1000',
			],
			'submit' => [
				'type' => 'submit',
				'buttonlabel-message' => 'ainut-rev-submit',
			],
		];

		$fields = $newFields + $fields;

		return $fields;
	}

	protected function alterForm( HTMLForm $form ) {
		$this->getOutput()->addModuleStyles( 'ext.ainut.form.styles' );
		$form->setId( 'ainut-app-form' );
		if ( $this->rev->getId() !== null ) {
			$ts = $this->getLanguage()->timeanddate( $this->app->getTimestamp() );
			$msg = new RawMessage( '<div class="successbox">$1</div>' );
			$msg->params( $this->msg( 'ainut-rev-old', $ts ) );
			$form->addPreText( $msg->parseAsBlock() );
		}
		$form->suppressDefaultSubmit();

		$list = $this->getPageTitle()->getFullUrl();
		$msg = new RawMessage( '<div class="plainlinks">$1</div>' );
		$msg->params( $this->msg( 'ainut-rev-back', $list ) );
		$this->getOutput()->addSubtitle( $msg->parse() );
	}

	public function onSubmit( array $data ) {
		$this->rev->setFields( [ 'review' => $data['review'] ] );
		$this->rev->setTimestamp( 0 );
		$this->revManager->saveReview( $this->rev );

		return Status::newGood();
	}

	public function onSuccess() {
		$out = $this->getOutput();

		$out->wrapWikiMsg( '<div class="successbox">$1</div>', 'ainut-rev-saved' );
		$out->addReturnTo( $this->getPageTitle() );
	}
}
