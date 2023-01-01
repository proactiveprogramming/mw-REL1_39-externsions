<?php
/**
 * API module to handle links table back-queries
 *
 * Copyright (C) 2019  Ostrzyciel
 * Copyright © 2014 Wikimedia Foundation and contributors
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
 */

use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\IntegerDef;

/**
 * This implements prop=ab_fileusage, prop=ab_linkshere
 */
class ApiQueryAdvancedBacklinksProp extends ApiQueryBase {

	// Data for the various modules implemented by this class
	private static $settings = [
		'ab_linkshere' => [
			'code' => 'ablh',
			'prefix' => 'abl',
			'linktable' => 'ab_links',
			'indexes' => [ 'abl_composite_to', 'abl_composite' ],
			'from_namespace' => true,
			'showredirects' => true,
		],
		'ab_fileusage' => [
			'code' => 'abfu',
			'prefix' => 'abi',
			'linktable' => 'ab_images',
			'indexes' => [ 'abi_composite_to', 'abi_composite' ],
			'from_namespace' => true,
			'to_namespace' => NS_FILE,
			'exampletitle' => 'File:Example.jpg',
			'showredirects' => true,
		],
	];

	public function __construct( ApiQuery $query, $moduleName ) {
		parent::__construct( $query, $moduleName, self::$settings[$moduleName]['code'] );
	}

