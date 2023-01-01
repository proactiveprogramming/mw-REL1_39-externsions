<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/CommandLineInc.php";

use MediaWiki\Extension\CentralAuth\CentralAuthServices;
use MediaWiki\Extension\CentralAuth\User\CentralAuthUser;

echo "Populating global groups table with stewards...\n";

// Fetch local stewards
$dbl = wfGetDB( DB_REPLICA );	// Get local database
$localStewards = $dbl->selectFieldValues(
	[ 'user', 'user_groups' ],
	'user_name',
	[
		'ug_group' => 'steward',
		'ug_expiry IS NULL OR ug_expiry >= ' . $dbl->addQuotes( $dbl->timestamp() ),
		'user_id = ug_user'
	],
	'migrateStewards.php'
);

echo "Fetched " . count( $localStewards ) . " from local database... Checking for attached ones\n";
$dbg = CentralAuthServices::getDatabaseManager()->getCentralDB( DB_PRIMARY );
$globalStewards = [];
$result = $dbg->select(
	[ 'globaluser', 'localuser' ],
	[ 'gu_name', 'gu_id' ],
	[
		'gu_name = lu_name',
		'lu_wiki' => WikiMap::getCurrentWikiId(),
		'gu_name IN (' . $dbg->makeList( $localStewards ) . ')',
	],
	'migrateStewards.php'
);
foreach ( $result as $row ) {
	$globalStewards[$row->gu_name] = $row->gu_id;
}

echo "Fetched " . count( $localStewards ) . " SULed stewards... Adding them in group\n";
foreach ( $globalStewards as $user => $id ) {
	$dbg->insert( 'global_user_groups',
		[
			'gug_user' => $id,
			'gug_group' => 'steward' ],
		'migrateStewards.php' );
	// Using id as a hack for phan-taint-check.
	echo "Added user id " . ( (int)$id ) . "\n";

	// @phan-suppress-next-line SecurityCheck-SQLInjection T290563
	$u = new CentralAuthUser( $user );
	$u->quickInvalidateCache(); // Don't bother regenerating the steward's cache.
}
