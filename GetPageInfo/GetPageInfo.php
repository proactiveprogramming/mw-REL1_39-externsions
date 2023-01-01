<?php


if (!defined('MEDIAWIKI')) {
	die('Not an entry point.');
}

$GLOBALS['wgExtensionCredits']['parserhook'][] = array(
		'path' => __FILE__,
		'name' => "GetPageInfo",
		'description' => "Print some info or metadata of a wiki page",
		'version' => 0.1, 
		'author' => "@toniher",
		'url' => "https://mediawiki.org/wiki/User:Toniher",
);


# Define a setup function
$GLOBALS['wgHooks']['ParserFirstCallInit'][] = 'wfGetPageProp_Setup';
# Add a hook to initialise the magic word
$GLOBALS['wgHooks']['LanguageGetMagic'][] = 'wfGetPageProp_Magic';

# Autoload
$GLOBALS['wgAutoloadClasses']['GetPageInfo'] = __DIR__ . '/GetPageInfo.body.php';

function wfGetPageProp_Setup( &$parser ) {
	$parser->setFunctionHook( 'getTitle', 'GetPageInfo::executeGetTitle', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'getNS', 'GetPageInfo::executeGetNS', SFH_OBJECT_ARGS );
	$parser->setFunctionHook( 'getSummary', 'GetPageInfo::executeGetSummary', SFH_OBJECT_ARGS );
	return true;
}

function wfGetPageProp_Magic( &$magicWords, $langCode ) {
	$magicWords['getTitle'] = array( 0, 'getTitle' );
	$magicWords['getNS'] = array( 0, 'getNS' );
	$magicWords['getSummary'] = array( 0, 'getSummary' );

	return true;
}



