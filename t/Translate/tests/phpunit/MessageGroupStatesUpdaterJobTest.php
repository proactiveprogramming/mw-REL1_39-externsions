<?php

use MediaWiki\Extension\Translate\MessageGroupProcessing\GroupReviewActionApi;
use MediaWiki\Extension\Translate\MessageGroupProcessing\MessageGroups;
use MediaWiki\MediaWikiServices;

/**
 * @group Database
 * @group medium
 * @covers MessageGroupStatesUpdaterJob
 */
class MessageGroupStatesUpdaterJobTest extends ApiTestCase {
	protected function setUp(): void {
		parent::setUp();
		$this->setMwGlobals( [
			'wgTranslateTranslationServices' => [],
			'wgTranslateMessageNamespaces' => [ NS_MEDIAWIKI ],
		] );
		$this->setTemporaryHook( 'TranslatePostInitGroups', [ $this, 'getTestGroups' ] );

		$mg = MessageGroups::singleton();
		$mg->setCache( new WANObjectCache( [ 'cache' => new HashBagOStuff() ] ) );
		$mg->recache();
	}

	public function getTestGroups( &$list ) {
		$messages = [ 'key1' => 'msg1', 'key2' => 'msg2' ];
		$list['group-trans'] = new MessageGroupWithTransitions( 'group-trans', $messages );
		$list['group-notrans'] = new MessageGroupWithoutTransitions( 'group-notrans', [] );

		return false;
	}

	public function testGetGroupsWithTransitions() {
		$handle = new MockMessageHandle();
		$groups = MessageGroupStatesUpdaterJob::getGroupsWithTransitions( $handle );
		foreach ( $groups as $id => $transitions ) {
			$this->assertEquals( 'group-trans', $id );
		}
	}

	/** @dataProvider provideStatValues */
	public function testGetStatValue( $type, $expected ) {
		$stats = [
			MessageGroupStats::TOTAL => 666,
			MessageGroupStats::FUZZY => 111,
			MessageGroupStats::TRANSLATED => 222,
			MessageGroupStats::PROOFREAD => 111,
		];
		$actual = MessageGroupStatesUpdaterJob::getStatValue( $stats, $type );
		$this->assertEquals( $expected, $actual );
	}

	public static function provideStatValues() {
		return [
			[ 'UNTRANSLATED', 333 ],
			[ 'OUTDATED', 111 ],
			[ 'TRANSLATED', 222 ],
			[ 'PROOFREAD', 111 ],
		];
	}

	/** @dataProvider provideMatchCondition */
	public function testMatchCondition( $expected, $value, $condition, $max ) {
		$actual = MessageGroupStatesUpdaterJob::matchCondition( $value, $condition, $max );
		$this->assertEquals( $expected, $actual );
	}

	public static function provideMatchCondition() {
		return [
			[ true, 0, 'ZERO', 666 ],
			[ false, 1, 'ZERO', 666 ],
			[ true, 1, 'NONZERO', 666 ],
			[ false, 0, 'NONZERO', 666 ],
			[ true, 666, 'MAX', 666 ],
			[ false, 0, 'MAX', 666 ],
			[ false, 12, 'MAX', 666 ],
		];
	}

	public function testGetNewState() {
		$group = MessageGroups::getGroup( 'group-trans' );
		$transitions = $group->getMessageGroupStates()->getConditions();

		$stats = [ 5, 0, 0, 0 ];
		$newstate = MessageGroupStatesUpdaterJob::getNewState( $stats, $transitions );
		$this->assertEquals( 'unset', $newstate, 'all zero, should be unset' );

		$stats = [ 5, 1, 0, 0 ];
		$newstate = MessageGroupStatesUpdaterJob::getNewState( $stats, $transitions );
		$this->assertEquals( 'inprogress', $newstate, 'one translated message' );

		$stats = [ 5, 0, 1, 0 ];
		$newstate = MessageGroupStatesUpdaterJob::getNewState( $stats, $transitions );
		$this->assertEquals( 'inprogress', $newstate, 'one outdated message' );

		$stats = [ 5, 1, 1, 0 ];
		$newstate = MessageGroupStatesUpdaterJob::getNewState( $stats, $transitions );
		$this->assertEquals( 'inprogress', $newstate, 'one translated and one outdated message' );

		$stats = [ 5, 5, 0, 0 ];
		$newstate = MessageGroupStatesUpdaterJob::getNewState( $stats, $transitions );
		$this->assertEquals( 'proofreading', $newstate, 'all translated' );
	}

