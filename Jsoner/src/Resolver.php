<?php

namespace jsoner;

use jsoner\exceptions\CurlException;
use jsoner\exceptions\HttpUriFormatException;
use League\Uri\Schemes\Http;

class Resolver
{
	private $config;
	private $queryOrFullUrl;

	/**
	 * Resolver constructor.
	 * @param \jsoner\Config $config
	 * @param string $queryOrFullUrl The part after the ? or a full URI
	 */
	public function __construct( $config , $queryOrFullUrl ) {
		$this->config = $config;
		$this->queryOrFullUrl = $queryOrFullUrl;
	}

	public function resolve() {
		$fullUrl = $this->buildUrl();

		$ch = curl_init();

		// Authenticate if User and Pass are provided
		$user = $this->config->getItem( "User" );
		$pass = $this->config->getItem( "Pass" );
		if ( $user != null && $pass != null ) {
			curl_setopt( $ch, CURLOPT_USERPWD, "$user:$pass" );
		}

		curl_setopt_array( $ch, [
			CURLOPT_URL => $fullUrl,
			CURLOPT_HTTPHEADER => ["Accept: application/json",],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_FAILONERROR => true,
			CURLOPT_QUOTE
		] );

		$response = curl_exec( $ch );
		$error_message = curl_error( $ch );
		$error_code = curl_errno( $ch );

		curl_close( $ch );

		# Error responses will now also be cached to prevent sending repeated requests
		# to a service that is already experiencing some kind of failure.
		# Adding more load to a struggeling service isn't helping.

		return $response;
	}

	/**
	 * Builds the URL that is used to resolve the JSON data.
	 * @return string A full URL
	 */
	public function buildUrl() {

		$baseUrl = $this->config['BaseUrl'];

		// Looks like a HTTP URI
		if ( strpos( trim( $this->queryOrFullUrl ), 'http' ) === 0 ) {
			$fullUrl = $this->queryOrFullUrl;
		} else {
			if ( $baseUrl === null ) {
				throw new HttpUriFormatException( 'You must set $jsonerBaseUrl.' );
			}
			$fullUrl = $baseUrl . $this->queryOrFullUrl;
		}

		$url = Http::createFromString( $fullUrl );
		return $url->__toString();
	}
}
