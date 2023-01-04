<?php

namespace BlueSpice\EchoConnector\Data\Watchlist;

use BlueSpice\Data\Watchlist\Reader as BaseReader;
use MWStake\MediaWiki\Component\DataStore\ReaderParams;

class Reader extends BaseReader {

	/**
	 *
	 * @param ReaderParams $params
	 * @return PrimaryDataProvider
	 */
	protected function makePrimaryDataProvider( $params ) {
		return new PrimaryDataProvider( $this->db, [] );
	}
}
