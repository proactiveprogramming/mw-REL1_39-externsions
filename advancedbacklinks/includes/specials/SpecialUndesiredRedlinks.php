<?php
/**
 * Implements Special:RedlinkAllergic
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

use MediaWiki\Linker\LinkTarget;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * A special page to show pages ordered by the number of pages linking to them.
 *
 * @ingroup SpecialPage
 */
class SpecialUndesiredRedlinks extends QueryPage {

	/**
	 * SpecialUndesiredRedlinks constructor.
	 * @param string $name
	 */
	function __construct( $name = 'UndesiredRedlinks' ) {
		parent::__construct( $name );
		$this->addHelpLink( 'Extension:AdvancedBacklinks' );
	}

	/**
	 * @return bool
	 */
	public function isExpensive() {
		return true;
	}

	/**
	 * @return bool
	 */
	function isSyndicated() {
		return false;
	}

	/**
	 * @return array
	 */
	public function getQueryInfo() {
		return [
			'tables' => [
				'p' => 'page',
				'pp_target' => 'page_props',
				'ab_links',
				'target' => 'page',
				'pp_through' => 'page_props'
			],
			'fields' => [
				'from' => 'p.page_id',
				'title' => 'abl_title',
				'namespace' => 'abl_namespace',
				'through' => 'abl_through',
				'hidden_through' => 'abl_hidden_through'
			],
			'conds' => [
				'(pp_target.pp_propname = \'redlinkallergic\' OR pp_through.pp_page IS NOT NULL)',
				'target.page_id IS NULL'
			],
			'join_conds' => [
				'pp_target' => [
					'JOIN',
					[
						'pp_page = p.page_id',
					]
				],
				'ab_links' => [
					'JOIN',
					[
						'abl_from = p.page_id',
					]
				],
				'target' => [
					'LEFT JOIN',
					[
						'target.page_namespace = abl_namespace',
						'target.page_title = abl_title'
					]
				],
				'pp_through' => [
					'LEFT JOIN',
					[
						'(pp_through.pp_page = abl_through OR pp_through.pp_page = abl_hidden_through)',
						'pp_through.pp_propname' => 'redlinkallergicthrough'
					]
				]
			],
			'options' => [
				'DISTINCT'
			]
		];
	}

	/**
	 * @return array|string[]
	 */
	public function getOrderFields() {
		//interestingly, DBAL does not escape ORDER BY arguments
		return [ '`from`', 'through' ];
	}

	/**
	 * @return bool
	 */
	public function sortDescending() {
		return false;
	}

	/**
	 * Transforms the query results into a cacheable form.
	 *
	 * @param $res
	 *
	 * @return array
	 */
	private function transformResults( $res ) : array {
		$index = [];
		$values = [];
		foreach ( $res as $i => $row ) {
			$through = $row->through ?: $row->hidden_through;

			if ( isset(
				$index[$row->from][$row->namespace][$row->title][$through]
			) ) {
				// The index already contains this link
				continue;
			}

			$index[$row->from][$row->namespace][$row->title][$through] = true;
			$values[] = [
				'abd_from' => $row->from,
				'abd_namespace' => $row->namespace,
				'abd_title' => $row->title,
				'abd_through' => $through
			];
		}

		return $values;
	}

	/**
	 * Clear the cache and save new results
	 *
	 * @param int|bool $limit Limit for SQL statement
	 * @param bool $ignoreErrors Whether to ignore database errors
	 * @throws DBError|Exception
	 * @return bool|int
	 */
	public function recache( $limit, $ignoreErrors = true ) {
		$dbw = wfGetDB( DB_PRIMARY );
		if ( !$dbw ) {
			return false;
		}

		try {
			// Do query
			$res = $this->reallyDoQuery( $limit );
			$num = false;
			if ( $res ) {
				// Transform results, eliminate duplicates
				$vals = $this->transformResults( $res );
				$num = $res->numRows();

				$dbw->doAtomicSection(
					__METHOD__,
					function ( IDatabase $dbw, $fname ) use ( $vals ) {
						// Clear out any old cached data
						$dbw->delete( 'ab_undesired', '*',	$fname );
						// Save results into the querycache table on the master
						if ( count( $vals ) ) {
							$dbw->insert( 'ab_undesired', $vals, $fname );
						}
						// Update the querycache_info record for the page
						$dbw->delete(
							'querycache_info',
							[ 'qci_type' => $this->getName() ],
							$fname
						);
						$dbw->insert(
							'querycache_info',
							[
								'qci_type' => $this->getName(),
								'qci_timestamp' => $dbw->timestamp()
							],
							$fname
						);
					}
				);
			}
		} catch ( DBError $e ) {
			if ( !$ignoreErrors ) {
				throw $e; // report query error
			}
			$num = false; // set result to false to indicate error
		}

		return $num;
	}

