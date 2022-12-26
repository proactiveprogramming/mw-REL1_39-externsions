<?php

namespace SearchVue;

use MediaWiki\MediaWikiServices;
use MediaWiki\Search\SearchWidgets\FullSearchResultWidget;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use OutputPage;
use SearchResultSet;
use SpecialPage;
use SpecialSearch;
use User;

/**
 * @license GPL-2.0-or-later
 */

class Hooks implements
	SpecialPageBeforeExecuteHook
{
	/**
	 * @var array holding the search result object.
	 */
	private $textMatches;

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialPageBeforeExecute
	 *
	 * @param SpecialPage $special
	 * @param string|null $subpage
	 * @return false|void false to abort the execution of the special page, "void" otherwise
	 */
	public function onSpecialPageBeforeExecute( $special, $subpage ) {
		if ( $special->getName() !== 'Search' ) {
			return;
		}
		$services = MediaWikiServices::getInstance();
		$searchPreviewEnabled = $this->searchPreviewIsEnabled( $special, $services );

		if ( $searchPreviewEnabled ) {
			$special->getOutput()->addModuleStyles( [ 'searchVue.styles' ] );
			$special->getOutput()->addModules( [ 'searchVue' ] );

			if ( $this->isMobileView( $special ) ) {
				$special->getOutput()->addModuleStyles( [ 'searchVue.mobile.styles' ] );
			}

			$repositoryApiBaseUri = $services->getMainConfig()->get( 'QuickViewMediaRepositoryApiBaseUri' );
			$repositorySearchUri = $services->getMainConfig()->get( 'QuickViewMediaRepositorySearchUri' );
			$repositoryUri = $services->getMainConfig()->get( 'QuickViewMediaRepositoryUri' );
			$searchFilterForQID = $services->getMainConfig()->get( 'QuickViewSearchFilterForQID' );
			$special->getOutput()->addJsConfigVars( [
				'wgQuickViewMediaRepositoryApiBaseUri' => $repositoryApiBaseUri,
				'wgQuickViewMediaRepositorySearchUri' => $repositorySearchUri,
				'wgQuickViewMediaRepositoryUri' => $repositoryUri,
				'wgQuickViewSearchFilterForQID' => $searchFilterForQID
			] );
		}
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResults
	 *
	 * @param string $term Search term
	 * @param SearchResultSet|null $titleMatches
	 * @param SearchResultSet|null $textMatches
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSpecialSearchResults( $term, $titleMatches, $textMatches ) {
		$this->textMatches = $this->formatResult( $textMatches );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialSearchResultsAppend
	 *
	 * @param SpecialSearch $special SpecialSearch object ($this)
	 * @param OutputPage $out $wgOut
	 * @param string $term Search term specified by the user
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onSpecialSearchResultsAppend( $special, $out, $term ) {
		if ( $special->getName() !== 'Search' ) {
			return;
		}

		$out->addJsConfigVars(
			[
				'wgSpecialSearchTextMatches' => $this->textMatches,
			]
		);
	}

	/**
	 * Extract the searchResult information required by the extension UI
	 *
	 * @param SearchResultSet|null $resultSet
	 * @return array
	 */
	private function formatResult( $resultSet ) {
		if ( !$resultSet ) {
			return [];
		}

		$services = MediaWikiServices::getInstance();
		$thumbnailProvider = $services->getSearchResultThumbnailProvider();

		$formattedResultSet = [];

		foreach ( $resultSet as $result ) {
			if ( $result->getTitle() !== '' ) {
				$thumbnails = $thumbnailProvider->getThumbnails(
					[ $result->getTitle() ],
					FullSearchResultWidget::THUMBNAIL_SIZE
				);

				$formattedResult = $result->getTitle();
				$formattedResult->text = $result->getTextSnippet();
				if ( $thumbnails ) {
					$thumbnail = reset( $thumbnails );
					$formattedResult->thumbnail = [
						'width' => $thumbnail->getWidth(),
						'height' => $thumbnail->getHeight(),
					];
				}
				$formattedResultSet[] = $formattedResult;
			}
		}

		return $formattedResultSet;
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 * Adds a default-enabled preference to gate the feature
	 * @param User $user
	 * @param array &$prefs
	 */
	public static function onGetPreferences( $user, &$prefs ) {
		$prefs['searchpreview'] = [
			'type' => 'toggle',
			'section' => 'searchoptions/searchmisc',
			'label-message' => 'searchvue-label',
			'help-message' => 'searchvue-help',
		];

		$prefs['searchpreview-tutorial-enabled'] = [
			'type' => 'api'
		];
	}

	/**
	 * Define if the extension should be enabled. This consider the device type
	 * and various configurations
	 *
	 * @param SpecialPage $special
	 * @param MediaWikiServices $services
	 * @return bool|string
	 */
	private function searchPreviewIsEnabled( $special, $services ) {
		$userConfig = $services->getUserOptionsLookup();
		$enabledInUserConfig = $userConfig->getBoolOption( $special->getUser(), 'searchpreview' );
		$isMobileView = $this->isMobileView( $special );
		$enabledOnMobile = $this->enableExtensionOnMobile( $special, $services );

		if ( !$enabledInUserConfig ) {
			return false;
		}
		if ( !$isMobileView ) {
			return true;
		}

		return $enabledOnMobile;
	}

	/**
	 * Define if the extension should be loaded on mobile taking into consideration
	 * query parameters and configuration setting
	 *
	 * @param SpecialPage $special
	 * @param MediaWikiServices $services
	 * @return string
	 */
	private static function enableExtensionOnMobile( $special, $services ) {
		$enableMobileQueryParams = $special->getRequest()->getVal( 'quickViewEnableMobile' );

		if ( $enableMobileQueryParams !== null ) {
			return $enableMobileQueryParams;
		} else {
			return $services->getMainConfig()->get( 'QuickViewEnableMobile' );
		}
	}

	/**
	 * Define if the the current request is serving the mobile skin 'minerva'
	 *
	 * @param SpecialPage $special
	 * @return bool
	 */
	private function isMobileView( $special ) {
		return $special->getSkin()->getSkinName() === 'minerva';
	}
}
