<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use ExternalStoreException;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;

/**
 * @since 0.1.0
 *
 * Assigns a UUID as identity in local scope before insert to table.
 */
abstract class AbstractUuidRdbmsCrud extends AbstractRdbmsCrud {

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
		$instance->setIdentity( Uuid::v4BytesFactory() );
		$rows = $this->serializeFields( $instance );
		$rows[ $this->getIdentityColumn() ] = $instance->getIdentity();
		$dbw = $this->getContext()->getDbLoadBalancer()->getConnectionRef( DB_PRIMARY );
		$dbw->insert( $this->getTable(), $rows );
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
		$instance->setIdentity( strval( $row[ $this->getIdentityColumn() ] ) );
	}

	/**
	 * @param string $identity
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function read(
		$identity
	): ?Persistent {
		return parent::read( $identity );
	}

	/**
	 * @param string $identity
	 * @since 0.1.0
	 */
	public function delete(
		$identity
	): void {
		parent::delete( $identity );
	}

}
