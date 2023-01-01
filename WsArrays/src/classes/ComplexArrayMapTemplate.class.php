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
 * Class ComplexArrayMapTemplate
 *
 * Defines the parser function {{#complexarraymaptemplate:}}, which allows users to map a multidimensional array to a list of templates.
 *
 * @extends WSArrays
 */
class ComplexArrayMapTemplate extends ResultPrinter {
	public function getName() {
		return 'complexarraymaptemplate';
	}

	public function getAliases() {
		return [
			'camaptemplate',
			'camapt',
			'catemplate'
		];
	}

	public function getType() {
		return 'normal';
	}

    /**
     * Define all allowed parameters.
     *
     * @param Parser $parser
     * @param string $name
     * @param string $template
     * @param string $options
     * @param string $new_delimiter
     * @return array
     *
     * @throws Exception
     */
	public static function getResult( Parser $parser, $name = '', $template = '', $options = '', $new_delimiter = '' ) {
		GlobalFunctions::fetchSemanticArrays();

		if ( empty( $name ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Name' ) );
		}

		if ( empty( $template ) ) {
			return GlobalFunctions::error( wfMessage( 'ca-omitted', 'Template' ) );
		}

        $new_delimiter = str_replace( [ '\n', '\s' ], [ "\n", ' ' ], $new_delimiter );

		return self::arrayMapTemplate( $name, $template, $options, $new_delimiter );
	}

    /**
     * @param $name
     * @param $template
     * @param string $options
     * @param string $new_delimiter
     * @return array|string
     *
     * @throws Exception
     */
	private static function arrayMapTemplate( $name, $template, $options = '', $new_delimiter = '' ) {
		$base_array = GlobalFunctions::getBaseArrayFromArrayName( $name );
		$array = GlobalFunctions::getArrayFromArrayName( $name );

		if ( !GlobalFunctions::arrayExists( $base_array ) || !$array ) {
			return '';
		}

		return [ self::mapToArray( $array, $template, $options, $new_delimiter ), "noparse" => false ];
	}

    /**
     * @param $array
     * @param $template
     * @param $options
     * @param $new_delimiter
     * @return string|null
     */
	private static function mapToArray( $array, $template, $options, $new_delimiter ) {
		$return = [];

		if ( GlobalFunctions::containsArray( $array ) && $options !== "condensed" ) {
			foreach ( $array as $value ) {
				self::map( $value, $return, $template );
			}
		} else {
			self::map( $array, $return, $template );
		}

		return implode( $new_delimiter, $return );
	}

	/**
	 * @param $value
	 * @param &$return
	 * @param $template
	 */
	private static function map( $value, &$return, $template ) {
		$t = "{{" . $template;

		if ( is_array( $value ) ) {
			foreach ( $value as $key => $subvalue ) {
				if ( is_array( $subvalue ) ) {
					$json = json_encode( $subvalue );
					GlobalFunctions::JSONtoWSON( $json );

					$subvalue = $json;
				}

				if ( is_numeric( $key ) ) {
					$key += 1;
				}

				$t .= "|$key=$subvalue";
			}
		} else {
			$t .= "|$value";
		}

		$return[] = $t . "}}";
	}
}
