<?php

namespace TimedNotify;

use EchoEvent;
use User;

/**
 * This interface is implemented by all periodic page notifiers.
 */
abstract class Notifier {
	/**
	 * Returns the name of this notification.
	 *
	 * @return string
	 */
	abstract public function getName(): string;

	/**
	 * Returns the class name of the presentation model.
	 *
	 * @return string
	 */
	abstract public function getPresentationModel(): string;

	/**
	 * Returns an array of notifications that should be sent. A notification should have the following form:
	 *
	 * [
	 *     'id'    => (string) a unique identifier for this notification (will automatically be scoped to the notifier).
	 *                         The notification will only be emitted if a notification with this key has not already
	 *                         been emitted. If this value is omitted, the notification will be emitted
	 *                         unconditionally. (optional),
	 *     'data'  => (array)  additional data to add to the notification. (optional)
	 * ]
	 *
	 * @return array[]
	 */
	abstract public function getNotifications(): array;

	/**
	 * Returns the users that should be notified by the given event.
	 *
	 * @param EchoEvent $event The event to get the users for
	 * @return User[] The user(s) to notify
	 */
	abstract public static function getNotificationUsers( EchoEvent $event ): array;

	/**
	 * Returns additional icons to define.
	 *
	 * @return array
	 */
	public function getIcons(): array {
		return [];
	}

	/**
	 * Returns the list of users that should NOT be notified by this event.
	 *
	 * @param EchoEvent $event The event to get the filtered users for
	 * @return User[] The user(s) not to notify
	 */
	public static function getFilteredUsers( EchoEvent $event ): array {
		return [];
	}
}
