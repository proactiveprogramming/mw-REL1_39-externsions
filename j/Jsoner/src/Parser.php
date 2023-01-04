<?php

namespace jsoner;

use jsoner\exceptions\ParserException;

class Parser
{
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( $config ) {
		$this->config = $config;
	}

	/**
	 * @param $json_as_string String A JSON object as string
	 * @return mixed A PHP array structure that is equivalent to the provided JSON.
	 * @throws ParserException If $json_as_string is invalid JSON.
	 */
	public function parse( $json_as_string ) {
		$decoded_json = self::jsonDecode( $json_as_string );

		$errorKey = $this->config['Parser-ErrorKey'];
		if ( array_key_exists( $errorKey, $decoded_json ) ) {
			$error = $decoded_json[$errorKey];
			if ( is_array( $error ) ) {
				$error_message = implode( ', ', $error );
			} else {
				$error_message = $error;
			}

			throw new ParserException( $error_message, 42 );
		}

		return $decoded_json;
	}

	/**
	 * @return Config
	 */
	public function getConfig() {
		return $this->config;
	}

	public static function jsonDecode( $json_as_string ) {
		// Hide warning if there is one
		// See: http://stackoverflow.com/a/2348181/488265
		$decoded_json = @json_decode( $json_as_string, true );

		// PHP sucks
		if ( $decoded_json === null && json_last_error() !== JSON_ERROR_NONE ) {
			$error_message = json_last_error_msg();
			$error_code = json_last_error();
			throw new ParserException( $error_message, $error_code );
		}

		return $decoded_json;
	}
}
