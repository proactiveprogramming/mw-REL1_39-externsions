<?php

namespace BlueSpice\EchoConnector\AttentionIndicator;

use BlueSpice\Discovery\AttentionIndicator;
use MWEchoNotifUser;

class Notice extends AttentionIndicator {

	protected function doIndicationCount(): int {
		$notifUser = MWEchoNotifUser::newFromUser( $this->user );
		return $notifUser->getMessageCount();
	}

}
