<?php

if ( function_exists( 'wfLoadExtension' ) ) {

    if ( !defined( 'SMW_VERSION' ) ) {
        die( '<b>Error:</b> This version of Nodos requires <a href="http://semantic-mediawiki.org/wiki/Semantic_MediaWiki">Semantic MediaWiki</a> installed.<br />' );
    }

    if ( version_compare( SMW_VERSION, '1.9', '<' ) ) {
        die( '<b>Error:</b> This version of Nodos requires Semantic MediaWiki 1.9 or above.' );
    }
	wfLoadExtension( 'nodos' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['nodos'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['nodosAlias'] = __DIR__ . '/nodos.i18n.alias.php';
	$wgExtensionMessagesFiles['nodosMagic'] = __DIR__ . '/nodos.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for nodos extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
    return true;
} else {
	die( 'This version of the nodos extension requires MediaWiki 1.25+' );
}
