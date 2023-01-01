<?php
/**
 * Implements Special:WikitextLonelyPages
 *
 * Directly derived from the SpecialLonelypages class in MediaWiki core.
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
 * @ingroup SpecialPage
 */

use MediaWiki\MediaWikiServices;

/**
 * A special page looking for articles with no article linking to them,
 * thus being lonely.
 *
 * @ingroup SpecialPage
 */
class SpecialWikitextContentLonelyPages extends PageQueryPage {
	function __construct( $name = 'WikitextContentLonelyPages' ) {
		parent::__construct( $name );
		$this->addHelpLink( 'Extension:AdvancedBacklinks' );
	}

	function getPageHeader() {
		return $this->msg( 'advancedBacklinks-contentlonelypagestext' )->parseAsBlock();
	}

	function sortDescending() {
		return false;
	}

	function isExpensive() {
		return true;
	}

	function isSyndicated() {
		return false;
	}

	function getQueryInfo() {
		$tables = [
			'page',
			'ab_links',
			'templatelinks',
			'pp_tl_from' => 'page_props',
		];
		$conds = [
			'abl_namespace IS NULL',
			'page_namespace' => MediaWikiServices::getInstance()->getNamespaceInfo()->getContentNamespaces(),
			'page_is_redirect' => 0,
			'(tl_namespace IS NULL OR pp_tl_from.pp_page IS NOT NULL)',
		];
		$joinConds = [
			'ab_links' => [
				'LEFT JOIN', [
					'abl_namespace = page_namespace',
					'abl_title = page_title',
					'abl_through = 0',
					'abl_from_namespace' => MediaWikiServices::getInstance()->getNamespaceInfo()->getContentNamespaces(),
				]
			],
			'templatelinks' => [
				'LEFT JOIN', [
					'tl_namespace = page_namespace',
					'tl_title = page_title',
					'tl_from_namespace' => MediaWikiServices::getInstance()->getNamespaceInfo()->getContentNamespaces(),
				]
			],
			'pp_tl_from' => [
				'LEFT JOIN',
				[
					'pp_tl_from.pp_page = tl_from',
					'pp_tl_from.pp_propname' => 'transclusionisnotadoption'
				]
			],
		];

		// Allow extensions to modify the query
		// This is most definitely not the same query as in core, but still should be compatible with most use cases.
		// As of writing (1st of September, 2019), there are three extensions using this hook and all of them look
		// compatible with this.
		$hooks = MediaWikiServices::getInstance()->getHookContainer();
		$hooks->run( 'LonelyPagesQuery', [ &$tables, &$conds, &$joinConds ] );

		return [
			'tables' => $tables,
			'fields' => [
				'namespace' => 'page_namespace',
				'title' => 'page_title',
				'value' => 'page_title'
			],
			'conds' => $conds,
			'join_conds' => $joinConds
		];
	}

	function getOrderFields() {
		// For some crazy reason ordering by a constant
		// causes a filesort in MySQL 5

		if ( count( MediaWikiServices::getInstance()->getNamespaceInfo()->getContentNamespaces() ) > 1
		) {
			return [ 'page_namespace', 'page_title' ];
		} else {
			return [ 'page_title' ];
		}
	}

	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}