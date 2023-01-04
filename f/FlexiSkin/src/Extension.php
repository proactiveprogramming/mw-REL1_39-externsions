<?php

namespace MediaWiki\Extension\FlexiSkin;

use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\CommonUserInterface\LessVars;
use RequestContext;

class Extension {

	/**
	 * @param User $user
	 * @return void
	 */
	public static function onUserLoadAfterLoadFromSession( $user ) {
		$skinname = RequestContext::getMain()->getSkin()->getSkinName();
		static::loadFlexiSkin( $skinname );
	}

	/**
	 * @return void
	 */
	public static function overrideLessVars() {
		if ( MW_ENTRY_POINT !== 'load' ) {
			return;
		}
		$skinname = RequestContext::getMain()->getRequest()->getVal( 'skin', '' );
		static::loadFlexiSkin( $skinname );
	}

	/**
	 *
	 * @param string $skinname
	 * @return void
	 */
	private static function loadFlexiSkin( $skinname ) {
		/** @var IFlexiSkinManager $flexiSkinManager */
		$flexiSkinManager = MediaWikiServices::getInstance()->get( 'FlexiSkinManager' );
		$active = $flexiSkinManager->getActive( $skinname );
		if ( !$active instanceof IFlexiSkin ) {
			return;
		}

		$vars = [];
		$config = $active->getConfig();
		/**
		 * @var string $pluginKey
		 * @var IPlugin $plugin
		 */
		foreach ( $flexiSkinManager->getPlugins() as $pluginKey => $plugin ) {
			$plugin->adaptConfiguration( $config );
			$vars = array_merge( $vars, $plugin->getLessVars( $active ) );
		}

		static::setGlobalImages( $config, 'logo', 'wgLogo' );
		static::setGlobalImages( $config, 'favicon', 'wgFavicon' );
		static::applyFreeCSS( $config );

		$lessVars = LessVars::getInstance();
		foreach ( $vars as $var => $value ) {
			$lessVars->setVar( $var, $value );
		}
		$hookContainer = MediaWikiServices::getInstance()->getHookContainer();
		$hookContainer->run( 'FlexiSkinAfterLoad', [ $active ] );
	}

	/**
	 * @param array $config
	 * @param string $skinVariable
	 * @param string $globalVariable
	 */
	protected static function setGlobalImages( array $config, $skinVariable, $globalVariable ) {
		$url = $config['images'][$skinVariable]['url'] ?? null;
		if ( $url ) {
			$GLOBALS[$globalVariable] = $url;
		}
	}

	/**
	 * Apply the CSS styles
	 *
	 * @param array $config
	 */
	protected static function applyFreeCSS( $config ) {
		if ( !isset( $config['free_css']['css'] ) ) {
			return;
		}

		$GLOBALS['wgHooks']['BeforePageDisplay'][] = static function ( \OutputPage $out, \Skin $skin ) use ( $config ) {
			$style = $config['free_css']['css'];
			$style = str_replace( "\n", '', $style );
			$style = preg_replace( '/\s/', ' ', $style );
			if ( empty( $style ) ) {
				return true;
			}

			$out->addInlineStyle( $style );

			return true;
		};
	}
}
