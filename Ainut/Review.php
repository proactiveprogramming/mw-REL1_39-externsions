<?php
/**
 * Application form.
 *
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

namespace Ainut;

class Review {
	/** @var int The application id for saved reviews. */
	protected $id;
	/** @var int User id who made the review. */
	protected $user;
	/** @var int Timestamp when the review was saved. */
	protected $timestamp;
	/** @var int Application number this review is for. */
	protected $appId;
	/** @var array Application fields and values. */
	protected $fields;

	public function __construct( int $user, int $appId ) {
		$this->user = $user;
		$this->appId = $appId;
	}

	public function getId(): ?int {
		return $this->id;
	}

	public function setId( int $x ) {
		$this->id = $x;
	}

	public function getUser(): int {
		return $this->user;
	}

	public function getTimestamp(): int {
		$this->timestamp = $this->timestamp ?: time();

		return $this->timestamp;
	}

	public function setTimestamp( int $x ) {
		$this->timestamp = $x;
	}

	public function getApplicationId(): int {
		return $this->appId;
	}

	public function getFields(): array {
		return $this->fields ?: [];
	}

	public function setFields( array $x ) {
		$this->fields = $x;
	}
}
