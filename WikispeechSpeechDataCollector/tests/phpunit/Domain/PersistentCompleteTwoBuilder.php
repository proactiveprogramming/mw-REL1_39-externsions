<?php

namespace MediaWiki\WikispeechSpeechDataCollector\Tests\Domain;

/**
 * @file
 * @ingroup Extensions
 * @license GPL-2.0-or-later
 */

use MediaWiki\WikispeechSpeechDataCollector\Domain\Language;
use MediaWiki\WikispeechSpeechDataCollector\Domain\LanguageProficiencyLevel;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Manuscript;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptDomain;
use MediaWiki\WikispeechSpeechDataCollector\Domain\ManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\PersistentVisitor;
use MediaWiki\WikispeechSpeechDataCollector\Domain\Recording;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotation;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingAnnotations;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReview;
use MediaWiki\WikispeechSpeechDataCollector\Domain\RecordingReviewValue;
use MediaWiki\WikispeechSpeechDataCollector\Domain\SkippedManuscriptPrompt;
use MediaWiki\WikispeechSpeechDataCollector\Domain\User;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserDialect;
use MediaWiki\WikispeechSpeechDataCollector\Domain\UserLanguageProficiencyLevel;
use MediaWiki\WikispeechSpeechDataCollector\Uuid;
use MWTimestamp;

/**
 * Sets at least all required values to non-null values in the visited instance,
 * except for the identity.
 * The combination of values set by this visitor
 * and {@link PersistentCompleteOneBuilder}
 * must set all required and non required values,
 * and the non-null values the two visitors set must differ from each other.
 *
 * @since 0.1.0
 */
class PersistentCompleteTwoBuilder implements PersistentVisitor {

	/**
	 * @param Language $language
	 * @return null
	 */
	public function visitLanguage(
		Language $language
	) {
		$language->setNativeName( 'English' );
		$language->setIso639a1( 'en' );
		$language->setIso639a2b( 'e2b' );
		$language->setIso639a2t( 'e2t' );
		$language->setIso639a3( 'e3' );
		return null;
	}

	/**
	 * @param Manuscript $manuscript
	 * @return null
	 */
	public function visitManuscript(
		Manuscript $manuscript
	) {
		$manuscript->setLanguage( Uuid::v4BytesFactory() );
		$manuscript->setDomain( Uuid::v4BytesFactory() );
		$manuscript->setDisabled( MWTimestamp::getInstance( 20200713145000 ) );
		$manuscript->setName( 'Updated manuscript name' );
		$manuscript->setCreated( MWTimestamp::getInstance( 20200712145000 ) );
		return null;
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return null
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	) {
		$manuscriptDomain->setName( 'Updated name' );
		$manuscriptDomain->setParent( Uuid::v4BytesFactory() );
		return null;
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return null
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	) {
		$manuscriptPrompt->setManuscript( Uuid::v4BytesFactory() );
		$manuscriptPrompt->setIndex( 2 );
		$manuscriptPrompt->setContent( 'Updated content' );
		return null;
	}

	/**
	 * @param Recording $recording
	 * @return null
	 */
	public function visitRecording(
		Recording $recording
	) {
		$recording->setManuscriptPrompt( Uuid::v4BytesFactory() );
		$recording->setSpokenDialect( Uuid::v4BytesFactory() );
		$recording->setVoiceOf( Uuid::v4BytesFactory() );
		$recording->setRecorded( MWTimestamp::getInstance( 20200714145000 ) );
		$recording->setAudioFileWikiPageIdentity( 2 );
		return null;
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return null
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	) {
		$recordingAnnotation = new RecordingAnnotation();
		$recordingAnnotation->setStart( 3 );
		$recordingAnnotation->setEnd( 4 );
		$recordingAnnotation->setStereotype( 'Stereotype B' );
		$recordingAnnotation->setValue( 'Updated value' );
		$recordingAnnotations->setItems( [ $recordingAnnotation ] );
		return null;
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return null
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	) {
		$recordingReview->setCreated( MWTimestamp::getInstance( 20200714145000 ) );
		$recordingReview->setReviewer( Uuid::v4BytesFactory() );
		$recordingReview->setRecording( Uuid::v4BytesFactory() );
		$recordingReview->setValue( RecordingReviewValue::THUMB_DOWN );
		return null;
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return null
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	) {
		$skippedManuscriptPrompt->setSkipped( MWTimestamp::getInstance( 20200714145000 ) );
		$skippedManuscriptPrompt->setUser( Uuid::v4BytesFactory() );
		$skippedManuscriptPrompt->setManuscriptPrompt( Uuid::v4BytesFactory() );
		return null;
	}

	/**
	 * @param User $user
	 * @return null
	 */
	public function visitUser(
		User $user
	) {
		$user->setYearBorn( 1914 );
		$user->setMediaWikiUser( 234 );
		return null;
	}

	/**
	 * @param UserDialect $userDialect
	 * @return null
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	) {
		$userDialect->setUser( Uuid::v4BytesFactory() );
		$userDialect->setLanguage( Uuid::v4BytesFactory() );
		$userDialect->setSpokenProficiencyLevel( LanguageProficiencyLevel::BASIC );
		$userDialect->setLocation(
			'{ "type": "Feature", "geometry": { "type": "Point", "coordinates": [59.9, 10.7] } }'
		);
		return null;
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return null
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	) {
		$languageProficiencyLevel->setUser( Uuid::v4BytesFactory() );
		$languageProficiencyLevel->setLanguage( Uuid::v4BytesFactory() );
		$languageProficiencyLevel->setProficiencyLevel( LanguageProficiencyLevel::NEAR_NATIVE );
		return null;
	}

}
