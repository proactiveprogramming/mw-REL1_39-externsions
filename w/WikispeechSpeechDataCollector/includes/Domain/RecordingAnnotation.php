<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

/**
 * Metadata describing a part between a start and an endpoint
 * of the audio in a {@link Recording}.
 *
 * @since 0.1.0
 */
class RecordingAnnotation {

	/** @var int|null Start position in milliseconds of recorded audio. */
	private $start;

	/** @var int|null End posision in milliseconds of recorded audio. */
	private $end;

	/**
	 * @var string|null What sort of values this is, e.g. "White noise level"
	 */
	private $stereotype;

	/**
	 * @var mixed The value associated with the stereotype. E.g. "-9dBm".
	 */
	private $value;

	public function __toString(): string {
		return '[ ' .
			'start => "' . $this->getStart() . '", ' .
			'end => "' . $this->getEnd() . '", ' .
			'stereotype => "' . $this->getStereotype() . '", ' .
			'value => "' . $this->getValue() . '" ' .
			']';
	}

	// getters and setters

	/**
	 * @see RecordingAnnotation::$start
	 * @return int|null
	 */
	public function getStart(): ?int {
		return $this->start;
	}

	/**
	 * @see RecordingAnnotation::$start
	 * @param int|null $start
	 */
	public function setStart( ?int $start ): void {
		$this->start = $start;
	}

	/**
	 * @see RecordingAnnotation::$end
	 * @return int|null
	 */
	public function getEnd(): ?int {
		return $this->end;
	}

	/**
	 * @see RecordingAnnotation::$end
	 * @param int|null $end
	 */
	public function setEnd( ?int $end ): void {
		$this->end = $end;
	}

	/**
	 * @see RecordingAnnotation::$stereotype
	 * @return string|null
	 */
	public function getStereotype(): ?string {
		return $this->stereotype;
	}

	/**
	 * @see RecordingAnnotation::$stereotype
	 * @param string|null $stereotype
	 */
	public function setStereotype( ?string $stereotype ): void {
		$this->stereotype = $stereotype;
	}

	/**
	 * @see RecordingAnnotation::$value
	 * @return mixed
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @see RecordingAnnotation::$value
	 * @param mixed $value
	 */
	public function setValue( $value ): void {
		$this->value = $value;
	}
}
