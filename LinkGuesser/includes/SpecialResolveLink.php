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
use User;

class SpecialResolveLink extends SpecialPage {
	public function __construct() {
		parent::__construct( 'resolveLink' );
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 */
	public function execute(
        $sub
    ) {
		$request = $this->getRequest();
        $performer = $this->getUser();
        $out = $this->getOutput();
        $this->setHeaders();

        $requestedNamespaceId = $request->getText( 'ns' );
        $requestedDbKey = isset( $sub ) && $sub !== ''
            ? $sub
            : $request->getText( 'pg' );

        // Try creating a Title object with what we are passed
        // If result is null, this is invalid and we throw an error.
        $tryMakingTitle = Title::newFromText(
            $requestedDbKey,
            (int) $requestedNamespaceId
        );

        if ( $tryMakingTitle === null ) {
            $this->showError(
                'Invalid page title and/or namespace.'
            );
            return;
        } else {
            $originalTitle = $tryMakingTitle;
        }

        $results = ResultsResolver::retrieveResults( $originalTitle );
        
        $this->showResults(
            $results,
            $originalTitle,
            $performer
        );
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

    private function showResults(
        array $results,
        Title $originalTitle,
        User $performer
    ) {
        $out = $this->getOutput();
        $out->enableOOUI();

        // No validation on $originalTitle needed, because it's already been done before it was
        // passed into showResults()
        $originalTitleText = $originalTitle->getText();

        $out->addHTML(
            "<p>The page you requested, <b>$originalTitle</b>, doesn't exist. Are any of the below pages correct?</p>"
        );

        $out->addHTML(
            Html::rawElement( 'h3', [], 'Possible matches' )
        );

        $resultOut = Html::openElement( 'table', [
            'class' => 'wikitable'
        ] );
        // $resultOut .= Html::openElement( 'thead' );
        // $resultOut .= Html::openElement( 'tr' );
        // $resultOut .= Html::rawElement( 'th', [], 'Page name' );
        // $resultOut .= Html::rawElement( 'th', [], 'Go to page' );
        // if ( $performer->isAllowed( 'edit' ) ) {
        //     $resultOut .= Html::rawElement( 'th', [], 'Fix link' );
        // }
        // $resultOut .= Html::closeElement( 'tr' );
        // $resultOut .= Html::closeElement( 'thead' );
        $resultOut .= Html::openElement( 'tbody' );

        if ( !empty( $results ) ) {
            foreach ( $results as $resultTitleObj ) {
                $titleName = $resultTitleObj->getPrefixedText();
                $localUrl = $resultTitleObj->getLocalURL();
                $linkBtn = new OOUI\ButtonWidget( [
                    'label' => 'Go',
                    'href' => $localUrl,
                    'flags' => [
                        'primary',
                        'progressive'
                    ]
                ] );
                $fixBtn = new OOUI\ButtonWidget( [
                    'label' => 'Fix link',
                    'href' => Title::newFromText(
                        'ChangeLink',
                        NS_SPECIAL
                    )->getLocalURL(),
                    'flags' => [
                        'destructive'
                    ]
                ] );
                $resultOut .= Html::openElement( 'tr' );
                $resultOut .= Html::rawElement( 'td', [], $titleName );
                $resultOut .= Html::rawElement( 'td', [], $linkBtn );
                if ( $performer->isAllowed( 'edit' ) ) {
                    $resultOut .= Html::rawElement( 'td', [], $fixBtn );
                }
                $resultOut .= Html::closeElement( 'tr' );
            }
        } else {
            $resultOut .= Html::rawElement( 'td', [
                'colspan' => '3'
            ], 'No matches found.' );
        }

        $resultOut .= Html::closeElement( 'tbody' );
        $resultOut .= Html::closeElement( 'table' );

        $out->addHTML( $resultOut );

        $out->addHTML(
            Html::rawElement( 'h3', [], 'Create page' )
        );
        // TODO: Only show this if VisualEditor is detected
        $originalUrlVeBtn = new OOUI\ButtonWidget( [
            'label' => 'Create requested page in VisualEditor',
            'href' => $originalTitle->getLocalURL( [
                'veaction' => 'edit'
            ] ),
            'flags' => [
                'primary',
                'progressive'
            ]
        ] );
        $originalUrlBtn = new OOUI\ButtonWidget( [
            'label' => 'Create requested page using wikitext',
            'href' => $originalTitle->getLocalURL( [
                'action' => 'edit'
            ] ),
        ] );
        $out->addHTML( "<p>$originalUrlVeBtn $originalUrlBtn</p>" );
    }
}