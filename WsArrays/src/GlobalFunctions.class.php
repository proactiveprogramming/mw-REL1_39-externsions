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

require_once 'ComplexArray.class.php';

/**
 * Class GlobalFunctions
 *
 * Grandfather class. These functions are available in all other classes.
 */
class GlobalFunctions {
	const CA_MARKUP_SIMPLE = 1;
	const CA_MARKUP_LEGACY = 3;

	/**
	 * Print an error message.
	 *
	 * @param string $message
	 * @return array
	 */
	public static function error( $message ) {
		$params = func_get_args();
		array_shift( $params );

		$msgHtml = Html::rawElement(
			'span',
			[ 'class' => 'error' ],
			wfMessage( $message, $params )->toString()
		);

		return [ $msgHtml, 'noparse' => true, 'isHTML' => false ];
	}

	/**
	 * Check if the given string $json is valid JSON.
	 *
	 * @param string $json
	 * @return bool
	 */
	public static function isValidJSON( $json ) {
		$value = json_decode( $json, true );

        // We check whether "$value" is an array, otherwise an integer would also be considered as valid JSON, which
        // leads to problems.
		return json_last_error() == JSON_ERROR_NONE && is_array( $value );
	}

	/**
	 * Convert WSON (custom JSON) to JSON.
	 *
	 * @param string &$wson
	 * @return string
	 */
	public static function WSONtoJSON( &$wson ) {
		$wson = preg_replace( "/(?!\B\"[^\"]*)\(\((?![^\"]*\"\B)/i", "{", $wson );
		$wson = preg_replace( "/(?!\B\"[^\"]*)\)\)(?![^\"]*\"\B)/i", "}", $wson );

		return $wson;
	}

	/**
	 * Convert JSON to WSON.
	 *
	 * @param string &$json
	 * @return string
	 */
	public static function JSONtoWSON( &$json ) {
		$json = preg_replace( "/(?!\B\"[^\"]*){(?![^\"]*\"\B)/i", "((", $json );
		$json = preg_replace( "/(?!\B\"[^\"]*)}(?![^\"]*\"\B)/i", "))", $json );

		return $json;
	}

	public static function arrayToMarkup( $array ) {
		if ( !is_array( $array ) ) {
			return false;
		}

		$json = json_encode( $array );
		self::JSONtoWSON( $json );

		return $json;
	}

	/**
	 * Convert markup to an array.
	 *
	 * @param string $markup
	 * @param string|null $separator
	 * @return array|null
	 */
	public static function markupToArray( $markup, $separator = null ) {
		if ( $markup === null || $markup === '' ) {
			return null;
		}

		$markup = str_replace( "\n", '', $markup );
		$markup_type = self::determineMarkup( $markup, $separator );

		switch ( $markup_type ) {
			case self::CA_MARKUP_LEGACY:
				self::WSONtoJSON( $markup );
				$array = self::trimElements( json_decode( $markup, true ) );

				return $array;
			case self::CA_MARKUP_SIMPLE:
				if ( !$separator ) {
					$separator = ',';
				}

				$markup = str_replace( array( "\n", "\r", '\n' ), "", $markup );

				$array = explode( $separator, $markup );
				$array = self::trimElements( $array );

				return $array;
			default:
				return null;
		}
	}

    /**
     * @param $markup
     * @param null $separator
     * @return int
     */
	public static function determineMarkup( $markup, $separator = null ) {
	    if ( $separator ) {
	        return self::CA_MARKUP_SIMPLE;
        }

		$json_markup = $markup;
		self::WSONtoJSON( $json_markup );

        if ( self::isValidJSON( $json_markup ) ) {
			return self::CA_MARKUP_LEGACY;
		}

		return self::CA_MARKUP_SIMPLE;
	}

	public static function getKeys( $array_name ) {
		if ( preg_match_all( "/(?<=\[).+?(?=\])/", $array_name, $matches ) === 0 ) {
			return false;
		}

		return $matches[0];
	}

	/**
	 * Create an array from a comma-separated list.
	 *
	 * @param string &$options
	 */
	public static function serializeOptions( &$options ) {
		$options = explode( ",", $options );
	}

