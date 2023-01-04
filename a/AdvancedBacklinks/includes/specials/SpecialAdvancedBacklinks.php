<?php
/**
 * Implements Special:AdvancedBacklinks
 *
 * Directly derived from the SpecialWhatLinksHere class in MediaWiki core.
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
 * @todo Use some variant of Pager or something; the pagination here is lousy.
 */

use MediaWiki\Content\IContentHandlerFactory;
use MediaWiki\MediaWikiServices;
use OOUI\CheckboxInputWidget;
use OOUI\FieldLayout;
use OOUI\FieldsetLayout;
use Wikimedia\Rdbms\IDatabase;

/**
 * Implements Special:Whatlinkshere
 *
 * @ingroup SpecialPage
 */
class SpecialAdvancedBacklinks extends IncludableSpecialPage {
	protected $selfTitle;

	/** @var bool */
	protected $filterDefault = true;

	/** @var Title */
	protected $target;

	protected $limits = [ 20, 50, 100, 250, 500 ];

	/** @var IContentHandlerFactory */
	private $contentHandlerFactory;

	public function __construct() {
		parent::__construct( 'AdvancedBacklinks' );

		// TODO: replace with DI
		$services = MediaWikiServices::getInstance();
		$this->contentHandlerFactory = $services->getContentHandlerFactory();
	}

	/**
	 * @param string|null $subPage
	 *
	 * @throws MWException
	 * @throws \OOUI\Exception
	 */
	function execute( $subPage ) {
		$out = $this->getOutput();

		$this->setHeaders();
		$this->outputHeader();
		$this->addHelpLink( 'Extension:AdvancedBacklinks' );
		$out->enableOOUI();

		$req = $this->getRequest();

		$limit = $req->getInt( 'limit', 50 );
		if ( $limit < 0 ) {
			$limit = 0;
		} elseif ( $limit > 5000 ) {
			$limit = 5000;
		}
		$req->setVal( 'limit', $limit );

		// Give precedence to subpage syntax
		if ( $subPage !== null ) {
			$req->setVal( 'target',
				$subPage );
		}

		$this->target = Title::newFromText( $req->getText( 'target' ) );
		if ( !$this->target ) {
			if ( !$this->including() ) {
				$this->advancedBacklinksForm();
			}

			return;
		}

		$this->filterDefault = !$req->getCheck( 's' );

		$this->getSkin()->setRelevantTitle( $this->target );

		$this->selfTitle = $this->getPageTitle( $this->target->getPrefixedDBkey() );

		$out->setPageTitle( $this->msg( 'whatlinkshere-title', $this->target->getPrefixedText() ) );
		$out->addBacklinkSubtitle( $this->target );
		$this->showIndirectLinks(
			0,
			$this->target,
			$req->getInt( 'limit', $this->getConfig()->get( 'QueryPageDefaultLimit' ) ),
			$req->getInt( 'from' ),
			$req->getInt( 'back' ),
			$req->getInt( 'fromt' ),
			$req->getInt( 'backt' )
		);
	}

