<?php

namespace jsoner;

class FakeMWConfig
{
	private $store;

	public function __construct( $initial_values = [] ) {

		$this->store = $initial_values;
	}

	public function get( $name ) {

		if ( array_key_exists( $name, $this->store ) ) {
			return $this->store[$name];
		}
		throw new \UnexpectedValueException( $name . " not in config." );
	}
}
