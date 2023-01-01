<?php

namespace BlueSpice\EchoConnector\Hook\PageMoveComplete;

use BlueSpice\EchoConnector\Notification\TitleMoveNotification;
use BlueSpice\Hook\PageMoveComplete;
use MediaWiki\MediaWikiServices;
use Title;

class NotifyUsers extends PageMoveComplete {
	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return $this->getServices()->getPermissionManager()->userHasRight(
			$this->userIdentity,
			'bot'
		);
	}

	protected function doProcess() {
		$services = MediaWikiServices::getInstance();
		$notificationsManager = $services->getService( 'BSNotificationManager' );

		$new = Title::newFromLinkTarget( $this->new );
		$old = Title::newFromLinkTarget( $this->old );
		$notifier = $notificationsManager->getNotifier();
		$user = $services->getUserFactory()->newFromUserIdentity( $this->userIdentity );
		$notification = new TitleMoveNotification(
			$user,
			$new,
			$old,
			$this->reason
		);
		$notifier->notify( $notification );

		return true;
	}
}
