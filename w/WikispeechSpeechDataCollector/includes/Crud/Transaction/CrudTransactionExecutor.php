<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\Clud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudFactory;
use MediaWiki\WikispeechSpeechDataCollector\Crud\PersistentRootGraphLoader;

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 *
 * A facade against {@link Clud} to access and modify a set of
 * {@link Persistent} objects.
 *
 * The name <b>transaction</b> might be a bit misleading,
 * as this is not an ACID transaction with rollback on errors etc.
 * If your execution fails halfway through, the parts executed
 * will have been committed.
 *
 * Consider single object (atomic) calls in case you are uncertain
 * about the validity of the object you pass to the executor.
 *
 * @since 0.1.0
 */
class CrudTransactionExecutor {

	/** @var CrudContext */
	private $context;

	/** @var Clud */
	private $clud;

	/**
	 * @param CrudContext $context
	 * @since 0.1.0
	 */
	public function __construct( CrudContext $context ) {
		$this->context = $context;
		$this->clud = new Clud( new CrudFactory( $context ) );
	}

	/**
	 * @param CrudTransactionRequest $request
	 * @return CrudTransactionResponse
	 * @throws CrudTransactionException
	 * @since 0.1.0
	 */
	public function execute(
		CrudTransactionRequest $request
	): CrudTransactionResponse {
		$response = new CrudTransactionResponse();
		$response->setReference( $request->getReference() );

		if ( $request->getCreate() ) {
			foreach ( $request->getCreate() as $instance ) {
				$this->clud->create( $instance );
				$response->addCreated( $instance );
			}
		}

		if ( $request->getRead() ) {
			foreach ( $request->getRead() as $instance ) {
				if ( $this->clud->load( $instance ) ) {
					$response->addRead( $instance );
				} else {
					throw new CrudTransactionException( "Unable to read $instance" );
				}
			}
			if ( $request->isReadGraph() ) {
				$graphLoader = new PersistentRootGraphLoader( $this->context );
				foreach ( $response->getRead() as $instance ) {
					$instance->accept( $graphLoader );
				}
				$graphLoader->getLoadedInstances()->removeAll( $response->getRead() );
				foreach ( $graphLoader->getLoadedInstances()->toArray() as $instance ) {
					$response->addRead( $instance );
				}
			}
		}

		if ( $request->getUpdate() ) {
			foreach ( $request->getUpdate() as $instance ) {
				$this->clud->update( $instance );
				$response->addUpdated( $instance );
			}
		}

		if ( $request->getDelete() ) {
			foreach ( $request->getDelete() as $instance ) {
				$this->clud->delete( $instance );
				$response->addDeleted( $instance );
			}
		}

		return $response;
	}
}
