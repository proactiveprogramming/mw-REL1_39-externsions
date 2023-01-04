<?php
/**
 * ResolveLink special page
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\LinkGuesser;

use Html;
use OOUI;
use SpecialPage;
use Title;

class SpecialChangeLink extends SpecialPage {
	public function __construct() {
		parent::__construct( 'changeLink', 'edit' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 */
	public function execute(
        $sub
    ) {
        $this->checkPermissions();
        
		$request = $this->getRequest();
        $performer = $this->getUser();
        $out = $this->getOutput();
        $this->setHeaders();

        // $out->addHTML( '<p>Sorry, this feature isn\'t available yet.</p>' );
        $this->showError( 'Sorry, this feature isn\'t available yet.' );
	}

	protected function getGroupName() {
		return 'other';
	}

    private function showError(
        string $message
    ) {
        $output = $this->getOutput();
        $output->enableOOUI();
        $output->addHTML(
            new OOUI\MessageWidget( [
                'type' => 'error',
                'label' => $message
            ] )
        );
    }

}