<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * Abstract access for instances of the corresponding underlying subclass of {@link Persistent}.
 *
 * @since 0.1.0
 */
abstract class AbstractCrud implements Crud {

	/** @var CrudContext */
	private $context;

	/**
	 * @param CrudContext $context
	 * @since 0.1.0
	 */
	public function __construct( CrudContext $context ) {
		$this->context = $context;
	}

	/**
	 * @return CrudContext
	 * @since 0.1.0
	 */
	public function getContext(): CrudContext {
		return $this->context;
	}

	/**
	 * Creates a new instance
	 * of the corresponding underlying subclass of {@link Persistent}.
	 *
	 * @return Persistent
	 * @since 0.1.0
	 */
	abstract public function instanceFactory(): Persistent;

	/**
	 * Given an identity,
	 * retrieves a persistent domain object from the database.
	 *
	 * @see load()
	 * @param mixed $identity
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function read(
		$identity
	): ?Persistent {
		$instance = $this->instanceFactory();
		$instance->setIdentity( $identity );
		return $this->load( $instance ) ? $instance : null;
	}
}
