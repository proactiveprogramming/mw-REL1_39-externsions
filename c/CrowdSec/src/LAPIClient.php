<?php

/**
 * CrowdSec LAPI Client implementation using MediaWiki Service.
 *
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
 */

namespace MediaWiki\Extension\CrowdSec;

use FormatJson;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use RequestContext;
use Status;

class LAPIClient {
	/** @var mixed */
	private $error = null;
	/** @var BagOStuff */
	private $cache;
	/** @var \Psr\Log\LoggerInterface */
	private $logger;
	/** @var LAPIClient|null */
	protected static $instance = null;

	public function __construct() {
		$this->logger = LoggerFactory::getInstance( 'crowdsec' );
		$this->cache = ObjectCache::getLocalClusterInstance();
	}

	public static function singleton() {
		if ( self::$instance === null ) {
			self::$instance = new LAPIClient();
		}

		return self::$instance;
	}

	/**
	 * handle lapi url for safe.
	 * @param string $url
	 * @return string
	 */
	private static function apiUrlHandler( string $url ) {
		return str_ends_with( $url, "/" ) ? $url : $url . "/";
	}

	/**
	 * get decision from cache and lapi
	 * @param string $ip
	 * @return string
	 */
	public function getDecision( string $ip ) {
		global $wgCrowdSecCache, $wgCrowdSecCacheTTL;
		if ( !$wgCrowdSecCache ) {
			return $this->requestDecision( $ip );
		}

		$cacheKey = $this->getCacheKey( $ip );
		$result = $this->cache->get( $cacheKey );
		// if not found on cache
		if ( $result === false ) {
			$result = $this->requestDecision( $ip );
			$this->cache->set( $cacheKey, $result, $wgCrowdSecCacheTTL );
		}

		return $result;
	}

	/**
	 * request decision to local api
	 * @param string $ip
	 * @return string
	 */
	private function requestDecision( string $ip ) {
		global $wgCrowdSecAPIKey, $wgCrowdSecAPIUrl;

		$webRequest = RequestContext::getMain()->getRequest();

		$url = self::apiUrlHandler( $wgCrowdSecAPIUrl ) . 'v1/decisions?scope=ip&ip=' . $ip;
		$options = [
			'method' => 'GET',
		];

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $url, $options, __METHOD__ );
		$request->setHeader( 'Accept', 'application/json' );
		$request->setHeader( 'X-Api-Key', $wgCrowdSecAPIKey );

		$status = $request->execute();
		if ( !$status->isOK() ) {
			$this->error = 'http';
			$this->logError( $status );
			$this->log( $request->getContent() );
			return false;
		}

		$content = $request->getContent();
		if ( $content === "null" ) {
			return "ok";
		}

		$response = FormatJson::decode( $request->getContent(), true );
		if ( !$response ) {
			$this->error = 'json';
			$this->logError( $this->error );
			return false;
		}
		if ( !isset( $response[0] ) || !isset( $response[0]['type'] ) ) {
			$this->error = 'crowdsec-lapi';
			$this->logError( $request->getContent() );
			return false;
		}

		return $response[0]['type'];
	}

	/**
	 * log error
	 * @param mixed $info
	 */
	private function logError( $info ): void {
		if ( $info instanceof Status ) {
			$errors = $info->getErrorsArray();
			$error = $errors[0][0];
		} elseif ( is_array( $info ) ) {
			$error = json_encode( $info );
		} else {
			$error = $info;
		}

		$this->logger->error( 'Unable to validate response: {error}', [ 'error' => $error ] );
	}

	/**
	 * log debug
	 * @param mixed $info
	 */
	private function log( $info ): void {
		$this->logger->debug( $info );
	}

	/**
	 * Get cache key for ip
	 * @param string $ip
	 * @return string
	 */
	protected function getCacheKey( $ip ) {
		return $this->cache->makeKey( 'CrowdSecLocalAPI', 'decision', $ip );
	}
}
