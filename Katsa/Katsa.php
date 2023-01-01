<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Katsa', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Katsa'] = __DIR__ . '/i18n';
	// $wgExtensionMessagesFiles['SpecMagic'] = __DIR__ . '/Katsa.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for Katsa extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Katsa extension requires MediaWiki 1.25+' );
}