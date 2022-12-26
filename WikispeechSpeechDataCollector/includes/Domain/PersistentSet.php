<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use Countable;

/**
 * A collection of unique {@link Persistent} instances.
 * Instances are unique per class and identity.
 *
 * @since 0.1.0
 */
class PersistentSet implements Countable {

	/** @var array */
	private $instancePerIdentityPerClass;

	/** @var int */
	private $count = 0;

	/**
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->instancePerIdentityPerClass = [];
	}

	/**
	 * @param Persistent $instance
	 * @return bool True if added, false if an instance
	 *  of the same class with the same identity already exists in the set.
	 * @since 0.1.0
	 */
	public function add( Persistent $instance ): bool {
		$class = get_class( $instance );
		if ( !array_key_exists( $class, $this->instancePerIdentityPerClass ) ) {
			$this->instancePerIdentityPerClass[$class] = [];
		}
		if ( array_key_exists( $instance->getIdentity(), $this->instancePerIdentityPerClass[$class] ) ) {
			return false;
		}
		$this->instancePerIdentityPerClass[$class][$instance->getIdentity()] = $instance;
		$this->count++;
		return true;
	}

	/**
	 * @param Persistent[] $instances
	 * @since 0.1.0
	 */
	public function addAll( array $instances ) {
		foreach ( $instances as $instance ) {
			$this->add( $instance );
		}
	}

	/**
	 * @param Persistent $instance
	 * @return bool Whether or not the an instance
	 *  of the same class with the same identity exists in the set.
	 * @since 0.1.0
	 */
	public function contains( Persistent $instance ): bool {
		$class = get_class( $instance );
		if ( !array_key_exists( $class, $this->instancePerIdentityPerClass ) ) {
			return false;
		}
		return array_key_exists( $instance->getIdentity(), $this->instancePerIdentityPerClass[$class] );
	}

	/**
	 * @param Persistent $instance
	 * @return bool Whether or not an instance
	 *  of the same class with the same identity was removed from the set.
	 * @since 0.1.0
	 */
	public function remove( Persistent $instance ): bool {
		$instancePerIdentity = $this->instancePerIdentityPerClass[ get_class( $instance ) ];
		if ( !$instancePerIdentity ) {
			return false;
		}
		if ( !array_key_exists( $instancePerIdentity, $instance->getIdentity() ) ) {
			return false;
		}
		unset( $instancePerIdentity[ $instance->getIdentity() ] );
		$this->count--;
		return true;
	}

	/**
	 * @param Persistent[]|null $instances
	 * @since 0.1.0
	 */
	public function removeAll( ?array $instances ) {
		if ( $instances ) {
			foreach ( $instances as $instance ) {
				$this->remove( $instance );
			}
		}
	}

	/**
	 * @param Persistent $instance
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function get( Persistent $instance ): ?Persistent {
		$instancePerIdentity = $this->instancePerIdentityPerClass[ get_class( $instance ) ];
		if ( !$instancePerIdentity ) {
			return null;
		}
		if ( array_key_exists( $instancePerIdentity, $instance->getIdentity() ) ) {
			return null;
		}
		return $instancePerIdentity[ $instance->getIdentity() ];
	}

	/**
	 * @return int
	 * @since 0.1.0
	 */
	public function count(): int {
		return $this->count;
	}

	/**
	 * @return array
	 * @since 0.1.0
	 */
	public function toArray(): array {
		$array = [];
		foreach ( $this->instancePerIdentityPerClass as $class => $instances ) {
			foreach ( $instances as $identity => $instance ) {
				$array[] = $instance;
			}
		}
		return $array;
	}

}
