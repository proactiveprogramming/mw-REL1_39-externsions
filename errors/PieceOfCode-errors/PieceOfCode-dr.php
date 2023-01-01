<?php
/**
 * @file PieceOfCode-dr.php
 *
 * Subversion
 *	- ID:  $Id$
 *	- URL: $URL$
 *
 * @copyright 2010 Alejandro Darío Simi
 * @license GPL
 * @author Alejandro Darío Simi
 * @date 2010-08-28
 */
$wgPieceOfCodeExtensionSysDir = dirname(__FILE__);
$wgPieceOfCodeExtensionWebDir = $wgScriptPath.'/extensions/'.basename(dirname(__FILE__));

require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'config.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCVersionManager.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCHistoryManager.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCFlags.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCErrorsHolder.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCSVNConnections.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCStoredCodes.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCStats.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'includes'.DIRECTORY_SEPARATOR.'POCCodeExtractor.php');
require_once($wgPieceOfCodeExtensionSysDir.DIRECTORY_SEPARATOR.'PieceOfCode-dr.body.php');

/**
 * Register function.
 */
function PieceOfCode_Hooker() {
	PieceOfCode::Instance();
}
function PieceOfCode_HeadHooker(&$out, &$sk) {
	global	$wgPieceOfCodeConfig;

	if($wgPieceOfCodeConfig['autocss']) {
		global	$wgPieceOfCodeExtensionWebDir;

		$script = $wgPieceOfCodeExtensionWebDir.'/includes/style.css';
		$out->addScript('<link type="text/css" rel="stylesheet" href="'.$script.'"/>');
	}

	return true;
}

if(!defined('MEDIAWIKI')) {
	die();
} else {
	/**
	 * MediaWiki Extension hooks Setter.
	 */
	$wgExtensionFunctions[]                  = 'PieceOfCode_Hooker';
	$wgHooks['BeforePageDisplay'][]          = 'PieceOfCode_HeadHooker';
	$wgExtensionMessagesFiles['PieceOfCode'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'PieceOfCode-dr.i18n.php';

	$wgAutoloadClasses  ['PieceOfCode'] = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'PieceOfCode-dr.body.php';
	$wgSpecialPages     ['PieceOfCode'] = 'PieceOfCode';
	$wgSpecialPageGroups['PieceOfCode'] = 'other';

	/**
	 * MediaWiki Extension Description.
	 */
	$wgExtensionCredits['parserhook'][] = array(
		'name'            => PieceOfCode::Property('name'),
		'version'         => PieceOfCode::Property('version'),
		'date'            => PieceOfCode::Property('date'),
		'description'     => PieceOfCode::Property('description'),
		'descriptionmsg'  => PieceOfCode::Property('descriptionmsg'),
		'author'          => PieceOfCode::Property('author'),
		'url'             => PieceOfCode::Property('url'),
		'svn-date'        => PieceOfCode::Property('svn-date'),
		'svn-revision'    => PieceOfCode::Property('svn-revision'),
	);
	$wgExtensionCredits['specialpage'][] = array(
		'name'            => PieceOfCode::Property('name'),
		'version'         => PieceOfCode::Property('version'),
		'date'            => PieceOfCode::Property('date'),
		'description'     => PieceOfCode::Property('sinfo-description'),
		'descriptionmsg'  => PieceOfCode::Property('sinfo-descriptionmsg'),
		'author'          => PieceOfCode::Property('author'),
		'url'             => PieceOfCode::Property('url'),
	);
}
?>