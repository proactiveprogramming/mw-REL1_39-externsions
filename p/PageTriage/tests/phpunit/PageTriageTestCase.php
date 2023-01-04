<?php

namespace MediaWiki\Extension\PageTriage\Test;

use ApiTestCase;
use ApiUsageException;
use ExtensionRegistry;
use MediaWiki\Extension\PageTriage\ArticleCompile\ArticleCompileProcessor;
use MediaWiki\Extension\PageTriage\ArticleMetadata;
use MediaWiki\Extension\PageTriage\PageTriage;
use MediaWiki\Extension\PageTriage\PageTriageUtil;
use MediaWiki\MediaWikiServices;
use MWException;

/**
 * @group PageTriage
 * @group extensions
 */
abstract class PageTriageTestCase extends ApiTestCase {

	/** @var int */
	protected $draftNsId = 150;

	protected function setUp(): void {
		parent::setUp();

		// Define a Draft NS unless there already is one.
		$draftNsId = MediaWikiServices::getInstance()->getNamespaceInfo()->
			getCanonicalIndex( 'draft' );
		if ( $draftNsId === null ) {
			$this->setMwGlobals( [
				'wgExtraNamespaces' => [ $this->draftNsId => 'Draft' ],
				'wgPageTriageDraftNamespaceId' => $this->draftNsId
			] );
			$this->overrideMwServices();
		} else {
			$this->draftNsId = $draftNsId;
		}

		// Insert minimal required data (subset of what's done in PageTriage/sql/PageTriageTags.sql)
		// @TODO figure out why this is only run for the first test method when its in addDbData().
		$db = PageTriageUtil::getConnection( DB_PRIMARY );
		$db->insert(
			'pagetriage_tags',
			[
				[ 'ptrt_tag_name' => 'afc_state' ],
				[ 'ptrt_tag_name' => 'user_name' ],
				[ 'ptrt_tag_name' => 'recreated' ]
			],
			__METHOD__,
			[ 'IGNORE' ]
		);
	}

	protected function setUpForOresCopyvioTests() {
		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'pagetriage_page';
		$this->tablesUsed[] = 'pagetriage_tags';
		$this->tablesUsed[] = 'pagetriage_page_tags';
		$this->tablesUsed[] = 'pagetriage_log';
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ORES' ) ) {
			$this->tablesUsed[] = 'ores_model';
			$this->tablesUsed[] = 'ores_classification';
		}
		ArticleMetadata::clearStaticCache();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'ORES' ) ) {
			$this->clearHook( 'ORESCheckModels' );
		}
	}

	/**
	 * Helper method to query the PageTriageList API. Defaults to unreviewed draft pages.
	 * @param array $params
	 * @return array
	 * @throws ApiUsageException
	 */
	protected function getPageTriageList( array $params = [] ) {
		$list = $this->doApiRequest( array_merge( [
			'action' => 'pagetriagelist',
			'showunreviewed' => '1',
			'namespace' => $this->draftNsId,
		], $params ) );

		return $list[0]['pagetriagelist']['pages'];
	}

	/**
	 * @param string $title
	 * @param bool $draftQualityClass
	 * @param bool $copyvio
	 * @return mixed
	 * @throws MWException
	 */
	protected function makePage( $title, $draftQualityClass = false, $copyvio = false ) {
		$user = static::getTestUser()->getUser();
		$pageAndTitle = $this->insertPage( $title, 'some content', $this->draftNsId, $user );
		$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $pageAndTitle[ 'title' ] );
		$revId = $page->getLatest();
		if ( $draftQualityClass ) {
			self::setDraftQuality( $revId, $draftQualityClass );
		}
		if ( $copyvio ) {
			self::setCopyvio( $pageAndTitle[ 'id' ], $revId );
		}
		$acp = ArticleCompileProcessor::newFromPageId( [
			$page->getId()
		] );
		$acp->compileMetadata();
		$pageTriage = new PageTriage( $page->getId() );
		$pageTriage->addToPageTriageQueue();
		return $pageAndTitle[ 'id' ];
	}

	/**
	 * @param string[] $expectedPages
	 * @param array[] $response
	 * @param string $msg
	 */
	protected function assertPages( $expectedPages, $response, $msg = '' ) {
		$pagesFromResponse = array_map( static function ( $item ) {
			$title = $item[ 'title' ];
			return strpos( $title, ':' ) !== false ?
				explode( ':', $title )[1] :
				$title;
		}, $response );

		$this->assertArrayEquals( $expectedPages, $pagesFromResponse, false, false, $msg );
	}

	public static function setDraftQuality( $revId, $classId ) {
		$dbw = PageTriageUtil::getConnection( DB_PRIMARY );
		foreach ( [ 0, 1, 2, 3 ] as $id ) {
			$predicted = $classId === $id;
			$dbw->insert( 'ores_classification', [
				'oresc_model' => self::ensureOresModel( 'draftquality' ),
				'oresc_class' => $id,
				'oresc_probability' => $predicted ? 0.7 : 0.1,
				'oresc_is_predicted' => $predicted ? 1 : 0,
				'oresc_rev' => $revId,
			] );
		}
	}

	public static function ensureCopyvioTag() {
		$dbw = PageTriageUtil::getConnection( DB_PRIMARY );

		$dbw->upsert(
			'pagetriage_tags',
			[ 'ptrt_tag_name' => 'copyvio', 'ptrt_tag_desc' => 'copyvio' ],
			[ 'ptrt_tag_name' ],
			[ 'ptrt_tag_desc' => 'copyvio' ]
		);
	}

	public static function setCopyvio( $pageId, $revId ) {
		$dbw = PageTriageUtil::getConnection( DB_PRIMARY );

		$tagId = $dbw->selectField(
			'pagetriage_tags', 'ptrt_tag_id', [ 'ptrt_tag_name' => 'copyvio' ]
		);

		$dbw->insert(
			'pagetriage_page_tags',
			[
				'ptrpt_page_id' => $pageId,
				'ptrpt_tag_id' => $tagId,
				'ptrpt_value' => $revId,
			]
		);
	}

	public static function ensureOresModel( $name ) {
		$dbw = PageTriageUtil::getConnection( DB_PRIMARY );
		$modelInfo = [
			'oresm_name' => $name,
			'oresm_version' => '0.0.1',
			'oresm_is_current' => 1
		];
		$model = $dbw->selectField( 'ores_model', 'oresm_id', $modelInfo );
		if ( $model ) {
			return $model;
		}
		$dbw->upsert( 'ores_model', $modelInfo, [ 'oresm_id' ], $modelInfo );
		return $dbw->selectField( 'ores_model', 'oresm_id', $modelInfo );
	}

}