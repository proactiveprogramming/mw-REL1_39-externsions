<?php
/**
* Multi-Category Search 1.69
* This MediaWiki extension represents a [[Special:MultiCategorySearch|special page]],
* 	that allows to find pages, included in several specified categories at once.
* File with extension main source code.
* Requires MediaWiki 1.8 or higher and MySQL 4.1 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:Multi-Category_Search
*
* Copyright (c) Moscow, 2008-2017, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

class MultiCategorySearch extends IncludableSpecialPage
{
	// Configuration settings

	/** Number of input boxes for categories to search. */
	var $inCategoriesNumber = 5;
	/** Number of input boxes for categories to exclude from search. */
	var $exCategoriesNumber = 3;
	/**
	* Drop-down lists activation. Change the following variable's value to true to activate drop-down lists.
	* If activated, user will be allowed to select predefined categories from drop-down lists.
	* Categories for drop-down lists must be defined in showDropdownLists() function in the end of this file.
	*/
	var $useDropdownLists = false;
	/**
	* EditTools insertion.
	* Change it to true to insert EditTools (http://www.mediawiki.org/wiki/MediaWiki:Edittools) at the bottom of the input form. It allows user to input special characters easily.
	* EditTools require CharInsert extension (http://www.mediawiki.org/wiki/Extension:CharInsert) to be installed.
	* EditTools also require AJAX to be enabled (global $wgUseAjax variable must be set to true in LocalSettings.php file).
	*/
	var $insertEditTools = false;
	/** Method of passing parameters. Change it to 'get', if you need that method for some reason. */
	var $paramsPassMethod = 'post';

	// End of configuration settings

	var $inCategories = array();
	var $exCategories = array();

	var $renderingContext = null;

	static private $memcached = NULL;
	static function getMCache() {
		global $wgMemCachedServers;
		if( self::$memcached == NULL ) {
			self::$memcached = new Memcache;
			self::$memcached->connect( $wgMemCachedServers[0] )
				or die ( "Multi-category search: Could not connect to memcached server." );
		}
		return self::$memcached;
	}

	function __construct( $name = 'MultiCategorySearch' ) {
		global $wgRequest;

		if( function_exists( 'wfLoadExtensionMessages' ) )
			wfLoadExtensionMessages( $name );
		parent::__construct( $name );
		list( $this->limit, $this->offset ) = $wgRequest->getLimitOffset( 100, 'searchlimit' );
	}

	function execute( $transclusionParams ) {
		global $wgRequest, $wgOut, $wgVersion;

		if( version_compare( $wgVersion, '1.8', '<' ) === true ) {
			$wgOut->showErrorPage( "Error: Upgrade required", "The Multi-Category Search " .
				"extension can't work on MediaWiki version older than 1.8. Please, upgrade." );
			return;
		}

		if( $this->including() && !empty( $transclusionParams ) ) {
			// $transclusionParams typically are like 'include=Cat1/include=Cat2/exclude=Cat3'
			$transclusionParams = preg_replace( '/\/(..)clude=/', '|\1clude=', $transclusionParams );
			$bits = explode( '|', trim( $transclusionParams ) );
			$in = 1;
			$ex = 1;
			foreach ( $bits as $bit ) {
				$bit = trim( $bit );
				$type = substr( $bit, 0, 8 );
				if ( $type == 'include=' ) {
					$this->inCategories[$in] = substr( $bit, 8 );
					$in++;
				} else if ( $type == 'exclude=' ) {
					$this->exCategories[$ex] = substr( $bit, 8 );
					$ex++;
				}
			}
		} else if( !$this->including() &&
			(!is_null( $wgRequest->getVal( 'wpSubmitSearchParams' ) ) ||
			stripos( $wgRequest->getRequestURL(), 'wpInCategory' ) !== false ||
			stripos( $wgRequest->getRequestURL(), 'wpExCategory' ) !== false )) {
				for( $i = 1; $i <= $this->inCategoriesNumber; $i++ ) {
					$cat = $wgRequest->getText( 'wpInCategory' . $i );
					if( $cat != '' && $cat != null )
						$this->inCategories[$i] = $cat;
				}
				for( $i = 1; $i <= $this->exCategoriesNumber; $i++ ) {
					$cat = $wgRequest->getText( 'wpExCategory' . $i );
					if( $cat != '' && $cat != null )
						$this->exCategories[$i] = $cat;
				}
		}

		$this->showResults();

		//if( empty( $transclusionParams ) )	// unusual headings damage (== ? ==) was detected
		if( !$this->including() )
			$this->showForm();
	}

	function showResults() {
		global $wgRequest, $wgOut, $wgLang, $wgTitle, $wgDBtype, $wgDBprefix;
		global $wgMultiCatSearchMemCachePrefix, $wgMultiCatSearchMemCacheTimeout;

		$inCategoriesCount = count( $this->inCategories );
		$exCategoriesCount = count( $this->exCategories );
		if( $inCategoriesCount == 0 && $exCategoriesCount == 0 ) {
			$wgOut->addHTML( '<h3>' .
				( function_exists( 'wfMsg' )
					? wfMsg( 'multicatsearch_no_params' )
					: wfMessage( 'multicatsearch_no_params' )->text() 
				) .
				'</h3><br /><hr /><br />' );
			return;
		}

		if( !isset( $wgMultiCatSearchMemCachePrefix ) ) {
			$wgMultiCatSearchMemCachePrefix = 'mcs:';
		}
		if( !isset( $wgMultiCatSearchMemCacheTimeout ) ) {
			$wgMultiCatSearchMemCacheTimeout = 900;
		}
		$memcache_loaded = extension_loaded( 'memcache' );
		// make sure that included saves are differentiated from non-included saves
		if( $this->including() ) {
			$wgMultiCatSearchMemCachePrefix .= 'including:';
		} else {
			$wgMultiCatSearchMemCachePrefix .= $this->limit . ':' . $this->offset . ':';
		}

		wfProfileIn( 'MultiCategorySearch::showResults' );

		$dbr = wfGetDB( DB_SLAVE );
/*
		if( $wgDBtype != 'mysql' ||
			version_compare( $dbr->getServerVersion(), '4.1', '<' ) === true ) {
				$wgOut->showErrorPage( 'Error: Upgrade Required', 'The Multi-Category Search ' .
					'extension requires MySQL database engine 4.1 or higher. Please, upgrade.' );
			return;
		}
*/

		if( !function_exists( 'prepareStrForDb' ) ) {
			function prepareStrForDb( &$catStr, $i, $dbr ) {
				$catStr = $dbr->addQuotes( str_replace( ' ', '_', ucfirst( $catStr ) ) );
			}
		}

		$inCategoriesStr = '';
		if( $inCategoriesCount > 0 ) {
			$categories = $this->inCategories;
			array_walk( $categories, 'prepareStrForDb', $dbr );
			$inCategoriesStr = implode( ',', $categories );
		}

		$exCategoriesStr = '';
		$exSqlQueryStr = '';
		if( $exCategoriesCount > 0 ) {
			$categories = $this->exCategories;
			array_walk( $categories, 'prepareStrForDb', $dbr );
			$exCategoriesStr = implode( ',', $categories );
			$exSqlQueryStr = "AND cl_from NOT IN " .
				"(SELECT cl_from " .
				"FROM {$wgDBprefix}categorylinks " .
				"WHERE cl_to IN({$exCategoriesStr}))";
		}

		$pageTableName = $dbr->tableName( 'page' );
		$catlinksTableName = $dbr->tableName( 'categorylinks' );

		if( $inCategoriesCount > 0 ) {
			$sqlQueryStr =
				"SELECT {$pageTableName}.page_namespace AS ns, " .
					"{$pageTableName}.page_title AS title " .
				"FROM {$pageTableName}, " .
					"(SELECT cl_from, COUNT(*) AS match_count " .
					"FROM {$catlinksTableName} " .
					"WHERE cl_to IN({$inCategoriesStr}) {$exSqlQueryStr} " .
					"GROUP BY cl_from) AS matches " .
				"WHERE matches.match_count = {$inCategoriesCount} " .
					"AND {$pageTableName}.page_id = matches.cl_from " .
				"ORDER BY {$pageTableName}.page_title";
		}
		else {
			$sqlQueryStr =
				"SELECT {$pageTableName}.page_namespace AS ns, " .
					"{$pageTableName}.page_title AS title " .
				"FROM {$pageTableName}, " .
					"(SELECT cl_from " .
					"FROM {$catlinksTableName} " .
					"WHERE cl_to IN({$exCategoriesStr}) " .
					"GROUP BY cl_from) AS matches " .
				"WHERE {$pageTableName}.page_id NOT IN(matches.cl_from) " .
					"AND {$pageTableName}.page_namespace <> 8 " .	// exclude MediaWiki namespace
				"ORDER BY {$pageTableName}.page_title";
		}

		// check the cache and query the database if necessary
		if( $memcache_loaded == 0 || !( $htresults =
			MultiCategorySearch::getMCache()->get( $wgMultiCatSearchMemCachePrefix . base64_encode(
			'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ) ) ) ) {
				$res = $dbr->query( $sqlQueryStr, 'MultiCategorySearch::showResults', false );
				$htresults = "";
				if( $dbr->numRows($res) == 0 ) {
					$htresults .=  '<h3>' .
						( function_exists( 'wfMsg' )
							? wfMsg( 'multicatsearch_no_result' )
							: wfMessage( 'multicatsearch_no_result' )->text() 
						) .
						'</h3><br /><hr /><br />' ;
					$wgOut->addHTML($htresults);
					if( $memcache_loaded == 1 ) {
						MultiCategorySearch::getMCache()->set(
							$wgMultiCatSearchMemCachePrefix . base64_encode(
								'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ),
							$htresults, false, $wgMultiCatSearchMemCacheTimeout);
					}
					return;
			}

			$queryStr = '';
			foreach( $wgRequest->getValues() as $requestKey => $requestVal ) {
				if( $requestVal != '' && $requestVal != null && 
					(stripos( $requestKey, 'wpInCategory' ) !== false ||
					stripos( $requestKey, 'wpExCategory' ) !== false) )
						$queryStr .= '&' . $requestKey . '=' . $requestVal;
			}

			if( $dbr->numRows($res) >= $this->limit && !$this->including() ) {
				$htresults .= '<p>' . wfShowingResults( $this->offset, $this->limit ) . "</p>\n";
				if( function_exists( 'wfViewPrevNext' ) ) {
					$jumpLinks = wfViewPrevNext(
						$this->offset, $this->limit, 'Special:MultiCategorySearch', $queryStr,
						($dbr->numRows($res) <= $this->offset + $this->limit) ? true : false );
				} else {
					$jumpLinks = $wgLang->viewPrevNext(
						$wgTitle, $this->offset, $this->limit, wfCgiToArray( $queryStr ),
						($dbr->numRows($res) <= $this->offset + $this->limit) ? true : false );
				}
				$htresults .= "<br />{$jumpLinks}<br />\n";
			}

			if( class_exists( 'RequestContext' ) ) {
				if( $this->renderingContext == null )
					$this->renderingContext = new RequestContext();		// for MediaWiki 1.19 and further
				$catView = new CategoryViewer(
					Title::makeTitle(
						'-1',
						( function_exists( 'wfMsg' )
							? wfMsg( 'multicategorysearch' )
							: wfMessage( 'multicategorysearch' )->text() 
						)
					),
					$this->renderingContext
				);
			}
			else
				$catView = new CategoryViewer(
					Title::makeTitle(
						'-1',
						( function_exists( 'wfMsg' )
							? wfMsg( 'multicategorysearch' )
							: wfMessage( 'multicategorysearch' )->text() 
						)
					)
				);

			$i = 0;
			$j = 0;
			while( $row = $dbr->fetchObject( $res ) ) {
				if( $i++ < $this->offset && !$this->including() )
					continue;
				if( $j++ == $this->limit && !$this->including() )
					break;
				$titleObj = Title::makeTitle( $row->ns, $row->title );
				if( $j == 1 )
					$startChar = $titleObj->getPrefixedText();
				$catView->AddPage( $titleObj, $titleObj->mTextform, 10000 );
			}
			$htresults .= $catView->formatList( $catView->articles, $catView->articles_start_char );

			if( $dbr->numRows( $res ) >= $this->limit && !$this->including() ) {
				$htresults .= "<br />{$jumpLinks}\n";
			}

			$dbr->freeResult( $res );

			if( $memcache_loaded == 1 ) {
				MultiCategorySearch::getMCache()->set(
					$wgMultiCatSearchMemCachePrefix . base64_encode(
						'in:' . $inCategoriesStr . ':ex:' . $exCategoriesStr ),
					$htresults, false, $wgMultiCatSearchMemCacheTimeout );
			}
		}

		$wgOut->addHTML( $htresults.'<br /><hr /><br />' );

		wfProfileOut( 'MultiCategorySearch::showResults' );
	}

	function showForm() {
		global $wgOut, $wgUser, $wgRequest;
		global $wgScriptPath, $wgUseAjax, $wgJsMimeType;

		$wgOut->setPagetitle(
			( function_exists( 'wfMsg' )
				? wfMsg( 'multicategorysearch' )
				: wfMessage( 'multicategorysearch' )->text() 
			)
		);
		$titleObj = Title::makeTitle( NS_SPECIAL, 'MultiCategorySearch' );
		$action = htmlspecialchars( $titleObj->getLocalUrl() );

		if( function_exists( 'wfMsgHtml' ) ) {    // function calls htmlspecialchars() for message
			$msgComment = wfMsgHtml( 'multicatsearch_comment' );
			$msgInCategories = wfMsgHtml( 'multicatsearch_include' );
			$msgExCategories = wfMsgHtml( 'multicatsearch_exclude' );
			$msgSubmitButton = wfMsgHtml( 'multicatsearch_submit_button' );
		}
		else {
			$msgComment = wfMessage( 'multicatsearch_comment' )->escaped();
			$msgInCategories = wfMessage( 'multicatsearch_include' )->escaped();
			$msgExCategories = wfMessage( 'multicatsearch_exclude' )->escaped();
			$msgSubmitButton = wfMessage( 'multicatsearch_submit_button' )->escaped();
		}

		$dropdownLists = $this->showDropdownLists();
		$dropdownListsCount = substr_count( $dropdownLists, '<select' );

		$wgOut->addWikiText( $msgComment );
		$wgOut->addHTML("
	<br />
	<form id=\"MultiCategorySearch\" method=\"{$this->paramsPassMethod}\" action=\"{$action}\">
	<table border=\"0\">
		<tr>
			<td colspan=\"2\" align=\"left\">{$msgInCategories}</td>
		</tr>
		{$dropdownLists}");
		for( $i = $dropdownListsCount + 1; $i <= $this->inCategoriesNumber; $i++ ) {
			$categoryTitle = '';
			if( array_key_exists( $i, $this->inCategories ) )
				$categoryTitle = htmlspecialchars( $this->inCategories[$i] );
			$wgOut->addHTML("
		<tr>
			<td colspan=\"2\">
				<input tabindex=\"{$i}\" type=\"text\" size=\"40\" name=\"wpInCategory{$i}\" " .
					"value=\"{$categoryTitle}\" />
			</td>
		</tr>");
		}
		$wgOut->addHTML("
		<tr>
			<td colspan=\"2\" align=\"left\">{$msgExCategories}</td>
		</tr>");
		for( $i = 1; $i <= $this->exCategoriesNumber; $i++ ) {
			$j = $this->inCategoriesNumber + $i;
			$categoryTitle = '';
			if( array_key_exists( $i, $this->exCategories ) )
				$categoryTitle = htmlspecialchars( $this->exCategories[$i] );
			$wgOut->addHTML("
		<tr>
			<td colspan=\"2\">
				<input tabindex=\"{$j}\" type=\"text\" size=\"40\" name=\"wpExCategory{$i}\" " .
					"value=\"{$categoryTitle}\" />
			</td>
		</tr>");
		}
		$j = $this->inCategoriesNumber + $this->exCategoriesNumber + 1;
		$wgOut->addHTML("
		<tr>
			<td colspan=\"2\" style=\"padding-top: 1em\" align=\"right\">
				<input tabindex=\"{$j}\" type=\"submit\" name=\"wpSubmitSearchParams\" " .
					"value=\"{$msgSubmitButton}\" />
			</td>
		</tr>
	</table>
	</form>\n");

		if( $this->insertEditTools == true && $wgUseAjax == true &&
			function_exists( 'charInsert' ) ) {
				$currentPath = str_replace( '\\', '/', dirname(__FILE__) );
				$curServerPath =
					substr( $currentPath, stripos( $currentPath, $wgScriptPath . '/' ) );
				$wgOut->addScript( "<script type=\"{$wgJsMimeType}\" " .
					"src=\"{$curServerPath}/edittools.js\"></script>\n" );

				$filename = dirname(__FILE__) . '/EditTools.htm';
				$handle = fopen( $filename, 'rb' );
				$contents = fread( $handle, filesize( $filename ) );
				fclose( $handle );

				$wgOut->addHtml( '<div class="mw-editTools">' );
				$wgOut->addWikiText( $contents );
				$wgOut->addHtml( '</div>' );
		}
	}

	// This function gets a list of sub-categories in the specified category
	function getSubCategories( $categoryTitle, $subCategoriesLimit = 500 ) {
		global $wgDBprefix;

		$dbr = wfGetDB( DB_SLAVE );
		$categoryTitle = $dbr->addQuotes( str_replace( ' ', '_', ucfirst( $categoryTitle ) ) );
		$subCategories = array( '*' => '' );

		$sqlQueryStr = "SELECT {$wgDBprefix}page.page_title " .
			"FROM {$wgDBprefix}page, {$wgDBprefix}categorylinks " .
			"WHERE {$wgDBprefix}categorylinks.cl_to = {$categoryTitle} " .
				"AND {$wgDBprefix}page.page_namespace = 14 " .
				"AND {$wgDBprefix}categorylinks.cl_from = {$wgDBprefix}page.page_id " .
			"ORDER BY {$wgDBprefix}categorylinks.cl_sortkey " .
			"LIMIT {$subCategoriesLimit}";
		$res = $dbr->query( $sqlQueryStr, 'MultiCategorySearch::getSubCategories', false );

		if( $dbr->numRows( $res ) == 0 )
			return false;
		while( $subCategory = $dbr->fetchObject( $res ) )
			$subCategories[ str_replace( '_', ' ', $subCategory->page_title ) ] =
				$subCategory->page_title;
		return $subCategories;
	}

	// This function inserts drop-down lists for categories selection (instead of simple text input fields)
	function showDropdownLists() {
		global $wgRequest;

		if( $this->useDropdownLists == false )
			return '';
		$dropDownList = array();
		$outputMarkup = '';

		// Configuration settings

		$dropDownList['1'] = array(
			'*' => '',
			'ListCaption' => 'TYPE LIST CAPTION HERE',		// enter list caption, it will appear to the
															// left of this drop-down list
			'AutoFillFrom' => 'TYPE PARENT CATEGORY NAME HERE',		// enter some category here, if you want
															// all of it's subcategories to appear in this list;
															// or left intact otherwise
			'TYPE DROP-DOWN OPTION 1 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',	// enter drop-down
															// list options and corresponding category names,
															// e. g. 'Actors of USA' => 'American actors',
															// these options are not shown when 'AutoFillFrom'
															// field is activated above
			'TYPE DROP-DOWN OPTION 2 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',
			'TYPE DROP-DOWN OPTION 3 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',	// add additional
															// options to the end of this list or delete
															// unnecessary options (just copy or delete lines);
															// you can also add or delete the whole drop-down 
															// lists as required, to add one just copy and adjust
															// it's number: $dropDownList['CHANGE NUMBER HERE']
		);

		$dropDownList['2'] = array(
			'*' => '',
			'ListCaption' => 'TYPE LIST CAPTION HERE',
			'AutoFillFrom' => 'TYPE PARENT CATEGORY NAME HERE',
			'TYPE DROP-DOWN OPTION 1 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',
			'TYPE DROP-DOWN OPTION 2 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',
			'TYPE DROP-DOWN OPTION 3 HERE' => 'TYPE CORRESPONDING CATEGORY NAME HERE',
		);

		// End of configuration settings, don't change the script below this line

		$listsNumber = count( $dropDownList );

		for( $i = 1; $i <= $listsNumber; $i++ ) {
			$listCaption = '';
			if( array_key_exists( 'ListCaption', $dropDownList[$i] ) )
				$listCaption = $dropDownList[$i]['ListCaption'];
			unset( $dropDownList[$i]['ListCaption'] );

			if( array_key_exists( 'AutoFillFrom', $dropDownList[$i] ) &&
				$dropDownList[$i]['AutoFillFrom'] !== 'TYPE PARENT CATEGORY NAME HERE' ) {
					$subCategories = $this->getSubCategories( $dropDownList[$i]['AutoFillFrom'] );
					if( $subCategories !== false )
						$dropDownList[$i] = $subCategories;
					else
						$dropDownList[$i] =
							array( 'ERROR: NO SUCH PARENT CATEGORY FOUND OR IT HAS NO SUBCATEGORIES' => '' );
			}
			else
				unset( $dropDownList[$i]['AutoFillFrom'] );

			$outputMarkup .= "
		<tr>
			<td>{$listCaption}</td>
			<td>
				<select tabindex=\"{$i}\" name=\"wpInCategory{$i}\" id=\"wpInCategory{$i}\">\n";
			foreach( $dropDownList[$i] as $optionName => $optionValue ) {
				$optionName = htmlspecialchars( $optionName );
				$optionValue = htmlspecialchars( $optionValue );
				$selected = '';
				if( $wgRequest->getVal( 'wpInCategory' . $i ) !== null &&
					$wgRequest->getVal( 'wpInCategory' . $i ) == $optionValue ) {
						$selected = ' selected="selected"';
					}
				$outputMarkup .= "\t\t\t\t\t<option value=\"{$optionValue}\"{$selected}>" .
					"{$optionName}</option>\n";
			}
			$outputMarkup .= "
				</select>
			</td>
		</tr>";
		}
		$outputMarkup .= '<tr><td colspan="2"><br /></td></tr>';

		return $outputMarkup;
	}
}
?>