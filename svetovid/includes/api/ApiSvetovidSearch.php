<?php

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;
use Wikimedia\ParamValidator\TypeDef\StringDef;

class ApiSvetovidSearch extends ApiBase {
	/**
	 * @inheritDoc
	 * @throws ApiUsageException
	 * @throws MWException
	 */
	public function execute() {
		$user = $this->getUser();
		if ( $user->isAnon() ) {
			$this->dieWithError( 'Only registered users can access this endpoint.' );
		}

		if ( !$this->getPermissionManager()->userHasRight( $user, 'svetovid-search' ) ) {
			$this->dieWithError( "You are not allowed to do that." );
		}

		if ( $user->pingLimiter( 'svsearch' ) ) {
			$this->dieWithError( 'Rate limit for Svetovid searching exceeded, please try again later.' );
		}

		$params = $this->extractRequestParams();
		$texts = $params['texts'];
		$namespaces = $params['namespaces'];
		$ignoreLinking = $params['ignorelinking'];

		if ( sizeof( $texts ) < 1 ) {
			$this->dieWithError( 'No search string was specified.' );
		}

		if ( sizeof( $namespaces ) < 1 ) {
			$this->dieWithError( 'No namespace was specified.' );
		}

		$pageId = $params['pageid'];
		$targetTitle = Title::newFromID( $pageId );

		if ( !$targetTitle ) {
			$this->dieWithError( 'Invalid target page ID.' );
		}

		if ( $ignoreLinking ) {
			$pagesToIgnore = $this->getPagesToIgnore( $targetTitle, $namespaces );

			if ( !in_array( $pageId, $pagesToIgnore ) ) {
				$pagesToIgnore[] = $pageId;
			}
		} else {
			$pagesToIgnore = [ $pageId ];   // ignore self
		}

		$se = MediaWikiServices::getInstance()->getSearchEngineFactory()->create( 'cirrussearch' );
		$se->setNamespaces( $namespaces );

		$searchStrings = self::makeSearchStrings( $texts );
		$finalResults = [];
		$finalIds = [];

		foreach ( $searchStrings as $searchString ) {
			$searchResults = $se->searchText( $searchString );

			if ( is_a( $searchResults, 'Status' ) ) {
				if ( !$searchResults->getStatusValue()->isGood() ) {
					$this->dieWithError( 'Search operation failed.' );
				}

				$resultsIterable = $searchResults->value;
			} elseif ( is_a( $searchResults, 'ISearchResultSet' ) ) {
				$resultsIterable = $searchResults->extractResults();
			} else {
				$this->dieWithError( 'Search operation failed.' );
			}

			/** @var ISearchResultSet $resultsIterable */
			/** @var SearchResult $result */
			foreach ( $resultsIterable as $result ) {
				$title = $result->getTitle();
				$id = $title->getArticleID();

				if ( $id == 0 || in_array( $id, $pagesToIgnore ) || in_array( $id, $finalIds ) ) {
					continue;
				}

				// TODO: maybe add some more search results filtering
				$finalResults[] = $title;
				$finalIds[] = $id;
			}
		}

		// save search params to cache
		$cache = MediaWikiServices::getInstance()->getLocalServerObjectCache();
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$pageFactory = MediaWikiServices::getInstance()->getWikiPageFactory();
		$cacheExpiry = $config->get( 'SvetovidSearchCacheExpiry' );
		$maxCount = $config->get( 'SvetovidMaxSearchResults' );
		$targetId = $targetTitle->getArticleID();
		$count = 0;

		// return results
		foreach ( $finalResults as $result ) {
			if ( $count >= $maxCount ) break;

			$page = $pageFactory->newFromLinkTarget( $result );
			$revision = $page->getRevisionRecord();
			$key = 'svsearch_' . $targetId . '_' . $revision->getId();

			if ( !$cache->get( $key ) ) {
				// if not already cached...
				$text = SvetovidUtilities::getTextFromRevision( $revision );
				if ( !$text ) continue;

				$hr = new SvetovidHookRunner();
				$changes = 0;
				if ( $hr->onSvetovidAddLinks( $targetTitle, $page, $texts, $text, $changes ) ) {
					$changes = SvetovidTextProcessing::addLinks( $text, $targetTitle, $texts );
				}

				if ( $changes === 0 ) continue;     // no changes, ignore this page

				$toCache = new stdClass();
				$toCache->text = $text;
				$toCache->changes = $changes;
				$cache->set( $key, $toCache, $cacheExpiry );
			}

			$stats = AdvancedBacklinksUtils::GetLinkStats( $result, true, true );
			$this->getResult()->addValue(
				null,
				null,
				[
					'title' => $result->getPrefixedText(),
					'link' => $result->getFullURL( 'action=edit&svetovid=' . $targetId ),
					'inlinks' => $stats['in'],
					'outlinks' => $stats['out']
				]
			);

			$count++;
		}
	}

