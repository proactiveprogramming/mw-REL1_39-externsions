<?php
/*
 * Copyright (c) 2010 University of Macau
 *
 * Licensed under the Educational Community License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may
 * obtain a copy of the License at
 *
 * http://www.osedu.org/licenses/ECL-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an "AS IS"
 * BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/**
 * @brief Encapsulates all the MySQL database queries performed by the CoAuthor extension.
 *
 * The CoAuthorMySQLFactory class is responsible for encapsulating all the
 * MySQL database queries performed by the CoAuthor extension when Mediawiki
 * is running a MySQL server.
 */
class CoAuthorMySQLFactory {

	/**
	 * Creates a temporary table to hold all the co-authors of the specified author.
	 *
	 * @return 	A MySQL query string.
	 */
	public static function createCoAuthorTemporaryTable() {
		return <<<SQL
			CREATE TEMPORARY TABLE co_author_tmp ( 
			`rev_user` INT(10) UNSIGNED NOT NULL, 
			`rev_user_text` VARCHAR(255), 
			`rev_page` INT(10) UNSIGNED NOT NULL, 
			`rev_max_time_a` DATE, 
			`rev_min_time_a` DATE, 
			`rev_max_time_b` DATE, 
			`rev_min_time_b` DATE, 
			KEY `rev_user` (`rev_user`)
			) CHARSET 'utf8' ;
SQL;
	}

	/**
	 * Select the related co-authors data from revision table, and insert them into the temporary table.
	 * Related data includes co-author's ID and user name, page ID, earliest and latest edit date for each page.
	 *
	 * @param	$userId		User ID of queried author.
	 * @return 	A MySQL query string.
	 */
	public static function insertCoAuthorTemporaryTable($userId) {
		return <<<SQL
			INSERT INTO co_author_tmp
			SELECT rev2.rev_user, rev2.rev_user_text, rev2.rev_page, 
			MAX(STR_TO_DATE(substr(rev1.rev_timestamp, 1, 8), '%Y%m%d')) AS rev_max_time_a, 
			MIN(STR_TO_DATE(substr(rev1.rev_timestamp, 1, 8), '%Y%m%d')) AS rev_min_time_a, 
			MAX(STR_TO_DATE(substr(rev2.rev_timestamp, 1, 8), '%Y%m%d')) AS rev_max_time_b, 
			MIN(STR_TO_DATE(substr(rev2.rev_timestamp, 1, 8), '%Y%m%d')) AS rev_min_time_b 
			FROM revision rev1 JOIN revision rev2 ON (rev1.rev_page = rev2.rev_page) 
			WHERE rev1.rev_user = $userId 
			AND rev1.rev_minor_edit = 0 
			AND rev2.rev_minor_edit = 0 
			AND rev2.rev_user <> $userId 
			AND rev2.rev_user <> 0 
			AND rev2.rev_user NOT IN (SELECT ug_user FROM user_groups WHERE ug_group = 'bot') 
			GROUP BY rev2.rev_user, rev2.rev_page ;
SQL;
	}

	/**
	 * Creates a temporary table to store the degree of coauthorship.
	 *
	 * @return 	A MySQL query string.
	 */
	public static function createDegreeTemporaryTable() {
		return <<<SQL
			CREATE TEMPORARY TABLE co_author_tmp2 (
			`rev_user_text` VARCHAR(255) NOT NULL, 
			`degree` DECIMAL(16,10) NOT NULL, 
			KEY `rev_user` (`rev_user_text`)
			) CHARSET 'utf8' ;
SQL;
	}

	/**
	 * Calculates degree of coauthorship and stored into the temporary table.
	 *
	 * @param	$userId		User ID of queried author.
	 * @return 	A MySQL query string.
	 */
	public static function insertDegreeTemporaryTable($userId) {
		return <<<SQL
			INSERT INTO co_author_tmp2 
			SELECT x.rev_user_text AS rev_user_text, 
			SUM(calculate_degree($userId, x.rev_user, x.rev_page, x.rev_max_time_a, x.rev_min_time_a, x.rev_max_time_b, x.rev_min_time_b, null, null, null, null, null, null)) AS degree 
			FROM co_author_tmp AS x 
			GROUP BY x.rev_user ;
SQL;
	}

