<?php
declare( strict_types = 1 );
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\Toolhub;

use array_unshift;
use is_array;
use Scribunto_LuaLibraryBase;

/**
 * Toolhub API integration for Scribunto.
 *
 * @copyright © 2022 Wikimedia Foundation and contributors
 */
class CoreLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * Register the library.
	 *
	 * @return array Lua package
	 */
	public function register(): array {
		$lib = [
			'getTool' => [ $this, 'getTool' ],
			'getList' => [ $this, 'getList' ],
			'findTools' => [ $this, 'findTools' ],
		];
		$settings = [];
		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.ext.toolhub.lua',
			$lib,
			$settings
		);
	}

	/**
	 * Cast a value to a Lua compatible form.
	 *
	 * @param mixed $val
	 * @return array
	 */
	private function toLua( $val ): array {
		// Return as an array to match Lua semantics of multiple return values
		return [ $this->asLuaValue( $val ) ];
	}

	/**
	 * Convert a PHP value to a Lua compatible form.
	 *
	 * @param mixed $val
	 * @return mixed
	 */
	private function asLuaValue( $val ) {
		$type = $this->getLuaType( $val );
		if (
			$type === 'nil' ||
			$type === 'function' ||
			// PHP type without direct Lua mapping
			preg_match( '/^PHP .*/', $type )
		) {
			// Strip out things that have no native Lua representation
			return null;
		}
		if ( is_array( $val ) ) {
			foreach ( $val as $key => $value ) {
				$val[ $key ] = $this->asLuaValue( $value );
			}
			// Make arrays be 1-based (Lua) rather than 0-based (PHP) by
			// prepending a new [0] element and then deleting it.
			array_unshift( $val, '' );
			unset( $val[0] );
		}
		return $val;
	}

	/**
	 * Get info for a specific tool.
	 *
	 * @param string $name Name of the tool
	 * @return array
	 */
	public function getTool( string $name ): array {
		$this->checkType( 'getTool', 1, $name, 'string' );
		$api = ToolhubServices::getApiClient();
		$resp = $api->getToolByName( $name );
		// FIXME: cache non-negative results
		return $this->toLua( $resp );
	}

	/**
	 * Get info for a specific list.
	 *
	 * @param int $id List id
	 * @return array
	 */
	public function getList( int $id ): array {
		$this->checkType( 'getList', 1, $id, 'number' );
		$api = ToolhubServices::getApiClient();
		$resp = $api->getListById( $id );
		// FIXME: cache non-negative results
		return $this->toLua( $resp );
	}

	/**
	 * Search for tools.
	 *
	 * @param ?string $query User provided query
	 * @param int $page Result page
	 * @param int $pageSize Number of tools per page
	 * @return array
	 */
	public function findTools(
		?string $query = null,
		int $page = 1,
		int $pageSize = 25
	): array {
		$this->checkTypeOptional( 'findTools', 1, $query, 'string', null );
		$this->checkType( 'findTools', 2, $page, 'number' );
		$this->checkType( 'findTools', 3, $pageSize, 'number' );
		$api = ToolhubServices::getApiClient();
		$resp = $api->findTools( $query, $page, $pageSize );
		// FIXME: cache non-negative results
		return $this->toLua( $resp );
	}
}
