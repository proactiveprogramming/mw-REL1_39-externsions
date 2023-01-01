<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CopyWatchers' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['CopyWatchers'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['CopyWatchersMagic'] = __DIR__ . '/Magic.php';
	wfWarn(
		'Deprecated PHP entry point used for FooBar extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the CopyWatchers extension requires MediaWiki 1.25+' );
}
