<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Jsoner' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Jsoner'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['JsonerMagic'] = __DIR__ . '/Jsoner.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for Jsoner extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Jsoner extension requires MediaWiki 1.25+' );
}
