<?php

namespace TimedNotify;

use IDatabase;

/**
 * This class is responsible for the interaction with the pushed notifications table, which is used to
 * store which notifications have been pushed so that notifications are not pushed more than once.
 */
class PushedNotificationBucket {
	public const PUSHED_NOTIFICATIONS_TABLE = 'timednotify_pushed_notifications';

	/**
	 * @var IDatabase The database connection
	 */
	private IDatabase $database;

	/**
	 * @var int After how many days a record is allowed to be purged
	 */
	private int $purgeOlder;

	/**
	 * @param IDatabase $database The database connection to use
	 * @param int $purgeOlder After how many days a record is allowed to be purged
	 */
	public function __construct( IDatabase $database, int $purgeOlder ) {
		$this->database = $database;
		$this->purgeOlder = $purgeOlder;
	}

	/**
	 * Add this notification to the pushed notifications table.
	 *
	 * @param string $notificationId
	 * @return void
	 */
	public function setPushed( string $notificationId ): void {
		$this->database->insert(
			self::PUSHED_NOTIFICATIONS_TABLE,
			[
				'pushed_notification_id' => $notificationId,
				'pushed_notification_timestamp' => time()
			],
			__METHOD__
		);
	}

	/**
	 * Returns true if the given notification has already been pushed.
	 *
	 * @param string $notificationId
	 * @return bool
	 */
	public function isPushed( string $notificationId ): bool {
		return $this->database
			->newSelectQueryBuilder()
			->select( [ 'pushed_notification_id' ] )
			->from( self::PUSHED_NOTIFICATIONS_TABLE )
			->where( [ 'pushed_notification_id' => $notificationId ] )
			->caller( __METHOD__ )
			->fetchRow() !== false;
	}

	/**
	 * Purge old records from the pushed notifications table.
	 *
	 * @param int|null $purgeOlder Purge records older than the given number of days; leave NULL to use the default
	 *  specified through $wgTimedNotifyPushedNotificationRetentionDays.
	 * @return void
	 */
	public function purgeOld( ?int $purgeOlder = null ): void {
		// Purge everything before this Unix timestamp
		$purgeBefore = time() - ( 60 * 60 * 24 * ( $purgeOlder ?? $this->purgeOlder ) );

		$this->database->delete(
			self::PUSHED_NOTIFICATIONS_TABLE,
			[ 'pushed_notification_timestamp <= ' . $purgeBefore ],
			__METHOD__
		);
	}
}
