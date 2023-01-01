<?php

class SpecialOpauthLoginInfo extends UnlistedSpecialPage {

	private $templater;

	public function __construct() {
		parent::__construct( 'OpauthLoginInfo' );
		$this->templater = new TemplateParser( dirname(__FILE__).'/../templates/', true );
	}

	public function execute( $subPage ) {

		$this->getOutput()->addModules('ext.OpauthLogin.main');

		$this->getOutput()->setPageTitle( wfMessage('opauthlogin-info-page-title')->plain() );

		// Nothing to do if there is no session
		if( session_id() == '' ) {
			$this->displayRestrictionError();
		}

		// Nothing to do if session vars are empty
		if(
			!array_key_exists('opauth_login_provider', $_SESSION) ||
			!array_key_exists('opauth_login_uid', $_SESSION) ||
			!array_key_exists('opauth_login_info', $_SESSION)
		) {
			$this->displayRestrictionError();
		}

		if( $this->getRequest()->wasPosted() ) {
			$this->processRequest();
		}else {
			$this->printForm();
		}

	}

	private function processRequest() {

		// Fetch username from request
		$username = $this->getRequest()->getVal('op_username', $_SESSION['opauth_login_info']['name']);
		// Update info
		$info = $_SESSION['opauth_login_info'];
		$info['name'] = $username;
		// Store other vars from the session
		$uid = $_SESSION['opauth_login_uid'];
		$provider = $_SESSION['opauth_login_provider'];

		// Clear out session vars
		unset($_SESSION['opauth_login_info']);
		unset($_SESSION['opauth_login_uid']);
		unset($_SESSION['opauth_login_provider']);

		// Let the function to do the rest
		OpauthLoginHooks::authenticateCreateUser(
			$provider,
			$uid,
			$info,
			null
		);

	}

	private function printForm() {

		$data = array(
			'username' => $_SESSION['opauth_login_info']['name'],
			'msg_text' => wfMessage('opauthlogin-info-page-text')->plain(),
			'msg_error' => wfMessage('opauthlogin-info-page-error')->plain()
		);
		$html = $this->templater->processTemplate( 'info_form', $data );
		$this->getOutput()->addHTML( $html );

	}

}