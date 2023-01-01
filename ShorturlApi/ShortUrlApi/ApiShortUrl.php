<?php
/**
 * @ingroup Extensions
 * @{
 * ApiShortUrl Class
 *
 * @file
 * @{
 * @copyright © 2014 Daniel Norton d/b/a WeirdoSoft - www.weirdosoft.com
 *
 * @section License
 * **GPL v3**\n
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * \n\n
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * \n\n
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * @}
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	echo "This file is an extension to MediaWiki software and is not designed for standalone use.\n";
	die( 1 );
}

/**
 * API for the ShortUrl extension.
 */
class ApiShortUrl extends ApiBase {

	/** Our API version */
	const VERSION = MW_EXT_SHORTURLAPI_VERSION;

	/** module ID ( short 2- or 3-letter code ) */
	const MID = MW_EXT_SHORTURLAPI_API_MID;

	/** query parameter name */
	const PARAM_NAME = MW_EXT_SHORTURLAPI_PARAM_NAME;

	/** name of 'codes' query parameter ( without the MID ) */
	const PARAM_CODES = 'codes';

	/** For parameters and semantics, see ApiBase::__construct */
	public function __construct( $query, $moduleName ) {

		// spit out a warning if the ShortUrl extension is not active
		if ( self::$_allowMissingShortUrlExtensionNotice &&
				!array_key_exists( 'ShortUrl', $GLOBALS['wgSpecialPages'] ) ) {

			// only do this once ( per load )
			self::$_allowMissingShortUrlExtensionNotice = false;

			// spit out the warning
			trigger_error(
				'The ShortUrl API was referenced, but the ShortUrl extension is not active.',
				E_USER_NOTICE );

		}

		$this->_moduleName = $moduleName ;  // save this for later
		parent::__construct( $query, $moduleName, self::MID );
	}

	/** For parameters and semantics, see ApiBase::execute */
	public function execute() {

		// create the results array
		$shorturls = array();

		$result = $this->getResult();

		// return the template for the short URL path
		$result->addValue( null, $this->_moduleName, array ( 'template' => self::getPathTemplate() ) );

		// get the list of pipe-separated ShortUrl codes
		$codesString =
			$this->getMain()->getVal( self::MID . self::PARAM_CODES );

		// if no codes, we're done
		if ( !count( $codesString ) ) {
			return;
		}

		// convert the codes to lower case and split them up
		$codes = explode( '|', strtolower( $codesString ) );

		// remove duplicate codes
		$codes = array_keys( array_flip( $codes ) );

		// fetch from the DB and iterate over the results
		foreach ( $this->_queryDB( $codes ) as $row ) {
			if ( $row->page_id ) {		// only report shorturl entries that are not orphaned
				$code = ApiShortUrl::codeFromId( $row->su_id );
				$title = self::getNamespaceText( $row->page_namespace ) . $row->page_title;
				global $wgArticlePath;
				$shorturls["code_$code"] =
					array(
						'code'    => $code,
						'pageid'  => $row->page_id,
						'title'   => str_replace( '_', ' ', $title ),
						'longpath'=> preg_replace( '/^(.*)$/', $wgArticlePath, $title ),
					);
			} elseif ( self::$_needToWarnAboutOrphanURLs ) {
			  // only do this once ( per load )
				self::$_needToWarnAboutOrphanURLs = false;

				trigger_error(
					'The ShortUrl DB table has orphan references.',
					E_USER_NOTICE );

			}
		}

		// add the result
		$result->addValue( null, $this->_moduleName, array( self::PARAM_CODES => $shorturls, ) );
	}

	/** For parameters and semantics, see ApiBase::getAllowedParams */
	public function getAllowedParams() {
		return array(
			self::PARAM_CODES => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		);
	}

	/** For parameters and semantics, see ApiBase::getParamDescription */
	public function getParamDescription() {
		return array(
			self::PARAM_CODES => array(
				'List of Short URL codes.',
				'Separate values with "|". ( e.g. 1|6|1094|794sa ).',
				)
		);
	}

