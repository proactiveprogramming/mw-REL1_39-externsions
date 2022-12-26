<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud\Mcr;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Crud\DecoratedAbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MWException;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud
 * @since 0.1.0
 */
class RecordingAnnotationsMcrCrudTest extends AbstractMcrCrudTest {
	/**
	 * @todo Need to add identity on Crud::create in the CrudFactory of the CLUD in the test.
	 * Until then, this test is disabled.
	 *
	 * https://phabricator.wikimedia.org/T274827
	 *
	 * Composite parts inherit their identity from the composite owner.
	 * Composite owners set identity on Crud::create,
	 * while composite parts has an identity which is manually set by the developer.
	 * Thus in order to share test code between owners and parts, the tests of parts
	 * need to be modified in a way that they add a new identity on create.
	 *
	 * This is quite OK to skip though. CLUD is a generic facade against CRUD,
	 * so as long as the CRUD test of this class works while CLUD tests of
	 * other classes work, everything is known to be healthy.
	 */
	public function testClud() {
		$this->markTestSkipped( 'Need to add identity on Crud::create for test to work' );
		// nop
	}

	/**
	 * @todo Need to add identity on Crud::create in the CrudFactory of the CLUD in the test.
	 * Until then, this test is disabled.
	 *
	 * https://phabricator.wikimedia.org/T274827
	 *
	 * Composite parts inherit their identity from the composite owner.
	 * Composite owners set identity on Crud::create,
	 * while composite parts has an identity which is manually set by the developer.
	 * Thus in order to share test code between owners and parts, the tests of parts
	 * need to be modified in a way that they add a new identity on create.
	 *
	 * This is quite OK to skip though. testCrudTransactionExecutor is a generic facade against CRUD,
	 * so as long as the CRUD test of this class works while testCrudTransactionExecutor tests of
	 * other classes work, everything is known to be healthy.
	 */
	public function testCrudTransactionExecutor() {
		$this->markTestSkipped( 'Need to add identity on Crud::create for test to work' );
		// nop
	}

	protected function newCrudInstance(
		CrudContext $context
	): AbstractCrud {
		// The idea here is implement a sub class of DecoratedAbstractCrud
		// that replace the below create function for any MCR composite part CRUD.
		// @todo Replace this anonymous inner class with that when adding new MCR composite part CRUDs.
		return new class( new RecordingAnnotationsCrud( $context ) ) extends DecoratedAbstractCrud {
			/**
			 * Adds identity to instance before calling decoration.
			 *
			 * @param Persistent $instance
			 * @throws MWException If identity is already set.
			 */
			public function create( Persistent $instance ): void {
				if ( $instance->getIdentity() !== null ) {
					throw new MWException( 'Identity is already set!' );
				}
				$instance->setIdentity( Uuid::v4BytesFactory() );
				parent::create( $instance );
			}
		};
	}
}
