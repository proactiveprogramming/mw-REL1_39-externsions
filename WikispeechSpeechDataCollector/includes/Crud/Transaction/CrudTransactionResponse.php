<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 *
 * A collection of {@link Persistent} objects that have either
 * been created, read, update or deleted via {@link CrudTransactionExecutor}.
 *
 * @since 0.1.0
 */
class CrudTransactionResponse {

	/**
	 * @var mixed|null Client reference
	 * @see CrudTransactionRequest::$reference
	 */
	private $reference;

	/** @var array|null {@link Persistent} instances with no identity set */
	private $created;

	/** @var array|null {@link Persistent} instances */
	private $read;

	/** @var array|null {@link Persistent} instances */
	private $updated;

	/** @var array|null {@link Persistent} instances with identity only set */
	private $deleted;

	// Add-helpers

	/** @param Persistent $instance */
	public function addCreated( Persistent $instance ) {
		if ( $this->created === null ) {
			$this->created = [];
		}
		array_push( $this->created, $instance );
	}

	/** @param Persistent $instance */
	public function addRead( Persistent $instance ) {
		if ( $this->read === null ) {
			$this->read = [];
		}
		array_push( $this->read, $instance );
	}

	/** @param Persistent $instance */
	public function addUpdated( Persistent $instance ) {
		if ( $this->updated === null ) {
			$this->updated = [];
		}
		array_push( $this->updated, $instance );
	}

	/** @param Persistent $instance */
	public function addDeleted( Persistent $instance ) {
		if ( $this->deleted === null ) {
			$this->deleted = [];
		}
		array_push( $this->deleted, $instance );
	}

	// Getters and setters

	/**
	 * @return mixed|null
	 */
	public function getReference() {
		return $this->reference;
	}

	/**
	 * @param mixed|null $reference
	 */
	public function setReference( $reference ): void {
		$this->reference = $reference;
	}

	/**
	 * @return array|null
	 */
	public function getCreated(): ?array {
		return $this->created;
	}

	/**
	 * @param array|null $created
	 */
	public function setCreated( ?array $created ): void {
		$this->created = $created;
	}

	/**
	 * @return array|null
	 */
	public function getRead(): ?array {
		return $this->read;
	}

	/**
	 * @param array|null $read
	 */
	public function setRead( ?array $read ): void {
		$this->read = $read;
	}

	/**
	 * @return array|null
	 */
	public function getUpdated(): ?array {
		return $this->updated;
	}

	/**
	 * @param array|null $updated
	 */
	public function setUpdated( ?array $updated ): void {
		$this->updated = $updated;
	}

	/**
	 * @return array|null
	 */
	public function getDeleted(): ?array {
		return $this->deleted;
	}

	/**
	 * @param array|null $deleted
	 */
	public function setDeleted( ?array $deleted ): void {
		$this->deleted = $deleted;
	}

}
