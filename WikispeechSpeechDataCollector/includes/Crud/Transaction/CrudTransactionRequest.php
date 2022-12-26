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
 * A collection of {@link Persistent} objects that will be sent
 * either to creation, to be read, to be updated or to be deleted
 * via {@link CrudTransactionExecutor}.
 *
 * @since 0.1.0
 */
class CrudTransactionRequest {

	/**
	 * @var mixed|null Client reference, passed back in response.
	 * @see CrudTransactionResponse::$reference
	 */
	private $reference;

	/** @var array|null {@link Persistent} instances */
	private $create;

	/** @var array|null {@link Persistent} instances */
	private $read;

	/** @var array|null {@link Persistent} instances */
	private $update;

	/** @var array|null {@link Persistent} instances */
	private $delete;

	/** @var bool Whether to load predefined object graphs or just the single requested object. */
	private $readGraph = false;

	// Add-helpers

	/** @param Persistent $instance */
	public function addCreate( Persistent $instance ) {
		if ( $this->create === null ) {
			$this->create = [];
		}
		$this->create[] = $instance;
	}

	/** @param Persistent $instance */
	public function addRead( Persistent $instance ) {
		if ( $this->read === null ) {
			$this->read = [];
		}
		$this->read[] = $instance;
	}

	/** @param Persistent $instance */
	public function addUpdate( Persistent $instance ) {
		if ( $this->update === null ) {
			$this->update = [];
		}
		$this->update[] = $instance;
	}

	/** @param Persistent $instance */
	public function addDelete( Persistent $instance ) {
		if ( $this->delete === null ) {
			$this->delete = [];
		}
		$this->delete[] = $instance;
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
	public function getCreate(): ?array {
		return $this->create;
	}

	/**
	 * @param array|null $create
	 */
	public function setCreate( ?array $create ): void {
		$this->create = $create;
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
	public function getUpdate(): ?array {
		return $this->update;
	}

	/**
	 * @param array|null $update
	 */
	public function setUpdate( ?array $update ): void {
		$this->update = $update;
	}

	/**
	 * @return array|null
	 */
	public function getDelete(): ?array {
		return $this->delete;
	}

	/**
	 * @param array|null $delete
	 */
	public function setDelete( ?array $delete ): void {
		$this->delete = $delete;
	}

	/**
	 * @return bool
	 */
	public function isReadGraph(): bool {
		return $this->readGraph;
	}

	/**
	 * @param bool $readGraph
	 */
	public function setReadGraph( bool $readGraph ): void {
		$this->readGraph = $readGraph;
	}

}
