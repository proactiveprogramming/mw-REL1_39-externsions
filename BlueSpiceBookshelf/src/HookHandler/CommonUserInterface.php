<?php

namespace BlueSpice\Bookshelf\HookHandler;

use BlueSpice\Bookshelf\ChapterPagerPanel;
use BlueSpice\Bookshelf\GlobalActionsManager;
use BlueSpice\Bookshelf\MainLinkPanel;
use BlueSpice\Bookshelf\SidebarBookPanel;
use ConfigFactory;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;
use RequestContext;

class CommonUserInterface implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @var ConfigFactory
	 */
	private $configFactory = null;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct( ConfigFactory $configFactory ) {
		$this->configFactory = $configFactory;
	}

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'GlobalActionsManager',
			[
				'bs-special-bookshelf' => [
					'factory' => static function () {
						return new GlobalActionsManager();
					}
				]
			]
		);
		$config = $this->configFactory->makeConfig( 'bsg' );
		if ( $config->get( 'BookshelfMainLinksBookshelf' ) ) {
			$registry->register(
				'MainLinksPanel',
				[
					'bs-special-bookshelf' => [
						'factory' => static function () {
							return new MainLinkPanel();
						},
						'position' => 20
					]
				]
			);
		}

		$context = RequestContext::getMain();
		$title = $context->getTitle();
		$registry->register(
			"SidebarPrimaryTabPanels",
			[
				'book' => [
					'factory' => static function () use ( $title ) {
						return new SidebarBookPanel( $title );
					}
				]
			]
		);
		if ( $config->get( 'BookShelfShowChapterNavigationPagerBeforeContent' ) ) {
			$registry->register(
				'DataBeforeContent', [
					'chapter-pager' => [
						'factory' => static function () use ( $title ) {
							return new ChapterPagerPanel( $title );
						}
					]
				]
			);
		}
		if ( $config->get( 'BookShelfShowChapterNavigationPagerAfterContent' ) ) {
			$registry->register(
				'DataAfterContent', [
					'chapter-pager' => [
						'factory' => static function () use ( $title ) {
							return new ChapterPagerPanel( $title );
						},
						'position' => 1
					]
				]
			);
		}
	}
}
