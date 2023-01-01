<?php
/** 
* @addtogroup Extensions 
*/
// Check environment
if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
	die( -1 );
}

/* Configuration */

// Credits
$wgExtensionCredits['other'][] = array (
	'path'=> __FILE__ ,
	'name'=>'VigLink',
	'url'=>'http://www.mediawiki.org/wiki/Extension:VigLink',
	'description'=>'Earn money with [http://www.viglink.com/?vgref=1051863 VigLink].',
//	'descriptionmsg' => 'VigLink-desc',
	'author'=>'[http://www.daschmedia.de DaSch]',
	'version'=>'0.9',
);

$dir = dirname( __FILE__ ) . '/';

//$wgExtensionMessagesFiles['VigLink']      = $dir . 'VigLink.i18n.php';
//$wgExtensionMessagesFiles['VigLinkAlias'] = $dir . 'VigLink.alias.php';

$wgAutoloadClasses[ 'VigLinkHooks' ]    = $dir . 'VigLink.hooks.php';
$wgHooks['SkinAfterBottomScripts'][] = 'VigLinkHooks::setupVigLink';