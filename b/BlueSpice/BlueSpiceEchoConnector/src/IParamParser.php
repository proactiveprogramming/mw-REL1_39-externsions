<?php

namespace BlueSpice\EchoConnector;

use Language;
use User;

interface IParamParser {

	/**
	 *
	 * @param \EchoEvent $event
	 * @param string $distributionType
	 * @param User|null $user
	 * @param Language|null $language
	 */
	public function __construct( \EchoEvent $event, $distributionType, $user, $language );

	/**
	 * Receives param name and determines value
	 * for given param based on Event data and
	 * sets the value to \Message object
	 * @param \Message $message
	 * @param array $param
	 */
	public function parseParam( \Message $message, $param );
}
