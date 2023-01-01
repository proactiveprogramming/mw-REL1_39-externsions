<?php

namespace TimedNotify\Tests\Integration;

use MediaWikiIntegrationTestCase;
use TimedNotify\EchoEventCreator;
use TimedNotify\MediaWiki\HookRunner;
use TimedNotify\NotificationRunner;
use TimedNotify\NotifierStore;
use TimedNotify\PushedNotificationBucket;
use TimedNotify\TimedNotifyServices;

/**
 * @covers \TimedNotify\TimedNotifyServices
 */
class TimedNotifyServicesTest extends MediaWikiIntegrationTestCase {
	public function testGetEchoEventCreator(): void {
		$this->assertInstanceOf( EchoEventCreator::class, TimedNotifyServices::getEchoEventCreator() );
	}

	public function testGetHookRunner(): void {
		$this->assertInstanceOf( HookRunner::class, TimedNotifyServices::getHookRunner() );
	}

	public function testGetNotificationRunner(): void {
		$this->assertInstanceOf( NotificationRunner::class, TimedNotifyServices::getNotificationRunner() );
	}

	public function testGetNotifierStore(): void {
		$this->assertInstanceOf( NotifierStore::class, TimedNotifyServices::getNotifierStore() );
	}

	public function testGetPushedNotificationBucket(): void {
		$this->assertInstanceOf( PushedNotificationBucket::class, TimedNotifyServices::getPushedNotificationBucket() );
	}
}
