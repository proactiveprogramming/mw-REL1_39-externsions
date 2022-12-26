<?php

namespace GettingStarted;

use Category;
use MediaWiki\Page\PageStore;
use RedisConnRef;
use RedisException;
use Title;
use TitleFactory;

// See PageSuggester for API documentation
class CategoryPageSuggester implements PageSuggester {
	/** @var RedisConnRef */
	protected $redisConnection;

	/** @var Category */
	protected $category;

	/** @var PageStore */
	private $pageStore;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * Constructs a CategoryPageSuggester that uses the given category
	 *
	 * @param RedisConnRef $redisConnection
	 * @param Category $category Category to use for suggestions
	 * @param PageStore $pageStore
	 * @param TitleFactory $titleFactory
	 */
	public function __construct(
		RedisConnRef $redisConnection, Category $category, PageStore $pageStore, TitleFactory $titleFactory
	) {
		$this->redisConnection = $redisConnection;
		$this->category = $category;
		$this->pageStore = $pageStore;
		$this->titleFactory = $titleFactory;
	}

	/**
	 * @param int $count
	 * @param int $offset Ignored, because it does not make sense when randomly pulling articles
	 *   out of Redis.
	 * @return Title[]
	 */
	public function getArticles( $count, $offset ) {
		$key = RedisCategorySync::makeCategoryKey( $this->category );

		if ( !$this->redisConnection ) {
			wfDebugLog( 'GettingStarted', "Unable to acquire redis connection.  Returning early.\n" );
			return [];
		}

		try {
			$randomArticleIDs = $this->redisConnection->sRandMember( $key, $count );
		} catch ( RedisException $e ) {
			wfDebugLog( 'GettingStarted', 'Redis exception: ' . $e->getMessage() . ".  Returning early.\n" );
			return [];
		}

		if ( is_array( $randomArticleIDs ) ) {
			$titles = $this->pageStore
				->newSelectQueryBuilder()
				->wherePageIds( $randomArticleIDs )
				->caller( __METHOD__ )
				->fetchPageRecords();

			return array_map(
				function ( $title ) {
					return $this->titleFactory
						->castFromPageIdentity( $title );
				},
				iterator_to_array( $titles )
			);
		} else {
			wfDebugLog( 'GettingStarted', 'Redis returned a non-array value, possibly an error.' );
			return [];
		}
	}

	public function isRandomized() {
		return true;
	}
}
