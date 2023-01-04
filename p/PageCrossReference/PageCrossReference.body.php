<?php
/**
 *
 * @file
 * @class PageCrossReference PageCrossReference.php
 * @see README.TXT
 */

 if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'Not an entry point.' );
    }

class PageCrossReference {
    ///< Prevents doEdit from running onArticleSaveComplete twice
    private $wgPageCrossReferenceLoop;
    ///< Article namespace index
    private $wgPageCrossReferenceArticleNamespaceIndex;
    ///< Article namespace text
    private $wgPageCrossReferenceArticleNamespaceText;


    /**
     * Setup function, hooks the extension's functions to MediaWiki events.
     *
     * @param $wgPageCrossReferenceLoop
     * @return $wgPageCrossReferenceLoop set to false
     */
    public static function PageCrossReferenceSetup() {
        global $wgPageCrossReferenceLoop;
        $wgPageCrossReferenceLoop = false;
    } # end PageCrossReferenceSetup

    /**
     * Active on every gui save event. Used from v1.18 - 1.20.
     * When $revision is an object, an edit was made
     * When $revision is null, no edit was made
     * $text is the wiki text, no XML or HTML
     *
     * @param &$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId
     * @return true
     */
    public static function onArticleSaveComplete( &$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, $baseRevId ) {
        global $wgPageCrossReferenceArticleNamespaceIndex;

        /**
         * A valid $revision object means an edit was made
         * !$minoredit means it was a major edit.
         * if $minoredit, the author can cross-reference himself.
		 *
         */
        if ( $revision && !$minoredit ){
            /*!< Set wgPageCrossReferenceArticleNamespaceIndex to $article Namespace */
            $wgPageCrossReferenceArticleNamespaceIndex = $article->getTitle()->getNamespace(); # Gets Namespace Index

			# Verify that namespace is valid
			if( self::namespaceVerify($wgPageCrossReferenceArticleNamespaceIndex) == false ) {
				return true;
			}

            $parseArticle_result =  self::parseArticle( $article->getTitle()->getText(), $text );

            /**
             * doEdit invokes onArticleSaveComplete, thus repeating the whole method for no reason.
             * doQuickEdit does not invoke onArticleSaveComplete, but also doesn't update the new page links.
             * Use $wgPageCrossReferenceLoop as a global variable, ugh, to prevent onArticleSaveComplete
             * from executing twice.
             * Retest with doEditContent in MW 1.21
             */
             if( $parseArticle_result == true ) {
	            $status = $article->doEdit( $text, $summary, null );
             }

        } // end if

        return true;
    } // onArticleSaveComplete


    /**
     * Active on every gui save event. Used from v1.20+
     * When $revision is an object, an edit was made
     * When $revision is null, no edit was made
    public static function onPageContentSaveComplete( $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
        return true;
    }
     */

    /**
     * parseArticle
     * @description
     * Search $text_article and change matching page_titles.
     * If match, create Internal Link.
     *
     * @param $title, &$text_article
     * @return boolean
     */
    private static function parseArticle( $title, &$text_article ) {
        /*!< Begin Run Once Check */
        global $wgPageCrossReferenceLoop;

        if( $wgPageCrossReferenceLoop == true ) {
            return false;
        }
        $wgPageCrossReferenceLoop = true;
        /*!< End Run Once Check */
        $Hit = 0;

        $page_titles_array = self::fetchPageTitles( $title );

        $page_titles_count = count( $page_titles_array );

        $text_article_array = self::parseText( $text_article, self::buildPageParseString() );
        $text_article_array_count = count( $text_article_array );

        /*!< Cycle through each page_title */
        foreach( $page_titles_array as $k ) {
            if( self::parsePage( $text_article_array, $text_article_array_count, $k ) == true ) {
            	$Hit++;
            }
        } // end for 1: page_title

		/*!< Test OneHitTest */
        if( $Hit > 0  ){
			$text_article = implode( '', $text_article_array );
			return true;
		}

        /*!< return false */
        return false;
    } // parseArticle( $title, &$text_article )


    /**
     * parsePage
     * build title array
	 * match titles against text
	 *
	 * @param &$text_article_array, $text_article_array_count, $target_title
     * @return boolean
	 *
	 */
	private static function parsePage( &$text_article_array, $text_article_array_count, $target_title ) {
        /*!< build 4 page_title items with or without namespaces */
        $title_array = self::buildTitleReplacements( $target_title );

        /*!< test for page_title with spaces */
		return ( self::parseContent( $text_article_array,
		$text_article_array_count,
		$title_array['page_title_spaces'],
		$title_array['page_title_spaces_link'] ) ||

        /*!< test for page_title with underscores */
		self::parseContent( $text_article_array,
		$text_article_array_count,
		$title_array['page_title_underscore'],
		$title_array['page_title_underscore_link'] ) );
	} // parsePage


