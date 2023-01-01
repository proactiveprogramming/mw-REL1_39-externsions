<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use IContextSource;
use MediaWiki\Permissions\PermissionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Status;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\EditEntity\EditEntity;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\Model\ItemRevision;
use Wikibase\Repo\RestApi\Domain\Services\ItemUpdateFailed;
use Wikibase\Repo\RestApi\Infrastructure\EditSummaryFormatter;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\MediaWikiEditEntityFactoryItemUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiEditEntityFactoryItemUpdaterTest extends TestCase {

	/**
	 * @var MockObject|IContextSource
	 */
	private $context;

	/**
	 * @var MockObject|MediawikiEditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var MockObject|LoggerInterface
	 */
	private $logger;

	/**
	 * @var MockObject|EditSummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var MockObject|PermissionManager
	 */
	private $permissionManager;

	protected function setUp(): void {
		parent::setUp();

		$this->context = $this->createStub( IContextSource::class );
		$this->context->method( 'getUser' )->willReturn( $this->createStub( User::class ) );
		$this->editEntityFactory = $this->createStub( MediawikiEditEntityFactory::class );
		$this->logger = $this->createStub( LoggerInterface::class );
		$this->summaryFormatter = $this->createStub( EditSummaryFormatter::class );
		$this->permissionManager = $this->createStub( PermissionManager::class );
		$this->permissionManager->method( 'userHasRight' )->willReturn( true );
	}

	/**
	 * @dataProvider editMetadataProvider
	 */
	public function testUpdate( EditMetadata $editMetadata ): void {
		$itemToUpdate = NewItem::withId( 'Q123' )->build();
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$expectedRevisionItem = $this->createStub( Item::class );
		$expectedFormattedSummary = 'FORMATTED SUMMARY';

		$this->summaryFormatter = $this->createMock( EditSummaryFormatter::class );
		$this->summaryFormatter->expects( $this->once() )
			->method( 'format' )
			->with( $editMetadata->getSummary() )
			->willReturn( $expectedFormattedSummary );

		$editEntity = $this->createMock( EditEntity::class );
		$editEntity->expects( $this->once() )
			->method( 'attemptSave' )
			->with(
				$itemToUpdate,
				$expectedFormattedSummary,
				$editMetadata->isBot() ? EDIT_UPDATE | EDIT_FORCE_BOT : EDIT_UPDATE,
				false,
				false,
				$editMetadata->getTags()
			)
			->willReturn(
				Status::newGood( [
					'revision' => new EntityRevision( $expectedRevisionItem, $expectedRevisionId, $expectedRevisionTimestamp ),
				] )
			);

		$this->editEntityFactory = $this->createMock( MediawikiEditEntityFactory::class );
		$this->editEntityFactory->expects( $this->once() )
			->method( 'newEditEntity' )
			->with( $this->context, $itemToUpdate->getId() )
			->willReturn( $editEntity );

		$itemRevision = $this->newItemUpdater()->update( $itemToUpdate, $editMetadata );

		$this->assertSame( $expectedRevisionItem, $itemRevision->getItem() );
		$this->assertSame( $expectedRevisionId, $itemRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $itemRevision->getLastModified() );
	}

	public function editMetadataProvider(): Generator {
		yield 'bot edit' => [
			new EditMetadata( [], true, $this->createStub( EditSummary::class ) ),
		];
		yield 'user edit' => [
			new EditMetadata( [], false, $this->createStub( EditSummary::class ) ),
		];
	}

	public function testGivenSavingFails_throwsException(): void {
		$itemToUpdate = NewItem::withId( 'Q123' )->build();
		$editMeta = new EditMetadata( [ 'tag', 'also a tag' ], false, $this->createStub( EditSummary::class ) );
		$errorStatus = Status::newFatal( 'failed to save. sad times.' );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $errorStatus );

		$this->editEntityFactory = $this->createStub( MediawikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$updater = $this->newItemUpdater();

		$this->expectException( ItemUpdateFailed::class );
		$this->expectErrorMessage( (string)$errorStatus );

		$updater->update( $itemToUpdate, $editMeta );
	}

	public function testGivenSavingSucceedsWithErrors_logsErrors(): void {
		$saveStatus = Status::newGood( [
			'revision' => new EntityRevision( new Item(), 123, '20221111070707' ),
		] );
		$saveStatus->merge( Status::newFatal( 'saving succeeded but something else went wrong' ) );
		$saveStatus->setOK( true );

		$this->logger = $this->createMock( LoggerInterface::class );
		$this->logger->expects( $this->once() )
			->method( 'warning' )
			->with( (string)$saveStatus );

		$editEntity = $this->createStub( EditEntity::class );
		$editEntity->method( 'attemptSave' )->willReturn( $saveStatus );

		$this->editEntityFactory = $this->createStub( MediawikiEditEntityFactory::class );
		$this->editEntityFactory->method( 'newEditEntity' )->willReturn( $editEntity );

		$this->assertInstanceOf(
			ItemRevision::class,
			$this->newItemUpdater()->update(
				$this->createStub( Item::class ),
				$this->createStub( EditMetadata::class )
			)
		);
	}

	public function testGivenUserWithoutBotRight_throwsForBotEdit(): void {
		$this->permissionManager = $this->createMock( PermissionManager::class );
		$this->permissionManager->expects( $this->once() )
			->method( 'userHasRight' )
			->with( $this->context->getUser(), 'bot' )
			->willReturn( false );

		$this->expectException( \RuntimeException::class );

		$this->newItemUpdater()->update(
			$this->createStub( Item::class ),
			new EditMetadata( [], true, $this->createStub( EditSummary::class ) )
		);
	}

	private function newItemUpdater(): MediaWikiEditEntityFactoryItemUpdater {
		return new MediaWikiEditEntityFactoryItemUpdater(
			$this->context,
			$this->editEntityFactory,
			$this->logger,
			$this->summaryFormatter,
			$this->permissionManager
		);
	}

}