	/**
	 * @param int $level Recursion level
	 * @param Title $target Target title
	 * @param int $limit Number of entries to display
	 * @param int $from Display from this article ID (default: 0)
	 * @param int $back Display from this article ID at backwards scrolling (default: 0)
	 * @param int $fromt
	 * @param int $backt
	 * @throws MWException
	 * @throws \OOUI\Exception
	 */
	function showIndirectLinks( $level, $target, $limit, $from = 0, $back = 0, $fromt = 0, $backt = 0 ) {
		$out = $this->getOutput();
		$dbr = wfGetDB( DB_REPLICA );
		// if Extension:Disambiguator has not been installed disable the disambig feature completely to save on joins
		$lookForDisambigs = ExtensionRegistry::getInstance()->isLoaded( 'Disambiguator' );

		$req = $this->getRequest();
		$showlinks = $req->getCheck( 'showlinks' ) || $this->filterDefault;
		$showredirs = $req->getCheck( 'showredirs' ) || $this->filterDefault;
		$showtrans = $req->getCheck( 'showtrans' ) || $this->filterDefault;
		$showdisambigs = $req->getCheck( 'showdisambigs' ) || $this->filterDefault;
		$showimages = $target->getNamespace() != NS_FILE || $req->getCheck( 'showimages' ) || $this->filterDefault;
		$wikitextOnly = $req->getCheck( 'wikitextonly' );

		$fetchlinks = ( $showlinks || $showredirs );

		// Build query conds in concert for all three tables...
		$conds['ab_links'] = [
			'abl_namespace' => $target->getNamespace(),
			'abl_title' => $target->getDBkey(),
		];
		$conds['templatelinks'] = [
			'tl_namespace' => $target->getNamespace(),
			'tl_title' => $target->getDBkey(),
		];
		$conds['ab_images'] = [
			'abi_title' => $target->getDBkey(),
		];

		if ( $wikitextOnly ) {
			$conds['ab_links']['abl_through'] = 0;
			$conds['ab_images']['abi_through'] = 0;
		}

		$namespace = $req->getVal( 'namespace' );
		if ( is_numeric( $namespace ) ) {
			$invert = $req->getCheck( 'invert' );
			$nsComparison = ( $invert ? '!= ' : '= ' ) . $dbr->addQuotes( $namespace );
			$conds['ab_links'][] = "abl_from_namespace $nsComparison";
			$conds['templatelinks'][] = "tl_from_namespace $nsComparison";
			$conds['ab_images'][] = "abi_from_namespace $nsComparison";
		}

		if ( $from || $fromt ) {
			if ( $fromt ) {
				$conds['templatelinks'][] = "tl_from < 0";
			} else {
				$conds['templatelinks'][] = "tl_from >= $from";
			}
			$conds['ab_links'][] = "(abl_from >= $from AND abl_through = $fromt) OR abl_through > $fromt";
			$conds['ab_images'][] = "(abi_from >= $from AND abi_through = $fromt) OR abi_through > $fromt";
		}

		if ( !$showredirs ) {
			$conds['ab_links']['rd_from'] = null;
		} elseif ( !$showlinks ) {
			$conds['ab_links'][] = 'rd_from is NOT NULL';
		}

		if ( $lookForDisambigs && !$showdisambigs ) {
			$conds['ab_links']['pp_value'] = null;
			$conds['ab_images']['pp_value'] = null;
			$conds['templatelinks']['pp_value'] = null;
		}

		$queryFunc = function ( IDatabase $dbr, $table, $fromCol, $throughCol = 0 ) use (
			$conds, $target, $limit, $lookForDisambigs
		) {
			// Read an extra row as an at-end check
			$queryLimit = $limit + 1;

			$tables = [ $table, 'redirect', 'page' ];
			$cols = [ $fromCol, 'rd_from', "$throughCol as through" ];
			$on = [
				"rd_from = $fromCol",
				'rd_title' => $target->getDBkey(),
				'rd_interwiki = ' . $dbr->addQuotes( '' ) . ' OR rd_interwiki IS NULL',
				'rd_namespace' => $target->getNamespace()
			];
			$joins = [
				'page' => [ 'JOIN', "$fromCol = page_id" ],
				'redirect' => [ 'LEFT JOIN', $on ]
			];
			$orderby = [];
			if ( $throughCol ) {
				$orderby[] = $throughCol;
			}
			$orderby[] = $fromCol;

			if ( $lookForDisambigs ) {
				$tables[] = 'page_props';
				$cols[] = 'pp_value as disambig';
				$joins['page_props'] = [
					'LEFT JOIN',
					[
						"pp_page = $fromCol",
						'pp_propname' => 'disambiguation'
					]
				];
			} else {
				$cols[] = 'NULL as disambig';
			}

			// Inner LIMIT is 2X in case of stale backlinks with wrong namespaces
			$subQuery = $dbr->buildSelectSubquery(
				$tables,
				$cols,
				$conds[$table],
				__CLASS__ . '::showIndirectLinks',
				// Force JOIN order per T106682 to avoid large filesorts
				[ 'ORDER BY' => $orderby, 'LIMIT' => 2 * $queryLimit, 'STRAIGHT_JOIN' ],
				$joins
			);

			return $dbr->select(
				[ 'page', 'temp_backlink_range' => $subQuery ],
				[ 'page_id', 'page_namespace', 'page_title', 'rd_from', 'page_is_redirect', 'through', 'disambig' ],
				[],
				__CLASS__ . '::showIndirectLinks',
				[ 'ORDER BY' => 'through, page_id', 'LIMIT' => $queryLimit ],
				[ 'page' => [ 'JOIN', "$fromCol = page_id" ] ]
			);
		};

		if ( $fetchlinks ) {
			$plRes = $queryFunc( $dbr, 'ab_links', 'abl_from', 'abl_through' );
		}

		if ( $showtrans ) {
			$tlRes = $queryFunc( $dbr, 'templatelinks', 'tl_from' );
		}

		if ( $showimages ) {
			$ilRes = $queryFunc( $dbr, 'ab_images', 'abi_from', 'abi_through' );
		}

		if ( ( !$fetchlinks || !$plRes->numRows() )
			&& ( !$showtrans || !$tlRes->numRows() )
			&& ( !$showimages || !$ilRes->numRows() )
		) {
			if ( $level == 0 && !$this->including() ) {
				$this->advancedBacklinksForm();

				if ( $wikitextOnly ) {
					$msgKey = is_numeric( $namespace ) ? 'advancedBacklinks-nowikitextlinkshere-ns' : 'advancedBacklinks-nowikitextlinkshere';
				} else {
					$msgKey = is_numeric( $namespace ) ? 'nolinkshere-ns' : 'nolinkshere';
				}

				$link = $this->getLinkRenderer()->makeLink(
					$this->target,
					null,
					[],
					$this->target->isRedirect() ? [ 'redirect' => 'no' ] : []
				);

				$errMsg = $this->msg( $msgKey )
					->params( $this->target->getPrefixedText() )
					->rawParams( $link )
					->parseAsBlock();
				$out->addHTML( $errMsg );
				$out->setStatusCode( 404 );
			}

			return;
		}

		// Read the rows into an array and remove duplicates
		// templatelinks comes second so that the templatelinks row overwrites the
		// pagelinks row, so we get (inclusion) rather than nothing
		$groups = [];

		if ( $fetchlinks ) {
			foreach ( $plRes as $row ) {
				$row->is_template = 0;
				$row->is_image = 0;
				$row->is_through = 0;
				$groups[$row->through][$row->page_id] = $row;
			}
		}
		if ( $showtrans ) {
			foreach ( $tlRes as $row ) {
				$row->is_template = 1;
				$row->is_image = 0;
				$row->is_through = 0;
				$groups[0][$row->page_id] = $row;
			}
		}
		if ( $showimages ) {
			foreach ( $ilRes as $row ) {
				$row->is_template = 0;
				$row->is_image = 1;
				$row->is_through = 0;
				$groups[$row->through][$row->page_id] = $row;
			}
		}

		ksort( $groups );
		$numRows = 0;
		$nextId = false;
		$limitIndex = -1;
		$deadMark = -1;

		// Sort by key and then change the keys to 0-based indices
		foreach ( $groups as $through => $rows ) {
			ksort( $rows );
			$numLast = $numRows;
			$numRows += count( $rows );

			if ( $limitIndex > -1 ) {
				if ( $limitIndex >= sizeof( $rows ) ) {
					$limitIndex -= sizeof( $rows );
				}
			} else if ( $numRows > $limit ) {
				// More rows available after these ones
				$limitIndex = $limit - $numLast;
			}

			if ( $limitIndex > -1 && $limitIndex < sizeof( $rows ) ) {
				// Get the ID from the last row in the result set
				$nextId = array_slice( $rows, $limitIndex, 1 )[0]->page_id;
				// Remove undisplayed rows
				$rows = array_slice( $rows, 0, $limitIndex );
				$groups[$through] = $rows;
				$deadMark = $through;
				break;
			} else {
				$groups[$through] = array_values( $rows );
			}
		}

		$prevId = $from;

		// use LinkBatch to make sure, that all required data (associated with Titles)
		// is loaded in one query
		$lb = new LinkBatch();
		foreach ( $groups as $through => $rows ) {
			if ( $deadMark > -1 && $through > $deadMark ) {
				break;
			}

			foreach ( $rows as $row ) {
				$lb->add( $row->page_namespace, $row->page_title );
			}
		}
		$lb->execute();

		if ( $level == 0 && !$this->including() ) {
			$this->advancedBacklinksForm();

			$link = $this->getLinkRenderer()->makeLink(
				$this->target,
				null,
				[],
				$this->target->isRedirect() ? [ 'redirect' => 'no' ] : []
			);

			$msg = $this->msg( $wikitextOnly ? 'advancedBacklinks-linksheredirectly' : 'linkshere' )
				->params( $this->target->getPrefixedText() )
				->rawParams( $link )
				->parseAsBlock();
			$out->addHTML( $msg );
			$prevnext = $this->getPrevNext( $prevId, $nextId, array_key_first( $groups ), $deadMark );
			$out->addHTML( $prevnext );
		}
		$out->addHTML( $this->listStart( $level ) );

		foreach ( $groups as $through => $rows ) {
			if ( $deadMark > -1 && $through > $deadMark ) {
				break;
			}

			if ( $through != 0 ) {
				$thTitle = Title::newFromID( $through );
				if ( !$thTitle ) {
					$out->addHTML(
						Xml::tags(
							'li',
							[ 'style' => 'color: red; font-weight: bold' ],
							$this->msg( 'advancedBacklinks-invalid-template' )->escaped()
						)
					);
				} else {
					//make a fake DB row and display it
					$row = new stdClass();
					$row->is_through = 1;
					$row->rd_from = 0;
					$row->is_template = 0;
					$row->is_image = 0;
					$row->page_is_redirect = 0;
					$row->disambig = null;
					$out->addHTML( $this->listItem( $row, $thTitle, $target ) );
				}
				$out->addHTML( $this->listStart( $level + 1 ) );
			}

			foreach ( $rows as $row ) {
				$nt = Title::makeTitle( $row->page_namespace, $row->page_title );

				if ( $row->rd_from && $level < 2 ) {
					$out->addHTML( $this->listItem( $row, $nt, $target, true ) );
					$this->showIndirectLinks(
						$level + 1,
						$nt,
						$this->getConfig()->get( 'MaxRedirectLinksRetrieved' )
					);
					$out->addHTML( Xml::closeElement( 'li' ) );
				} else {
					$out->addHTML( $this->listItem( $row, $nt, $target ) );
				}
			}

			if ( $through != 0 ) {
				$out->addHTML( $this->listEnd() );
			}
		}

		$out->addHTML( $this->listEnd() );

		if ( $level == 0 && !$this->including() ) {
			$out->addHTML( $prevnext );
		}
	}

