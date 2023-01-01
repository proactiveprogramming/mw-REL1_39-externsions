<?php

namespace TimedNotify\Tests\Helpers;

use BadMethodCallException;
use EchoEvent;
use TimedNotify\Notifier;

// phpcs:disable

class TestNotifier2 extends Notifier {
	public function getName(): string {
		return "TestNotifier1";
	}

	public function getPresentationModel(): string {
		throw new BadMethodCallException();
	}

	public function getIcons(): array {
		return [];
	}

	public function getNotifications(): array {
		return [];
	}

	public static function getNotificationUsers( EchoEvent $event ): array {
		return [];
	}
}
