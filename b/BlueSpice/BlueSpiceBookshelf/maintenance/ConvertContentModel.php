<?php

use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class ConvertContentModel extends LoggedUpdateMaintenance {

	/**
	 * @param IDatabase $db
	 * @return array
	 */
	private function getPages( $db ) {
		$res = $db->select(
			'page',
			[ 'page_id', 'page_namespace', 'page_title', 'page_content_model' ],
			[ 'page_namespace' => [ NS_BOOK, NS_USER ] ],
			__METHOD__
		);

		$pages = [];
		foreach ( $res as $row ) {
			if ( $row->page_content_model === 'book' ) {
				// Nothing to do
				continue;
			}
			$title = Title::newFromRow( $row );
			if ( !$title ) {
				continue;
			}
			if ( $this->isBook( $title ) ) {
				$pages[(int)$row->page_id] = $title;
			}
			if ( $this->isUserBook( $title ) ) {
				$pages[(int)$row->page_id] = $title;
			}
		}

		return $pages;
	}

	/**
	 * @param Title[] $pages
	 * @param IDatabase $db
	 * @return bool
	 */
	private function convert( $pages, IDatabase $db ) {
		if ( empty( $pages ) ) {
			return true;
		}

		$res = $db->update(
			'page',
			[
				'page_content_model' => 'book'
			],
			[
				'page_id' => array_keys( $pages )
			],
			__METHOD__
		);
		if ( $res ) {
			foreach ( $pages as $title ) {
				$title->invalidateCache();
			}
		}
		return $res;
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	private function isBook( $title ) {
		return (int)$title->getNamespace() === NS_BOOK;
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	private function isUserBook( $title ) {
		if ( !(int)$title->getNamespace() === NS_USER ) {
			return false;
		}
		if ( !$title->isSubpage() ) {
			return false;
		}
		$user = MediaWikiServices::getInstance()->getUserFactory()
			->newFromName( $title->getRootText() );
		if ( !$user || $user->isAnon() ) {
			// ignore non existing/deleted users - they do not need books anymore :)
			return false;
		}
		$prefix = Message::newFromKey(
			'bs-bookshelf-personal-books-page-prefix',
			$user->getName()
		);
		$bookTitle = Title::makeTitle(
			NS_USER,
			$prefix->inContentLanguage()->parse() . $title->getSubpageText()
		);
		return $bookTitle && $title->equals( $bookTitle );
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "...Update '" . $this->getUpdateKey() . "': " );
		$dbLoadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$pages = $this->getPages(
			$dbLoadBalancer->getConnection( DB_REPLICA )
		);
		$res = $this->convert(
			$pages,
			$dbLoadBalancer->getConnection( DB_PRIMARY )
		);
		$this->output( "OK\n" );
		return $res;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_bookmaker_convert_content_model3';
	}
}

$maintClass = ConvertContentModel::class;
require_once RUN_MAINTENANCE_IF_MAIN;