	/**
	 * @param $level
	 *
	 * @return string
	 */
	protected function listStart( $level ) {
		return Xml::openElement( 'ul', ( $level ? [] : [ 'id' => 'mw-whatlinkshere-list' ] ) );
	}

	/**
	 * @param $row
	 * @param $nt
	 * @param $target
	 * @param bool $notClose
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function listItem( $row, $nt, $target, $notClose = false ) {
		$dirmark = $this->getLanguage()->getDirMark();

		# local message cache
		static $msgcache = null;
		if ( $msgcache === null ) {
			static $msgs = [ 'isredirect', 'istemplate', 'semicolon-separator',
				'whatlinkshere-links', 'isimage', 'editlink', 'advancedBacklinks-isdirect',
				'advancedBacklinks-through-transclusion', 'advancedBacklinks-disambig' ];
			$msgcache = [];
			foreach ( $msgs as $msg ) {
				$msgcache[$msg] = $this->msg( $msg )->escaped();
			}
		}

		if ( $row->rd_from ) {
			$query = [ 'redirect' => 'no' ];
		} else {
			$query = [];
		}

		$link = $this->getLinkRenderer()->makeKnownLink(
			$nt,
			null,
			$row->page_is_redirect ? [ 'class' => 'mw-redirect' ] : [],
			$query
		);

		// Display properties (redirect or template)
		$propsText = '';
		$props = [];
		if ( $row->rd_from ) {
			$props[] = $msgcache['isredirect'];
		}
		if ( $row->is_template ) {
			$props[] = $msgcache['istemplate'];
		}
		if ( $row->is_image ) {
			$props[] = $msgcache['isimage'];
		}
		if ( $row->is_through ) {
			$props[] = $msgcache['advancedBacklinks-through-transclusion'];
		}
		if ( $row->disambig !== null ) {
			$props[] = $msgcache['advancedBacklinks-disambig'];
		}

		$hooks = MediaWikiServices::getInstance()->getHookContainer();
		$hooks->run( 'WhatLinksHereProps', [ $row, $nt, $target, &$props ] );

		if ( count( $props ) ) {
			$propsText = $this->msg( 'parentheses' )
				->rawParams( implode( $msgcache['semicolon-separator'], $props ) )->escaped();
		}

		# Space for utilities links, with a what-links-here link provided
		$wlhLink = $this->wlhLink( $nt, $msgcache['whatlinkshere-links'], $msgcache['editlink'] );
		$wlh = Xml::wrapClass(
			$this->msg( 'parentheses' )->rawParams( $wlhLink )->escaped(),
			'mw-whatlinkshere-tools'
		);

		return $notClose ?
			Xml::openElement( 'li' ) . "$link $propsText $dirmark $wlh\n" :
			Xml::tags( 'li', null, "$link $propsText $dirmark $wlh" ) . "\n";
	}

	/**
	 * @return string
	 */
	protected function listEnd() {
		return Xml::closeElement( 'ul' );
	}

