<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * Interface Crud
 *
 * Database access for
 * instances of the corresponding underlying subclass of {@link Persistent}.
 *
 * @note Consider implementing Phan generics for hard typing subclass of Persistent.
 * @since 0.1.0
 */
interface Crud {
	/**
	 * Given a persistent domain object instance with at least identity set,
	 * creates representation in database to correspond to the data set in the domain object.
	 *
	 * @param Persistent $instance Instance to be inserted to the persistent layer.
	 * @since 0.1.0
	 */
	public function create(
		Persistent $instance
	): void;

	/**
	 * Given an identity,
	 * retrieves a persistent domain object from the database.
	 *
	 * @see load()
	 * @param mixed $identity
	 * @return Persistent|null
	 * @since 0.1.0
	 */
	public function read(
		$identity
	): ?Persistent;

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
	): bool;

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * updates the database to correspond to the data set in the domain object.
	 *
	 * @param Persistent $instance
	 * @since 0.1.0
	 */
	public function update(
		Persistent $instance
	): void;

	/**
	 * Given an identity,
	 * removes the corresponding persistent domain object from the database.
	 *
	 * @param mixed $identity
	 * @since 0.1.0
	 */
	public function delete(
		$identity
	): void;
}
