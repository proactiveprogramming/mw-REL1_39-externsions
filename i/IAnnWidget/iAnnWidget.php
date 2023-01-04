<?php

// Protect against register_globals vulnerabilities.
// This line must be present before any global variable is referenced.
if( !defined('MEDIAWIKI') ){
	echo("This is an extension to the MediaWiki package and cannot be run standalone.\n");
	die(-1);
}

// Extension credits that will show up on Special:Version    
$wgExtensionCredits['parserhook'][] = array(
	'path'           => __FILE__,
	'name'           => 'iAnnWidget',
	'version'        => '0.1',
	'author'         => 'Flavien Bossiaux', 
	'url'            => 'https://github.com/BFlavien/iann-widget-for-mediawiki',
	'descriptionmsg' => 'iannwidget-descriptionmsg',
	'description'    => 'iannwidget-description'
);

// Load the main class of the extension and his i18n
$wgAutoloadClasses['iAnnWidget']        = dirname( __FILE__ ) . "/iAnnWidget.body.php";
$wgExtensionMessagesFiles['iAnnWidget'] = dirname( __FILE__ ) . '/iAnnWidget.i18n.php';

$wgResourceModules['iAnnWidget'] = array(	
	'localBasePath' => dirname( __FILE__ ),
	'remoteExtPath' => 'iAnnWidget'
);

$wgHooks['ParserFirstCallInit'][] = 'wfiAnnWidgetSetup';

// Hook our callback function into the parser
function wfiAnnWidgetSetup( Parser $parser ) {
	$iAnnWidget = new iAnnWidget;
	
	// When the parser sees the <sample> tag, it executes 
	// the 'createWidget' function of '$iAnnWidget' previously created
	$parser->setHook( 'iannwidget', array($iAnnWidget, 'createWidget') );
	
	// Always return true from this function. The return value does not denote
	// success or otherwise have meaning - it just must always be true.
	return true;
}
