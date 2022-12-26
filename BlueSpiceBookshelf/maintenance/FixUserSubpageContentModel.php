<?php

use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class FixUserSubpageContentModel extends LoggedUpdateMaintenance {

	/**
	 * @param IDatabase $db
	 * @return array
	 */
	private function getPages( $db ) {
		$res = $db->select(
			'page',
			'*',
			[
				'page_namespace' => NS_USER,
				'page_content_model' => 'book'
			],
			__METHOD__
		);

		$pages = [];
		foreach ( $res as $row ) {
			$title = Title::newFromRow( $row );
			if ( !$title ) {
				continue;
			}
			if ( !$this->isUserBook( $title ) ) {
				$this->output( "\n{$title->getPrefixedDBkey()}" );
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
	private function convert( $pages, $db ) {
		if ( empty( $pages ) ) {
			return true;
		}

		$res = $db->update(
			'page',
			[
				'page_content_model' => 'wikitext'
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
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$pages = $this->getPages( $dbw );
		$res = $this->convert( $pages, $dbw );
		$this->output( "\nOK\n" );
		return $res;
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_bookmaker_fix_user_subpage_content_model';
	}
}

$maintClass = FixUserSubpageContentModel::class;
require_once RUN_MAINTENANCE_IF_MAIN;
