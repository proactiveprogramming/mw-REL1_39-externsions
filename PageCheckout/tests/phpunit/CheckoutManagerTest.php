<?php

namespace MediaWiki\Extension\PageCheckout\Tests;

use MediaWiki\Extension\PageCheckout\CheckoutManager;
use MediaWiki\Extension\PageCheckout\Entity\CheckoutEntity;
use MediaWiki\Extension\PageCheckout\Repo\CheckoutEventRepo;
use MediaWiki\Extension\PageCheckout\Repo\CheckoutRepo;
use MediaWiki\Extension\PageCheckout\SpecialLogLogger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Title;
use User;

/**
 * @covers \MediaWiki\Extension\PageCheckout\CheckoutManager
 */
class CheckoutManagerTest extends TestCase {
	/** @var CheckoutRepo|MockObject */
	private $checkoutRepoMock;
	/** @var CheckoutEventRepo|MockObject */
	private $eventRepoMock;
	/** @var CheckoutManager */
	private $manager;
	/** @var Title */
	private $title;
	/** @var User */
	private $user;

	protected function setUp(): void {
		$this->title = $this->createMock( Title::class );
		$this->title->method( 'getLatestRevID' )->willReturn( 2 );
		$this->title->method( 'getArticleID' )->willReturn( 1 );
		$this->title->method( 'getDbKey' )->willReturn( 'Dummy' );
		$this->title->method( 'getNamespace' )->willReturn( 0 );
		$this->title->method( 'exists' )->willReturn( true );

		$this->checkoutRepoMock = $this->createMock( CheckoutRepo::class );
		$this->eventRepoMock = $this->createMock( CheckoutEventRepo::class );

		$this->user = $this->createMock( \User::class );
		$this->user->method( 'getId' )->willReturn( 100 );
		$this->user->method( 'getName' )->willReturn( 'Test' );
		$this->user->method( 'isRegistered' )->willReturn( true );

		$specialLogLoggerMock = $this->createMock( SpecialLogLogger::class );
		$this->manager = new CheckoutManager(
			$this->user, $this->checkoutRepoMock, $this->eventRepoMock, $specialLogLoggerMock
		);
	}

	/**
	 * @covers \MediaWiki\Extension\PageCheckout\CheckoutManager::checkout
	 * @throws \MWException
	 */
	public function testCheckout() {
		$this->eventRepoMock->method( 'save' )->willReturn( true );
		$this->checkoutRepoMock->method( 'save' )->willReturn(
			new CheckoutEntity( 1, $this->title, $this->user )
		);
		$this->checkoutRepoMock->expects( $this->once() )->method( 'save' );
		$this->manager->checkout( $this->title, $this->user );
	}

	/**
	 * @covers \MediaWiki\Extension\PageCheckout\CheckoutManager::checkIn
	 * @throws \MWException
	 */
	public function testCheckin() {
		$this->checkoutRepoMock->method( 'getForPage' )->willReturn(
			new CheckoutEntity( 1, $this->title, $this->user )
		);
		$this->checkoutRepoMock->expects( $this->once() )->method( 'delete' );

		$this->manager->checkIn( $this->title );
	}

	/**
	 * @covers \MediaWiki\Extension\PageCheckout\CheckoutManager::clearCheckout
	 * @throws \MWException
	 */
	public function testClearCheckout() {
		$this->checkoutRepoMock->method( 'getForPage' )->willReturn(
			new CheckoutEntity( 1, $this->title, $this->user )
		);
		$this->checkoutRepoMock->expects( $this->once() )->method( 'delete' );

		$this->manager->clearCheckout( $this->title );
	}
}
