<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use FormatJson;
use MWException;

/**
 * Loads a persistent instance from a JSON string representation.
 *
 * @since 0.1.0
 */
class PersistentJsonDeserializer implements PersistentVisitor {

	/** @var array|null JSON string representation decoded to an associative array */
	private $array;

	/**
	 * @param string|null $json JSON string representation
	 * @throws MWException If unable to parse JSON string.
	 * @since 0.1.0
	 */
	public function __construct( ?string $json ) {
		if ( $json !== null ) {
			$status = FormatJson::parse( $json, FormatJson::FORCE_ASSOC );
			if ( !$status->isOK() ) {
				throw new MWException( "Failed to parse JSON string: $status" );
			}
			$this->array = $status->getValue();
		}
	}

	/**
	 * @param Language $language
	 * @return Language|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitLanguage(
		Language $language
	) {
		return $language->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param Manuscript $manuscript
	 * @return Manuscript|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitManuscript(
		Manuscript $manuscript
	) {
		return $manuscript->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return ManuscriptDomain|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	) {
		return $manuscriptDomain->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return ManuscriptPrompt|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	) {
		return $manuscriptPrompt->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param Recording $recording
	 * @return Recording|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitRecording(
		Recording $recording
	) {
		return $recording->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return RecordingAnnotations|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	) {
		return $recordingAnnotations->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return RecordingReview|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	) {
		return $recordingReview->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return SkippedManuscriptPrompt|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	) {
		return $skippedManuscriptPrompt->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array ) );
	}

	/**
	 * @param User $user
	 * @return User|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitUser(
		User $user
	) {
		return $user->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param UserDialect $userDialect
	 * @return UserDialect|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	) {
		return $userDialect->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array )
		);
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return UserLanguageProficiencyLevel|null Same instance, or null if serialized value is null.
	 * @since 0.1.0
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	) {
		return $languageProficiencyLevel->accept(
			new PersistentMWAssociateArrayDeserializer( $this->array ) );
	}

}
