<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
// Need to be able to define ::onUploadForm_initial

namespace MediaWiki\Extension\WikimediaMessages;

use ChangesListSpecialPage;
use Config;
use ConfigException;
use ErrorPageError;
use ExtensionRegistry;
use Html;
use HtmlArmor;
use IContextSource;
use Linker;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Cache\Hook\MessageCache__getHook;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\GuidedTour\GuidedTourLauncher;
use MediaWiki\Extension\WikimediaMessages\LogFormatter\WMUserMergeLogFormatter;
use MediaWiki\Hook\EditPageCopyrightWarningHook;
use MediaWiki\Hook\SkinAddFooterLinksHook;
use MediaWiki\Hook\SkinCopyrightFooterHook;
use MediaWiki\Hook\UploadForm_initialHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderRegisterModulesHook;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWiki\SpecialPage\Hook\ChangesListSpecialPageStructuredFiltersHook;
use MediaWiki\SpecialPage\Hook\SpecialPageBeforeExecuteHook;
use MediaWiki\User\UserOptionsManager;
use MessageLocalizer;
use OOUI\IconWidget;
use OOUI\Tag;
use ORES\Hooks\Helpers as ORESHookHelpers;
use Skin;
use SpecialPage;
use SpecialUpload;
use Title;
use User;
use Wikimedia\IPUtils;

/**
 * Hooks for WikimediaMessages extension
 *
 * @file
 * @ingroup Extensions
 */
