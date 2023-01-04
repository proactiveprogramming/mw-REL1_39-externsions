<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\AbstractRdbmsCrud;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud\AbstractCrudTest;
use Wikimedia\TestingAccessWrapper;

/**
 * @todo Add helpers for testing listing and getting object by indices.
 *
 * @since 0.1.0
 * @group Database
 */
abstract class AbstractRdbmsCrudTest extends AbstractCrudTest {

	/** @var CrudContext */
	private $context;

	protected function getCrudContext(): CrudContext {
		return $this->context;
	}

	/**
	 * @param CrudContext $context
	 * @return AbstractRdbmsCrud
	 */
	abstract protected function newCrudInstance( CrudContext $context ): AbstractCrud;

	protected function setUp(): void {
		parent::setUp();
		$this->context = new CrudContext(
			MediaWikiServices::getInstance()->getDBLoadBalancer(),
			null,
			null
		);
		$crudWrapper = TestingAccessWrapper::newFromObject( $this->newCrudInstance( $this->context ) );
		$this->tablesUsed[] = $crudWrapper->getTable();
	}

}
