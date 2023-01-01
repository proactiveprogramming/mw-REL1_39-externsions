<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'EPSG' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['EPSG'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['EPSGAlias'] = __DIR__ . '/EPSG.i18n.alias.php';
	$wgExtensionMessagesFiles['EPSGMagic'] = __DIR__ . '/EPSG.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for EPSG extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the EPSG extension requires MediaWiki 1.25+' );
}