	/**
	 * @param Title $target
	 * @param $text
	 * @param $editText
	 *
	 * @return mixed
	 * @throws MWException
	 * @throws MWUnknownContentModelException
	 */
	protected function wlhLink( Title $target, $text, $editText ) {
		static $title = null;
		if ( $title === null ) {
			$title = $this->getPageTitle();
		}

		$linkRenderer = $this->getLinkRenderer();

		if ( $text !== null ) {
			$text = new HtmlArmor( $text );
		}

		// always show a "<- Links" link
		$links = [
			'links' => $linkRenderer->makeKnownLink(
				$title,
				$text,
				[],
				[ 'target' => $target->getPrefixedText() ]
			),
		];

		// if the page is editable, add an edit link
		if (
			// check user permissions
			$this->getAuthority()->isAllowed( 'edit' ) &&
			// check, if the content model is editable through action=edit
			$this->contentHandlerFactory->getContentHandler( $target->getContentModel() )
				->supportsDirectEditing()
		) {
			if ( $editText !== null ) {
				$editText = new HtmlArmor( $editText );
			}

			$links['edit'] = $linkRenderer->makeKnownLink(
				$target,
				$editText,
				[],
				[ 'action' => 'edit' ]
			);
		}

		// build the links html
		return $this->getLanguage()->pipeList( $links );
	}

