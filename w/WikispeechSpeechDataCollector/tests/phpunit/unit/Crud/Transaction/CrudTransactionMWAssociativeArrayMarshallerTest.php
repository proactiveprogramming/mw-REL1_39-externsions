<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Unit\Crud\Transaction;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionMWAssociativeArrayMarshaller;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionResponse;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentMWAssociateArrayDeserializer;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentMWAssociativeArraySerializer;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentCompleteOneBuilder;
use MediaWiki\WikispeechSpeechDataCollector\Tests\Domain\PersistentEqualsConstraintFactory;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MediaWikiUnitTestCase;

/**
 * @todo This is part of an internal development API. It should be removed before deployment.
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionMWAssociativeArrayMarshaller
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionResponse
 * @covers \MediaWiki\WikispeechSpeechDataCollector\Crud\Transaction\CrudTransactionRequest
 */
class CrudTransactionMWAssociativeArrayMarshallerTest extends MediaWikiUnitTestCase {

	public function testDeserializeRequest_singleUsers_equalsSerializedRequest() {
		$createUser = new User();
		$createUser->accept( new PersistentCompleteOneBuilder() );

		$readUser = new User();
		$readUser->setIdentity( Uuid::v4BytesFactory() );

		$updateUser = new User();
		$updateUser->setIdentity( Uuid::v4BytesFactory() );
		$updateUser->accept( new PersistentCompleteOneBuilder() );

		$deleteUser = new User();
		$deleteUser->setIdentity( Uuid::v4BytesFactory() );

		$array = [
			'reference' => 'reference',
			'create' => [
				'User' => [
					$createUser->accept(
						new PersistentMWAssociativeArraySerializer() )
				]
			],
			'read' => [
				'User' => [
						Uuid::asHex( $readUser->getIdentity() )
				]
			],
			'update' => [
				'User' => [
					$updateUser->accept(
						new PersistentMWAssociativeArraySerializer() )
				]
			],
			'delete' => [
				'User' => [
					Uuid::asHex( $deleteUser->getIdentity() )
				]
			]
		];

		$marshaller = new CrudTransactionMWAssociativeArrayMarshaller();
		$request = $marshaller->deserializeRequest( $array );

		$this->assertSame( 'reference', $request->getReference() );

		$this->assertCount( 1, $request->getCreate() );
		$this->assertThat(
			$createUser,
			$request->getCreate()[0]->accept(
				new PersistentEqualsConstraintFactory() )
		);

		$this->assertCount( 1, $request->getRead() );
		$this->assertThat(
			$readUser,
			$request->getRead()[0]->accept(
				new PersistentEqualsConstraintFactory() )
		);

		$this->assertCount( 1, $request->getUpdate() );
		$this->assertThat(
			$updateUser,
			$request->getUpdate()[0]->accept(
				new PersistentEqualsConstraintFactory() )
		);

		$this->assertCount( 1, $request->getDelete() );
		$this->assertThat(
			$deleteUser,
			$request->getDelete()[0]->accept(
				new PersistentEqualsConstraintFactory() )
		);
	}

	public function testSerializeResponse_singleUsers_equalsOriginalResponse() {
		$createdUser = new User();
		$createdUser->setIdentity( Uuid::v4BytesFactory() );
		$createdUser->accept( new PersistentCompleteOneBuilder() );

		$readUser = new User();
		$readUser->setIdentity( Uuid::v4BytesFactory() );
		$readUser->accept( new PersistentCompleteOneBuilder() );

		$updatedUser = new User();
		$updatedUser->setIdentity( Uuid::v4BytesFactory() );
		$updatedUser->accept( new PersistentCompleteOneBuilder() );

		$deletedUser = new User();
		$deletedUser->setIdentity( Uuid::v4BytesFactory() );
		$deletedUser->accept( new PersistentCompleteOneBuilder() );

		$response = new CrudTransactionResponse();
		$response->setReference( 'reference' );
		$response->addCreated( $createdUser );
		$response->addRead( $readUser );
		$response->addUpdated( $updatedUser );
		$response->addDeleted( $deletedUser );

		$marshaller = new CrudTransactionMWAssociativeArrayMarshaller();
		$array = $marshaller->serializeResponse( $response );

		$this->assertSame( $response->getReference(), $array['reference'] );

		$this->assertCount( 1, $array['created'] );
		$this->assertCount( 1, $array['created']['User'] );
		$deserializedCreatedUser = new User();
		$deserializedCreatedUser->accept(
			new PersistentMWAssociateArrayDeserializer(
				$array['created']['User'][0] ) );
		$this->assertThat(
			$createdUser,
			$deserializedCreatedUser->accept(
				new PersistentEqualsConstraintFactory() )
		);

		$this->assertCount( 1, $array['read'] );
		$this->assertCount( 1, $array['read']['User'] );
		$deserializedReadUser = new User();
		$deserializedReadUser->accept(
			new PersistentMWAssociateArrayDeserializer(
				$array['read']['User'][0] ) );
		$this->assertThat(
			$readUser,
			$deserializedReadUser->accept(
				new PersistentEqualsConstraintFactory() )
		);

		$this->assertCount( 1, $array['updated'] );
		$this->assertCount( 1, $array['updated']['User'] );
		$deserializedUpdatedUser = new User();
		$deserializedUpdatedUser->accept(
			new PersistentMWAssociateArrayDeserializer(
				$array['updated']['User'][0] ) );
		$this->assertThat(
			$updatedUser,
			$deserializedUpdatedUser->accept(
				new PersistentEqualsConstraintFactory() ) );

		$this->assertCount( 1, $array['deleted'] );
		$this->assertCount( 1, $array['deleted']['User'] );
		$this->assertSame(
			Uuid::asBytes( $deletedUser->getIdentity() ),
			Uuid::asBytes( $array['deleted']['User'][0] )
		);
	}

}
