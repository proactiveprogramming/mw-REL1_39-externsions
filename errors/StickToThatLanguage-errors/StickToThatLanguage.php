<?php

namespace STTLanguage;

use MediaWiki\MediaWikiServices;

/**
 * Initialization file for the 'Stick to That Language' extension.
 *
 * Documentation:  https://www.mediawiki.org/wiki/Extension:Stick_to_That_Language
 * Support:        https://www.mediawiki.org/wiki/Extension_talk:Stick_to_That_Language
 * Source code:    https://gerrit.wikimedia.org/r/gitweb?p=mediawiki/extensions/WikidataRepo.git
 *
 * TODO:
 * - getting rid of the overall hackiness of this extension, especially the part where the output buffer is hacked to
 *   get the 'uselang' parameter into forms. For this to be fixed, non-trivial MW core changes need to be done or the
 *   overall concept of how this extension works has to be changed.
 *
 * @file StickToThatLanguage.php
 * @ingroup STTLanguage
 *
 * @licence GNU GPL v2+
 * @author: Daniel Werner < daniel.werner@wikimedia.de >
 */

if( ! defined( 'MEDIAWIKI' ) ) { die(); }

$wgExtensionCredits['other'][] = [
	'path'           => __FILE__,
	'name'           => 'Stick to That Language',
	'descriptionmsg' => 'sticktothatlanguage-desc',
	'version'        => Ext::VERSION,
	'url'            => 'https://www.mediawiki.org/wiki/Extension:Stick_to_That_Language',
	'author'         => [ '[https://www.mediawiki.org/wiki/User:Danwe Daniel Werner]' ],
	'license-name'   => 'GPL-2.0-or-later',
];

// i18n
$wgMessagesDirs['StickToThatLanguage'] = __DIR__ . '/i18n';

// Autoloading
$wgAutoloadClasses['STTLanguage\Hooks']   = __DIR__ . '/StickToThatLanguage.hooks.php';

// hooks registration:
$wgHooks['UnitTestsList'][]                    = 'STTLanguage\Hooks::registerUnitTests';
$wgHooks['GetPreferences'][]                   = 'STTLanguage\Hooks::onGetPreferences';
$wgHooks['UserGetDefaultOptions'][]            = 'STTLanguage\Hooks::onUserGetDefaultOptions';
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = 'STTLanguage\Hooks::onSkinTemplateOutputPageBeforeExec';

if( !$wgCommandLineMode ) {
	// We don't want to hook in these places when running tests. This is because core tests will fail since they
	// simply do not consider extensions to change the output of the tested functions.
	$wgHooks['BeforePageDisplay'][]                = 'STTLanguage\Hooks::onBeforePageDisplay';
	$wgHooks['GetLocalURL::Internal'][]            = 'STTLanguage\Hooks::onGetLocalUrlInternally';
	$wgHooks['LinkBegin'][]                        = 'STTLanguage\Hooks::onLinkBegin';
	$wgHooks['AfterFinalPageOutput'][]             = 'STTLanguage\Hooks::onAfterFinalPageOutput';
}

// Resource Loader Module:
$wgResourceModules['sticktothatlanguage'] = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'StickToThatLanguage',
	'scripts' => [
		'resources/StickToThatLanguage.js'
	],
	'styles' => [
		'resources/StickToThatLanguage.css'
	],
	'messages' => [
		'sttl-languages-more-link'
	],
	'dependencies' => [
		'jquery.ui'
	],
	'group' => 'ext.sticktothatlanguage',
];

// Include settings:
require_once __DIR__ . '/StickToThatLanguage.settings.php';


/**
 * 'Stick to That Language' extension class with basic extension information and functions which can be used
 * by other extensions.
 *
 * @since 0.1
 */
class Ext {
	/**
	 * Version of the extension.
	 *
	 * @since 0.1
	 *
	 * @var string
	 */
	const VERSION = '0.2.0';

	/**
	 * Returns the list of languages the user has set as preferred languages in the preferences.
	 * This also includes the users main language always.
	 *
	 * @since 0.1
	 *
	 * @param \User $user
	 * @return array with language codes as values
	 */
	public static function getUserLanguageCodes( $user ) {
		$languageCodes = [];

		$services = MediaWikiServices::getInstance();
		if ( method_exists( $services, 'getUserOptionsManager' ) ) {
			// MW 1.35 +
			$userOptionsManager = $services->getUserOptionsManager();
			// check for all languages whether they are selected as users preferred language:
			foreach( \Language::fetchLanguageNames() as $code => $name ) {
				if( $userOptionsManager->getOption( $user, "sttl-languages-$code" ) ) {
					$languageCodes[] = $code;
				}
			}
			// make sure users overall language is represented within:
			$userLang = $userOptionsManager->getOption( $user, 'language' );
		} else {
			// check for all languages whether they are selected as users preferred language:
			foreach( \Language::fetchLanguageNames() as $code => $name ) {
				if( $user->getOption( "sttl-languages-$code" ) ) {
					$languageCodes[] = $code;
				}
			}
			// make sure users overall language is represented within:
			$userLang = $user->getOption( 'language' );
		}
		if( !in_array( $userLang, $languageCodes ) ) {
			$languageCodes[] = $userLang;
		}

		return $languageCodes;
	}
}
