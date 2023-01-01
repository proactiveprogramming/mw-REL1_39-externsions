<?php

namespace TimedNotify;

use EchoEvent;
use MWException;

class EchoEventCreator {
	/**
	 * Wrapper for EchoEvent::create(...).
	 *
	 * @param array $data
	 * @return EchoEvent|false
	 * @throws MWException
	 */
	public function create( array $data ) {
		return EchoEvent::create( $data );
	}
}
