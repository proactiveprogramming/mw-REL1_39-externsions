<?php
/**
 * OnlyRecentRecentChanges extension for MediaWiki
 *
 * The behaviour of the recent changes view is changed
 * so that any changed article is only listed once.
 * Older changes are not listed any longer.
 *
 * Manual and homepage:
 *
 * https://www.mediawiki.org/wiki/Extension:OnlyRecentRecentChanges
 *
 * @file
 * @ingroup Extensions
 * @author Thomas Gries
 * @license GPL-2.0-only
 * @license MIT
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 */

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'OnlyRecentRecentChanges' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['OnlyRecentRecentChanges'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for the OnlyRecentRecentChanges extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the OnlyRecentRecentChanges extension requires MediaWiki 1.35+' );
}
