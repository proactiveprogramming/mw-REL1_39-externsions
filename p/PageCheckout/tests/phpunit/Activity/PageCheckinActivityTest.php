<?php

namespace MediaWiki\Extension\PageCheckout\Tests\Activity;

use MediaWiki\Extension\PageCheckout\Activity\PageCheckInActivity;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;

/**
 * @group Broken
 * @covers \MediaWiki\Extension\PageCheckout\Activity\PageCheckInActivity
 * @group Database
 */
class PageCheckinActivityTest extends PageCheckoutActivityTest {

	/**
	 * @throws WorkflowExecutionException
	 * @covers \MediaWiki\Extension\PageCheckout\Activity\PageCheckInActivity::execute
	 */
	public function testExecute() {
		parent::testExecute();
	}

	/**
	 * @inheritDoc
	 */
	protected function getActivity( $manager, $userFactory, $titleFactoryMock, $task ) {
		return new PageCheckInActivity( $manager, $userFactory, $titleFactoryMock, $task );
	}

	/**
	 * @inheritDoc
	 */
	protected function getExpectedMethod() {
		return 'checkin';
	}
}
