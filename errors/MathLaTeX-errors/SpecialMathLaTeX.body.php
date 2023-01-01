<?php
/**
 * Controls debug output.
 * true = debug information is printed to a log file.
 * false = no debug information is printed.
 */
/**
 * SpecialMathLaTeX
 *
 * @brief Extends SpecialPage to add the 'MathLaTeX' Special Pages.
 *
 * @file
 * @name SpecialMathLaTeX
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
class SpecialMathLaTeX extends SpecialPage {
/**
 * __construct
 * 
 * @brief Constructor override
 *
 * @function
 * @name __construct
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	function __construct() {
		parent::__construct( 'MathLaTeX', 'mathlatex' );
	}

/**
 * execute
 * 
 * @brief Create buttons for gallery, packages, and wrapper pages
 *
 * @function
 * @name execute
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @param wikipage handle $par
 */
	function execute( $par ) {

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
		}

		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();
		$this->outputHeader();

		$this->displayForm();

		if ( $request->wasPosted()
			&& $this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			// gallery
			if ( $request->getVal( 'action' ) == 'gallery' ) {
				self::rebuildGallery ();
			} // if gallery
			if ( $request->getVal( 'action' ) == 'packages' ) {
				self::rebuildPackages ();
			} // if packages
			if ( $request->getVal( 'action' ) == 'wrapper' ) {
				self::rebuildWrapper();
			} // if packages
		} // if request

	} // execute

/**
 * displayForm
 * 
 * @brief Add buttons to Special:MathLaTeX
 *
 * @function
 * @name execute
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 *
 * @global string $wgArticlePath
 */
	protected function displayForm() {
		global $wgArticlePath;

		$output = $this->getOutput();

		// gallery element
		$output->addHTML(
			Xml::openElement(
			'form',
				array(
					'action' => $this->getTitle()->getLocalURL( 'action=gallery' ),
					'method' => 'post'
				)
			) .
			'<table><tr><td>' . // first row
			Xml::submitButton( $this->msg( 'mathlatex-rebuild-gallery' )->text() ) .
			'</td><td>' .
			'<a href=' .
			str_replace( '$1', wfMessage('mathlatex-galleryname') , $wgArticlePath ) .
			' title=' .
			wfMessage('mathlatex-galleryname') .
			'>' .
			wfMessage('mathlatex-galleryname') .
			'</a></td></tr>' .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			Xml::closeElement( 'form' )
		); // end gallery element

		// packages element
		$output->addHTML(
			Xml::openElement(
			'form',
				array(
					'action' => $this->getTitle()->getLocalURL( 'action=packages' ),
					'method' => 'post'
				)
			) .
			'<tr><td>' . // second row
			Xml::submitButton( $this->msg( 'mathlatex-rebuild-packages' )->text() ) .
			'</td><td>' .
			'<a href=' .
			str_replace( '$1', wfMessage('mathlatex-packagename') , $wgArticlePath ) .
			' title=' .
			wfMessage('mathlatex-packagename') .
			'>' .
			wfMessage('mathlatex-packagename') .
			'</a></td></tr>' .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			Xml::closeElement( 'form' )
		); // end package element

 		// end wrapper element
		$output->addHTML(
			Xml::openElement(
			'form',
				array(
					'action' => $this->getTitle()->getLocalURL( 'action=wrapper' ),
					'method' => 'post'
				)
			) .
			'<tr><td>' . // second row
			Xml::submitButton( $this->msg( 'mathlatex-rebuild-wrapper' )->text() ) .
			'</td><td>' .
			'<a href=' .
			str_replace( '$1', wfMessage('mathlatex-wrappername') , $wgArticlePath ) .
			' title=' .
			wfMessage('mathlatex-wrappername') .
			'>' .
			wfMessage('mathlatex-wrappername') .
			'</a></td></tr>' .
			'</table>' .
			Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() ) .
			Xml::closeElement( 'form' )
		); // end wrapper element

	} // displayForm

