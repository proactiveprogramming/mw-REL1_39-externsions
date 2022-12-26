<?php

namespace MediaWiki\Extension\PageCheckout\Tests\Activity;

use MediaWiki\Extension\PageCheckout\Activity\PageCheckoutActivity;
use MediaWiki\Extension\PageCheckout\CheckoutManager;
use MediaWiki\Extension\Workflows\Definition\DefinitionContext;
use MediaWiki\Extension\Workflows\Definition\Element\Task;
use MediaWiki\Extension\Workflows\Exception\WorkflowExecutionException;
use MediaWiki\Extension\Workflows\WorkflowContext;
use MediaWiki\User\UserFactory;
use PHPUnit\Framework\TestCase;
use Title;
use TitleFactory;
use User;

/**
 * @group Broken
 * @covers \MediaWiki\Extension\PageCheckout\Activity\PageCheckoutActivity
 * @group Database
 */
class PageCheckoutActivityTest extends TestCase {

	/**
	 * @param string|null $username
	 * @param int $pageId
	 * @dataProvider provideExceptionData
	 */
	public function testExceptions( $username, $pageId ) {
		$this->expectException( WorkflowExecutionException::class );

		$managerMock = $this->createMock( CheckoutManager::class );
		$userMock = $this->createMock( User::class );
		$userMock->method( 'isRegistered' )->willReturn( true );
		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->method( 'newFromName' )->willReturnCallback( static function ( $username ) use ( $userMock ) {
			return $username === 'UTSysop' ? $userMock : null;
		} );

		$taskMock = $this->createMock( Task::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$activity = new PageCheckoutActivity( $managerMock, $userFactoryMock, $titleFactoryMock, $taskMock );

		$titleMock = $this->createMock( Title::class );
		$titleFactoryMock->method( 'newFromID' )->willReturnCallback( static function ( $pageId ) use( $titleMock ) {
			return $pageId === 100 ? $titleMock : null;
		} );
		$context = new WorkflowContext( new DefinitionContext( [
			'pageId' => $pageId
		] ), $titleFactoryMock );
		$data = [];
		if ( $username !== null ) {
			$data['user'] = $username;
		}
		$activity->execute( $data, $context );
	}

	/**
	 * @covers \MediaWiki\Extension\PageCheckout\Activity\PageCheckoutActivity::execute
	 * @throws WorkflowExecutionException
	 */
	public function testExecute() {
		$managerMock = $this->createMock( CheckoutManager::class );
		$managerMock->expects( $this->once() )->method( $this->getExpectedMethod() );

		$userMock = $this->createMock( User::class );
		$userMock->method( 'isRegistered' )->willReturn( true );
		$userFactoryMock = $this->createMock( UserFactory::class );
		$userFactoryMock->method( 'newFromName' )->willReturn( $userMock );

		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$taskMock = $this->createMock( Task::class );

		$activity = $this->getActivity( $managerMock, $userFactoryMock, $titleFactoryMock, $taskMock );

		$titleMock = $this->createMock( Title::class );
		$titleFactoryMock = $this->createMock( TitleFactory::class );
		$titleFactoryMock->method( 'newFromID' )->willReturn( $titleMock );

		$context = new WorkflowContext( new DefinitionContext( [
			'pageId' => 100
		] ), $titleFactoryMock );
		$activity->execute( [
			'user' => 'UTSysop'
		], $context );
	}

	protected function getActivity( $manager, $userFactoryMock, $titleFactoryMock, $task ) {
		return new PageCheckoutActivity( $manager, $userFactoryMock, $titleFactoryMock, $task );
	}

	protected function getExpectedMethod() {
		return 'checkout';
	}

	/**
	 * @return array[]
	 */
	public function provideExceptionData() {
		return [
			'username-not-set' => [
				null, 100
			],
			'non-existing-user' => [
				'NonExistingUser', 100
			],
			'invalid-page' => [
				'UTSysop', 200
			]
		];
	}

	/**
	 * @return bool
	 */
	public function needsDB() {
		return true;
	}
}