	/**
	 * Drop a temporary table used by this extension.
	 *
	 * @param $tableName	Name of the temporary table to be dropped.
	 * @return 	A MySQL query string.
	 */
	public static function dropTemporaryTable($tableName) {
		return <<<SQL
			DROP TEMPORARY TABLE IF EXISTS $tableName ;
SQL;
	}

	/**
	 * Sums up the total degree of coauthorship.
	 *
	 * @return 	A MySQL query string.
	 */
	public static function selectTotalDegree() {
		return <<<SQL
			SELECT SUM(degree) AS d_total FROM co_author_tmp2 ;
SQL;
	}

	/**
	 * Select degree of coauthorship value and its percentage from the degree table.
	 *
	 * @param	$degree_total	Total degree of coauthorship.
	 * @param	$orderCol		Name of column to order by.
	 * @param	$orderType		Order of the rows. ASC for ascending, DESC for descending.
	 * @param	$limit			Number of rows to be outputted.
	 * @return 	A MySQL query string.
	 */
	public static function selectDegree($degree_total, $orderCol, $orderType, $limit) {
		if( $limit > 0 ) {
			$limit = ' LIMIT ' . $limit;
		} else {
			$limit = '';
		}

		return <<<SQL
			SELECT y.rev_user_text, y.degree / $degree_total AS degree_percentage, y.degree AS degree_value 
			FROM co_author_tmp2 AS y 
			ORDER BY $orderCol $orderType 
			$limit;
SQL;
	}

	/**
	 * Query the user ID by user name.
	 *
	 * @param	$userText	User name of the author.
	 * @return 	A MySQL query string.
	 */
	public static function selectUserId($userText) {
		return <<<SQL
			SELECT rev_user 
			FROM revision 
			WHERE rev_user_text = '$userText' 
			AND rev_user <> 0 
			LIMIT 1;
SQL;
	}
}


/**
 *
 * @brief Encapsulates all the PostgreSQL database queries performed by the CoAuthor extension.
 *
 * The CoAuthorPostgreSQLFactory class is responsible for encapsulating all the PostgreSQL database
 * queries performed by the CoAuthor extension when Mediawiki is running a PostgreSQL
 * server.
 */
class CoAuthorPostgreSQLFactory {

	/**
	 * Creates a temporary table to hold all the co-authors of the specified author.
	 *
	 * @return 	A PostgreSQL query string.
	 */
	public static function createCoAuthorTemporaryTable() {
		return <<<SQL
			CREATE TEMPORARY TABLE co_author_tmp (
			rev_user NUMERIC(11) NOT NULL, 
			rev_user_text VARCHAR(255), 
			rev_page NUMERIC(11) NOT NULL, 
			rev_max_time_a DATE, 
			rev_min_time_a DATE, 
			rev_max_time_b DATE, 
			rev_min_time_b DATE
			);
SQL;
	}

	/**
	 * Select the related co-authors data from revision table, and insert them into the temporary table.
	 * Related data includes co-author's ID and user name, page ID, earliest and latest edit date for each page.
	 *
	 * @param	$userId		User ID of queried author.
	 * @return 	A PostgreSQL query string.
	 */
	public static function insertCoAuthorTemporaryTable($userId) {
		return <<<SQL
			INSERT INTO co_author_tmp 
			SELECT rev2.rev_user, rev2.rev_user_text, rev2.rev_page, 
			MAX(TO_DATE(SUBSTR(TO_CHAR(rev1.rev_timestamp, 'YYYYMMDD'), 1, 8), 'YYYYMMDD')) AS rev_max_time_a, 
			MIN(TO_DATE(SUBSTR(TO_CHAR(rev1.rev_timestamp, 'YYYYMMDD'), 1, 8), 'YYYYMMDD')) AS rev_min_time_a, 
			MAX(TO_DATE(SUBSTR(TO_CHAR(rev2.rev_timestamp, 'YYYYMMDD'), 1, 8), 'YYYYMMDD')) AS rev_max_time_b, 
			MIN(TO_DATE(SUBSTR(TO_CHAR(rev2.rev_timestamp, 'YYYYMMDD'), 1, 8), 'YYYYMMDD')) AS rev_min_time_b 
			FROM revision rev1 JOIN revision rev2 ON (rev1.rev_page = rev2.rev_page) 
			WHERE rev1.rev_user = $userId 
			AND rev1.rev_minor_edit = 0 
			AND rev2.rev_minor_edit = 0 
			AND rev2.rev_user <> $userId 
			AND rev2.rev_user <> 0 
			AND rev2.rev_user NOT IN (SELECT ug_user FROM user_groups WHERE ug_group = 'bot') 
			GROUP BY rev2.rev_user, rev2.rev_user_text, rev2.rev_page ;
SQL;
	}