	/**
	 * @throws ApiUsageException
	 */
	public function execute() {
		$settings = self::$settings[$this->getModuleName()];

		$db = $this->getDB();
		$params = $this->extractRequestParams();
		$prop = array_flip( $params['prop'] );
		$emptyString = $db->addQuotes( '' );

		$pageSet = $this->getPageSet();
		$pages = $pageSet->getGoodAndMissingPages();
		$map = $pageSet->getGoodAndMissingTitlesByNamespace();

		// Add in special pages, they can theoretically have backlinks too.
		// (although currently they only do for prop=redirects)
		foreach ( $pageSet->getSpecialPages() as $id => $page ) {
			$pages[] = $page;
			$map[$page->getNamespace()][$page->getDBkey()] = $id;
		}

		// Determine our fields to query on
		$p = $settings['prefix'];
		$hasNS = !isset( $settings['to_namespace'] );
		$bl_title = "{$p}_title";
		if ( $hasNS ) {
			$bl_namespace = "{$p}_namespace";

		} else {
			$bl_namespace = $settings['to_namespace'];

			$pages = array_filter( $pages, function ( $t ) use ( $bl_namespace ) {
				return $t->getNamespace() === $bl_namespace;
			} );
			$map = array_intersect_key( $map, [ $bl_namespace => true ] );
		}
		$bl_from = "{$p}_from";
		$bl_through = "{$p}_through";

		if ( !$pages ) {
			return; // nothing to do
		}
		if ( $params['namespace'] !== null && count( $params['namespace'] ) === 0 ) {
			return; // nothing to do
		}

		// Figure out what we're sorting by, and add associated WHERE clauses.
		// MySQL's query planner screws up if we include a field in ORDER BY
		// when it's constant in WHERE, so we have to test that for each field.
		$sortby = [];
		if ( $hasNS && count( $map ) > 1 ) {
			$sortby[$bl_namespace] = 'ns';
		}
		$theTitle = null;
		foreach ( $map as $nsTitles ) {
			reset( $nsTitles );
			$key = key( $nsTitles );
			if ( $theTitle === null ) {
				$theTitle = $key;
			}
			if ( count( $nsTitles ) > 1 || $key !== $theTitle ) {
				$sortby[$bl_title] = 'title';
				break;
			}
		}
		$miser_ns = null;
		if ( $params['namespace'] !== null ) {
			if ( empty( $settings['from_namespace'] ) ) {
				if ( $this->getConfig()->get( 'MiserMode' ) ) {
					$miser_ns = $params['namespace'];
				} else {
					$this->addWhereFld( 'page_namespace', $params['namespace'] );
				}
			} else {
				$this->addWhereFld( "{$p}_from_namespace", $params['namespace'] );
				if ( !empty( $settings['from_namespace'] )
					&& $params['namespace'] !== null && count( $params['namespace'] ) > 1
				) {
					$sortby["{$p}_from_namespace"] = 'int';
				}
			}
		}
		$sortby[$bl_from] = 'int';

		// Now use the $sortby to figure out the continuation
		if ( !is_null( $params['continue'] ) ) {
			$cont = explode( '|', $params['continue'] );
			$this->dieContinueUsageIf( count( $cont ) != count( $sortby ) );
			$where = '';
			$i = count( $sortby ) - 1;
			foreach ( array_reverse( $sortby, true ) as $field => $type ) {
				$v = $cont[$i];
				switch ( $type ) {
					case 'ns':
					case 'int':
						$v = (int)$v;
						$this->dieContinueUsageIf( $v != $cont[$i] );
						break;
					default:
						$v = $db->addQuotes( $v );
						break;
				}

				if ( $where === '' ) {
					$where = "$field >= $v";
				} else {
					$where = "$field > $v OR ($field = $v AND ($where))";
				}

				$i--;
			}
			$this->addWhere( $where );
		}

		// Populate the rest of the query
		$this->addTables( [ $settings['linktable'], 'page' ] );
		$this->addWhere( "$bl_from = page_id" );

		if ( $this->getModuleName() === 'redirects' ) {
			$this->addWhere( "rd_interwiki = $emptyString OR rd_interwiki IS NULL" );
		}

		$this->addFields( array_keys( $sortby ) );
		$this->addFields( [
			'bl_namespace' => $bl_namespace,
			'bl_title' => $bl_title
		] );

		$fld_pageid = isset( $prop['pageid'] );
		$fld_title = isset( $prop['title'] );
		$fld_redirect = isset( $prop['redirect'] );
		$fld_through = isset( $prop['through'] );

		$this->addFieldsIf( 'page_id', true );
		$this->addFieldsIf( [ 'page_title', 'page_namespace' ], $fld_title );
		$this->addFieldsIf( 'page_is_redirect', $fld_redirect );
		$this->addFieldsIf( [ 'bl_through' => $bl_through ], true );

		// prop=redirects
		$fld_fragment = isset( $prop['fragment'] );
		$this->addFieldsIf( 'rd_fragment', $fld_fragment );

		$this->addFieldsIf( 'page_namespace', $miser_ns !== null );

		if ( $hasNS ) {
			// Can't use LinkBatch because it throws away Special titles.
			// And we already have the needed data structure anyway.
			$this->addWhere( $db->makeWhereFrom2d( $map, $bl_namespace, $bl_title ) );
		} else {
			$where = [];
			foreach ( $pages as $t ) {
				if ( $t->getNamespace() == $bl_namespace ) {
					$where[] = "$bl_title = " . $db->addQuotes( $t->getDBkey() );
				}
			}
			$this->addWhere( $db->makeList( $where, LIST_OR ) );
		}

		if ( $params['show'] !== null ) {
			// prop=redirects only
			$show = array_flip( $params['show'] );
			if ( isset( $show['fragment'] ) && isset( $show['!fragment'] ) ||
				isset( $show['redirect'] ) && isset( $show['!redirect'] ) ||
				isset( $show['direct'] ) && isset( $show['!direct'] )
			) {
				$this->dieWithError( 'apierror-show' );
			}
			$this->addWhereIf( "rd_fragment != $emptyString", isset( $show['fragment'] ) );
			$this->addWhereIf(
				"rd_fragment = $emptyString OR rd_fragment IS NULL",
				isset( $show['!fragment'] )
			);
			$this->addWhereIf( [ 'page_is_redirect' => 1 ], isset( $show['redirect'] ) );
			$this->addWhereIf( [ 'page_is_redirect' => 0 ], isset( $show['!redirect'] ) );
			$this->addWhereIf( "{$p}_through = 0", isset( $show['direct'] ) );
			$this->addWhereIf( "{$p}_through <> 0", isset( $show['!direct'] ) );
		}

		// Override any ORDER BY from above with what we calculated earlier.
		$this->addOption( 'ORDER BY', array_keys( $sortby ) );

		// MySQL's optimizer chokes if we have too many values in "$bl_title IN
		// (...)" and chooses the wrong index, so specify the correct index to
		// use for the query. See T139056 for details.
		if ( !empty( $settings['indexes'] ) ) {
			list( $idxNoFromNS, $idxWithFromNS ) = $settings['indexes'];
			if ( $params['namespace'] !== null && !empty( $settings['from_namespace'] ) ) {
				$this->addOption( 'USE INDEX', [ $settings['linktable'] => $idxWithFromNS ] );
			} else {
				$this->addOption( 'USE INDEX', [ $settings['linktable'] => $idxNoFromNS ] );
			}
		}

		// MySQL (or at least 5.5.5-10.0.23-MariaDB) chooses a really bad query
		// plan if it thinks there will be more matching rows in the linktable
		// than are in page. Use STRAIGHT_JOIN here to force it to use the
		// intended, fast plan. See T145079 for details.
		$this->addOption( 'STRAIGHT_JOIN' );

		$this->addOption( 'LIMIT', $params['limit'] + 1 );

		$res = $this->select( __METHOD__ );

		$result = $this->getResult();

		$count = 0;
		foreach ( $res as $row ) {
			if ( ++$count > $params['limit'] ) {
				// We've reached the one extra which shows that
				// there are additional pages to be had. Stop here...
				$this->setContinue( $row, $sortby );
				break;
			}

			if ( $miser_ns !== null && !in_array( $row->page_namespace, $miser_ns ) ) {
				// Miser mode namespace check
				continue;
			}

			$id = $map[$row->bl_namespace][$row->bl_title];
			$vals = [];

			if ( $fld_pageid ) {
				$vals['pageid'] = $row->page_id;
			}

			if ( $fld_title ) {
				$title = Title::newFromID( $row->page_id );
				if ( $title && $title->isValid() ) {
					$vals['title'] = $title->getPrefixedText();
					$vals['ns'] = $row->page_namespace;
				}
			}

			if ( $fld_through ) {
				$vals['through'] = $row->bl_through;
			}

			if ( $fld_redirect && $row->page_is_redirect ) {
				$vals['redirect'] = '';
			}

			$fit = $result->addValue( [ 'query', 'pages', $id, $this->getModuleName() ], null, $vals );
			if ( !$fit ) {
				$this->setContinue( $row, $sortby );
			}
		}
	}