	function makeSelfLink( $text, $query ) {
		if ( $text !== null ) {
			$text = new HtmlArmor( $text );
		}

		return $this->getLinkRenderer()->makeKnownLink(
			$this->selfTitle,
			$text,
			[],
			$query
		);
	}

	function getPrevNext( $prevId, $nextId, $prevT, $nextT ) {
		$req = $this->getRequest();
		$currentLimit = $req->getInt( 'limit', 50 );
		$prev = $this->msg( 'whatlinkshere-prev' )->numParams( $currentLimit )->escaped();
		$next = $this->msg( 'whatlinkshere-next' )->numParams( $currentLimit )->escaped();

		$vals = $req->getValues();
		unset( $vals['target'] ); // Already in the request title
		unset( $vals['title'] );

		if ( $prevId != 0 || $prevT != 0 ) {
			$overrides = [
				'from' => $req->getInt( 'back',  0 ),
				'fromt' => $req->getInt( 'backt', 0 )
			];
			$prev = $this->makeSelfLink( $prev, array_merge( $vals, $overrides ) );
		}
		if ( $nextId != 0 ) {
			$overrides = [
				'from' => $nextId,
				'back' => $prevId,
				'fromt' => $nextT,
				'backt' => $prevT
			];
			$next = $this->makeSelfLink( $next, array_merge( $vals, $overrides ) );
		}

		$limitLinks = [];
		$lang = $this->getLanguage();
		foreach ( $this->limits as $limit ) {
			$prettyLimit = htmlspecialchars( $lang->formatNum( $limit ) );
			$overrides = [ 'limit' => $limit ];
			$limitLinks[] = $this->makeSelfLink( $prettyLimit, array_merge( $vals, $overrides ) );
		}

		$nums = $lang->pipeList( $limitLinks );

		return $this->msg( 'viewprevnext' )->rawParams( $prev, $next, $nums )->escaped();
	}

