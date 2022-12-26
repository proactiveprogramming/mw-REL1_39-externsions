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
 * Returns a JSON string representing the Persistent instance.
 *
 * @since 0.1.0
 */
class PersistentJsonSerializer implements PersistentVisitor {

	/**
	 * @param Persistent $instance
	 * @return string JSON representation
	 * @throws MWException If unable to encode instance to JSON.
	 */
	private function visitPersistent(
		Persistent $instance
	): string {
		$array = $instance->accept( new PersistentMWAssociativeArraySerializer() );
		$json = FormatJson::encode( $array );
		if ( $json === false ) {
			throw new MWException( 'Failed to encode instance to JSON' );
		}
		return $json;
	}

	/**
	 * @param Language $language
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitLanguage(
		Language $language
	) {
		return $this->visitPersistent( $language );
	}

	/**
	 * @param Manuscript $manuscript
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitManuscript(
		Manuscript $manuscript
	) {
		return $this->visitPersistent( $manuscript );
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	) {
		return $this->visitPersistent( $manuscriptDomain );
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	) {
		return $this->visitPersistent( $manuscriptPrompt );
	}

	/**
	 * @param Recording $recording
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitRecording(
		Recording $recording
	 ) {
		 return $this->visitPersistent( $recording );
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	) {
		return $this->visitPersistent( $recordingAnnotations );
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	) {
		return $this->visitPersistent( $recordingReview );
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	) {
		return $this->visitPersistent( $skippedManuscriptPrompt );
	}

	/**
	 * @param User $user
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitUser( User $user ) {
		return $this->visitPersistent( $user );
	}

	/**
	 * @param UserDialect $userDialect
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	) {
		return $this->visitPersistent( $userDialect );
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return string JSON representation
	 * @since 0.1.0
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	) {
		return $this->visitPersistent( $languageProficiencyLevel );
	}

}