    /**
     * parseContent
     * Search $text_article_array for matching $page_title.
     * Update $text_article_array with the internal link once.
     *
     * @param  &$text_article_array, $text_article_array_count, $page_title, $replacement
     * @return boolean
     */
    private static function parseContent( &$text_article_array, $text_article_array_count, $page_title, $page_title_link ) {
        /*!< article loop */
        for( $article_index = 0; $article_index < $text_article_array_count; $article_index++)
        {
            /*!< Use $string for comparison */
            $string = $text_article_array[$article_index];
            /*!< test for whitespace or blank line */
            if( ctype_space( $string ) || $string == '' ) {
                continue;
            }

            /**
             * Replace Once, First Test - pre-existing internal link
             * The pre-existing internal link can be anywhere
             * This function assumes the author intended the link be here.
			 *
             */
            if( self::sub_string_cmp( $string, $page_title_link) == true )
            {
                return true;
            }

            /**
             * Tag Skipping.
             * All < > tags are on odd indexs, between the tags are on even indexes.
			 *
             */
            if( preg_match( '/<nowiki>|<pre>/ms', $string ) ) {
                $article_index += 2;
                /**
                 * Advance article_index to the closing tag continue moves
				 * the pointer to the for loop, which increments
                 * article_index to the next element.
				 *
                 */
                continue;
            } // end if 1

            /**
             * Even number test
             * even indexes will point to text that is not enclosed by brackets.
			 *
             */
            if( self::is_even( $article_index ) ) {
                /*!< Need a more sophisticated match to include subpages, namespaces */
                $result_string = self::parseLine( $string, $page_title, $page_title_link  );

                /*!< Test OneHitTest */
                if( strcmp( $string , $result_string ) != 0  ){
                    $text_article_array[$article_index] = $result_string;
                    return true;
                }
            } // end if % 2 check
        } // end for 1: article_text

        return false;
    } // end parseContent

    /**
     * parseLine
     * Match and replace in $Line with page_title having
     * space or underscore once.
     *
     * @param $page_titles_array, $Line
     * @return $Line
     */
    private static function parseLine( $Line, $page_title, $page_title_link )    {

		$Line_array = self::parseText( $Line, "/(" . $page_title . ")/" );

		// $Line_array[1] will have target text, first, and the only one we care about
		if( isset( $Line_array[1] ) ) {
			$Line_array[1] = $page_title_link;
		}

        /*!< return no matter what */
        return implode( '', $Line_array );
    } // parseLine


////////////////////////////////////
// Minor Methods
////////////////////////////////////

    /**
     *  buildTitleReplacements
     *  Produce page_title strings and internal links with
     *  and without spaces.
     *
     *  @param $page_title
     *  @return array
     */
    private static function buildTitleReplacements( $page_title ){
	    global $wgPageCrossReferenceArticleNamespaceIndex;
        global $wgExtraNamespaces;
        /*!< Check for Main Namespace, most common case */
        $page_title_spaces = str_replace('_', ' ', $page_title);
        $page_title_underscore = $page_title;
        $page_title_spaces_link = null;
        $page_title_underscore_link = null;
        if( $wgPageCrossReferenceArticleNamespaceIndex == 0 ) {
            $page_title_spaces_link = '[[' . $page_title_spaces .']]';
            $page_title_underscore_link = '[[' . $page_title_underscore .']]';
        } else { 
$page_title_spaces_link = "[[". $wgExtraNamespaces[$wgPageCrossReferenceArticleNamespaceIndex] . ":" . $page_title_spaces ."]]";
$page_title_underscore_link = "[[" . $wgExtraNamespaces[$wgPageCrossReferenceArticleNamespaceIndex] . ":" . $page_title_underscore ."]]";
		} 
        return array( 'page_title_spaces' => $page_title_spaces,
                      'page_title_underscore' => $page_title_underscore,
                      'page_title_spaces_link' => $page_title_spaces_link,
                      'page_title_underscore_link' => $page_title_underscore_link );
    } // buildTitleReplacements


