<?php

// Autoload dependencies
if ( file_exists( __DIR__ . '/../vendor/autoload.php' ) ) {
	require_once __DIR__ . '/../vendor/autoload.php';
}

// Command that starts the built-in web server
$command = sprintf(
	'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
	WEB_SERVER_HOST,
	WEB_SERVER_PORT,
	WEB_SERVER_DOCROOT
);

// Execute the command and store the process ID
$output = [];
exec( $command, $output );
$pid = (int) $output[0];

echo sprintf(
		'%s - Web server started on %s:%d (%s) with PID %d',
		date( 'r' ),
		WEB_SERVER_HOST,
		WEB_SERVER_PORT,
		realpath( WEB_SERVER_DOCROOT ),
		$pid
	) . PHP_EOL;

// Kill the web server when the process ends
register_shutdown_function( function() use ( $pid ) {
	echo sprintf( '%s - Killing process with ID %d', date( 'r' ), $pid ) . PHP_EOL;
	exec( 'kill ' . $pid );
} );
