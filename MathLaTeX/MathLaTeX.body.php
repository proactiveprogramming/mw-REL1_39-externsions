<?php
/**
 * MathLaTeX Class
 *
 * @brief MathLaTeX.body implements the MathLaTex class.
 *
 * @file
 * @name MathLaTeX.body
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
class MathLaTeX {
/**
 * setup
 *
 * @brief  Set up globals if they're not already set.
 * Check for directory access
 *
 * @file
 * @name setup
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $PHPpath
 * @global string $MathDotsPerInch
 * @return true
 */
	static function setup() {
		global $MathTempPath;
		global $PHPpath;
		global $MathDotsPerInch;

		// Initialize $MathTempPath
		$MathTempPath = self::setTmp();

		// Initialize $MathDotsPerInch to DEFAULT_DPI
		$MathDotsPerInch = DEFAULT_DPI;
		/**
		 * PHP_BINARY was changed from C:/xampp/php/php.exe
		 * to C:/xampp/apache/bin/httpd.exe.
		 */
		$PHPpath = self::phpBinary();

		// Test $MathTempPath for access
		if ( file_exists( $MathTempPath ) == false || is_writable( $MathTempPath ) == false )
		{
			wfDebugLog( 'MathLaTeX', 'MathLaTeX_body wgMathTempPath is inaccessible.' );
			return false;
		}
		// Test $PHPpath for access
		if ( file_exists( $PHPpath ) == false || is_executable( $PHPpath ) == false )
		{
			wfDebugLog( 'MathLaTeX', 'MathLaTeX_body wgPHPpath is inaccessible.' );
			return false;
		}

		return true;
	} // setup

/**
 * onPageContentSave
 *
 * @function
 * @name onPageContentSave
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathDotsPerInch
 * @global string $NamespaceWhiteList
 * @param WikiPage $page
 * @param User $user User object for the current user
 * @param Content $content New page content
 * @param string $summary Edit summary of the edit
 * @param bool $isMinor Was the edit marked as minor?
 * @param null $isWatch Unused.
 * @param null $section Unused.
 * @param int $flags Bitfield
 * @param Revision|null $revision New Revision object or null
 * @return bool True
 */
	public static function onPageContentSave( &$wikiPage, &$user, &$content, &$summary,	$isMinor, $isWatch, $section, &$flags, &$status ) {
		global $MathDotsPerInch;
		global $NamespaceWhiteList;

		// test for allowed Namespace
		if( in_array( $wikiPage->mTitle->getNamespace(), $NamespaceWhiteList ) == false ) {
			return true;
		}

		// fetch the artcile text
		$text = ContentHandler::getContentText( $content );
		// fetch the first position for the tag
		$text_pos = strpos ( $text , '<mathlatex' );

		// test if a tag is even in $text
		if( $text_pos === false ) {
			return true;
		}

		$equation_array = array();
		$equation_array = self::get_tag( 'mathlatex', $text );

// test if get_tag returned anything in the 2D array
		if( ( count( $equation_array ) > 0 ) == false ) {
			return true;
		}

// Now to cycle through $equation_array  with a foreach loop
		foreach ( $equation_array as $key => $value) {
			// process each in turn
			$current_equation_array = array();
			$current_equation_array = $value;

			// initialize
			$MathDotsPerInch = DEFAULT_DPI;

		// Maximizing equation reusability requires formating the author's
		// plain-text equation into some canonical form.
		// The form will be to trim whitespace fore and aft on each line.
		// Spaces and newlines inside the equation are the author's worry.
			$input = self::trimplaintext( $current_equation_array['equation'] );

			if ( strlen( $input ) == 0 ) { // No equation left.
				$msg =  "<span style=\"color:red\">MathLaTeX Error begin</span><br />\n" .
				"<span style=\"color:red\">MathLaTeX::body::onPageContentSave</span> No equation left<br />\n" .
				"Equation begin<br />\n" .
				$current_equation_array['equation'] .
				"Equation end<br />\n" .
				"<span style=\"color:red\">MathLaTeX Error end</span><br />\n";
				wfDebugLog( 'MathLaTeX', $msg );
				$text =  str_replace ( $current_equation_array['tag'] , $msg , $text  );
				continue;
			}

			// have a valid equation string
			// not to make the file name
			// test for	$MathDotsPerInch
			// All equation tests are positive
			// test the $current_equation_array for dpi
			if( isset( $current_equation_array['dpi'] ) == true){
				$MathDotsPerInch = $current_equation_array['dpi'];
			}

			// build the filename AFTER $MathDotsPerInch is set by the User
			$equation_filename = self::assembleFileName( $input );

			// build the equation directory by slicing the image tag off
			$equation_temp_path = explode ( '.',  $equation_filename )[0];

			// Does this name equal a file in the repository?
			// Repository::inRepository returns an array with image metadata
			// on found or false on not found.
			$inRepository_result = MathLaTeXRepository::inRepository( $equation_filename );

			// Repository::inRepository returns true on found
			// or false on not found

			if( $inRepository_result === false ) {
				// execute createImage
				$create_result = self::createImage( $equation_temp_path, $equation_filename, $input );
				if( is_string( $create_result ) == true ) {
					// createImage returned an error
					// return some error message$create_result;
					$msg = "<span style=\"color:red\">MathLaTeX Error begin</span><br />\n" .
					"<span style=\"color:red\">MathLaTeX::body::onPageContentSave createImage</span> failed<br />\n" .
					"Equation begin<br />\n" .
					$current_equation_array['equation'] .
					"Equation end<br />\n" .
					$create_result .
					"<span style=\"color:red\">MathLaTeX Error end</span>\n";
					wfDebugLog( 'MathLaTeX', $msg );
					$text =  str_replace ( $current_equation_array['tag'] , $msg , $text  );
					continue;
				}
			}

			// $equation_filename is valid
			// image was created or already exists
			// build the image tag
			$imagetag_string = self::buildImageTag( $equation_filename, $current_equation_array );

			// text replacement
			$text =  str_replace ( $current_equation_array['tag']  , $imagetag_string , $text  );

		} // foreach

		// update $content
		$content = $content->getContentHandler()->unserializeContent( $text );

		// Time to save the WikiPage
		self::saveWikiPage( $wikiPage, $content );

		return true; // always return true, well, because
	} // onPageContentSave


