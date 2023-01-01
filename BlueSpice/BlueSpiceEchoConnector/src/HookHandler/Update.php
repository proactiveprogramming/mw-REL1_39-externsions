<?php

namespace BlueSpice\EchoConnector\HookHandler;

use BSMigrateWatchlistNotificationSettings;
use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class Update implements LoadExtensionSchemaUpdatesHook {

	/**
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addPostDatabaseUpdateMaintenance(
			BSMigrateWatchlistNotificationSettings::class
		);
	}

}
