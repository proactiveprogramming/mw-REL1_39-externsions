<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Crud;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use ExternalStoreException;
use InvalidArgumentException;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Mcr\RecordingAnnotationsCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\LanguageCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\ManuscriptPromptCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\RecordingReviewCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserCrud;
use MediaWiki\WikispeechSpeechDataCollector\Crud\Rdbms\UserDialectCrud;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptDomain;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Persistent;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentSet;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitor;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotations;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;

/**
 * Loads a graph of associated aggregated persistent objects per persistent class
 * as defined by the code of this visitor.
 *
 * @since 0.1.0
 */
class PersistentRootGraphLoader implements PersistentVisitor {

	/** @var PersistentSet */
	private $loadedInstances;

	/** @var CrudContext */
	private $context;

	/**
	 * @param CrudContext $context
	 * @param PersistentSet|null $loadedInstances
	 * @since 0.1.0
	 */
	public function __construct(
		CrudContext $context,
		PersistentSet $loadedInstances = null
	) {
		$this->context = $context;
		$this->loadedInstances = $loadedInstances ?? new PersistentSet();
	}

	/**
	 * @return PersistentSet
	 * @since 0.1.0
	 */
	public function getLoadedInstances(): PersistentSet {
		return $this->loadedInstances;
	}

	/**
	 * @param Persistent $instance
	 * @throws InvalidArgumentException If identity is not set.
	 * @since 0.1.0
	 */
	private function visitRoot( Persistent $instance ) {
		if ( $instance->getIdentity() === null ) {
			throw new InvalidArgumentException( 'Instance must be loaded.' );
		}
		$this->loadedInstances->add( $instance );
	}

	/**
	 * @param Language $language
	 * @return null
	 * @since 0.1.0
	 */
	public function visitLanguage(
		Language $language
	) {
		$this->visitRoot( $language );
		return null;
	}

	/**
	 * @param Manuscript $manuscript
	 * @return null
	 * @since 0.1.0
	 */
	public function visitManuscript(
		Manuscript $manuscript
	) {
		$this->visitRoot( $manuscript );
		return null;
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return null
	 * @since 0.1.0
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	) {
		$this->visitRoot( $manuscriptDomain );
		return null;
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return null
	 * @since 0.1.0
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	) {
		$this->visitRoot( $manuscriptPrompt );
		return null;
	}

