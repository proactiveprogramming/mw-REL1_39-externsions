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

use Title;

/**
 * Contains methods for retrieving Topic objects from Structured Discussions.
 */
class TopicRepository {
	use CallSubmoduleTrait;

	/**
	 * Retrieves the Topic for a given Title. This Title object should be the Title of the actual topic page in the
	 * Topic namespace (NS_TOPIC).
	 *
	 * @param Title $title
	 * @return SDTopic|null The corresponding Topic, or NULL when the topic does not exist or when something went wrong
	 */
	public function getByTitle( Title $title ): ?SDTopic {
		$parameters = [ 'page' => $title->getFullText(), 'vtformat' => 'html' ];
		$viewTopic = $this->callSubmodule( 'view-topic', $parameters );

		if ( $viewTopic === null ) {
			return null;
		}

		if ( !isset( $viewTopic['topic'] ) ) {
			return null;
		}

		return new SDTopic( $viewTopic['topic'] );
	}
}
