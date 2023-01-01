<?php
/**
 * SpecialPage for UserHistory extension
 *
 * @ingroup Extensions
 */

class SpecialUserHistory extends QueryPage {
	function __construct( $name = 'UserHistory' ) {
		parent::__construct( $name );
	}

	public function isExpensive() {
		return true;
	}

	public function isSyndicated() {
		return false;
	}

	public function getQueryInfo() {
		return [
			'tables' => [ 'user_history', 'page' ],
			'fields' => [
				'uh_timestamp' => 'uh_timestamp',
				'title' => 'page_title',
				'namespace' => 'page_namespace'
			],
			'conds' => [
				'uh_user_id' => $this->getUser()->getId(),
				"page_namespace != '" . NS_MEDIAWIKI . "'"
			],
			'join_conds' => [
				'page' => [ 'LEFT JOIN', [ 'user_history.uh_page_id=page.page_id' ] ]
			]
		];
	}

	public function sortDescending() {
		return true;
	}

	public function getOrderFields() {
		return [ 'uh_timestamp' ];
	}

	protected function getGroupName() {
		return 'other';
	}

	/**
	 * Show the page to the user
	 *
	 * @param string $sub The subpage string argument (if any).
	 *
	 * @return bool
	 */
	public function execute( $sub ) {
		$this->requireLogin();
		$this->getOutput()->addWikiMsg( 'userhistory-description' );

		parent::execute( $sub );
	}

	/**
	 * Format the results
	 *
	 * @param Skin $skin
	 * @param object $result Result row
	 * @return string|bool String or false to skip
	 */
	function formatResult( $skin, $result ) {
		$nt = Title::makeTitleSafe( $result->namespace, $result->title );

		$page = Linker::linkKnown( $nt );
		$details = $this->getLanguage()->userTimeAndDate( $result->uh_timestamp, $this->getUser() );

		return $this->getLanguage()->specialList( $page, $details );
	}
}
