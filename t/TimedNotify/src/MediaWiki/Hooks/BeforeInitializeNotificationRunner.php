<?php

namespace TimedNotify\MediaWiki\Hooks;

use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\MediaWikiServices;
use MWException;
use TimedNotify\NotificationRunner;

/**
 * This class is responsible for running the notifications during the initialisation of the wiki.
 */
class BeforeInitializeNotificationRunner implements BeforeInitializeHook {
	/**
	 * @var NotificationRunner The notification runner to use
	 */
	private NotificationRunner $notificationRunner;

	/**
	 * @param NotificationRunner $notificationRunner The notification runner to use
	 */
	public function __construct( NotificationRunner $notificationRunner ) {
		$this->notificationRunner = $notificationRunner;
	}

	/**
	 * @inheritDoc
	 * @throws MWException
	 */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediaWiki ): void {
		$this->notificationRunner->runOccasionally(
			MediaWikiServices::getInstance()->getMainConfig()->get( 'TimedNotifyRunDeferred' )
		);
	}
}
