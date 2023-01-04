<?php

declare( strict_types = 1 );

namespace ContentTranslation\Store;

use ContentTranslation\DTO\SectionTranslationDTO;
use ContentTranslation\Entity\SectionTranslation;
use ContentTranslation\LoadBalancer;
use Wikimedia\Rdbms\Platform\ISQLPlatform;
use Wikimedia\Rdbms\SelectQueryBuilder;

class SectionTranslationStore {
	public const TABLE_NAME = 'cx_section_translations';

	/** @var LoadBalancer */
	private $lb;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->lb = $loadBalancer;
	}

	public function insertTranslation( SectionTranslation $translation ) {
		$dbw = $this->lb->getConnection( DB_PRIMARY );
		$values = $this->translationToDBRow( $translation );
		$dbw->insert( self::TABLE_NAME, $values, __METHOD__ );
		$translation->setId( $dbw->insertId() );
	}

	public function updateTranslation( SectionTranslation $translation ) {
		$dbw = $this->lb->getConnection( DB_PRIMARY );
		$values = $this->translationToDBRow( $translation );
		$dbw->update(
			self::TABLE_NAME,
			$values,
			[ 'cxsx_id' => $translation->getId() ],
			__METHOD__
		);
	}

	public function findTranslation( int $translationId, string $sectionId ): ?SectionTranslation {
		$dbr = $this->lb->getConnection( DB_REPLICA );

		$values = [ 'cxsx_translation_id' => $translationId, 'cxsx_section_id' => $sectionId ];

		$row = $dbr->selectRow( self::TABLE_NAME, \IDatabase::ALL_ROWS, $values, __METHOD__ );
		return $row ? $this->createTranslationFromRow( $row ) : null;
	}

	public function createTranslationFromRow( \stdClass $row ): SectionTranslation {
		return new SectionTranslation(
			(int)$row->cxsx_id,
			(int)$row->cxsx_translation_id,
			$row->cxsx_section_id,
			$row->cxsx_source_section_title,
			$row->cxsx_target_section_title
		);
	}

	/**
	 * @param int $userId User ID
	 * @param string|null $from
	 * @param string|null $to
	 * @param string|null $status The status of the translation. Either "draft" or "published"
	 * @param int $limit How many results to return. Defaults to 100, same as for the "contenttranslation" list API
	 * @param string|null $offset Offset condition (timestamp)
	 * @return SectionTranslationDTO[]
	 */
	public function findSectionTranslationsByUser(
		int $userId,
		string $from = null,
		string $to = null,
		string $status = null,
		int $limit = 100,
		string $offset = null
	): array {
		// Note: there is no index on translation_last_updated_timestamp
		$dbr = $this->lb->getConnection( DB_REPLICA );

		$onClauseConditions = [
			'translator_translation_id = translation_id',
			'translator_user_id' => $userId
		];

		$whereConditions = [];
		if ( $status !== null ) {
			$whereConditions['translation_status'] = $status;
		}
		if ( $from !== null ) {
			$whereConditions['translation_source_language'] = $from;
		}
		if ( $to !== null ) {
			$whereConditions['translation_target_language'] = $to;
		}
		if ( $offset !== null ) {
			$ts = $dbr->addQuotes( $dbr->timestamp( $offset ) );
			$whereConditions[] = "translation_last_updated_timestamp < $ts";
		}

		$resultSet = $dbr->newSelectQueryBuilder()
			->select( ISQLPlatform::ALL_ROWS )
			->from( self::TABLE_NAME )
			->join( 'cx_translations', null, 'translation_id = cxsx_translation_id' )
			->join( 'cx_translators', null, $onClauseConditions )
			->where( $whereConditions )
			->orderBy( 'translation_last_updated_timestamp', SelectQueryBuilder::SORT_DESC )
			->limit( $limit )
			->caller( __METHOD__ )
			->fetchResultSet();

		$result = [];
		foreach ( $resultSet as $row ) {
			$result[] = new SectionTranslationDTO(
				$row->cxsx_translation_id,
				$row->translation_source_title,
				$row->translation_source_language,
				$row->translation_target_language,
				$row->translation_start_timestamp,
				$row->translation_last_updated_timestamp,
				$row->translation_status,
				$row->translation_source_revision_id,
				$row->translation_target_title,
				$row->cxsx_source_section_title,
				$row->cxsx_target_section_title,
			);
		}

		return $result;
	}

	private function translationToDBRow( SectionTranslation $translation ): array {
		return [
			'cxsx_translation_id' => $translation->getTranslationId(),
			'cxsx_section_id' => $translation->getSectionId(),
			'cxsx_source_section_title' => $translation->getSourceSectionTitle(),
			'cxsx_target_section_title' => $translation->getTargetSectionTitle(),
		];
	}
}
