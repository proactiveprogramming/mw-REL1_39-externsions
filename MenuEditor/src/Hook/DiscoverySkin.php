<?php

namespace MediaWiki\Extension\MenuEditor\Hook;

use BlueSpice\Discovery\Hook\BlueSpiceDiscoveryTemplateDataProviderAfterInit;
use BlueSpice\Discovery\ITemplateDataProvider;

class DiscoverySkin implements BlueSpiceDiscoveryTemplateDataProviderAfterInit {

	/**
	 * @param ITemplateDataProvider $registry
	 */
	public function onBlueSpiceDiscoveryTemplateDataProviderAfterInit( $registry ): void {
		if ( !$this->title ) {
			return;
		}
		$registry->register( 'panel/edit', 'ca-menueditsource' );
		$registry->unregister( 'panel/edit', 'ca-new-section' );
		$registry->unregister( 'panel/edit', 'ca-ve-edit' );
	}
}
