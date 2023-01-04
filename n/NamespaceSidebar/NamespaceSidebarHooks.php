<?php

/**
 * Hooks for NamespaceSidebar extension
 *
 * @file
 * @ingroup Extensions
 */
class NamespaceSidebarHooks {

	/**
	 *
	 * @param Skin $skin
	 * @param array &$bar
	 */
	public static function onSkinBuildSidebar( Skin $skin, &$bar ) {
		/** @todo check how $wgEnableSidebarCache affects this */
		$sidebarKey = 'sidebar-' . $skin->getTitle()->getNsText();
		if ( wfMessage( $sidebarKey )->exists() ) {
			$bar = [];
			$skin->addToSidebar( $bar, $sidebarKey );
		}
	}
}