/**
 * onParserFirstCallInit
 *
 * @brief Register the <mathlatex> tag with the Parser.
 *
 * @function
 * @name onParserFirstCallInit
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param Parser $parser
 * @return Boolean: true
 */
	static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'mathlatex', array( 'MathLaTeX', 'init' ) );

		return true;
	} // onParserFirstCallInit

/**
 * init
 *
 * @brief Callback function for the <mathlatex> tag.
 *
 * @param string      $input - plain-text equation
 * @param array       $args - width, height, and dpi
 * @param Parser      $parser
 * @param PPFrame     $ppframe
 * @return string     $input
 */
	static function init( $input, array $args, Parser $parser, PPFrame $ppframe ) {
		return htmlspecialchars( $input );
	} // init

/**
 * onEditPageBeforeEditToolbar
 *
 * @brief Add the MathLaTeX button to the toolbar
 *
 * @function
 * @name onEditPageBeforeEditToolbar
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $wgOut
 * @param type $toolbar
 */
	static function onEditPageBeforeEditToolbar( &$toolbar ) {
		global $wgOut;
		$wgOut->addModules( array( 'ext.mathlatex.editbutton.enabler' ) );
	} // onEditPageBeforeEditToolbar

/**
 * private functions
 *
 */

/**
 * assembleFileName
 *
 * @brief Assemble and return the image filename.
 *
 * @function
 * @name assembleFileName
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 * 
 * @global string $MathDotsPerInch
 * @global string $MathImageExt
 * @global string $MathNameTag
 * @param string $equation
 * @return string 
 */
	 private function assembleFileName( $equation ) {
		global $MathDotsPerInch;
		global $MathImageExt;
		global $MathNameTag;
		return $MathNameTag . "_" . Title::newFromText( self::md5hash( $equation ) , NS_MAIN ) . "_" . $MathDotsPerInch . '.' . $MathImageExt;
	} // assembleFileName

/**
 * buildImageTag
 *
 * @brief Assemble the mathlatex tag from the 
 * user provided attributes.
 *
 * @function
 * @name buildImageTag
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param  string $content - equation plain-text, whitespace trimmed
 * @param  array $attributes - 'width', 'height', and 'dpi'
 * @return WikiText image tag or error message
 */
	private function buildImageTag( $content, array $attributes ){
	// most likely condition
		if( isset ( $attributes['width']  ) == false &&
			isset ( $attributes['height'] ) == false ) {
			return "[[Image:" . $content . "]]";
		}

	// 2nd most likely condition
		if( isset ( $attributes['width']  ) == true &&
			isset ( $attributes['height'] ) == false ) {
			return "[[Image:" . $content . "|".  $attributes['width'] ."px]]";
		}

	// 3rd most likely condition
		if( isset ( $attributes['width']  ) == true &&
			isset ( $attributes['height'] ) == true ) {
			return "[[Image:" . $content . "|".  $attributes['width'] ."x". $attributes['height'] ."px]]";
		}

	// least likely condition
		if( isset ( $attributes['width']  ) == false &&
			isset ( $attributes['height'] ) == true ) {
			return "[[Image:" . $content . "|x". $attributes['height'] ."px]]";
		}

		return "[[Image:" . $content . "]]";
	} // buildImageTag

