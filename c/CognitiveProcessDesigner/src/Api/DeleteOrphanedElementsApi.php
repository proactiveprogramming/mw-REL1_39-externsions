<?php

namespace CognitiveProcessDesigner\Api;

use ApiBase;
use CommentStoreComment;
use ContentHandler;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Message;
use MWException;
use RecentChange;
use RuntimeException;
use Title;
use Wikimedia\ParamValidator\ParamValidator;
use WikiPage;

class DeleteOrphanedElementsApi extends ApiBase {

	/**
	 * @inheritDoc
	 */
	protected function getAllowedParams() {
		return [
			'elements' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => 'cpd-api-delete-orphaned-elements-param-elements'
			]
		];
	}

	/**
	 * @inheritDoc
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @inheritDoc
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$elements = json_decode( $params['elements'], true );

		$errors = [];
		$warnings = [];

		if ( $elements ) {
			if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
				// MW 1.36+
				$wikiPageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
			} else {
				$wikiPageFactory = null;
			}
			foreach ( $elements as $element ) {
				$title = Title::makeTitle( NS_MAIN, $element['title'] );

				if ( $wikiPageFactory !== null ) {
					// MW 1.36+
					$wikipage = $wikiPageFactory->newFromTitle( $title );
				} else {
					$wikipage = WikiPage::factory( $title );
				}

				$updater = $wikipage->newPageUpdater( $this->getContext()->getUser() );

				$content = ContentHandler::makeContent( '[[Category:Delete]]', $title );

				$updater->setContent( SlotRecord::MAIN, $content );
				$updater->setRcPatrolStatus( RecentChange::PRC_PATROLLED );

				$comment = Message::newFromKey( 'cpd-api-delete-orphaned-elements-update-comment' );
				$commentStore = CommentStoreComment::newUnsavedComment( $comment );

				try {
					$result = $updater->saveRevision( $commentStore, EDIT_UPDATE );
				} catch ( MWException | RuntimeException $e ) {
					$errors[$element['title']] = $e->getMessage();

					continue;
				}

				if ( $result === null || !$updater->wasSuccessful() ) {
					$status = $updater->getStatus();

					if ( $status->getErrors() ) {
						// If status is okay but there are errors - they are not fatal, just warnings
						if ( $status->isOK() ) {
							$warnings[$element['title']] = $status->getMessage();
						} else {
							$errors[$element['title']] = $status->getMessage();
						}
					}
				}
			}
		}

		$success = true;
		if ( $errors ) {
			$success = false;
		}

		if ( $errors ) {
			$this->getResult()->addValue( null, 'errors', $errors );
		}
		if ( $warnings ) {
			$this->getResult()->addValue( null, 'warnings', $warnings );
		}

		$this->getResult()->addValue( null, 'success', $success );
	}
}
