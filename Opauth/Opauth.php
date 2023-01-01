<?php
/**
 * Initialization file for the Opauth extension.
 *
 * @file Opauth.php
 * @ingroup Opauth
 *
 * @licence GNU GPL v3
 * @author Wikivote llc < http://wikivote.ru >
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'Not an entry point.' );
}

if ( version_compare( $wgVersion, '1.17', '<' ) ) {
	die( '<b>Error:</b> This version of Opauth requires MediaWiki 1.17 or above.' );
}

global $wgOpauth;
$wgOpauthDir = dirname( __FILE__ );

/* Credits page */
$wgExtensionCredits['specialpage'][] = array(
    'path' => __FILE__,
    'name' => 'Opauth',
    'version' => '0.1',
    'author' => 'Jon Anderton',
    'url' => '',
    'descriptionmsg' => 'Opauth-credits',
);

/* Resource modules */
/*$wgResourceModules['ext.Opauth.main'] = array(
    'localBasePath' => dirname( __FILE__ ) . '/',
    'remoteExtPath' => 'Opauth/',
    'group' => 'ext.Opauth',
    'scripts' => '',
    'styles' => ''
);*/

/* Message Files */
$wgExtensionMessagesFiles['Opauth'] = dirname( __FILE__ ) . '/Opauth.i18n.php';

/* Autoload classes */
$wgAutoloadClasses['OpauthHelper'] = dirname( __FILE__ ) . '/OpauthHelper.php';
$wgAutoloadClasses['Opauth'] = dirname( __FILE__ ) . '/lib/Opauth/Opauth.php';
$wgAutoloadClasses['OpauthStrategy'] = dirname( __FILE__ ) . '/lib/Opauth/OpauthStrategy.php';

#$wgAutoloadClasses['OpauthHooks'] = dirname( __FILE__ ) . '/Opauth.hooks.php';

/* ORM,MODELS */
#$wgAutoloadClasses['Opauth_Model_'] = dirname( __FILE__ ) . '/includes/Opauth_Model_.php';

/* ORM,PAGES */
$wgAutoloadClasses['OpauthSpecial'] = dirname( __FILE__ ) . '/OpauthSpecial.php';

/* Rights */
#$wgAvailableRights[] = 'example_rights';

/* Permissions */
#$wgGroupPermissions['sysop']['example_rights'] = true;

/* Special Pages */
$wgSpecialPages['Opauth'] = 'OpauthSpecial';

/* Hooks */
#$wgHooks['example_hook'][] = 'OpauthHooks::onExampleHook';

/* Default Opauth config */
$wgOpauthConfig = array(

    /**
     * Path where Opauth is accessed.
     *  - Begins and ends with /
     *  - eg. if Opauth is reached via http://example.org/auth/, path is '/auth/'
     *  - if Opauth is reached via http://auth.example.org/, path is '/'
     */
    'path' => $wgScriptPath.'/index.php/Special:Opauth/',

    /**
     * Callback URL: redirected to after authentication, successful or otherwise
     */
    'callback_url' => '{path}callback.php',

    /**
     * A random string used for signing of $auth response.
     *
     * NOTE: PLEASE CHANGE THIS INTO SOME OTHER RANDOM STRING
     */
    'security_salt' => '',

    /**
     * Strategy
     * Refer to individual strategy's documentation on configuration requirements.
     *
     * eg.
     * 'Strategy' => array(
     *
     *   'Facebook' => array(
     *      'app_id' => 'APP ID',
     *      'app_secret' => 'APP_SECRET'
     *    ),
     *
     * )
     *
     */
    'Strategy' => array(
        // Define strategies and their respective configs here
    ),
);