	/**
	 * @group Broken
	 * This tests fails regularly on WMF CI but haven't been able to reproduce locally.
	 */
	public function testHooks() {
		$user = $this->getTestSysop()->getUser();
		$group = MessageGroups::getGroup( 'group-trans' );

		// In the beginning...
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertFalse( $currentState, 'groups start from unset state' );

		// First translation
		$title = Title::newFromText( 'MediaWiki:key1/fi' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( 'trans1', $title );

		$status = $page->doUserEditContent( $content, $user, __METHOD__ );

		self::translateRunJobs();
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertEquals( 'inprogress', $currentState, 'in progress after first translation' );

		// First review
		$this->doApiRequestWithToken( [
			'action' => 'translationreview',
			'revision' => self::getRevisionRecordId( $status )
		], null, $user );

		self::translateRunJobs();
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertEquals( 'inprogress', $currentState, 'in progress while untranslated messages' );

		// Second translation
		$title = Title::newFromText( 'MediaWiki:key2/fi' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( 'trans2', $title );

		$status = $page->doUserEditContent( $content, $user, __METHOD__ );

		self::translateRunJobs();
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertEquals( 'proofreading', $currentState, 'proofreading after second translation' );

		// Second review
		$this->doApiRequestWithToken( [
			'action' => 'translationreview',
			'revision' => self::getRevisionRecordId( $status )
		], null, $user );
		self::translateRunJobs();
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertEquals( 'ready', $currentState, 'ready when all proofread' );

		// Change to translation
		$title = Title::newFromText( 'MediaWiki:key1/fi' );
		$page = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$content = ContentHandler::makeContent( 'trans1 updated', $title );

		$page->doUserEditContent( $content, $user, __METHOD__ );

		self::translateRunJobs();
		$currentState = GroupReviewActionApi::getState( $group, 'fi' );
		$this->assertEquals(
			'proofreading',
			$currentState,
			'back to proofreading after translation changed'
		);
	}

	protected static function getRevisionRecordId( Status $s ) {
		$value = $s->getValue();

		return $value['revision-record']->getId();
	}

	protected static function translateRunJobs() {
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroup();
		do {
			$job = $jobQueueGroup->pop();
			if ( !$job ) {
				break;
			}
			$job->run();
		} while ( true );
	}
}

class MockMessageHandle extends MessageHandle {
	public function __construct() {
	}

	public function getGroupIds() {
		return [ 'group-trans', 'group-notrans' ];
	}
}

class MessageGroupWithoutTransitions extends MockWikiMessageGroup {
	public function getMessageGroupStates() {
		return new MessageGroupStates();
	}
}

class MessageGroupWithTransitions extends MockWikiMessageGroup {
	public function getMessageGroupStates() {
		return new MessageGroupStates( [
			'state conditions' => [
				[ 'ready', [ 'PROOFREAD' => 'MAX' ] ],
				[ 'proofreading', [ 'TRANSLATED' => 'MAX' ] ],
				[
					'unset',
					[
						'UNTRANSLATED' => 'MAX',
						'OUTDATED' => 'ZERO',
						'TRANSLATED' => 'ZERO'
					]
				],
				[ 'inprogress', [ 'UNTRANSLATED' => 'NONZERO' ] ],
			]
		] );
	}
}
