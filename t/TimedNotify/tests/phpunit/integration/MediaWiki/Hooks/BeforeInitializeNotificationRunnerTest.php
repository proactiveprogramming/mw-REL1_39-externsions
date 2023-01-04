<?php

namespace TimedNotify\Tests\Integration\MediaWiki\Hooks;

use MediaWikiIntegrationTestCase;
use TimedNotify\MediaWiki\Hooks\BeforeInitializeNotificationRunner;
use TimedNotify\NotificationRunner;

/**
 * @covers \TimedNotify\MediaWiki\Hooks\BeforeInitializeNotificationRunner
 */
class BeforeInitializeNotificationRunnerTest extends MediaWikiIntegrationTestCase {
	public function testHookCallsNotificationRunnerWithTimedNotifyRunDeferred(): void {
		$this->setMwGlobals( [ 'wgTimedNotifyRunDeferred' => false ] );

		$notificationRunnerMock = $this->getMockBuilder( NotificationRunner::class )
			->disableOriginalConstructor()
			->getMock();
		$notificationRunnerMock->expects( $this->once() )->method( 'runOccasionally' )->with( false );

		$runner = new BeforeInitializeNotificationRunner( $notificationRunnerMock );
		$runner->onBeforeInitialize( null, null, null, null, null, null );

		$this->setMwGlobals( [ 'wgTimedNotifyRunDeferred' => true ] );

		$notificationRunnerMock = $this->getMockBuilder( NotificationRunner::class )
			->disableOriginalConstructor()
			->getMock();
		$notificationRunnerMock->expects( $this->once() )->method( 'runOccasionally' )->with( true );

		$runner = new BeforeInitializeNotificationRunner( $notificationRunnerMock );
		$runner->onBeforeInitialize( null, null, null, null, null, null );
	}
}
