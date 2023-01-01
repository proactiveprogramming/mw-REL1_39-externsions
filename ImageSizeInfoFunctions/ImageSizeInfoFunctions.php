<?php
/**
 * ImageSizeInfoFunctions
 * ImageSizeInfoFunctions Mediawiki Settings
 *
 * @license		GNU GPL v2.0
 * @package		ImageSizeInfoFunctions
 * @link		https://github.com/CurseStaff/ImageSizeInfoFunctions
 *
 **/
 if ( function_exists( 'wfLoadExtension' ) ) {
 	wfLoadExtension( 'ImageSizeInfoFunctions' );
 	// Keep i18n globals so mergeMessageFileList.php doesn't break
 	$wgMessagesDirs['ImageSizeInfoFunctions'] = __DIR__ . '/i18n';
 	wfWarn(
 		'Deprecated PHP entry point used for ImageSizeInfoFunctions extension. Please use wfLoadExtension instead, ' .
 		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
 	);
 	return;
 } else {
 	die( 'This version of the ImageSizeInfoFunctions extension requires MediaWiki 1.25+' );
 }
