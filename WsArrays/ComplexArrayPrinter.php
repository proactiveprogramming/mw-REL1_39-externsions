<?php

/**
 * WSArrays - Associative and multidimensional arrays for MediaWiki.
 * Copyright (C) 2019 Marijn van Wezel
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

namespace SMW\Query\ResultPrinters;

use Exception;

/**
 * Class ComplexArray
 *
 * It defines the object arrays should be stored in. Arrays that are stored in this object, are always escaped and safe. This class is a copy of the class in src/ComplexArray.class.php.
 *
 * @package SMW\Query\ResultPrinters
 * @alias src/ComplexArray.class.php
 */
class ComplexArray {
	/**
	 * @var array
	 */
	private $array = [];

	/**
	 * @param array $array
	 */
	public function __construct( array $array = [] ) {
		$this->array = $array;
	}

	/**
	 * Return the array with escaped characters.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getArray() {
		return $this->array;
	}
}

/**
 * Class ComplexArrayPrinter
 *
 * @package SMW\Query\ResultPrinters
 * @extends ResultPrinter
 */
class ComplexArrayPrinter extends ResultPrinter {
	/**
	 * @var string
	 */
	private $name = '';

	/**
	 * @var string
	 */
	private $delimiter = ',';

	/**
	 * @var bool
	 */
	private $detailed = false;

	/**
	 * @var array
	 */
	private $r = [];
	private $v = [];
	private $res = [];
	private $return = [];

	/**
	 * Define the name of the format.
	 *
	 * @return string
	 */
	public function getName() {
		return "ComplexArray";
	}

	/**
	 * @param array $definitions
	 * @return array
	 */
	public function getParamDefinitions( array $definitions ) {
		$definitions = parent::getParamDefinitions( $definitions );

		$definitions[] = [
			'name' => 'name',
			'message' => 'ca-smw-paramdesc-name',
			'default' => ''
		];

		$definitions[] = [
			'name' => 'detailed',
			'message' => 'ca-smw-paramdesc-detailed',
			'default' => 'false'
		];

		$definitions[] = [
			'name' => 'valuesep',
			'message' => 'ca-smw-paramdesc-valuesep',
			'default' => ','
		];

		return $definitions;
	}

	/**
	 * Creates an empty array with the specified name before the result is passed to getResultText().
	 *
	 * @inheritDoc
	 */
	protected function handleParameters( array $params, $outputMode ) {
		$name = $params['name'];

		global $wfDefinedArraysGlobal;
		$wfDefinedArraysGlobal[ $name ] = new \ComplexArray( [] );
	}

	/**
	 * @param \SMWQueryResult $queryResult
	 * @param $outputMode
	 * @return bool|string
	 */
	protected function getResultText( \SMWQueryResult $queryResult, $outputMode ) {
		return $this->buildContents( $queryResult );
	}

	/**
	 * @param \SMWQueryResult $queryResult
	 * @return bool|string
	 */
	private function buildContents( \SMWQueryResult $queryResult ) {
		global $wfDefinedArraysGlobal;

		$this->name = $this->params[ 'name' ];
		$this->delimiter = $this->params[ 'valuesep' ];
		$this->detailed = filter_var( $this->params[ 'detailed' ], FILTER_VALIDATE_BOOLEAN );

		if ( !$this->name ) {
			$json = json_encode( $this->buildResultArray( $queryResult ) );

			$json = preg_replace( "/(?!\B\"[^\"]*){(?![^\"]*\"\B)/i", "((", $json );
			$json = preg_replace( "/(?!\B\"[^\"]*)}(?![^\"]*\"\B)/i", "))", $json );

			return $json;
		}

		$result = $this->buildResultArray( $queryResult );

		$wfDefinedArraysGlobal[ $this->name ] = new \ComplexArray( $result );

		return null;
	}

	/**
	 * @param \SMWQueryResult $res
	 * @return array
	 */
	private function buildResultArray( \SMWQueryResult $res ) {
		$this->res = array_merge( $res->serializeToArray() );

		foreach ( $this->res['results'] as $result ) {
			$this->r = [];
			$this->formatResult( $result );
		}

		return $this->return;
	}

