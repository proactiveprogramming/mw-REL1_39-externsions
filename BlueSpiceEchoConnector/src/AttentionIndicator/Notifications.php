<?php

namespace BlueSpice\EchoConnector\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator\Collection;

class Notifications extends Collection {

	/**
	 * @return string[]
	 */
	protected function getSubIndicatorKeys(): array {
		return [
			'notifications-notice',
			'notifications-alert'
		];
	}

}