	/**
	 * @throws \OOUI\Exception
	 */
	function advancedBacklinksForm(): void {
		$req = $this->getRequest();

		$target = $this->target ? $this->target->getPrefixedText() : '';
		$namespace = $req->getVal( 'namespace' );
		$nsinvert = $req->getCheck( 'invert' );
		$wikitextonly = $req->getCheck( 'wikitextonly' );
		$req->setVal( 'title', $this->getPageTitle()->getPrefixedText() );

		$consumedParams = [
			'namespace', 'target', 'invert', 'wikitextonly',
			'back', 'from', 'backt', 'fromt',
			'showtrans', 'showlinks', 'showredirs', 'showimages', 'showdisambigs'
		];
		$hiddenFields = [
			new OOUI\HiddenInputWidget( [
				'name' => 's',
				'value' => ''
			] )
		];
		foreach ( $req->getValues() as $key => $value ) {
			if ( in_array( $key, $consumedParams ) ) {
				continue;
			}

			$hiddenFields[] = new OOUI\HiddenInputWidget( [
				'name' => $key,
				'value' => $value
			] );
		}

		$pageInput = new FieldLayout(
			new MediaWiki\Widget\TitleInputWidget( [
				'name' => 'target',
				'id' => 'mw-whatlinkshere-target',
				'value' => $target,
				'infusable' => true
			] ),
			[
				'label' => $this->msg( 'whatlinkshere-page' )->text(),
				'align' => 'top'
			]
		);

		$nsInput = new FieldLayout(
			new OOUI\Widget( [
				'content' => new OOUI\HorizontalLayout( [
					'items' => array_merge( [
						new FieldLayout(
							new MediaWiki\Widget\NamespaceInputWidget( [
								'value' => $namespace,
								'name' => 'namespace',
								'id' => 'namespace',
								'infusable' => true,
								'includeAllValue' => 'all'
							] ),
							[
								'classes' => [ 'ab-namespace-field' ]
							]
						),
						new FieldLayout(
							new CheckboxInputWidget( [
								'name' => 'invert',
								'id' => 'nsinvert',
								'selected' => $nsinvert
							] ),
							[
								'align' => 'inline',
								'label' => $this->msg( 'invert' )->text(),
								'title' => $this->msg( 'tooltip-whatlinkshere-invert' )->text()
							]
						)
					],
						$hiddenFields
					)
				] )
			] ),
			[
				'label' => $this->msg( 'namespace' )->text(),
				'align' => 'top'
			]
		);

		$wOnlyField = new OOUI\FieldLayout(
			new OOUI\CheckboxInputWidget( [
				'selected' => $wikitextonly,
				'name' => 'wikitextonly'
			] ),
			[
				'label' => $this->msg( 'advancedBacklinks-wikitext-only' )->text(),
				'title' => $this->msg( 'advancedBacklinks-wikitext-only-tooltip' )->text(),
				'align' => 'inline'
			]
		);

		$submitButton = new OOUI\FieldLayout(
			new OOUI\ButtonInputWidget( [
				'type' => 'submit',
				'label' => $this->msg( 'whatlinkshere-submit' )->text(),
				'flags' => [
					'primary',
					'progressive'
				]
			] )
		);

		$f = new FieldsetLayout( [
			'items' => [
				$pageInput,
				$nsInput,
				$wOnlyField,
				$this->getFilterPanel( $this->filterDefault ),
				$submitButton
			]
		] );

		$f = new OOUI\FormLayout( [
			'action' => wfScript(),
			'method' => 'get',
			'items' => [ $f ]
		] );

		$f = new OOUI\PanelLayout( [
			'padded' => true,
			'framed' => true,
			'expanded' => false,
			'classes' => [ 'mw-htmlform-ooui-wrapper' ],
			'content' => $f
		] );

		$out = $this->getOutput();
		$out->addHTML( $f );
		$out->addModules( 'ext.ab.specialAb' );
	}

	/**
	 * Create filter panel
	 *
	 * @param bool $default
	 * @return FieldLayout HTML fieldset and filter panel with the show/hide links
	 * @throws \OOUI\Exception
	 */
	function getFilterPanel( bool $default ) : FieldLayout {
		$req = $this->getRequest();
		$items = [];
		$types = [ 'showtrans', 'showlinks', 'showredirs' ];

		if ( $this->target && $this->target->getNamespace() == NS_FILE ) {
			$types[] = 'showimages';
		} else {
			$items[] = new OOUI\HiddenInputWidget( [
				'name' => 'showimages',
				'value' => $req->getCheck( 'showimages' ) || $default
			] );
		}

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Disambiguator' ) ) {
			$types[] = 'showdisambigs';
		}

		$msg = $this->msg( 'show' )->escaped();
		foreach ( $types as $type ) {
			$invertedName = str_replace( 'show', 'hide', $type );
			$items[] = new FieldLayout(
				new CheckboxInputWidget( [
					'name' => $type,
					'selected' => $req->getCheck( $type ) || $default
				] ),
				[
					'align' => 'inline',
					'label' => $this->msg( "whatlinkshere-$invertedName", $msg )->escaped()
				]
			);
		}

		return new OOUI\FieldLayout(
			new OOUI\Widget( [
				'content' => [
					new OOUI\HorizontalLayout( [
						'items' => $items
					] )
				]
			] ),
			[
				'label' => $this->msg( 'whatlinkshere-filters' )->text(),
				'align' => 'top'
			]
		);

	}

	/**
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		return $this->prefixSearchString( $search, $limit, $offset );
	}

	protected function getGroupName() {
		return 'advancedBacklinks';
	}
}
