<?php declare( strict_types=1 );
/**
 * Semantic Structured Discussions MediaWiki extension
 * Copyright (C) 2022  Wikibase Solutions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace SemanticStructuredDiscussions\StructuredDiscussions;

use ApiMain;
use DerivativeRequest;
use Exception;
use FauxRequest;
use MediaWiki\Session\SessionManager;
use MWException;
use RequestContext;

trait CallSubmoduleTrait {
	/**
	 * Calls the specified submodule of the StructuredDiscussions API and returns the result.
	 *
	 * @param string $submodule The submodule to call
	 * @param array $parameters The parameters with which to call the submodule
	 * @param bool $wasPosted Whether to use a POST request or not
	 * @return array|null The result, or NULL on failure
	 */
	private function callSubmodule( string $submodule, array $parameters, bool $wasPosted = false ): ?array {
		$parameters = [ 'action' => 'flow', 'submodule' => $submodule ] + $parameters;
		$baseRequest = RequestContext::getMain()->getRequest();
		$derivativeRequest = new DerivativeRequest( $baseRequest, $parameters, $wasPosted );

		$module = new ApiMain( $derivativeRequest, false );

		try {
			$module->execute();
		} catch ( Exception $exception ) {
			return null;
		}

		return $module->getResult()->getResultData( [ 'flow', $submodule, 'result' ], [ 'Strip' => 'all' ] );
	}
}
