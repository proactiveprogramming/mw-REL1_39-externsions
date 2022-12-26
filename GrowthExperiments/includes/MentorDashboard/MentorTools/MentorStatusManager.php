<?php

namespace GrowthExperiments\MentorDashboard\MentorTools;

use DBAccessObjectUtils;
use IDBAccessObject;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserIdentityLookup;
use MediaWiki\User\UserOptionsManager;
use StatusValue;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Timestamp\ConvertibleTimestamp;

class MentorStatusManager implements IDBAccessObject {

	/** @var string Mentor status */
	public const STATUS_ACTIVE = 'active';
	/** @var string Mentor status */
	public const STATUS_AWAY = 'away';

	/** @var string[] List of MentorStatusManager::STATUS_* constants */
	public const STATUSES = [
		self::STATUS_ACTIVE,
		self::STATUS_AWAY
	];

	/** @var string */
	public const AWAY_BECAUSE_TIMESTAMP = 'timestamp';
	/** @var string */
	public const AWAY_BECAUSE_BLOCK = 'block';
	/** @var string */
	public const AWAY_BECAUSE_LOCK = 'lock';

	/** @var string Preference key to store mentor's away timestamp */
	public const MENTOR_AWAY_TIMESTAMP_PREF = 'growthexperiments-mentor-away-timestamp';

	/** @var int Number of seconds in a day */
	private const SECONDS_DAY = 86400;

	/** @var UserOptionsManager */
	private $userOptionsManager;

	/** @var UserIdentityLookup */
	private $userIdentityLookup;

	/** @var UserFactory */
	private $userFactory;

	/** @var IDatabase */
	private $dbr;

	/** @var IDatabase */
	private $dbw;

	/**
	 * @param UserOptionsManager $userOptionsManager
	 * @param UserIdentityLookup $userIdentityLookup
	 * @param UserFactory $userFactory
	 * @param IDatabase $dbr
	 * @param IDatabase $dbw
	 */
	public function __construct(
		UserOptionsManager $userOptionsManager,
		UserIdentityLookup $userIdentityLookup,
		UserFactory $userFactory,
		IDatabase $dbr,
		IDatabase $dbw
	) {
		$this->userOptionsManager = $userOptionsManager;
		$this->userIdentityLookup = $userIdentityLookup;
		$this->userFactory = $userFactory;
		$this->dbr = $dbr;
		$this->dbw = $dbw;
	}

	/**
	 * Can user change their status?
	 *
	 * @param UserIdentity $mentor
	 * @param int $flags bitfield consisting of MentorStatusManager::READ_* constants
	 * @return StatusValue
	 */
	public function canChangeStatus( UserIdentity $mentor, int $flags = 0 ): StatusValue {
		$awayReason = $this->getAwayReason( $mentor, $flags );
		switch ( $awayReason ) {
			case self::AWAY_BECAUSE_BLOCK:
				return StatusValue::newFatal(
					'growthexperiments-mentor-dashboard-mentor-tools-mentor-status-error-cannot-be-changed-block'
				);
			case self::AWAY_BECAUSE_LOCK:
				return StatusValue::newFatal(
					'growthexperiments-mentor-dashboard-mentor-tools-mentor-status-error-cannot-be-changed-lock',
					$mentor->getName()
				);
			default:
				return StatusValue::newGood();
		}
	}

	/**
	 * @param UserIdentity $mentor
	 * @param int $flags bitfield ocnsisting of MentorStatusManager::READ_* constants
	 * @return string|null Away reason or null if mentor is not away
	 */
	public function getAwayReason( UserIdentity $mentor, int $flags = 0 ): ?string {
		// NOTE: (b)lock checking must be first. This is to make canChangeStatus() work for mentors
		// who are blocked _and_ (manually) away.
		$block = $this->userFactory->newFromUserIdentity( $mentor )
			->getBlock( $flags );
		if ( $block !== null && $block->isSitewide() ) {
			return self::AWAY_BECAUSE_BLOCK;
		}

		if ( $this->userFactory->newFromUserIdentity( $mentor )->isLocked() ) {
			return self::AWAY_BECAUSE_LOCK;
		}

		if ( $this->getMentorBackTimestamp( $mentor, $flags ) !== null ) {
			return self::AWAY_BECAUSE_TIMESTAMP;
		}

		// user is not away
		return null;
	}