	/**
	 * Creates a temporary table to store the degree of coauthorship.
	 *
	 * @return 	A PostgreSQL query string.
	 */
	public static function createDegreeTemporaryTable() {
		return <<<SQL
			CREATE TABLE co_author_tmp2 (
			rev_user_text VARCHAR(255) NOT NULL,
			degree DECIMAL(16,10) NOT NULL
			) ;
SQL;
	}

	/**
	 * Calculate degrees of coauthorship for each co-author, and store into the temporary table.
	 *
	 * @param	$userId		User ID of queried author.
	 * @return 	A PostgreSQL query string.
	 */
	public static function insertDegreeTemporaryTable($userId) {
		$userIdNum = (int)$userId;

		return <<<SQL
			INSERT INTO co_author_tmp2 
			SELECT x.rev_user_text AS rev_user_text, 
			SUM(calculate_degree($userIdNum, x.rev_user, x.rev_page, x.rev_max_time_a, x.rev_min_time_a, x.rev_max_time_b, x.rev_min_time_b)) AS degree 
			FROM co_author_tmp AS x 
			GROUP BY x.rev_user, x.rev_user_text ;
SQL;
	}

	/**
	 * Drop a temporary table used by this extension.
	 *
	 * @param	$tableName	Name of the temporary table to be dropped.
	 * @return 	A PostgreSQL query string.
	 */
	public static function dropTemporaryTable($tableName) {
		return <<<SQL
			DROP TABLE IF EXISTS $tableName ;
SQL;
	}

	/**
	 * Sums up the total degree of coauthorship.
	 *
	 * @return 	A PostgreSQL query string.
	 */
	public static function selectTotalDegree() {
		return <<<SQL
			SELECT SUM(degree) AS d_total FROM co_author_tmp2 ;
SQL;
	}

	/**
	 * Select degree of coauthorship value and its percentage from the degree table.
	 *
	 * @param $degree_total	Total degree of coauthorship.
	 * @param $orderCol		Name of column to order by.
	 * @param $orderType	Order of the rows. ASC for ascending, DESC for descending.
	 * @param $limit		Number of rows to be outputted.
	 * @return 	A PostgreSQL query string.
	 */
	public static function selectDegree($degree_total, $orderCol, $orderType, $limit) {
		if( $limit > 0 ) {
			$limit = ' limit ' . $limit;
		} else {
			$limit = '';
		}

		return <<<SQL
			SELECT y.rev_user_text, y.degree / $degree_total AS degree_percentage, y.degree AS degree_value
			FROM co_author_tmp2 AS y
			ORDER BY $orderCol $orderType
			$limit;
SQL;
	}

	/**
	 * Query the user ID by user name.
	 *
	 * @param	$userText	User name of the author.
	 * @return 	A PostgreSQL query string.
	 */
	public static function selectUserId($userText) {
		return <<<SQL
			SELECT rev_user
			FROM revision
			WHERE rev_user_text = '$userText'
			AND rev_user <> 0
			LIMIT 1;
SQL;
	}
}


/**
 * @brief Handling all the output formatting for CoAuthor extension.
 *
 * Class CoAuthorFormatter is responsible for handling all the presentation and output
 * formatting in the CoAuthor extension.
 */
class CoAuthorFormatter {

