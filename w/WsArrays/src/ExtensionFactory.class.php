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
 * Class ExtensionFactory
 */
class ExtensionFactory extends WSArrays {
	/**
	 * @var string
	 */
	private static $extension_dir = '';

	/**
	 * @param Parser &$parser
	 * @return bool
	 */
	public static function loadExtensions( Parser &$parser ) {
		require_once "Extension.class.php";

		self::$extension_dir = dirname( __DIR__ ) . '/extensions';

		spl_autoload_register( "ExtensionFactory::autoload" );

		$handles = glob( self::$extension_dir . '/*.php' );
		if ( !is_array( $handles ) ) {
			return false;
		}

		foreach ( $handles as $extension ) {
			if ( is_file( $extension ) ) {
				self::loadExtension( $parser, $extension );
			}
		}

		return true;
	}

	/**
	 * @param Parser &$parser
	 * @param $extension
	 * @return bool
	 */
	private static function loadExtension( Parser &$parser, $extension ) {
		$class_file = basename( $extension );
		$class = pathinfo( $class_file, PATHINFO_FILENAME );

		// Is this actually an extension?
		if ( get_parent_class( $class ) !== "Extension" ) {
			return false;
		}

		$object = new $class();

		$parser_name = $object->getName();
		$parser_aliases = $object->getAliases();
		$parser_type = $object->getType();

		self::setHook( $parser, $class, $parser_name, $parser_aliases, $parser_type );

		return true;
	}

	/**
	 * @param Parser &$parser
	 * @param $class
	 * @param $parser_name
	 * @param array $parser_aliases
	 * @param $parser_type
	 */
	private static function setHook( Parser &$parser, $class, $parser_name, array $parser_aliases = [], $parser_type = 'normal' ) {
		if ( $parser_type === 'sfh' ) {
			$parser->setFunctionHook( $parser_name, [ $class, 'getResult' ], Parser::SFH_OBJECT_ARGS );
		} else {
			$parser->setFunctionHook( $parser_name, [ $class, 'getResult' ] );
		}

		if ( count( $parser_aliases ) > 0 ) {
			foreach ( $parser_aliases as $alias ) {
				if ( $parser_type === 'sfh' ) {
					$parser->setFunctionHook( $alias, [ $class, 'getResult' ], Parser::SFH_OBJECT_ARGS );
				} else {
					$parser->setFunctionHook( $alias, [ $class, 'getResult' ] );
				}
			}
		}
	}

	/**
	 * @param $class
	 */
	protected static function autoload( $class ) {
		$file = self::$extension_dir . '/' . $class . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
