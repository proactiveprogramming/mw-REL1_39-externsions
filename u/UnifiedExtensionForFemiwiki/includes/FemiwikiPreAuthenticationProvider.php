<?php

namespace MediaWiki\Extension\UnifiedExtensionForFemiwiki;

use MediaWiki\Auth\AbstractPreAuthenticationProvider;
use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthManager;
use Status;
use StatusValue;
use User;

class FemiwikiPreAuthenticationProvider extends AbstractPreAuthenticationProvider {

	/**
	 * @see AbstractPreAuthenticationProvider::getAuthenticationRequests()
	 *
	 * @param string $action
	 * @param array $options
	 * @return AuthenticationRequest[]
	 */
	public function getAuthenticationRequests( $action, array $options ) {
		if ( !$this->config->get( 'UnifiedExtensionForFemiwikiPreAuth' ) || $action != AuthManager::ACTION_CREATE ) {
			return [];
		}
		return [ new FemiwikiAuthenticationRequest() ];
	}

	/**
	 * @see AbstractPreAuthenticationProvider::testForAccountCreation()
	 *
	 * @param User $user
	 * @param User $creator
	 * @param AuthenticationRequest[] $reqs
	 * @return StatusValue
	 */
	public function testForAccountCreation( $user, $creator, array $reqs ) {
		if ( !$this->config->get( 'UnifiedExtensionForFemiwikiPreAuth' ) ) {
			return Status::newGood();
		}

		/** @var FemiwikiAuthenticationRequest $req */
		$req = AuthenticationRequest::getRequestByClass( $reqs, FemiwikiAuthenticationRequest::class );

		if ( self::testInternal( $req->femiwikiOpenSesame ) ) {
			return Status::newGood();
		}
		return Status::newFatal( 'unifiedextensionforfemiwiki-createaccount-fail' );
	}

	/**
	 * @param string $phrase
	 * @return bool
	 */
	private static function testInternal( $phrase ) {
		$phrase = strtolower( $phrase );
		$pattern = '/.*[나저]는\s*페미니스트\s*(?:입니다|이?다).*'
			. '|.*i(?:\s*am|\'m)\s*(?:an?\s*)?feminist.*/u';
		return preg_match( $pattern, $phrase ) !== 0;
	}
}