	/** Default decimal point position for display degree in percentage */
	const cDecimalPercent = 3;

	/** Display <cMinPercent instead of the actual percentage if the strength < this value */
	const cMinPercent = 0.01;

	/** Default decimal point position for display degree in value */
	const cDecimalValue = 1;

	/** Display cMinValue instead of the actual value if the strength < this value */
	const cMinValue = 0.01;

	/** Muliply the value by this scale factor for display */
	const cScaleFactor = 1000;

	/**
	 * Get a reference to the current Mediawiki Skin object.
	 */
	private static function getSkin() {
		global $wgUser;
		return $wgUser->getSkin();
	}

	/**
	 * Format the percentage value on the table.
	 *
	 * @param	$degree		The percentage value to be formatted.
	 * @return	A string of formatted percentage value.
	 */
	private static function formatDegreePercentage($degree) {
		$r_degree = round( $degree, self::cDecimalPercent );

		/* If the percentage is smaller than the defined minimum, display as
		 * "<x.xx%", otherwise show the percentage rounded to defined decimal point.
		 */
		if( $r_degree < self::cMinPercent ) {
			return '<' . self::cMinPercent . '%';
		} else {
			return number_format( $r_degree, self::cDecimalPercent, '.', '' ) . '%';
		}
	}

	/**
	 * Format the degree value on the table.
	 *
	 * @param	$degree		The degree value to be formatted.
	 * @return	A string of formatted degree value.
	 */
	private static function formatDegreeValue($degree) {
		$r_degree = $degree;

		/* If the degree is smaller than the defined minimum, display as
		 * "<x.xx", otherwise show the degree rounded to defined decimal point.
		 */
		if( $r_degree < self::cMinValue ) {
			return '<' . ( self::cMinPercent * self::cScaleFactor );
		} else {
			return number_format( $r_degree * self::cScaleFactor, self::cDecimalValue, '.', '' );
		}
	}

	/**
	 * Creates a link to the user page of an author.
	 *
	 * @param	$userText	Username of the author.
	 * @return	A hyperlink to the user page of this author.
	 */
	private static function formatUserTextLink($userText) {
		return self::getSkin()->makeLinkObj( Title::makeTitle( NS_USER, $userText ), htmlspecialchars( $userText ) );
	}

	/**
	 * Creates a link to the co-author page of an author.
	 *
	 * @param	$userText	Username of the author.
	 * @return	An HTML <a> element that links to the co-author page of this author.
	 */
	private static function formatCoAuthorLink($userText) {
		return '(' . self::getSkin()->makeLinkObj( SpecialPage::getTitleFor( 'CoAuthor', $userText ), wfMsgHtml( 'coauthor-label' ) ) . ')';
	}

	/**
	 * Creates the links on the header row of the result table.
	 *
	 * @param	$userText	Username of the author.
	 * @param	$orderCol	Name of column to order by.
	 * @param	$orderType	Order of the rows. 'asc' for ascending, 'desc' for descending.
	 * @param 	$limit		Number of rows to be outputted.
	 * @return	A hyperlink to the co-author page with specified parameter.
	 */
	private static function formatTableHeaderLink($userText, $orderCol, $orderType, $limit) {
		global $wgScriptPath;
		return $wgScriptPath . "/index.php?title=Special:" . wfMsgHtml( 'coauthor-url' ) . "&userText=$userText&orderCol=$orderCol&orderType=$orderType&limit=$limit";
	}

