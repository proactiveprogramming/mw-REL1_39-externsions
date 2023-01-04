<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

/**
 * Interface PersistentVisitor
 *
 * https://en.wikipedia.org/wiki/Visitor_pattern
 * @since 0.1.0
 */
interface PersistentVisitor {
	/**
	 * @param Language $language
	 * @return mixed|null
	 */
	public function visitLanguage(
		Language $language
	);

	/**
	 * @param Manuscript $manuscript
	 * @return mixed|null
	 */
	public function visitManuscript(
		Manuscript $manuscript
	);

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return mixed|null
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	);

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return mixed|null
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	);

	/**
	 * @param Recording $recording
	 * @return mixed|null
	 */
	public function visitRecording(
		Recording $recording
	);

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return mixed|null
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	);

	/**
	 * @param RecordingReview $recordingReview
	 * @return mixed|null
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	);

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return mixed|null
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	);

	/**
	 * @param User $user
	 * @return mixed|null
	 */
	public function visitUser(
		User $user
	);

	/**
	 * @param UserDialect $userDialect
	 * @return mixed|null
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	);

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return mixed|null
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	);
}
