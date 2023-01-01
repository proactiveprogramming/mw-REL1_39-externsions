<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater;

use BagOStuff;
use MediaWiki\OAuthClient\Token;
use MediaWiki\User\UserIdentity;
use Wikimedia\LightweightObjectStore\ExpirationAwareness;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class AccessTokenStore {
	/** @var BagOStuff */
	private $cache;

	public function __construct( BagOStuff $cache ) {
		$this->cache = $cache;
	}

	public function get( UserIdentity $u ): ?Token {
		$accessToken = null;
		$tokenString = $this->cache->get(
			$this->cache->makeKey( __CLASS__, $u->getId() )
		);

		if ( $tokenString ) {
			[ $key, $secret ] = explode( '|', $tokenString, 2 );
			$accessToken = new Token( $key, $secret );
		}
		return $accessToken;
	}

	public function set( UserIdentity $u, Token $token ) {
		$tokenString = $token->key . '|' . $token->secret;
		$this->cache->set(
			$this->cache->makeKey( __CLASS__, $u->getId() ),
			$tokenString,
			ExpirationAwareness::TTL_WEEK
		);
	}
}
