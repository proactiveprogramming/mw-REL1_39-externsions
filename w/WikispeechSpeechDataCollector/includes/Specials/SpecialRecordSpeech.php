<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Specials;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use Html;
use OOUI\ButtonWidget;
use OOUI\FieldLayout;
use OOUI\FieldsetLayout;
use OOUI\HorizontalLayout;
use OOUI\HtmlSnippet;
use OOUI\LabelWidget;
use OOUI\MultilineTextInputWidget;
use OOUI\Widget;
use SpecialPage;

/**
 * Special page for recording speech.
 *
 * Presents the user with a prompt to read and controls for recording,
 * reviewing and saving.
 *
 * @since 0.1.0
 */

class SpecialRecordSpeech extends SpecialPage {

	/**
	 * @since 0.1.0
	 */
	public function __construct() {
		parent::__construct( 'RecordSpeech' );
	}

	/**
	 * @since 0.1.0
	 * @param string|null $subpage
	 */
	public function execute( $subpage ) {
		$out = $this->getOutput();

		$out->addModuleStyles( [
			'ext.wikispeech-sdc.specialRecordSpeech.styles'
		] );
		$out->addModules( [
			'ext.wikispeech-sdc.specialRecordSpeech'
		] );
		$out->enableOOUI();
		$out->setPageTitle( $this->msg( 'recordspeech' ) );
		$this->addElements();
	}

	/**
	 * Add UI elements.
	 *
	 * @since 0.1.0
	 */
	private function addElements() {
		// Add a text box for the prompt.
		$promptField = new FieldLayout(
			new MultilineTextInputWidget( [
				'classes' => [
					'ext-wikispeech-sdc-record-prompt',
				],
				'readOnly' => true,
				'rows' => 5
			] )
		);

		// Button for starting recording and status label.
		$recordButton = new ButtonWidget( [
			'label' => $this->msg( 'wikispeech-sdc-record' )->text(),
			'flags' => [
				'primary',
				'progressive'
			],
			'classes' => [ 'ext-wikispeech-sdc-record' ],
			'infusable' => true,
			'disabled' => true
		] );
		$recordStatus = new LabelWidget( [
			'label' => $this->msg( 'wikispeech-sdc-status-starting' )->text(),
			'classes' => [ 'ext-wikispeech-sdc-status' ],
			'infusable' => true
		] );
		$recordField = new FieldLayout(
			new Widget( [
				'content' => new HorizontalLayout( [
					'items' => [ $recordButton, $recordStatus ]
				] )
			] )
		);

		// Preview player.
		$playerHtml = Html::element(
			'audio',
			$attribs = [
				'class' => 'ext-wikispeech-sdc-preview-player',
				'controls'
			]
		);
		$previewPlayer = new FieldLayout(
			new Widget( [
				'content' => new HtmlSnippet( $playerHtml )
			] )
		);

		// Save recording to storage.
		$saveButton = new ButtonWidget( [
			'label' => $this->msg( 'wikispeech-sdc-save' )->text(),
			'flags' => [ 'progressive' ]
		] );

		// Skip the prompt without saving.
		$skipButton = new ButtonWidget( [
			'label' => $this->msg( 'wikispeech-sdc-skip' )->text(),
			'flags' => [ 'destructive' ]
		] );

		$actionButtons = new FieldLayout(
			new Widget( [
				'content' => new HorizontalLayout( [
					'items' => [
						$saveButton,
						$skipButton
					]
				] )
			] )
		);

		$this->getOutput()->addHTML(
			new FieldsetLayout( [
				'items' => [
					$promptField,
					$recordField,
					$previewPlayer,
					$actionButtons
				]
			] )
		);
	}
}
