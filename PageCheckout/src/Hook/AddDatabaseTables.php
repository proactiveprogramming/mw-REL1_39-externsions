<?php

namespace MediaWiki\Extension\PageCheckout\Hook;

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class AddDatabaseTables implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 * @return bool
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$base = dirname( dirname( __DIR__ ) ) . '/db';

		$updater->addExtensionTable(
			'page_checkout_event',
			"$base/page_checkout_event.sql"
		);
		$updater->addExtensionTable(
			'page_checkout_locks',
			"$base/page_checkout_locks.sql"
		);

		return true;
	}
}
