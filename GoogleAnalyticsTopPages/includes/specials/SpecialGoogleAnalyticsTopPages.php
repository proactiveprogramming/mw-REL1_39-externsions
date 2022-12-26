<?php
class SpecialGoogleAnalyticsTopPages extends SpecialPage {
	/** @inheritDoc */
	public function __construct() {
		parent::__construct( 'GoogleAnalyticsTopPages' );
	}

	/** @inheritDoc */
	public function execute( $par ) {
	}

	/** @inheritDoc */
	protected function getGroupName() {
		return 'other';
	}
}
