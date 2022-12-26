<?php

use MediaWiki\MediaWikiServices;

require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/maintenance/Maintenance.php';

class UserBookContentLanguage extends LoggedUpdateMaintenance {

	/** @var MediaWikiServices */
	protected $services = null;

	public function __construct() {
		parent::__construct();
		$this->services = MediaWikiServices::getInstance();
	}

	/**
	 * @param Title $bookTitle
	 * @param string[] $langCodes
	 * @param string $contentLang
	 * @return Title|bool
	 */
	private function getMoveToTitle( $bookTitle, $langCodes, $contentLang ) {
		if ( !$bookTitle->isSubpage() ) {
			return false;
		}
		$user = $this->services->getUserFactory()->newFromName( $bookTitle->getRootText() );
		if ( !$user || $user->isAnon() ) {
			// ignore non existing users such a delted ones - they do not need books anymore :)
			return false;
		}
		$prefix = Message::newFromKey(
			'bs-bookshelf-personal-books-page-prefix',
			$user->getName()
		);
		$correctTitle = Title::makeTitle(
			NS_USER,
			$prefix->inContentLanguage()->parse() . $bookTitle->getSubpageText()
		);
		// most likely already in the correct place
		if ( !$correctTitle || $correctTitle->equals( $bookTitle ) ) {
			return false;
		}
		foreach ( $langCodes as $langCode ) {
			if ( $langCode === $contentLang ) {
				// already checked above for performance reasons
				continue;
			}
			try {
				$moveTitle = Title::makeTitle(
					NS_USER,
					$prefix->inLanguage( $langCode )->parse() . $bookTitle->getSubpageText()
				);
			} catch ( Exception $e ) {
				continue;
			}
			if ( !$moveTitle || !$moveTitle->exists() ) {
				continue;
			}
			// we found one one stored the users lanugage, return how it should be
			return $correctTitle;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function doDBUpdates() {
		$this->output( "...Update '" . $this->getUpdateKey() . "': " );
		$res = $this->services->getDBLoadBalancer()
			->getConnection( DB_REPLICA )->select(
			'page',
			[ 'page_id', 'page_namespace', 'page_title' ],
			[ 'page_namespace' => NS_USER ],
			__METHOD__
		);
		if ( $res->numRows() < 1 ) {
			$this->output( "OK\n" );
			return true;
		}
		$langUtils = $this->services->getLanguageNameUtils();
		$contentLang = $this->services->getConfigFactory()->makeConfig( 'bsg' )
			->get( 'LanguageCode' );
		$langCodes = array_keys( $langUtils->getLanguageNames() );
		foreach ( $res as $row ) {
			$title = Title::newFromRow( $row );
			if ( !$title ) {
				continue;
			}
			$this->output( "." );
			$moveTitle = $this->getMoveToTitle( $title, $langCodes, $contentLang );
			if ( !$moveTitle ) {
				continue;
			}
			$status = $this->moveBook( $title, $moveTitle );
		}
		$this->output( "OK\n" );
		return true;
	}

	/**
	 *
	 * @param Title $title
	 * @param Title $moveTitle
	 * @return Status
	 */
	private function moveBook( Title $title, Title $moveTitle ) {
		$status = Status::newGood();
		try{
			$move = $this->services->getMovePageFactory()->newMovePage( $title, $moveTitle );
			$status = $move->move(
				$this->getMaintenanceUser(),
				"Bookshelf: Store user books in content language subpage",
				false
			);
		} catch ( Exception $e ) {
			$status->fatal( $e->getMessage() );
		}
		return $status;
	}

	/**
	 *
	 * @return User
	 */
	protected function getMaintenanceUser() {
		return $this->services->getService( 'BSUtilityFactory' )
			->getMaintenanceUser()->getUser();
	}

	/**
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'bs_bookmaker_user_book_content_lang';
	}

}

$maintClass = UserBookContentLanguage::class;
require_once RUN_MAINTENANCE_IF_MAIN;
