<?php

use MediaWiki\MediaWikiServices;

class SkinCustomiserHooks extends Hooks {

	/**
	 * Hook: BeforePageDisplay
	 * @param OutputPage $out
	 * @param Skin $skin
	 * https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {

		// 1. Add meta data
		global $wgHeadMetaCode, $wgHeadMetaName;

		if ( !empty( $wgHeadMetaCode ) && !empty( $wgHeadMetaName ) ) {
			if ( ( $wgHeadMetaCode !== '<!-- No Head Meta -->' ) && ( $wgHeadMetaName !== '<!-- No Meta Name -->' ) ) {
				$out->addMeta( $wgHeadMetaName, $wgHeadMetaCode );
			}
		}


		// 2. Add skripts
		global $wgHeadScriptCode, $wgHeadScriptName;

		if ( !empty( $wgHeadScriptCode ) && !empty( $wgHeadScriptName ) ) {
			if ( ( $wgHeadScriptCode !== '<!-- No Head Script -->' ) && ( $wgHeadScriptName !== '<!-- No Script Name -->' ) ) {
				$out->addHeadItem( $wgHeadScriptName, $wgHeadScriptCode );
			}
		}


		// 3. Customize skins
		$skinname = $skin->getSkinName();
		$out->addModuleStyles( 'ext.skincustomiser.common' );
		$out->addModuleStyles( 'ext.skincustomiser.mobile' );
		if ( self::isSupported( $skinname ) ) {
			$out->addModuleStyles( 'ext.skincustomiser.' . $skinname );
		} else if ( $skinname !== 'fallback' ) {
			wfLogWarning( 'Skin ' . $skinname . ' not supported by SkinCustomiser.' . "\n" );
		}
	}

	private static function isSupported( $skinname ) {

		// 4. Add another supported skin here:
		$mySkin = 'anotherskin';
		return in_array( $skinname, [ 'cologneblue', 'minerva', 'modern', 'monobook', 'timeless', 'vector', 'vector-2022', $mySkin ] );
	}
}
