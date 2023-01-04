<?php
/**
 * Saving a section to an existing wikipage, created using Section translation feature of ContentTranslation.
 * The following special things happen when the page is created:
 * - HTML from the translation editor's contenteditable is converted to wiki syntax using Parsoid.
 * - A change tag is added.
 * - The edit summary shows a link to the revision from which the translation was made.
 *
 * @copyright See AUTHORS.txt
 * @license GPL-2.0-or-later
 */

namespace ContentTranslation\ActionApi;

use ApiBase;
use ApiMain;
use ContentTranslation\ContentTranslationHookRunner;
use ContentTranslation\ParsoidClient;
use ContentTranslation\ParsoidClientFactory;
use ContentTranslation\SandboxTitleMaker;
use ContentTranslation\SectionPositionCalculator;
use ContentTranslation\SiteMapper;
use ContentTranslation\Translation;
use ContentTranslation\TranslationWork;
use ContentTranslation\Translator;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\Languages\LanguageNameUtils;
use Title;
use TitleFactory;
use User;
use Wikimedia\ParamValidator\ParamValidator;

class ApiSectionTranslationPublish extends ApiBase {

	/** @var TitleFactory */
	private $titleFactory;

	/** @var HookContainer */
	private $hookContainer;

	/** @var LanguageNameUtils */
	private $languageNameUtils;

	/** @var ParsoidClientFactory */
	protected $parsoidClientFactory;

	/** @var SectionPositionCalculator */
	private $sectionPositionCalculator;

	/** @var SandboxTitleMaker */
	private $sandboxTitleMaker;

	/**
	 * @param ApiMain $main
	 * @param string $action
	 * @param TitleFactory $titleFactory
	 * @param HookContainer $hookContainer
	 * @param LanguageNameUtils $languageNameUtils
	 * @param ParsoidClientFactory $parsoidClientFactory
	 * @param SectionPositionCalculator $sectionPositionCalculator
	 * @param SandboxTitleMaker $sandboxTitleMaker
	 */
	public function __construct(
		ApiMain $main,
		$action,
		TitleFactory $titleFactory,
		HookContainer $hookContainer,
		LanguageNameUtils $languageNameUtils,
		ParsoidClientFactory $parsoidClientFactory,
		SectionPositionCalculator $sectionPositionCalculator,
		SandboxTitleMaker $sandboxTitleMaker
	) {
		parent::__construct( $main, $action );
		$this->titleFactory = $titleFactory;
		$this->hookContainer = $hookContainer;
		$this->languageNameUtils = $languageNameUtils;
		$this->parsoidClientFactory = $parsoidClientFactory;
		$this->sectionPositionCalculator = $sectionPositionCalculator;
		$this->sandboxTitleMaker = $sandboxTitleMaker;
	}

	protected function getParsoidClient(): ParsoidClient {
		return $this->parsoidClientFactory->createParsoidClient();
	}

	/**
	 * @param string $sourceLanguage
	 * @param string $sourceRevId
	 * @param string $sourceTitle
	 * @param bool $isLeadSection
	 * @param string $sourceSectionTitle
	 * @return string
	 */
	private function getPublishSummary(
		string $sourceLanguage,
		string $sourceRevId,
		string $sourceTitle,
		bool $isLeadSection,
		string $sourceSectionTitle
	): string {
		$sourceLink = "[[:{$sourceLanguage}:Special:Redirect/revision/{$sourceRevId}|{$sourceTitle}]]";
		// if the published section is a lead section, the summary should be slightly different
		if ( $isLeadSection ) {
			return $this->msg(
				'cx-sx-publish-lead-section-summary',
				$sourceLink
			)->inContentLanguage()->text();
		} else {
			return $this->msg(
				'cx-sx-publish-summary',
				$sourceSectionTitle,
				$sourceLink
			)->inContentLanguage()->text();
		}
	}

	/**
	 * Attempt to save a given page's wikitext to MediaWiki's storage layer via its API
	 *
	 * @param Title $title The title of the page to write
	 * @param string $wikitext The wikitext to write
	 * @param int|string $sectionNumber
	 * @return mixed The result of the save attempt
	 * @throws \ApiUsageException
	 */
	protected function submitEditAction( Title $title, string $wikitext, $sectionNumber ) {
		$params = $this->extractRequestParams();
		[ 'sourcelanguage' => $from, 'sourcerevid' => $sourceRevId, 'sourcetitle' => $sourceTitle ] = $params;
		$isLeadSection = $sectionNumber === 0;
		$summary = $this->getPublishSummary(
			$from,
			$sourceRevId,
			$sourceTitle,
			$isLeadSection,
			$params['sourcesectiontitle']
		);

		$apiParams = [
			'action' => 'edit',
			'title' => $title->getPrefixedDBkey(),
			'summary' => $summary,
			'sectiontitle' => $params['targetsectiontitle'],
			'section' => $sectionNumber,
			'captchaid' => $params['captchaid'],
			'captchaword' => $params['captchaword'],
		];

		if ( (int)$sectionNumber > 0 ) {
			$apiParams['prependtext'] = $wikitext;
		} else {
			$apiParams['text'] = $wikitext;
		}

		// Pass any unrecognized query parameters to the internal action=edit API request.
		$allowedParams = array_diff_key(
			$this->getRequest()->getValues(),
			$this->getAllowedParams(),
			$this->getMain()->getAllowedParams()
		);
		$api = new ApiMain(
			new \DerivativeRequest(
				$this->getRequest(),
				$apiParams + $allowedParams,
				/* was posted? */ true
			),
			/* enable write? */ true
		);
		$api->execute();
		return $api->getResult()->getResultData();
	}