	/**
	 * Check if an array contains a subarray.
	 *
	 * @param array $array
	 * @return bool
	 */
	public static function containsArray( $array ) {
		if ( !is_array( $array ) ) {
			return false;
		}

		foreach ( $array as $value ) {
			if ( is_array( $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the contents of a subarray based on the name (basearray[subarray][subarray]...).
	 *
	 * @param string $array_name
	 * @param bool $unsafe
	 * @return bool|array
	 *
	 * @throws Exception
	 */
	public static function getArrayFromArrayName( $array_name ) {
		/* This is already a base array, so just get the array */
		if ( !strpos( $array_name, "[" ) ) {
			if ( isset( WSArrays::$arrays[ $array_name ] ) ) {
				return self::getArrayFromComplexArray( WSArrays::$arrays[ $array_name ] );
			}
		} else {
			return self::getSubarrayFromArrayName( $array_name );
		}

		return false;
	}

	/**
	 * Get the subarray from an array name in the form of <base_array>[<sub1>][<sub2>][...]. Used by GlobalFunctions::getArrayFromArrayName().
	 *
	 * @param string $array_name
	 * @param bool $unsafe
	 * @return array|bool|mixed
	 * @throws Exception
	 */
	private static function getSubarrayFromArrayName( $array_name ) {
		/* Get the name of the base array */
		$base_array_name = self::getBaseArrayFromArrayName( $array_name );

		if ( !self::arrayExists( $base_array_name ) ) {
			return false;
		}

		if ( preg_match_all( "/(?<=\[).+?(?=\])/", $array_name, $matches ) === 0 ) {
			return false;
		}

		$array = self::getArrayFromComplexArray( WSArrays::$arrays[ $base_array_name ] );

		if ( !is_array( $array ) ) {
			return false;
		}

		$array = self::getArrayFromMatch( $array, $matches[0] );

		return $array;
	}

	/**
	 * @param array $array
	 * @param array $matches
	 * @return array|bool|mixed
	 */
	private static function getArrayFromMatch( array $array, array $matches ) {
		$wairudokado_helper_object = false;

		foreach ( $matches as $index => $match ) {
			$current_array = $array;

			if ( $wairudokado_helper_object === true ) {
				$wairudokado_helper_object = false;
				continue;
			}

			// The Wairudokado (transliterated Japanese for wildcard, tribute to the Scope Resolution Operator in PHP)
            // operator gives users the ability to use wildcards as pointers in an array
			if ( self::isWairudokado( $match ) ) {
				if ( self::isWairudokado( end( $matches ) ) ) {
					// The Wairudokado operator does not make sense when it's at the end, so just ignore it
					return $array;
				}

				if ( self::isWairudokado( $matches[$index + 1] ) ) {
					// Skip sequential Wairudokado operators and interpret them as one
					continue;
				}

				$array = self::getArrayFromWairudokado( $array, $matches, $index );
				$wairudokado_helper_object = true;
			} else {
			    if ( !is_array( $array ) ) {
			        return false;
                }

				foreach ( $array as $key => $value ) {
					if ( $key == $match ) {
						$array = $value;
						continue;
					}
				}

				if ( $current_array === $array ) {
					return false;
				}
			}
		}

		return $array;
	}

	/**
	 * @param $array
	 * @param $matches
	 * @param $index
	 * @return array
	 */
	private static function getArrayFromWairudokado( $array, $matches, $index ) {
		$helper_array = [];

		foreach ( $array as $item ) {
			if ( !is_array( $item ) || !isset( $item[ $matches[ $index + 1 ] ] ) ) {
				continue;
			}

			array_push( $helper_array, $item[ $matches[ $index + 1 ] ] );
		}

		return $helper_array;
	}

	/**
	 * Check if the user has supplied a wildcard. Used by GlobalFunctions::getSubarrayFromArrayName().
	 *
	 * @param string $match
	 * @return bool
	 */
	private static function isWairudokado( $match ) {
		if ( $match === '*' ) {
			return true;
		}

		return false;
	}

	/**
	 * Fetch any arrays defined by Semantic MediaWiki.
	 *
	 * Semantic MediaWiki stores all ComplexArrays in the configuration parameter $wfDefinedArraysGlobal. In order to allow access to these array, we need to move them to WSArrays::$arrays.
	 *
	 * @return void
	 */
	public static function fetchSemanticArrays() {
		global $wfDefinedArraysGlobal;
		if ( $wfDefinedArraysGlobal !== null ) {
			WSArrays::$arrays = array_merge( WSArrays::$arrays, $wfDefinedArraysGlobal );
		}

		$wfDefinedArraysGlobal = [];
	}

	/**
	 * Get the name of the base array from a full name.
	 *
	 * @param string $array_name
	 * @return string
	 */
	public static function getBaseArrayFromArrayName( $array_name ) {
		return strtok( $array_name, "[" );
	}

	/**
	 * @param string $array_name
	 * @return bool
	 */
	public static function isValidArrayName( $array_name ) {
		if ( strpos( $array_name, '[' ) !== false ||
			strpos( $array_name, ']' ) !== false ||
			strpos( $array_name, '{' ) !== false ||
			strpos( $array_name, '}' ) !== false ) {
			return false;
		}

		if ( is_numeric( $array_name ) ) {
		    return false;
        }

		if ( ctype_digit( $array_name ) ) {
		    return false;
        }

		return true;
	}

	/**
	 * @param ComplexArray $array
	 * @return array
	 * @throws Exception
	 */
	public static function getArrayFromComplexArray( ComplexArray $array ) {
		return $array->getArray();
	}

	/**
	 * @param string $array_name
	 * @return bool
	 */
	public static function arrayExists( $array_name ) {
		if ( isset( WSArrays::$arrays[$array_name] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $arg
	 * @param $frame
	 * @param string $parser
	 * @param string $noparse
	 * @return string
	 * @throws Exception
	 */
	public static function getValue( $arg, $frame, $parser = '', $noparse = '' ) {
		if ( !isset( $arg ) || empty( $arg ) ) {
			return null;
		}

		$noparse_arguments = self::formatNoparse( $noparse );
		$noparse_arguments = array_map( 'trim', $noparse_arguments );

		if ( count( $noparse_arguments ) > 0 ) {
			return self::rawValue( $arg, $frame, $noparse_arguments );
		} else {
			return self::getSFHValue( $arg, $frame );
		}
	}

	/**
	 * @param $noparse
	 * @return array
	 */
	public static function formatNoparse( $noparse ) {
		$noparse_arguments = explode( ',', $noparse );

		$arguments = [];
		foreach ( $noparse_arguments as &$argument ) {
			switch ( $argument ) {
				case "NO_IGNORE":
					$arguments[] = PPFrame::NO_IGNORE;
					break;
				case "NO_ARGS":
					$arguments[] = PPFrame::NO_ARGS;
					break;
				case "NO_TAGS":
					$arguments[] = PPFrame::NO_TAGS;
					break;
				case "NO_TEMPLATES":
					$arguments[] = PPFrame::NO_TEMPLATES;
					break;
			}
		}

		return $arguments;
	}

	/**
	 * @param $arg
	 * @param $frame
	 * @param array $noparse_arguments
	 * @return string
	 */
	public static function rawValue( $arg, $frame, $noparse_arguments = [] ) {
		if ( !$noparse_arguments ) {
			$expanded_frame = $frame->expand( $arg );

		} else {
			$flags = array_reduce( $noparse_arguments, function ( $a, $b ) { return $a | $b;
			}, 0 );
			$expanded_frame = $frame->expand( $arg, $flags );
		}

		$trimmed_frame = trim( $expanded_frame );

		return $trimmed_frame;
	}

	/**
	 * @param $arg
	 * @param $frame
	 * @return string
	 */
	public static function getSFHValue( $arg, $frame ) {
		return trim( $frame->expand( $arg ) );
	}

    private static function trimElements( $array ) {
	    array_walk_recursive( $array, function ( &$value ) { $value = trim( $value ); } );

	    return $array;
    }
}