	/**
	 * Remove a cached result.
	 * Useful for interactive backlogs where the user can fix problems in-place.
	 * @param LinkTarget $title The page to remove.
	 * @since 1.34
	 */
	public function delete( LinkTarget $title ) {
		//no-op, this is unsupported as it doesn't make that much sense for this special page
	}

	/**
	 * Fetch the query results from the query cache
	 * @param int|bool $limit Numerical limit or false for no limit
	 * @param int|bool $offset Numerical offset or false for no offset
	 * @return IResultWrapper
	 */
	public function fetchFromCache( $limit, $offset = false ) {
		$dbr = wfGetDB( DB_REPLICA );
		$options = [];

		if ( $limit !== false ) {
			$options['LIMIT'] = intval( $limit );
		}

		if ( $offset !== false ) {
			$options['OFFSET'] = intval( $offset );
		}

		$options['ORDER BY'] = $this->getOrderFields();

		return $dbr->select( 'ab_undesired',
			[
				'from' => 'abd_from',
				'namespace' => 'abd_namespace',
				'title' => 'abd_title',
				'through' => 'abd_through'
			],
			[],
			__METHOD__,
			$options
		);
	}

	/**
	 * Pre-fill the link cache
	 *
	 * @param IDatabase $db
	 * @param IResultWrapper $res
	 */
	function preprocessResults( $db, $res ) {
		if ( !$res->numRows() ) {
			return;
		}

		$batch = new LinkBatch;
		foreach ( $res as $row ) {
			$batch->add( $ns ?? $row->namespace, $row->title );
			$batch->addObj( Title::newFromID( $row->from ) );
			$batch->addObj( Title::newFromID( $row->through ) );
		}
		$batch->execute();

		$res->seek( 0 );
	}

	/**
	 * @param OutputPage $out
	 * @param Skin $skin
	 * @param IDatabase $dbr
	 * @param IResultWrapper $res
	 * @param int $num
	 * @param int $offset
	 */
	protected function outputResults( $out, $skin, $dbr, $res, $num, $offset ) {
		if ( $num > 0 ) {
			$html = Xml::openElement( 'ul' );
			$linkRenderer = $this->getLinkRenderer();
			$prevFrom = 0;
			$prevThrough = 0;

			// $res might contain the whole 1,000 rows, so we read up to $num
			for ( $i = 0; $i < $num && $row = $res->fetchObject(); $i++ ) {
				$throughTitle = Title::newFromID( $row->through );
				if ( $row->through && !$throughTitle ) {
					// outdated through entry
					continue;
				}

				$fromTitle = Title::newFromID( $row->from );
				if ( !$fromTitle ) {
					// outdated from entry
					continue;
				}

				if ( $prevFrom != $row->from ) {
					if ( $prevFrom > 0 ) {
						$html .= Xml::closeElement( 'ul' );
						$html .= Xml::closeElement( 'li' );

						if ( $prevThrough > 0 ) {
							$html .= Xml::closeElement( 'ul' );
							$html .= Xml::closeElement( 'li' );
						}
					}

					$prevFrom = $row->from;
					$html .= Xml::openElement( 'li' );
					$html .= $linkRenderer->makeKnownLink( $fromTitle );
					$html .= Xml::openElement( 'ul' );
					$prevThrough = 0;
				}

				if ( $prevThrough != $row->through ) {
					if ( $prevThrough > 0 ) {
						$html .= Xml::closeElement( 'ul' );
						$html .= Xml::closeElement( 'li' );
					}

					$prevThrough = $row->through;
					$html .= Xml::openElement( 'li' );
					$html .= $linkRenderer->makeKnownLink( $throughTitle );
					$html .= ' (' . $this->msg( 'advancedBacklinks-through-transclusion' )->escaped() . ')';
					$html .= Xml::openElement( 'ul' );
				}

				$html .= Xml::tags(
					'li',
					null,
					$linkRenderer->makeLink( Title::newFromText( $row->title, $row->namespace ) )
				);
			}

			if ( $prevThrough > 0 ) {
				$html .= Xml::closeElement( 'ul' );
				$html .= Xml::closeElement( 'li' );
			}

			$html .= Xml::closeElement( 'ul' );
			$html .= Xml::closeElement( 'li' );
			$html .= Xml::closeElement( 'ul' );
			$out->addHTML( $html );
		}
	}

	/**
	 * Make a link to "what links here" for the specified title
	 *
	 * @param Title $title Title being queried
	 * @param string $caption Text to display on the link
	 * @return string
	 * @throws MWException
	 */
	function makeLink( $title, $caption ) {
		$wlh = SpecialPage::getTitleFor( 'UndesiredRedlinks', $title->getPrefixedDBkey() );

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
	 */
	function formatResult( $skin, $result ) {
		return '';
	}

	/**
	 * @return string
	 */
	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}