/**
 * createImage
 *
 * @brief Render the LaTeX statement as an image.
 *
 * @function
 * @name createImage
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string - md5_dpi.png format
 * @param string - md5_dpi format
 * @param string - equation, trimmed.
 * @return string or bool - true on success
 */
	private function createImage( $equation_temp_path, $equation_filename, $equation ) {
		// create the subdirectory under $MathTempPath
		// all functions below assume the directory $MathTempPath/$file_name
		// exists
		$makedir_result = self::makedir( $equation_temp_path );

		if( is_string( $makedir_result ) == true ) {
			$msg = "<span style=\"color:red\">MathLaTeX::body::createImage makedir</span> failed<br>\n" . 
			$makedir_result;
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		$render_result = MathLaTeXRender::render( $equation_temp_path, $equation_filename, $equation );

		if( is_string( $render_result ) == true ) {
		// render failed, return error message
			$msg = "<span style=\"color:red\">MathLaTeX::body::createImage render</span> failed<br />\n" .
			$render_result ;
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// add it to the repository
		// returns true on success or error message on failure
		$add_result = MathLaTeXRepository::add( $equation_temp_path, $equation_filename, $equation );

		// Repository::inRepository returns true on found
		// or a string on failure
		if( is_string( $add_result ) == true ) {
			// return the error message
			$msg = "<span style=\"color:red\">MathLaTeX::body::createImage add</span> failed<br />\n" .
			$add_result;
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// delete the temporary directory
		$delete_result = self::delete( $equation_temp_path );
		if( is_string( $delete_result ) == true ) {
			// return the error message
			$msg = "<span style=\"color:red\">MathLaTeX:body:createImage delete</span> failed<br />\n" .
			$delete_result;
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		return true;
	} // createImage

/**
 * delete
 *
 * @brief Delete the directory and files in $equation_temp_path.
 *
 * @function
 * @name delete
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @global string $MathDebug
 * @param string $equation_temp_path - case insensitive
 * @return string error message upon failure
 * @return bool true upon success
 */
	private function delete( $equation_temp_path ) {
		global $MathTempPath;
		global $MathDebug;

		// do not delete files when Debug is enabled.
		if( $MathDebug == true ) {
			return true;
		}

		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath ) == false ) {
			$msg = "<span style=\"color:red\">MathLaTeX:body:delete directory</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// put log file code AFTER chdir or the log isn't created

		// have the input file.
		// assemble the latex call
		$cmd = 'rm -f -r ' .    // rm with no prompt
		$equation_temp_path ; // source directory

		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if rm was successful.
		if( file_exists( $equation_temp_path ) == true ) {
			$msg = "<span style=\"color:red\">MathLaTeX::body::delete directory</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"rm result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // delete


/**
 * makedir
 *
 * @brief Register the <mathlatex> tag with the Parser.
 *
 * @function
 * @name makedir
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $MathTempPath
 * @param string $equation_temp_path 
 * @return string error message upon failure
 * @return bool true upon success
 */
	private function makedir( $equation_temp_path ) {
		global $MathTempPath;

		// change current dir to $MathTempPath
		if( chdir ( $MathTempPath ) == false ) {
			$msg = "<span style=\"color:red\">MathLaTeX:body:makedir chdir</span> failed<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}

		// if dir already exists, return true
		if( file_exists( $equation_temp_path ) == true ) {
			return true;
		}

		// put log file code AFTER chdir or the log isn't created

		$cmd = 'mkdir ' .      // make directory
		$equation_temp_path;   // use file name without the tag
		
		$retval = null;
		$contents = wfShellExec( $cmd, $retval );

		// verify if makedir was successful.
		if( file_exists( $equation_temp_path ) == false ) {
			$msg = "<span style=\"color:red\">MathLaTeX::body::makedir mkdir</span> failed<br />\n" .
			"cmd " . $cmd . "<br />\n" .
			"retval " . $retval . "<br />\n" .
			"mkdir result " . $contents . "<br />\n";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		} else {
			return true;
		}
	} // makedir

/**
 * phpBinary
 *
 * @brief Determine the path to php.exe used by XAMPP and 
 * return it.
 *
 * @function
 * @name phpBinary
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $IP
 * @return string path to php.exe
 */
	private function phpBinary() {
		global $IP;
		$IP_slashforward = self::slashforward( $IP );
		$IP_explode_array = explode( '/', $IP_slashforward );
		return $IP_explode_array[0] . '/' . $IP_explode_array[1] . '/php/php.exe';
	} // phpBinary


/**
 * saveWikiPage
 *
 * @brief Save the wikiPage.
 *
 * @function
 * @name saveWikiPage
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param &wikiPage
 * @param string page content
 * @return string error message upon failure
 * @return bool true upon success
 */
	 private function saveWikiPage( &$wikiPage, $content ) {
	 	try {
			// grab the Title
			$page_Title = $wikiPage->getTitle();
			$edit_method = false;
			// check for file existence
			if( ($page_Title && $page_Title->isKnown()) == true ) {
				$edit_method = EDIT_UPDATE;
			} else {
			// Article with Title does not exist
				$edit_method = EDIT_NEW;
			}
			$wikiPage->doEditContent( $content, '', 0, false, null, null );
		} catch ( MWException $e ) {
			$msg = "<span style=\"color:red\">MathLaTeX:body:saveWikiPage</span> failed<br />\n" .
			$e ."\n<br />";
			wfDebugLog( 'MathLaTeX', $msg );
			return $msg;
		}
		return true;
	} // saveWikiPage

/**
 * slashforward
 *
 * @brief Because I couldn't find a slick regex expression
 * to replace unknown numbers of \ and / with one /
 * two while loops were used.
 *
 * TODO: find a slick regex expression to replace
 * this function
 *
 * @function
 * @name slashforward
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string 
 * @return string with backslashes changed to forward slashes and duplicates removed
 */
	private function slashforward( $str ) {
		while ( strpos( $str, "\\") !== false ){
					$str = str_replace('\\', '/', $str);
		}

		while ( strpos( $str, "//") !== false ){
					$str = str_replace('//', '/', $str);
		}

		return $str;
	} // slashforward

/**
 * md5hash
 *
 * @brief md5 hash generator. returns the md5 hash for a given
 * string.
 *
 * @function
 * @name md5hash
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param $inputstring as any char string
 * @return md5 hash without raw binary for that string
 */
	private function md5hash( $inputstring ) {
		return md5( $inputstring, false );
	} // md5hash

/**
 * setTmp
 * 
 * @brief Fetch the tempdir and set \ to /
 *
 * @function
 * @name setTmp
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function setTmp() {
		return self::slashforward ( wfTempDir() );
	} // setTmp

/**
 * get_tag
 *
 * @brief Extract equations and attributes from the wikipage
 *
 * @function
 * @name get_tag
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string tags to fetch
 * @param string wikipage 
 * @return array equations and tags in the array
 */
	private function get_tag( $tag, $xml ) {

		preg_match_all('{<'.$tag.'[^>]*>\s*((?s).*?)\s*</'.$tag.'>}', $xml, $matches, PREG_SET_ORDER);

		// run through add_attributes
	 	foreach ($matches as $key => $value) {
	 		$attrib_result = self::add_attributes( $value );
	 		if( is_array( $attrib_result ) === true ){
	 			$matches[$key] = $attrib_result;
			}
			$matches[$key]['tag'] = $matches[$key][0];
			$matches[$key]['equation'] = $matches[$key][1];
			unset( $matches[$key][0] );
			unset( $matches[$key][1] );
		}

	  return $matches;
	} // get_tag

/**
 * add_attributes
 *
 * @brief Helper function for get_tag that assembles
 * tags and attributes into an array.
 *
 * @function
 * @name add_attributes
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param array tags
 * @return array with tags and attributes sorted
 */
	// return false on failure or an array on success
	private function add_attributes( array $tag_array ) {
		// $tag_array[0] has the whole tag
		// $tag_array[1] has the equation
		// test it for attributes
		$equation_string = $tag_array[0];
		if (preg_match('/(width|height|dpi)/',$equation_string) != 1 ) {
			// nothing to do, return false
			return false;
		} // if
		// at least 1 attribute was detected
		// parse and add to $tag_array
		// then return $tag_array
		preg_match_all("/(width|height|dpi)=([^>,= ]+)/", $equation_string, $r );
		// [1] names [2] values
		$names = $r[1];
		$values = $r[2];
		// foreach array using parallel arrays
		// $key is 0-2, $values are
		foreach ($names as $key => $value) {
			$tag_array[$value] = str_replace(array('\'', '"'), '', $values[$key] );
		}
		return $tag_array;
	} // add_attributes

/**
 * trimplaintext
 *
 * @brief Trim whitespace from the string
 *
 * @function
 * @name trimplaintext
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param string  
 * @return string with whitespace removed
 */
	private function trimplaintext( $equation ) {
		$equation_return = '';
		$deliminator_string = PHP_EOL;
		$equation_explode = explode( $deliminator_string , $equation );

		foreach ($equation_explode as $v) {
			$line_trimmed = trim ( $v, WHITESPACE_REGEX );
			$equation_return .= $line_trimmed . PHP_EOL;
		}

		// trim the trailing "\n"
		$equation_return = trim ( $equation_return, PHP_EOL );

		return $equation_return;
	} // trimplaintext


} // MathLaTeX
?>