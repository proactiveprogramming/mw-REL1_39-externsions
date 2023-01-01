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

use MediaWiki\MediaWikiServices;
use SemanticStructuredDiscussions\SemanticMediaWiki\AnnotatorStore;
use SemanticStructuredDiscussions\SemanticMediaWiki\DataAnnotator;
use SemanticStructuredDiscussions\Services;
use SemanticStructuredDiscussions\StructuredDiscussions\TopicRepository;

/**
 * This file is loaded by MediaWiki\MediaWikiServices::getInstance() during the
 * bootstrapping of the dependency injection framework.
 *
 * @file
 */

return [
	/**
	 * Instantiator function for the AnnotatorStore singleton.
	 *
	 * @return AnnotatorStore The AnnotatorStore singleton
	 */
	'SemanticStructuredDiscussions.SemanticMediaWiki.AnnotatorStore' => static function (): AnnotatorStore {
		return new AnnotatorStore();
	},
	/**
	 * Instantiator function for the DataAnnotator singleton.
	 *
	 * @return DataAnnotator The DataAnnotator singleton
	 */
	'SemanticStructuredDiscussions.SemanticMediaWiki.DataAnnotator' => static function (
		MediaWikiServices $services
	): DataAnnotator {
		return new DataAnnotator( Services::getAnnotatorStore( $services ) );
	},
	/**
	 * Instantiator function for the TopicRepository singleton.
	 *
	 * @return TopicRepository The TopicRepository singleton
	 */
	'SemanticStructuredDiscussions.StructuredDiscussions.TopicRepository' => static function (): TopicRepository {
		return new TopicRepository();
	},
];