	/** For parameters and semantics, see ApiBase::getDescription */
	public function getDescription() {
		return array(
			'Returns information about short URLs provided by the ShortUrl extension.',
			'  template: string formatting template for URL paths.',
			'  codes: list of each code and its related information.',
		);
	}

	/** For parameters and semantics, see ApiBase::getExamples */
	public function getExamples() {
		return array(
			'api.php?action=' . self::PARAM_NAME . '&' .
						self::MID . self::PARAM_CODES .
						'=1|6|1094|794sa' =>
				'Fetch information about short URLs with codes "1", "6", "1094" and "794sa"',
			'api.php?action=' . self::PARAM_NAME =>
				'Fetch basic information about short URL configuration',
		);
	}

	/** Get the namespace text from the namespace id number. */
	public static function getNamespaceText( $index ) {
		if ( !$index ) {
			return "";   // there is no namespace
		}

		// first, try for the canonical name
		$text = MWNamespace::getCanonicalName( $index );

		// next, try for a custom name
		if ( !$text ) {
			if ( array_key_exists( $index, $wgExtraNamespaces ) ) {
				$text = $wgExtraNamespaces[$index];
			}

			// if the namespace isn't defined, just fabricate one with its id number
			if ( !$text ) {
				$text = "UNDEFINED_NS_$index";
			}

		}
		return "$text:";
	}

	/** get a URL path template */
	public static function getPathTemplate( $shortPath = true ) {
		global $wgShortUrlTemplate, $wgCanonicalNamespaceNames, $wgArticlePath;

		// use the configured short url template, if specified
		if ( $shortPath && $wgShortUrlTemplate ) {
			$pathTemplate = $wgShortUrlTemplate;
		} else {
			$titleText = $wgCanonicalNamespaceNames[NS_SPECIAL] . ':' .
				SpecialPage::getTitleFor( 'ShortUrl', '$1' )->mUrlform;
			$pathTemplate = preg_replace( '/^(.*)$/', $wgArticlePath, $titleText );
		}
		return $pathTemplate;
	}

	/**
	 * Get a ShortUrl code from its ShortUrl numeric ID
	 *
	 * @param   int    $id the ShortUrl numeric ID ( su_id )
	 * @returns        string that contains the ShortUrl code
	 */
	public static function codeFromId( $id ) {
	  return base_convert( $id, 10, 36 );
	}

	/**
	 * Get a ShortUrl numeric ID from its ShortUrl code
	 *
	 * @param   string $code the ShortUrl code to convert
	 * @returns        int that contains the ShortUrl numeric ID ( su_id )
	 */
	public static function idFromCode( $code ) {
	  return base_convert( $code, 36, 10 );
	}

	/**
	 * Query the ShortUrl database for details about specified ShortUrl codes
	 *
	 * @param   array short URL codes to query
	 * @returns       ResultWrapper|bool returns result or @c false on error.
	 */
	private function _queryDB( $codes ) {
		// convert codes to ids (this also scrubs the input)
		$ids = array_map( 'ApiShortUrl::idFromCode', $codes );

		// remove duplicate ids (e.g. can result when codes have leading zeros)
		$ids = array_keys( array_flip( $ids ) );

		// build the DB query
		$dbTables = array( 'shorturls', 'page' );
		$dbFields = array( 'su_id', 'page_id', 'page_title', 'page_namespace' );
		$dbConds  = array( 'su_id' => $ids );
		$dbOptions = array();
		$dbJoinConds = array( 'page' => array(
			'LEFT OUTER JOIN',
			array(
				'page_namespace = su_namespace',
				'page_title = su_title',
			),
		) );

		// fetch the select query result from the DB and return it
		return $this->getDB()->select(
			$dbTables,
			$dbFields,
			$dbConds,
			__METHOD__,
			$dbOptions,
			$dbJoinConds
		);

	}

	/** the name of our module, as provided to our constructor */
	private $_moduleName;

	/** flag to prevent repeat warnings of missing ShortUrl extension during the same request */
	private static $_allowMissingShortUrlExtensionNotice = true;

	/** flag to prevent repeat warnings of orphan short URLs */
	private static $_needToWarnAboutOrphanURLs = true;

}

/** @}*/
