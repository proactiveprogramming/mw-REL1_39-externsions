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

/**
 * A {@link PersistentVisitor} that populates an associative array
 * with data set in the persistent instance, to be used as an MW API response.
 *
 * $array = $persistent->accept( new PersistentMWAssociateArraySerializer() );
 *
 * @since 0.1.0
 */
class PersistentMWAssociativeArraySerializer implements PersistentVisitor {

	/**
	 * @param Manuscript $manuscript
	 * @return array
	 */
	public function visitManuscript(
		Manuscript $manuscript
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $manuscript->getIdentity() );
		$array['name'] = $manuscript->getName();
		$array['created'] = $this->serializeTimestamp( $manuscript->getCreated() );
		$array['disabled'] = $this->serializeTimestamp( $manuscript->getDisabled() );
		$array['language'] = $this->serializeUuid( $manuscript->getLanguage() );
		$array['domain'] = $this->serializeUuid( $manuscript->getDomain() );
		return $array;
	}

	/**
	 * @param ManuscriptPrompt $manuscriptPrompt
	 * @return array
	 */
	public function visitManuscriptPrompt(
		ManuscriptPrompt $manuscriptPrompt
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $manuscriptPrompt->getIdentity() );
		$array['manuscript'] = $this->serializeUuid( $manuscriptPrompt->getManuscript() );
		$array['index'] = $manuscriptPrompt->getIndex();
		$array['content'] = $manuscriptPrompt->getContent();
		return $array;
	}

	/**
	 * @param Recording $recording
	 * @return array
	 */
	public function visitRecording(
		Recording $recording
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $recording->getIdentity() );
		$array['recorded'] = $this->serializeTimestamp( $recording->getRecorded() );
		$array['voiceOf'] = $this->serializeUuid( $recording->getVoiceOf() );
		$array['spokenDialect'] = $this->serializeUuid( $recording->getSpokenDialect() );
		$array['manuscriptPrompt'] = $this->serializeUuid( $recording->getManuscriptPrompt() );
		$array['audioFileWikiPageIdentity'] = $recording->getAudioFileWikiPageIdentity();
		return $array;
	}

	/**
	 * @param Language $language
	 * @return array
	 */
	public function visitLanguage(
		Language $language
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $language->getIdentity() );
		$array['nativeName'] = $language->getNativeName();
		$array['iso639a1'] = $language->getIso639a1();
		$array['iso639a2b'] = $language->getIso639a2b();
		$array['iso639a2t'] = $language->getIso639a2t();
		$array['iso639a3'] = $language->getIso639a3();
		return $array;
	}

	/**
	 * @param ManuscriptDomain $manuscriptDomain
	 * @return array
	 */
	public function visitManuscriptDomain(
		ManuscriptDomain $manuscriptDomain
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $manuscriptDomain->getIdentity() );
		$array['name'] = $manuscriptDomain->getName();
		$array['parent'] = $this->serializeUuid( $manuscriptDomain->getParent() );
		return $array;
	}

	/**
	 * @param RecordingAnnotation $recordingAnnotation
	 * @return array
	 */
	public function visitRecordingAnnotation(
		RecordingAnnotation $recordingAnnotation
	): array {
		$array = [];
		$array['start'] = $recordingAnnotation->getStart();
		$array['end'] = $recordingAnnotation->getEnd();
		$array['stereotype'] = $recordingAnnotation->getStereotype();
		$array['value'] = $recordingAnnotation->getValue();
		return $array;
	}

	/**
	 * @param RecordingAnnotations $recordingAnnotations
	 * @return array
	 */
	public function visitRecordingAnnotations(
		RecordingAnnotations $recordingAnnotations
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $recordingAnnotations->getIdentity() );
		$array['items'] = [];
		if ( $recordingAnnotations->getItems() !== null &&
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullableInternal
			count( $recordingAnnotations->getItems() ) > 0
		) {
			foreach ( $recordingAnnotations->getItems() as $recordingAnnotation ) {
				$array['items'][] = $this->visitRecordingAnnotation( $recordingAnnotation );
			}
		}

		return $array;
	}

	/**
	 * @param RecordingReview $recordingReview
	 * @return array
	 */
	public function visitRecordingReview(
		RecordingReview $recordingReview
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $recordingReview->getIdentity() );
		$array['created'] = $this->serializeTimestamp( $recordingReview->getCreated() );
		$array['value'] = $recordingReview->getValue();
		$array['reviewer'] = $this->serializeUuid( $recordingReview->getReviewer() );
		$array['recording'] = $this->serializeUuid( $recordingReview->getRecording() );
		return $array;
	}

	/**
	 * @param SkippedManuscriptPrompt $skippedManuscriptPrompt
	 * @return array
	 */
	public function visitSkippedManuscriptPrompt(
		SkippedManuscriptPrompt $skippedManuscriptPrompt
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $skippedManuscriptPrompt->getIdentity() );
		$array['manuscriptPrompt'] =
			$this->serializeUuid( $skippedManuscriptPrompt->getManuscriptPrompt() );
		$array['user'] = $this->serializeUuid( $skippedManuscriptPrompt->getUser() );
		$array['skipped'] =	$this->serializeTimestamp( $skippedManuscriptPrompt->getSkipped() );
		return $array;
	}

	/**
	 * @param User $user
	 * @return array
	 */
	public function visitUser(
		User $user
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $user->getIdentity() );
		$array['mediaWikiUser'] = $user->getMediaWikiUser();
		$array['yearBorn'] = $user->getYearBorn();
		return $array;
	}

	/**
	 * @param UserDialect $userDialect
	 * @return array
	 */
	public function visitUserDialect(
		UserDialect $userDialect
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $userDialect->getIdentity() );
		$array['user'] = $this->serializeUuid( $userDialect->getUser() );
		$array['language'] = $this->serializeUuid( $userDialect->getLanguage() );
		$array['spokenProficiencyLevel'] = $userDialect->getSpokenProficiencyLevel();
		$array['location'] = $this->serializeJson( $userDialect->getLocation() );
		return $array;
	}

	/**
	 * @param UserLanguageProficiencyLevel $languageProficiencyLevel
	 * @return array
	 */
	public function visitUserLanguageProficiencyLevel(
		UserLanguageProficiencyLevel $languageProficiencyLevel
	): array {
		$array = [];
		$array['identity'] = $this->serializeUuid( $languageProficiencyLevel->getIdentity() );
		$array['user'] = $this->serializeUuid( $languageProficiencyLevel->getUser() );
		$array['language'] = $this->serializeUuid( $languageProficiencyLevel->getLanguage() );
		$array['proficiencyLevel'] = $languageProficiencyLevel->getProficiencyLevel();
		return $array;
	}

	/**
	 * @param MWTimestamp|null $value
	 * @return string|null ISO 8601
	 */
	private function serializeTimestamp(
		?MWTimestamp $value
	): ?string {
		return $value === null ? null : $value->getTimestamp( TS_ISO_8601 );
	}

	/**
	 * @param string|null $value
	 * @return string|null Hex encoded UUID with dash separation.
	 */
	private function serializeUuid(
		?string $value
	): ?string {
		return Uuid::asHex( $value, true );
	}

	/**
	 * @param string|null $value
	 * @return mixed
	 * @throws MWException
	 */
	private function serializeJson(
		?string $value
	) {
		if ( $value === null ) {
			return null;
		}
		$parsed = FormatJson::parse( $value, FormatJson::FORCE_ASSOC );
		if ( !$parsed->isOK() ) {
			throw new MWException( "Unable to parse JSON.\n" . $parsed->__toString() );
		}
		return $parsed->getValue();
	}
}
