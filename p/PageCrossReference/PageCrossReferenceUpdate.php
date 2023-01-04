<?php
/**
 *
 * @file
 * @class PageCrossReferenceUpdate PageCrossReferenceUpdate.php
 * @see README.TXT
 */

# test for Maintenance.php
require_once( __DIR__ . '/Maintenance.php' );

class PageCrossReferenceUpdate extends Maintenance {
	public $Namespace_string;
	public $Namespace_int;
	public $MinimumWordLength_int;

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Add PageCrossReference links all articles in a given namespace.";
		$this->addOption( 'namespace', "Namespace index to search.", 0 );
		$this->addOption( 'minwordlength', "Min page_title words", 2 );
	} // end __construct


    public function execute() {
		global $Namespace_int;

		$page_title_array = array();

		# namespaceVerify tests for Namespace validity
		# and initializes Namespace_string and Namespace_int
		if( $this->namespaceVerify() == false ) {
			$this->error( " " . $Namespace_int . " is not a valid Namespace Index.\n", true );
		}

		# fetch page_titles in Namespace_int without Main_Page
		$page_title_array = $this->fetchPageTitlesNamespaces( $Namespace_int );

		# cycle through all page_titles in Namespace
		foreach( $page_title_array as $result ) {

		# Copy the title array
			$page_title_array_copy = $page_title_array;

	# pass the new $page_title_array, without $result to parseArticle
			if(($key = array_search($result, $page_title_array_copy)) !== false) {
				unset($page_title_array_copy[$key]);
			}

			$this->parseArticle( $result, $page_title_array_copy );
		}
    } // execute


    /**
     * parseArticle
     * @description
     * Search $article_text and change matching page_titles found in
	 * $page_title_array.
     * $page_title_array does not have $title_text.
     *
     * @param $title, $text_article
     * @return $text_article with or without internal links added
     */
    public function parseArticle( $title_text, $page_title_array ) {
		global $Namespace_string;
		$Hit = 0;
        $text_article_array = $this->fetchArticleText( $title_text );
        $text_article_array_count = count( $text_article_array );

        /*!< Cycle through each page_title */
		foreach( $page_title_array as $target_title ) {
            if( $this->parsePage( $text_article_array, $text_article_array_count, $target_title ) == true) {
				$Hit++;
			}
		} // end foreach

		$result_article = implode( '', $text_article_array );

		/*!< Test OneHitTest */
        if( $Hit > 0  ){
		echo " " . $Namespace_string . ":" .$title_text . " updated \n";
			$this->saveArticle( $Namespace_string .":".$title_text, $result_article );
		}
    } // parseArticle( $title_text, $page_title_array  )


    /**
     * parsePage
     * build title array
	 * match titles against text
	 *
	 * @param &$text_article_array, $text_article_array_count, $target_title
     * @return boolean
	 *
	 */
	public function parsePage( &$text_article_array, $text_article_array_count, $target_title ) {
        /*!< build 4 page_title items with or without namespaces */
        $title_array = $this->buildTitleReplacements( $target_title );

        /*!< test for page_title with spaces */
		return ( $this->parseContent( $text_article_array, 
		$text_article_array_count, 
		$title_array['page_title_spaces'], 
		$title_array['page_title_spaces_link'] ) ||
	
        /*!< test for page_title with underscores */
		$this->parseContent( $text_article_array,
		$text_article_array_count,
		$title_array['page_title_underscore'],
		$title_array['page_title_underscore_link'] ) );
	} // parsePage

    /**
     * parseContent
     * Search $text_article_array for matching $page_title.
     * Update $text_article_array with the internal link once.
     *
     * @param  &$text_article_array, $text_article_array_count, $page_title, $page_title_link
     * @return boolean
     */
    public function parseContent( &$text_article_array, $text_article_array_count, $page_title, $page_title_link ) {
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
             * This function assumes the author intended the link be there.
			 *
             */
            if( $this->sub_string_cmp( $string, $page_title_link) == true )
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
            if( $this->is_even( $article_index ) ) {
                /*!< Need a more sophisticated match to include subpages, namespaces */
                $result_string = $this->parseLine( $string, $page_title, $page_title_link );

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
     * @param $Line, $page_title, $page_title_link
     * @return $Line
     */
    public function parseLine( $Line, $page_title, $page_title_link ) {

		$Line_array = $this->parseText( $Line, "/(" . $page_title . ")/" );

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

	# Save article text given $title_text and $array_output
	# containing article text
	public function saveArticle($title_text, $array_output ) {
		$title = Title::newFromText( $title_text );
		$wiki = WikiPage::factory( $title );
		$wiki->doEdit( $array_output, "", null );
	}

    /**
     *  buildTitleReplacements
     *  Produce page_title strings and internal links with
     *  and without spaces.
     *
     *  @param $page_title
     *  @return array
     */
    public function buildTitleReplacements( $page_title ){
	    global $Namespace_int;
        global $wgExtraNamespaces;
        /*!< Check for Main Namespace, most common case */
        $page_title_spaces = str_replace('_', ' ', $page_title);
        $page_title_underscore = $page_title;
        $page_title_spaces_link = null;
        $page_title_underscore_link = null;
        if( $Namespace_int == 0 ) {
            $page_title_spaces_link = '[[' . $page_title_spaces .']]';
            $page_title_underscore_link = '[[' . $page_title_underscore .']]';
        } else { 
$page_title_spaces_link = "[[". $wgExtraNamespaces[$Namespace_int] . ":" . $page_title_spaces ."]]";
$page_title_underscore_link = "[[" . $wgExtraNamespaces[$Namespace_int] . ":" . $page_title_underscore ."]]";
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
     * @param $text
     * @return $text, parsed
     */
    public function parseText( $text, $parse_key ) {
        return preg_split( $parse_key, $text, -1, PREG_SPLIT_DELIM_CAPTURE );
    } // end parseText


	public function fetchArticleText( $title_text ) {
		global $Namespace_string;
		$article_text = shell_exec( 'php getText.php '. $Namespace_string .":". $title_text ) ;

        /*!< Build and fetch the Page parsing string */
        return $this->parseText( $article_text, $this->buildPageParseString() );
	}

    
    /**
     *  buildPageParseString
     *
     *  @return regex parse string
     */
    public function buildPageParseString() {
        $header_delimiter = null;
        $tag_delimiter = null;
        $template_delimiter = null;

        /*!< build the $parse_key to compare against the $text */
		// skip headers
        $header_delimiter = '=+.+?=+' . '|'; # headers

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
                     ')/';

        return $parse_key;
    } // buildPageParseString

    /**
	 * fetch all page_titles within $namespace
	 * return as an array
	 * This DOES NOT fetch Talk pages
	 *
	 */
	public function fetchPageTitlesNamespaces( $namespace ) {
		$dbw = wfGetDB( DB_MASTER );
		$res = $dbw->query( "SELECT page_title FROM page WHERE page_namespace = ". $namespace ." AND page_title <> 'Main_Page' AND page_is_redirect = 0 ORDER BY page_title ASC" );

		$set = array();
		foreach( $res as $result ) {
			$set[] = $result->page_title;
		}

		return $set;
	} // fetchPageTitlesNamespaces
	
	/**
	 * Verify the Namespace argument is valid.
	 * A valid Namespace index is 0, or in $wgExtraNamespaces and even.
	 * initialize $Namespace_int and $Namespace_string
	 * return true or false
	 *
	 */
	public function namespaceVerify( ) {
		global $Namespace_int;
		global $Namespace_string;
		global $wgExtraNamespaces;

		$Namespace_int = $this->getOption( 'namespace', 0 );
		$Namespace_string = MWNamespace::getCanonicalName( $Namespace_int );

		# Test for Main or an even entry in $wgExtraNamespaces
		if( ($Namespace_int == 0 ||
            isset( $wgExtraNamespaces[$Namespace_int] ) ) &&
			$this->is_even( $Namespace_int)
          ) {
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
    public function getWordCount( $article_title ) {
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
    public function sub_string_cmp( $test_string, $match ) {
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
    public function is_odd($number) { return($number & 1); }

    /**
     *  is_even
     *
     *  @param int
     *  @return true, false
     */
    public function is_even($number) { return(!($number & 1)); }
} // end PageCrossReferenceUpdate class


$maintClass = "PageCrossReferenceUpdate";
require_once( RUN_MAINTENANCE_IF_MAIN );
?>