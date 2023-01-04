<?php
/**
 * Implements Special:MostWikitextLinked
 *
 * Directly derived from the MostlinkedPage class in MediaWiki core.
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
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @author Rob Church <robchur@gmail.com>
 * @author Ostrzyciel
 */

use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * A special page to show pages ordered by the number of pages linking to them.
 *
 * @ingroup SpecialPage
 */
class SpecialMostWikitextLinked extends QueryPage {
	function __construct( $name = 'MostWikitextLinked' ) {
		parent::__construct( $name );
		$this->addHelpLink( 'Extension:AdvancedBacklinks' );
	}

	public function isExpensive() {
		return true;
	}

	function isSyndicated() {
		return false;
	}

	public function getQueryInfo() {
		return [
			'tables' => [ 'ab_links', 'page' ],
			'fields' => [
				'namespace' => 'abl_namespace',
				'title' => 'abl_title',
				'value' => 'COUNT(*)',
				'page_namespace'
			],
			'conds' => [
				'abl_through' => 0
			],
			'options' => [
				'HAVING' => 'COUNT(*) > 1',
				'GROUP BY' => [
					'abl_namespace', 'abl_title',
					'page_namespace'
				]
			],
			'join_conds' => [
				'page' => [
					'LEFT JOIN',
					[
						'page_namespace = abl_namespace',
						'page_title = abl_title'
					]
				]
			]
		];
	}

	/**
	 * Pre-fill the link cache
	 *
	 * @param IDatabase $db
	 * @param IResultWrapper $res
	 */
	function preprocessResults( $db, $res ) {
		$this->executeLBFromResultWrapper( $res );
	}

	/**
	 * Make a link to "what links here" for the specified title
	 *
	 * @param Title $title Title being queried
	 * @param string $caption Text to display on the link
	 * @return string
	 * @throws MWException
	 */
	function makeWlhLink( $title, $caption ) {
		$wlh = SpecialPage::getTitleFor( 'AdvancedBacklinks', $title->getPrefixedDBkey() );

		$linkRenderer = $this->getLinkRenderer();
		return $linkRenderer->makeKnownLink( $wlh, $caption );
	}

	/**
	 * Make links to the page corresponding to the item,
	 * and the "what links here" page for it
	 *
	 * @param Skin $skin Skin to be used
	 * @param object $result Result row
	 * @return string
	 * @throws MWException
	 */
	function formatResult( $skin, $result ) {
		$title = Title::makeTitleSafe( $result->namespace, $result->title );
		if ( !$title ) {
			return Html::element(
				'span',
				[ 'class' => 'mw-invalidtitle' ],
				Linker::getInvalidTitleDescription(
					$this->getContext(),
					$result->namespace,
					$result->title )
			);
		}

		$linkRenderer = $this->getLinkRenderer();
		$link = $linkRenderer->makeLink( $title );
		$wlh = $this->makeWlhLink(
			$title,
			$this->msg( 'nlinks' )->numParams( $result->value )->text()
		);

		return $this->getLanguage()->specialList( $link, $wlh );
	}

	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}
