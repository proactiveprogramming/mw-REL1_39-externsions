<?php

namespace BlueSpice\NamespaceCSS;

use MediaWiki\MediaWikiServices;

class Helper {

	/**
	 *
	 * @param int $idx
	 * @return string|false
	 */
	public static function buildNamespaceNameFromNamespaceIndex( $idx ) {
		$services = MediaWikiServices::getInstance();
		$namespaceInfo = $services->getNamespaceInfo();
		if ( $namespaceInfo->isTalk( $idx ) ) {
			$idx--;
		}

		$excludeNs = $services->getConfigFactory()
			->makeConfig( 'bsg' )->get( 'NamespaceCSSExcludeNamespaces' );

		if ( in_array( $idx, $excludeNs ) ) {
			return false;
		}
		if ( $idx === NS_MAIN ) {
			// This method returns canonical namespace names. The canonical
			// language is english
			return wfMessage( 'bs-ns_main' )->inLanguage( 'en' )->plain();
		}
		$nsName = $namespaceInfo->getCanonicalName( $idx );
		if ( !$nsName ) {
			return false;
		}

		return $nsName;
	}

	/**
	 *
	 * @param int $idx
	 * @return \Title|false
	 */
	public static function buildTitleFromNamespaceIndex( $idx ) {
		$text = static::buildNamespaceNameFromNamespaceIndex( $idx );
		if ( !$text ) {
			return false;
		}
		if ( $idx === NS_MAIN ) {
			// Pseudo canonical name. Used for Page name
			$text = 'Main';
		}

		return \Title::newFromText( "$text.css", NS_MEDIAWIKI );
	}
}
