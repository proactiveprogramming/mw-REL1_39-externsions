<?php

namespace BlueSpice\EchoConnector\Notification;

use BlueSpice\BaseNotification;

class TitleMoveNotification extends BaseNotification {
	/**
	 * @var \Title
	 */
	protected $oldTitle;

	/**
	 * @var string
	 */
	protected $reason;

	/**
	 *
	 * @param \User $agent
	 * @param \Title $title
	 * @param \Title $oldTitle
	 * @param string $reason
	 */
	public function __construct( $agent, $title, $oldTitle, $reason ) {
		parent::__construct( 'bs-move', $agent, $title );

		$this->oldTitle = $oldTitle;
		$this->reason = $reason;
	}

	/**
	 *
	 * @return array
	 */
	public function getParams() {
		return [
			'oldtitle' => $this->oldTitle,
			'realname' => $this->getUserRealName(),
			'movereason' => $this->reason,
			'time' => $this->title->getTouched()
		];
	}
}
