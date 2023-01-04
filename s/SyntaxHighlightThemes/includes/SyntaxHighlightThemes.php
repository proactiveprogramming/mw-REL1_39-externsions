<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Shell\Shell;

class SyntaxHighlightThemes {

	/**
	 * Conditionally register resource loader modules that depends on the
	 * SyntaxHighlight MediaWiki extension.
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public static function onResourceLoaderRegisterModules( $resourceLoader ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SyntaxHighlight' ) ) {
			return;
		}

		$resourceLoader->register( 'ext.syntaxhighlight.themes', [
			'class' => ResourceLoaderSyntaxHighlightThemeModule::class,
			'localBasePath' => __DIR__ . '/../modules',
			'remoteExtPath' => 'SyntaxHighlightThemes/modules',
			'targets' => [ 'desktop', 'mobile' ]
		] );
	}

	/**
	 * Add SyntaxHighlight Theme preference to the user's Special:Preferences page directly underneath skins.
	 *
	 * @param User $user User whose preferences are being modified.
	 * @param array[] &$prefs Preferences description array, to be fed to a HTMLForm object.
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		static $themes = null;

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SyntaxHighlight' ) ) {
			return;
		}

		if ( !$themes ) {
			$themes = require __DIR__ . '/../SyntaxHighlight.themes.php';
		}

		$preferences['syntaxhighlight-theme'] = [
			'type' => 'select',
			'label-message' => 'prefs-sytaxhighlight-theme-label',
			'section' => 'rendering/skin',
			'options' => $themes
		];
	}

	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModuleStyles( 'ext.syntaxhighlight.themes' );
	}

}
