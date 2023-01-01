<?php

namespace TimedNotify\Tests\Integration;

use EchoEvent;
use MediaWikiIntegrationTestCase;
use TimedNotify\EchoEventCreator;

/**
 * @covers \TimedNotify\EchoEventCreator
 */
class EchoEventCreatorTest extends MediaWikiIntegrationTestCase {
	private EchoEventCreator $eventCreator;

	public function setUp(): void {
		$this->eventCreator = new EchoEventCreator();
	}

	public function testCreatePushesEchoEvent(): void {
		$event = $this->eventCreator->create( [
			'type' => 'mention-success'
		] );

		$this->assertInstanceOf( EchoEvent::class, $event );
		$this->assertTrue( $event->isEnabledEvent() );
	}
}
