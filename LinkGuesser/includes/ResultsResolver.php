<?php
namespace MediaWiki\Extension\LinkGuesser;

use MediaWiki\MediaWikiServices;
use ExtensionRegistry;
use Status;
use SearchEngine;
use SearchEngineConfig;
use SearchEngineFactory;
use Title;

class ResultsResolver {
    public static function retrieveResults(
        Title $querySubject
    ): array {
        $services = MediaWikiServices::getInstance();
        $config = $services->getConfigFactory()->makeConfig( 'LinkGuesser' );

        $titleText = $querySubject->getText();

        // First try: change all special characters to spaces and search
        $strippedTitle = preg_replace('/[^A-Za-z0-9\-]/', ' ', $titleText);

        $matches = ResultsResolver::doSearch( "intitle:$strippedTitle" );

        // Secondary tries
        if ( $matches->count() < 1 ) {
            // Try searching with subpage's ending
            if ( strpos( $titleText, '/' ) !== false ) {
                $subpageTitleParts = explode( '/', $titleText );
                $lastPartOfTitle = end( $subpageTitleParts ); // can't put explode() call directly in here per PHP manual on end()
                $matches = ResultsResolver::doSearch( "intitle:$lastPartOfTitle" );
            }
        }

        $maxResultsRaw = $config->get( 'LinkGuesserResultsLimit' );
        $maxResults = is_int( $maxResultsRaw )
            ? $maxResultsRaw
            : 25; // if $wgLinkGuesserResultsLimit isn't an int, fall back to default value.

        $count = 0;
        $results = [];

        foreach ( $matches as $result ) {
			$count++;

            if ( $count > $maxResults ) {
                break;
            }

			// Silently skip broken and missing titles
			if ( $result->isBrokenTitle() || $result->isMissingRevision() ) {
				continue;
			}

			$resultTitle = $result->getTitle();
            $results[] = $resultTitle;
		}

        return $results;
    }

    // Shamelessly inspired by api/ApiQuerySearch.php
    private static function doSearch(
        string $query
    ): object {
        $services = MediaWikiServices::getInstance();

        $searchEngineConfig = new SearchEngineConfig(
			$services->getMainConfig(),
			$services->getContentLanguage(),
			$services->getHookContainer(),
			ExtensionRegistry::getInstance()->getAttribute( 'SearchMappings' )
        );
        $defaultNamespaces = $searchEngineConfig->defaultNamespaces();
        $searchEngineFactory = $services->getSearchEngineFactory();
        $searchEngine = $searchEngineFactory->create();
        $searchEngine->setNamespaces( $defaultNamespaces );

        $matches = $searchEngine->searchText( $query );

        if ( $matches instanceof Status ) {
            $status = $matches;
            $matches = $status->getValue();
        } else {
            $status = null;
        }

        if ( $status ) {
			if ( !$status->isOK() ) {
                return []; // failure
			}
		} elseif ( $matches === null ) {
			return [];
		}

        return $matches;
    }
}