	private function setContinue( $row, $sortby ) {
		$cont = [];
		foreach ( $sortby as $field => $v ) {
			$cont[] = $row->$field;
		}
		$this->setContinueEnumParameter( 'continue', implode( '|', $cont ) );
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	public function getAllowedParams() {
		$settings = self::$settings[$this->getModuleName()];

		$ret = [
			'prop' => [
				ParamValidator::PARAM_TYPE => [
					'pageid',
					'title',
					'through'
				],
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_DEFAULT => 'pageid|title|through',
				ApiBase::PARAM_HELP_MSG_PER_VALUE => [],
			],
			'namespace' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'namespace',
			],
			'show' => null, // Will be filled/removed below
			'limit' => [
				ParamValidator::PARAM_DEFAULT => 10,
				ParamValidator::PARAM_TYPE => 'limit',
				IntegerDef::PARAM_MIN => 1,
				IntegerDef::PARAM_MAX => ApiBase::LIMIT_BIG1,
				IntegerDef::PARAM_MAX2 => ApiBase::LIMIT_BIG2
			],
			'continue' => [
				ApiBase::PARAM_HELP_MSG => 'api-help-param-continue',
			],
		];

		if ( empty( $settings['from_namespace'] ) && $this->getConfig()->get( 'MiserMode' ) ) {
			$ret['namespace'][ApiBase::PARAM_HELP_MSG_APPEND] = [
				'api-help-param-limited-in-miser-mode',
			];
		}

		if ( !empty( $settings['showredirects'] ) ) {
			$ret['prop'][ParamValidator::PARAM_TYPE][] = 'redirect';
			$ret['prop'][ParamValidator::PARAM_DEFAULT] .= '|redirect';
		}
		if ( isset( $settings['props'] ) ) {
			$ret['prop'][ParamValidator::PARAM_TYPE] = array_merge(
				$ret['prop'][ParamValidator::PARAM_TYPE], $settings['props']
			);
		}

		$show = [ 'direct', '!direct' ];
		if ( !empty( $settings['showredirects'] ) ) {
			$show[] = 'redirect';
			$show[] = '!redirect';
		}
		if ( isset( $settings['show'] ) ) {
			$show = array_merge( $show, $settings['show'] );
		}
		if ( $show ) {
			$ret['show'] = [
				ParamValidator::PARAM_TYPE => $show,
				ParamValidator::PARAM_ISMULTI => true,
			];
		} else {
			unset( $ret['show'] );
		}

		return $ret;
	}

	protected function getExamplesMessages() {
		$settings = self::$settings[$this->getModuleName()];
		$name = $this->getModuleName();
		$path = $this->getModulePath();
		$title = $settings['exampletitle'] ?? 'Main Page';
		$etitle = rawurlencode( $title );

		return [
			"action=query&prop=$name&titles=$etitle"
			=> "apihelp-$path-example-simple",
			"action=query&generator=$name&titles=$etitle&prop=info"
			=> "apihelp-$path-example-generator",
		];
	}

	public function getHelpUrls() {
		return "https://www.mediawiki.org/wiki/Extension:AdvancedBacklinks";
	}
}
