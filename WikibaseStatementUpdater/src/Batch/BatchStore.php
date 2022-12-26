<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Batch;

use stdClass;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\Rdbms\IDatabase;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class BatchStore {
	private const TABLE = 'wsu_batch';
	/** @var IDatabase */
	private $db;

	public function __construct( IDatabase $db ) {
		$this->db = $db;
	}

	/**
	 * @param BatchListRecord $list
	 * @param BatchItem[] $items
	 */
	public function addItems( BatchListRecord $list, array $items ) {
		$rows = [];
		foreach ( $items as $item ) {
			$rows[] = [
				'wsub_batch' => $list->getId(),
				'wsub_input' => $this->serialise( $item->getSpec() ),
			];
		}

		$this->db->insert( self::TABLE, $rows, __METHOD__ );
	}

	/** @return BatchItemRecord[] */
	public function getRecords( BatchListRecord $list ): array {
		$res = $this->db->select(
			self::TABLE,
			[ 'wsub_id', 'wsub_input', 'wsub_output' ],
			[ 'wsub_batch' => $list->getId() ],
			__METHOD__
		);

		$records = [];
		foreach ( $res as $row ) {
			$records[] = $this->makeRecord( $row );
		}
		return $records;
	}

	private function makeRecord( stdClass $row ): BatchItemRecord {
		/** @var BatchItem $item */
		$item = ObjectFactory::getObjectFromSpec( $this->unserialize( $row->wsub_input ) );

		return new BatchItemRecord(
			(int)$row->wsub_id, $item, $this->unserialize( $row->wsub_output ?? '[]' ),
		);
	}

	public function getRecord( int $id ): ?BatchItemRecord {
		$row = $this->db->selectRow(
			self::TABLE,
			[ 'wsub_id', 'wsub_input', 'wsub_output' ],
			[ 'wsub_id' => $id ],
			__METHOD__
		);

		return $row ? $this->makeRecord( $row ) : null;
	}

	public function updateOutput( BatchItemRecord $record ): void {
		$this->db->update(
			self::TABLE,
			[ 'wsub_output' => $this->serialise( $record->getOutput() ) ],
			[ 'wsub_id' => $record->getId() ],
			__METHOD__
		);
	}

	private function serialise( array $x ): string {
		return json_encode( $x, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	private function unserialize( string $x ): array {
		return json_decode( $x, true );
	}
}
