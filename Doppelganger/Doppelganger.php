<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Doppelganger', __DIR__ . '/extension.json' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Doppelganger'] = __DIR__ . '/i18n';
	// $wgExtensionMessagesFiles['SpecMagic'] = __DIR__ . '/Doppelganger.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for Doppelganger extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the Doppelganger extension requires MediaWiki 1.25+' );
}