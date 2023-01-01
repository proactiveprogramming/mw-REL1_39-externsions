<?php
/**
 * Copyright (c) 2021  Pascal Noisette
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\Extension\Slides;

use Action as MWAction;
use ActorMigration;
use Linker;
use MediaWiki\MediaWikiServices;
use ParserOptions;

/**
 * Display article content in a custom html page with only revealjs on it.
 *
 * @package MediaWiki
 * @subpackage Extensions
 */
class SlidesAction extends MWAction {

	/**
	 * Keep subsections of level 1 (h2) in memory
	 * @var $subSectionsHTML string[]
	 */
	protected $subSection = [];

	/**
	 * Action name is slide
	 *
	 * @return string
	 */
	public function getName() {
		// This should be the same name as used when registering the
		// action in $wgActions.
		return 'slide';
	}

	/**
	 * Use the html of SlideShow.phtml as main page
	 *
	 * @return bool
	 */
	public function show() {
		$this->context->getOutput()->setArticleBodyOnly( true );
		$this->context->getOutput()->disable();
		$this->getArticle()->view();
		include __DIR__ . "/SlideShow.phtml";
		return true;
	}

	/**
	 * Use the same css/js as the other page of the wiki
	 * so other extension shall work
	 *
	 * @return string
	 */
	public function getHeaderScript() {
		return str_replace( 'async=""', '', $this->context->getOutput()->getRlClient()->getHeadHtml() );
	}

	/**
	 * Get summary
	 *
	 * @return string
	 */
	public function getTOCHTML() {
		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $this->context );
		$parserOutput = $this->getArticle()->getPage()->getParserOutput( $parserOptions, $this->getRev()->getId() );
		return $parserOutput->getTOCHTML();
	}

	/**
	 * Get current revision
	 *
	 * @return Revision
	 */
	public function getRev() {
		return MediaWikiServices::getInstance()
		->getRevisionLookup()
		->getRevisionByTitle( $this->getArticle()->getPage()->getTitle() );
	}

	/**
	 * Parse page name as slideshow title
	 *
	 * @return string
	 */
	public function getNiceTitle() {
		$title = explode( ':', $this->context->getOutput()->getTitle() );
		return array_pop( $title );
	}

	/**
	 * Get sections list
	 *
	 * @return Section[]
	 */
	public function getSections() {
		$parserOptions = $this->getArticle()->getPage()->makeParserOptions( $this->context );
		$parserOutput = $this->getArticle()->getPage()->getParserOutput( $parserOptions, $this->getRev()->getId() );
		return array_filter(
			$parserOutput->getSections(),
			/**
			 * Only h2 are slides
			 * @param $section []
			 * @return bool
			 */
			function ( $section ) {
				if ( $section["toclevel"] == 2 ) {
					// store subsection in memory
					$this->subSection[explode( ".", $section["number"] )[0]][] = $section;
				}
				return $section["toclevel"] == 1;
			}
		);
	}

	/**
	 * Retrieve the begining of a section
	 *
	 * @param string $html content
	 *
	 * @return string
	 */
	public function isNotEmpty( $html ) {
		return trim( strip_tags( $html, 'img' ) );
	}

	/**
	 * Retrieve the begining of a section
	 *
	 * @param Section|null $section index
	 *
	 * @return string
	 */
	public function getIntro( $section = null ) {
		if ( !$section ) {
			return explode( '<h2>', $this->context->getOutput()->getHTML() )[0] . '</div>';
		}
		$ending = '</div>';
		if ( isset( $this->subSection[$section['number']] ) ) {
			if ( count( $this->subSection[$section['number']] ) < 2 ) {
				$ending = "";
			}
		}
		return explode( '<h3>', $this->getSectionHTML( $section ) )[0] . $ending;
	}

	/**
	 * Generate html for a given section
	 *
	 * @param Section $section index
	 *
	 * @return string
	 */
	public function getSubSectionHTML( $section ) {
		if ( isset( $this->subSection[$section['number']] ) ) {
			$subSections = $this->subSection[$section['number']];
			$subSectionsHTML = array_map( [ $this, "getSectionHTML" ], $subSections );
			return implode( '</section><section>', $subSectionsHTML );
		}
		return '';
	}

	/**
	 * Generate html for a given section
	 *
	 * @param Section $section index
	 *
	 * @return string
	 */
	public function getSectionHTML( $section ) {
		$content = $this->getRev()->getContent( \MediaWiki\Revision\SlotRecord::MAIN );
		$template = $content->getSection( $section['index'] )->getText();
		$text = MediaWikiServices::getInstance()->getParser()->parse(
			$template,
			$this->getArticle()->getPage()->getTitle(),
			ParserOptions::newFromContext( $this->context ),
			true,
			true,
			$this->getRev()->getId()
		);
		return $text->getText();
	}

	/**
	 * Fetch author real name
	 *
	 * @return string[]
	 */
	public function getAuthor() {
		$articleID = $this->getArticle()->getID();
		$database = wfGetDB( DB_REPLICA );
		$links = [];

		$actorQuery = ActorMigration::newMigration()->getJoin( 'rev_user' );
		$fieldRevUser = $actorQuery['fields']['rev_user'];
		$result = $database->select(
			[ 'revision' => 'revision' ] + $actorQuery['tables'] + [ 'user' => 'user' ],
			[ 'user_id', 'user_name', 'user_real_name' ],
			[ 'rev_page' => $articleID, $fieldRevUser . ' > 0', 'rev_deleted = 0' ],
			__METHOD__,
			[ 'DISTINCT', 'ORDER BY' => 'user_name ASC' ],
			$actorQuery['joins'] + [ 'user' => [ 'JOIN', 'user_id = ' . $fieldRevUser ] ]
		);

		foreach ( $result as $row ) {
			$link = Linker::userLink( $row->user_id, $row->user_name, $row->user_real_name );
			$links[] = $link;
			if ( count( $links ) > 3 ) {
				break;
			}
		}

		return implode( ', ', $links );
	}
}
