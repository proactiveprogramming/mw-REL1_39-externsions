<?php

namespace TimedNotify\MediaWiki;

use MediaWiki\HookContainer\HookContainer;
use TimedNotify\MediaWiki\HookContainers\TimedNotifyGetNotifierClasses;

/**
 * This class is responsible for running hooks defined by TimedNotify.
 */
class HookRunner implements TimedNotifyGetNotifierClasses {
	/**
	 * @var HookContainer
	 */
	private HookContainer $hookContainer;

	/**
	 * @param HookContainer $hookContainer
	 */
	public function __construct( HookContainer $hookContainer ) {
		$this->hookContainer = $hookContainer;
	}

	/**
	 * @inheritDoc
	 */
	public function onTimedNotifyGetNotifierClasses( array &$notifierClasses ): void {
		$this->hookContainer->run( 'TimedNotifyGetNotifierClasses', [ &$notifierClasses ] );
	}
}
