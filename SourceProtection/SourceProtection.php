<?php
/**
 * The sourceProtection extension to MediaWiki allows to restrict the edit
 * access to user pages.
 *
 * @version 1.1.1 2017-10-09
 *
 * @author Sen-Sai
 *
 * @copyright Copyright (C) 2017, Sen-Sai
 *
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */


 if ( function_exists( 'wfLoadExtension' ) ) {
 	wfLoadExtension( 'SourceProtection' );
 	// Keep i18n globals so mergeMessageFileList.php doesn't break
 	$wgMessagesDirs['SourceProtection'] = __DIR__ . '/i18n';
 	wfWarn(
 		'Deprecated PHP entry point used for SourceProtection extension. Please use wfLoadExtension ' .
 		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
 	);
 	return true;
 } else {
 	die( 'This version of the SourceProtection extension requires MediaWiki 1.24+' );
 }