    /**
     * parseText
     * Parse $text according to buildPageParseString and return.
     *
     * @see buildPageParseString
     * @param $text, $parse_key
     * @return array
     */
    private static function parseText( $text, $parse_key ) {
        return preg_split( $parse_key, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
    } // end parseText



    /**
     *  buildPageParseString
     *
     *  @param $wgPageCrossReferenceSkipHeaders
     *  @return regex parse string
     */
    private static function buildPageParseString() {
        global $wgPageCrossReferenceSkipHeaders;
        $header_delimiter = null;
        $tag_delimiter = null;
        $template_delimiter = null;
        /**
         * fetch the page titles with >= $wgPageCrossReferenceMinimumWordLength
         * and not in article title
         * and not in $wgPageCrossReferenceBlackList.
		 *
         */

        /*!< build the $parse_key to compare against the $text */
        if($wgPageCrossReferenceSkipHeaders){
            $header_delimiter = '=+.+?=+' . '|'; # headers
        }

        /*!< Do not seacher inside < > */
        $tag_delimiter = '<+.+?>+' . '|'; # tags

        /*!< Do not seacher inside {{ }} */
        $template_delimiter = '{{.+}}'. '|';

        $urlPattern = '[a-z]+?\:\/\/(?:\S+\.)+\S+(?:\/.*)?';

        /*!< make sure no || occurs, that will split by single characters */
        $parse_key = '/('.
                     $tag_delimiter .
                     $header_delimiter .
                     $template_delimiter .
                     '\[\[.*?\]\]'.        /*!< Skip internal links [[ ]] this one goes before [] */
                     '|'.
                     '\[.*?\]'.            /*!< Skip single [ ] */
                     '|'.
                     '(\<[^\>]+\>)([^\<]+)(\<\/[^\>]+\>)?'.
                     '|'.
                     '\n'. '|' .           /*!<  skip newline */
                     '\w+\/\w+'. '|' .     /*!< skip subpage or ratio with word/word match */
                     $urlPattern .
                     '\s.+?\]|'.
                     $urlPattern .
                     '(?=\s|$)|(?<=\b)\S+\@(?:\S+\.)+\S+(?=\b)'.
                     ')/i';

        return $parse_key;
    } // buildPageParseString


    /**
     *  fetchPageTitles
     *  Fetches page_titles and returns a ASC sorted list.
     *
     *  @param $article_title
     *  @return page_title(s) in an array
     */
    private static function fetchPageTitles( $article_title ) {
        global $wgPageCrossReferenceMinimumWordLength;
        global $wgPageCrossReferenceArticleNamespaceIndex;
        global $wgPageCrossReferenceBlackList;

        $black_list = null;
        $return_title_array = array();
        $res = null; /*!< result set */
        $namespacelimiter = null;

        /*!< replace space with underscore for the sql query */
        $article_title = str_replace( ' ', '_', $article_title );

        /**
         * Apply the BlackList
         * add article, add article namespace
         * assume page_titles have underscores for spaces.
		 *
         */
        $black_list_page_title = " NOT (page_title IN ( '" . $article_title;
        foreach( $wgPageCrossReferenceBlackList as $k  ){
            $black_list_page_title .= "','" . $k;
        }
        $black_list = $black_list_page_title ."') )";

        /*!< limit namespace to article's page_namespace */
        $namespacelimiter = " AND page_namespace = ". $wgPageCrossReferenceArticleNamespaceIndex . " ";

        /*!< Rejecting mediawiki's mysql wrapper because it doesn't work for complex queries */
        $dbr = wfGetDB( DB_MASTER );
        $sPageTable = $dbr->tableName( 'page' );
        $sSqlSelectFrom = "SELECT page_title FROM page WHERE ". $black_list. " ". $namespacelimiter ." AND page_title NOT LIKE '%/%' ORDER BY page_title ASC";
        /*!< Execute query */
        $res = $dbr->query($sSqlSelectFrom );

        /*!< fill the return array */
        while ($row = $dbr->fetchObject( $res ) ) {
            if( $wgPageCrossReferenceMinimumWordLength <= self::getWordCount( $row->page_title)) {
                $return_title_array[] = $row->page_title;
            }
        }
        /*!< Deallocate $res */
        $dbr->freeResult( $res );

        return $return_title_array;
    } // fetchPageTitles

	
	# Verify the Namespace argument is valid.
	# A valid Namespace index is 0, or in $wgExtraNamespaces and even.
	# initialize $Namespace_int and $Namespace_string
	# return true or false
	public static function namespaceVerify( $Namespace_int ) {
	    global $wgPageCrossReferenceArticleNamespaceText;
		global $wgExtraNamespaces;

		# Test for Main or an even entry in $wgExtraNamespaces
		if( ($Namespace_int == 0 || 
            isset( $wgExtraNamespaces[$Namespace_int] ) ) &&
			self::is_even( $Namespace_int)
          ) {
            $wgPageCrossReferenceArticleNamespaceText = $wgExtraNamespaces[$Namespace_int];
			return true;
		}
		return false;
	} // namespaceVerify


    /*!< Had to add these because php didn't */
    /**
     *  getWordCount
     *  Count spaces or underscores, add 1.
     *
     *  @param $article_title
     *  @return int
     */
    private static function getWordCount( $article_title ) {
        /*!< Check for null */
        if( !$article_title ) { return 0; }

        /*!< we have at least 1 word */
        return 1 + substr_count($article_title, '_');
    } // end getWordCount


    /**
     *  sub_string_cmp
     *  Substring search $test_string for $match.
     *
     *  @param $test_string, $match
     *  @return true, false
     */
    private static function sub_string_cmp( $test_string, $match )
    {
    /**
    * Prepend a char to the test string so any true result evaluates to >= 1
    * Because php === false doesn't evaluate to 0 in boolean statements
    * and strpos returns 0 if a match starts on the first char.
	*
    */
        $string = "*" . $test_string;

        $pos1 = strpos( $string, $match, 0 );

        return ( $pos1 > 0 );
    } //  sub_string_cmp

    /**
     *  is_odd
     *
     *  @param int
     *  @return true, false
     */
    private static function is_odd($number) { return($number & 1); }

    /**
     *  is_even
     *
     *  @param int
     *  @return true, false
     */
    private static function is_even($number) { return(!($number & 1)); }
} // end PageCrossReference class
?>