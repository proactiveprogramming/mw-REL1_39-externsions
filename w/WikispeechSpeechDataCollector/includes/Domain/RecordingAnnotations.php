<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

/**
 * A list of {@link RecordingAnnotation} associated with a {@link Recording}.
 *
 * @since 0.1.0
 */
class RecordingAnnotations implements Persistent {
	/**
	 * Inherited from composite owning Recording.
	 *
	 * @var string|null 128 bits UUID
	 * @see Recording::$identity
	 */
	private $identity;

	/**
	 * @var RecordingAnnotation[]|null
	 */
	private $items;

	// visitor

	/**
	 * @param PersistentVisitor $visitor
	 * @return mixed
	 */
	public function accept( PersistentVisitor $visitor ) {
		return $visitor->visitRecordingAnnotations( $this );
	}

	public function __toString(): string {
		$string = '[ ' .
			'identity => "' . $this->getIdentity() . '", ' .
			'items => ';
		if ( $this->getItems() === null ) {
			$string .= 'null';
		} else {
			$string .= '[';
			foreach ( $this->getItems() as $recordingAnnotation ) {
				$string .= $recordingAnnotation;
				$string .= ', ';
			}
			$string .= ']';
		}
		$string .= ' ]';
		return $string;
	}

	// getters and setters

	/**
	 * @see RecordingAnnotation::$identity
	 * @return string|null
	 */
	public function getIdentity(): ?string {
		return $this->identity;
	}

	/**
	 * @see RecordingAnnotation::$identity
	 * @param string|null $identity
	 */
	public function setIdentity( $identity ): void {
		$this->identity = $identity;
	}

	/**
	 * @return RecordingAnnotation[]|null
	 */
	public function getItems(): ?array {
		return $this->items;
	}

	/**
	 * @param RecordingAnnotation[]|null $items
	 */
	public function setItems( ?array $items ): void {
		$this->items = $items;
	}

}
