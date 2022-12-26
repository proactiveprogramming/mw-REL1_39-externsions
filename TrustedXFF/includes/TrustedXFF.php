<?php

namespace MediaWiki\Extension\TrustedXFF;

use MediaWiki\Hook\IsTrustedProxyHook;
use Wikimedia\IPSet;

class TrustedXFF implements IsTrustedProxyHook {
	/**
	 * @var TrustedXFF
	 */
	private static $instance;

	/** @var IPSet */
	private $ipSet;

	/**
	 * @codeCoverageIgnore
	 * @param IPSet $set
	 */
	private function __construct( IPSet $set ) {
		$this->ipSet = $set;
	}

	/**
	 * @param string $ip
	 * @param bool &$trusted
	 */
	public function onIsTrustedProxy( $ip, &$trusted ) {
		// Don't want to override hosts that are already trusted
		if ( !$trusted ) {
			$trusted = $this->isTrusted( $ip );
		}
	}

	/**
	 * @codeCoverageIgnore
	 * @return TrustedXFF
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new TrustedXFF(
				IPSet::newFromJson(
					file_get_contents( dirname( __DIR__ ) . '/trusted-hosts.json' )
				)
			);
		}
		return self::$instance;
	}

	/**
	 * @param string $ip
	 * @return bool
	 */
	public function isTrusted( $ip ) {
		return $this->ipSet->match( $ip );
	}
}
