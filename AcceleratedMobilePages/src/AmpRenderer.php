<?php

namespace AMP;

use AMP\Description\PageDescription;
use Article;
use Config;
use HtmlArmor;
use MediaWiki\MediaWikiServices;
use MessageLocalizer;
use ParserOutput;
use ResourceLoaderSkinModule;
use TemplateParser;
use Title;

class AmpRenderer {
	/** @var Title */
	private $mainPage;
	/** @var AmpStylesheet */
	private $ampStylesheet;
	/** @var PageDescription */
	private $pageDescription;

	public function __construct(
		AmpStylesheet $ampStylesheet, PageDescription $pageDescription, Title $mainPage
	) {
		$this->mainPage = $mainPage;
		$this->ampStylesheet = $ampStylesheet;
		$this->pageDescription = $pageDescription;
	}

	/**
	 * @param Article $article
	 * @return string
	 * @throws RevisionNotFound
	 */
	public function render( Article $article ) {
		$parserOutput = $article->getParserOutput();

		if ( $parserOutput === false ) {
			throw new RevisionNotFound();
		}

		$title = $parserOutput->getTitleText();

		$templates = new TemplateParser( __DIR__ . '/templates' );
		$pageContent = $this->pageContent( $parserOutput, $article->getContext()->getConfig() );
		$params = [
			'html-meta-description' => $this->pageDescription->retrieve( $article->getPage() ),
			'stylesheet' => $this->ampStylesheet->read(),
			'canonical-url' => $article->getTitle()->getCanonicalUrl(),
			'title' => $title,
			'main-page-url' => $this->mainPage->getLinkURL(),
			'logo-url' => ResourceLoaderSkinModule::getAvailableLogos( $article->getContext()
				->getConfig() )['1x'],
			'site-name' => $article->getContext()->getConfig()->get( 'Sitename' ),
			'article-url' => $article->getTitle()->getLocalURL(),
			'article-message' => $article->getContext()->msg( 'nstab-main' ),
			'talk-url' => $article->getTitle()->getTalkPageIfDefined()->getLocalURL(),
			'talk-message' => $article->getContext()->msg( 'talk' ),
			'history-url' => $article->getTitle()->getLocalURL(
				$options = [ 'action' => 'history' ] ),
			'history-message' => $article->getContext()->msg( 'history' ),
			'has-forms' => strpos( $pageContent, '<form' ) !== false,
			'page-content' => $pageContent,
			'edit-url' => $article->getTitle()->getLocalURL( $article->getContext()
				->getSkin()
				->editUrlOptions() ),
			'edit-message' => $article->getContext()->msg( 'edit' ),
			'category-links' => $this->categoryList( $article->getContext(),
				$article->getContext()->getLanguage(), $parserOutput ),
			'copyright' => $article->getContext()->getSkin()->getCopyright(),
			'about-link' => $article->getContext()
				->getSkin()
				->footerLink( 'aboutsite', 'aboutpage' ),
			'disclaimer-link' => $article->getContext()
				->getSkin()
				->footerLink( 'disclaimers', 'disclaimerpage' ),
			'privacy-link' => $article->getContext()
				->getSkin()
				->footerLink( 'privacy', 'privacypage' ),
		];

		return $templates->processTemplate( 'amp', $params );
	}

	private function pageContent( ParserOutput $parserOutput, Config $config ) {
		$text = $parserOutput->getText( [
			'allowTOC' => false,
			'enableSectionEditLinks' => false,
		] );
		$text = str_replace( '<img', '<amp-img', $text );
		if ( $config->get( 'NativeImageLazyLoading' ) ) {
			$text = str_replace( 'loading="lazy"', '', $text );
		}
		// decoding is not supported on amp-img
		$text = str_replace( 'decoding="async"', '', $text );
		// https://amp.dev/documentation/components/amp-form/#target
		$text = str_replace( '<form ', '<form target="_blank" ', $text );
		// mostly for tests: remove NewPP comments with changing timestamps
		$text = preg_replace( '/<!--(.|\s)*?-->(\n)?/', '', $text );
		// TODO: Maybe we find a better way to support iframes:
		// https://amp.dev/documentation/components/amp-iframe/
		$text = preg_replace( '/<iframe.*?<\/iframe>/s', '', $text );

		return $text;
	}

	private function categoryList(
		MessageLocalizer $localizer, \Language $language, ParserOutput $parserOutput
	) {
		$catLinks = [];
		$categories = $parserOutput->getCategoryNames();
		$link = MediaWikiServices::getInstance()->getLinkRenderer();
		foreach ( $categories as $key => $category ) {
			$catLinks[] =
				$link->makeKnownLink( Title::newFromText( $category, NS_CATEGORY ),
					new HtmlArmor( $category ) );
		}

		return $localizer->msg( 'categories' ) . ': ' . $language->commaList( $catLinks );
	}
}
