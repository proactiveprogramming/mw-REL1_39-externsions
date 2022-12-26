<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use CommentStoreComment;
use ExternalStoreException;
use InvalidArgumentException;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\PageUpdater;
use MediaWiki\WikispeechSpeechDataCollector\Crud\AbstractCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\CrudContext;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;

/**
 * Multi Content Revision mapping and access for
 * instances of the corresponding underlying subclass of {@link Persistent}.
 *
 * @since 0.1.0
 */
abstract class AbstractMcrCrud extends AbstractCrud {

	/** @var string|null Edit summary set when saving wiki page */
	private $editSummary;

	/** @var AbstractMcrCrudIdentityStrategy */
	private $identityStrategy;

	/**
	 * @param CrudContext $context
	 * @param AbstractMcrCrudIdentityStrategy $identityStrategy
	 * @since 0.1.0
	 */
	public function __construct(
		CrudContext $context,
		AbstractMcrCrudIdentityStrategy $identityStrategy
	) {
		parent::__construct( $context );
		$this->identityStrategy = $identityStrategy;
	}

	/**
	 * Adds the instance to the page in a way it can be deserialized at a later time.
	 *
	 * @param Persistent $instance
	 * @param PageUpdater $pageUpdater
	 * @see AbstractMcrCrud::deserialize()
	 * @since 0.1.0
	 */
	abstract public function serialize(
		Persistent $instance,
		PageUpdater $pageUpdater
	): void;

	/**
	 * @param Persistent $instance
	 * @param PageUpdater $pageUpdater
	 * @since 0.1.0
	 */
	protected function setMainSlotToIdentityJson(
		Persistent $instance,
		PageUpdater $pageUpdater
	): void {
		if ( $instance->getIdentity() === null ) {
			throw new InvalidArgumentException( 'Identity not set' );
		}
		$slotRecord = SlotRecord::newUnsaved(
			SlotRecord::MAIN,
			$this->identityStrategy->identityJsonContentFactory( $instance->getIdentity() )
		);
		$pageUpdater->setSlot( $slotRecord );
	}

	/**
	 * Deserializes an instance from the content of a page.
	 *
	 * @param Persistent $instance
	 * @param RevisionRecord $revisionRecord
	 * @return bool true if deserialized, false if not found or deserialized to null.
	 * @since 0.1.0
	 */
	abstract public function deserialize(
		Persistent $instance,
		RevisionRecord $revisionRecord
	): bool;

	/**
	 * @inheritDoc
	 * @since 0.10.
	 */
	public function create( Persistent $instance ): void {
		$page = $this->getIdentityStrategy()->getWikiPage(
			$instance->getIdentity(),
			$this->getContext()
		);
		$pageUpdater = $page->newPageUpdater( $this->getContext()->getMediawikiUser() );
		$this->setMainSlotToIdentityJson( $instance, $pageUpdater );
		$this->serialize( $instance, $pageUpdater );
		$revisionRecord = $pageUpdater->saveRevision( $this->assembleCommentStoreComment() );
		if ( !$pageUpdater->wasSuccessful() ) {
			throw new ExternalStoreException(
				'Failed to create page: ' . $pageUpdater->getStatus()
			);
		}
	}

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * updates the domain object to correspond data retrieved from the database.
	 *
	 * @see read()
	 * @param Persistent $instance Instance to be loaded. Identity must be set.
	 * @throws InvalidArgumentException If instance identity is not set
	 * @throws ExternalStoreException If the page exists but has no revision record.
	 * @return bool true if found, false if not found.
	 * @since 0.1.0
	 */
	public function load( Persistent $instance ): bool {
		if ( $instance->getIdentity() === null ) {
			throw new InvalidArgumentException( 'Identity not set' );
		}
		$page = $this->getIdentityStrategy()->getWikiPage(
			$instance->getIdentity(),
			$this->getContext()
		);
		if ( !$page->exists() ) {
			return false;
		}
		/** @var $revisionRecord RevisionRecord|bool */
		$revisionRecord = $this->getContext()->getRevisionStore()->getKnownCurrentRevision(
			$page->getTitle() );
		if ( $revisionRecord === false ) {
			throw new ExternalStoreException(
				'The page ' . $page->getTitle() . ' exists, but there is no revision record!'
			);
		}
		return $this->deserialize( $instance, $revisionRecord );
	}

	/**
	 * Given a persistent domain object instance with at least identity set,
	 * updates the database to correspond to the data set in the domain object.
	 *
	 * @param Persistent $instance
	 * @throws InvalidArgumentException If instance identity is not set
	 * @throws ExternalStoreException If the page does not exist, or if the update fails.
	 * @since 0.1.0
	 */
	public function update( Persistent $instance ): void {
		if ( $instance->getIdentity() === null ) {
			throw new InvalidArgumentException( 'Identity not set' );
		}
		$page = $this->getIdentityStrategy()->getWikiPage(
			$instance->getIdentity(),
			$this->getContext()
		);
		if ( !$page->exists() ) {
			throw new ExternalStoreException( 'Page does not exist' );
		}
		$pageUpdater = $page->newPageUpdater( $this->getContext()->getMediawikiUser() );
		// @todo Should we assert that identity in main slot equals instance identity?
		$this->serialize( $instance, $pageUpdater );
		$revisionRecord = $pageUpdater->saveRevision( $this->assembleCommentStoreComment() );
		if ( !$pageUpdater->wasSuccessful() ) {
			throw new ExternalStoreException(
				'Failed to update page: ' . $pageUpdater->getStatus()
			);
		}
	}

	/**
	 * Given an identity,
	 * removes the corresponding persistent domain object from the database.
	 *
	 * @param mixed $identity
	 * @throws InvalidArgumentException If the identity is null.
	 * @throws ExternalStoreException If unable to delete the underlying article.
	 * @since 0.1.0
	 */
	public function delete( $identity ): void {
		if ( $identity === null ) {
			throw new InvalidArgumentException( 'Identity not set' );
		}
		$page = $this->getIdentityStrategy()->getWikiPage(
			$identity,
			$this->getContext()
		);
		if ( !$page->exists() ) {
			return;
		}
		$status = $page->doDeleteArticleReal(
			$this->assembleSummary(),
			$this->getContext()->getMediawikiUser()
		);
		if ( !$status->isOK() ) {
			throw new ExternalStoreException(
				"Failed to delete instance with identity $identity: $status"
			);
		}
	}

	/**
	 * @return CommentStoreComment
	 * @since 0.1.0
	 */
	private function assembleCommentStoreComment(): CommentStoreComment {
		return CommentStoreComment::newUnsavedComment(
			$this->assembleSummary()
		);
	}

	/**
	 * @return string
	 * @since 0.1.0
	 */
	private function assembleSummary(): string {
		// @phan-suppress-next-line PhanTypeMismatchReturnNullable
		return $this->getEditSummary() === null ? '' : $this->getEditSummary();
	}

	/**
	 * @return string|null
	 * @since 0.1.0
	 */
	public function getEditSummary(): ?string {
		return $this->editSummary;
	}

	/**
	 * @param string|null $editSummary
	 * @since 0.1.0
	 */
	public function setEditSummary( ?string $editSummary ): void {
		$this->editSummary = $editSummary;
	}

	/**
	 * @return AbstractMcrCrudIdentityStrategy
	 * @since 0.1.0
	 */
	public function getIdentityStrategy(): AbstractMcrCrudIdentityStrategy {
		return $this->identityStrategy;
	}

}
