<?php
declare( strict_types=1 );

namespace MediaWiki\Extension\WikibaseStatementUpdater\Batch;

use DateTime;
use MediaWiki\User\UserIdentity;
use User;
use Wikimedia\Rdbms\IDatabase;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class BatchListStore {
	private const TABLE = 'wsu_batchlist';
	/** @var IDatabase */
	private $db;

	public function __construct( IDatabase $db ) {
		$this->db = $db;
	}

	public function add( BatchList $list ): BatchListRecord {
		$ts = ( new DateTime() )->getTimestamp();

		$this->db->insert(
			self::TABLE,
			[
				'wsubl_name' => $list->getName(),
				'wsubl_actor' => $list->getOwner()->getActorId(),
				'wsubl_createdat' => $ts,
			],
			__METHOD__
		);

		return new BatchListRecord(
			$list->getName(), $list->getOwner(), $this->db->insertId(), $ts
		);
	}

	/** @return BatchListRecord[] */
	public function getForUser( UserIdentity $user ): array {
		$res = $this->db->select(
			self::TABLE,
			[ 'wsubl_id', 'wsubl_name', 'wsubl_createdat', 'wsubl_status' ],
			[ 'wsubl_actor' => $user->getActorId() ],
			__METHOD__
		);

		$lists = [];
		foreach ( $res as $row ) {
			$lists[] = new BatchListRecord(
				$row->wsubl_name,
				$user,
				(int)$row->wsubl_id,
				(int)$row->wsubl_createdat,
				$row->wsubl_status
			);
		}
		return $lists;
	}

	public function get( int $id ): ?BatchListRecord {
		$row = $this->db->selectRow(
			self::TABLE,
			[ 'wsubl_id', 'wsubl_name', 'wsubl_actor', 'wsubl_createdat', 'wsubl_status' ],
			[ 'wsubl_id' => $id ],
			__METHOD__
		);

		if ( !$row ) {
			return null;
		}

		$user = User::newFromActorId( $row->wsubl_actor );

		return new BatchListRecord(
			$row->wsubl_name,
			$user,
			(int)$row->wsubl_id,
			(int)$row->wsubl_createdat,
			$row->wsubl_status
		);
	}

	public function updateStatus( BatchListRecord $record, string $status = null ) {
		$this->db->update(
			self::TABLE,
			[ 'wsubl_status' => $status ],
			[ 'wsubl_id' => $record->getId() ]
		);
	}
}
