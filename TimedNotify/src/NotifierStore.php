<?php

namespace TimedNotify;

use TimedNotify\MediaWiki\HookRunner;

class NotifierStore {
	/**
	 * @var HookRunner The hook runner to use
	 */
	private HookRunner $hookRunner;

	/**
	 * @var <string bool>[] Array of notifiers names that are disabled
	 */
	private array $disabledNotifiers;

	/**
	 * @var Notifier[] Cache of instantiated notifiers
	 */
	private array $notifierInstancesCache;

	/**
	 * @param HookRunner $hookRunner The hook runner to use
	 * @param <string, bool>[] $disabledNotifiers Array of notifiers that are disabled
	 */
	public function __construct( HookRunner $hookRunner, array $disabledNotifiers = [] ) {
		$this->hookRunner = $hookRunner;
		$this->disabledNotifiers = $disabledNotifiers;
	}

	/**
	 * Returns instances of notifiers.
	 *
	 * @return Notifier[]
	 */
	public function getNotifiers(): array {
		if ( isset( $this->notifierInstancesCache ) ) {
			return $this->notifierInstancesCache;
		}

		$this->notifierInstancesCache = [];

		foreach ( $this->getNotifierClasses() as $notifierClass ) {
			$instance = new $notifierClass();
			$disabled = $this->disabledNotifiers[$instance->getName()] ?? false;

			if ( !$disabled ) {
				$this->notifierInstancesCache[] = $instance;
			}
		}

		return $this->notifierInstancesCache;
	}

	/**
	 * Returns an array of notifier class names.
	 *
	 * @return array
	 */
	private function getNotifierClasses(): array {
		$notifierClasses = [];
		$this->hookRunner->onTimedNotifyGetNotifierClasses( $notifierClasses );

		return $notifierClasses;
	}
}