	/**
	 * Creates the header row of the result table.
	 *
	 * @param	$userText			Username of the author.
	 * @param	$orderCol			Name of the column currently ordered by.
	 * @param	$orderType			Current order of the rows. 'asc' for ascending, 'desc' for descending.
	 * @param 	$limit				Number of rows to be outputted.
	 * @param	$defaultOrderCol	Name of column to order by.
	 * @param	$defaultOrderType	Order of the rows. 'asc' for ascending, 'desc' for descending.
	 * @param	$content			Text to be displayed on the field.
	 * @return	The HTML code of the table header.
	 */
	private static function formatTableHeader($userText, $orderCol, $orderType, $limit, $defaultOrderCol, $defaultOrderType, $content) {
		$table_header = Xml::openElement( 'th' );

		if( $orderCol == $defaultOrderCol ) {
			if( $orderType == 'desc' ) {
				$table_header .= Xml::element( 'a', array( 'href' => self::formatTableHeaderLink( $userText, $orderCol, 'asc', $limit ) ), $content );
			} else {
				$table_header .= Xml::element( 'a', array( 'href' => self::formatTableHeaderLink( $userText, $orderCol, 'desc', $limit ) ), $content );
			}
		} else {
			$table_header .= Xml::element( 'a', array( 'href' => self::formatTableHeaderLink( $userText, $defaultOrderCol, $defaultOrderType, $limit ) ), $content );
		}

		$table_header .= Xml::closeElement( 'th' );

		return $table_header;
	}

	/**
	 * Creates the title line.
	 *
	 * @param 	$userText	Username of the author being queried.
	 * @param 	$queryTime	Processing time of this query.
	 * @return
	 */
	public static function formatResultTitle($userText, $queryTime) {
		return self::formatUserTextLink( $userText ) . wfMsg( 'ca_authors_coauthor' ) . ' (' . round( $queryTime, 2 ) . ' ' . wfMsg( 'ca_seconds' ) . ')';
	}

	/**
	 * Creates the query form at the beginning of the special page.
	 *
	 * @param	$userText	Username of the author being queried.
	 * @param	$orderCol	Name of the column currently ordered by.
	 * @param	$orderType	Current order of the rows. 'asc' for ascending, 'desc' for descending.
	 * @param	$limit		Current limit of result rows.
	 * @return	An HTML query form.
	 */
	public static function formatCoAuthorForm($userText, $orderCol, $orderType, $limit) {
		global $wgScript, $wgTitle;

		$form  = Xml::openElement( 'form', array( 'method' => 'get', 'action' => $wgScript ) );
		{
			$form .= Xml::hidden( 'title', $wgTitle->getPrefixedText() );

			$form .= Xml::openElement( 'fieldset' );
			{
				$form .= Xml::element( 'legend', array(), wfMsg( 'ca_search_box_title' ) );
				/* Username textbox */
				$form .= Xml::label( wfMsg( 'ca_user' ) . ' ', 'ca_user' );
				$form .= Xml::input( 'userText', 20, $userText );
				/* Submit button */
				$form .= Xml::submitButton( wfMsg( 'ca_button_search') );

				$form .= Xml::element( 'br' );

				/* Rows per page selector */
				$form .= Xml::label( wfMsg( 'ca_list' ) . ' ', 'ca_list' );

				$form .= Xml::openElement( 'select', array( 'id' => 'limit', 'name' => 'limit' ) );
				{
					$form .= Xml::option( wfMsg( 'ca_at_most' ) . ' 20', '20', $limit == 20 );
					$form .= Xml::option( wfMsg( 'ca_at_most' ) . ' 50', '50', $limit == 50 );
					$form .= Xml::option( wfMsg( 'ca_at_most' ) . ' 100', '100', $limit == 100 );
					$form .= Xml::option( wfMsg( 'ca_at_most' ) . ' 250', '250', $limit == 250 );
					$form .= Xml::option( wfMsg( 'ca_at_most' ) . ' 500', '500', $limit == 500 );
					$form .= Xml::option( wfMsg( 'ca_show_all' ), '0', $limit == 0 );
				}
				$form .= Xml::closeElement( 'select' );
			}
			$form .= Xml::closeElement( 'fieldset' );
		}
		$form .= Xml::closeElement( 'form' );

		return $form;
	}