	/**
	 * Loads user voice of.
	 * Loads spoken dialect.
	 * Loads spoken dialect language
	 * Loads all reviews (but not the reviewer user).
	 * Loads annotations and annotation stereotypes.
	 * Loads manuscript prompt.
	 * Loads manuscript.
	 * Loads manuscript language.
	 * Loads manuscript domains recursive to manuscript domain root.
	 *
	 * @param Recording $recording
	 * @return null
	 * @throws ExternalStoreException If unable to load an aggregated instance.
	 * @since 0.1.0
	 */
	public function visitRecording(
		Recording $recording
	) {
		$this->visitRoot( $recording );

		if ( $recording->getSpokenDialect() ) {
			$spokenDialect = new UserDialect();
			$spokenDialect->setIdentity( $recording->getSpokenDialect() );
			if ( !$this->loadedInstances->contains( $spokenDialect ) ) {
				$userDialectCrud = new UserDialectCrud( $this->context );
				if ( !$userDialectCrud->load( $spokenDialect ) ) {
					throw new ExternalStoreException( 'Unable to load ' . $spokenDialect );
				}
				$this->loadedInstances->add( $spokenDialect );
			} else {
				$spokenDialect = $this->loadedInstances->get( $spokenDialect );
			}

			if ( $spokenDialect->getLanguage() ) {
				$language = new Language();
				$language->setIdentity( $spokenDialect->getLanguage() );
				if ( !$this->loadedInstances->contains( $language ) ) {
					$languageCrud = new LanguageCrud( $this->context );
					if ( !$languageCrud->load( $language ) ) {
						throw new ExternalStoreException( 'Unable to load ' . $language );
					}
					$this->loadedInstances->add( $language );
				}
			}
		}

		if ( $recording->getVoiceOf() ) {
			$voiceOf = new User();
			$voiceOf->setIdentity( $recording->getVoiceOf() );
			if ( !$this->loadedInstances->contains( $voiceOf ) ) {
				$userCrud = new UserCrud( $this->context );
				if ( !$userCrud->load( $voiceOf ) ) {
					throw new ExternalStoreException( 'Unable to load ' . $voiceOf );
				}
				$this->loadedInstances->add( $voiceOf );
			} else {
				$voiceOf = $this->loadedInstances->get( $voiceOf );
			}

			// @todo load UserDialects and UserLanguageProficiencyLevels + languages
			// that are matching the recorded manuscript prompt manuscript language
		}

		$reviewCrud = new RecordingReviewCrud( $this->context );
		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		$reviews = $reviewCrud->listByRecording( $recording->getIdentity() );
		if ( $reviews ) {
			$this->loadedInstances->addAll( $reviews );
		}

		$recordingAnnotationsCrud = new RecordingAnnotationsCrud( $this->context );
		$recordingAnnotations = $recordingAnnotationsCrud->read( $recording->getIdentity() );
		if ( $recordingAnnotations !== null ) {
			$this->loadedInstances->add( $recordingAnnotations );
		}

		if ( $recording->getManuscriptPrompt() ) {
			$manuscriptPrompt = new ManuscriptPrompt();
			$manuscriptPrompt->setIdentity( $recording->getManuscriptPrompt() );
			if ( !$this->loadedInstances->contains( $manuscriptPrompt ) ) {
				$manuscriptPromptCrud = new ManuscriptPromptCrud( $this->context );
				if ( !$manuscriptPromptCrud->load( $manuscriptPrompt ) ) {
					throw new ExternalStoreException( 'Unable to load ' . $manuscriptPrompt );
				}
				$this->loadedInstances->add( $manuscriptPrompt );
			} else {
				$manuscriptPrompt = $this->loadedInstances->get( $manuscriptPrompt );
			}

			if ( $manuscriptPrompt->getManuscript() ) {
				$manuscript = new Manuscript();
				$manuscript->setIdentity( $manuscriptPrompt->getManuscript() );
				if ( !$this->loadedInstances->contains( $manuscript ) ) {
					$manuscriptCrud = new ManuscriptCrud( $this->context );
					if ( !$manuscriptCrud->load( $manuscript ) ) {
						throw new ExternalStoreException( 'Unable to load ' . $manuscript );
					}
					$this->loadedInstances->add( $manuscript );
				}

				if ( $manuscript->getDomain() ) {
					$manuscriptDomain = new ManuscriptDomain();
					$manuscriptDomain->setIdentity( $manuscript->getDomain() );
					if ( !$this->loadedInstances->contains( $manuscriptDomain ) ) {
						$manuscriptDomainCrud = new ManuscriptCrud( $this->context );
						if ( !$manuscriptDomainCrud->load( $manuscriptDomain ) ) {
							throw new ExternalStoreException( 'Unable to load ' . $manuscriptDomain );
						}
						$this->loadedInstances->add( $manuscriptDomain );
					}

					// load parents to root
					while ( $manuscriptDomain->getParent() ) {
						$parentManuscriptDomain = new ManuscriptDomain();
						$parentManuscriptDomain->setIdentity( $manuscriptDomain->getParent() );
						if ( !$this->loadedInstances->contains( $parentManuscriptDomain ) ) {
							$manuscriptDomainCrud = new ManuscriptCrud( $this->context );
							if ( !$manuscriptDomainCrud->load( $parentManuscriptDomain ) ) {
								throw new ExternalStoreException( 'Unable to load ' . $parentManuscriptDomain );
							}
							$this->loadedInstances->add( $manuscriptDomain );
							$manuscriptDomain = $parentManuscriptDomain;
						} else {
							$manuscriptDomain = $this->loadedInstances->get( $parentManuscriptDomain );
						}
					}
				}

				if ( $manuscript->getLanguage() ) {
					$language = new Language();
					$language->setIdentity( $manuscript->getLanguage() );
					if ( !$this->loadedInstances->contains( $language ) ) {
						$languageCrud = new LanguageCrud( $this->context );
						if ( !$languageCrud->load( $language ) ) {
							throw new ExternalStoreException( 'Unable to load ' . $language );
						}
						$this->loadedInstances->add( $language );
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return null
	 * @since 0.1.0
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	) {
		$this->visitRoot( $recordingAnnotations );
		return null;
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return null
	 * @since 0.1.0
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	) {
		$this->visitRoot( $recordingReview );
		return null;
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return null
	 * @since 0.1.0
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	) {
		$this->visitRoot( $skippedManuscriptPrompt );
		return null;
	}

	/**
	 * @param User $user
	 * @return null
	 * @since 0.1.0
	 */
	public function visitUser(
		User $user
	) {
		$this->visitRoot( $user );
		return null;
	}

	/**
	 * @param UserDialect $userDialect
	 * @return null
	 * @since 0.1.0
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	) {
		$this->visitRoot( $userDialect );
		return null;
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return null
	 * @since 0.1.0
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	) {
		$this->visitRoot( $languageProficiencyLevel );
		return null;
	}

}
