<?php
/**
 * Character Escapes
 * Character Escapes Mediawiki Settings
 *
 * @author		David M. Sledge
 * @package		Character Escapes
 * @license		GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 * @link		http://www.mediawiki.org/wiki/Extension:Character_Escapes
 *
 **/

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CharacterEscapes' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['CharacterEscapes'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for Character Escapes extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the Character Escapes extension requires MediaWiki 1.25+' );
}
