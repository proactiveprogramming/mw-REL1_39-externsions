<?php
/**
	JobSchEd - Job Schedule Edit

	Needed external modules:
	* JSWikiGantt ver. 0.3.0 or higher (includes date-functions.js)
	* sftJSmsg ver 0.3.0 or higher
	* jquery.ui that contains datepicker (please note that provided CSS was modified; see: lib/datepicker/css/ui-lightness/jquery-ui.custom.css)
 
    Copyright:  ©2010-2011 Maciej Jaros (pl:User:Nux, en:User:EcceNux)
 
	To activate this extension, add the following into your LocalSettings.php file:
	require_once("$IP/extensions/JobSchEd/JobSchEd.php");
	OR
	you could also simply add this script to your wiki: edit_calend.modules.mini.js
	
	@ingroup Extensions
	@author Maciej Jaros <egil@wp.pl>
	@license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
*/
 
/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

//
// Extension credits that will show up on Special:Version
//
$wgExtensionCredits['parserhook'][] = array(
	'path'         => __FILE__,
	'name'         => 'JobSchEd',
	'version'      => '0.10.3',
	'author'       => 'Maciej Jaros', 
	'url'          => 'http://www.mediawiki.org/wiki/Extension:JobSchEd',
	'description'  => ''
		." This extension edits ''jsgantt'' tag contents to create specific diagrams of urlopy and stuff :-)."
);

//
// Absolute path
//
$wgJobSchEdDir = rtrim(dirname(__FILE__), "/\ ");
$wgJobSchEdScriptDir = "{$wgScriptPath}/extensions/JobSchEd";

//
// Configuration file
//
//require_once ("{$wgJobSchEdDir}/JobSchEd.config.php");

//
// Class setup
//
$wgAutoloadClasses['ecSimpleJSLoader'] = "{$wgJobSchEdDir}/JobSchEd.loader.php";

//
// add hook setup and init class/object
//
$wgHooks['BeforePageDisplay'][] = 'efJobSchEdSetup';
function efJobSchEdSetup($wgOut)
{
	global $wgJobSchEdDir;

	$oLoader = new ecSimpleJSLoader($wgJobSchEdDir);
	
	// Note! This name should be the same as in other extensions
	//! @todo Make this optional
	$wgOut->addHeadItem('sftJSmsg' , Html::linkedScript( efJobSchEdgetCSSJSLink("lib/sftJSmsg.js") ) );

	// Note! This name should be the same as in other extensions
	//! @todo Make this optional
	$wgOut->addHeadItem('jquery.ui.css', Html::linkedStyle ( efJobSchEdgetCSSJSLink("lib/datepicker/css/ui-lightness/jquery-ui.custom.css") ) );
	$wgOut->addHeadItem('jquery.ui.js' , Html::linkedScript( efJobSchEdgetCSSJSLink("lib/datepicker/js/jquery-ui.custom.min.js") ) );
	$wgOut->addHeadItem('jquery.ui.datepicker-pl.js' , Html::linkedScript( efJobSchEdgetCSSJSLink("lib/datepicker/js/jquery.ui.datepicker-pl.js") ) );

	// "modules"
	$strMiniModulesFile = $oLoader->createMiniModules(array(
		'_core',
		'form_cr',
		'parsing',
		'wikicodebuilder',
		'msgs_mod_p',
		'msgs_mod_t',
		'msgs_list_p',
		'msgs_list_t',
	));
	$wgOut->addHeadItem('JobSchEdJSmini', Html::linkedScript(efJobSchEdgetCSSJSLink($strMiniModulesFile)));
	
	// Note! This name should be the same as in JSWikiGantt extension
	$wgOut->addHeadItem('jsganttDateJS' , Html::linkedScript( efJobSchEdgetCSSJSLink("date-functions.js") ) );
	
	return true;
}

$wgJobSchEdtScriptVersion = 9;
function efJobSchEdgetCSSJSLink($strFileName)
{
	global $wgJobSchEdtScriptVersion, $wgJobSchEdScriptDir;
	
	return "{$wgJobSchEdScriptDir}/{$strFileName}?{$wgJobSchEdtScriptVersion}";
}