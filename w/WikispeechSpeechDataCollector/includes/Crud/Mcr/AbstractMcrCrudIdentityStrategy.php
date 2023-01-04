<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use JsonContent;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use WikiPage;

/**
 * @version 0.1.8
 */
interface AbstractMcrCrudIdentityStrategy {

	/**
	 * @param mixed $identity
	 * @param CrudContext $context
	 * @return WikiPage
	 * @since 0.1.0
	 */
	public function getWikiPage(
		$identity,
		CrudContext $context
	): WikiPage;

	/**
	 * @param mixed $identity
	 * @return JsonContent
	 * @since 0.1.0
	 */
	public function identityJsonContentFactory(
		$identity
	): JsonContent;

	/**
	 * Sets a new identity to the instance.
	 * @param Persistent $instance
	 * @since 0.1.0
	 */
	public function identityFactory(
		Persistent $instance
	): void;
}
