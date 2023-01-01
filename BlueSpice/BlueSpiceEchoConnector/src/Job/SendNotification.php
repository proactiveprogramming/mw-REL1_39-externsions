<?php

namespace BlueSpice\EchoConnector\Job;

use MediaWiki\MediaWikiServices;
use Title;
use User;

class SendNotification extends \Job {

	/**
	 *
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		if ( $title instanceof Title ) {
			// parent::__construct Backwards compatibility for old signature
			// ($command, $title, $params) seems to have an issue retrieving the
			// actual correct data and ends up writing -1|FullText into the DB
			// ERM:24241
			$this->title = $title;
			$params['namespace'] = $title->getNamespace();
			$params['title'] = $title->getDBkey();
		}
		parent::__construct( 'sendNotification', $this->compressParams( $params ) );
	}

	public function run() {
		$this->expandParams();
		if ( $this->params['title'] instanceof Title && $this->params['agent'] instanceof User ) {
			\EchoEvent::create( $this->params );
		}
	}

	/**
	 * @param array $params
	 * @return array
	 */
	private function compressParams( $params ) {
		if ( isset( $params['agent'] ) && $params['agent'] instanceof User ) {
			$params['agent'] = $params['agent']->getId();
		}
		return $params;
	}

	private function expandParams() {
		if ( isset( $this->params['title'] ) && is_string( $this->params['title'] ) ) {
			$this->params['title'] = Title::newFromText( $this->params['title'], $this->params['namespace'] );
		}

		if ( isset( $this->params['agent'] ) && is_int( $this->params['agent'] ) ) {
			$userFactory = MediaWikiServices::getInstance()->getUserFactory();
			$this->params['agent'] = $userFactory->newFromId( $this->params['agent'] );
		}
	}
}
