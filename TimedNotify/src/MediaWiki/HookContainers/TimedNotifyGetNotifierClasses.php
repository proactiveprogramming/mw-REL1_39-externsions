<?php

namespace TimedNotify\MediaWiki\HookContainers;

/**
 * Interface for the "TimedNotifyGetNotifierClasses" hook.
 */
interface TimedNotifyGetNotifierClasses {
	/**
	 * Called when retrieving the notifier classes.
	 *
	 * @param string[] &$notifierClasses Array of class names that extend Notifier
	 * @return void
	 */
	public function onTimedNotifyGetNotifierClasses( array &$notifierClasses ): void;
}
