<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Clud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudFactory;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\AbstractRdbmsCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionExecutor;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionRequest;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentCompleteOneBuilder;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentCompleteTwoBuilder;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentEqualsConstraintFactory;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentSetNullableNull;
use MediaWikiIntegrationTestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\Clud
 * @since 0.1.0
 */
abstract class AbstractCrudTest extends MediaWikiIntegrationTestCase {

	/** @var LoggerInterface */
	private $logger;

	/**
	 * Should only contain the information required to access the underlying CRUD implementation.
	 * @return CrudContext
	 */
	abstract protected function getCrudContext(): CrudContext;

	/**
	 * @param CrudContext $context
	 * @return AbstractRdbmsCrud CRUD for the underlying persistent class being tested.
	 */
	abstract protected function newCrudInstance(
		CrudContext $context
	): AbstractCrud;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = LoggerFactory::getInstance( __CLASS__ );
	}

	/**
	 * Create-Read-Update-Delete tests using CRUD.
	 * Ensures that serialization and deserialization match.
	 */
	public function testCrud() {
		$crud = $this->newCrudInstance( $this->getCrudContext() );

		// create initial
		$instance = $crud->instanceFactory();
		$instance->accept( new PersistentCompleteOneBuilder() );
		$this->assertNull( $instance->getIdentity() );

		// initial instance

		$crud->create( $instance );
		$this->assertNotNull( $instance->getIdentity() );

		$readInstance = $crud->read( $instance->getIdentity() );
		$this->logger->debug( 'Instance @ CRUD read#1 is ' . $readInstance );
		$this->assertThat( $instance,
			$readInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// update instance fields

		$readInstance->accept( new PersistentCompleteTwoBuilder() );
		$crud->update( $readInstance );

		$updatedInstance = $crud->read( $instance->getIdentity() );
		$this->logger->debug( 'Instance @ CRUD read#2 is ' . $updatedInstance );
		$this->assertThat( $readInstance,
			$updatedInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// delete instance

		$crud->delete( $instance->getIdentity() );
		$deletedInstance = $crud->read( $instance->getIdentity() );
		$this->logger->debug( 'Instance @ CRUD read#3 is ' . $deletedInstance );
		$this->assertNull( $deletedInstance );
	}

	/**
	 * Ensures that serialization and deserialization match
	 * for all nullable fields when set to null.
	 */
	public function testCrud_setNullableNull_deserializationMatches() {
		$crud = $this->newCrudInstance( $this->getCrudContext() );

		$instance = $crud->instanceFactory();
		$instance->accept( new PersistentCompleteTwoBuilder() );
		$instance->accept( new PersistentSetNullableNull() );
		$crud->create( $instance );
		$readInstance = $crud->read( $instance->getIdentity() );
		$this->assertThat( $instance,
			$readInstance->accept( new PersistentEqualsConstraintFactory() ) );
	}

	/**
	 * Create-Load-Update-Delete tests using CLUD.
	 * Ensures that serialization and deserialization match.
	 *
	 * As the CLUD is just a generic facade against all CRUDs,
	 * there is no need to execute further tests on CLUD
	 * (e.g. testClud_setNullableNull_deserializationMatches)
	 * to ensure specific features in regard to persistent instances.
	 */
	public function testClud() {
		$crud = $this->newCrudInstance( $this->getCrudContext() );
		$clud = new Clud( new CrudFactory( $this->getCrudContext() ) );

		// create instance

		$instance = $crud->instanceFactory();
		$instance->accept( new PersistentCompleteOneBuilder() );
		$this->assertNull( $instance->getIdentity() );

		// initial instance

		$clud->create( $instance );
		$this->assertNotNull( $instance->getIdentity() );

		$readInstance = $crud->instanceFactory();
		$readInstance->setIdentity( $instance->getIdentity() );
		$this->assertTrue( $clud->load( $readInstance ) );
		$this->logger->debug( 'Instance @ CLUD read#1 is ' . $readInstance );
		$this->assertSame( $instance->getIdentity(), $readInstance->getIdentity() );
		$this->assertThat( $instance,
			$readInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// update instance fields

		$readInstance->accept( new PersistentCompleteTwoBuilder() );
		$clud->update( $readInstance );

		$updatedInstance = $crud->instanceFactory();
		$updatedInstance->setIdentity( $instance->getIdentity() );
		$this->assertTrue( $clud->load( $updatedInstance ) );
		$this->logger->debug( 'Instance @ CLUD read#2 is ' . $updatedInstance );
		$this->assertSame( $instance->getIdentity(), $updatedInstance->getIdentity() );
		$this->assertThat( $readInstance,
			$updatedInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// delete instance

		$deletedInstance = $crud->instanceFactory();
		$deletedInstance->setIdentity( $instance->getIdentity() );
		$clud->delete( $deletedInstance );
		$this->assertFalse( $clud->load( $deletedInstance ) );
	}

	/**
	 * @todo This is part of an internal development API. It should be removed before deployment.
	 * @throws \MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionException
	 */
	public function testCrudTransactionExecutor() {
		$executor = new CrudTransactionExecutor( $this->getCrudContext() );
		$crud = $this->newCrudInstance( $this->getCrudContext() );

		// create initial

		$instance = $crud->instanceFactory();
		$instance->accept( new PersistentCompleteOneBuilder() );
		$this->assertNull( $instance->getIdentity() );

		// initial instance

		$request = new CrudTransactionRequest();
		$request->setCreate( [ $instance ] );
		$response = $executor->execute( $request );
		$this->assertCount( 1, $response->getCreated() );
		$instance = $response->getCreated()[0];
		$this->assertNotNull( $instance->getIdentity() );

		$request = new CrudTransactionRequest();
		$request->setRead( [ $instance ] );
		$response = $executor->execute( $request );
		$this->assertCount( 1, $response->getRead() );
		$readInstance = $response->getRead()[0];
		$this->logger->debug( 'Instance @ transaction read#1 is ' . $readInstance );
		$this->assertThat( $instance,
			$readInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// update instance fields

		$readInstance->accept( new PersistentCompleteTwoBuilder() );
		$request = new CrudTransactionRequest();
		$request->setUpdate( [ $readInstance ] );

		$response = $executor->execute( $request );
		$this->assertCount( 1, $response->getUpdated() );
		$updatedInstance = $response->getUpdated()[0];
		$this->logger->debug( 'Instance @ transaction read#2 is ' . $updatedInstance );
		$this->assertThat( $readInstance,
			$updatedInstance->accept( new PersistentEqualsConstraintFactory() ) );

		// delete instance

		$request = new CrudTransactionRequest();
		$request->setDelete( [ $instance ] );
		$response = $executor->execute( $request );
		$this->assertCount( 1, $response->getDeleted() );
		$deletedInstance = $response->getDeleted()[0];
		$this->logger->debug( 'Instance @ transaction read#3 is ' . $deletedInstance );
		$this->assertThat( $updatedInstance, $deletedInstance->accept( new PersistentEqualsConstraintFactory() ) );
	}

}
