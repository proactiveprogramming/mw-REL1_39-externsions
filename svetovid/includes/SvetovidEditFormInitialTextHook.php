<?php

use MediaWiki\MediaWikiServices;

class SvetovidEditFormInitialTextHook {
	/**
	 * @var EditPage
	 */
	private $editPage;
	/**
	 * @var IContextSource
	 */
	private $context;
	/**
	 * @var BagOStuff
	 */
	private $cache;
	/**
	 * @var User
	 */
	private $user;

	/**
	 * Runs the hook.
	 * @param EditPage $editPage
	 */
	public static function run( EditPage $editPage ) {
		$instance = new self( $editPage );
		$instance->execute();
	}

	/**
	 * SvetovidEditFormInitialTextHook constructor.
	 * @param EditPage $editPage
	 */
	private function __construct( EditPage $editPage ) {
		$this->editPage = $editPage;
		$this->context = $editPage->getContext();
		$this->cache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		$this->user = $this->context->getUser();
	}

	/**
	 * Executes the hook instance.
	 */
	private function execute() {
		$request = $this->context->getRequest();
		if ( !array_key_exists( 'svetovid', $request->getQueryValues() ) ) {
			return;
		}

		$targetId = $request->getVal( 'svetovid' );

		$pm = MediaWikiServices::getInstance()->getPermissionManager();
		if ( $this->user->isAnon() || !$pm->userHasRight( $this->user, 'svetovid-search' ) ) {
			// TODO: display some sort of error, but this is a rare case anyway
			$this->showError( 'svetovid-not-allowed-to-edit' );
			return;
		}

		$rev = $this->editPage->getExpectedParentRevision();
		$revId = $rev ? $rev->getId() ?? 0 : 0;
		$params = $this->cache->get( 'svsearch_' . $targetId . '_' . $revId );
		if ( !$params ) {
			$this->showError( 'svetovid-cache-expired-or-invalid' );
			return;
		}

		$edits = $params->changes;
		$this->editPage->textbox1 = $params->text;

		if ( $edits == 0 ) {
			$this->showError( 'svetovid-no-edits-made' );
			return;
		}

		$target = Title::newFromID( $targetId );
		$this->editPage->summary =
			$this->context->msg( 'svetovid-edit-summary', $edits, $target->getPrefixedText() )->text();

		if ( $this->editPage->editFormPageTop ) {
			$this->editPage->editFormPageTop .= '<br />';
		}
		$this->editPage->editFormPageTop .=
			'<div id="sv-summary-top">' .
			$this->context->msg( 'svetovid-summary-top', $edits )->parse() .
			'</div>' .
			$this->context->msg( 'svetovid-responsibility-warning', $edits )->parse();

		$out = $this->editPage->getContext()->getOutput();
		$out->addModules( 'ext.svetovid.editor' );
		$out->addJsConfigVars( [ 'wgSvetovidTargetTitle' => $target->getPrefixedText() ] );

		$this->editPage->showDiff();
	}

	private function showError( $msg ) {
		if ( $this->editPage->editFormTextBeforeContent ) {
			$this->editPage->editFormTextBeforeContent .= '<br/>';
		}
		$text = $this->context->msg( $msg )->parse();
		$this->editPage->editFormTextBeforeContent .= '<strong class="error">' . $text . '</strong>';
	}
}
