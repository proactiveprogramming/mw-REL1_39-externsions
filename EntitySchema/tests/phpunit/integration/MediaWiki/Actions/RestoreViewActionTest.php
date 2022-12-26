<?php

namespace EntitySchema\Tests\Integration\MediaWiki\Actions;

use Article;
use CommentStoreComment;
use EntitySchema\MediaWiki\Actions\RestoreViewAction;
use EntitySchema\MediaWiki\Content\EntitySchemaContent;
use EntitySchema\MediaWiki\Content\EntitySchemaSlotDiffRenderer;
use FauxRequest;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use RequestContext;
use TextSlotDiffRenderer;
use Title;
use WikiPage;

/**
 * @license GPL-2.0-or-later
 *
 * @group Database
 *
 * @covers \EntitySchema\MediaWiki\Actions\RestoreViewAction
 * @covers \EntitySchema\Presentation\DiffRenderer
 * @covers \EntitySchema\Presentation\ConfirmationFormRenderer
 */
final class RestoreViewActionTest extends MediaWikiIntegrationTestCase {

	public function setUp(): void {
		parent::setUp();
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'recentchanges';
	}

	public function testRestoreView() {
		// arrange
		$page = $this->getServiceContainer()->getWikiPageFactory()
			->newFromTitle( Title::makeTitle( NS_ENTITYSCHEMA_JSON, 'E1234' ) );

		$firstID = $this->saveSchemaPageContent(
			$page,
			[ 'schemaText' => 'abc' ]
		);
		$this->saveSchemaPageContent( $page, [ 'schemaText' => 'def' ] );

		$context = RequestContext::getMain();
		$context->setWikiPage( $page );
		$context->setRequest(
			new FauxRequest(
				[
					'action' => 'edit',
					'restore' => $firstID,
				],
				false
			)
		);

		$textSlotDiffRenderer = new TextSlotDiffRenderer();
		$textSlotDiffRenderer->setEngine( TextSlotDiffRenderer::ENGINE_PHP );
		$diffRenderer = new EntitySchemaSlotDiffRenderer(
			$context,
			$textSlotDiffRenderer
		);
		$undoViewAction = new RestoreViewAction(
			Article::newFromWikiPage( $page, $context ),
			$context,
			$diffRenderer
		);

		// act
		$undoViewAction->show();

		// assert
		$actualHTML = $undoViewAction->getContext()->getOutput()->getHTML();
		$this->assertStringContainsString(
			'<ins class="diffchange diffchange-inline">abc</ins>',
			$actualHTML
		);
		$this->assertStringContainsString(
			'<del class="diffchange diffchange-inline">def</del>',
			$actualHTML
		);
	}

	private function saveSchemaPageContent( WikiPage $page, array $content ) {
		$content['serializationVersion'] = '3.0';
		$updater = $page->newPageUpdater( self::getTestUser()->getUser() );
		$updater->setContent( SlotRecord::MAIN, new EntitySchemaContent( json_encode( $content ) ) );
		$firstRevRecord = $updater->saveRevision(
			CommentStoreComment::newUnsavedComment(
				'test summary 1'
			)
		);

		return $firstRevRecord->getId();
	}

}
