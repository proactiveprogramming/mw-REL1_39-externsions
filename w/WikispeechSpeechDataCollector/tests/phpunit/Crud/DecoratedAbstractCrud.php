<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * Decorator pattern implementation for {@link AbstractCrud}.
 * @since 0.1.0
 */
class DecoratedAbstractCrud extends AbstractCrud {

	/** @var AbstractCrud */
	private $decorated;

	/**
	 * @param AbstractCrud $decorated
	 * @since 0.1.0
	 */
	public function __construct( AbstractCrud $decorated ) {
		$this->decorated = $decorated;
	}

	/** @inheritDoc */
	public function instanceFactory(): Persistent {
		return $this->decorated->instanceFactory();
	}

	/** @inheritDoc */
	public function create( Persistent $instance ): void {
		$this->decorated->create( $instance );
	}

	/** @inheritDoc */
	public function load( Persistent $instance ): bool {
		return $this->decorated->load( $instance );
	}

	/** @inheritDoc */
	public function update( Persistent $instance ): void {
		$this->decorated->update( $instance );
	}

	/** @inheritDoc */
	public function delete( $identity ): void {
		$this->decorated->delete( $identity );
	}

	/** @inheritDoc */
	public function getContext(): CrudContext {
		return $this->decorated->getContext();
	}

	/** @inheritDoc */
	public function read( $identity ): ?Persistent {
		return $this->decorated->read( $identity );
	}

}
