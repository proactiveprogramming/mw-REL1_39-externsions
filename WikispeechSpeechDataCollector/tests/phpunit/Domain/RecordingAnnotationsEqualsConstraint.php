<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotations;

/**
 * @since 0.1.0
 */
class RecordingAnnotationsEqualsConstraint extends PersistentEqualsConstraint {

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotations $expected
	 * @param RecordingAnnotations $actual
	 */
	protected function evaluateNonIdentityFields(
		Persistent $expected,
		Persistent $actual
	) {
		if ( $expected->getItems() === null && $actual->getItems() === null ) {
			return;
		}
		if ( ( $expected->getItems() === null && $actual->getItems() !== null ) ||
			( $expected->getItems() !== null && $actual->getItems() === null ) ) {
			$this->failField( 'items', $expected->getItems(), $actual->getItems() );
			return;
		}
		$expectedCount = count( $expected->getItems() );
		$actualCount = count( $actual->getItems() );
		if ( $expectedCount !== $actualCount ) {
			$this->failField( 'items', $expected->getItems(), $actual->getItems() );
			return;
		}
		for ( $itemIndex = 0; $itemIndex < $expectedCount; $itemIndex++ ) {
			$expectedItem = $expected->getItems()[ $itemIndex ];
			$actualItem = $actual->getItems()[ $itemIndex ];
			$constraint = new RecordingAnnotationEqualsConstraint( $expectedItem );
			if ( !$constraint->matches( $actualItem ) ) {
				foreach ( $this->getFailedFields() as $failedField ) {
					$fieldName = "item[$itemIndex]." . $failedField['field'];
					$this->failField( $fieldName, $failedField['expected'], $failedField['actual'] );
				}
			}
		}
	}

}
