<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace MediaWiki\Extension\TrustedXFF;

use Benchmarker;
use Wikimedia\RunningStat;
use const RUN_MAINTENANCE_IF_MAIN;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/includes/Benchmarker.php";

/**
 * Benchmark the TrustedXFF Lookup
 *
 * @codeCoverageIgnore
 */
class BenchmarkLookup extends Benchmarker {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Benchmark for TrustedXFF Lookup' );
		$this->requireExtension( 'TrustedXFF' );
	}

	public function execute() {
		$ips = [
			// IPv4 address from first range in list
			'64.12.96.0',
			// IPv4 address from middle of list
			'107.167.115.215',
			// Last IPv4 address in list
			'217.237.151.205',
			// IPv4 address not in list
			'127.0.0.1',

			// IPv6 address in list
			'2001:4c28:1::1',
			// IPv6 address not in list
			'2001:4c28:2::1',
		];

		$stat = new RunningStat();
		$t = microtime( true );
		$xff = TrustedXFF::getInstance();
		$t = ( microtime( true ) - $t ) * 1000;

		$stat->addObservation( $t );

		// Copy pasta from Benchmarker.php
		$this->addResult( [
			'name' => 'setup',
			'count' => $stat->getCount(),
			// Get rate per second from mean (in ms)
			'rate' => $stat->getMean() == 0 ? INF : ( 1.0 / ( $stat->getMean() / 1000.0 ) ),
			'total' => $stat->getMean() * $stat->getCount(),
			'mean' => $stat->getMean(),
			'max' => $stat->max,
			'stddev' => $stat->getStdDev(),
			'usage' => [
				'mem' => memory_get_usage( true ),
				'mempeak' => memory_get_peak_usage( true ),
			],
		] );

		$benches = [];
		foreach ( $ips as $ip ) {
			$benches[] = [
				'function' => [ $xff, 'isTrusted' ],
				'args' => [ $ip ],
			];
		}
		$this->bench( $benches );
	}
}

$maintClass = BenchmarkLookup::class;
require_once RUN_MAINTENANCE_IF_MAIN;
