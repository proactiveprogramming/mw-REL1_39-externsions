<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CoordinateConversion' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['CoordinateConversion'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['CoordinateConversionAlias'] = __DIR__ . '/CoordinateConversion.i18n.alias.php';
	$wgExtensionMessagesFiles['CoordinateConversionMagic'] = __DIR__ . '/CoordinateConversion.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for CoordinateConversion extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the CoordinateConversion extension requires MediaWiki 1.25+' );
}
