<?php

namespace MediaWiki\WikispeechSpeechDataCollector\CRUD;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\LanguageCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptDomainCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptPromptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\RecordingCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\RecordingReviewCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\SkippedManuscriptPromptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserDialectCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserLanguageProficiencyLevelCrud;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptDomain;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitor;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotations;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;

/**
 * A {@link PersistentVisitor}
 * that return the corresponding {@link Crud}
 * that represents the given {@link Persistent}.
 *
 * @since 0.1.0
 */
class CrudFactory implements PersistentVisitor {

	/** @var CrudContext */
	private $context;

	/**
	 * @param CrudContext $context
	 * @since 0.1.0
	 */
	public function __construct(
		CrudContext $context
	) {
		$this->context = $context;
	}

	/**
	 * @param Language $language
	 * @return LanguageCrud
	 */
	public function visitLanguage(
		Language $language
	): LanguageCrud {
		return new LanguageCrud( $this->context );
	}

	/**
	 * @param Manuscript $manuscript
	 * @return ManuscriptCrud
	 */
	public function visitManuscript(
		Manuscript $manuscript
	): ManuscriptCrud {
		return new ManuscriptCrud( $this->context );
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return ManuscriptDomainCrud
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	): ManuscriptDomainCrud {
		return new ManuscriptDomainCrud( $this->context );
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return ManuscriptPromptCrud
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	): ManuscriptPromptCrud {
		return new ManuscriptPromptCrud( $this->context );
	}

	/**
	 * @param Recording $recording
	 * @return RecordingCrud
	 */
	public function visitRecording(
		Recording $recording
	): RecordingCrud {
		return new RecordingCrud( $this->context );
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return RecordingAnnotationsCrud
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	): RecordingAnnotationsCrud {
		return new RecordingAnnotationsCrud( $this->context );
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return RecordingReviewCrud
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	): RecordingReviewCrud {
		return new RecordingReviewCrud( $this->context );
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return SkippedManuscriptPromptCrud
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	): SkippedManuscriptPromptCrud {
		return new SkippedManuscriptPromptCrud( $this->context );
	}

	/**
	 * @param User $user
	 * @return UserCrud
	 */
	public function visitUser(
		User $user
	): UserCrud {
		return new UserCrud( $this->context );
	}

	/**
	 * @param UserDialect $userDialect
	 * @return UserDialectCrud
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	): UserDialectCrud {
		return new UserDialectCrud( $this->context );
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return UserLanguageProficiencyLevelCrud
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	): UserLanguageProficiencyLevelCrud {
		return new UserLanguageProficiencyLevelCrud( $this->context );
	}

}
