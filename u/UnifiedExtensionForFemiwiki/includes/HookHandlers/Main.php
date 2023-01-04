<?php

namespace MediaWiki\Extension\UnifiedExtensionForFemiwiki\HookHandlers;

use Config;
use ExtensionRegistry;
use Html;
use RequestContext;
use Skin;
use Title;
use Wikibase\Client\ClientHooks;
use Wikibase\Client\WikibaseClient;

class Main implements
	\MediaWiki\Hook\LinkerMakeExternalLinkHook,
	\MediaWiki\Hook\SidebarBeforeOutputHook,
	\MediaWiki\Hook\SkinAddFooterLinksHook,
	\MediaWiki\Hook\UserMailerTransformContentHook,
	\MediaWiki\Linker\Hook\HtmlPageLinkRendererBeginHook
	{

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->config = $config;
	}

	/**
	 * Add a few links to the footer.
	 *
	 * @inheritDoc
	 */
	public function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerItems ) {
		if ( $key !== 'places' ) {
			return true;
		}

		$termsDestination = Skin::makeInternalOrExternalUrl(
			$skin->msg( 'femiwiki-terms-page' )->inContentLanguage()->text()
		);
		$footerItems['femiwiki-terms'] = Html::element(
			'a',
			[ 'href' => $termsDestination ],
			$skin->msg( 'femiwiki-terms-label' )->text()
		);

		$infringementDestination = Skin::makeInternalOrExternalUrl(
			$skin->msg( 'femiwiki-support-page' )->inContentLanguage()->text()
		);
		$footerItems['femiwiki-support'] = Html::element(
			'a',
			[ 'href' => $infringementDestination ],
			$skin->msg( 'femiwiki-support-label' )->text()
		);
	}

	/**
	 * Treat external links to FemiWiki as internal links.
	 *
	 * @inheritDoc
	 */
	public function onLinkerMakeExternalLink( &$url, &$text, &$link, &$attribs,
		$linkType
	) {
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}
		$canonicalServer = RequestContext::getMain()->getConfig()->get( 'CanonicalServer' );
		if ( strpos( $canonicalServer, parse_url( $url, PHP_URL_HOST ) ) === false ) {
			return true;
		}

		$attribs['class'] = str_replace( 'external', '', $attribs['class'] );
		$attribs['href'] = $url;
		unset( $attribs['target'] );

		$link = Html::rawElement( 'a', $attribs, $text );
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		$this->addWikibaseNewItemLink( $skin, $sidebar );
		$this->sidebarConvertLinks( $sidebar );
	}

	/**
	 * Add a link to create new Wikibase item in toolbox when the title is not linked with any item.
	 *
	 * - Wikibase\Client\Hooks\SidebarHookHandler::onSidebarBeforeOutput (REL1_35)
	 * - Wikibase\Client\ClientHooks::onBaseTemplateToolbox (REL1_35)
	 * - Wikibase\Client\RepoItemLinkGenerator::getNewItemUrl (REL1_35)
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 * @return void
	 */
	private function addWikibaseNewItemLink( $skin, &$sidebar ): void {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ||
			ClientHooks::buildWikidataItemLink( $skin ) ) {
			return;
		}
		$title = $skin->getTitle();
		$repoLinker = WikibaseClient::getRepoLinker();

		$params = [
			'site' => WikibaseClient::getSettings()->getSetting( 'siteGlobalID' ),
			'page' => $title->getPrefixedText()
		];

		$url = $repoLinker->getPageUrl( 'Special:NewItem' );
		$url = $repoLinker->addQueryParams( $url, $params );

		$sidebar['TOOLBOX']['wikibase'] = [
			'text' => $skin->msg( 'wikibase-dataitem' )->text(),
			'href' => $url,
			'id' => 't-wikibase'
		];
	}

	/**
	 * Treat external links to FemiWiki as internal links in the Sidebar.
	 * @param array &$bar
	 * @return void
	 */
	private function sidebarConvertLinks( &$bar ): void {
		$canonicalServer = $this->config->get( 'CanonicalServer' );

		foreach ( $bar as $heading => $content ) {
			foreach ( $content as $key => $item ) {
				if ( !isset( $item['href'] ) ) {
					continue;
				}
				$href = strval( parse_url( $item['href'], PHP_URL_HOST ) );
				if ( $href && strpos( $canonicalServer, $href ) !== false ) {
					unset( $bar[$heading][$key]['rel'] );
					unset( $bar[$heading][$key]['target'] );
				}
			}
		}
	}

	/**
	 * Do not show edit page when user clicks red link
	 * @inheritDoc
	 */
	public function onHtmlPageLinkRendererBegin( $linkRenderer, $target, &$text,
		&$customAttribs, &$query, &$ret
	) {
		// See https://github.com/femiwiki/UnifiedExtensionForFemiwiki/issues/23
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		$title = Title::newFromLinkTarget( $target );
		if ( !$title->isKnown() ) {
			$query['action'] = 'view';
			$query['redlink'] = '1';
		}

		return false;
	}

	/**
	 * Echo(REL1_31)'s content values
	 * @See https://gerrit.wikimedia.org/r/plugins/gitiles/mediawiki/extensions/Echo/+/REL1_31/includes/formatters/EchoHtmlEmailFormatter.php
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private const PRIMARY_LINK_STYLE = 'cursor:pointer; text-align:center; text-decoration:none; padding:.45em 0.6em .45em; color:#FFF; background:#36C; font-family: Arial, Helvetica, sans-serif;font-size: 13px;';
	private const FOOTER_PREFERENCE_LINK_STYLE = 'text-decoration: none; color: #36C;';

	/**
	 * Styles for Femiwiki
	 */
	// phpcs:ignore Generic.Files.LineLength.TooLong
	private const FEMIWIKI_PRIMARY_LINK_STYLE = 'cursor:pointer; text-align:center; text-decoration:none; padding:.45em 0.6em .45em; color:#FFF; background:#aca7e2; font-family: Arial, Helvetica, sans-serif;font-size: 13px;';
	private const FEMIWIKI_FOOTER_PREFERENCE_LINK_STYLE = 'text-decoration: none; color: #5144a3;';

	/**
	 * Modifying HTML mails sent from Echo.
	 * @inheritDoc
	 */
	public function onUserMailerTransformContent( $to, $from, &$body, &$error ) {
		if (
			!$this->config->get( 'UnifiedExtensionForFemiwikiModifyEmailTheme' ) ||
			!ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ||
			!is_array( $body ) ||
			!isset( $body['html'] )
		) {
			return;
		}

		$body['html'] = str_replace(
			[ self::PRIMARY_LINK_STYLE,
				self::FOOTER_PREFERENCE_LINK_STYLE ],
			[ self::FEMIWIKI_PRIMARY_LINK_STYLE,
				self::FEMIWIKI_FOOTER_PREFERENCE_LINK_STYLE ],
			$body['html']
		);
	}
}
