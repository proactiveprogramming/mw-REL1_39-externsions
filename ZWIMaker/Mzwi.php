<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Mzwi' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Mzwi'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['Mzwi'] = __DIR__ . '/PhpTags.i18n.magic.php';
//	wfWarn(
//		'Deprecated PHP entry point used for PhpTags extension. ' .
//		'Please use wfLoadExtension instead, ' .
//		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
//	);
	return;
} else {
	die( 'This version of the Mzwi extension requires MediaWiki 1.36+' );
}
