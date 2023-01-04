<?php

namespace TimedNotify\MediaWiki\Hooks;

use ExtensionRegistry;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * This class is responsible for updating the database's schema whenever "maintenance/update.php" is run.
 */
class DatabaseSchemaUpdater implements LoadExtensionSchemaUpdatesHook {
	/**
	 * @inheritDoc
	 */
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		// We MUST use the ExtensionRegistry singleton here, since the LoadExtensionSchemaUpdates hook
		// does not support service injections
		$extensionDirectory = dirname( ExtensionRegistry::getInstance()->getAllThings()["TimedNotify"]["path"] );
		$sqlDirectory = sprintf( "%s/sql", $extensionDirectory );
		$sqlFiles = glob( $sqlDirectory . "/" . $updater->getDB()->getType() . "/*_table.sql" );

		foreach ( $sqlFiles as $sqlFile ) {
			// Remove the "_table.sql" suffix
			$tableName = substr( basename( $sqlFile ), 0, -strlen( "_table.sql" ) );

			// Add our extension table
			$updater->addExtensionTable( $tableName, $sqlFile );
		}
	}

}
