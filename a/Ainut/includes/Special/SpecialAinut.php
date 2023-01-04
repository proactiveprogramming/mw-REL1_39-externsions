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
use HTMLForm;
use MediaWiki\MediaWikiServices;
use RawMessage;
use Status;
use User;

class SpecialAinut extends FormSpecialPage {
	/** @var Application */
	protected $app;
	/** @var ApplicationManager */
	protected $appManager;

	public function __construct() {
		parent::__construct( 'Ainut' );
	}

	public function isListed() {
		return false;
	}

	protected function getDisplayFormat() {
		return 'ooui';
	}

	public function execute( $par ) {
		$this->requireLogin();
		$this->checkReadOnly();

		$userId = $this->getUser()->getId();

		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$this->appManager = new ApplicationManager( $lb );
		if ( $par && $this->getUser()->isAllowed( 'ainut-admin' ) ) {
			$this->app = $this->appManager->findById( $par );
		} else {
			$this->app = $this->appManager->findLatestByUser( $userId );
		}

		if ( !$this->app ) {
			$this->app = new Application( $userId );
		}

		parent::execute( $par );
	}

	protected function checkExecutePermissions( User $user ) {
		parent::checkExecutePermissions( $user );

		if ( !$this->getConfig()->get( 'AinutApplicationsOpen' ) ) {
			throw new ErrorPageError( 'ainut', 'ainut-app-closed' );
		}
	}

	protected function getFormFields() {
		$appForm = new ApplicationForm();
		return $appForm->getFormFields(
			$this->app->getFields(),
			[ $this->getContext(), 'msg' ]
		);
	}

	protected function alterForm( HTMLForm $form ) {
		$this->getOutput()->addModuleStyles( 'ext.ainut.form.styles' );
		$form->setId( 'ainut-app-form' );
		$form->setSubmitTextMsg( 'ainut-app-submit' );

		if ( $this->app->getRevision() > 0 ) {
			$ts = $this->getLanguage()->timeanddate( $this->app->getTimestamp() );
			$msg = new RawMessage( '<div class="successbox">$1</div>' );
			$msg->params( $this->msg( 'ainut-app-old', $ts ) );
			$form->addPreText( $msg->parseAsBlock() );
		}

		$msg = new RawMessage( "<br><div class=successbox>$1</div>" );
		$msg->params( $this->msg( 'ainut-app-presave' ) );
		$form->addPostText( $msg->parseAsBlock() );

		$msg = new RawMessage( '<div class="warningbox">$1</div>' );
		$msg->params( $this->msg( 'ainut-app-guide' ) );
		$form->addPreText( $msg->parseAsBlock() );
	}

	public function onSubmit( array $data ) {
		$this->app->setRevision( $this->app->getRevision() + 1 );
		$this->app->setFields( $data );
		$this->app->setTimestamp( 0 );
		$this->appManager->saveApplication( $this->app );

		return Status::newGood();
	}

	public function onSuccess() {
		$out = $this->getOutput();

		$out->wrapWikiMsg( '<div class="successbox">$1</div>', 'ainut-app-saved' );
		$out->addReturnTo( $this->getPageTitle() );
	}
}
