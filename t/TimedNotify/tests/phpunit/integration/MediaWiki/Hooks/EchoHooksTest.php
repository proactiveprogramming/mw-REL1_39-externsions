<?php

namespace TimedNotify\Tests\Integration\MediaWiki\Hooks;

use MediaWikiIntegrationTestCase;
use TimedNotify\MediaWiki\Hooks\EchoHooks;
use TimedNotify\Notifier;
use TimedNotify\NotifierStore;

/**
 * @covers \TimedNotify\MediaWiki\Hooks\EchoHooks
 */
class EchoHooksTest extends MediaWikiIntegrationTestCase {
	public function testTimeBasedNotificationCategoryIsAdded(): void {
		$notifications = [];
		$notificationCategories = [];
		$icons = [];

		EchoHooks::onBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );

		$this->assertArrayEquals( [
			'timednotify-time-based-notification' => [
				'priority' => 2,
				'tooltip' => 'echo-pref-tooltip-timednotify-time-based-notification'
			]
		], $notificationCategories );
	}

	public function testClockIconIsAdded(): void {
		$notifications = [];
		$notificationCategories = [];
		$icons = [];

		EchoHooks::onBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );

		$this->assertArrayEquals( [
			'timednotify-clock' => [
				'path' => 'TimedNotify/modules/icons/clock.svg'
			]
		], $icons );
	}

	public function testNotifiersAreRegistered(): void {
		$notifierMock = $this->getMockBuilder( Notifier::class )->getMock();
		$notifierMock->method( 'getName' )->willReturn( 'TestNotifier' );
		$notifierMock->method( 'getIcons' )->willReturn( [
			'fake-icon' => [
				'path' => 'fake/path'
			]
		] );
		$notifierMock->method( 'getPresentationModel' )->willReturn( 'TestNotifierPresentationModel' );

		$this->overrideMwServices( null, [
			'TimedNotify.NotifierStore' => function () use ( $notifierMock ) {
				$notifierStoreMock = $this->getMockBuilder( NotifierStore::class )
					->disableOriginalConstructor()
					->getMock();
				$notifierStoreMock->method( 'getNotifiers' )->willReturn( [ $notifierMock ] );

				return $notifierStoreMock;
			}
		] );

		$notifications = [];
		$notificationCategories = [];
		$icons = [];

		EchoHooks::onBeforeCreateEchoEvent( $notifications, $notificationCategories, $icons );

		$this->assertArrayEquals( [
			'TestNotifier' => [
				'category' => 'timednotify-time-based-notification',
				'section' => 'alert',
				'group' => 'neutral',
				'presentation-model' => 'TestNotifierPresentationModel',
				'user-locators' => [ get_class( $notifierMock ) . '::getNotificationUsers' ],
				'user-filters' => [ get_class( $notifierMock ) . '::getFilteredUsers' ]
			]
		], $notifications );

		$this->assertArrayEquals( [
			'fake-icon' => [
				'path' => 'fake/path'
			],
			'timednotify-clock' => [
				'path' => 'TimedNotify/modules/icons/clock.svg'
			]
		], $icons );
	}
}
