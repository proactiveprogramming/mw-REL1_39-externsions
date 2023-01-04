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

/**
 * Class ComplexArraySort
 *
 * Defines the parser function {{#complexarraysort:}}, which allows users to sort arrays.
 *
 * @extends WSArrays
 */
class ComplexArraySort extends ResultPrinter {
	public function getName() {
		return 'complexarraysort';
	}

	public function getAliases() {
		return [
			'casort'
		];
	}

	public function getType() {
		return 'normal';
	}

	/**
	 * @var
	 */
	private static $key;

	/**
	 * @var
	 */
	private static $array_name;

	/**
	 * @var
	 */
	private static $array;

	/**
	 * Define all allowed parameters.
	 *
	 * @param Parser $parser
	 * @param string $array_name
	 * @param string $options
	 * @param string $key
	 * @return array|null
	 *
	 * @throws Exception
	 */
	public static function getResult( Parser $parser, $array_name = '', $options = '', $key = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $array_name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		return self::arraySort( $array_name, $options, $key );
	}

	/**
	 * @param string $array_name
	 * @param string $options
	 * @param string $key
	 * @return array|string
	 *
	 * @throws Exception
	 */
	private static function arraySort( $array_name, $options = '', $key = '' ) {
		if ( !GlobalFunctions::arrayExists( $array_name ) ) {
			return '';
		}

		self::$array      = GlobalFunctions::getArrayFromComplexArray( WSArrays::$arrays[ $array_name ] );
		self::$array_name = $array_name;

		if ( empty( $options ) ) {
			$result = self::sortArray( "sort" );
		} else {
			if ( $key !== '' ) {
				self::$key = $key;
			}

			$result = self::sortArray( $options );
		}

		if ( $result === true ) {
			WSArrays::$arrays[$array_name] = new ComplexArray( self::$array );

			return '';
		}

		return GlobalFunctions::error( $result );
	}

	/**
	 * @param $algo
	 * @return bool|string
	 */
	private static function sortArray( $algo ) {
		switch ( $algo ) {
			case 'multisort':
				$array = self::multisort();
				break;
			case 'asort':
				$array = self::asort();
				break;
			case 'arsort':
				$array = self::arsort();
				break;
			case 'krsort':
				$array = self::krsort();
				break;
			case 'natcasesort':
				$array = self::natcasesort();
				break;
			case 'natsort':
				$array = self::natsort();
				break;
			case 'rsort':
				$array = self::rsort();
				break;
			case 'shuffle':
				$array = self::shuffle();
				break;
			case 'keysort':
				$array = self::keysort( null );
				break;
			case 'keysort,desc':
				$array = self::keysort( 'desc' );
				break;
			case 'sort':
			default:
				$array = self::sort();
				break;
		}

		return $array;
	}

	/**
	 * Sort array using multisort
	 *
	 * @return bool|string
	 */
	private static function multisort() {
		if ( !array_multisort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'multisort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using asort
	 *
	 * @return bool|string
	 */
	private static function asort() {
		if ( !asort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'asort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using arsort
	 *
	 * @return bool|string
	 */
	private static function arsort() {
		if ( !arsort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'arsort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using krsort
	 *
	 * @return bool|string
	 */
	private static function krsort() {
		if ( !krsort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'krsort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using natcasesort
	 *
	 * @return bool|string
	 */
	private static function natcasesort() {
		if ( !natcasesort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'natcasesort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using natsort
	 *
	 * @return bool|string
	 */
	private static function natsort() {
		if ( !natsort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'natsort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using rsort
	 *
	 * @return bool|string
	 */
	private static function rsort() {
		if ( !rsort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'rsort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using shuffle
	 *
	 * @return bool|string
	 */
	private static function shuffle() {
		if ( !shuffle( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'shuffle' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using sort
	 *
	 * @return bool|string
	 */
	private static function sort() {
		if ( !sort( self::$array ) ) {
			$ca_sort_broken = wfMessage( 'ca-sort-broken', 'sort' );

			return $ca_sort_broken;
		}

		return true;
	}

	/**
	 * Sort array using keysort
	 *
	 * @param $order
	 *
	 * @return bool|string
	 */
	private static function keysort( $order ) {
		if ( self::$key === null ) {
			return wfMessage( 'ca-sort-missing-key' );
		}

		foreach ( self::$array as $value ) {
		    if ( !isset( $value[ self::$key ] ) ) {
		        return wfMessage( 'ca-sort-invalid-key' );
            }

			if ( is_array( $value[ self::$key ] ) ) {
				return wfMessage( 'ca-sort-array-too-deep' );
			}
		}

		self::ksort( self::$array, self::$key );

		$i = 0;
		$temp = [];
		foreach ( self::$array as $key => $item ) {
			$temp[ $i ] = $item;
			$i++;
		}

		self::$array = $temp;

		if ( $order == "desc" ) {
			self::$array = array_reverse( self::$array );
		}

		WSArrays::$arrays[ self::$array_name ] = new ComplexArray( self::$array );

		return true;
	}

	/**
	 * User-defined sorting function which sorts based on key.
	 *
	 * @param &$array
	 * @param $key
	 */
	private static function ksort( &$array, $key ) {
		$sorter = [];
		$ret = [];

		reset( $array );

		foreach ( $array as $ii => $va ) {
			$sorter[ $ii ] = $va[ $key ];
		}

		asort( $sorter );

		foreach ( $sorter as $ii => $va ) {
			$ret[ $ii ] = $array[ $ii ];
		}

		$array = $ret;
	}
}
