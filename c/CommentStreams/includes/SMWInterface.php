<?php
/*
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace MediaWiki\Extension\CommentStreams;

use ConfigException;
use ExtensionRegistry;
use JobQueueGroup;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\User\UserIdentity;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\PropertyRegistry;
use SMW\SemanticData;
use SMW\Store;
use SMW\StoreFactory;
use SMWDataItem;
use SMWDIBlob;
use SMWDINumber;
use Title;

class SMWInterface {
	public const CONSTRUCTOR_OPTIONS = [
		'CommentStreamsEnableVoting'
	];

	/**
	 * @var bool
	 */
	private $isLoaded;

	/**
	 * @var CommentStreamsStore
	 */
	private $commentStreamsStore;

	/**
	 * @var WikiPageFactory
	 */
	private $wikiPageFactory;

	/**
	 * @var JobQueueGroup
	 */
	private $jobQueueGroup;

	/**
	 * @var bool
	 */
	private $enableVoting;

	/**
	 * @param ServiceOptions $options
	 * @param ExtensionRegistry $extensionRegistry
	 * @param CommentStreamsStore $commentStreamsStore
	 * @param WikiPageFactory $wikiPageFactory
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct(
		ServiceOptions $options,
		ExtensionRegistry $extensionRegistry,
		CommentStreamsStore $commentStreamsStore,
		WikiPageFactory $wikiPageFactory,
		JobQueueGroup $jobQueueGroup
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->enableVoting = (bool)$options->get( 'CommentStreamsEnableVoting' );
		$this->isLoaded = $extensionRegistry->isLoaded( 'SemanticMediaWiki' );
		$this->commentStreamsStore = $commentStreamsStore;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->jobQueueGroup = $jobQueueGroup;
	}

	/**
	 * @return bool
	 */
	public function isLoaded(): bool {
		return $this->isLoaded;
	}

	/**
	 * @param Title $title
	 */
	public function update( Title $title ) {
		if ( !$this->isLoaded ) {
			return;
		}
		$job = new UpdateJob( $title, [] );
		$this->jobQueueGroup->push( $job );
	}

	/**
	 * return the value of a property on a user page
	 *
	 * @param UserIdentity $user the user
	 * @param string $propertyName the name of the property
	 * @return string|Title|null the value of the property
	 */
	public function getUserProperty( UserIdentity $user, string $propertyName ) {
		if ( !$this->isLoaded ) {
			return null;
		}
		$userpage = Title::makeTitle( NS_USER, $user->getName() );
		if ( $userpage->exists() ) {
			$subject = DIWikiPage::newFromTitle( $userpage );
			$store = StoreFactory::getStore();
			$data = $store->getSemanticData( $subject );
			$property = DIProperty::newFromUserLabel( $propertyName );
			$values = $data->getPropertyValues( $property );
			if ( count( $values ) > 0 ) {
				// this property should only have one value so pick the first one
				$value = $values[0];
				if ( ( defined( 'SMWDataItem::TYPE_STRING' ) &&
						$value->getDIType() == SMWDataItem::TYPE_STRING ) ||
					$value->getDIType() == SMWDataItem::TYPE_BLOB ) {
					return $value->getString();
				} elseif ( $value->getDIType() == SMWDataItem::TYPE_WIKIPAGE ) {
					return $value->getTitle();
				}
			}
		}
		return null;
	}

	/**
	 * Initialize extra Semantic MediaWiki properties.
	 * This won't get called unless Semantic MediaWiki is installed.
	 * @param PropertyRegistry $propertyRegistry
	 */
	public function initProperties( PropertyRegistry $propertyRegistry ) {
		$propertyRegistry->registerProperty( '___CS_ASSOCPG', '_wpg', 'Comment on' );
		$propertyRegistry->registerProperty( '___CS_REPLYTO', '_wpg', 'Reply to' );
		$propertyRegistry->registerProperty( '___CS_TITLE', '_txt', 'Comment title of' );
		if ( $this->enableVoting ) {
			$propertyRegistry->registerProperty( '___CS_UPVOTES', '_num', 'Comment up votes' );
			$propertyRegistry->registerProperty( '___CS_DOWNVOTES', '_num', 'Comment down votes' );
			$propertyRegistry->registerProperty( '___CS_VOTEDIFF', '_num', 'Comment vote diff' );
		}
	}

	/**
	 * Implements Semantic MediaWiki SMWStore::updateDataBefore callback.
	 * This won't get called unless Semantic MediaWiki is installed.
	 * If the comment has not been added to the database yet, which is indicated
	 * by a null associated page id, this function will return early, but it
	 * will be invoked again by an update job.
	 *
	 * @param Store $store semantic data store
	 * @param SemanticData $semanticData semantic data for page
	 * @return bool true to continue
	 * @noinspection PhpUnusedParameterInspection
	 * @throws ConfigException
	 */
	public function updateData( Store $store, SemanticData $semanticData ): bool {
		$subject = $semanticData->getSubject();
		if ( !$subject || !$subject->getTitle() || $subject->getTitle()->getNamespace() !== NS_COMMENTSTREAMS ) {
			return true;
		}

		$pageId = $subject->getTitle()->getArticleID( Title::READ_LATEST );

		$comment = $this->commentStreamsStore->getComment( $pageId );
		if ( !$comment ) {
			$reply = $this->commentStreamsStore->getReply( $pageId );
			if ( !$reply ) {
				return true;
			}

			$parentWikiPage = $this->wikiPageFactory->newFromID( $reply[ 'comment_page_id' ] );
			if ( $parentWikiPage ) {
				$propertyDI = new DIProperty( '___CS_REPLYTO' );
				$dataItem = DIWikiPage::newFromTitle( $parentWikiPage->getTitle() );
				$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
			}

			return true;
		}

		$assocWikiPage = $this->wikiPageFactory->newFromID( $comment[ 'assoc_page_id' ] );
		if ( $assocWikiPage ) {
			$propertyDI = new DIProperty( '___CS_ASSOCPG' );
			$dataItem = DIWikiPage::newFromTitle( $assocWikiPage->getTitle() );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
		}

		$propertyDI = new DIProperty( '___CS_TITLE' );
		$dataItem = new SMWDIBlob( $comment[ 'comment_title' ] );
		$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );

		if ( $this->enableVoting ) {
			$upvotes = $this->commentStreamsStore->getNumUpVotes( $pageId );
			$propertyDI = new DIProperty( '___CS_UPVOTES' );
			$dataItem = new SMWDINumber( $upvotes );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
			$downvotes = $this->commentStreamsStore->getNumDownVotes( $pageId );
			$propertyDI = new DIProperty( '___CS_DOWNVOTES' );
			$dataItem = new SMWDINumber( $downvotes );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
			$votediff = $upvotes - $downvotes;
			$propertyDI = new DIProperty( '___CS_VOTEDIFF' );
			$dataItem = new SMWDINumber( $votediff );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );
		}

		return true;
	}
}
