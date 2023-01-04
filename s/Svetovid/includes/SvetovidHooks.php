<?php

use MediaWiki\MediaWikiServices;

class SvetovidHooks {
	const SV_PREF_SIDEBAR_LINK = 'svetovid-show-link-in-sidebar';

	/**
	 * Registers this extension's user preferences
	 *
	 * @param $user
	 * @param $preferences
	 */
	public static function onGetPreferences( $user, &$preferences ) {
		$preferences[self::SV_PREF_SIDEBAR_LINK] = [
			'type' => 'check',
			'label-message' => 'svetovid-pref-sidebar-link',
			'section' => 'rendering/advancedbacklinks',
		];
	}

	/**
	 * Adds a link in the toolbox to Special:LinkCreator
	 *
	 * @param Skin $skin
	 * @param $bar
	 *
	 * @throws MWException
	 */
	public static function onSidebarBeforeOutput( Skin $skin, &$bar ) {
		$optionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		if ( !$optionsLookup->getBoolOption( $skin->getUser(), self::SV_PREF_SIDEBAR_LINK ) ) {
			return;
		}

		$title = $skin->getTitle();
		if ( !$title ) {
			return;
		}

		if ( $title->getNamespace() < 0 ) {
			$subpage = $title->getText();
			$ix = strpos( $subpage, '/' );
			if ( $ix === false ) {
				return;
			}

			$subpage = substr( $subpage, $ix + 1 );
			if ( $subpage == $title->getText() ) {
				return;
			}

			$title = Title::newFromText( $subpage );
			if ( !$title || $title->getNamespace() < 0 ) {
				return;
			}
		}

		if ( !$title->exists() ) {
			return;
		}

		$bar['TOOLBOX'][] = [
			'text' => $skin->msg( 'linkcreator' ),
			'href' => SpecialPage::getTitleFor( 'LinkCreator', $title->getPrefixedText() )->getFullURL()
		];
	}
}
