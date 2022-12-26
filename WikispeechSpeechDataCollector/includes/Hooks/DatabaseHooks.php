<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Hooks;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use DatabaseUpdater;
use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\LanguageCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptDomainCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptPromptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\RecordingCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\RecordingReviewCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\SkippedManuscriptPromptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserDialectCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserLanguageProficiencyLevelCrud;

/**
 * @since 0.1.0
 */
class DatabaseHooks
	implements LoadExtensionSchemaUpdatesHook
{
	/**
	 * Creates database tables.
	 *
	 * @since 0.1.0
	 * @param DatabaseUpdater $updater
	 */
	public function onLoadExtensionSchemaUpdates( $updater ) {
		$this->addCrudExtensionTable( $updater, LanguageCrud::TABLE );
		$this->addCrudExtensionTable( $updater, ManuscriptCrud::TABLE );
		$this->addCrudExtensionTable( $updater, ManuscriptDomainCrud::TABLE );
		$this->addCrudExtensionTable( $updater, ManuscriptPromptCrud::TABLE );
		$this->addCrudExtensionTable( $updater, RecordingCrud::TABLE );
		$this->addCrudExtensionTable( $updater, RecordingReviewCrud::TABLE );
		$this->addCrudExtensionTable( $updater, SkippedManuscriptPromptCrud::TABLE );
		$this->addCrudExtensionTable( $updater, UserCrud::TABLE );
		$this->addCrudExtensionTable( $updater, UserDialectCrud::TABLE );
		$this->addCrudExtensionTable( $updater, UserLanguageProficiencyLevelCrud::TABLE );
	}

	/**
	 * Creates a database table for the CRUD pattern.
	 *
	 * @since 0.1.0
	 * @param DatabaseUpdater $updater
	 * @param string $tableName
	 */
	private function addCrudExtensionTable(
		DatabaseUpdater $updater,
		string $tableName
	) {
		$updater->addExtensionTable(
			$tableName,
			__DIR__ . '/../../sql/' . $tableName . '.sql'
		);
	}

}
