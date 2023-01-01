<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'UserHistory' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['UserHistory'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['UserHistoryAlias'] = __DIR__ . '/UserHistory.i18n.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for UserHistory extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the UserHistory extension requires MediaWiki 1.25+' );
}