	/**
	 * Get mentor's current status
	 *
	 * @param UserIdentity $mentor
	 * @param int $flags bitfield; consists of MentorStatusManager::READ_* constants
	 * @return string one of MentorStatusManager::STATUS_* constants
	 */
	public function getMentorStatus( UserIdentity $mentor, int $flags = 0 ): string {
		return $this->getAwayReason( $mentor, $flags ) === null
			? self::STATUS_ACTIVE
			: self::STATUS_AWAY;
	}

	/**
	 * @param UserIdentity $mentor
	 * @param int $flags bitfield; consists of MentorStatusManager::READ_* constants
	 * @return string|null Null if expiry is not set (mentor's current status does not expire)
	 */
	public function getMentorBackTimestamp( UserIdentity $mentor, int $flags = 0 ): ?string {
		return $this->parseBackTimestamp( $this->userOptionsManager->getOption(
			$mentor,
			self::MENTOR_AWAY_TIMESTAMP_PREF,
			null,
			false,
			$flags
		) );
	}

	/**
	 * @param string|null $rawTs
	 * @return string|null
	 */
	private function parseBackTimestamp( ?string $rawTs ): ?string {
		if (
			$rawTs === null ||
			(int)ConvertibleTimestamp::convert( TS_UNIX, $rawTs ) < (int)wfTimestamp( TS_UNIX )
		) {
			return null;
		}

		return $rawTs;
	}

	/**
	 * Get mentors marked as away
	 *
	 * @param int $flags bitfield; consists of MentorStatusManager::READ_* constants
	 * @return UserIdentity[]
	 */
	public function getAwayMentors( int $flags = 0 ): array {
		list( $index, $options ) = DBAccessObjectUtils::getDBOptions( $flags );
		$db = ( $index === DB_PRIMARY ) ? $this->dbw : $this->dbr;

		// This should be okay, as up_property is an index, and we won't
		// get a lot of rows to process.
		$awayMentorIds = $db->newSelectQueryBuilder()
			->select( 'up_user' )
			->from( 'user_properties' )
			->where( [
				'up_property' => self::MENTOR_AWAY_TIMESTAMP_PREF,
				'up_value IS NOT NULL',
				'up_value > ' . $db->addQuotes(
					$db->timestamp()
				)
			] )
			->options( $options )
			->caller( __METHOD__ )
			->fetchFieldValues();

		if ( $awayMentorIds === [] ) {
			return [];
		}

		return iterator_to_array(
			$this->userIdentityLookup
				->newSelectQueryBuilder()
				->whereUserIds( $awayMentorIds )
				->fetchUserIdentities()
		);
	}

	/**
	 * Mark a mentor as away
	 *
	 * @param UserIdentity $mentor
	 * @param int $backInDays Length of mentor's wiki-vacation in days
	 * @return StatusValue
	 */
	public function markMentorAsAway( UserIdentity $mentor, int $backInDays ): StatusValue {
		return $this->markMentorAsAwayTimestamp(
			$mentor,
			ConvertibleTimestamp::convert(
				TS_MW,
				(int)wfTimestamp( TS_UNIX ) + self::SECONDS_DAY * $backInDays
			)
		);
	}

	/**
	 * Mark a mentor as away
	 *
	 * @param UserIdentity $mentor
	 * @param string $timestamp When will the mentor be back?
	 * @return StatusValue
	 */
	public function markMentorAsAwayTimestamp(
		UserIdentity $mentor,
		string $timestamp
	): StatusValue {
		$canChangeStatus = $this->canChangeStatus( $mentor );
		if ( !$canChangeStatus->isOK() ) {
			return $canChangeStatus;
		}

		$this->userOptionsManager->setOption(
			$mentor,
			self::MENTOR_AWAY_TIMESTAMP_PREF,
			ConvertibleTimestamp::convert(
				TS_MW,
				$timestamp
			)
		);
		$this->userOptionsManager->saveOptions( $mentor );
		return StatusValue::newGood();
	}

	/**
	 * Mark a mentor as active
	 *
	 * @param UserIdentity $mentor
	 * @return StatusValue
	 */
	public function markMentorAsActive( UserIdentity $mentor ): StatusValue {
		$canChangeStatus = $this->canChangeStatus( $mentor );
		if ( !$canChangeStatus->isOK() ) {
			return $canChangeStatus;
		}

		$this->userOptionsManager->setOption(
			$mentor,
			self::MENTOR_AWAY_TIMESTAMP_PREF,
			null
		);
		$this->userOptionsManager->saveOptions( $mentor );
		return StatusValue::newGood();
	}
}