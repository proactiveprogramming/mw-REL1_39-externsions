<?php
/**
 * TitlesAndTargets
 *
 * Adds two parser functions to identify page titles
 *
 * @link https://www.mediawiki.org/wiki/Extension:TitlesAndTargets
 *
 * @author WGK.derdicke <wgk.derdicke@web.de>
 * @authorlink  https://www.mediawiki.org/wiki/User:Wgkderdicke
 * @copyright Copyright © 2018 WGK.derdicke (Werner G. Kaukerat).
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */
 
 // Ensure that the script cannot be executed outside of MediaWiki.
 if ( !defined( 'MEDIAWIKI' ) )
	 die( 'This file is an MediaWiki extension and not a valid entry point.' );
 
 // Display extension properties on MediaWiki.
 $wgExtensionCredits[ 'parserhook' ][] = array(
	'path' => __FILE__,
	'name' => 'TitlesAndTargets',
	'author' => array(
		'Werner G. Kaukerat (WGK.derdicke)'
	),
	'url' => 'https://www.mediawiki.org/wiki/Extension:TitlesAndTargets',
	'descriptionmsg' => 'titlesandtargets-desc',
	'license-name' => 'GPL-2.0-or-later',
	'version' => '1.0.0'
);
 
// Register extension messages and other localisation.
$wgMessagesDirs['TitlesAndTargets'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['TitlesAndTargetsMagic'] = __DIR__ . '/TitlesAndTargets.i18n.magic.php';
 
// Register extension hooks
$wgHooks['ParserFirstCallInit'][] = 'efTitlesAndTargets_Setup';
 
// Do the extension's actions.
function efTitlesAndTargets_Setup( &$parser ) {
	$parser->setFunctionHook( 'idTitle', 'getTitleFromPageId' );
	$parser->setFunctionHook( 'rdTarget', 'getTargetFromRedirect' );
	return true;
}

function getTitleFromPageId( $parser, $id = '' ) {

	if ( $id == '' ) {
		return throwError( 'titlesandtargets-err1a' );
	}
	$intid = (int)$id;
	if ( $intid <= 0 ) {
		return throwError( 'titlesandtargets-err1b' );
	}
	$dbr = wfGetDB( DB_MASTER );
	$srow = $dbr->selectRow(
		'page',
		array( 'page_namespace', 'page_title' ),
		array( 'page_id' => $intid ),
		__METHOD__
	);
	if ( $srow === false ) {
		return throwError( 'titlesandtargets-err1c' );
	}
	$t = strtr( Title::makeName( $srow->page_namespace, $srow->page_title ), '_', ' ');
	return $t;

}

function getTargetFromRedirect( $parser, $rdir = '' ) {

	if ( $rdir == '' ) {
		return throwError( 'titlesandtargets-err2a' );
	}
	$title = Title::newFromText( $rdir );
	if ( $title->isKnown() != '1' ) {
		return throwError( 'titlesandtargets-err2b' );
	}
	if ( $title->isRedirect() != '1' ) {
		return throwError( 'titlesandtargets-err2c' );
	}
	$dbr = wfGetDB( DB_MASTER );
	$srow = $dbr->selectRow(
		'redirect',
		array( 'rd_namespace', 'rd_title' ),
		array( 'rd_from' => $title->getArticleID() ),
		__METHOD__
	);
	if ( $srow === false ) {
		return throwError( 'titlesandtargets-err2d' );
	}
	$t = strtr( Title::makeName( $srow->rd_namespace, $srow->rd_title ), '_', ' ');
	return $t;
	
}

function throwError ( $err = '' ) {
	return Html::element( 'strong', array( 'class' => 'error' ), 
			wfMessage( $err )->inContentLanguage()->text()
		);
}
?>