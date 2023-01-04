<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\GetPersistentClassName;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentMWAssociateArrayDeserializer;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentMWAssociativeArraySerializer;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MWException;

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 *
 * Associative array serialization and deserialization for CRUD transaction API.
 *
 * @since 0.1.0
 */
class CrudTransactionMWAssociativeArrayMarshaller implements CrudTransactionMarshaller {

	private const PERSISTENT_NAMESPACE = '\\MediaWiki\\WikispeechSpeechDataCollector\\Domain\\';

	/**
	 * @param array $transaction Associative array representation
	 * @return CrudTransactionRequest
	 * @throws CrudTransactionException|MWException
	 */
	public function deserializeRequest( $transaction ): CrudTransactionRequest {
		$request = new CrudTransactionRequest();

		// CREATE

		if ( array_key_exists( 'create', $transaction ) && $transaction['create'] ) {
			if ( !is_array( $transaction['create'] ) ) {
				throw new CrudTransactionException( 'Field "create" must be an array or a falsy value.' );
			}
			foreach ( $transaction[ 'create' ] as $persistentClass => $serializedInstances ) {
				$absolutePersistentClass = self::PERSISTENT_NAMESPACE . $persistentClass;
				foreach ( $serializedInstances as $serializedInstance ) {
					/** @var Persistent $instance */
					$instance = new $absolutePersistentClass();
					$instance = $instance->accept( new PersistentMWAssociateArrayDeserializer( $serializedInstance ) );
					$request->addCreate( $instance );
				}
			}
		}

		// READ

		if ( array_key_exists( 'read', $transaction ) && $transaction['read'] ) {
			if ( !is_array( $transaction['read'] ) ) {
				throw new CrudTransactionException( 'Field "read" must be an array or a falsy value.' );
			}
			foreach ( $transaction[ 'read' ] as $persistentClass => $identities ) {
				$absolutePersistentClass = self::PERSISTENT_NAMESPACE . $persistentClass;
				foreach ( $identities as $identity ) {
					/** @var Persistent $instance */
					$instance = new $absolutePersistentClass();
					$instance->setIdentity( Uuid::asBytes( $identity ) );
					$request->addRead( $instance );
				}
			}
		}

		// UPDATE

		if ( array_key_exists( 'update', $transaction ) && $transaction['update'] ) {
			if ( !is_array( $transaction['update'] ) ) {
				throw new CrudTransactionException( 'Field "update" must be an array or a falsy value.' );
			}
			foreach ( $transaction[ 'update' ] as $persistentClass => $serializedInstances ) {
				$absolutePersistentClass = self::PERSISTENT_NAMESPACE . $persistentClass;
				foreach ( $serializedInstances as $serializedInstance ) {
					/** @var Persistent $instance */
					$instance = new $absolutePersistentClass();
					$instance = $instance->accept( new PersistentMWAssociateArrayDeserializer( $serializedInstance ) );
					$request->addUpdate( $instance );
				}
			}
		}

		// DELETE

		if ( array_key_exists( 'delete', $transaction ) && $transaction['delete'] ) {
			if ( !is_array( $transaction['delete'] ) ) {
				throw new CrudTransactionException( 'Field "delete" must be an array or a falsy value.' );
			}
			foreach ( $transaction[ 'delete' ] as $persistentClass => $identities ) {
				$absolutePersistentClass = self::PERSISTENT_NAMESPACE . $persistentClass;
				foreach ( $identities as $identity ) {
					/** @var Persistent $instance */
					$instance = new $absolutePersistentClass();
					$instance->setIdentity( Uuid::asBytes( $identity ) );
					$request->addDelete( $instance );
				}
			}
		}

		if ( array_key_exists( 'reference', $transaction ) ) {
			$request->setReference( $transaction['reference'] );
		}

		if ( array_key_exists( 'readGraph', $transaction ) ) {
			$request->setReadGraph( $transaction['readGraph'] );
		}

		return $request;
	}

	/**
	 * @param CrudTransactionResponse $response
	 * @return array Associative array
	 */
	public function serializeResponse( CrudTransactionResponse $response ) {
		$array = [];
		$serializer = new PersistentMWAssociativeArraySerializer();

		if ( $response->getReference() !== null ) {
			$array['reference'] = $response->getReference();
		}

		// CREATED

		if ( $response->getCreated() ) {
			$createdByClass = $this->instancesArrayToInstancesByClassName( $response->getCreated() );
			if ( $createdByClass ) {
				$array['created'] = [];
				foreach ( $createdByClass as $class => $instances ) {
					$array['created'][$class] = [];
					foreach ( $instances as /** @var Persistent */ $instance ) {
						array_push( $array['created'][$class], $instance->accept( $serializer ) );
					}
				}
			}
		}

		// READ

		if ( $response->getRead() ) {
			$readByClass = $this->instancesArrayToInstancesByClassName( $response->getRead() );
			if ( $readByClass ) {
				$array['read'] = [];
				foreach ( $readByClass as $class => $instances ) {
					$array['read'][$class] = [];
					foreach ( $instances as /** @var Persistent */ $instance ) {
						array_push( $array['read'][$class], $instance->accept( $serializer ) );
					}
				}
			}
		}

		// UPDATED

		if ( $response->getUpdated() ) {
			$updatedByClass = $this->instancesArrayToInstancesByClassName( $response->getUpdated() );
			if ( $updatedByClass ) {
				$array['updated'] = [];
				foreach ( $updatedByClass as $class => $instances ) {
					$array['updated'][$class] = [];
					foreach ( $instances as /** @var Persistent */ $instance ) {
						// @todo only identity?
						array_push( $array['updated'][$class], $instance->accept( $serializer ) );
					}
				}
			}
		}

		// DELETED

		if ( $response->getDeleted() ) {
			$deletedByClass = $this->instancesArrayToInstancesByClassName( $response->getDeleted() );
			if ( $deletedByClass ) {
				$array['deleted'] = [];
				foreach ( $deletedByClass as $class => $instances ) {
					$array['deleted'][$class] = [];
					foreach ( $instances as /** @var Persistent */ $instance ) {
						array_push( $array['deleted'][$class], Uuid::asHex( $instance->getIdentity(), true ) );
					}
				}
			}
		}

		return $array;
	}

	/**
	 * Converts an array of persistent instances
	 * to an associative array with one field per persistent class name
	 * containing each an array with the persistent instances.
	 *
	 * @param array|null $instances
	 * @return array|null Associative array class name => array instances, or null.
	 */
	private function instancesArrayToInstancesByClassName(
		?array $instances
	): ?array {
		if ( $instances === null ) {
			return null;
		}
		$getPersistentClassName = new GetPersistentClassName();
		$instancesByClassName = [];
		foreach ( $instances as /** @var Persistent */ $instance ) {
			$className = $instance->accept( $getPersistentClassName );
			if ( !array_key_exists( $className, $instancesByClassName ) ) {
				$instancesByClassName[$className] = [];
			}
			array_push( $instancesByClassName[$className], $instance );
		}
		return $instancesByClassName;
	}
}