	/**
	 * Creates the table that shows the degree of coauthorship.
	 *
	 * @param	$connection		Reference to Mediawiki database object with read and write privilege.
	 * @param	$result			Result of the degree of coauthorship query.
	 * @param	$userText		Username of the author being queried.
	 * @param	$orderCol		Name of the column currently ordered by.
	 * @param	$orderType		Current order of the rows. 'asc' for ascending, 'desc' for descending.
	 * @param	$limit			Current limit of result rows.
	 * @return	An HTML table that contains the result.
	 */
	public static function formatDegreeTable($connection, $result, $userText, $orderCol, $orderType, $limit) {
		global $wgScriptPath;

		$degree_table = Xml::openElement( 'table', array( 'border' => '0', 'bordercolor' => 'gray', 'cellpadding' => '2', 'cellspacing' => '0' ) );

		$degree_table .= CoAuthorFormatter::formatTableHeader($userText, $orderCol, $orderType, $limit, 'rev_user_text', 'asc', wfMsg( 'ca_user' ) );
		$degree_table .= CoAuthorFormatter::formatTableHeader($userText, $orderCol, $orderType, $limit, 'degree', 'desc', wfMsg( 'ca_degree_value' ) );
		$degree_table .= CoAuthorFormatter::formatTableHeader($userText, $orderCol, $orderType, $limit, 'degree', 'desc', wfMsg( 'ca_degree_strength' ) );
		$degree_table .= Xml::element( 'th' );


		for( $i = 0; $row = $connection->fetchRow( $result ); ++$i ) {
			$userText = $row['rev_user_text'];
			$degreeValue = $row['degree_value'];
			$degreePercentage = $row['degree_percentage'];

			if( ( $i % 2 ) != 0 ) {
				$degree_table .= Xml::openElement( 'tr', array( 'valign' => 'top' ) );
			} else {
				$degree_table .= Xml::openElement( 'tr', array( 'valign' => 'top', 'bgcolor' => "#EBEBEB" ) );
			}

			$degree_table .= Xml::openElement( 'td', array( 'style' => 'padding-left: 5px; padding-right: 10px' ) );
			$degree_table .= CoAuthorFormatter::formatUserTextLink( $userText );
			$degree_table .= ' ';
			$degree_table .= CoAuthorFormatter::formatCoAuthorLink( $userText );
			$degree_table .= Xml::closeElement( 'td' );

			$degree_table .= Xml::element( 'td', array( 'style' => 'padding-left: 10px; padding-right: 10px' ), CoAuthorFormatter::formatDegreeValue( $degreeValue ) );
			$degree_table .= Xml::element( 'td', array( 'style' => 'padding-left: 10px; padding-right: 10px' ), CoAuthorFormatter::formatDegreePercentage( $degreePercentage ) );

			$degree_table .= Xml::openElement( 'td', array( 'align' => 'left' ) );
			$degree_table .= Xml::element( 'img', array( 'src' => $wgScriptPath . '/extensions/CoAuthor/CoAuthorBar.php?percentage=' . $degreePercentage ) );
			$degree_table .= Xml::closeElement( 'td' );

			$degree_table .= Xml::closeElement( 'tr' );
		}

		$degree_table .= Xml::closeElement( 'table' );

		return $degree_table;
	}
}


/**
 * @brief Implements the CoAuthor special page.
 *
 * Class CoAuthor is responsible for implementing the CoAuthor Special Page
 * and for handling all the extension logic.
 */
class CoAuthor extends SpecialPage {

	const defaultUserText = '';
	const defaultOrderCol = 'degree';
	const defaultOrderType = 'desc';
	const defaultLimit = 50;

	// Record the time used in query.
	private $time = 0;
	// Default SQL factory used.
	private $sqlFactory = 'CoAuthorMySQLFactory';

	/**
	 * Constructor. Also selects the SQL factory to be used.
	 */
	function CoAuthor() {
		global $wgDBtype;

		SpecialPage::SpecialPage( 'CoAuthor' );
		wfLoadExtensionMessages( 'CoAuthor' );

		if ($wgDBtype == 'mysql') {
			$this->sqlFactory = 'CoAuthorMySQLFactory';
		}
		elseif ($wgDBtype == 'postgres') {
			$this->sqlFactory = 'CoAuthorPostgreSQLFactory';
		}
	}

	/**
	 * Get the user ID by user name.
	 *
	 * @param	$userText	The user name to be queried.
	 * @return 	User ID, or NULL if not found.
	 */
	private function getUserId($userText) {
		$connection = wfGetDB( DB_SLAVE );
		$userTextPara = str_replace( "'", "''", $userText );
		$result = $connection->query( call_user_func($this->sqlFactory . '::selectUserId', $userTextPara) ) or die( $connection->lastError() );

		if( $row = $connection->fetchRow( $result ) ) {
			return $row['rev_user'];
		}

		return null;
	}

