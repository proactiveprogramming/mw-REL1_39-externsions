<?php
# (C) 2008 Steren Giannini
# Licensed under the GNU GPLv2 (or later).

$IP = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';
require_once $IP . "/maintenance/Maintenance.php";

class CheckForReminders extends Maintenance {

	public function execute() {
		if ( !$this->isQuiet() ) {
			print "ST check for reminders\n";
		}
		ST\SemanticTasksMailer::remindAssignees();
	}
}

$maintClass = 'CheckForReminders';

require_once RUN_MAINTENANCE_IF_MAIN;
