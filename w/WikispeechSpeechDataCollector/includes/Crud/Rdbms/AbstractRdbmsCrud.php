<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MWTimestamp;

/**
 * Database mapping and access for
 * instances of the corresponding underlying subclass of {@link Persistent}.
 *
 * @since 0.1.0
 */
abstract class AbstractRdbmsCrud extends AbstractCrud {

	/** @var string Prefix of all extension tables in database. */
	protected const TABLES_PREFIX = 'wikispeech_sdc_';

	/** @var string Prefix of all columns in database extension tables. */
	protected const COLUMNS_PREFIX = 'wssdc';

	/**
	 * @return string COLUMNS_PREFIX . 'class prefix' . '_'
	 */
	abstract protected function getClassColumnsPrefix(): string;

	/** @var string Name of table column that holds the identity. */
	private $identityColumn;

	/**
	 * @param CrudContext $context
	 * @since 0.1.0
	 */
	public function __construct( CrudContext $context ) {
		parent::__construct( $context );
		$this->identityColumn = $this->getClassColumnsPrefix() . 'identity';
	}

	/**
	 * Single column identity for this CRUD.
	 *
	 * If you want to use a multi column identity you'll probably have to extract
	 * this method and all uses of it to a strategy pattern or something.
	 *
	 * @return string
	 * @since 0.1.0
	 */
	public function getIdentityColumn(): string {
		return $this->identityColumn;
	}

	/**
	 * @return string Name of database table representing this class.
	 * @since 0.1.0
	 */
	abstract protected function getTable(): string;

	/**
	 * @return string[] Columns in table required to deserialize an instance, identity excluded.
	 * @since 0.1.0
	 */
	abstract protected function getColumns(): array;

	/**
	 * @var string[] Columns in table required to deserialize an instance, identity included.
	 * @since 0.1.0
	 */
	private $allColumns;

	/**
	 * @return array|string[] Columns in table required to deserialize an instance, identity included
	 * @since 0.1.0
	 */
	protected function getAllColumns(): array {
		if ( !$this->allColumns ) {
			$allColumns = $this->getColumns();
			array_push( $allColumns, $this->getIdentityColumn() );
			$this->allColumns = $allColumns;
		}
		return $this->allColumns;
	}

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * updates the domain object to correspond data retrieved from the database.
	 *
	 * @see read()
	 * @param Persistent $instance Instance to be loaded. Identity must be set.
	 * @return bool true if found, false if not found.
	 * @since 0.1.0
	 */
	public function load(
		Persistent $instance
	): bool {
		return $this->loadByConditions( [
			$this->getIdentityColumn() => $instance->getIdentity()
		], $instance );
	}

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * updates the database to correspond to the data set in the domain object.
	 *
	 * @param Persistent $instance
	 * @since 0.1.0
	 */
	public function update(
		Persistent $instance
	): void {
		$set = $this->serializeFields( $instance );
		$dbw = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_PRIMARY );
		$dbw->update( $this->getTable(), $set, [
			$this->getIdentityColumn() => $instance->getIdentity()
		] );
	}

	/**
	 * Given an identity,
	 * removes the corresponding persistent domain object from the database.
	 *
	 * @param mixed $identity
	 * @since 0.1.0
	 */
	public function delete(
		$identity
	): void {
		$dbw = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_PRIMARY );
		$dbw->delete( $this->getTable(), [
			$this->getIdentityColumn() => $identity
		] );
	}

	/**
	 * Picks up a single instance. Creates a new object instance.
	 *
	 * @param array $conditions See {@link \Wikimedia\Rdbms\IDatabase::select} conditions.
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function getByConditions(
		array $conditions
	): ?Persistent {
		$instance = $this->instanceFactory();
		return $this->loadByConditions( $conditions, $instance ) ? $instance : null;
	}

	/**
	 * Picks up a single instance. Loads to existing object instance.
	 *
	 * @param array $conditions See {@link \Wikimedia\Rdbms\IDatabase::select} conditions.
	 * @param Persistent $instance Instance to be populated with data.
	 * @return bool true if found, false if not found.
	 * @since 0.1.0
	 */
	public function loadByConditions(
		array $conditions,
		Persistent $instance
	): bool {
		$dbr = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_REPLICA );
		$row = $dbr->selectRow( $this->getTable(), $this->getAllColumns(), $conditions );
		if ( !$row ) {
			return false;
		}
		$rowArray = (array)$row;
		$this->deserializeRowIdentity( $instance, $rowArray );
		$this->deserializeRow( $instance, $rowArray );
		return true;
	}

	/**
	 * @param array $conditions
	 * @return Persistent[]|null
	 * @since 0.1.0
	 */
	public function listByConditions(
		array $conditions
	): ?array {
		$dbr = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_REPLICA );
		$res = $dbr->select( $this->getTable(), $this->getAllColumns(), $conditions );
		$instances = [];
		foreach ( $res as $row ) {
			$rowArray = (array)$row;
			$instance = $this->instanceFactory();
			$this->deserializeRowIdentity( $instance, $rowArray );
			$this->deserializeRow( $instance, $rowArray );
			array_push( $instances, $instance );
		}
		return $instances;
	}

	/**
	 * Reads all class fields except the identity field from the row.
	 *
	 * @param mixed $instance An instance of the corresponding underlying Persistent subclass.
	 * @param array $row
	 * @since 0.1.0
	 */
	abstract protected function deserializeRow(
		$instance,
		array $row
	): void;

	/**
	 * @param Persistent $instance
	 * @param array $row
	 * @since 0.1.0
	 */
	abstract protected function deserializeRowIdentity(
		Persistent $instance,
		$row
	): void;

	/**
	 * Adds all class fields except for the identity field
	 * to the array using the table column name as key.
	 *
	 * Used to execute create- and update statements.
	 *
	 * @param mixed $instance An instance of the corresponding underlying Persistent subclass.
	 * @return array
	 * @since 0.1.0
	 */
	abstract protected function serializeFields(
		$instance
	): array;

	/**
	 * @param array $row
	 * @param string $columnName
	 * @return string|null
	 * @since 0.1.0
	 */
	protected function deserializeString( array $row, string $columnName ): ?string {
		if ( !array_key_exists( $columnName, $row ) ) {
			return null;
		}
		return $row[$columnName] !== null ? strval( $row[$columnName] ) : null;
	}

	/**
	 * @param array $row
	 * @param string $columnName
	 * @return int|null
	 * @since 0.1.0
	 */
	protected function deserializeInt( array $row, string $columnName ): ?int {
		if ( !array_key_exists( $columnName, $row ) ) {
			return null;
		}
		return $row[$columnName] !== null ? intval( $row[$columnName] ) : null;
	}

	/**
	 * @param array $row
	 * @param string $columnName
	 * @return string|null
	 * @since 0.1.0
	 */
	protected function deserializeUuid( array $row, string $columnName ): ?string {
		if ( !array_key_exists( $columnName, $row ) ) {
			return null;
		}
		return $row[$columnName] !== null ? Uuid::asBytes( strval( $row[$columnName] ) ) : null;
	}

	/**
	 * @param array $row
	 * @param string $columnName
	 * @return MWTimestamp|null
	 * @since 0.1.0
	 */
	protected function deserializeTimestamp( array $row, string $columnName ): ?MWTimestamp {
		if ( !array_key_exists( $columnName, $row ) ) {
			return null;
		}
		return $row[$columnName] !== null ? new MWTimestamp( $row[$columnName] ) : null;
	}
}