	/**
	 * @throws \ApiUsageException
	 */
	public function execute() {
		$params = $this->extractRequestParams();

		$block = $this->getUser()->getBlock();
		if ( $block && $block->isSitewide() ) {
			$this->dieBlocked( $block );
		}

		if ( !$this->languageNameUtils->isKnownLanguageTag( $params['sourcelanguage'] ) ) {
			$this->dieWithError( 'apierror-cx-invalidsourcelanguage', 'invalidsourcelanguage' );
		}

		if ( !$this->languageNameUtils->isKnownLanguageTag( $params['targetlanguage'] ) ) {
			$this->dieWithError( 'apierror-cx-invalidtargetlanguage', 'invalidtargetlanguage' );
		}

		if ( trim( $params['html'] ) === '' ) {
			$this->dieWithError( [ 'apierror-paramempty', 'html' ], 'invalidhtml' );
		}

		$this->publish();
	}

	public function publish() {
		$params = $this->extractRequestParams();

		$targetTitleRaw = $params['title'];
		$isSandbox = $params['issandbox'];
		$user = $this->getUser();
		if ( $isSandbox ) {
			$targetTitle = $this->sandboxTitleMaker->makeSandboxTitle( $user, $targetTitleRaw );
		} else {
			$targetTitle = $this->titleFactory->newFromText( $targetTitleRaw );
		}

		if ( !$targetTitle ) {
			$this->dieWithError( [ 'apierror-invalidtitle', wfEscapeWikiText( $targetTitleRaw ) ] );
		}

		$hookRunner = new ContentTranslationHookRunner( $this->hookContainer );
		$targetLanguage = $params['targetlanguage'];
		'@phan-var \Title $targetTitle';
		$hookRunner->onSectionTranslationBeforePublish( $targetTitle, $targetLanguage, $user );

		$sectionNumber = $this->sectionPositionCalculator->calculateSectionPosition(
			$targetTitle,
			$targetLanguage,
			$isSandbox
		);

		$targetSectionTitle = $params['targetsectiontitle'];
		$editResult = $this->saveWikitext( $params['html'], $targetTitle, $sectionNumber, $targetSectionTitle );
		$editStatus = $editResult['result'];

		if ( $editStatus === 'Success' ) {
			$result = [
				'result' => 'success',
				'edit' => $editResult,
				'targettitle' => $targetTitle->getPrefixedURL()
			];
			// newrevid can be unset when publishing already present sections with the exact same
			// contents as the current revision
			if ( isset( $editResult['newrevid'] ) ) {
				// Add the tags post-send, after RC row insertion
				$newRevId = intval( $editResult['newrevid'] );
				$this->storeTags( $newRevId );

				[ 'sourcelanguage' => $sourceLanguage,'sourcetitle' => $sourceTitle ] = $params;
				$translation = $this->getExistingTranslation( $user, $sourceLanguage, $targetLanguage, $sourceTitle );

				if ( $translation === null ) {
					$this->dieWithError( 'apierror-cxpublishsection-translationnotfound', 'translationnotfound' );
				}

				// if translation exists update the "translation_target_revision_id" field for this row
				'@phan-var Translation $translation';
				$this->updateTranslation( $translation, $user, $newRevId, $targetTitleRaw, $targetLanguage );
			}
		} else {
			$result = [
				'result' => 'error',
				'edit' => $editResult
			];
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	/**
	 * Given the HTML of the published translation and the target page title (as a Title instance),
	 * this method uses the RestbaseClient service to convert the HTML to wikitext and calls
	 * the "submitEditAction" method to save the wikitext to MediaWiki's storage layer via its API.
	 *
	 * @param string $html
	 * @param Title $targetTitle
	 * @param int|string $sectionNumber
	 * @param string $targetSectionTitle
	 * @return mixed
	 * @throws \ApiUsageException
	 */
	private function saveWikitext( string $html, Title $targetTitle, $sectionNumber, string $targetSectionTitle ) {
		// When the section number is a positive integer, it means that the section needs to be positioned
		// before the first appendix section. In those cases, we need to prepend the target section title
		// to the HTML that is being published
		if ( (int)$sectionNumber > 0 ) {
			$html = $this->prependSectionTitle( $html, $targetSectionTitle );
		}
		$wikitext = null;
		try {
			$wikitext = $this->getParsoidClient()->convertHtmlToWikitext(
				$targetTitle,
				$html
			)['body'];
		} catch ( \MWException $e ) {
			$this->dieWithError(
				[ 'apierror-cx-docserverexception', wfEscapeWikiText( $e->getMessage() ) ], 'docserver'
			);
		}
		$editResult = $this->submitEditAction( $targetTitle, $wikitext, $sectionNumber );
		return $editResult['edit'];
	}

	/**
	 * This method prepends the target section title to the HTML that is being published.
	 * Used for sections that need to be prepended to the first appendix section of the
	 * target article.
	 * @param string $html
	 * @param string $sectionTitle
	 * @return string
	 */
	private function prependSectionTitle( string $html, string $sectionTitle ): string {
		// add empty line to the end of HTML string, so that the first appendix section title goes into the next line
		return "<h2>$sectionTitle</h2>\n$html\n";
	}

	/**
	 * Given a new target revision id, this method adds a deferred update to be executed at the end
	 * of the current request. This update adds the "contenttranslation" and "sectiontranslation" tags
	 * to the given revision.
	 *
	 * @param int $newRevId
	 * @return void
	 */
	private function storeTags( int $newRevId ) {
		$tags = [
			'contenttranslation',
			'sectiontranslation'
		];
		\DeferredUpdates::addCallableUpdate( static function () use ( $newRevId, $tags ) {
			\ChangeTags::addTags( $tags, null, $newRevId, null );
		} );
	}

	/**
	 * This method finds a translation inside "cx_translations" table, that corresponds
	 * to the source/target languages, the source title and the user of the published
	 * translation, and returns it. If no such translation exists, the method returns null.
	 *
	 * @param User $user
	 * @param string $from
	 * @param string $to
	 * @param string $sourceTitle
	 * @return Translation|null
	 */
	private function getExistingTranslation( User $user, string $from, string $to, string $sourceTitle ): ?Translation {
		$translator = new Translator( $user );
		$work = new TranslationWork( $sourceTitle, $from, $to );
		return Translation::findForTranslator( $work, $translator );
	}

	/**
	 * Given an existing Translation model and a new target revision id, this method updates the target
	 * revision id for this model and saves the corresponding row inside "cx_translations" table.
	 *
	 * @param Translation $translation
	 * @param User $user
	 * @param int $newRevId
	 * @param string $targetTitle
	 * @param string $targetLanguage
	 * @return void
	 */
	private function updateTranslation(
		Translation $translation,
		User $user,
		int $newRevId,
		string $targetTitle,
		string $targetLanguage
	): void {
		$translator = new Translator( $user );
		$translation->translation['status'] = 'published';
		$translation->translation['targetURL'] = $this->createTargetUrl(
			$user,
			$targetTitle,
			$targetLanguage
		);
		$translation->translation['targetRevisionId'] = $newRevId;
		$translation->save( $translator );
	}

	/**
	 * Given the target page title as a string and the target language of the translation,
	 * this method returns a string containing the URL of the target page.
	 *
	 * @param User $user
	 * @param string $targetTitleRaw
	 * @param string $targetLanguage
	 * @return string
	 */
	private function createTargetUrl( User $user, string $targetTitleRaw, string $targetLanguage ): string {
		$contentTranslationTranslateInTarget = $this->getConfig()->get( 'ContentTranslationTranslateInTarget' );
		if ( $contentTranslationTranslateInTarget ) {
			$targetPage = SiteMapper::getTargetTitle( $targetTitleRaw, $user->getName() );
			return SiteMapper::getPageURL( $targetLanguage, $targetPage );
		}
		$targetTitle = $this->titleFactory->newFromText( $targetTitleRaw );
		return $targetTitle->getCanonicalURL();
	}

	public function getAllowedParams() {
		return [
			'title' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'html' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			'sourcelanguage' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'targetlanguage' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'sourcetitle' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'sourcerevid' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'sourcesectiontitle' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'targetsectiontitle' => [
				ParamValidator::PARAM_REQUIRED => true,
			],
			'issandbox' => [
				ParamValidator::PARAM_TYPE => 'boolean',
				ParamValidator::PARAM_REQUIRED => false,
			],
			'captchaid' => null,
			'captchaword' => null,
		];
	}

	public function needsToken() {
		return 'csrf';
	}

	public function isWriteMode() {
		return true;
	}

	public function isInternal() {
		return true;
	}
}
