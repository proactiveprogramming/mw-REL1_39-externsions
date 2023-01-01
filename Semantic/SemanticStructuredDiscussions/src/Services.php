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

namespace SemanticStructuredDiscussions;

use MediaWiki\MediaWikiServices;
use SemanticStructuredDiscussions\SemanticMediaWiki\AnnotatorStore;
use SemanticStructuredDiscussions\SemanticMediaWiki\DataAnnotator;
use SemanticStructuredDiscussions\StructuredDiscussions\TopicRepository;
use Wikimedia\Services\ServiceContainer;

/**
 * Data-class for all SemanticStructuredDiscussions services. This class reduces the risk of mistyping
 * a service name and serves as the interface for retrieving services for SemanticStructuredDiscussions.
 *
 * @note Program logic should use dependency injection instead of this class wherever possible.
 *
 * @note This class should only contain static methods.
 */
final class Services {
	/**
	 * Disable the construction of this class by making the constructor private.
	 */
	private function __construct() {
	}

	/**
	 * Returns the AnnotatorStore singleton.
	 *
	 * @param ServiceContainer|null $services
	 * @return AnnotatorStore
	 */
	public static function getAnnotatorStore( ?ServiceContainer $services = null ): AnnotatorStore {
		return ( $services ?? MediaWikiServices::getInstance() )
			->getService( 'SemanticStructuredDiscussions.SemanticMediaWiki.AnnotatorStore' );
	}

	/**
	 * Returns the DataAnnotator singleton.
	 *
	 * @param ServiceContainer|null $services
	 * @return DataAnnotator
	 */
	public static function getDataAnnotator( ?ServiceContainer $services = null ): DataAnnotator {
		return ( $services ?? MediaWikiServices::getInstance() )
			->getService( 'SemanticStructuredDiscussions.SemanticMediaWiki.DataAnnotator' );
	}

	/**
	 * Returns the TopicRepository singleton.
	 *
	 * @param ServiceContainer|null $services
	 * @return TopicRepository
	 */
	public static function getTopicRepository( ?ServiceContainer $services = null ): TopicRepository {
		return ( $services ?? MediaWikiServices::getInstance() )
			->getService( 'SemanticStructuredDiscussions.StructuredDiscussions.TopicRepository' );
	}
}
