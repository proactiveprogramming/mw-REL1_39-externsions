<?php

namespace MediaWiki\Extension\UnifiedTaskOverview\Special;

use Html;
use OOUI\SearchInputWidget;
use SpecialPage;

class UnifiedTaskOverview extends SpecialPage {

	/**
	 */
	public function __construct() {
		parent::__construct( 'UnifiedTaskOverview' );
	}

	/**
	 * @param string $par
	 */
	public function execute( $par ) {
		$output = $this->getOutput();

		$output->addModules( 'ext.unifiedTaskOverview.specialPage' );

		$output->enableOOUI();
		$search = Html::openElement( 'div', [
			'style' => 'width: 100%; text-align: center; margin-top:1rem;'
		] );
		$search .= new SearchInputWidget( [
			'infusable' => true,
			'id' => 'taskSearch',
			'icon' => 'search',
			'placeholder' => $this->msg( 'unifiedtaskoverview-search-placeholder' )->text(),
		] );
		$search .= Html::closeElement( 'div' );
		$output->addHTML( $search );
		$output->addHTML( Html::element( 'div', [ 'id' => 'unifiedTaskOverview-tiles' ] ) );
		$this->setHeaders();
	}
}
