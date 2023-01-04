<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use Hamcrest\Core\IsSame;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotation;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Exporter\Exporter;

/**
 * @todo extract shared methods with PersistentEqualsConstraint
 * to new abstract class DomainClassEqualsConstraint
 * https://phabricator.wikimedia.org/T274826
 *
 * @since 0.1.0
 */
class RecordingAnnotationEqualsConstraint extends Constraint {

	/** @var RecordingAnnotation */
	private $expected;

	/** @var bool */
	private $success = true;

	/** @var array */
	private $failedFields = [];

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotation $expected
	 */
	public function __construct( RecordingAnnotation $expected ) {
		$this->expected = $expected;
	}

	/**
	 * @return array
	 */
	protected function getFailedFields(): array {
		return $this->failedFields;
	}

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotation $actual
	 * @param string $description
	 * @param bool|null $returnResult
	 * @return bool|null Whether or not $actual passed assertion, or null if $returnResult is false.
	 * @throws ExpectationFailedException if expected is not equal and $returnResult is false.
	 */
	final public function evaluate(
		$actual,
		string $description = '',
		bool $returnResult = false
	): ?bool {
		if ( $actual === null && $this->expected === null ) {
			return $this->returnSuccessOrFail( $actual, $returnResult, $description );
		}
		if ( $actual !== null && $this->expected === null ) {
			if ( !$returnResult ) {
				$this->fail( $actual, $description . "\nExpected to be null." );
			}
			return false;
		}
		if ( $actual === null && $this->expected !== null ) {
			if ( !$returnResult ) {
				$this->fail( $actual, $description . "\nExpected to be not null." );
			}
			return false;
		}

		$this->evaluateFields( $this->expected, $actual );
		return $this->returnSuccessOrFail( $actual, $returnResult, $description );
	}

	/**
	 * @since 0.1.0
	 * @param string $field
	 * @param mixed|null $expected
	 * @param mixed|null $actual
	 */
	protected function failField(
		string $field,
		$expected,
		$actual
	) {
		$this->success = false;
		array_push( $this->failedFields, [
			'field' => $field,
			'expected' => $expected,
			'actual' => $actual
		] );
	}

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotation|null $actual
	 * @param bool $returnResult
	 * @param string $description
	 * @return bool Whether or not $actual passed assertion, or null if $returnResult is false.
	 * @throws ExpectationFailedException if expected is not equal and $returnResult is false.
	 */
	protected function returnSuccessOrFail(
		?RecordingAnnotation $actual,
		bool $returnResult,
		string $description
	): ?bool {
		if ( $returnResult ) {
			return $this->success;
		}
		if ( !$this->success ) {
			$exporter = new Exporter();
			$message = "$description \n";
			foreach ( $this->failedFields as $failedField ) {
				$message .= $failedField['field'] . ' does not match expected ' .
					$exporter->export( $failedField['expected'] ) . ', is ' .
					$exporter->export( $failedField['actual'] ) . "\n";
			}
			$message .= "\nin object\n" . $exporter->export( $this->expected );
			$this->fail( $actual, $message );
		}
		return null;
	}

	/**
	 * @since 0.1.0
	 * @return string
	 */
	public function toString(): string {
		return 'RecordingAnnotation equals';
	}

	/**
	 * @since 0.1.0
	 * @param string $field
	 * @param mixed|null $expected
	 * @param mixed|null $actual
	 */
	protected function matchIsSame(
		string $field,
		$expected,
		$actual
	) {
		$matcher = new IsSame( $expected );
		if ( !$matcher->matches( $actual ) ) {
			$this->failField( $field, $expected, $actual );
		}
	}

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotation $expected
	 * @param RecordingAnnotation $actual
	 */
	protected function evaluateFields(
		$expected,
		$actual
	) {
		$this->matchIsSame( 'start', $expected->getStart(), $actual->getStart() );
		$this->matchIsSame( 'end',	$expected->getEnd(), $actual->getEnd() );
		$this->matchIsSame( 'stereotype', $expected->getStereotype(), $actual->getStereotype() );
		$this->matchIsSame( 'value', $expected->getValue(), $actual->getValue() );
	}

}
