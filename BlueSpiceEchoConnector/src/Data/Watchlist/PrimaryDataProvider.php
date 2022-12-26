<?php

namespace BlueSpice\EchoConnector\Data\Watchlist;

use BlueSpice\Data\Watchlist\PrimaryDataProvider as BasePrimaryDataProvider;
use MWStake\MediaWiki\Component\DataStore\Filter;

class PrimaryDataProvider extends BasePrimaryDataProvider {

	/**
	 *
	 * @param Filter[] $preFilters
	 * @return array
	 */
	protected function makePreFilterConds( $preFilters ) {
		$conds = parent::makePreFilterConds( $preFilters );
		unset( $conds['wl_namespace'] );

		return $conds;
	}
}