	/**
	 * Initiates request parameters. Set to user specified values if provided, otherwise default values are used.
	 *
	 * @param	$wgRequest	Mediawiki wgRequest object.
	 * @param	$parameter	Sub-page style parameter from the special page. Specify user name to be queried.
	 * @param	$userText	User name to be queried, in HTTP GET format.
	 * @param	$userId		User ID to be queried. To be assigned by this function.
	 * @param	$orderCol	Name of the column used to sort the result.
	 * @param	$orderType	Sort order to be followed. 'asc' for ascending, 'desc' for descending.
	 * @param	$limit		Number of co-authors to be shown in the result.
	 */
	private function initRequestParameters($wgRequest, $parameter, &$userText, &$userId, &$orderCol, &$orderType, &$limit) {
		if( isset( $parameter ) ) {
			$userText = $parameter;
		} else {
			$userText = $wgRequest->getVal( 'userText' );
		}

		if( !isset( $userText ) || trim( $userText ) == '' ) {
			$userText = null;
			$userId = null;
			$orderCol = null;
			$orderType = null;
			$limit = null;
		} else {
			$nt = Title::makeTitleSafe( NS_USER, $userText );
			$userText = $nt->getText();
			$userId = $this->getUserId( $userText );
			$orderCol = $wgRequest->getVal( 'orderCol' );
			$orderType = $wgRequest->getVal( 'orderType' );
			$limit = $wgRequest->getInt( 'limit' );
		}

		if ( !in_array( $orderCol, array( 'rev_user_text', 'degree' ) ) ) {
			$orderCol = self::defaultOrderCol;
		}

		if( !in_array( $orderType, array( 'desc', 'asc' ) ) ) {
			if ( $orderCol == 'degree' ) {
				$orderType = 'desc';
			} else {
				$orderType = 'asc';
			}
		}

		if ( !is_numeric( $limit ) || $limit <= 0 ) {
			$limit = self::defaultLimit;
		}
	}

	/**
	 * Add the co-authors query form into the page.
	 * 
	 * @param	$wgOut		Mediawiki wgOut object.
	 * @param	$userText	User name to be queried. (optional)
	 * @param	$orderCol	Name of column used to sort the result. (optional)
	 * @param	$orderType	Sort order to be followed. 'asc' for ascending, 'desc' for descending. (optional)
	 * @param 	$limit		Number of co-authors to be shown in the result. (optional)
	 */
	private function displayCoAuthorForm($wgOut, $userText = self::defaultUserText, $orderCol = self::defaultOrderCol, $orderType = self::defaultOrderType, $limit = self::defaultLimit) {
		$wgOut->addHTML( CoAuthorFormatter::formatCoAuthorForm( $userText, $orderCol, $orderType, $limit ) );
	}

	/**
	 * Displays the empty query form.
	 * 
	 * @param	$wgOut		Mediawiki wgOut object.
	 */
	private function displayUserForm($wgOut) {
		$this->displayCoAuthorForm( $wgOut );
		$wgOut->addHTML( wfMsg( 'ca_input_user_name' ) . '.' );
	}

	/**
	 * Displays the empty query form, and shows 'the specified user cannot be found' message.
	 * 
	 * @param	$wgOut		Mediawiki wgOut object.
	 * @param	$userText	The non-exist user name.
	 */
	private function displayRepeatForm($wgOut, $userText) {
		$this->displayCoAuthorForm( $wgOut );
		$wgOut->addHTML( wfMsg( 'ca_user' ) . ' ' . $userText . ' ' . wfMsg( 'ca_user_account_not_found' ) . '.' );
	}

