<?php
/**
 * Namespace Paths
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @copyright Copyright © 2010 – Daniel Friesen
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:NamespacePaths Documentation
 */
use MediaWiki\MediaWikiServices;

class NamespacePaths {
	/**
	 * @param PathRouter $router
	 * @return bool
	 */
	public static function onWebRequestPathInfoRouter( PathRouter $router ): bool {
		global $wgNamespacePaths;
		$router->add( $wgNamespacePaths,
			[ 'data:page_title' => '$1', 'data:ns' => '$key' ],
			[ 'callback' => [ __CLASS__, 'onNamespacePathCallback' ] ],
		);
		return true;
	}

	/**
	 * @param array &$matches
	 * @param array $data
	 * @return void
	 */
	public static function onNamespacePathCallback( array &$matches, array $data ): void {
		$nstext = MediaWikiServices::getInstance()
			->getNamespaceInfo()
			->getCanonicalName( intval( $data['ns'] ) );
		$matches['title'] = $nstext . ':' . $data['page_title'];
	}

	/**
	 * @param Title $title
	 * @param string &$url
	 * @return bool
	 */
	public static function onGetLocalURLArticle( Title $title, string &$url ): bool {
		global $wgNamespacePaths;
		// Ensure that the context of this url is one we'd do article path replacements in
		$ns = $title->getNamespace();
		if ( array_key_exists( $ns, $wgNamespacePaths ) ) {
			$url = str_replace( '$1', wfUrlencode( $title->getDBkey() ), $wgNamespacePaths[$ns] );
		}
		return true;
	}
}
