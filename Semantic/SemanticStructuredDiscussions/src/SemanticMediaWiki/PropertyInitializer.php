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

namespace SemanticStructuredDiscussions\SemanticMediaWiki;

use SMW\PropertyRegistry;

/**
 * Initializes the predefined properties required by for Structured Discussions.
 */
class PropertyInitializer {
	/**
	 * @var PropertyRegistry The PropertyRegistry in which to initialise the predefined properties
	 */
	private PropertyRegistry $propertyRegistry;

	/**
	 * @param PropertyRegistry $propertyRegistry The PropertyRegistry in which to initialise the predefined properties
	 */
	public function __construct( PropertyRegistry $propertyRegistry ) {
		$this->propertyRegistry = $propertyRegistry;
	}

	/**
	 * Initialize the predefined properties.
	 *
	 * @link https://github.com/SemanticMediaWiki/SemanticMediaWiki/blob/master/docs/examples/hook.property.initproperties.md
	 */
	public function initializeProperties(): void {
		$definitions = $this->getPropertyDefinitions();

		foreach ( $definitions as $propertyId => $definition ) {
			$this->propertyRegistry->registerProperty(
				$propertyId,
				$definition['type'],
				$definition['label'] ?? false,
				$definition['viewable'] ?? false,
				$definition['annotable'] ?? true,
				$definition['declarative'] ?? false
			);

			if ( isset( $definition['alias'] ) ) {
				$this->propertyRegistry->registerPropertyAlias(
					$propertyId,
					wfMessage( $definition['alias'] )->text()
				);

				$this->propertyRegistry->registerPropertyAliasByMsgKey(
					$propertyId,
					$definition['alias']
				);
			}

			if ( isset( $definition['description'] ) ) {
				$this->propertyRegistry->registerPropertyDescriptionByMsgKey(
					$propertyId,
					$definition['description']
				);
			}
		}
	}

	/**
	 * Returns the property definitions.
	 *
	 * @return array[]
	 */
	private function getPropertyDefinitions(): array {
		$definitions = [];
		$annotations = AnnotatorStore::getAnnotators();

		foreach ( $annotations as $annotation ) {
			$definitions[$annotation::getId()] = $annotation::getDefinition();
		}

		return $definitions;
	}
}
