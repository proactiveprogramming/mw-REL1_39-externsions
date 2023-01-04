<?php

namespace TimedNotify\Maintenance;

use Maintenance;
use MWException;
use TimedNotify\TimedNotifyServices;

if ( getenv( "MW_INSTALL_PATH" ) ) {
	require_once getenv( "MW_INSTALL_PATH" ) . "/maintenance/Maintenance.php";
} else {
	require_once __DIR__ . "/../../../maintenance/Maintenance.php";
}

class RunNotifications extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'TimedNotify' );
		$this->addDescription( 'Manually run the notifications' );
	}

	public function execute() {
		try {
			$notificationRunner = TimedNotifyServices::getNotificationRunner();
			$notificationRunner->run();
		} catch ( MWException $exception ) {
			$this->fatalError( $exception->getText() );
		}

		$this->output( "Done.\n" );
	}
}

$maintClass = RunNotifications::class;
require_once RUN_MAINTENANCE_IF_MAIN;
