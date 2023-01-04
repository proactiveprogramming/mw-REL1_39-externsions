<?php
/**
 * replace supplement User Group with supplementcomment User Group for update from 0.2.x
 *
 */

use MediaWiki\MediaWikiServices;

require_once( __DIR__ . '/../../../maintenance/Maintenance.php' );

class FixUserGroupSupplement extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addOption( 'log', 'Logging the change(s) of user groups of user(s).' );
		$this->addDescription( 'replace supplement User Group with supplementcomment ' .
			'User Group for update from 0.2.x' );
		$this->requireExtension( 'RevisionCommentSupplement' );
	}

	public function execute() {
		$this->output( "\nThis will replace 10 seconds after.\n" );
		$this->countDown( 10 );
		$systemUser = User::newSystemUser( 'Maintenance script', array( 'steal' => true ) );
		$i = 0;
		$dbr = wfGetDB( DB_REPLICA );
		$rows = $dbr->select(
			'user_groups',
			'ug_user',
			array( 'ug_group' => 'supplement' ),
			__METHOD__
		);
		$userFactory = MediaWikiServices::getInstance()->getUserFactory();
		$userGroupManager = MediaWikiServices::getInstance()->getUserGroupManager();
		foreach ( $rows as $row ) {
			# from SpecialUserrights.php
			$user = $userFactory->newFromId( (int)$row->ug_user );
			$oldGroups = $userGroupManager->getUserGroups( $user );
			$userGroupManager->removeUserFromGroup( $user, 'supplement' );
			$userGroupManager->addUserToGroup( $user, 'supplementcomment' );
			$newGroups = $userGroupManager->getUserGroups( $user );
			$i++;
			if ( $this->hasOption( 'log' ) ) {
				$log = new LogPage( 'rights' );

				$log->addEntry(
					'rights',
					$user->getUserPage(),
					'RevisionCommentSupplement update from 0.2.x',
					array( implode( ', ', $oldGroups ), implode( ', ', $newGroups ) ),
					$systemUser
				);
			}
		}
		$this->output( "changed user groups of $i users.\n" );
		return true;
	}

}

$maintClass = FixUserGroupSupplement::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
