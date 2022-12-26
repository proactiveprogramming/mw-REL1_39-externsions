<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use ExternalStoreException;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * @since 0.1.0
 *
 * Expects that the integer identity is created during insert to table.
 */
abstract class AbstractIntRdbmsCrud extends AbstractRdbmsCrud {

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * creates representation in database to correspond to the data set in the domain object.
	 *
	 * @param Persistent $instance
	 * @throws ExternalStoreException If instance identity is already set
	 * @since 0.1.0
	 */
	public function create(
		Persistent $instance
	): void {
		if ( $instance->getIdentity() ) {
			throw new ExternalStoreException( 'Identity already set' );
		}
		$rows = $this->serializeFields( $instance );
		$dbw = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_PRIMARY );
		$dbw->insert( $this->getTable(), $rows );
		$instance->setIdentity( $dbw->insertId() );
	}

	/**
	 * @param Persistent $instance
	 * @param array $row
	 * @since 0.1.0
	 */
	protected function deserializeRowIdentity(
		Persistent $instance,
		$row
	): void {
		$instance->setIdentity( intval( $row[ $this->getIdentityColumn() ] ) );
	}

	/**
	 * @param int $identity
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function read(
		$identity
	): ?Persistent {
		return parent::read( $identity );
	}

	/**
	 * @param int $identity
	 * @since 0.1.0
	 */
	public function delete(
		$identity
	): void {
		parent::delete( $identity );
	}
}
