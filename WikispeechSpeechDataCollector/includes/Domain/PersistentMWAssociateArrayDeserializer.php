<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use FormatJson;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MWException;
use MWTimestamp;
use Wikimedia\Timestamp\TimestampException;

/**
 * A {@link PersistentVisitor} that populates the instance with data
 * within an associative array produced from MW API request.
 *
 * The visitor methods will return the passed down instance, or null
 * if the associative array is null. Hence common usage looks like this:
 *
 * $persistent = $persistent->accept( new PersistentMWAssociateArrayDeserializer( $array ) );
 *
 * @since 0.1.0
 */
class PersistentMWAssociateArrayDeserializer implements PersistentVisitor {

	/**
	 * @var array|null Associative array produced from MW API request.
	 */
	private $array;

	/**
	 * @param array|null $array Associative array produced from MW API request.
	 */
	public function __construct( ?array $array ) {
		$this->array = $array;
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	private function get( string $key ) {
		return array_key_exists( $key, $this->array ) ? $this->array[$key] : null;
	}

	/**
	 * @param Language $language
	 * @return Language|null
	 */
	public function visitLanguage(
		Language $language
	): ?Language {
		if ( $this->array === null ) {
			return null;
		}
		$language->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$language->setNativeName( $this->get( 'nativeName' ) );
		$language->setIso639a1( $this->get( 'iso639a1' ) );
		$language->setIso639a2b( $this->get( 'iso639a2b' ) );
		$language->setIso639a2t( $this->get( 'iso639a2t' ) );
		$language->setIso639a3( $this->get( 'iso639a3' ) );
		return $language;
	}

	/**
	 * @param Manuscript $manuscript
	 * @return Manuscript|null
	 */
	public function visitManuscript(
		Manuscript $manuscript
	): ?Manuscript {
		if ( $this->array === null ) {
			return null;
		}
		$manuscript->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$manuscript->setName( $this->get( 'name' ) );
		$manuscript->setCreated( $this->deserializeTimestamp( $this->get( 'created' ) ) );
		$manuscript->setDisabled( $this->deserializeTimestamp( $this->get( 'disabled' ) ) );
		$manuscript->setLanguage( $this->deserializeUuid( $this->get( 'language' ) ) );
		$manuscript->setDomain( $this->deserializeUuid( $this->get( 'domain' ) ) );
		return $manuscript;
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return ManuscriptDomain|null
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	): ?ManuscriptDomain {
		if ( $this->array === null ) {
			return null;
		}
		$manuscriptDomain->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$manuscriptDomain->setName( $this->get( 'name' ) );
		$manuscriptDomain->setParent( $this->deserializeUuid( $this->get( 'parent' ) ) );
		return $manuscriptDomain;
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return ManuscriptPrompt|null
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	): ?ManuscriptPrompt {
		if ( $this->array === null ) {
			return null;
		}
		$manuscriptPrompt->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$manuscriptPrompt->setManuscript( $this->deserializeUuid( $this->get( 'manuscript' ) ) );
		$manuscriptPrompt->setIndex( $this->get( 'index' ) );
		$manuscriptPrompt->setContent( $this->get( 'content' ) );
		return $manuscriptPrompt;
	}

	/**
	 * @param Recording $recording
	 * @return Recording|null
	 */
	public function visitRecording(
		Recording $recording
	): ?Recording {
		if ( $this->array === null ) {
			return null;
		}
		$recording->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$recording->setRecorded( $this->deserializeTimestamp( $this->get( 'recorded' ) ) );
		$recording->setVoiceOf( $this->deserializeUuid( $this->get( 'voiceOf' ) ) );
		$recording->setSpokenDialect( $this->deserializeUuid( $this->get( 'spokenDialect' ) ) );
		$recording->setManuscriptPrompt( $this->deserializeUuid( $this->get( 'manuscriptPrompt' ) ) );
		$recording->setAudioFileWikiPageIdentity( $this->get( 'audioFileWikiPageIdentity' ) );
		return $recording;
	}

	/**
	 * @param RecordingAnnotation $recordingAnnotation
	 * @return RecordingAnnotation|null
	 */
	public function visitRecordingAnnotation(
		RecordingAnnotation $recordingAnnotation
	): ?RecordingAnnotation {
		if ( $this->array === null ) {
			return null;
		}
		$recordingAnnotation->setStart( $this->get( 'start' ) );
		$recordingAnnotation->setEnd( $this->get( 'end' ) );
		$recordingAnnotation->setStereotype( $this->get( 'stereotype' ) );
		$recordingAnnotation->setValue( $this->get( 'value' ) );
		return $recordingAnnotation;
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return RecordingAnnotations|null
	 * @throws MWException In case of annotations list contains null entries.
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	): ?RecordingAnnotations {
		if ( $this->array === null ) {
			return null;
		}
		$recordingAnnotations->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$serializedItems = $this->get( 'items' );
		if ( $serializedItems === null || count( $serializedItems ) === 0 ) {
			$recordingAnnotations->setItems( null );
		} else {
			$items = [];
			foreach ( $serializedItems as $serializedItem ) {
				$recordingAnnotation = new RecordingAnnotation();
				$this->array = $serializedItem;
				$recordingAnnotation = $this->visitRecordingAnnotation( $recordingAnnotation );
				if ( $recordingAnnotation === null ) {
					throw new MWException( 'Unexpected null entry in list of annotations.' );
				}
				$items[] = $recordingAnnotation;
			}
			$recordingAnnotations->setItems( $items );
		}
		return $recordingAnnotations;
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return RecordingReview|null
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	): ?RecordingReview {
		if ( $this->array === null ) {
			return null;
		}
		$recordingReview->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$recordingReview->setCreated( $this->deserializeTimestamp( $this->get( 'created' ) ) );
		$recordingReview->setValue( $this->get( 'value' ) );
		$recordingReview->setReviewer( $this->deserializeUuid( $this->get( 'reviewer' ) ) );
		$recordingReview->setRecording( $this->deserializeUuid( $this->get( 'recording' ) ) );
		return $recordingReview;
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return SkippedManuscriptPrompt|null
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	): ?SkippedManuscriptPrompt {
		if ( $this->array === null ) {
			return null;
		}
		$skippedManuscriptPrompt->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$skippedManuscriptPrompt->setManuscriptPrompt(
			$this->deserializeUuid( $this->get( 'manuscriptPrompt' ) ) );
		$skippedManuscriptPrompt->setUser( $this->deserializeUuid( $this->get( 'user' ) ) );
		$skippedManuscriptPrompt->setSkipped( $this->deserializeTimestamp( $this->get( 'skipped' ) ) );
		return $skippedManuscriptPrompt;
	}

	/**
	 * @param User $user
	 * @return User|null
	 */
	public function visitUser(
		User $user
	): ?User {
		if ( $this->array === null ) {
			return null;
		}
		$user->setIdentity( $this->deserializeUuid( $this->array[ 'identity' ] ) );
		$user->setMediaWikiUser( $this->get( 'mediaWikiUser' ) );
		$user->setYearBorn( $this->get( 'yearBorn' ) );
		return $user;
	}

	/**
	 * @param UserDialect $userDialect
	 * @return UserDialect|null
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	): ?UserDialect {
		if ( $this->array === null ) {
			return null;
		}
		$userDialect->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$userDialect->setUser( $this->deserializeUuid( $this->get( 'user' ) ) );
		$userDialect->setLanguage( $this->deserializeUuid( $this->get( 'language' ) ) );
		$userDialect->setSpokenProficiencyLevel( $this->get( 'spokenProficiencyLevel' ) );
		$userDialect->setLocation( $this->deserializeJsonObject( $this->get( 'location' ) ) );
		return $userDialect;
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return UserLanguageProficiencyLevel|null
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	): ?UserLanguageProficiencyLevel {
		if ( $this->array === null ) {
			return null;
		}
		$languageProficiencyLevel->setIdentity( $this->deserializeUuid( $this->get( 'identity' ) ) );
		$languageProficiencyLevel->setUser( $this->deserializeUuid( $this->get( 'user' ) ) );
		$languageProficiencyLevel->setLanguage( $this->deserializeUuid( $this->get( 'language' ) ) );
		$languageProficiencyLevel->setProficiencyLevel( $this->get( 'proficiencyLevel' ) );
		return $languageProficiencyLevel;
	}

	/**
	 * @param string|null $value
	 * @return string|null
	 */
	private function deserializeUuid(
		?string $value
	): ?string {
		if ( $value === null ) {
			return null;
		}
		return Uuid::asBytes( $value );
	}

	/**
	 * @param mixed|null $value
	 * @return MWTimestamp|null
	 * @throws TimestampException
	 */
	private function deserializeTimestamp(
		$value
	): ?MWTimestamp {
		if ( $value === null ) {
			return null;
		}
		$timestamp = new MWTimestamp();
		$timestamp->setTimestamp( $value );
		return $timestamp;
	}

	/**
	 * @param string|null $value
	 * @return string|null
	 * @throws MWException
	 */
	private function deserializeJsonObject(
		$value
	): ?string {
		if ( $value === null ) {
			return null;
		}
		$json = FormatJson::encode( $value );
		if ( $json === false ) {
			throw new MWException( 'Failed to encode to JSON.' );
		}
		return $json;
	}

}