	/**
	 * Display the co-authors query result, or shows 'co-author not found' message.
	 * 
	 * @param	$wgOut		Mediawiki wgOut object.
	 * @param	$userText	User name to be queried.
	 * @param	$userId		User ID of the author to be queried.
	 * @param	$orderCol	Name of column used to sort the result.
	 * @param	$orderType	Sort order to be followed. 'asc' for ascending, 'desc' for descending.
	 * @param 	$limit		Number of co-authors to be shown in the result.
	 */
	private function displayResults($wgOut, $userText, $userId, $orderCol, $orderType, $limit) {
		$this->startTime();

		$connection = wfGetDB( DB_MASTER );

		$query = $this->calculateDegree( $connection, $userId, $orderCol, $orderType, $limit );
		$this->displayCoAuthorForm( $wgOut, $userText, $orderCol, $orderType, $limit );

		if ( is_null( $query ) ) {
			$wgOut->addHTML( wfMsg( 'ca_no_coauthor' ) );
		} else {
			$wgOut->addHTML( CoAuthorFormatter::formatResultTitle( $userText, round( $this->diffTime(), 2 ) ) . '.' );
			$wgOut->addHTML( CoAuthorFormatter::formatDegreeTable( $connection, $query, $userText, $orderCol, $orderType, $limit ) );
		}
	}

	/**
	 * Execute database queries to calculate coauthorship degree of specified author.
	 * 
	 * @param	$connection		Reference to Mediawiki database object with read and write privilege.
	 * @param	$userId			User ID of the author to be queried.
	 * @param	$orderCol		Name of column used to sort the result.
	 * @param	$orderType		Sort order to be followed. 'asc' for ascending, 'desc' for descending.
	 * @param	$limit			Number of co-authors to be shown in the result.
	 */
	private function calculateDegree($connection, $userId, $orderCol, $orderType, $limit) {
		$connection->query( call_user_func($this->sqlFactory . '::dropTemporaryTable', 'co_author_tmp') ) or die( $connection->lastError() );
		$connection->query( call_user_func($this->sqlFactory . '::createCoAuthorTemporaryTable') ) or die( $connection->lastError() );
		$connection->query( call_user_func($this->sqlFactory . '::insertCoAuthorTemporaryTable', $userId) ) or die( $connection->lastError() );
		$connection->query( call_user_func($this->sqlFactory . '::dropTemporaryTable', 'co_author_tmp2') ) or die( $connection->lastError() );
		$connection->query( call_user_func($this->sqlFactory . '::createDegreeTemporaryTable') ) or die( $connection->lastError() );
		$connection->query( call_user_func($this->sqlFactory . '::insertDegreeTemporaryTable', $userId) ) or die( $connection->lastError() );
		$result = $connection->query( call_user_func($this->sqlFactory . '::selectTotalDegree') ) or die( $connection->lastError() );

		$degreeTotal = 0;

		if( $row = $connection->fetchRow( $result ) ) {
			$degreeTotal = $row['d_total'] / 100;
		}

		if( $degreeTotal == 0 ) {
			return null;
		}


		return $connection->query( call_user_func($this->sqlFactory . '::selectDegree', $degreeTotal, $orderCol, $orderType, $limit ) );
	}

	/**
	 * Entry point of the CoAuthor class. Generate the query form if no parameter is specified,
	 * 'user not found' page if the specified user name cannot be found, or the degree of coauthorship
	 * result if it can be found.
	 *
	 * @param	$par	User name, in sub-page style parameter.
	 */
	function execute( $par ) {
		global $wgRequest, $wgOut;

		$this->setHeaders();
		$this->initRequestParameters( $wgRequest, $par, $userText, $userId, $orderCol, $orderType, $limit );

		if ( !isset( $userText ) ) {
			$this->displayUserForm( $wgOut );
		} else if ( !isset( $userId ) ) {
			$this->displayRepeatForm( $wgOut, $userText );
		} else {
			$this->displayResults( $wgOut, $userText, $userId, $orderCol, $orderType, $limit );
		}
	}

	/**
	 * Record the time before all the queries begin.
	 */
	private function startTime() {
		$this->time = microtime( true );
	}

	/**
	 * Return time used by all the queries.
	 * @return The time used.
	 */
	private function diffTime() {
		return microtime( true ) - $this->time;
	}
}

?>
