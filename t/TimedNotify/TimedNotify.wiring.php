<?php

/**
 * This file is loaded by MediaWiki\MediaWikiServices::getInstance() during the
 * bootstrapping of the dependency injection framework.
 *
 * @file
 */

use MediaWiki\MediaWikiServices;
use TimedNotify\EchoEventCreator;
use TimedNotify\MediaWiki\HookRunner;
use TimedNotify\NotificationRunner;
use TimedNotify\NotifierStore;
use TimedNotify\PushedNotificationBucket;
use TimedNotify\TimedNotifyServices;

return [
	"TimedNotify.EchoEventCreator" => static function (): EchoEventCreator {
		return new EchoEventCreator();
	},
	"TimedNotify.HookRunner" => static function ( MediaWikiServices $services ): HookRunner {
		return new HookRunner( $services->getHookContainer() );
	},
	"TimedNotify.NotificationRunner" => static function ( MediaWikiServices $services ): NotificationRunner {
		return new NotificationRunner(
			TimedNotifyServices::getNotifierStore( $services ),
			TimedNotifyServices::getPushedNotificationBucket( $services ),
			TimedNotifyServices::getEchoEventCreator( $services ),
			$services->getMainConfig()->get( 'TimedNotifyRunRate' )
		);
	},
	"TimedNotify.NotifierStore" => static function ( MediaWikiServices $services ): NotifierStore {
		return new NotifierStore(
			TimedNotifyServices::getHookRunner( $services ),
			$services->getMainConfig()->get( 'TimedNotifyDisabledNotifiers' )
		);
	},
	"TimedNotify.PushedNotificationBucket" => static function (
		MediaWikiServices $services
	): PushedNotificationBucket {
		return new PushedNotificationBucket(
			$services->getDBLoadBalancer()->getConnection( DB_PRIMARY ),
			$services->getMainConfig()->get( 'TimedNotifyPushedNotificationRetentionDays' )
		);
	}
];
