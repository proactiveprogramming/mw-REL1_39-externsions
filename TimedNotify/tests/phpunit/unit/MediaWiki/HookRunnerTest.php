<?php

namespace TimedNotify\Tests\Unit\MediaWiki;

use MediaWiki\HookContainer\HookContainer;
use MediaWikiUnitTestCase;
use TimedNotify\MediaWiki\HookRunner;

/**
 * @covers \TimedNotify\MediaWiki\HookRunner
 */
class HookRunnerTest extends MediaWikiUnitTestCase {
	public function testOnTimedNotifyGetNotifierClassesCallsHook(): void {
		$classes = [
			"Testing",
			"Is",
			"A",
			"Virtue"
		];

		$hookContainerMock = $this->getMockBuilder( HookContainer::class )
			->disableOriginalConstructor()
			->getMock();
		$hookContainerMock
			->expects( $this->once() )
			->method( 'run' )
			->with( 'TimedNotifyGetNotifierClasses', [ $classes ] );

		$hookRunner = new HookRunner( $hookContainerMock );
		$hookRunner->onTimedNotifyGetNotifierClasses( $classes );
	}
}
