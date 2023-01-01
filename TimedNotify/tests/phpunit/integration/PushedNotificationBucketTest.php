<?php

namespace TimedNotify\Tests\Integration;

use IDatabase;
use MediaWikiIntegrationTestCase;
use TimedNotify\PushedNotificationBucket;
use TimedNotify\TimedNotifyServices;

/**
 * @covers \TimedNotify\PushedNotificationBucket
 */
class PushedNotificationBucketTest extends MediaWikiIntegrationTestCase {
	private const RETENTION_TIME = 60;

	public function setUp(): void {
		$this->setMwGlobals( [ 'TimedNotifyPushedNotificationRetentionDays' => self::RETENTION_TIME ] );
		$this->pushedNotificationBucket = TimedNotifyServices::getPushedNotificationBucket();
	}

	public function tearDown(): void {
		wfGetDB( DB_PRIMARY )->delete( PushedNotificationBucket::PUSHED_NOTIFICATIONS_TABLE, IDatabase::ALL_ROWS );
	}

	public function testIsPushedOnlyAfterPush() {
		$this->assertFalse( $this->pushedNotificationBucket->isPushed( 'testing' ) );
		$this->pushedNotificationBucket->setPushed( 'testing' );
		$this->assertTrue( $this->pushedNotificationBucket->isPushed( 'testing' ) );
	}

	public function testOldDataIsPurged() {
		// Insert a record with a fake timestamp
		wfGetDB( DB_PRIMARY )->insert(
			PushedNotificationBucket::PUSHED_NOTIFICATIONS_TABLE, [
				'pushed_notification_id' => 'testingOld',
				'pushed_notification_timestamp' => time() - self::RETENTION_TIME * 60 * 60 * 24
			],
			__METHOD__
		);

		// Insert a record with the current timestamp
		$this->pushedNotificationBucket->setPushed( 'testingNew' );

		$this->assertTrue( $this->pushedNotificationBucket->isPushed( 'testingOld' ) );
		$this->assertTrue( $this->pushedNotificationBucket->isPushed( 'testingNew' ) );

		$this->pushedNotificationBucket->purgeOld();

		$this->assertFalse( $this->pushedNotificationBucket->isPushed( 'testingOld' ) );
		$this->assertTrue( $this->pushedNotificationBucket->isPushed( 'testingNew' ) );
	}
}