class Hooks implements
	ChangesListSpecialPageStructuredFiltersHook,
	EditPageCopyrightWarningHook,
	GetPreferencesHook,
	LocalUserCreatedHook,
	MessageCache__getHook,
	ResourceLoaderRegisterModulesHook,
	SkinAddFooterLinksHook,
	SkinCopyrightFooterHook,
	SpecialPageBeforeExecuteHook,
	UploadForm_initialHook
{

	/** @var ExtensionRegistry */
	private $extensionRegistry;

	/** @var ServiceOptions */
	private $options;

	/** @var UserOptionsManager */
	private $userOptionsManager;

	/**
	 * @param ExtensionRegistry $extensionRegistry
	 * @param ServiceOptions $options
	 * @param UserOptionsManager $userOptionsManager
	 */
	public function __construct(
		ExtensionRegistry $extensionRegistry,
		ServiceOptions $options,
		UserOptionsManager $userOptionsManager
	) {
		$this->extensionRegistry = $extensionRegistry;
		$this->options = $options;
		$this->userOptionsManager = $userOptionsManager;
	}

	/**
	 * @param Config $mainConfig
	 * @param UserOptionsManager $userOptionsManager
	 *
	 * @return Hooks
	 */
	public static function factory(
		Config $mainConfig,
		UserOptionsManager $userOptionsManager ): Hooks {
		return new self(
			ExtensionRegistry::getInstance(),
			new ServiceOptions(
				[
					'DBname',
					'ForceUIMsgAsContentMsg',
					'WikimediaMessagesLicensing',
					'LanguageCode',
				],
				$mainConfig
			),
			$userOptionsManager
		);
	}

	/**
	 * When core requests certain messages, change the key to a Wikimedia version.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/MessageCache::get
	 * @param string &$lcKey message key to check and possibly convert
	 */
	public function onMessageCache__get( &$lcKey ) {
		global $wmgRealm;

		static $keys = [
			'acct_creation_throttle_hit',
			'centralauth-contribs-locked',
			// T216347
			'centralauth-login-error-locked',
			// T86741
			'centralauth-groupname',
			'checkuser-toollinks',
			'createacct-helpusername',
			'createacct-imgcaptcha-help',
			// T139797
			'delete-toobig',
			'feedback-termsofuse',
			'globalblocking-blockedtext-ip',
			'globalblocking-blockedtext-range',
			'globalblocking-blockedtext-xff',
			'globalrenamequeue-email-body-approved',
			'globalrenamequeue-email-body-rejected',
			'interfaceadmin-info',
			// T121639
			'ipb-confirmhideuser',
			'mwoauth-form-privacypolicy-link',
			// T252375
			'mobile-frontend-terms-url',
			'mwoauthserver-invalid-user',
			// T272412
			'prefs-user-downloaddata-help-message',
			'privacypage',
			'sidebar',
			// T140941
			'sitematrix',
			'sitematrix-others',
			'sitematrix-summary',
			'sulrenamewarning-usenew',
			// T248367
			'webauthn-module-description',
			// T248367
			'webauthn-ui-login-prompt',
			// T218160
			'log-action-filter-usermerge',
			'log-action-filter-usermerge-mergeuser',
			'log-action-filter-usermerge-deleteuser',
			'notification-body-oathauth-disable-other',
			'oathauth-notifications-disable-helplink',
			// T307493
			'ipinfo-source-geoip2',
			'ipinfo-infobox-use-terms',
			'ipinfo-preference-use-agreement',
			'similareditors-error-default',
		];

		static $allbutmetawikikeys = [
			// T221526
			'apierror-urlshortener-disabled',
			// T230253
			'apierror-urlshortener-permissiondenied',
			// T230253
			'urlshortener-badaccessgroups',
			// T221526
			'urlshortener-disabled',
		];

		if ( $wmgRealm === 'labs' && $lcKey === 'privacypage' ) {
			$lcKey = 'wikimedia-privacypage-labs';
		} elseif ( $lcKey === 'grouppage-suppress' ) {
			// Temporarily override grouppage-suppress to grouppage-oversight,
			// to avoid breaking links. See T112147.
			$transformedKey = 'grouppage-oversight';
		} elseif ( in_array( $lcKey, $keys, true ) ||
			( $this->options->get( 'DBname' ) !== 'metawiki' && in_array( $lcKey, $allbutmetawikikeys, true ) )
		) {
			$transformedKey = "wikimedia-$lcKey";

			// MessageCache uses ucfirst if ord( key ) is < 128, which is true of all
			// of the above.  Revisit if non-ASCII keys are used.
			$ucKey = ucfirst( $lcKey );

			$cache = MediaWikiServices::getInstance()->getMessageCache();
			if (
				/*
				 * Override order:
				 * 1. If the MediaWiki:$ucKey page exists, use the key unprefixed
				 * (in all languages) with normal fallback order.  Specific
				 * language pages (MediaWiki:$ucKey/xy) are not checked when
				 * deciding which key to use, but are still used if applicable
				 * after the key is decided.
				 *
				 * 2. Otherwise, use the prefixed key with normal fallback order
				 * (including MediaWiki pages if they exist).
				 */
				$cache->getMsgFromNamespace( $ucKey, $this->options->get( 'LanguageCode' ) ) === false
			) {
				$lcKey = $transformedKey;
			}
		}
		// Set license for mobile editor and talk overlays.
		if ( $lcKey === 'mobile-frontend-license-links' ) {
			$licensing = $this->options->get( 'WikimediaMessagesLicensing' );
			switch ( $licensing ) {
				case 'mediawiki':
					$lcKey = 'mediawiki.org-mobile-license-links';
					break;
				case 'wikidata':
					$lcKey = 'wikidata-mobile-license-links';
					break;
				case 'wikifunctions':
					$lcKey = 'wikifunctions-mobile-license-links';
					break;
				case 'commons':
				case 'standard':
					$lcKey = 'wikimedia-mobile-license-links';
					break;
				case 'wikinews':
					// This is necessary because MobileFrontend doesn't always display the license based on
					// config settings (T296791)
					$lcKey = 'wikinews-mobile-license-links';
					$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wikimedia-messages' );
					if ( $config->get( 'RightsText' ) === 'Creative Commons Attribution 3.0' ) {
						$lcKey = 'wikinews-mobile-license-links-ccby30';
					}
					break;
				default:
					throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
			}
		}

		if ( $lcKey === 'mainpage-title-loggedin' && ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$services = MediaWikiServices::getInstance();
			$context = $services->getService( 'MobileFrontend.Context' );
			if ( $context->shouldDisplayMobileView() ) {
				$lcKey = 'wikimedia-mobile-mainpage-title-loggedin';
			}
		}
	}

	/**
	 * @param Config $config
	 * @return string
	 */
	private function shortenLicenseLink( Config $config ): string {
		$services = MediaWikiServices::getInstance();
		$rightsText = $config->get( 'RightsText' );
		$rightsPage = $config->get( 'RightsPage' );
		$rightsUrl = $config->get( 'RightsUrl' );
		$commonLicenses = [
			'Creative Commons Attribution-Share Alike 3.0' => 'CC BY-SA 3.0',
			'Creative Commons Attribution 3.0' => 'CC BY 3.0',
			'Creative Commons Attribution 2.5' => 'CC BY 2.5',
		];
		if ( isset( $commonLicenses[$rightsText] ) ) {
			$rightsText = $commonLicenses[$rightsText];
		}
		if ( $rightsPage ) {
			$title = Title::newFromText( $rightsPage );
			$linkRenderer = $services->getLinkRenderer();
			$link = $linkRenderer->makeKnownLink( $title, new HtmlArmor( $rightsText ), [] );
		} elseif ( $rightsUrl ) {
			$link = Linker::makeExternalLink( $rightsUrl, $rightsText, true, '', [] );
		} else {
			$link = $rightsText;
		}
		return $link;
	}

	/**
	 * Override for copyright message in skin footer.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinCopyrightFooter
	 *
	 * @param Title $title
	 * @param string $type
	 * @param string &$msg
	 * @param string &$link
	 */
	public function onSkinCopyrightFooter( $title, $type, &$msg, &$link ) {
		$services = MediaWikiServices::getInstance();
		$config = $services->getConfigFactory()->makeConfig( 'wikimedia-messages' );
		if ( $type === 'history' ) {
			return;
		}

		$licensing = $this->options->get( 'WikimediaMessagesLicensing' );
		if ( ExtensionRegistry::getInstance()->isLoaded( 'MobileFrontend' ) ) {
			$context = $services->getService( 'MobileFrontend.Context' );
			if ( $context->shouldDisplayMobileView() ) {
				$msg = 'mobile-frontend-copyright';
				$link = $this->shortenLicenseLink( $config );
				self::skinCopyrightFooterMobile( $msg );
				return;
			}
		}

		switch ( $licensing ) {
			case 'wikidata':
				// Wikidata is licensed under CC-BY-SA 3.0 only, no GFDL. (Also, the data is under CC0.)
				$msg = 'wikidata-copyright';
				break;
			case 'mediawiki':
				// MediaWiki.org has a special licence for the Help: namespace.
				$msg = 'mediawiki.org-copyright';
				break;
			case 'commons':
				// Commons has a special licence for the structured data.
				// TODO: Should we also mention the special Data: namespace?
				$msg = 'wikimedia-commons-copyright';
				break;
			case 'standard':
				// Almost all Wikimedia wikis using CC-BY-SA 3.0 are also dual-licensed under GFDL.
				$msg = 'wikimedia-copyright';
				break;
			case 'wikinews':
				// Use the default MediaWiki message. (It's overridden locally on most Wikinewses.)
				break;
			case 'wikifunctions':
				// Wikifunctions like Wikidata is licensed under CC-BY-SA 3.0 only, no GFDL. The data is
				// under CC0. The code is under Apache 2.0.
				$msg = 'wikifunctions-site-footer-copyright';
				break;
			default:
				throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
		}
	}

	/**
	 * Override for copyright message on edit page.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/EditPageCopyrightWarning
	 *
	 * @param Title $title
	 * @param array &$msg
	 */
	public function onEditPageCopyrightWarning( $title, &$msg ) {
		$licensing = $this->options->get( 'WikimediaMessagesLicensing' );

		switch ( $licensing ) {
			case 'wikidata':
			case 'wikifunctions':
				// Wikidata and Wikifunctions are licensed under CC-BY-SA 3.0 only, no GFDL.
				$msg = [ 'wikimedia-copyrightwarning-ccbysa30only' ];
				break;
			case 'mediawiki':
				// MediaWiki.org pages are dual-licensed under CC-BY-SA 3.0 and GFDL, except for the Help: namespace.
				$msg = [ 'mediawiki.org-copyrightwarning' ];
				break;
			case 'commons':
			case 'standard':
				// Almost all Wikimedia wikis using CC-BY-SA 3.0 are also dual-licensed under GFDL.
				$msg = [ 'wikimedia-copyrightwarning' ];
				break;
			case 'wikinews':
				// Use the default MediaWiki message. (It's overridden locally on most Wikinewses.)
				break;
			default:
				throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
		}
	}

	/**
	 * Override for copyright message (MobileFrontend extension).
	 *
	 * @param string &$msg
	 */
	private static function skinCopyrightFooterMobile( &$msg ) {
		global $wgWikimediaMessagesLicensing;
		$licensing = $wgWikimediaMessagesLicensing;

		switch ( $licensing ) {
			case 'wikidata':
				// Wikidata needs its own special message. See T112088
				$msg = 'wikidata-copyright';
				break;
			case 'mediawiki':
				// MediaWiki.org also needs its own special message, to mention the Help: namespace.
				$msg = 'mediawiki.org-copyright';
				break;
			case 'commons':
				// Commons also needs its own special message, to mention the structured data.
				$msg = 'wikimedia-commons-copyright';
				break;
			case 'standard':
			case 'wikinews':
				// Use the default MobileFrontend message.
				break;
			case 'wikifunctions':
				// Wikifunctions is licensed under CC-BY-SA 3.0 only, no GFDL. The data is under CC0.
				// The code is under Apache 2.0.
				$msg = 'wikifunctions-site-footer-copyright';
				break;
			default:
				throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
		}
	}

	/**
	 * Override for copyright messages (DiscussionTools extension).
	 *
	 * @param array &$messages
	 * @param MessageLocalizer $context
	 * @param Config $config
	 */
	public static function onDiscussionToolsTermsOfUseMessages(
		array &$messages, MessageLocalizer $context, Config $config
	) {
		$licensing = $config->get( 'WikimediaMessagesLicensing' );

		switch ( $licensing ) {
			case 'wikidata':
			case 'wikifunctions':
				// Do not use $wgRightsText or $wgRightsPage, as they are customized for the API (T112606).
				$rightsUrl = $config->get( 'RightsUrl' );
				$rightsText = 'Creative Commons Attribution-Share Alike 3.0';
				$links = [ "[$rightsUrl $rightsText]", 1 ];
				break;
			case 'wikinews':
				// Add the license name to the terms-of-use message
				$rightsUrl = $config->get( 'RightsUrl' );
				$rightsText = $config->get( 'RightsText' );
				$links = [ "[$rightsUrl $rightsText]", 1 ];
				break;
			case 'commons':
			case 'mediawiki':
			case 'standard':
				// Add the license names to the terms-of-use message - dual-licensed wikis
				$links = [ $context->msg( 'wikimedia-license-links' )->plain(), 2 ];
				break;
			default:
				throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
		}

		$messages['reply'] = array_merge( [ 'wikimedia-discussiontools-replywidget-terms-click',
			$context->msg( 'discussiontools-replywidget-reply' )->text() ], $links );
		$messages['newtopic'] = array_merge( [ 'wikimedia-discussiontools-replywidget-terms-click',
			$context->msg( 'discussiontools-replywidget-newtopic' )->text() ], $links );
	}

	/**
	 * Override for copyright messages (Flow extension).
	 *
	 * @param array &$messages
	 * @param MessageLocalizer $context
	 * @param Config $config
	 */
	public static function onFlowTermsOfUseMessages(
		array &$messages, MessageLocalizer $context, Config $config
	) {
		$licensing = $config->get( 'WikimediaMessagesLicensing' );

		switch ( $licensing ) {
			case 'wikidata':
			case 'wikifunctions':
				// Do not use $wgRightsText or $wgRightsPage, as they are customized for the API (T112606).
				$rightsUrl = $config->get( 'RightsUrl' );
				$rightsText = 'Creative Commons Attribution-Share Alike 3.0';
				$links = [ "[$rightsUrl $rightsText]", 1 ];
				break;
			case 'wikinews':
				// Add the license name to the terms-of-use message
				$rightsUrl = $config->get( 'RightsUrl' );
				$rightsText = $config->get( 'RightsText' );
				$links = [ "[$rightsUrl $rightsText]", 1 ];
				break;
			case 'commons':
			case 'mediawiki':
			case 'standard':
				// Add the license names to the terms-of-use message - dual-licensed wikis
				$links = [ $context->msg( 'wikimedia-license-links' )->plain(), 2 ];
				break;
			default:
				throw new ConfigException( "Unknown value for WikimediaMessagesLicensing: '$licensing'" );
		}

		$messages['edit'] = array_merge( [ 'wikimedia-flow-terms-of-use-edit' ], $links );
		$messages['lock-topic'] = array_merge( [ 'wikimedia-flow-terms-of-use-lock-topic' ], $links );
		$messages['new-topic'] = array_merge( [ 'wikimedia-flow-terms-of-use-new-topic' ], $links );
		$messages['reply'] = array_merge( [ 'wikimedia-flow-terms-of-use-reply' ], $links );
		$messages['summarize'] = array_merge( [ 'wikimedia-flow-terms-of-use-summarize' ], $links );
		$messages['unlock-topic'] = array_merge( [ 'wikimedia-flow-terms-of-use-unlock-topic' ], $links );
	}

	/**
	 * Add links to the footer of every page:
	 *   - "Developers" (T35464),
	 *   - "Statistics" (T235803), and
	 *   - "Cookie statement" (T124366)
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinAddFooterLinks
	 *
	 * @param Skin $skin
	 * @param string $key
	 * @param array &$footerLinks
	 */
	public function onSkinAddFooterLinks( Skin $skin, string $key, array &$footerLinks ) {
		if ( $key !== 'places' ) {
			return;
		}
		$devDestination = Skin::makeInternalOrExternalUrl(
			$skin->msg( 'wikimedia-developers-url' )->inContentLanguage()->text()
		);
		$footerLinks['developers'] = Html::element(
			'a',
			[ 'href' => $devDestination ],
			$skin->msg( 'wikimedia-developers' )->text()
		);

		$statsDestination = Skin::makeInternalOrExternalUrl(
			$skin->msg(
				'wikimedia-statslink-url', $skin->getConfig()->get( 'ServerName' )
			)->inContentLanguage()->text()
		);
		$footerLinks['statslink'] = Html::element(
			'a',
			[ 'href' => $statsDestination ],
			$skin->msg( 'wikimedia-statslink' )->text()
		);

		$cookieDestination = Skin::makeInternalOrExternalUrl(
			$skin->msg( 'wikimedia-cookiestatement-page' )->inContentLanguage()->text()
		);
		$footerLinks['cookiestatement'] = Html::element(
			'a',
			[ 'href' => $cookieDestination ],
			$skin->msg( 'wikimedia-cookiestatement' )->text()
		);
	}

	/**
	 * Change which message is shown when TorBlock triggers (TorBlock extension)
	 *
	 * @todo FIXME: Should have a specific message for WMF projects (T44231)
	 *
	 * @todo once TorBlock updates to use hook interfaces that can be implemented,
	 * convert to using this class as a hook handler and make non-static with DI
	 *
	 * @param string &$msg The message key
	 */
	public static function onTorBlockBlockedMsg( &$msg ) {
		$msg = 'wikimedia-torblock-blocked';
	}

	/**
	 * Change which message is shown for global IP blocks (GlobalBlocking extension)
	 *
	 * @todo once GlobalBlocking updates to use hook interfaces that can be implemented,
	 * convert to using this class as a hook handler and make non-static with DI
	 *
	 * @param string &$msg The message key
	 */
	public static function onGlobalBlockingBlockedIpMsg( &$msg ) {
		$msg = 'wikimedia-globalblocking-ipblocked';
	}

	/**
	 * Change which message is shown for global IP range blocks (GlobalBlocking extension)
	 *
	 * @todo once GlobalBlocking updates to use hook interfaces that can be implemented,
	 * convert to using this class as a hook handler and make non-static with DI
	 *
	 * @param string &$msg The message key
	 */
	public static function onGlobalBlockingBlockedIpRangeMsg( &$msg ) {
		$msg = 'wikimedia-globalblocking-ipblocked-range';
	}

	/**
	 * Change which message is shown for global XFF IP blocks
	 * and rangeblocks (GlobalBlocking extension)
	 *
	 * @todo once GlobalBlocking updates to use hook interfaces that can be implemented,
	 * convert to using this class as a hook handler and make non-static with DI
	 *
	 * @param string &$msg The message key
	 */
	public static function onGlobalBlockingBlockedIpXffMsg( &$msg ) {
		$msg = 'wikimedia-globalblocking-ipblocked-xff';
	}

	/**
	 * Require the creation of MediaWiki:Licenses to enable uploading.
	 * This should prevent a lot of uploading without licenses on small wikis;
	 * some or many of the small WMF wikis do not have any license options,
	 * which is really needed for our copyright policy.
	 *
	 * Do not require it when licenses is in $wgForceUIMsgAsContentMsg,
	 * to prevent checking each subpage of MediaWiki:Licenses.
	 *
	 * @param SpecialUpload $upload
	 * @throws ErrorPageError
	 */
	public function onUploadForm_initial( $upload ) {
		$forceUIMsgAsContentMsg = $this->options->get( 'ForceUIMsgAsContentMsg' );

		// TODO inject something to replace use of wfMessage
		if ( !in_array( 'licenses', $forceUIMsgAsContentMsg )
			&& wfMessage( 'licenses' )->inContentLanguage()->isDisabled()
		) {
			throw new ErrorPageError( 'uploaddisabled', 'wikimedia-upload-nolicenses' );
		}
	}

	/**
	 * Override `$wgGrammarForms` for Wikimedia sites as a more convenient form
	 * than setting in InitialiseSettings.php. Initial version was compiled from
	 * then-current Wikimedia configuration and contents of LanguageXx.php.
	 */
	public static function onRegistration() {
		global $wgGrammarForms;

		$wgGrammarForms['ang'] = [
			// accusative
			'wrēgendlīc' => [
				'Wikipǣdia' => 'Wikipǣdie',
				'Wikiwordbōc' => 'Wikiwordbōc',
			],
			// genitive
			'geāgniendlīc' => [
				'Wikipǣdia' => 'Wikipǣdie',
				'Wikiwordbōc' => 'Wikiwordbēc',
			],
			// dative
			'forgifendlīc' => [
				'Wikipǣdia' => 'Wikipǣdie',
				'Wikiwordbōc' => 'Wikiwordbēc',
			],
			// instrumental
			'tōllīc' => [
				'Wikipǣdia' => 'Wikipǣdie',
				'Wikiwordbōc' => 'Wikiwordbēc',
			],
		];

		$wgGrammarForms['be'] = [
			// genitive
			'родны' => [
				'ВікіВіды'    => 'ВікіВідаў',
				'Вікідадзеныя' => 'Вікідадзеных',
				'ВікіКнігі'   => 'ВікіКніг',
				'Вікікрыніцы' => 'Вікікрыніц',
				'ВікіНавіны'  => 'ВікіНавін',
				'Вікіслоўнік' => 'Вікіслоўніка',
				'Вікіпедыя'   => 'Вікіпедыі',
			],
			// accusative
			'вінавальны' => [
				'Вікіпедыя'   => 'Вікіпедыю',
			],
			// prepositional
			'месны' => [
				'ВікіВіды'    => 'ВікіВідах',
				'Вікідадзеныя' => 'Вікідадзеных',
				'ВікіКнігі'   => 'ВікіКнігах',
				'Вікікрыніцы' => 'Вікікрыніцах',
				'ВікіНавіны'  => 'ВікіНавінах',
				'Вікіслоўнік' => 'Вікіслоўніку',
				'Вікіпедыя'   => 'Вікіпедыі',
			],
		];

		$wgGrammarForms['be-tarask'] = [
			// genitive
			'родны' => [
				'Віківіды'    => 'Віківідаў',
				'Вікізьвесткі' => 'Вікізьвестак',
				'Вікікнігі'   => 'Вікікніг',
				'Вікікрыніцы' => 'Вікікрыніц',
				'Вікімэдыя'   => 'Вікімэдыі',
				'Вікінавіны'  => 'Вікінавін',
				'Вікіпэдыя'   => 'Вікіпэдыі',
				'Вікіслоўнік' => 'Вікіслоўніка',
				'Вікісховішча' => 'Вікісховішча',
				'Фундацыя «Вікімэдыя»' => 'Фундацыі «Вікімэдыя»',
			],
			// dative
			'давальны' => [
				'Віківіды'    => 'Віківідам',
				'Вікізьвесткі' => 'Вікізьвесткам',
				'Вікікнігі'   => 'Вікікнігам',
				'Вікікрыніцы' => 'Вікікрыніцам',
				'Вікімэдыя'   => 'Вікімэдыі',
				'Вікінавіны'  => 'Вікінавінам',
				'Вікіпэдыя'   => 'Вікіпэдыі',
				'Вікіслоўнік' => 'Вікіслоўніку',
				'Вікісховішча' => 'Вікісховішчу',
				'Фундацыя «Вікімэдыя»' => 'Фундацыі «Вікімэдыя»',
			],
			// accusative
			'вінавальны' => [
				'Віківіды'    => 'Віківіды',
				'Вікізьвесткі' => 'Вікізьвесті',
				'Вікікнігі'   => 'Вікікнігі',
				'Вікікрыніцы' => 'Вікікрыніцы',
				'Вікімэдыя'   => 'Вікімэдыю',
				'Вікінавіны'  => 'Вікінавіны',
				'Вікіпэдыя'   => 'Вікіпэдыю',
				'Вікіслоўнік' => 'Вікіслоўнік',
				'Вікісховішча' => 'Вікісховішча',
				'Фундацыя «Вікімэдыя»' => 'Фундацыю «Вікімэдыя»',
			],
			// instrumental
			'творны' => [
				'Віківіды'    => 'Віківідамі',
				'Вікізьвесткі' => 'Вікізьвесткамі',
				'Вікікнігі'   => 'Вікікнігамі',
				'Вікікрыніцы' => 'Вікікрыніцамі',
				'Вікімэдыя'   => 'Вікімэдыяй',
				'Вікінавіны'  => 'Вікінавінамі',
				'Вікіпэдыя'   => 'Вікіпэдыяй',
				'Вікіслоўнік' => 'Вікіслоўнікам',
				'Вікісховішча' => 'Вікісховішчам',
				'Фундацыя «Вікімэдыя»' => 'Фундацыяй «Вікімэдыя»',
			],
			// prepositional
			'месны' => [
				'Віківіды'    => 'Віківідах',
				'Вікізьвесткі' => 'Вікізьвестках',
				'Вікікнігі'   => 'Вікікнігах',
				'Вікікрыніцы' => 'Вікікрыніцах',
				'Вікімэдыя'   => 'Вікімэдыі',
				'Вікінавіны'  => 'Вікінавінах',
				'Вікіпэдыя'   => 'Вікіпэдыі',
				'Вікіслоўнік' => 'Вікіслоўніку',
				'Вікісховішча' => 'Вікісховішчы',
				'Фундацыя «Вікімэдыя»' => 'Фундацыі «Вікімэдыя»',
			],
		];

		$wgGrammarForms['bs'] = [
			// genitive
			'genitiv' => [
				'Vikirječnik' => 'Wikirječnika',
				'Wikicitati'  => 'Wikicitata',
				'Wikiizvor'   => 'Wikiizvora',
				'Wikiknjige'  => 'Wikiknjiga',
				'Wikipedia'   => 'Wikipedije',
				'Wikipodaci'  => 'Wikipodataka',
				'Wikimedia Commons' => 'Wikimedia Commonsa',
			],
			// dative
			'dativ' => [
				'Vikirječnik' => 'Wikirječniku',
				'Wikicitati'  => 'Wikicitatima',
				'Wikiizvor'   => 'Wikiizvoru',
				'Wikiknjige'  => 'Wikiknjigama',
				'Wikipedia'   => 'Wikipediji',
				'Wikipodaci'  => 'Wikipodacima',
				'Wikivijesti' => 'Wikivijestima',
				'Wikimedia Commons' => 'Wikimedia Commonsu',
			],
			// accusative
			'akuzativ' => [
				'Vikirječnik' => 'Wikirječnik',
				'Wikicitati'  => 'Wikicitate',
				'Wikiizvor'   => 'Wikiizvora',
				'Wikipedia'   => 'Wikipediju',
				'Wikipodaci'  => 'Wikipodatke',
			],
			// vocative
			'vokativ' => [
				'Vikirječnik' => 'Wikirječniče',
				'Wikiizvor'   => 'Wikizivoru',
				'Wikipedia'   => 'Wikipedijo',
				'Wikimedia Commons' => 'Wikimedia Commonse',
			],
			// instrumental
			'instrumental' => [
				'Vikirječnik' => 'Wikirječnikom',
				'Wikicitati'  => 'Wikicitatima',
				'Wikiizvor'   => 'Wikiizvorom',
				'Wikiknjige'  => 'Wikiknjigama',
				// T130141
				'Wikipedia'   => 'Wikipedijom',
				'Wikipodaci'  => 'Wikipodacima',
				'Wikivijesti' => 'Wikivijestima',
				'Wikimedia Commons' => 'Wikimedia Commonsom',
			],
			// locative
			'lokativ' => [
				'Vikirječnik' => 'Wikirječniku',
				'Wikicitati'  => 'Wikicitatima',
				'Wikiizvor'   => 'Wikiizvoru',
				'Wikiknjige'  => 'Wikiknjigama',
				'Wikipedia'   => 'Wikipediji',
				'Wikipodaci'  => 'Wikipodacima',
				'Wikivijesti' => 'Wikivijestima',
				'Wikimedia Commons' => 'Wikimedia Commonsu',
			],
		];

		$wgGrammarForms['cs'] = [
			// only forms different than default/given
			'1sg' => [
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzita',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovník',
			],
			'2sg' => [
				'uživatel'    => 'uživatele',
				'Wikibooks'   => 'Wikiknih',
				'Wikidata'    => 'Wikidat',
				'Wikinews'    => 'Wikizpráv',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitátů',
				'Wikisource'  => 'Wikizdrojů',
				'Wikispecies' => 'Wikidruhů',
				'Wikiversity' => 'Wikiverzity',
				'Wikivoyage'  => 'Wikicest',
				'Wiktionary'  => 'Wikislovníku',
				'Wikicesty'   => 'Wikicest',
				'Wikicitáty'  => 'Wikicitátů',
				'Wikidruhy'   => 'Wikidruhů',
				'Wikiknihy'   => 'Wikiknih',
				'Wikislovník' => 'Wikislovníku',
				'Wikiverzita' => 'Wikiverzity',
				'Wikizdroje'  => 'Wikizdrojů',
				'Wikizprávy'  => 'Wikizpráv',
			],
			'3sg' => [
				'uživatel'    => 'uživateli',
				'Wikibooks'   => 'Wikiknihám',
				'Wikidata'    => 'Wikidatům',
				'Wikinews'    => 'Wikizprávám',
				'Wikipedia'   => 'Wikipedii',
				'Wikiquote'   => 'Wikicitátům',
				'Wikisource'  => 'Wikizdrojům',
				'Wikispecies' => 'Wikidruhům',
				'Wikiversity' => 'Wikiverzitě',
				'Wikivoyage'  => 'Wikicestám',
				'Wiktionary'  => 'Wikislovníku',
				'Wikicesty'   => 'Wikicestám',
				'Wikicitáty'  => 'Wikicitátům',
				'Wikidruhy'   => 'Wikidruhům',
				'Wikiknihy'   => 'Wikiknihám',
				'Wikipedie'   => 'Wikipedii',
				'Wikislovník' => 'Wikislovníku',
				'Wikiverzita' => 'Wikiverzitě',
				'Wikizdroje'  => 'Wikizdrojům',
				'Wikizprávy'  => 'Wikizprávám',
			],
			'4sg' => [
				'uživatel'    => 'uživatele',
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedii',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzitu',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovník',
				'Wikipedie'   => 'Wikipedii',
				'Wikiverzita' => 'Wikiverzitu',
			],
			'5sg' => [
				'uživatel'    => 'uživateli',
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzito',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovníku',
				'Wikislovník' => 'Wikislovníku',
				'Wikiverzita' => 'Wikiverzito',
			],
			'6sg' => [
				'uživatel'    => 'uživateli',
				'Wikibooks'   => 'Wikiknihách',
				'Wikidata'    => 'Wikidatech',
				'Wikinews'    => 'Wikizprávách',
				'Wikipedia'   => 'Wikipedii',
				'Wikiquote'   => 'Wikicitátech',
				'Wikisource'  => 'Wikizdrojích',
				'Wikispecies' => 'Wikidruzích',
				'Wikiversity' => 'Wikiverzitě',
				'Wikivoyage'  => 'Wikicestách',
				'Wiktionary'  => 'Wikislovníku',
				'Wikicesty'   => 'Wikicestách',
				'Wikicitáty'  => 'Wikicitátech',
				'Wikidruhy'   => 'Wikidruzích',
				'Wikiknihy'   => 'Wikiknihách',
				'Wikipedie'   => 'Wikipedii',
				'Wikislovník' => 'Wikislovníku',
				'Wikiverzita' => 'Wikiverzitě',
				'Wikizdroje'  => 'Wikizdrojích',
				'Wikizprávy'  => 'Wikizprávách',
			],
			'7sg' => [
				'uživatel'    => 'uživatelem',
				'Wikibooks'   => 'Wikiknihami',
				'Wikidata'    => 'Wikidaty',
				'Wikinews'    => 'Wikizprávami',
				'Wikipedia'   => 'Wikipedií',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroji',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzitou',
				'Wikivoyage'  => 'Wikicestami',
				'Wiktionary'  => 'Wikislovníkem',
				'Wikicesty'   => 'Wikicestami',
				'Wikiknihy'   => 'Wikiknihami',
				'Wikipedie'   => 'Wikipedií',
				'Wikislovník' => 'Wikislovníkem',
				'Wikiverzita' => 'Wikiverzitou',
				'Wikizdroje'  => 'Wikizdroji',
				'Wikizprávy'  => 'Wikizprávami',
			],
			'1pl' => [
				'uživatel'    => 'uživatelé',
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzity',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovníky',
				'Wikislovník' => 'Wikislovníky',
				'Wikiverzita' => 'Wikiverzity',
			],
			'2pl' => [
				'uživatel'    => 'uživatelů',
				'Wikibooks'   => 'Wikiknih',
				'Wikidata'    => 'Wikidat',
				'Wikinews'    => 'Wikizpráv',
				'Wikipedia'   => 'Wikipedií',
				'Wikiquote'   => 'Wikicitátů',
				'Wikisource'  => 'Wikizdrojů',
				'Wikispecies' => 'Wikidruhů',
				'Wikiversity' => 'Wikiverzit',
				'Wikivoyage'  => 'Wikicest',
				'Wiktionary'  => 'Wikislovníků',
				'Wikicesty'   => 'Wikicest',
				'Wikicitáty'  => 'Wikicitátů',
				'Wikidruhy'   => 'Wikidruhů',
				'Wikiknihy'   => 'Wikiknih',
				'Wikipedie'   => 'Wikipedií',
				'Wikislovník' => 'Wikislovníků',
				'Wikiverzita' => 'Wikiverzit',
				'Wikizdroje'  => 'Wikizdrojů',
				'Wikizprávy'  => 'Wikizpráv',
			],
			'3pl' => [
				'uživatel'    => 'uživatelům',
				'Wikibooks'   => 'Wikiknihám',
				'Wikidata'    => 'Wikidatům',
				'Wikinews'    => 'Wikizprávám',
				'Wikipedia'   => 'Wikipediím',
				'Wikiquote'   => 'Wikicitátům',
				'Wikisource'  => 'Wikizdrojům',
				'Wikispecies' => 'Wikidruhům',
				'Wikiversity' => 'Wikiverzitám',
				'Wikivoyage'  => 'Wikicestám',
				'Wiktionary'  => 'Wikislovníkům',
				'Wikicesty'   => 'Wikicestám',
				'Wikicitáty'  => 'Wikicitátům',
				'Wikidruhy'   => 'Wikidruhům',
				'Wikiknihy'   => 'Wikiknihám',
				'Wikipedie'   => 'Wikipediím',
				'Wikislovník' => 'Wikislovníkům',
				'Wikiverzita' => 'Wikiverzitám',
				'Wikizdroje'  => 'Wikizdrojům',
				'Wikizprávy'  => 'Wikizprávám',
			],
			'4pl' => [
				'uživatel'    => 'uživatele',
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzity',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovníky',
				'Wikislovník' => 'Wikislovníky',
				'Wikiverzita' => 'Wikiverzity',
			],
			'5pl' => [
				'uživatel'    => 'uživatelé',
				'Wikibooks'   => 'Wikiknihy',
				'Wikinews'    => 'Wikizprávy',
				'Wikipedia'   => 'Wikipedie',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroje',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzity',
				'Wikivoyage'  => 'Wikicesty',
				'Wiktionary'  => 'Wikislovníky',
				'Wikislovník' => 'Wikislovníky',
				'Wikiverzita' => 'Wikiverzity',
			],
			'6pl' => [
				'uživatel'    => 'uživatelích',
				'Wikibooks'   => 'Wikiknihách',
				'Wikidata'    => 'Wikidatech',
				'Wikinews'    => 'Wikizprávách',
				'Wikipedia'   => 'Wikipediích',
				'Wikiquote'   => 'Wikicitátech',
				'Wikisource'  => 'Wikizdrojích',
				'Wikispecies' => 'Wikidruzích',
				'Wikiversity' => 'Wikiverzitách',
				'Wikivoyage'  => 'Wikicestách',
				'Wiktionary'  => 'Wikislovnících',
				'Wikicesty'   => 'Wikicestách',
				'Wikicitáty'  => 'Wikicitátech',
				'Wikidruhy'   => 'Wikidruzích',
				'Wikiknihy'   => 'Wikiknihách',
				'Wikipedie'   => 'Wikipediích',
				'Wikislovník' => 'Wikislovnících',
				'Wikiverzita' => 'Wikiverzitách',
				'Wikizdroje'  => 'Wikizdrojích',
				'Wikizprávy'  => 'Wikizprávách',
			],
			'7pl' => [
				'uživatel'    => 'uživateli',
				'Wikibooks'   => 'Wikiknihami',
				'Wikidata'    => 'Wikidaty',
				'Wikinews'    => 'Wikizprávami',
				'Wikipedia'   => 'Wikipediemi',
				'Wikiquote'   => 'Wikicitáty',
				'Wikisource'  => 'Wikizdroji',
				'Wikispecies' => 'Wikidruhy',
				'Wikiversity' => 'Wikiverzitami',
				'Wikivoyage'  => 'Wikicestami',
				'Wiktionary'  => 'Wikislovníky',
				'Wikicesty'   => 'Wikicestami',
				'Wikiknihy'   => 'Wikiknihami',
				'Wikipedie'   => 'Wikipediemi',
				'Wikislovník' => 'Wikislovníky',
				'Wikiverzita' => 'Wikiverzitami',
				'Wikizdroje'  => 'Wikizdroji',
				'Wikizprávy'  => 'Wikizprávami',
			],
		];

		$wgGrammarForms['dsb'] = [
			// genitive
			'genitiw' => [
				'Wikipedija'  => 'Wikipedije',
				'Wikiknihi'   => 'Wikiknih',
				'Wikinowiny'  => 'Wikinowin',
				'Wikižórło'   => 'Wikižórła',
				'Wikicitaty'  => 'Wikicitatow',
				'Wikisłownik' => 'Wikisłownika',
			],
			// dative
			'datiw' => [
				'Wikipedija'  => 'Wikipediji',
				'Wikiknihi'   => 'Wikikniham',
				'Wikinowiny'  => 'Wikinowinam',
				'Wikižórło'   => 'Wikižórłu',
				'Wikicitaty'  => 'Wikicitatam',
				'Wikisłownik' => 'Wikisłownikej',
			],
			// accusative
			'akuzativ' => [
				'Wikipedija'  => 'Wikipediju',
				'Wikiknihi'   => 'Wikiknknihi',
			],
			// instrumental
			'instrumental' => [
				'Wikipedija'  => 'Wikipediju',
				'Wikiknihi'   => 'Wikiknihami',
				'Wikinowiny'  => 'Wikinowinami',
				'Wikižórło'   => 'Wikižórłom',
				'Wikicitaty'  => 'Wikicitatami',
				'Wikisłownik' => 'Wikisłownikom',
			],
			// locative
			'lokatiw' => [
				'Wikipedija'  => 'Wikipediji',
				'Wikiknihi'   => 'Wikiknihach',
				'Wikinowiny'  => 'Wikinowinach',
				'Wikižórło'   => 'Wikižórłu',
				'Wikicitaty'  => 'Wikicitatach',
				'Wikisłownik' => 'Wikisłowniku',
			],
		];

		$wgGrammarForms['et'] = [
			'genitive' => [
				'Vikisõnastik'  => 'Vikisõnastiku',
				'Vikitekstid'   => 'Vikitekstide',
				'Vikitsitaadid' => 'Vikitsitaatide',
				'Vikiõpikud'    => 'Vikiõpikute',
			],
			'partitive' => [
				'Vikipeedia'    => 'Vikipeediat',
				'Vikisõnastik'  => 'Vikisõnastikku',
				'Vikitekstid'   => 'Vikitekste',
				'Vikitsitaadid' => 'Vikitsitaate',
				'Vikiõpikud'    => 'Vikiõpikuid',
			],
			'illative' => [
				'Vikipeedia'    => 'Vikipeediasse',
				'Vikisõnastik'  => 'Vikisõnastikku',
				'Vikitekstid'   => 'Vikitekstidesse',
				'Vikitsitaadid' => 'Vikitsitaatidesse',
				'Vikiõpikud'    => 'Vikiõpikutesse',
			],
			'inessive' => [
				'Vikipeedia'    => 'Vikipeedias',
				'Vikisõnastik'  => 'Vikisõnastikus',
				'Vikitekstid'   => 'Vikitekstides',
				'Vikitsitaadid' => 'Vikitsitaatides',
				'Vikiõpikud'    => 'Vikiõpikutes',
			],
			'elative' => [
				'Vikipeedia'    => 'Vikipeediast',
				'Vikisõnastik'  => 'Vikisõnastikust',
				'Vikitekstid'   => 'Vikitekstidest',
				'Vikitsitaadid' => 'Vikitsitaatidest',
				'Vikiõpikud'    => 'Vikiõpikutest',
			],
		];

		$wgGrammarForms['fi'] = [
			'genitive' => [
				'Wikiuutiset' => 'Wikiuutisten',
				'Wikisitaatit' => 'Wikisitaattien',
				'Wikimedia Suomi' => 'Wikimedia Suomen',
				'Wikimatkat' => 'Wikimatkojen',
			],
			'partitive' => [
				'Wikiuutiset' => 'Wikiuutisia',
				'Wikisitaatit' => 'Wikisitaatteja',
				'Wikimedia Suomi' => 'Wikimedia Suomea',
				'Wikimatkat' => 'Wikimatkoja',
			],
			'elative' => [
				'Wikiuutiset' => 'Wikiuutisista',
				'Wikisitaatit' => 'Wikisitaateista',
				'Wikimedia Suomi' => 'Wikimedia Suomesta',
				'Wikimatkat' => 'Wikimatkoista',
			],
			'inessive' => [
				'Wikiuutiset' => 'Wikiuutisissa',
				'Wikisitaatit' => 'Wikisitaateissa',
				'Wikimedia Suomi' => 'Wikimedia Suomessa',
				'Wikimatkat' => 'Wikimatkoissa',
			],
			'illative' => [
				'Wikiuutiset' => 'Wikiuutisiin',
				'Wikisitaatit' => 'Wikisitaatteihin',
				'Wikimedia Suomi' => 'Wikimedia Suomeen',
				'Wikimatkat' => 'Wikimatkoihin',
			],
		];

		$wgGrammarForms['ga'] = [
			'genitive' => [
				'Vicipéid'     => 'Vicipéide',
				'Vicífhoclóir' => 'Vicífhoclóra',
				'Vicíleabhair' => 'Vicíleabhar',
				'Vicíshliocht' => 'Vicíshleachta',
				'Vicífhoinse'  => 'Vicífhoinse',
				'Vicíghnéithe' => 'Vicíghnéithe',
				'Vicínuacht'   => 'Vicínuachta',
			],
		];

		$wgGrammarForms['gsw'] = [
			// dative
			'dativ' => [
				'Wikipedia'       => 'vo de Wikipedia',
				'Wikinorchrichte' => 'vo de Wikinochrichte',
				'Wiktionaire'     => 'vom Wiktionaire',
				'Wikibuecher'     => 'vo de Wikibuecher',
				'Wikisprüch'      => 'vo de Wikisprüch',
				'Wikiquälle'      => 'vo de Wikiquälle',
			],
			// accusative
			'akkusativ' => [
				'Wikipedia'       => 'd Wikipedia',
				'Wikinorchrichte' => 'd Wikinorchrichte',
				'Wiktionaire'     => 's Wiktionaire',
				'Wikibuecher'     => 'd Wikibuecher',
				'Wikisprüch'      => 'd Wikisprüch',
				'Wikiquälle'      => 'd Wikiquälle',
			],
			// nominative
			'nominativ' => [
				'Wikipedia'       => 'd Wikipedia',
				'Wikinorchrichte' => 'd Wikinorchrichte',
				'Wiktionaire'     => 's Wiktionaire',
				'Wikibuecher'     => 'd Wikibuecher',
				'Wikisprüch'      => 'd Wikisprüch',
				'Wikiquälle'      => 'd Wikiquälle',
			],
		];

		$wgGrammarForms['hsb'] = [
			// genitive
			'genitiw' => [
				'Wikipedija'  => 'Wikipedije',
				'Wikiknihi'   => 'Wikiknih',
				'Wikinowiny'  => 'Wikinowin',
				'Wikižórło'   => 'Wikižórła',
				'Wikicitaty'  => 'Wikicitatow',
				'Wikisłownik' => 'Wikisłownika',
			],
			// dative
			'datiw' => [
				'Wikipedija'  => 'Wikipediji',
				'Wikiknihi'   => 'Wikikniham',
				'Wikinowiny'  => 'Wikinowinam',
				'Wikižórło'   => 'Wikižórłu',
				'Wikicitaty'  => 'Wikicitatam',
				'Wikisłownik' => 'Wikisłownikej',
			],
			// accusative
			'akuzativ' => [
				'Wikipedija'  => 'Wikipediju',
				'Wikiknihi'   => 'Wikiknknihi',
			],
			// instrumental
			'instrumental' => [
				'Wikipedija'  => 'Wikipediju',
				'Wikiknihi'   => 'Wikiknihami',
				'Wikinowiny'  => 'Wikinowinami',
				'Wikižórło'   => 'Wikižórłom',
				'Wikicitaty'  => 'Wikicitatami',
				'Wikisłownik' => 'Wikisłownikom',
			],
			// locative
			'lokatiw' => [
				'Wikipedija'  => 'Wikipediji',
				'Wikiknihi'   => 'Wikiknihach',
				'Wikinowiny'  => 'Wikinowinach',
				'Wikižórło'   => 'Wikižórłu',
				'Wikicitaty'  => 'Wikicitatach',
				'Wikisłownik' => 'Wikisłowniku',
			],
		];

		$wgGrammarForms['hu'] = [
			'rol' => [
				'Wikipédia'   => 'Wikipédiáról',
				'Wikidézet'   => 'Wikidézetről',
				'Wikiszótár'  => 'Wikiszótárról',
				'Wikikönyvek' => 'Wikikönyvekről',
			],
			'ba' => [
				'Wikipédia'   => 'Wikipédiába',
				'Wikidézet'   => 'Wikidézetbe',
				'Wikiszótár'  => 'Wikiszótárba',
				'Wikikönyvek' => 'Wikikönyvekbe',
			],
			'k' => [
				'Wikipédia'   => 'Wikipédiák',
				'Wikidézet'   => 'Wikidézetek',
				'Wikiszótár'  => 'Wikiszótárak',
			],
		];
		// T289767
		$wgGrammarForms['ks'] = [
			// ergative
			'کرٛاوَل' => [
				'وِکیٖلۄغَتھ' => 'وِکیٖلۄغتَن',
			],
			// dative
			'دُکرٛٲوؠ' => [
				'وِکیٖلۄغَتھ' => 'وِکیٖلۄغتَس',
			],
			// ablative
			'آیِتؠ' => [
				'وِکیٖلۄغَتھ' => 'وِکیٖلۄغتہٕ',
			],
		];
		$wgGrammarForms['la'] = [
			'genitive' => [
				'Vicimedia Communia' => 'Vicimediorum Communium',
			],
			'ablative' => [
				'Vicimedia Communia' => 'Vicimediis Communibus',
			],
		];

		$wgGrammarForms['lv'] = [
			'ģenitīvs' => [
				'Vikipēdija'   => 'Vikipēdijas',
				'Vikivārdnīca' => 'Vikivārdnīcas',
			],
			'datīvs' => [
				'Vikipēdija'   => 'Vikipēdijai',
				'Vikivārdnīca' => 'Vikivārdnīcai',
			],
			'akuzatīvs' => [
				'Vikipēdija'   => 'Vikipēdiju',
				'Vikivārdnīca' => 'Vikivārdnīcu',
			],
			'lokatīvs' => [
				'Vikipēdija'   => 'Vikipēdijā',
				'Vikivārdnīca' => 'Vikivārdnīcā',
			],
		];

		$wgGrammarForms['pl'] = [
			'D.lp' => [
				'Wikicytaty'   => 'Wikicytatów',
				'Wikipedia'    => 'Wikipedii',
				'Wikipodróże'  => 'Wikipodróży',
				'Wikisłownik'  => 'Wikisłownika',
				'Wikiźródła'   => 'Wikiźródeł',
				'Wikidane'     => 'Wikidanych',
			],
			'C.lp' => [
				'Wikicytaty'   => 'Wikicytatom',
				'Wikipedia'    => 'Wikipedii',
				'Wikipodróże'  => 'Wikipodróżom',
				'Wikisłownik'  => 'Wikisłownikowi',
				'Wikiźródła'   => 'Wikiźródłom',
				'Wikidane'     => 'Wikidanym',
			],
			'B.lp' => [
				'Wikipedia'    => 'Wikipedię',
			],
			'N.lp' => [
				'Wikicytaty'   => 'Wikicytatami',
				'Wikipedia'    => 'Wikipedią',
				'Wikipodróże'  => 'Wikipodróżami',
				'Wikisłownik'  => 'Wikisłownikiem',
				'Wikiźródła'   => 'Wikiźródłami',
				'Wikidane'     => 'Wikidanymi',
			],
			'MS.lp' => [
				'Wikicytaty'   => 'Wikicytatach',
				'Wikipedia'    => 'Wikipedii',
				'Wikipodróże'  => 'Wikipodróżach',
				'Wikisłownik'  => 'Wikisłowniku',
				'Wikiźródła'   => 'Wikiźródłach',
				'Wikidane'     => 'Wikidanych',
			],
			'W.lp' => [
				'Wikipedia'    => 'Wikipedio',
				'Wikisłownik'  => 'Wikisłowniku',
			],
		];

		$wgGrammarForms['rmy'] = [
			// genitive (m.sg.)
			'genitive-m-sg' => [
				'Vikipidiya' => 'Vikipidiyako',
				'Vikcyonaro' => 'Vikcyonaresko',
			],
			// genitive (f.sg.)
			'genitive-f-sg' => [
				'Vikipidiya' => 'Vikipidiyaki',
				'Vikcyonaro' => 'Vikcyonareski',
			],
			// genitive (pl.)
			'genitive-pl' => [
				'Vikipidiya' => 'Vikipidiyake',
				'Vikcyonaro' => 'Vikcyonareske',
			],
			// dative
			'dativ' => [
				'Vikipidiya' => 'Wikipediji',
				'Vikcyonaro' => 'Vikcyonareske',
			],
			// locative
			'locative' => [
				'Vikipidiya' => 'Wikipedijo',
				'Vikcyonaro' => 'Vikcyonareste',
			],
			// ablative
			'ablative' => [
				'Vikipidiya' => 'o Wikipediji',
				'Vikcyonaro' => 'Vikcyonarestar',
			],
			// instrumental
			'instrumental' => [
				'Vikipidiya' => 'z Wikipedijo',
				'Vikcyonaro' => 'Vikcyonaresa',
			],
		];

		$wgGrammarForms['sk'] = [
			'genitív' => [
				'Wikipédia'   => 'Wikipédie',
				'Wikislovník' => 'Wikislovníku',
				'Wikicitáty'  => 'Wikicitátov',
				'Wikiknihy'   => 'Wikikníh',
			],
			'datív' => [
				'Wikipédia'   => 'Wikipédii',
				'Wikislovník' => 'Wikislovníku',
				'Wikicitáty'  => 'Wikicitátom',
				'Wikiknihy'   => 'Wikiknihám',
			],
			'akuzatív' => [
				'Wikipédia'   => 'Wikipédiu',
				'Wikislovník' => 'Wikislovník',
				'Wikicitáty'  => 'Wikicitáty',
				'Wikiknihy'   => 'Wikiknihy',
			],
			'lokál' => [
				'Wikipédia'   => 'Wikipédii',
				'Wikislovník' => 'Wikislovníku',
				'Wikicitáty'  => 'Wikicitátoch',
				'Wikiknihy'   => 'Wikiknihách',
			],
			'inštrumentál' => [
				'Wikipédia'   => 'Wikipédiou',
				'Wikislovník' => 'Wikislovníkom',
				'Wikicitáty'  => 'Wikicitátmi',
				'Wikiknihy'   => 'Wikiknihami',
			],
		];

		$wgGrammarForms['sl'] = [
			// genitive
			'rodilnik' => [
				'Wikipedija'  => 'Wikipedije',
				'Wikiknjige'  => 'Wikiknjig',
				'Wikinovice'  => 'Wikinovic',
				'Wikinavedek' => 'Wikinavedka',
				'Wikivir'     => 'Wikivira',
				'Wikislovar'  => 'Wikislovarja',
			],
			// dative
			'dajalnik' => [
				'Wikipedija'  => 'Wikipediji',
				'Wikiknjige'  => 'Wikiknjigam',
				'Wikinovice'  => 'Wikinovicam',
				'Wikinavedek' => 'Wikinavedku',
				'Wikivir'     => 'Wikiviru',
				'Wikislovar'  => 'Wikislovarju',
			],
			// accusative
			'tožilnik' => [
				'Wikipedija'  => 'Wikipedijo',
				// no need to transform the others
			],
			// locative
			'mestnik' => [
				'Wikipedija'  => 'o Wikipediji',
				'Wikiknjige'  => 'o Wikiknjigah',
				'Wikinovice'  => 'o Wikinovicah',
				'Wikinavedek' => 'o Wikinavedku',
				'Wikivir'     => 'o Wikiviru',
				'Wikislovar'  => 'o Wikislovarju',
			],
			// instrumental
			'orodnik' => [
				'Wikipedija'  => 'z Wikipedijo',
				'Wikiknjige'  => 'z Wikiknjigami',
				'Wikinovice'  => 'z Wikinovicami',
				'Wikinavedek' => 'z Wikinavedkom',
				'Wikivir'     => 'z Wikivirom',
				'Wikislovar'  => 'z Wikislovarjem',
			],
		];

		$wgGrammarForms['sr-ec'] = [
			// genitive
			'генитив' => [
				'Викиречник'  => 'Викиречника',
				'Викицитат'   => 'Викицитата',
				'Викизворник' => 'Викизворника',
				'Викикњиге'   => 'Викикњига',
				'Википедија'  => 'Википедије',
				'Википодаци'  => 'Википодатака',
				'Викимедијина остава' => 'Викимедијине оставе',
				'Викиверзитет' => 'Викиверзитета',
			],
			// dative
			'датив' => [
				'Викиречник'  => 'Викиречнику',
				'Викицитат'   => 'Викицитату',
				'Викизворник' => 'Викизворнику',
				'Викикњиге'   => 'Викикњигама',
				'Википедија'  => 'Википедији',
				'Википодаци'  => 'Википодацима',
				'Викиновости'   => 'Викиновистима',
				'Викимедијина остава' => 'Викимедијиној остави',
				'Викиверзитет' => 'Викиверзитету',
			],
			// accusative
			'акузатив' => [
				'Викиречник'  => 'Викиречник',
				'Викицитат'   => 'Викицитат',
				'Викизворник' => 'Викизворник',
				'Википедија'  => 'Википедију',
				'Википодаци'  => 'Википодатке',
			],
			// vocative
			'вокатив' => [
				'Викиречник'  => 'Викиречниче',
				'Викизворник' => 'Викизворниче',
				'Википедија'  => 'Википедијо',
				'Викимедијина остава' => 'Викимедијина оставо',
				'Викиверзитет' => 'Викиверзитету',
			],
			// instrumental
			'инструментал' => [
				'Викиречник'  => 'Викиречником',
				'Викицитат'   => 'Викицитатом',
				'Викизворник' => 'Викизворником',
				'Викикњиге'   => 'Викикњигама',
				'Википедија'  => 'Википедијом',
				'Википодаци'  => 'Википодацима',
				'Викиновости'   => 'Викиновостима',
				'Викимедијина остава' => 'Викимедијином оставом',
				'Викиверзитет' => 'Викиверзитетом',
			],
			// locative
			'локатив' => [
				'Викиречник'  => 'Викиречнику',
				'Викицитат'   => 'Викицитату',
				'Викизворник' => 'Викизворнику',
				'Викикњиге'   => 'Викикњигама',
				'Википедија'  => 'Википедији',
				'Википодаци'  => 'Википодацима',
				'Викиновости'   => 'Викиновостима',
				'Викимедијина остава' => 'Викимедијиној остави',
				'Викиверзитет' => 'Викиверзитету',
			],
		];

		$wgGrammarForms['sr-el'] = [
			// genitive
			'genitiv' => [
				'Vikirečnik'  => 'Vikirečnika',
				'Vikicitat'   => 'Vikicitata',
				'Vikizvornik' => 'Vikizvornika',
				'Vikiknjige'  => 'Vikiknjiga',
				'Vikipedija'  => 'Vikipedije',
				'Vikipodaci'  => 'Vikipodataka',
				'Vikimedijina ostava' => 'Vikimedijine ostave',
				'Vikiverzitet' => 'Vikiverziteta',
			],
			// dative
			'dativ' => [
				'Vikirečnik'  => 'Vikirečniku',
				'Vikicitat'   => 'Vikicitatu',
				'Vikizvornik' => 'Vikizvorniku',
				'Vikiknjige'  => 'Vikiknjigama',
				'Vikipedija'  => 'Vikipediji',
				'Vikipodaci'  => 'Vikipodacima',
				'Vikinovosti'   => 'Vikinovostima',
				'Vikimedijina ostava' => 'Vikimedijinoj ostavi',
				'Vikiverzitet' => 'Vikiverzitetu',
			],
			// accusative
			'akuzativ' => [
				'Vikirečnik'  => 'Vikirečnik',
				'Vikicitat'   => 'Vikicitat',
				'Vikizvornik' => 'Vikizvornik',
				'Vikipedija'  => 'Vikipediju',
				'Vikipodaci'  => 'Vikipodatke',
			],
			// vocative
			'vokativ' => [
				'Vikirečnik'  => 'Vikirečniče',
				'Vikizvornik' => 'Vikizvorniče',
				'Vikipedija'  => 'Vikipedijo',
				'Vikimedijina ostava' => 'Vikimedijina ostavo',
				'Vikiverzitet' => 'Vikiverzitetu',
			],
			// instrumental
			'instrumental' => [
				'Vikirečnik'  => 'Vikirečnikom',
				'Vikicitat'   => 'Vikicitatom',
				'Vikizvornik' => 'Vikizvornikom',
				'Vikiknjige'  => 'Vikiknjigama',
				'Vikipedija'  => 'Vikipedijom',
				'Vikipodaci'  => 'Vikipodacima',
				'Vikinovosti'   => 'Vikinovostima',
				'Vikimedijina ostava' => 'Vikimedijinom ostavom',
				'Vikiverzitet' => 'Vikiverzitetom',
			],
			// locative
			'lokativ' => [
				'Vikirečnik'  => 'Vikirečniku',
				'Vikicitat'   => 'Vikicitatu',
				'Vikizvornik' => 'Vikizvorniku',
				'Vikiknjige'  => 'Vikiknjigama',
				'Vikipedija'  => 'Vikipediji',
				'Vikipodaci'  => 'Vikipodacima',
				'Vikinovosti'   => 'Vikinovostima',
				'Vikimedijina ostava' => 'Vikimedijinoj ostavi',
				'Vikiverzitet' => 'Vikiverzitetu',
			],
		];

		$wgGrammarForms['uk'] = [
			// genitive
			'genitive' => [
				'Вікіпедія' => 'Вікіпедії',
				'Вікісловник' => 'Вікісловника',
				'Вікісховище' => 'Вікісховища',
				'Вікіпідручник' => 'Вікіпідручника',
				'Вікіцитати' => 'Вікіцитат',
				'Вікіджерела' => 'Вікіджерел',
				'Вікіновини' => 'Вікіновин',
				'Вікідані' => 'Вікіданих',
				'Вікімандри' => 'Вікімандрів',
			],
			// dative
			'dative' => [
				'Вікіпедія' => 'Вікіпедії',
				'Вікісловник' => 'Вікісловнику',
				'Вікісховище' => 'Вікісховищу',
				'Вікіпідручник' => 'Вікіпідручнику',
				'Вікіцитати' => 'Вікіцитатам',
				'Вікіджерела' => 'Вікіджерелам',
				'Вікіновини' => 'Вікіновинам',
				'Вікідані' => 'Вікіданим',
				'Вікімандри' => 'Вікімандрам',
			],
			// accusative
			'accusative' => [
				'Вікіпедія' => 'Вікіпедію',
				'Вікісловник' => 'Вікісловник',
				'Вікісховище' => 'Вікісховище',
				'Вікіпідручник' => 'Вікіпідручник',
				'Вікіцитати' => 'Вікіцитати',
				'Вікіджерела' => 'Вікіджерела',
				'Вікіновини' => 'Вікіновини',
				'Вікідані' => 'Вікідані',
				'Вікімандри' => 'Вікімандри',
			],
			// instrumental
			'instrumental' => [
				'Вікіпедія' => 'Вікіпедією',
				'Вікісловник' => 'Вікісловником',
				'Вікісховище' => 'Вікісховищем',
				'Вікіпідручник' => 'Вікіпідручником',
				'Вікіцитати' => 'Вікіцитатами',
				'Вікіджерела' => 'Вікіджерелами',
				'Вікіновини' => 'Вікіновинами',
				'Вікідані' => 'Вікіданими',
				'Вікімандри' => 'Вікімандрами',
			],
			// locative
			'locative' => [
				'Вікіпедія' => 'у Вікіпедії',
				'Вікісловник' => 'у Вікісловнику',
				'Вікісховище' => 'у Вікісховищі',
				'Вікіпідручник' => 'у Вікіпідручнику',
				'Вікіцитати' => 'у Вікіцитатах',
				'Вікіджерела' => 'у Вікіджерелах',
				'Вікіновини' => 'у Вікіновинах',
				'Вікідані' => 'у Вікіданих',
				'Вікімандри' => 'у Вікімандрах',
			],
			// vocative
			'vocative' => [
				'Вікіпедія' => 'Вікіпедіє',
				'Вікісловник' => 'Вікісловнику',
				'Вікісховище' => 'Вікісховище',
				'Вікіпідручник' => 'Вікіпідручнику',
				'Вікіцитати' => 'Вікіцитати',
				'Вікіджерела' => 'Вікіджерела',
				'Вікіновини' => 'Вікіновини',
				'Вікідані' => 'Вікідані',
				'Вікімандри' => 'Вікімандри',
			],
		];

		// Avoid weirdness if both extensions are loaded at the same time.
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'UserMerge' ) ) {
			global $wgLogTypes, $wgLogNames, $wgLogHeaders, $wgLogActionsHandlers, $wgActionFilteredLogs;

			$wgLogTypes[] = 'usermerge';
			$wgLogNames['usermerge'] = 'wikimedia-usermerge-logpage';
			$wgLogHeaders['usermerge'] = 'wikimedia-usermerge-logpagetext';

			$wgLogActionsHandlers['usermerge/*'] = WMUserMergeLogFormatter::class;
			$wgActionFilteredLogs['usermerge'] = [
				'mergeuser'  => [ 'mergeuser' ],
				'deleteuser' => [ 'deleteuser' ],
			];
		}
	}

	/**
	 * Check if one or both of the 'damaging' and 'goodfaith' models are
	 * available on the current wiki.
	 *
	 * @return bool
	 */
	private function isOresAvailable() {
		return $this->extensionRegistry->isLoaded( 'ORES' ) &&
			(
				ORESHookHelpers::isModelEnabled( 'damaging' )
				|| ORESHookHelpers::isModelEnabled( 'goodfaith' )
			);
	}

	/**
	 * - Prepare guided tours relevant to ChangesListSpecialPage.
	 *   In MediaWiki core: RecentChanges, RecentChangesLinked, and Watchlist (depending
	 *   on the current user's preferences).
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ChangesListSpecialPageStructuredFilters
	 *
	 * @param ChangesListSpecialPage $special
	 */
	public function onChangesListSpecialPageStructuredFilters( $special ) {
		if ( !$this->extensionRegistry->isLoaded( 'GuidedTour' ) ) {
			return;
		}
		$title = $special->getPageTitle();
		$user = $special->getUser();
		$out = $special->getOutput();

		if (
			// If we're on Special:RecentChanges
			$title->isSpecial( 'Recentchanges' ) &&
			// And the user is one with an account
			$user->isRegistered() &&
			// If RCFilters UI is enabled
			$special->isStructuredFilterUiEnabled()
		) {
			if ( !$this->userOptionsManager->getOption( $user, 'rcenhancedfilters-seen-tour' ) ) {
				GuidedTourLauncher::launchTour( 'RcFiltersIntro', 'Welcome' );
				$out->addJsConfigVars( 'wgRCFiltersORESAvailable', $this->isOresAvailable() );
			}

			if ( !$this->userOptionsManager->getOption( $user, 'rcenhancedfilters-tried-highlight' ) ) {
				$out->addModules( 'ext.guidedTour.tour.RcFiltersHighlight' );
			}
		} elseif (
			$title->isSpecial( 'Watchlist' ) &&
			$user->isRegistered() &&
			$special->isStructuredFilterUiEnabled() &&
			!$this->userOptionsManager->getOption( $user, 'wlenhancedfilters-seen-tour' )
		) {
			// Show watchlist tour
			GuidedTourLauncher::launchTour( 'WlFiltersIntro', 'Welcome' );
			$out->addJsConfigVars( 'wgRCFiltersORESAvailable', $this->isOresAvailable() );
		}
	}

	/**
	 * This hook is called when a new user account is (auto-)created.
	 *
	 * It is used to prevent new users from seeing RCFilters guided tours
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LocalUserCreated
	 *
	 * @param User $user
	 * @param bool $autocreated
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		$this->userOptionsManager->setOption( $user, 'rcenhancedfilters-seen-tour', true );
		$this->userOptionsManager->setOption( $user, 'wlenhancedfilters-seen-tour', true );
	}

	/**
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ResourceLoaderRegisterModules
	 *
	 * @param ResourceLoader $resourceLoader
	 */
	public function onResourceLoaderRegisterModules( ResourceLoader $resourceLoader ): void {
		if ( $this->extensionRegistry->isLoaded( 'GuidedTour' ) ) {
			$resourceLoader->register( 'ext.guidedTour.tour.RcFiltersIntro', [
				'localBasePath' => dirname( __DIR__ ) . '/modules',
				'remoteExtPath' => 'WikimediaMessages/modules',
				'scripts' => 'rcfilters-intro-tour.js',
				'styles' => 'rcfilters-intro-tour.less',
				'messages' => [
					'eri-rcfilters-tour-intro-welcome-title',
					'eri-rcfilters-tour-intro-welcome-description',
					'eri-rcfilters-tour-help',
					'eri-rcfilters-tour-intro-welcome-no-ores-description',
					'eri-rcfilters-tour-intro-preferences-description',
					'eri-rcfilters-tour-intro-welcome-button',
				],
				'dependencies' => [
					'ext.guidedTour'
				],
			] );
			$resourceLoader->register( 'ext.guidedTour.tour.WlFiltersIntro', [
				'localBasePath' => dirname( __DIR__ ) . '/modules',
				'remoteExtPath' => 'WikimediaMessages/modules',
				'scripts' => 'wlfilters-intro-tour.js',
				'styles' => 'rcfilters-intro-tour.less',
				'messages' => [
					'eri-wlfilters-tour-intro-welcome-title',
					'eri-rcfilters-tour-intro-welcome-description',
					'eri-rcfilters-tour-help',
					'eri-rcfilters-tour-intro-welcome-no-ores-description',
					'eri-wlfilters-tour-intro-preferences-description',
					'eri-rcfilters-tour-intro-welcome-button',
					'eri-rcfilters-tour-intro-disable-button',
				],
				'dependencies' => [
					'ext.guidedTour'
				],
			] );
			$resourceLoader->register( 'ext.guidedTour.tour.RcFiltersHighlight', [
				'localBasePath' => dirname( __DIR__ ) . '/modules',
				'remoteExtPath' => 'WikimediaMessages/modules',
				'scripts' => [
					'rcfilters-highlight-tour-hooks.js',
					'rcfilters-highlight-tour.js',
				],
				'styles' => 'rcfilters-highlight-tour.less',
				'messages' => [
					'eri-rcfilters-tour-highlight-title',
					'eri-rcfilters-tour-highlight-description',
					'eri-rcfilters-tour-highlight-button',
				],
				'dependencies' => [
					'ext.guidedTour'
				],
			] );
		}

		if ( $this->extensionRegistry->isLoaded( 'IPInfo' ) ) {
			$resourceLoader->register( 'ext.wikimediaMessages.ipInfo.hooks', [
				'localBasePath' => dirname( __DIR__ ) . '/modules/ext.wikimediaMessages.ipInfo.hooks',
				'remoteExtPath' => 'WikimediaMessages/modules/ext.wikimediaMessages.ipInfo.hooks',
				'scripts' => 'infobox.js',
				'styles' => 'infobox.less',
				'messages' => [
					'ipinfo-feedback-button-label',
					'ipinfo-global-contributions-url-label',
					'ipinfo-global-contributions-url',
					'ipinfo-xtools-url-label',
					'ipinfo-xtools-url',
				],
				'dependencies' => [
					'ext.ipInfo',
					'oojs-ui.styles.icons-interactions',
				],
			] );
		}
	}

	/**
	 * Register extra preferences.
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/GetPreferences
	 *
	 * @param User $user
	 * @param array &$preferences
	 */
	public function onGetPreferences( $user, &$preferences ) {
		$preferences['rcenhancedfilters-seen-tour'] = [
			'type' => 'api',
		];

		$preferences['rcenhancedfilters-tried-highlight'] = [
			'type' => 'api',
		];

		$preferences['rcenhancedfilters-seen-highlight-button-counter'] = [
			'type' => 'api',
		];

		$preferences['wlenhancedfilters-seen-tour'] = [
			'type' => 'api',
		];
	}

	/**
	 * Handle SpecialPageBeforeExecute hook
	 *
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 */
	public function onSpecialPageBeforeExecute( $special, $subPage ) {
		$this->addBlockFeedbackLink( $special );
		$this->addIPInfoLinks( $special, $subPage );
	}

	/**
	 * @param SpecialPage $special
	 */
	private function addBlockFeedbackLink( $special ) {
		if (
			$special->getName() !== 'Block' ||
			!$special->userCanExecute( $special->getUser() )
		) {
			return;
		}

		$output = $special->getOutput();

		$output->enableOOUI();
		$output->addModuleStyles( [
			'mediawiki.special.block.feedback.request',
			'oojs-ui.styles.icons-interactions'
		] );

		$icon = new IconWidget( [
			'icon' => 'feedback',
		] );

		$link = Html::rawElement(
			'a',
			[
				'href' => 'https://meta.wikimedia.org/wiki/Special:MyLanguage/' .
					'Community_health_initiative/Blocking_tools_and_improvements/Feedback',
				'target' => '_blank',
				'class' => 'mw-feedbacklink',
			],
			$icon . $special->msg( 'specialblockfeedback' )->escaped()
		);

		$output->setIndicators(
			[ 'mw-feedbacklink' => $link ]
		);
	}

	/**
	 * @param SpecialPage $special
	 * @param string|null $subPage
	 */
	private function addIPInfoLinks( $special, $subPage ): void {
		$out = $special->getOutput();
		if (
			!$out->getTitle() ||
			(
				!$out->getTitle()->isSpecial( 'Contributions' ) &&
				!$out->getTitle()->isSpecial( 'DeletedContributions' )
			)
		) {
			return;
		}

		// Return if target is not an IP address
		if ( !IPUtils::isValid( $subPage ) ) {
			return;
		}

		$accessingUser = $out->getUser();
		$isBetaFeaturesLoaded = $this->extensionRegistry->isLoaded( 'BetaFeatures' );
		$services = MediaWikiServices::getInstance();
		$permissionManager = $services->getPermissionManager();
		$userOptionsLookup = $services->getUserOptionsLookup();

		// Check the same permissions and preferences as
		// MediaWiki\IPInfo\HookHandler\InfoboxHandler
		if (
			!$permissionManager->userHasRight( $accessingUser, 'ipinfo' ) ||
			$isBetaFeaturesLoaded &&
			!$userOptionsLookup->getOption( $accessingUser, 'ipinfo-beta-feature-enable' )
		) {
			return;
		}

		$out->addModules( 'ext.wikimediaMessages.ipInfo.hooks' );
	}

	/**
	 * Use this hook to modify the subtitle links on Special:Investigate.
	 *
	 * @param IContextSource $context
	 * @param array &$links
	 */
	public static function onCheckUserSubtitleLinks( IContextSource $context, array &$links ) {
		$text = $context->msg( 'specialinvestigate-feedback' )->text();
		$links[] = ( new Tag( 'a' ) )->appendContent( $text )->setAttributes( [
			'href' => 'https://meta.wikimedia.org/wiki/Special:MyLanguage/'
				. 'Talk:IP_Editing:_Privacy_Enhancement_and_Abuse_Mitigation/CheckUser_Improvements',
		] );
	}

}
