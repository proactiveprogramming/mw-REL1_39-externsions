<?php

namespace StickyTOC;

use Config;
use OutputPage;
use Skin;

/**
 * Class StickyTOCHooks
 * @package StickyTOC
 */
class StickyTOCHooks {

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 */
	public static function onBeforePageDisplay( &$out, &$skin ) {
		$out->addModules( [ 'ext.stickytoc' ] );
	}

	/**
	 * @param array &$vars
	 * @param string $skin
	 * @param Config $config
	 */
	public static function onResourceLoaderGetConfigVars( &$vars, $skin, $config ) {
		$vars['wgStickyTOCFloat'] = $config->get( 'StickyTOCFloat' );
		$vars['wgStickyTOCNavbar'] = $config->get( 'StickyTOCNavbar' );
		$vars['wgStickyTOCReplaceTitle'] = $config->get( 'StickyTOCReplaceTitle' );
	}

}
