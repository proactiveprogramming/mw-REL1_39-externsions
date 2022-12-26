<?php
/**
 * Namespace Paths
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @copyright Copyright © 2010 – Daniel Friesen
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:NamespacePaths Documentation
 */
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'NamespacePaths' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['NamespacePaths'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for the NamespacePaths extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the NamespacePaths extension requires MediaWiki 1.35+' );
}
