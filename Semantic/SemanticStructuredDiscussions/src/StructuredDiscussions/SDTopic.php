<?php declare( strict_types=1 );
/**
 * Semantic Structured Discussions MediaWiki extension
 * Copyright (C) 2022  Wikibase Solutions
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace SemanticStructuredDiscussions\StructuredDiscussions;

use Flow\Container;
use Flow\Exception\CrossWikiException;
use Flow\Exception\InvalidInputException;
use Flow\Model\AbstractRevision;
use Flow\Model\Workflow;
use Title;

/**
 * Class that serves as a wrapper over the "view-topic" API result.
 *
 * @link https://www.mediawiki.org/w/api.php?action=help&modules=flow%2Bview-topic
 */
final class SDTopic {
	/**
	 * @var array The topic info (in the format of the "view-topic" API module)
	 */
	private array $topicInfo;

	/**
	 * @var Title A cache for the associated title (so we don't have to query the database twice)
	 */
	private Title $associatedTitle;

	/**
	 * Topic constructor.
	 *
	 * @param array $topicInfo The topic info retrieved from the "view-topic" API submodule
	 */
	public function __construct( array $topicInfo ) {
		$this->topicInfo = $topicInfo;
	}

	/**
	 * Returns the title of the topic.
	 *
	 * @return string
	 */
	public function getTitle(): string {
		return $this->getRootRevision()['properties']['topic-of-post']['plaintext'];
	}

	/**
	 * Returns the summary of the topic, or NULL if the topic is not summarized.
	 *
	 * @return string|null
	 */
	public function getSummary(): ?string {
		$rootRevision = $this->getRootRevision();

		if ( isset( $rootRevision['summary']['revision']['content']['content'] ) ) {
			return strip_tags( $rootRevision['summary']['revision']['content']['content'] );
		} else {
			return null;
		}
	}

	/**
	 * Returns the user that created the topic.
	 *
	 * @return string
	 */
	public function getCreator(): string {
		return $this->getRootRevision()['creator']['name'];
	}

	/**
	 * Returns the timestamp on which this topic was last edited.
	 *
	 * @return array
	 */
	public function getLastModifiedTimestamp(): array {
		return date_parse_from_format( 'YmdHis', $this->getRootRevision()['timestamp'] );
	}

	/**
	 * Returns true if this topic is moderated.
	 *
	 * @return bool
	 */
	public function isModerated(): bool {
		return $this->getRootRevision()['isModerated'];
	}

	/**
	 * Returns the moderation state.
	 *
	 * @return string
	 */
	public function getModerationState(): string {
		return $this->getRootRevision()['moderateState'] ?? AbstractRevision::MODERATED_NONE;
	}

	/**
	 * Returns the topic's current lock state.
	 *
	 * @return string
	 */
	public function getLockStatus(): bool {
		return $this->getRootRevision()['isLocked'];		
	}

	/**
	 * Returns true if any user that is able to view the topic owner is also able to view this topic.
	 * This is not always the case, since a topic can be hidden, suppressed or deleted.
	 *
	 * @return bool
	 */
	public function isEveryoneAllowed(): bool {
		$moderationState = $this->getModerationState();

		// Everyone is allowed if the topic is not moderated or is locked
		return $moderationState === AbstractRevision::MODERATED_NONE ||
			$moderationState === AbstractRevision::MODERATED_LOCKED;
	}

	/**
	 * Returns the replies to this topic.
	 *
	 * @return SDReply[]
	 */
	public function getReplies(): array {
		$replies = $this->recursiveGetReplies( $this->getRootPostId() );
		$replyRevisions = array_map( fn ( string $postId ) => $this->getRevisionByPostId( $postId ), $replies );

		return array_map( fn ( array $revision ): SDReply => new SDReply( $revision ), $replyRevisions );
	}

	/**
	 * Returns the Title of the owner of this topic. The owner is the board where the topic was created.
	 *
	 * @return Title
	 * @throws CrossWikiException
	 * @throws InvalidInputException
	 */
	public function getTopicOwner(): Title {
		if ( !isset( $this->associatedTitle ) ) {
			$this->associatedTitle = $this->getWorkflow()->getOwnerTitle();
		}

		return $this->associatedTitle;
	}

	/**
	 * Returns the workflow of this topic.
	 *
	 * @return Workflow
	 * @throws CrossWikiException
	 * @throws InvalidInputException
	 */
	private function getWorkflow(): Workflow {
		$workflowFactory = Container::get( 'factory.loader.workflow' );
		$title = Title::makeTitleSafe( NS_TOPIC, $this->topicInfo['workflowId'] );
		$workflowLoader = $workflowFactory->createWorkflowLoader( $title );

		return $workflowLoader->getWorkflow();
	}

	/**
	 * Returns the latest revision for the root of this topic.
	 *
	 * @return array
	 */
	private function getRootRevision(): array {
		return $this->getRevisionByPostId( $this->getRootPostId() );
	}

	/**
	 * Returns the post ID of the root of this topic.
	 *
	 * @return string
	 */
	private function getRootPostId(): string {
		return $this->topicInfo['roots'][0];
	}

	/**
	 * Returns the (latest) revision based on the given post ID.
	 *
	 * @param string $postId
	 * @return array
	 */
	private function getRevisionByPostId( string $postId ): array {
		$revisionId = $this->topicInfo['posts'][$postId][0];

		return $this->topicInfo['revisions'][$revisionId];
	}

	/**
	 * Recursively get the replies for the given post ID. This returns a flat array of all the
	 * replies to a given post, including replies to replies.
	 *
	 * This function is used to get all the replies for a given postId (usually the root/topic postId) in a flat
	 * array, so we can store each reply as a subobject, since nested subobjects are not supported by Semantic
	 * MediaWiki.
	 *
	 * @param string $postId
	 * @return array
	 */
	private function recursiveGetReplies( string $postId ): array {
		$revision = $this->getRevisionByPostId( $postId );
		$replies = $revision['replies'] ?? [];

		foreach ( $replies as $replyPostId ) {
			$replies = array_merge( $replies, $this->recursiveGetReplies( $replyPostId ) );
		}

		return array_unique( $replies );
	}
}
