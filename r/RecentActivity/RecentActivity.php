<?php
/**
 * RecentActivityMod extension - Adds parser functions for listing recently created and edited articles
 * A for of RecentActivity extension http://www.mediawiki.org/wiki/Extension:RecentActivity
 * See https://gitlab.com/lucamauri/recentactivity for installation and usage details.
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley, Luca Mauri
 * @license GNU General Public Licence 2.0 or later
 */
if (!defined('MEDIAWIKI')) {
	die('Not an entry point.');
}

use MediaWiki\MediaWikiServices;

class RecentActivity
{
	function onParserFirstCallInit( Parser $parser )
	{
		global $wgParser;
		#$wgParser->setFunctionHook('RecentActivity', array(self::class, 'expandMagic'), SFH_NO_HASH);
		$parser->setFunctionHook('RecentActivity', array(self::class, 'expandMagic'), SFH_NO_HASH);
		wfDebugLog('RecentActivity', '[RecentActivity]End of -FirstCall-');
	}

	function expandMagic(Parser $parser)
	{
		global $RecentActivityExclusionsCat;
		global $config;

		wfDebugLog('RecentActivity', '[RecentActivity]Start of -Expand-');
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('RecentActivity');
		$RecentActivityExclusionsCat = $config->get('RAExclusionsCat');

		if ($RecentActivityExclusionsCat[0] == null or $RecentActivityExclusionsCat == '') {
			$RecentActivityExclusionsCat = 'Excluded from RecentActivity';
		}	

		$dbr = wfGetDB(DB_REPLICA);
		$rev = $dbr->tableName( 'revision' );
		$cl  = $dbr->tableName( 'categorylinks' );

		// Populate $argv with both named and numeric parameters
		$argv = array();
		foreach( func_get_args() as $arg) if( !is_object( $arg ) ) {
			if( preg_match( '/^(.+?)\\s*=\\s*(.+)$/', $arg, $match ) ) $argv[$match[1]] = $match[2]; else $argv[] = $arg;
		}
		$type   = isset( $argv['type'] )   ? strtolower( $argv['type'] ) : '';
		$user   = isset( $argv['user'] )   ? $argv['user']   : false;
		$count  = isset( $argv['count'] )  ? $argv['count']  : 5;
		$format = isset( $argv['format'] ) ? $argv['format'] : '*';

		// Build an SQL condition for the exclusions category
		$conds = array( "rev_minor_edit = 0" );
		$cat = $dbr->addQuotes( Title::newFromText( $RecentActivityExclusionsCat, NS_CATEGORY )->getDBkey() );
		$res = $dbr->select( $cl, 'cl_from', "cl_to = $cat", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
		while( $row = $dbr->fetchRow( $res ) ) $conds[] = "rev_page != $row[0]";

		// Build the list
		$items = array();
		switch( $type ) {

			case 'edits':
				if( $user ) $conds[] = 'rev_user_text = ' . $dbr->addQuotes($user);
				$res = $dbr->select(
					$rev,
					'distinct rev_page',
					$conds,
					__METHOD__,
					array('ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => $count)
				);
				while($row = $dbr->fetchRow($res)) {
					$title = Title::newFromId($row['rev_page']);
					if(is_object($title)) {
						$page = $title->getPrefixedText();
						$anchor = $title->getText();
						$items[] = $format . "[[:$page|$anchor]]";
					}
				}
				$dbr->freeResult($res);
			break;

			case 'new':
				$tbl = $dbr->tableName( 'revision' );
				if( $user ) $conds[] = 'rev_user_text = ' . $dbr->addQuotes( $user );
				$res = $dbr->select(
					$rev,
					'rev_page, MIN(rev_id) as minid',
					$conds,
					__METHOD__,
					array( 'GROUP BY' => 'rev_page', 'ORDER BY' => 'minid DESC', 'LIMIT' => $count )
				);
				while( $row = $dbr->fetchRow( $res ) ) {
					$title = Title::newFromId( $row['rev_page'] );
					if( is_object( $title ) ) {
						$page = $title->getPrefixedText();
						$anchor = $title->getText();
						$items[] = $format . "[[:$page|$anchor]]";
					}
				}
				$dbr->freeResult( $res );
			break;

			default: $items[] = 'Bad activity type specified!';
		}

		return join( "\n", $items );
	}
}