	private static function makeSearchStrings( array $texts ) {
		$maxLen = MediaWikiServices::getInstance()->getMainConfig()->get( "CirrusSearchMaxFullTextQueryLength" );
		$searchStrings = [];
		$current = [];
		$currentLen = 0;

		foreach ( $texts as $text ) {
			$text = str_replace( '"', '', $text );
			$sl = mb_strlen( $text );
			if ( $sl > $maxLen ) {
				continue;       // nope.
			}

			if ( $currentLen + $sl + 4 * ( sizeof( $current ) + 1 ) > $maxLen ) {
				if ( sizeof( $current ) === 1 ) {
					$searchStrings[] = $current[0];
				} else {
					$searchStrings[] = '"' . implode( '"OR"', $current ) . '"';
				}

				$current = [];
				$currentLen = 0;
			}

			$current[] = $text;
			$currentLen += $sl;
		}

		if ( sizeof( $current ) == 1 ) {
			$searchStrings[] = $current[0];
		} elseif ( sizeof( $current ) > 1 ) {
			$searchStrings[] = '"' . implode( '"OR"', $current ) . '"';
		}

		return $searchStrings;
	}

	private function getPagesToIgnore( Title $target, array $namespaces ) {
		$dbr = wfGetDB( DB_REPLICA );
		$ids = $dbr->selectFieldValues(
			[ 'ab_links' ],
			'abl_from',
			[
				'abl_title' => $target->getDBkey(),
				'abl_namespace' => $target->getNamespace(),
				'abl_from_namespace' => $namespaces,
				'abl_through' => 0
			],
			__METHOD__,
			[ 'DISTINCT' ]
		);

		$ids = array_merge( $ids, $dbr->selectFieldValues(
			[ 'ab_links', 'page', 'redirect' ],
			'abl_from',
			[
				'rd_title' => $target->getDBkey(),
				'rd_namespace' => $target->getNamespace(),
				'page_id = rd_from',
				'abl_title = page_title',
				'abl_namespace = page_namespace',
				'abl_through' => 0
			],
			__METHOD__,
			[ 'DISTINCT' ]
		) );
		$ids = array_unique( $ids );

		// add titles from blacklist
		$blacklist = MediaWikiServices::getInstance()->getMainConfig()->get( 'SvetovidSearchBlacklist' );
		foreach ( $blacklist as $name ) {
			$title = Title::newFromText( $name );
			$id = $title ? $title->getArticleID() : 0;

			if ( $id && !in_array( $id, $ids ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Return an array describing all possible parameters to this module
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'pageid' => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true
			],
			'texts' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				StringDef::PARAM_MAX_CHARS => 256
			],
			'namespaces' => [
				ParamValidator::PARAM_ISMULTI => true,
				ParamValidator::PARAM_TYPE => 'namespace',
				ParamValidator::PARAM_REQUIRED => true
			],
			'ignorelinking' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => true
			]
		];
	}
}
