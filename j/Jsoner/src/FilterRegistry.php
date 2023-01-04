<?php

namespace jsoner;

use jsoner\exceptions\FilterException;
use jsoner\filter\Filter;

class FilterRegistry
{
	private $config;

	private $filters = [
		'f-CensorKeys' =>    "\\jsoner\\filter\\CensorKeysFilter",
		'f-RemoveKeys' =>    "\\jsoner\\filter\\RemoveKeysFilter",
		'f-SelectKeys' =>    "\\jsoner\\filter\\SelectKeysFilter",
		'f-SelectSubtree' => "\\jsoner\\filter\\SelectSubtreeFilter",
		'f-SelectRecord' =>  "\\jsoner\\filter\\SelectRecordFilter"
	];

	public function __construct( $config ) {
		$this->config = $config;
	}

	public function addFilter( $key, $fqcn ) {
		$this->filters[$key] = $fqcn;
	}

	/**
	 * @param $key
	 * @return Filter
	 * @throws FilterException
	 */
	public function getFilterByKey( $key ) {
		$filterKey = Helper::getArrayValueOrDefault( $this->filters, $key );

		if ( $filterKey !== null ) {
			return new $filterKey( $this->config );
		}

		throw new FilterException( "No such filter: $key" );
	}

	public function getFiltersByKeys( $filterKeys ) {

		$filters = [];
		foreach ( $filterKeys as $filter ) {
			$filters[] = $this->getFilterByKey( $filter );
		}
		return $filters;
	}
}
