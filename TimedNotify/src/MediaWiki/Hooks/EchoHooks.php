<?php

namespace TimedNotify\MediaWiki\Hooks;

use TimedNotify\Notifier;
use TimedNotify\TimedNotifyServices;

/**
 * Hook handler for Echo hooks implemented by TimedNotify.
 */
class EchoHooks {
	/**
	 * Called when the events for Echo are retrieved and created.
	 *
	 * @param array &$notifications
	 * @param array &$notificationCategories
	 * @param array &$icons
	 * @return void
	 */
	public static function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	): void {
		foreach ( TimedNotifyServices::getNotifierStore()->getNotifiers() as $notifier ) {
			$notifications[$notifier->getName()] = self::createDefinition( $notifier );
			$icons = array_merge( $icons, $notifier->getIcons() );
		}

		$notificationCategories['timednotify-time-based-notification'] = [
			'priority' => 2,
			'tooltip' => 'echo-pref-tooltip-timednotify-time-based-notification'
		];

		$icons['timednotify-clock'] = [
			'path' => 'TimedNotify/modules/icons/clock.svg'
		];
	}

	/**
	 * Creates a notification definition for the given notifier.
	 *
	 * @param Notifier $notifier The notifier class to get the definition for
	 * @return array
	 */
	private static function createDefinition( Notifier $notifier ): array {
		$definition = [
			'category' => 'timednotify-time-based-notification',
			'section' => 'alert',
			'group' => 'neutral'
		];

		$definition['presentation-model'] = $notifier->getPresentationModel();
		$definition['user-locators'] = [ get_class( $notifier ) . '::getNotificationUsers' ];
		$definition['user-filters'] = [ get_class( $notifier ) . '::getFilteredUsers' ];

		return $definition;
	}
}
