<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud\Mcr;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\AbstractMcrCrud;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Integration\Crud\AbstractCrudTest;

/**
 * @since 0.1.0
 */
abstract class AbstractMcrCrudTest extends AbstractCrudTest {

	/** @var CrudContext */
	private $context;

	protected function getCrudContext(): CrudContext {
		return $this->context;
	}

	/**
	 * @param CrudContext $context
	 * @return AbstractMcrCrud
	 */
	abstract protected function newCrudInstance( CrudContext $context ): AbstractCrud;

	protected function setUp(): void {
		parent::setUp();
		$this->context = new CrudContext(
			null,
			self::getTestUser()->getUser(),
			MediaWikiServices::getInstance()->getRevisionStore()
		);
	}
}
