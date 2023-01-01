<?php

namespace TimedNotify\Tests\Unit;

use MediaWikiUnitTestCase;
use TimedNotify\MediaWiki\HookRunner;
use TimedNotify\NotifierStore;
use TimedNotify\Tests\Helpers\TestNotifier1;
use TimedNotify\Tests\Helpers\TestNotifier2;

/**
 * @covers \TimedNotify\NotifierStore
 */
class NotifierStoreTest extends MediaWikiUnitTestCase {
	public function setUp(): void {
		require_once __DIR__ . "/../helpers/TestNotifier1.php";
		require_once __DIR__ . "/../helpers/TestNotifier2.php";

		$this->hookRunner = $this->getMockBuilder( HookRunner::class )->disableOriginalConstructor()->getMock();
		$this->hookRunner->method( 'onTimedNotifyGetNotifierClasses' )->willReturnCallback(
			function ( array &$notifierClasses ) {
				$notifierClasses[] = TestNotifier1::class;
				$notifierClasses[] = TestNotifier2::class;
			}
		);
	}

	public function testDisabledNotifiersAreNotReturnedByGetNotifiers() {
		$notifierStore = new NotifierStore( $this->hookRunner, [
			"TestNotifier1" => true,
			"TestNotifier2" => true
		] );

		$this->assertEmpty( $notifierStore->getNotifiers() );
	}

	public function testNotDisabledIsReturnedByGetNotifiers() {
		$notifierStore = new NotifierStore( $this->hookRunner, [
			TestNotifier1::class => false
		] );

		$this->assertArrayEquals( $notifierStore->getNotifiers(), [
			new TestNotifier1(),
			new TestNotifier2()
		] );
	}

	public function testInstancesAreCached() {
		$notifierStore = new NotifierStore( $this->hookRunner, [] );
		$notifiers = $notifierStore->getNotifiers();

		$this->assertSame( $notifiers, $notifierStore->getNotifiers() );
	}
}
