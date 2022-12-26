<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;

/**
 * @since 0.1.0
 */
class UserCrud extends AbstractUuidRdbmsCrud {

	/** @var string Name of table in database. */
	public const TABLE = self::TABLES_PREFIX . 'user';

	/**
	 * @return string Name of database table representing this class.
	 */
	protected function getTable(): string {
		return self::TABLE;
	}

	private const CLASS_COLUMNS_PREFIX = self::COLUMNS_PREFIX . 'u_';

	/**
	 * @return string COLUMNS_PREFIX . 'class prefix' . '_'
	 */
	protected function getClassColumnsPrefix(): string {
		return self::CLASS_COLUMNS_PREFIX;
	}

	private const COLUMN_MEDIAWIKI_USER = self::CLASS_COLUMNS_PREFIX . 'mediawiki_user';
	private const COLUMN_YEAR_BORN = self::CLASS_COLUMNS_PREFIX . 'year_born';

	/**
	 * @return string[] Columns in table required to deserialize an instance, identity excluded.
	 */
	protected function getColumns(): array {
		return [
			self::COLUMN_MEDIAWIKI_USER,
			self::COLUMN_YEAR_BORN
		];
	}

	public function instanceFactory(): Persistent {
		return new User();
	}

	/**
	 * @param User $instance
	 * @param array $row
	 */
	protected function deserializeRow(
		$instance,
		array $row
	): void {
		$instance->setMediaWikiUser( $this->deserializeInt( $row, self::COLUMN_MEDIAWIKI_USER ) );
		$instance->setYearBorn( $this->deserializeInt( $row, self::COLUMN_YEAR_BORN ) );
	}

	/**
	 * @param User $instance
	 * @return array
	 */
	protected function serializeFields(
		$instance
	): array {
		$array = [];
		$array[ self::COLUMN_MEDIAWIKI_USER ] = $instance->getMediaWikiUser();
		$array[ self::COLUMN_YEAR_BORN ] = $instance->getYearBorn();
		return $array;
	}

	/**
	 * @param int $mediaWikiUser
	 * @return User|null
	 */
	public function getByMediaWikiUser(
		int $mediaWikiUser
	): ?Persistent {
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return $this->getByConditions( [
			self::COLUMN_MEDIAWIKI_USER => $mediaWikiUser
		] );
	}
}
