<?php

namespace TimedNotify\Maintenance;

use Maintenance;
use MediaWiki\MediaWikiServices;
use TimedNotify\TimedNotifyServices;

if ( getenv( "MW_INSTALL_PATH" ) ) {
	require_once getenv( "MW_INSTALL_PATH" ) . "/maintenance/Maintenance.php";
} else {
	require_once __DIR__ . "/../../../maintenance/Maintenance.php";
}

class PurgeOldPushedNotifications extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'TimedNotify' );
		$this->addDescription( 'Manually purge any old pushed notifications from the database' );

        // phpcs:ignore
		$this->addOption( "older-than", "Purge pushed notifications older than this number of days, or leave empty to use the default \$wgTimedNotifyPushedNotificationRetentionDays", false, true );
	}

	public function execute() {
		$config = MediaWikiServices::getInstance()->getMainConfig();

		$purgeOlderDefault = $config->get( "TimedNotifyPushedNotificationRetentionDays" );
		$purgeOlder = $this->getOption( "older", $purgeOlderDefault );

		$pushedNotificationBucket = TimedNotifyServices::getPushedNotificationBucket();
		$pushedNotificationBucket->purgeOld( $purgeOlder );

		$this->output( "Done.\n" );
	}
}

$maintClass = PurgeOldPushedNotifications::class;
require_once RUN_MAINTENANCE_IF_MAIN;
