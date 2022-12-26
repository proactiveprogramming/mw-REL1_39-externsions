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

use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Toolhub API client.
 *
 * @copyright © 2022 Wikimedia Foundation and contributors
 */
class ApiClient implements LoggerAwareInterface {

	/** @var HttpRequestFactory */
	private $requestFactory;
	/** @var string */
	private $baseUrl;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * Constructor.
	 *
	 * @param HttpRequestFactory $requestFactory
	 * @param string $baseUrl
	 */
	public function __construct(
		HttpRequestFactory $requestFactory,
		string $baseUrl
	) {
		$this->requestFactory = $requestFactory;
		$this->baseUrl = $baseUrl;
		$this->logger = new NullLogger;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger( LoggerInterface $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Build a query string from an array of key => value pairs.
	 *
	 * @param array $params
	 * @return string URL encoded query string
	 */
	public static function makeQueryString( array $params ): string {
		$params = array_filter(
			$params,
			static function ( $v ) {
				return $v !== null;
			}
		);
		ksort( $params );
		return http_build_query( $params );
	}

	/**
	 * Perform an HTTP request.
	 *
	 * @param string $verb HTTP verb
	 * @param string $url Full URL including query string if needed
	 * @param array $opts See HttpRequestFactory::create
	 * @param string $caller The method making this request, for profiling
	 * @return array Response data
	 */
	public function makeApiCall(
		string $verb,
		string $url,
		array $opts,
		string $caller
	): array {
		// FIXME: add defaults to $opts:
		// Accept header
		// Content-Type header
		// Accept-Language header?
		// User-Agent header
		$resp = $this->requestFactory->request( $verb, $url, $opts, $caller );
		if ( $resp === null ) {
			// FIXME: what should really happen here?
			return [ 'error' => 'Got a null response so BOOM!' ];
		}
		return json_decode( $resp, true );
	}

	/**
	 * Get info for a specific tool.
	 *
	 * @param string $name Name of the tool
	 * @return array
	 */
	public function getToolByName( string $name ): array {
		$escName = urlencode( $name );
		$req = "{$this->baseUrl}/api/tools/{$escName}/";
		return $this->makeApiCall( 'GET', $req, [], __METHOD__ );
	}

	/**
	 * Get info for a specific list.
	 *
	 * @param int $id List id
	 * @return array
	 */
	public function getListById( int $id ): array {
		$req = "{$this->baseUrl}/api/lists/{$id}/";
		return $this->makeApiCall( 'GET', $req, [], __METHOD__ );
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
		$qs = self::makeQueryString(
			[
				'q' => $query,
				'ordering' => '-score',
				'page' => $page,
				'page_size' => $pageSize,
			]
		);
		$req = "{$this->baseUrl}/api/search/tools/?{$qs}";
		return $this->makeApiCall( 'GET', $req, [], __METHOD__ );
	}
}
