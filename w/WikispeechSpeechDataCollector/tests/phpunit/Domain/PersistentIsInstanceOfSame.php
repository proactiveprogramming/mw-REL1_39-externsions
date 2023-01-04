<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptDomain;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitor;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotations;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;

/**
 * Visitor functions returns true if the instance passed to constructor
 * is an instance of the same class as visited.
 *
 * @since 0.1.0
 */
class PersistentIsInstanceOfSame implements PersistentVisitor {

	/** @var Persistent */
	private $other;

	/**
	 * @since 0.1.0
	 * @param Persistent $other
	 */
	public function __construct(
		Persistent $other
	) {
		$this->other = $other;
	}

	/**
	 * @since 0.1.0
	 * @param Language $language
	 * @return bool
	 */
	public function visitLanguage(
		Language $language
	): bool {
		return $this->other instanceof Language;
	}

	/**
	 * @since 0.1.0
	 * @param Manuscript $manuscript
	 * @return bool
	 */
	public function visitManuscript(
		Manuscript $manuscript
	): bool {
		return $this->other instanceof Manuscript;
	}

	/**
	 * @since 0.1.0
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return bool
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	): bool {
		return $this->other instanceof ManuscriptDomain;
	}

	/**
	 * @since 0.1.0
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return bool
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	): bool {
		return $this->other instanceof ManuscriptPrompt;
	}

	/**
	 * @since 0.1.0
	 * @param Recording $recording
	 * @return bool
	 */
	public function visitRecording(
		Recording $recording
	): bool {
		return $this->other instanceof Recording;
	}

	/**
	 * @since 0.1.0
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return bool
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	): bool {
		return $this->other instanceof RecordingAnnotations;
	}

	/**
	 * @since 0.1.0
	 * @param RecordingReview $recordingReview
	 * @return bool
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	): bool {
		return $this->other instanceof RecordingReview;
	}

	/**
	 * @since 0.1.0
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return bool
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	): bool {
		return $this->other instanceof SkippedManuscriptPrompt;
	}

	/**
	 * @since 0.1.0
	 * @param User $user
	 * @return bool
	 */
	public function visitUser( User $user ): bool {
		return $this->other instanceof User;
	}

	/**
	 * @since 0.1.0
	 * @param UserDialect $userDialect
	 * @return bool
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	): bool {
		return $this->other instanceof UserDialect;
	}

	/**
	 * @since 0.1.0
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return bool
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	): bool {
		return $this->other instanceof UserLanguageProficiencyLevel;
	}
}
