<?php
/**
 * Application form.
 *
 * @file
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

namespace Ainut;

class Application {
	/** @var int The application id for saved applications. */
	protected $id;
	/** @var int User id who made the application. */
	protected $user;
	/** @var int Timestamp when the application was saved. */
	protected $timestamp;
	/** @var string Access code. Maximum length is 10 bytes. */
	protected $code;
	/** @var int Revision number. */
	protected $revision;
	/** @var string Title of the application. */
	protected $title;
	/** @var array Application fields and values. */
	protected $fields;

	public function __construct( int $user ) {
		$this->user = $user;
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

	public function getRevision(): int {
		return $this->revision ?: 0;
	}

	public function setRevision( int $x ) {
		$this->revision = $x;
	}

	public function getCode(): string {
		$this->code = $this->code ?: bin2hex( random_bytes( 5 ) );

		return $this->code;
	}

	public function setCode( string $x ) {
		$this->code = $x;
	}

	public function getFields(): array {
		return $this->fields ?: [];
	}

	public function setFields( array $x ) {
		$this->fields = $x;
	}
}