/**
 * rebuildGallery
 * 
 * @brief Update the MW_MATH_GALLERY page
 *
 * @function
 * @name rebuildGallery
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	public function rebuildGallery() {
		$status = Status::newGood();
		try {
			// create a sanitized filename and check
			// file, namespace for existence
			$ret = Title::newFromText( 'MW_MATH_GALLERY' , NS_MAIN );
			$edit_method = false;

			// check for file existence
			if ($ret == true ) {
				// Article with Title exists
				if( $ret->isKnown() == true ) {
				$edit_method = EDIT_UPDATE;
				} else {
				// Article with Title does not exist
					$edit_method = EDIT_NEW;
				}
			}
			// flush and rebuild text, that needs another
			$test_page = WikiPage::factory( $ret );

			// new page
			$content = new WikitextContent( self::createGalleryText() );
			$test_page->doEditContent( $content,
			"first edit",
			$edit_method ,
			false,
			User::newFromName( 'MW_MATH'),
			null );
		} catch ( MWException $e ) {
			//using raw, because $wgShowExceptionDetails can not be set yet
			wfDebugLog( 'MathLaTeX', wfMessage('mathlatex-gallery-failed') . $e->getMessage() );
			$status->fatal( wfMessage('mathlatex-gallery-failed'), $e->getMessage() );
		}
	} // rebuildGallery

/**
 * rebuildPackages
 * 
 * @brief Update the MW_MATH_PACKAGES page
 *
 * @function
 * @name rebuildPackages
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function rebuildPackages () {
		$status = Status::newGood();
		try {
			// create a sanitized filename and check
			// file namespace for existence
			$ret = Title::newFromText( 'MW_MATH_PACKAGES' , NS_MAIN );

			$edit_method = false;

			// Title creation successful
			if ($ret == true ) {
				// Article with Title exists
				if( $ret->isKnown() == true ) {
				$edit_method = EDIT_UPDATE;
				} else {
				// Article with Title does not exist
					$edit_method = EDIT_NEW;
				}
			}
			// flush and rebuild text, that needs another
			$test_page = WikiPage::factory( $ret );

			// new page
			$content = new WikitextContent( self::createPackagesText()  );

			$test_page->doEditContent( $content,
			'',
			$edit_method ,
			false,
			User::newFromName( 'MW_MATH'),
			null );
		} catch ( MWException $e ) {
			//using raw, because $wgShowExceptionDetails can not be set yet
			wfDebugLog( 'MathLaTeX', wfMessage('mathlatex-packages-failed') . $e->getMessage() );
			$status->fatal( wfMessage('mathlatex-packages-failed'), $e->getMessage() );
		}
	} // rebuildPackages

/**
 * rebuildWrapper
 * 
 * @brief Update the MW_MATH_WRAPPER page
 *
 * @function
 * @name rebuildWrapper
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function rebuildWrapper () {
		$status = Status::newGood();
		try {
			// create a sanitized filename and check
			// file namespace for existence
			$ret = Title::newFromText( 'MW_MATH_WRAPPER' , NS_MAIN );

			$edit_method = false;

			// Title creation successful
			if ($ret == true ) {
				// Article with Title exists
				if( $ret->isKnown() == true ) {
				$edit_method = EDIT_UPDATE;
				} else {
				// Article with Title does not exist
					$edit_method = EDIT_NEW;
				}
			}
			// flush and rebuild text, that needs another
			$test_page = WikiPage::factory( $ret );

			// new page
			$content = new WikitextContent( self::createWrapperText()  );

			$test_page->doEditContent( $content,
			'',
			$edit_method ,
			false,
			User::newFromName( 'MW_MATH'),
			null );
		} catch ( MWException $e ) {
			//using raw, because $wgShowExceptionDetails can not be set yet
			wfDebugLog( 'MathLaTeX', wfMessage('mathlatex-wrapper-failed') . $e->getMessage() );
			$status->fatal( wfMessage('mathlatex-wrapper-failed'), $e->getMessage() );
		}
	} // rebuildWrapper

/**
 * Private Functions
 */
