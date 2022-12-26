<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Batch;

use MediaWiki\User\UserIdentity;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class BatchList {
	/** @var string */
	private $name;
	/** @var UserIdentity */
	private $owner;

	public function __construct(
		string $name,
		UserIdentity $owner
	) {
		$this->name = $name;
		$this->owner = $owner;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getOwner(): UserIdentity {
		return $this->owner;
	}
}