	private function formatResult( $result ) {
		foreach ( $result["printouts"] as $key => $printout ) {
			$this->formatPrintout( $key, $printout );
		}

		if ( isset( $result[ 'fulltext' ] ) ) {
		    $this->r[ 'catitle' ] = $result[ 'fulltext' ];
		}

		if ( isset( $result[ 'fullurl' ] ) ) {
		    $this->r[ 'cafullurl' ] = $result[ 'fullurl' ];
		}

		if ( isset( $result[ 'namespace' ] ) ) {
		    $this->r[ 'canamespace' ] = $result[ 'namespace' ];
		}

		if ( isset( $result[ 'exists' ] ) ) {
		    $this->r[ 'caexists' ] = $result[ 'exists' ];
		}

		if ( isset( $result[ 'displaytitle' ] ) ) {
		    $this->r[ 'cadisplaytitle' ] = $result[ 'displaytitle' ];
		}

		array_push( $this->return, $this->r );
	}

	/**
	 * @param $key
	 * @param $printout
	 */
	private function formatPrintout( $key, $printout ) {
		$this->v = [];

		$prop_type = $this->fetchPropType( $key );
		foreach ( $printout as $property ) {
			$this->formatProperty( $prop_type, $property );
		}

		$this->addPrintout( $key );
	}

	/**
	 * @param $key
	 */
	private function addPrintout( $key ) {
		if ( !empty( $this->v ) ) {
            if ( count( $this->v ) === 1 ) {
                $this->r[$key] = $this->v[0];
            } else {
                $this->r[$key] = $this->v;
            }
		}
	}

	/**
	 * @param $prop_type
	 * @param $property
	 */
	private function formatProperty( $prop_type, $property ) {
		switch ( $prop_type ) {
			case "_wpg":
				array_push( $this->v, $this->formatPropertyOfType_wpg( $property ) );
				break;
			case "_dat":
				array_push( $this->v, $this->formatPropertyOfType_dat( $property ) );
				break;
			case "_ema":
				array_push( $this->v, $this->formatPropertyOfType_ema( $property ) );
				break;
			case "_boo":
				array_push( $this->v, $this->formatPropertyOfType_boo( $property ) );
				break;
			default:
				array_push( $this->v, $this->formatPropertyOfType_txt( $property ) );
				break;
		}
	}

	/**
	 * Format property values of type _wpg (page).
	 *
	 * @param $property
	 * @return string|array
	 */
	private function formatPropertyOfType_wpg( $property ) {
		if ( $this->detailed === true && isset( $property['fulltext'] ) ) {
			return $property['fulltext'];
		}

		return $property;
	}

	/**
	 * Format property values of type _dat (date).
	 *
	 * @param $property
	 * @return string
	 */
	private function formatPropertyOfType_dat( $property ) {
		$unix_timestamp = $property["timestamp"];

		// Return ISO 8601 timestamp
		return date( 'c', $unix_timestamp );
	}

	/**
	 * Format property values of type _ema (email).
	 *
	 * @param $property
	 * @return string
	 */
	private function formatPropertyOfType_ema( $property ) {
		return str_replace( "mailto:", "", $property );
	}

	/**
	 * Format property values of type _boo (boolean).
	 *
	 * @param $property
	 * @return string
	 */
	private function formatPropertyOfType_boo( $property ) {
		switch ( $property ) {
			case 't':
				return '1';
			case 'f':
				return '0';
		}

		return $property;
	}

	/**
	 * This function is not really necessary, it is just here for proper semantics.
	 *
	 * @param $property
	 * @return string
	 */
	private function formatPropertyOfType_txt( $property ) {
	    return $property;
	}

	/**
	 * @param $key
	 * @return string
	 */
	private function fetchPropType( $key ) {
		$print_requests = $this->res["printrequests"];

		foreach ( $print_requests as $print_request ) {
			if ( $print_request["label"] === $key ) {
				return $print_request["typeid"];
			}
		}

		// When the property isn't found (should never happen) assume _txt.
		return "_txt";
	}
}
