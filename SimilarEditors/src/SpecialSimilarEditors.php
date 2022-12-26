<?php

namespace MediaWiki\Extension\SimilarEditors;

use Html;
use HTMLForm;
use OOUI\HtmlSnippet;
use OOUI\MessageWidget;
use SpecialPage;
use Status;

class SpecialSimilarEditors extends SpecialPage {

	/** @var Client */
	private $similarEditorsClient;

	/** @var ResultsFormatterFactory */
	private $resultsFormatterFactory;

	/** @var HTMLForm */
	private $form;

	/**
	 * @param Client $similarEditorsClient
	 * @param ResultsFormatterFactory $resultsFormatterFactory
	 */
	public function __construct(
		Client $similarEditorsClient,
		ResultsFormatterFactory $resultsFormatterFactory
	) {
		parent::__construct( 'SimilarEditors', 'similareditors', true );
		$this->similarEditorsClient = $similarEditorsClient;
		$this->resultsFormatterFactory = $resultsFormatterFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->checkPermissions();
		$this->outputHeader();
		$this->addHelpLink( 'Help:Extension:SimilarEditors' );

		$out = $this->getOutput();
		$out->addModuleStyles( 'mediawiki.pager.styles' );
		$out->addModuleStyles( 'ext.similarEditors.styles' );

		// Ensure the correct survey is added, in case multiple are enabled
		$out->addHTML( Html::element( 'div', [ 'id' => 'similareditors-survey-embed' ] ) );

		$fields = [
			'Target' => [
				'type' => 'user',
				'label-message' => 'similareditors-form-field-target-label',
				'placeholder-message' => 'similareditors-form-field-target-placeholder',
				// TODO: revert as part of T309675
				'exists' => false,
				'ipallowed' => true,
				'required' => true,
			],
		];

		$this->form = HTMLForm::factory( 'ooui', $fields, $this->getContext() );
		$this->form
			->setMethod( 'post' )
			->setWrapperLegendMsg( 'similareditors-form-legend' )
			->setSubmitTextMsg( 'similareditors-form-submit' )
			->setSubmitCallback( [ $this, 'onSubmit' ] );

		if ( $this->getRequest()->getVal( 'wpTarget' ) === null ) {
			$this->form->prepareForm()
				->displayForm( false );
		} else {
			$status = $this->form->show();
			if ( $status === true || $status instanceof Status && $status->isGood() ) {
				$this->onSuccess();
			}
		}
	}

	/**
	 * Placeholder for the submit callback as required by HTMLForm
	 *
	 * @param array $formData
	 * @return bool
	 */
	public function onSubmit( $formData ) {
		return true;
	}

	/**
	 * Handle successful form submission.
	 *
	 * This includes handling errors from the Similarusers service.
	 */
	public function onSuccess() {
		$out = $this->getOutput();

		$target = $this->getRequest()->getVal( 'wpTarget' );
		$result = $this->similarEditorsClient->getSimilarEditors( $target );

		if ( is_array( $result ) ) {
			$this->form->displayForm( true );
			$out->addModules( 'ext.similarEditors' );
			if ( count( $result ) > 0 ) {
				$resultsFormatter = $this->resultsFormatterFactory->createFormatter(
					$this->getLanguage()
				);
				$out->addHtml( $resultsFormatter->formatResults( $target, $result ) );
			} else {
				$out->addHtml( $this->msg( 'similareditors-no-results' ) );
			}
		} else {
			switch ( $result ) {
				// We encountered an error, but with no type or an unrecognized type.
				// Display the default message instead of a customized message.
				case 'database-refresh':
					$message = $this->msg( 'similareditors-error-database-refresh' );
					break;
				case 'user-no-account':
					$message = $this->msg( 'similareditors-error-user-no-account', $target );
					break;
				case 'user-bot':
					$message = $this->msg( 'similareditors-error-user-bot', $target );
					break;
				case 'user-no-edits':
					$message = $this->msg( 'similareditors-error-user-no-edits', $target );
					break;
				default:
					$message = 'similareditors-error-default';
			}
			$out->addHtml(
				new MessageWidget( [
					'type' => 'error',
					'label' => new HtmlSnippet(
						$this->msg( $message )->parse() ),
				] )
			);
			$this->form->displayForm( false );
		}
	}
}
