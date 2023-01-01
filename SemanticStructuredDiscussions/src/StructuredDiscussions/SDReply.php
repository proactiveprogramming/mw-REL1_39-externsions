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

/**
 * Class that serves as a wrapper over a reply revision from the "view-topic" API submodule.
 *
 * @link https://www.mediawiki.org/w/api.php?action=help&modules=flow%2Bview-topic
 */
final class SDReply {
	/**
	 * @var array
	 */
	private array $replyRevision;

	/**
	 * Reply constructor.
	 *
	 * @param array $replyRevision The revision belonging to this reply (in the format of the "view-topic" API module)
	 */
	public function __construct( array $replyRevision ) {
		$this->replyRevision = $replyRevision;
	}

	/**
	 * Returns the postId of this comment.
	 *
	 * @return string
	 */
	public function getPostId(): string {
		return $this->replyRevision['postId'];
	}

	/**
	 * Returns the content of this revision with HTML tags stripped.
	 *
	 * @return string
	 */
	public function getContent(): string {
		return strip_tags( $this->replyRevision['content']['content'] );
	}

	/**
	 * Returns the creator of this comment.
	 *
	 * @return string
	 */
	public function getCreator(): string {
		return $this->replyRevision['creator']['name'];
	}

	/**
	 * Returns the timestamp on which this comment was last edited.
	 *
	 * @return array
	 */
	public function getLastModifiedTimestamp(): array {
		return date_parse_from_format( 'YmdHis', $this->replyRevision['timestamp'] );
	}

	/**
	 * Returns true if this reply is moderated.
	 *
	 * @return bool
	 */
	public function isModerated(): bool {
		return $this->replyRevision['isModerated'];
	}

	/**
	 * Returns the moderation state.
	 *
	 * @return string
	 */
	public function getModerationState(): string {
		return $this->replyRevision['moderateState'] ?? '';
	}

	/**
	 * Returns true if any user that is able to view the topic is also able to view this reply. This is not
	 * always the case, since a reply can be hidden, suppressed or deleted.
	 *
	 * @return bool
	 */
	public function isEveryoneAllowed(): bool {
		return !$this->isModerated();
	}
}
