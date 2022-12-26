<?php

namespace BlueSpice\EchoConnector\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator;
use MWEchoNotifUser;

class Alert extends AttentionIndicator {

	protected function doIndicationCount(): int {
		$notifUser = MWEchoNotifUser::newFromUser( $this->user );
		return $notifUser->getAlertCount();
	}
}
