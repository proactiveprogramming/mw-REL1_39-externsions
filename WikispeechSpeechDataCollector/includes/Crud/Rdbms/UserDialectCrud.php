<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;

/**
 * @since 0.1.0
 */
class UserDialectCrud extends AbstractUuidRdbmsCrud {

	/** @var string Name of table in database. */
	public const TABLE = self::TABLES_PREFIX . 'user_dialect';

	/**
	 * @return string Name of database table representing this class.
	 */
	protected function getTable(): string {
		return self::TABLE;
	}

	private const CLASS_COLUMNS_PREFIX = self::COLUMNS_PREFIX . 'ud_';

	/**
	 * @return string COLUMNS_PREFIX . 'class prefix' . '_'
	 */
	protected function getClassColumnsPrefix(): string {
		return self::CLASS_COLUMNS_PREFIX;
	}

	private const COLUMN_USER = self::CLASS_COLUMNS_PREFIX . 'user';
	private const COLUMN_LANGUAGE = self::CLASS_COLUMNS_PREFIX . 'language';
	private const COLUMN_SPOKEN_PROFICIENCY_LEVEL =
		self::CLASS_COLUMNS_PREFIX . 'spoken_proficiency_level';
	private const COLUMN_LOCATION = self::CLASS_COLUMNS_PREFIX . 'location';

	/**
	 * @return string[] Columns in table required to deserialize an instance, identity excluded.
	 */
	protected function getColumns(): array {
		return [
			self::COLUMN_USER,
			self::COLUMN_LANGUAGE,
			self::COLUMN_SPOKEN_PROFICIENCY_LEVEL,
			self::COLUMN_LOCATION
		];
	}

	public function instanceFactory(): Persistent {
		return new UserDialect();
	}

	/**
	 * @param UserDialect $instance
	 * @param array $row
	 */
	protected function deserializeRow(
		$instance,
		array $row
	): void {
		$instance->setUser( $this->deserializeUuid( $row, self::COLUMN_USER ) );
		$instance->setLanguage( $this->deserializeUuid( $row, self::COLUMN_LANGUAGE ) );
		$instance->setSpokenProficiencyLevel(
			$this->deserializeInt( $row, self::COLUMN_SPOKEN_PROFICIENCY_LEVEL ) );
		$instance->setLocation( $this->deserializeString( $row, self::COLUMN_LOCATION ) );
	}

	/**
	 * @param UserDialect $instance
	 * @return array
	 */
	protected function serializeFields(
		$instance
	): array {
		$array = [];
		$array[ self::COLUMN_USER ] = $instance->getUser();
		$array[ self::COLUMN_LANGUAGE ] = $instance->getLanguage();
		$array[ self::COLUMN_SPOKEN_PROFICIENCY_LEVEL ] = $instance->getSpokenProficiencyLevel();
		$array[ self::COLUMN_LOCATION ] = $instance->getLocation();
		return $array;
	}

	/**
	 * @param string $user
	 * @return UserDialect[]|null
	 */
	public function listByUser(
		string $user
	): ?array {
		// @phan-suppress-next-line PhanTypeMismatchReturn
		return $this->listByConditions( [
			self::COLUMN_USER => $user
		] );
	}
}
