<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentSet;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentSet
 * @since 0.1.0
 */
class PersistentSetTest extends MediaWikiUnitTestCase {

	public function testAdd_singleRecordingInstanceAddedTwice_isOnlyAddedOnce() {
		$recording = new Recording();
		$recording->setIdentity( Uuid::v4BytesFactory() );

		$set = new PersistentSet();
		$this->assertTrue( $set->add( $recording ) );
		$this->assertFalse( $set->add( $recording ) );
	}

	public function testContains_singleRecording_match() {
		$recording = new Recording();
		$recording->setIdentity( Uuid::v4BytesFactory() );

		$set = new PersistentSet();
		$this->assertFalse( $set->contains( $recording ) );
		$set->add( $recording );
		$this->assertTrue( $set->contains( $recording ) );
	}

	public function testToArray_multipleClassInstances_containsAll() {
		$recording = new Recording();
		$recording->setIdentity( Uuid::v4BytesFactory() );

		$user1 = new User();
		$user1->setIdentity( Uuid::v4BytesFactory() );

		$user2 = new User();
		$user2->setIdentity( Uuid::v4BytesFactory() );

		$set = new PersistentSet();
		$set->add( $recording );
		$set->add( $user1 );
		$set->add( $user2 );

		$array = $set->toArray();
		$this->assertCount( 3, $array );
		$this->assertTrue( $array[0] === $recording );
		$this->assertTrue( $array[1] === $user1 );
		$this->assertTrue( $array[2] === $user2 );
	}

	public function testCount_multipleClassInstances_match() {
		$recording = new Recording();
		$recording->setIdentity( Uuid::v4BytesFactory() );

		$user1 = new User();
		$user1->setIdentity( Uuid::v4BytesFactory() );

		$user2 = new User();
		$user2->setIdentity( Uuid::v4BytesFactory() );

		$set = new PersistentSet();
		$set->add( $recording );
		$this->assertCount( 1, $set );
		$set->add( $user1 );
		$this->assertCount( 2, $set );
		$set->add( $user2 );
		$this->assertCount( 3, $set );
	}

	public function testCount_singleClass_match() {
		$user1 = new User();
		$user1->setIdentity( Uuid::v4BytesFactory() );

		$user2 = new User();
		$user2->setIdentity( Uuid::v4BytesFactory() );

		$set = new PersistentSet();
		$set->add( $user1 );
		$this->assertCount( 1, $set );
		$set->add( $user2 );
		$this->assertCount( 2, $set );
	}

	public function testCount_emptySet_countZero() {
		$set = new PersistentSet();
		$this->assertCount( 0, $set );
	}

}
