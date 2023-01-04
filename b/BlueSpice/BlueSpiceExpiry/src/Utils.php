<?php

namespace BlueSpice\Expiry;

use MediaWiki\MediaWikiServices;
use Title;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\ILoadBalancer;

class Utils {

	/**
	 *
	 * @var ILoadBalancer
	 */
	protected $loadBalancer = null;

	/**
	 *
	 * @param ILoadBalancer $loadBalancer
	 */
	public function __construct( $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	/**
	 *
	 * @param int $type
	 * @return IDatabase
	 */
	protected function getDB( $type = DB_REPLICA ) {
		return $this->loadBalancer->getConnection( $type );
	}

	/**
	 * TODO: Replace with registry!
	 * @param Title $title
	 * @return \User[]
	 */
	public function getPageModerators( $title ) {
		$moderators = [];

		// This is not nice. A hook should be added and 'PageAssignments' (or
		// some other provider) should then fill the list
		$services = MediaWikiServices::getInstance();
		if ( !$services->hasService( 'BSPageAssignmentsAssignmentFactory' ) ) {
			return $moderators;
		}
		$factory = $services->getService( 'BSPageAssignmentsAssignmentFactory' );
		$target = $factory->newFromTargetTitle( $title );
		if ( !$target ) {
			return $moderators;
		}
		$userFactory = $services()->getUserFactory();
		foreach ( $target->getAssignedUserIDs() as $userID ) {
			$assigneduser = $userFactory->newFromId( $userID );
			if ( !$assigneduser ) {
				continue;
			}
			$assigneduser->load();
			if ( $assigneduser->isAnon() ) {
				// Clear out fragments to prevent `Echo` from breaking
				continue;
			}
			$moderators[] = $assigneduser;
		}

		return $moderators;
	}

	/**
	 * TODO: Replace with store soon!
	 * @return \User[]
	 */
	public function getSysops() {
		$res = $this->getDB()->select(
			'user_groups',
			'ug_user',
			[ 'ug_group' => 'sysop' ]
		);

		$sysops = [];

		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		foreach ( $res as $row ) {
			$sysop = $userFactory->newFromId( $row->ug_user );
			if ( $sysop instanceof \User ) {
				$sysop->load();
				if ( $sysop->isAnon() ) {
					// Clear out fragments to prevent `Echo` from breaking
					continue;
				}
				$sysops[] = $sysop;
			}
		}

		return $sysops;
	}
}
