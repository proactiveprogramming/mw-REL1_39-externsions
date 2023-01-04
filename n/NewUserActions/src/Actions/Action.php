<?php

namespace MediaWiki\Extension\NewUserActions\Actions;

use User;

abstract class Action {
	/**
	 * The newly created user
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * @param User $user
	 */
	public function __construct( User $user ) {
		$this->user = $user;
	}

	abstract public function run();
}