/**
 * createGalleryText
 * 
 * @brief Create MW_MATH_GALLERY content
 *
 * @function
 * @name createGalleryText
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function createGalleryText() {

		$mBatchSize = 1000;

		$dbr = wfGetDB( DB_SLAVE );
		$numImages = 0;
		$output  = "<b>Last build date</b> " . date( 'd-M-o H:i' ) . "<br />\n";
		$output .= "{| class=\"wikitable\"\n";
		$output .= "!colspan=\"3\"|MW MATH GALLERY\n";
		$output .= "|-\n";
		$output .= "!Count\n";
		$output .= "!Filename\n";
		$output .= "!Equation\n";
		$output .= "|-\n";

		$res = $dbr->select( 'image', 'img_name',	array( 'img_user_text' => 'MW MATH'  ), __METHOD__, array( 'ORDER BY' => 'img_name', 'LIMIT' => $mBatchSize ) 	);

		foreach( $res as $row ) {
			$numImages++;
			$output .= "|$numImages\n|<nowiki>[[File:" . $row->img_name . "]]</nowiki>\n|[[File:" . $row->img_name .  "]]\n";
			$output .= "|-\n";
		}
			$output .= "|}";

		return $output;
	} // createGalleryText

/**
 * createPackagesText
 * 
 * @brief Create MW_MATH_PACKAGES content
 *
 * @function
 * @name createPackagesText
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function createPackagesText() {
		include 'config/latexpackages.php';

		$pre     = "<pre>";
		$pre_end = "</pre>";
		$pre_red = "<pre style=\"color: red\">";
		$pre_green = "<pre style=\"color: green\">";

		// start with the header
		$return_string = "<b>Last build date</b> " .  date( 'd-M-o H:i' ) . "<br />\n";
		$return_string .= $pre_green . wfMessage('mathlatex-req-packages') . $pre_end .
				"\n" .
			    $pre_red . wfMessage('mathlatex-miss-packages') . $pre_end .
				"\n" .
				$pre . wfMessage('mathlatex-extra-packages') . $pre_end .
				"\n";

		// dump all the packages into $out_array
		// this can be 45k or larger
		$out_array = array();
		$lib_array = array();
		$lamb = exec( 'cygcheck -c -d ' , $out_array );

		$lib_string = '';
		// support libs
		foreach( $seachlibs as $v ) {
			$result_array = self::array_match( '^'.$v, $out_array );
			if( is_array( $result_array ) == true ) {
				//found it, and there might be many
				$lib_array = array_merge( $lib_array, $result_array );
			}
		}

		// verify requirements
		foreach( $lib_array as $A ) {
			// $pieces[0] should match $B
			$pieces = explode(" ", $A);
			$found = false;
			// use $requiredlibs and pop each
			// item found or passed.
			foreach( $requiredlibs as $B ) {
				$comparison = strcasecmp( $B, $pieces[0] );
				// strings are equal, they match
				if( $comparison == 0 ) {
					$return_string .=  $pre_green . $A . $pre_end . "\n";
					array_shift ( $requiredlibs );
					array_shift ( $lib_array );
					$found = true;
					break;
				} elseif( $comparison < 0 ) {
					$return_string .=  $pre_red . $B . $pre_end  . "\n";
					array_shift ( $requiredlibs );
				}
			} // foreach 2
			if( $found == false ) {
				$return_string .=  '<pre>' . $A . '</pre>'  . "\n";
			}
		} // foreach 1
		return $return_string;
	} // createPackagesText

/**
 * createWrapperText
 * 
 * @brief Create MW_MATH_WRAPPER content
 *
 * @function
 * @name createWrapperText
 * @version 1.0
 * @author Jesse B. Dooley
 * @date January 8, 2016
 * @ingroup Extensions
 * @link http://mathlatex.sourceforge.net
 */
	private function createWrapperText() {
		$return_string  = "<b>Last build date</b> " .  date( 'd-M-o H:i' ) . "<br />\n";
		$return_string .= wfMessage('mathlatex-desc-wrapper') ."<br /><br />\n <nowiki>\n";
		$return_string .= MathLaTeXRender::wrapper("Equation Text Here.");
		$return_string .= "\n</nowiki>";
		return $return_string;
	} // createWrapperText


	// given string $needle and array haystack_array
	// make a case-insensitive search among the values.
	// on success - return an array with one or more values
	// on failure - returns false
	private function array_match( $needle, $haystack_array ){
		$matches = array();
		$matches = array_filter($haystack_array, function($var) use ($needle) { return preg_match("/$needle/i", $var); });
		if( count( $matches ) == 0 ) {
			return false;
		}
		return $matches;
	} // array_match
} // SpecialMathLaTeX
?>