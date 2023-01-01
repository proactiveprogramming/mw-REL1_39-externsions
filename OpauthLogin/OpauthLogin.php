<?php
/**
 * Initialization file for the OpauthLogin extension.
 *
 * @file OpauthLogin.php
 * @ingroup OpauthLogin
 *
 * @licence GNU GPL v3
 * @author Wikivote llc < http://wikivote.ru >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.17', '<' ) ) {
	die( '<b>Error:</b> This version of OpauthLogin requires MediaWiki 1.17 or above.' );
}

if( !class_exists('Opauth') ) {
    die( '<b>Error:</b>: OpauthLogin extension required Opauth extension to be installed. ' .
        'Please follow <a href="https://www.mediawiki.org/wiki/Extension:Opauth">this link</a> to install Opauth extension.' );
}

global $wgOpauthLogin;
$wgOpauthLoginDir = dirname( __FILE__ );

// Display login buttons at sign-in page
$wgOpauthLoginEnableButtons = false;
// Allow users to manually enter their username
$wgOpauthLoginRequestUsername = false;

/* Credits page */
$wgExtensionCredits['other'][] = array(
    'path' => __FILE__,
    'name' => 'OpauthLogin',
    'version' => '0.1',
    'author' => 'Jon Anderton',
    'url' => '',
    'descriptionmsg' => 'OpauthLogin-credits',
);

/* Resource modules */
$wgResourceModules['ext.OpauthLogin.main'] = array(
    'localBasePath' => dirname( __FILE__ ) . '/modules/',
    'remoteExtPath' => 'OpauthLogin/modules/',
    'group' => 'ext.OpauthLogin',
    'scripts' => array('js/script.js'),
    'styles' => array('css/style.css')
);

/* Message Files */
$wgExtensionMessagesFiles['OpauthLogin'] = dirname( __FILE__ ) . '/OpauthLogin.i18n.php';

/* Autoload classes */
$wgAutoloadClasses['OpauthLogin'] = dirname( __FILE__ ) . '/OpauthLogin.class.php';
$wgAutoloadClasses['OpauthLoginHooks'] = dirname( __FILE__ ) . '/OpauthLogin.hooks.php';
$wgAutoloadClasses['SpecialOpauthLoginInfo'] = dirname( __FILE__ ) . '/specials/OpauthLoginInfo.php';

/* ORM,MODELS */
#$wgAutoloadClasses['OpauthLogin_Model_'] = dirname( __FILE__ ) . '/includes/OpauthLogin_Model_.php';

/* ORM,PAGES */
#$wgAutoloadClasses['OpauthLoginSpecial'] = dirname( __FILE__ ) . '/pages/OpauthLoginSpecial/OpauthLoginSpecial.php';

/* Rights */
#$wgAvailableRights[] = 'example_rights';

/* Permissions */
#$wgGroupPermissions['sysop']['example_rights'] = true;

/* Special Pages */
$wgSpecialPages['OpauthLoginInfo'] = 'SpecialOpauthLoginInfo';

/* Hooks */
$wgHooks['OpauthUserAuthorized'][] = 'OpauthLoginHooks::onOpauthUserAuthorized';
$wgHooks['UserLoadFromSession'][] = 'OpauthLoginHooks::onUserLoadFromSession';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'OpauthLoginHooks::onLoadExtensionSchemaUpdates';
$wgHooks['UserCreateForm'][] = 'OpauthLoginHooks::onUserCreateForm';
$wgHooks['UserLoginForm'][] = 'OpauthLoginHooks::onUserLoginForm';