<?php
/**
 * An extension that adds a info tab on each page
 *
 * @author Suriyaa Sundararuban <contact@suriyaa.tk>
 * @copyright Copyright © 2017-present, Suriyaa Sundararuban
 * @license https://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 * @website https://www.mediawiki.org/wiki/Extension:Info
 * @version 1.0.0
 */

if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'Info',
    'author' => '[https://about.suriyaa.tk/ Suriyaa Sundararuban]', 
    'url' => 'https://www.mediawiki.org/wiki/Extension:Info',
    'description' => 'Adds a info tab on all normal pages, allowing for quick info displaying',
    'version'  => '2.0.0',
    'license-name' => "GPL-2.0+",
);

$wgMessagesDirs['Info'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['Info'] = __DIR__ . '/Info.i18n.php';

$wgHooks['SkinTemplateNavigation'][] = 'InfoActionExtension::contentHook';

$wgResourceModules['ext.info'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Info',
	'scripts' => [ 'resources/ext.info.js' ],
	'messages' => [ 'info-failed', ],
];

class InfoActionExtension{
	public static function contentHook( $skin, array &$content_actions ) {
		global $wgRequest, $wgUser;
		// Use getRelevantTitle if present so that this will work on some special pages
		$title = method_exists( $skin, 'getRelevantTitle' ) ?
			$skin->getRelevantTitle() : $skin->getTitle();
		if ( $title->getNamespace() !== NS_SPECIAL && $wgUser->isAllowed( 'info' ) ) {
			$skin->getOutput()->addModules( 'ext.info' );
			$action = $wgRequest->getText( 'action' );

			$content_actions['actions']['info'] = array(
				'class' => $action === 'info' ? 'selected' : false,
				'text' => wfMessage( 'info' )->text(),
				'href' => $title->getLocalUrl( 'action=info' )
			);
		}

		return true;
	}
}
