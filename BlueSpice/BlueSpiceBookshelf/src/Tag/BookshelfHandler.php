<?php

namespace BlueSpice\Bookshelf\Tag;

use BlueSpice\Tag\Handler;
use Config;
use Exception;
use FormatJson;
use Html;
use MediaWiki\MediaWikiServices;
use PageHierarchyProvider;
use Parser;
use PPFrame;
use RequestContext;

class BookshelfHandler extends Handler {

	/**
	 * @var array
	 */
	private $errors = [];

	/**
	 * @param string $processedInput
	 * @param array $processedArgs
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @param Config $config
	 */
	public function __construct( $processedInput, array $processedArgs, Parser $parser,
		PPFrame $frame, Config $config ) {
		$this->config = $config;
		parent::__construct( $processedInput, $processedArgs, $parser, $frame );
	}

	/**
	 * @return string
	 */
	public function handle() {
		$context = RequestContext::getMain();
		$src = $this->processedArgs[Bookshelf::PARAM_SRC];
		if ( !empty( $this->processedArgs[Bookshelf::PARAM_BOOK] ) ) {
			$src = $this->processedArgs[Bookshelf::PARAM_BOOK];
		}
		if ( !empty( $this->processedArgs[Bookshelf::PARAM_STYLE] ) ) {
			$this->processedArgs[Bookshelf::PARAM_STYLE]
				= " style=\"{$this->processedArgs[Bookshelf::PARAM_STYLE]}\"";
		} else {
			if ( $this->processedArgs[Bookshelf::PARAM_FLOAT] == 'right'
				|| $this->processedArgs[Bookshelf::PARAM_FLOAT] == 'left' ) {
				$side = $this->processedArgs[Bookshelf::PARAM_FLOAT] == 'right' ? 'left' : 'right';
				$this->processedArgs[Bookshelf::PARAM_STYLE] = " style=\"margin-$side: 10px; "
					. "float: {$this->processedArgs[Bookshelf::PARAM_FLOAT]}\"";
			} elseif ( !empty( $this->processedArgs[Bookshelf::PARAM_FLOAT] ) ) {
				$this->errors[Bookshelf::PARAM_FLOAT]
					= $context->msg( 'bs-bookshelf-tagerror-attribute-float-not-valid' )->text();
			}
		}

		if ( $this->processedArgs[Bookshelf::PARAM_WIDTH] < 0 ) {
			$this->errors[Bookshelf::PARAM_WIDTH]
				= $context->msg( 'bs-bookshelf-positive-integer-validation-not-approved' )->text();
		}
		if ( $this->processedArgs[Bookshelf::PARAM_HEIGHT] < 0 ) {
			$this->errors[Bookshelf::PARAM_HEIGHT]
				= $context->msg( 'bs-bookshelf-positive-integer-validation-not-approved' )->text();
		}
		if ( empty( $src ) ) {
			$this->errors[Bookshelf::PARAM_SRC]
				= $context->msg( 'bs-bookshelf-tagerror-no-attribute-given' )->text();
		}
		if ( !empty( $this->errors ) ) {
			return $this->makeErrorOutput();
		}

		/** @var \Title $title */
		$title = $this->parser->getPage();
		$displayTitle = $titleText = $title->getPrefixedText();
		$number = '';
		$haschildren = false;

		try {
			$oPHP = PageHierarchyProvider::getInstanceFor(
				$src,
				[ 'follow-redirects' => true ]
			);
			$treeJSON = $oPHP->getExtendedTOCJSON();
			$entry = $oPHP->getEntryFor( $titleText );
			if ( $entry !== null ) {
				$number = $entry->articleNumber;

				if ( isset( $entry->articleDisplayTitle ) ) {
					$displayTitle = $entry->articleDisplayTitle;
				}
				// Fallback in case of no display title but subpage
				if ( str_replace( '_', ' ', $displayTitle ) === $titleText
					&& $title->isSubpage() ) {
					$displayTitle = basename( $title->getText() );
				}
				$haschildren = isset( $entry->children ) && !empty( $entry->children );
			}
		} catch ( Exception $e ) {
			$this->errors[Bookshelf::PARAM_SRC] = $context->msg(
				'bs-bookshelf-tagerror-article-title-not-valid',
				$src
			)->plain();
			return $this->makeErrorOutput();
		}

		$attribs = [];
		MediaWikiServices::getInstance()->getHookContainer()->run(
			'BSBookshelfTagBeforeRender',
			[
				&$src,
				$treeJSON,
				&$number,
				&$attribs
			]
		);

		$this->parser->getOutput()->setPageProperty( 'bs-bookshelf-sourcearticle', $src );
		$this->parser->getOutput()->setPageProperty( 'bs-bookshelf-number', $number );
		$this->parser->getOutput()->setPageProperty( 'bs-bookshelf-display-title', $displayTitle );

		if ( $this->config->get( 'BookshelfPrependPageTOCNumbers' ) ) {
			$this->parser->getOptions()->setNumberHeadings( true );
		}
		if ( $this->config->get( 'BookshelfTitleDisplayText' ) ) {
			$titleTextText = $displayTitle;
			if ( $number ) {
				$titleTextText = '<span class="bs-chapter-number">' . $number
					. '. </span>' . $titleTextText;
			}
			if ( $titleTextText !== $titleText ) {
				$this->parser->getOutput()->setTitleText( $titleTextText );
			}
		}

		// This seems to better place than "BeforePageDisplay" hook
		$this->parser->getOutput()->addModules( [ 'ext.bluespice.bookshelf' ] );
		$this->parser->getOutput()->addModuleStyles( [ 'ext.bluespice.bookshelf.styles' ] );

		$attribs = array_merge( [
			'class' => 'bs-bookshelf-toc',
			'data-bs-src' => $src,
			'data-bs-has-children' => $haschildren,
			'data-bs-tree' => FormatJson::encode( $treeJSON )
		], $attribs );

		if ( !empty( $number ) ) {
			$attribs['data-bs-number'] = $number;
		}

		return Html::element( 'div', $attribs );
	}

	/**
	 * @return string
	 */
	private function makeErrorOutput() {
		$out = [];
		foreach ( $this->errors as $errorKey => $errorMessage ) {
			$label = $this->makeErrorLabel( $errorKey );
			$out[] = Html::element(
				'div',
				[ 'class' => 'bs-error bs-tag' ],
				$label . $errorMessage
			);
		}
		return implode( "\n", $out );
	}

	/**
	 * @param string $errorKey
	 * @return string
	 */
	protected function makeErrorLabel( $errorKey ) {
		$keyParts = explode( '-', $errorKey, 2 );
		$argName = end( $keyParts );
		if ( $keyParts[0] === 'input' ) {
			return '';
		}

		return "$argName: ";
	}
}
