<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks implements LoadExtensionSchemaUpdatesHook {
	public function onLoadExtensionSchemaUpdates( $updater ): void {
		$dir = dirname( __DIR__ );
		$type = $updater->getDB()->getType();

		$updater->addExtensionTable(
			'wsu_batchlist',
			"$dir/schemas/$type/tables-generated.sql"
		);
	}
}
