<?php
/**
 * Implements Special:WikitextWantedPages
 *
 * Directly derived from the WantedPagesPage and WantedQueryPage classes in MediaWiki core.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

/**
 * A special page that lists most linked pages that does not exist
 *
 * @ingroup SpecialPage
 */
class SpecialWikitextWantedPages extends WantedQueryPage {

	function __construct( $name = 'WikitextWantedPages' ) {
		parent::__construct( $name );
		$this->addHelpLink( 'Extension:AdvancedBacklinks' );
	}

	function isIncludable() {
		return true;
	}

	function execute( $par ) {
		$inc = $this->including();

		if ( $inc ) {
			$this->limit = (int)$par;
			$this->offset = 0;
		}
		$this->setListoutput( $inc );
		$this->shownavigation = !$inc;
		parent::execute( $par );
	}

	function getQueryInfo() {
		$dbr = wfGetDB( DB_REPLICA );
		$count = $this->getConfig()->get( 'WantedPagesThreshold' ) - 1;

		return [
			'tables' => [
				'ab_links',
				'pg1' => 'page',
				'pg2' => 'page'
			],
			'fields' => [
				'namespace' => 'abl_namespace',
				'title' => 'abl_title',
				'value' => 'COUNT(*)'
			],
			'conds' => [
				'pg1.page_namespace IS NULL',
				'abl_namespace NOT IN (' . $dbr->makeList( [ NS_USER, NS_USER_TALK ] ) . ')',
				'abl_through = 0',
				'pg2.page_namespace != ' . $dbr->addQuotes( NS_MEDIAWIKI ),
			],
			'options' => [
				'HAVING' => [
					'COUNT(*) > ' . $dbr->addQuotes( $count ),
					'COUNT(*) > SUM(pg2.page_is_redirect)'
				],
				'GROUP BY' => [ 'abl_namespace', 'abl_title' ]
			],
			'join_conds' => [
				'pg1' => [
					'LEFT JOIN', [
						'pg1.page_namespace = abl_namespace',
						'pg1.page_title = abl_title'
					]
				],
				'pg2' => [ 'LEFT JOIN', 'pg2.page_id = abl_from' ]
			]
		];
	}

	/**
	 * Make a "what links here" link for a given title
	 * Almost the same as the function in WantedQueryPage.php in core.
	 * The only difference is the special page title in the link.
	 *
	 * @param Title $title Title to make the link for
	 * @param object $result Result row
	 * @return string
	 * @throws MWException
	 */
	protected function makeWlhLink( $title, $result ) {
		$wlh = SpecialPage::getTitleFor( 'AdvancedBacklinks', $title->getPrefixedText() );
		$label = $this->msg( 'nlinks' )->numParams( $result->value )->text();
		return $this->getLinkRenderer()->makeLink( $wlh, $label );
	}

	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}