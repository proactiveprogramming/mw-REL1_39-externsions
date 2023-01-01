<?php

namespace TimedNotify;

use MediaWiki\MediaWikiServices;
use TimedNotify\MediaWiki\HookRunner;
use Wikimedia\Services\ServiceContainer;

/**
 * Getter for all TimedNotify services. This class reduces the risk of mistyping
 * a service name and serves as the interface for retrieving services for TimedNotify.
 *
 * @note Program logic should use dependency injection instead of this class whenever
 * possible.
 *
 * @note This class should only contain static methods.
 */
final class TimedNotifyServices {
	/**
	 * Disable the construction of this class by making the constructor private.
	 */
	private function __construct() {
	}

	/**
	 * Returns the EchoEventCreator service.
	 *
	 * @param ServiceContainer|null $services
	 * @return EchoEventCreator
	 */
	public static function getEchoEventCreator( ?ServiceContainer $services = null ): EchoEventCreator {
		return self::getService( "EchoEventCreator", $services );
	}

	/**
	 * Returns the HookRunner service.
	 *
	 * @param ServiceContainer|null $services
	 * @return HookRunner
	 */
	public static function getHookRunner( ?ServiceContainer $services = null ): HookRunner {
		return self::getService( "HookRunner", $services );
	}

	/**
	 * Returns the NotificationRunner service.
	 *
	 * @param ServiceContainer|null $services
	 * @return NotificationRunner
	 */
	public static function getNotificationRunner( ?ServiceContainer $services = null ): NotificationRunner {
		return self::getService( "NotificationRunner", $services );
	}

	/**
	 * Returns the NotifierStore service.
	 *
	 * @param ServiceContainer|null $services
	 * @return NotifierStore
	 */
	public static function getNotifierStore( ?ServiceContainer $services = null ): NotifierStore {
		return self::getService( "NotifierStore", $services );
	}

	/**
	 * Returns the PushedNotificationBucket service.
	 *
	 * @param ServiceContainer|null $services
	 * @return PushedNotificationBucket
	 */
	public static function getPushedNotificationBucket( ?ServiceContainer $services = null ): PushedNotificationBucket {
		return self::getService( "PushedNotificationBucket", $services );
	}

	/**
	 * Returns the service with the given name.
	 *
	 * @param string $service
	 * @param ServiceContainer|null $services
	 * @return mixed
	 */
	private static function getService( string $service, ?ServiceContainer $services ) {
		return ( $services ?: MediaWikiServices::getInstance() )->getService( "TimedNotify.$service" );
	}
}
